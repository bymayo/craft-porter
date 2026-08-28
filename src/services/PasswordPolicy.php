<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\assetbundles\porter\PorterPasswordAsset;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\View;

use GuzzleHttp\Exception\GuzzleException;

class PasswordPolicy extends Component
{

   /**
    * Have I Been Pwned range endpoint (k-anonymity).
    */
   const PWNED_ENDPOINT = 'https://api.pwnedpasswords.com/range/';

   /**
    * Hard ceiling on how many previous passwords we'll compare against, so a
    * silly setting can't turn every password change into a bcrypt marathon.
    */
   const HISTORY_MAX = 24;

   private $settings;

   public function init(): void
   {
      $this->settings = Porter::getInstance()->helper->settings();
   }

   // Exemptions
   // =========================================================================

   /**
    * Whether this user sits outside the password policy entirely — both the
    * rules and forced expiry resets.
    */
   public function isExempt(?User $user = null): bool
   {

      if (!$user)
      {
         return false;
      }

      if ($this->settings->passwordExemptAdmins && $user->admin)
      {
         return true;
      }

      return $this->_inGroups($user, $this->settings->passwordExemptGroups);

   }

   // Rules
   // =========================================================================

   /**
    * Whether the length and character rules are switched on.
    */
   public function lengthRulesEnabled(): bool
   {
      return (bool) $this->settings->passwordForcePolicy;
   }

   /**
    * Whether the blocklist is switched on, and has anything to block.
    */
   public function blocklistEnabled(): bool
   {

      if (!$this->settings->passwordBlocklist)
      {
         return false;
      }

      // 'substitutions' only changes how the other sources are matched, so
      // on its own there's nothing to block.
      $hasSource = (bool) array_intersect(['userDetails', 'siteName'], $this->blocklistSources());

      return $hasSource || $this->_customBlocklistWords() !== [];

   }

   /**
    * Whether password history is switched on.
    */
   public function historyEnabled(): bool
   {
      return (bool) $this->settings->passwordHistory;
   }

   /**
    * Whether breach checking is switched on.
    */
   public function pwnedEnabled(): bool
   {
      return (bool) $this->settings->passwordPwned;
   }

   /**
    * Whether the strength indicator should be shown.
    */
   public function strengthIndicatorEnabled(): bool
   {
      return (bool) $this->settings->passwordStrengthIndicator;
   }

   /**
    * The blocklist sources that are switched on.
    */
   public function blocklistSources(): array
   {

      $sources = $this->settings->passwordBlocklistSources;

      if (!is_array($sources))
      {
         return [];
      }

      return array_values(array_intersect(['userDetails', 'siteName', 'substitutions'], $sources));

   }

   /**
    * Whether any password rule at all is active.
    *
    * Each feature stands on its own — you can run breach checking without
    * enforcing symbols, or history without a length rule.
    */
   public function isEnabled(): bool
   {

      return $this->lengthRulesEnabled()
         || $this->blocklistEnabled()
         || $this->historyEnabled()
         || $this->pwnedEnabled()
         || $this->minScore() > 0;

   }

   /**
    * The minimum length, floored at Craft's own minimum of 6.
    */
   public function minLength(): int
   {
      return max(6, (int) $this->settings->passwordForcePolicyMin);
   }

   /**
    * The maximum length, or null when it shouldn't be enforced.
    */
   public function maxLength(): ?int
   {

      $max = (int) $this->settings->passwordForcePolicyMax;

      if ($max <= 0 || $max <= $this->minLength())
      {
         return null;
      }

      return min(160, $max);

   }

   /**
    * The enabled character class rules.
    */
   public function characterRules(): array
   {

      $rules = $this->settings->passwordForcePolicyRules;

      if (!is_array($rules))
      {
         return [];
      }

      return array_values(array_intersect(['lowercase', 'uppercase', 'numeric', 'symbol'], $rules));

   }

   /**
    * Structured descriptors for every active rule.
    *
    * Shared by the Twig helpers and the strength indicator JS, so the
    * front-end checklist and the server-side validation can never drift.
    */
   public function describeRules(): array
   {

      $descriptors = [];

      if ($this->lengthRulesEnabled())
      {

         $min = $this->minLength();

         $descriptors[] = [
            'key' => 'minLength',
            'label' => Craft::t('porter', 'At least {min} characters.', ['min' => $min]),
            'min' => $min
         ];

         $max = $this->maxLength();

         if ($max)
         {
            $descriptors[] = [
               'key' => 'maxLength',
               'label' => Craft::t('porter', 'No more than {max} characters.', ['max' => $max]),
               'max' => $max
            ];
         }

         $patterns = $this->_characterPatterns();
         $labels = $this->_characterLabels();

         foreach ($this->characterRules() as $rule)
         {
            $descriptors[] = [
               'key' => $rule,
               'label' => $labels[$rule],
               'pattern' => $patterns[$rule]
            ];
         }

      }

      if ($this->blocklistEnabled())
      {
         $descriptors[] = [
            'key' => 'blocklist',
            'label' => Craft::t('porter', 'Doesn’t contain your personal details or other easily guessed words.')
         ];
      }

      if ($this->historyEnabled())
      {
         $descriptors[] = [
            'key' => 'history',
            'label' => Craft::t('porter', 'Isn’t one of your last {count} passwords.', ['count' => $this->historyCount()])
         ];
      }

      if ($this->pwnedEnabled())
      {
         $descriptors[] = [
            'key' => 'pwned',
            'label' => Craft::t('porter', 'Hasn’t appeared in a known data breach.')
         ];
      }

      $minScore = $this->minScore();

      if ($minScore > 0)
      {
         $descriptors[] = [
            'key' => 'strength',
            'label' => Craft::t('porter', 'Is rated at least {label}.', ['label' => $this->scoreLabel($minScore)]),
            'minScore' => $minScore
         ];
      }

      return $descriptors;

   }

   /**
    * A single human readable sentence describing the policy.
    */
   public function message(): string
   {

      $labels = array_column($this->describeRules(), 'label');

      if (!$labels)
      {
         return '';
      }

      return implode(' ', $labels);

   }

   /**
    * How many previous passwords to remember.
    */
   public function historyCount(): int
   {
      return max(1, min(self::HISTORY_MAX, (int) $this->settings->passwordHistoryCount));
   }

   /**
    * The minimum strength score required, or 0 when not enforced.
    */
   public function minScore(): int
   {

      // The score sits inside the strength section, so it only applies when
      // that section is on — nobody should be rejected for a weak password
      // without the meter there to show them why.
      if (!$this->strengthIndicatorEnabled())
      {
         return 0;
      }

      return max(0, min(4, (int) $this->settings->passwordStrengthMinScore));

   }

   // Validation
   // =========================================================================

   /**
    * Validates a password against every enabled rule and returns the errors.
    *
    * Length is handled separately by Craft's own UserPasswordValidator (see
    * bymayo\porter\rules\UserRules), so it isn't repeated here.
    */
   public function check($password, ?User $user = null): array
   {

      $errors = [];

      if ($this->isExempt($user))
      {
         return $errors;
      }

      if ($password === null || $password === '')
      {
         return $errors;
      }

      // Tracked separately from the other errors: the strength score is
      // derived from length and character variety, so a "too weak" message
      // on top of "too short" or "needs a symbol" tells the user nothing
      // they aren't already being told.
      $compositionFailed = false;

      if ($this->lengthRulesEnabled())
      {

         $length = strlen($password);
         $max = $this->maxLength();

         if ($length < $this->minLength() || ($max && $length > $max))
         {
            // Craft's own UserPasswordValidator reports the length itself,
            // so nothing is added here — it just counts as a failure.
            $compositionFailed = true;
         }

         foreach ($this->characterRules() as $rule)
         {
            if (!preg_match('/' . $this->_characterPatterns()[$rule] . '/u', $password))
            {
               $errors[] = $this->_characterErrors()[$rule];
               $compositionFailed = true;
            }
         }

      }

      if ($this->blocklistEnabled())
      {

         $match = $this->blocklistMatch($password, $user);

         if ($match !== null)
         {
            $errors[] = Craft::t('porter', 'Password can’t contain “{word}”.', ['word' => $match]);
         }

      }

      $minScore = $this->minScore();

      if ($minScore > 0 && !$compositionFailed && $this->score($password) < $minScore)
      {
         $errors[] = Craft::t(
            'porter',
            'Password is too weak. It needs to be rated at least {label}.',
            ['label' => $this->scoreLabel($minScore)]
         );
      }

      if ($this->historyEnabled() && $user && $user->id)
      {

         if (Porter::getInstance()->passwordRetention->isReused($password, $user))
         {
            $errors[] = Craft::t(
               'porter',
               'Password has been used recently. Please choose one that isn’t among your last {count} passwords.',
               ['count' => $this->historyCount()]
            );
         }

      }

      return $errors;

   }

   // Blocklist
   // =========================================================================

   /**
    * Returns the first blocked word found in the password, or null.
    */
   public function blocklistMatch($password, ?User $user = null): ?string
   {

      $substitutions = in_array('substitutions', $this->blocklistSources(), true);

      $haystack = $substitutions ? $this->_normalise($password) : strtolower((string) $password);

      if ($haystack === '')
      {
         return null;
      }

      foreach ($this->blocklistWords($user) as $word)
      {

         $needle = $substitutions ? $this->_normalise($word) : strtolower((string) $word);

         if (strlen($needle) < 3)
         {
            continue;
         }

         if (str_contains($haystack, $needle))
         {
            return $word;
         }

      }

      return null;

   }

   /**
    * Every word the password isn't allowed to contain.
    */
   public function blocklistWords(?User $user = null): array
   {

      $words = [];

      if (in_array('userDetails', $this->blocklistSources(), true) && $user)
      {

         $words[] = $user->username;
         $words[] = $user->firstName;
         $words[] = $user->lastName;

         if ($user->email)
         {
            $words[] = strtok($user->email, '@');
         }

      }

      if (in_array('siteName', $this->blocklistSources(), true))
      {

         try {
            $words[] = Craft::$app->getSites()->getPrimarySite()->getName();
         } catch (\Throwable $e) {
            // No sites yet (install time) — nothing to block.
         }

      }

      $words = array_merge($words, $this->_customBlocklistWords());

      $words = array_map('trim', array_filter($words, fn($word) => is_string($word) && trim($word) !== ''));

      return array_values(array_unique($words));

   }

   // Strength
   // =========================================================================

   /**
    * Scores a password from 0 (very weak) to 4 (strong).
    *
    * A deliberately small entropy estimate, mirrored character for character
    * by PorterPasswordStrength.js. The JS copy is advisory only — this one is
    * what actually gets enforced.
    */
   public function score($password): int
   {

      if ($password === null || $password === '')
      {
         return 0;
      }

      $length = strlen($password);
      $pool = 0;

      if (preg_match('/[a-z]/', $password)) { $pool += 26; }
      if (preg_match('/[A-Z]/', $password)) { $pool += 26; }
      if (preg_match('/[0-9]/', $password)) { $pool += 10; }
      if (preg_match('/[^a-zA-Z0-9]/', $password)) { $pool += 33; }

      if ($pool === 0)
      {
         return 0;
      }

      $entropy = $length * log($pool, 2);

      // A password that's a known-common one gets capped hard, however long it is.
      if ($this->isCommon($password))
      {
         $entropy = min($entropy, 12);
      }

      // Penalise runs of the same character, and ascending/descending runs.
      $entropy -= $this->_longestRepeatRun($password) * 2;
      $entropy -= $this->_longestSequenceRun($password) * 2;

      // Penalise low character variety ("aaaabbbb" shouldn't score like "a1b2c3d4").
      $distinct = count(array_unique(str_split(strtolower($password))));
      $entropy *= max(0.5, min(1, $distinct / $length));

      if ($entropy < 28) { return 0; }
      if ($entropy < 36) { return 1; }
      if ($entropy < 60) { return 2; }
      if ($entropy < 80) { return 3; }

      return 4;

   }

   /**
    * A label for a strength score.
    */
   public function scoreLabel(int $score): string
   {

      return match ($score) {
         0 => Craft::t('porter', 'Very weak'),
         1 => Craft::t('porter', 'Weak'),
         2 => Craft::t('porter', 'Fair'),
         3 => Craft::t('porter', 'Good'),
         default => Craft::t('porter', 'Strong')
      };

   }

   /**
    * Whether the password appears in the bundled common password list.
    */
   public function isCommon($password): bool
   {
      return in_array($this->_normalise($password), $this->commonPasswords(), true);
   }

   /**
    * The bundled common password list, normalised.
    *
    * Owned here so the PHP scorer and the JS indicator can never disagree —
    * the JS receives this exact list as config.
    */
   public function commonPasswords(): array
   {

      return [
         'password', 'passw0rd', 'password1', 'password123', 'letmein', 'welcome', 'welcome1',
         'qwerty', 'qwertyuiop', 'qwerty123', 'azerty', 'monkey', 'dragon', 'sunshine',
         'princess', 'football', 'baseball', 'basketball', 'superman', 'batman', 'iloveyou',
         'trustno1', 'starwars', 'whatever', 'freedom', 'shadow', 'master', 'michael',
         'jennifer', 'jordan', 'harley', 'ranger', 'hunter', 'buster', 'soccer', 'hockey',
         'killer', 'george', 'charlie', 'andrew', 'thomas', 'robert', 'daniel', 'matthew',
         'joshua', 'ashley', 'bailey', 'access', 'flower', 'pepper', 'ginger', 'summer',
         'winter', 'chelsea', 'liverpool', 'arsenal', 'admin', 'administrator', 'root',
         'guest', 'test', 'testing', 'default', 'changeme', 'secret', 'login', 'abc123',
         'abcdef', 'abcd1234', 'a1b2c3', '123456', '1234567', '12345678', '123456789',
         '1234567890', '12345', '111111', '000000', '654321', '121212', '696969', '987654321',
         'zxcvbn', 'zxcvbnm', 'asdfgh', 'asdfghjkl', 'qazwsx', '1q2w3e4r', '1qaz2wsx',
         'iloveyou1', 'computer', 'internet', 'samsung', 'google', 'facebook', 'craftcms',
         'letmein1', 'passwordpassword', 'nothing', 'cheese', 'banana', 'orange', 'purple',
         'chocolate', 'diamond', 'phoenix', 'silver', 'yellow', 'london', 'newyork', 'canada'
      ];

   }

   // Have I Been Pwned
   // =========================================================================

   /**
    * Whether a password appears in the Have I Been Pwned corpus.
    *
    * Returns true (breached), false (not breached) or null (couldn't tell).
    * Only the first five characters of the SHA-1 hash ever leave the server.
    */
   public function pwned($password): ?bool
   {

      $hash = strtoupper(sha1($password));
      $prefix = substr($hash, 0, 5);
      $suffix = substr($hash, 5);

      try {

         $client = Craft::createGuzzleClient([
            'headers' => [
               'Add-Padding' => 'true'
            ],
            // Always verify TLS here regardless of any site-level guzzle
            // config override — this check is only meaningful over a
            // verified channel.
            'verify' => true,
            'timeout' => 5,
            'connect_timeout' => 3
         ]);

         $response = $client->request('GET', self::PWNED_ENDPOINT . $prefix);
         $body = $response->getBody()->getContents();

         foreach (preg_split('/\r\n|\r|\n/', $body) as $line)
         {

            $candidate = strtok(trim($line), ':');

            if ($candidate === $suffix)
            {
               return true;
            }

         }

         return false;

      } catch (GuzzleException | \Throwable $e) {

         Porter::warn('[Password Policy] Pwned Passwords lookup failed: ' . $e->getMessage());

         return null;

      }

   }

   /**
    * Whether an unreachable Pwned Passwords API should block the password.
    */
   public function pwnedFailsClosed(): bool
   {
      return $this->settings->passwordPwnedFailMode === 'closed';
   }

   // Indicator config
   // =========================================================================

   /**
    * The config handed to the strength indicator JS.
    */
   public function indicatorConfig(?User $user = null): array
   {

      // The blocklist can only be checked client side when we have the full
      // word list. In the control panel an admin may be editing somebody
      // else, so the user's own details aren't sent — better to show that
      // rule as "checked on save" than to show a ✓ the server will reject.
      $blocklistCheckable = !$this->blocklistEnabled()
         || !in_array('userDetails', $this->blocklistSources(), true)
         || $user !== null;

      return [
         'rules' => $this->describeRules(),
         'minScore' => $this->minScore(),
         'common' => $this->commonPasswords(),
         'blocklistCheckable' => $blocklistCheckable,
         'blocklistSubstitutions' => in_array('substitutions', $this->blocklistSources(), true),
         'blocklist' => $this->blocklistEnabled() ? $this->blocklistWords($user) : [],
         'labels' => [
            0 => $this->scoreLabel(0),
            1 => $this->scoreLabel(1),
            2 => $this->scoreLabel(2),
            3 => $this->scoreLabel(3),
            4 => $this->scoreLabel(4)
         ],
         'strengthLabel' => Craft::t('porter', 'Password strength'),
         'deferredLabel' => Craft::t('porter', 'Checked when you save:')
      ];

   }

   /**
    * Registers the indicator JS/CSS and its config, once per request.
    */
   public function registerIndicatorAssets(?User $user = null): void
   {

      $view = Craft::$app->getView();

      $view->registerAssetBundle(PorterPasswordAsset::class);

      $options = [];

      if ($this->settings->passwordCspNonce)
      {
         $options['nonce'] = Porter::getInstance()->security->getNonce();
      }

      $view->registerScript(
         'window.porterPasswordPolicy = ' . Json::encode($this->indicatorConfig($user)) . ';',
         View::POS_HEAD,
         $options,
         'porter-password-policy-config'
      );

   }

   /**
    * Default properties for the front-end indicator component.
    */
   public function defaultIndicatorProperties(): array
   {

      return [
         'field' => 'newPassword',
         'containerClass' => 'porter__password-strength',
         'showRules' => true
      ];

   }

   /**
    * Renders the front-end indicator component.
    */
   public function renderIndicatorTemplate($properties)
   {

      // The same switch governs both surfaces, so a site that turns the
      // indicator off doesn't get an empty meter left behind on the front end.
      if (!$this->strengthIndicatorEnabled() || !Craft::$app->getRequest()->getIsSiteRequest())
      {
         return null;
      }

      $defaults = $this->defaultIndicatorProperties();
      $properties = $properties ? array_merge($defaults, $properties) : $defaults;

      $this->registerIndicatorAssets(Craft::$app->getUser()->getIdentity());

      $view = Craft::$app->getView();

      $templatePath = $view->getTemplatesPath();

      $view->setTemplatesPath(Porter::getInstance()->getBasePath());

      $template = $view->renderTemplate('/templates/components/passwordStrengthIndicator', $properties);

      $view->setTemplatesPath($templatePath);

      return Template::raw($template);

   }

   // Private Methods
   // =========================================================================

   /**
    * The custom banned words, split and tidied.
    */
   private function _customBlocklistWords(): array
   {

      if (!$this->settings->passwordBlocklistWords)
      {
         return [];
      }

      $words = preg_split('/[\r\n,]+/', $this->settings->passwordBlocklistWords);

      return array_values(array_filter(array_map('trim', $words), fn($word) => $word !== ''));

   }

   private function _characterPatterns(): array
   {

      return [
         'lowercase' => '[a-z]',
         'uppercase' => '[A-Z]',
         'numeric' => '[0-9]',
         'symbol' => '[^a-zA-Z0-9]'
      ];

   }

   private function _characterLabels(): array
   {

      return [
         'lowercase' => Craft::t('porter', 'At least one lower case character.'),
         'uppercase' => Craft::t('porter', 'At least one upper case character.'),
         'numeric' => Craft::t('porter', 'At least one number.'),
         'symbol' => Craft::t('porter', 'At least one symbol.')
      ];

   }

   private function _characterErrors(): array
   {

      return [
         'lowercase' => Craft::t('porter', 'Password must contain at least 1 lowercase character.'),
         'uppercase' => Craft::t('porter', 'Password must contain at least 1 uppercase character.'),
         'numeric' => Craft::t('porter', 'Password must contain at least 1 numeric character.'),
         'symbol' => Craft::t('porter', 'Password must contain at least 1 symbol character e.g. #, ?, $.')
      ];

   }

   private function _inGroups(User $user, $groupUids): bool
   {

      if (!is_array($groupUids) || !$groupUids)
      {
         return false;
      }

      if (!$user->id)
      {
         return false;
      }

      try {
         $groups = $user->getGroups();
      } catch (\Throwable $e) {
         return false;
      }

      foreach ($groups as $group)
      {
         if (in_array($group->uid, $groupUids, true))
         {
            return true;
         }
      }

      return false;

   }

   /**
    * Lower cases and un-leets a string so "P4ssw0rd" trips the same
    * blocklist and common-password checks that "password" does.
    */
   private function _normalise($value): string
   {

      $value = strtolower((string) $value);

      return strtr($value, [
         '@' => 'a',
         '4' => 'a',
         '8' => 'b',
         '(' => 'c',
         '3' => 'e',
         '6' => 'g',
         '1' => 'i',
         '!' => 'i',
         '|' => 'i',
         '0' => 'o',
         '$' => 's',
         '5' => 's',
         '7' => 't',
         '+' => 't',
         '2' => 'z'
      ]);

   }

   /**
    * The longest run of the same character, or 0 if under 3.
    */
   private function _longestRepeatRun($password): int
   {

      $longest = 1;
      $run = 1;

      for ($i = 1; $i < strlen($password); $i++)
      {

         if ($password[$i] === $password[$i - 1])
         {
            $run++;
            $longest = max($longest, $run);
         }
         else
         {
            $run = 1;
         }

      }

      return $longest >= 3 ? $longest : 0;

   }

   /**
    * The longest ascending or descending character run (abc, 321), or 0 if under 3.
    */
   private function _longestSequenceRun($password): int
   {

      $longest = 1;
      $run = 1;
      $direction = 0;

      for ($i = 1; $i < strlen($password); $i++)
      {

         $step = ord($password[$i]) - ord($password[$i - 1]);

         if ($step === 1 || $step === -1)
         {

            if ($step === $direction)
            {
               $run++;
            }
            else
            {
               $direction = $step;
               $run = 2;
            }

            $longest = max($longest, $run);

         }
         else
         {
            $direction = 0;
            $run = 1;
         }

      }

      return $longest >= 3 ? $longest : 0;

   }

}

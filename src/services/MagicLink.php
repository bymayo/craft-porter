<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\services\Helper;
use bymayo\porter\records\MagicLinkRecord;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Template;

class MagicLink extends Component
{

    private $settings;
    private $defaultTemplateProperties;
    private ?string $newUserRedirect = null;

    public function init(): void
    {

        $this->settings = Porter::getInstance()->helper->settings();

        $this->defaultTemplateProperties = array(
            'redirect' => $this->settings->magicLinkRedirect,
            'newUserRedirect' => $this->settings->magicLinkRegisterRedirect,
            'alertClass' => 'porter__alert',
            'fieldContainerClass' => 'porter__field-container',
            'fieldLabelClass' => 'porter__field-label',
            'fieldClass' => 'porter__field',
            'buttonClass' => 'porter__button',
            'buttonText' => 'Send Magic Link',
         );

    }

    public function defaultTemplateProperties()
    {
       return $this->defaultTemplateProperties;
    }

    public function renderFormTemplate($properties)
    {
 
       if (
             $this->settings->magicLink &&
             $this->settings->magicLinkFrontEnd &&
             Craft::$app->request->getIsSiteRequest()
       ) {
 
          $properties = $properties ? array_merge($this->defaultTemplateProperties, $properties) : $this->defaultTemplateProperties;

          $view = Craft::$app->getView();
 
          $templatePath = $view->getTemplatesPath();
 
          $view->setTemplatesPath(Porter::getInstance()->getBasePath());
 
          $template = $view->renderTemplate('/templates/components/magicLinkForm', $properties);
 
          $view->setTemplatesPath($templatePath);
 
          return Template::raw($template);
 
       }
 
    }

   public function request($request)
   {

        if (!$this->settings->magicLink)
        {
            return;
        }

        $start = microtime(true);

        $email = $request->getBodyParam('email');

        // Every outcome below answers the same way, so the response can't be
        // used to work out which addresses have accounts. Only input that
        // isn't an email address at all is treated as an error.
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            return $this->_requestFailed($request);
        }

        if (!$this->withinThrottle($email))
        {
            // Deliberately the success response: saying "too many requests"
            // would confirm the address is worth hammering.
            return $this->_requestSent($request, $start);
        }

        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($email);

        $isNew = false;

        // Passwordless sign up: no account yet, so make one and send them
        // a link. The link is what proves they own the address.
        if (!$user && $this->registrationEnabled())
        {

           $user = $this->registerUser($email, $request);

           $isNew = $user !== null;

        }

        $token = $user ? $this->createToken($user, $isNew, $this->postedNewUserRedirect($request)) : false;

        if ($token)
        {

            Porter::getInstance()->helper->notify(
                'porter_magic_link_email',
                $user->email,
                array(
                    'user' => $user,
                    'link' => $this->createTokenLink($token)
                )
            );

        }


        // Sent or not, the caller is told the same thing. No account, a
        // suspended one, or one using two step verification all land here.
        return $this->_requestSent($request, $start);

   }

   private function _requestSent($request, ?float $start = null)
   {

      if ($start !== null)
      {
         $this->padResponse($start);
      }

      if ($request->getAcceptsJson())
      {
         return [
            'success' => true,
            'message' => Craft::t('porter', 'porter_magic_link_sent')
         ];
      }

      Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_sent'));

      return true;

   }

   private function _requestFailed($request)
   {

      if ($request->getAcceptsJson())
      {
         return [
            'success' => false,
            'message' => Craft::t('porter', 'porter_magic_link_failed')
         ];
      }

      Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_failed'));

      return;

   }

   /**
    * Holds the response back until a fixed floor has passed.
    *
    * Every outcome of a request answers the same way, but only some of them
    * send an email, and that difference is measurable from the outside. Doing
    * matching busy work instead would mean guessing what a mail send costs on
    * this install, which is unknowable - a floor doesn't have to guess.
    *
    * It bounds the common case rather than closing the channel outright: a
    * mail send slower than the floor still shows. Raise
    * `magicLinkMinResponseMs` on an install with slow SMTP.
    */
   private function padResponse(float $start): void
   {

      $floor = (int) $this->settings->magicLinkMinResponseMs;

      if ($floor <= 0)
      {
         return;
      }

      $remaining = ($floor / 1000) - (microtime(true) - $start);

      if ($remaining > 0)
      {
         usleep((int) ($remaining * 1000000));
      }

   }

   /**
    * Whether another link may be sent to this address, and counts this one.
    *
    * Without a cap, the form is a way to mail bomb any address a third party
    * cares to type in. Kept in the cache rather than a table: the counter is
    * worthless once its window closes, so it should expire on its own.
    */
   public function withinThrottle(string $email): bool
   {

      $limit = (int) $this->settings->magicLinkThrottleLimit;
      $window = (int) $this->settings->magicLinkThrottleWindow;

      if ($limit <= 0 || $window <= 0)
      {
         return true;
      }

      $cache = Craft::$app->getCache();

      // Hashed so the cache never holds a list of addresses that have asked
      // for a link.
      $key = 'porter.magicLink.throttle.' . hash('sha256', mb_strtolower(trim($email)));

      $count = (int) $cache->get($key);

      if ($count >= $limit)
      {
         return false;
      }

      // Yii has no way to bump a value without resetting its expiry, so the
      // window slides: the counter clears once the address has been quiet for
      // a full window, rather than a fixed period after the first request.
      $cache->set($key, $count + 1, $window);

      return true;

   }

   /**
    * Where a brand new account should land, once its token has been spent.
    *
    * Only meaningful straight after validateToken() returned 'new', since the
    * token row is deleted as it's used.
    */
   public function newUserRedirect(): ?string
   {
      return $this->newUserRedirect;
   }

   /**
    * The template's `newUserRedirect` override, if there is one.
    *
    * Read through getValidatedBodyParam so a tampered form field can't turn
    * this into an open redirect - Craft hashes the value on the way out and
    * refuses it here if it doesn't match.
    */
   private function postedNewUserRedirect($request): ?string
   {

      if (!$request || !$request->getIsPost())
      {
         return null;
      }

      try
      {
         $redirect = $request->getValidatedBodyParam('newUserRedirect');
      }
      catch (\yii\web\BadRequestHttpException $e)
      {
         return null;
      }

      return $redirect ?: null;

   }

   /**
    * The stored form of a magic link token.
    *
    * Tokens are 64 random characters, so there's nothing to brute force and
    * no salt or work factor needed - this exists so the table holds something
    * that can't be replayed, not to resist a dictionary attack.
    */
   private function hashToken(string $token): string
   {
      return hash('sha256', $token);
   }

   public function invalidateTokens($user)
   {

        MagicLinkRecord::deleteAll(
            [
                'userId' => $user->id
            ]
        );

   }

   public function createTokenLink($token)
   {
    
       return UrlHelper::actionUrl('/porter/magic-link/access', array('authToken' => $token));

   }

   public function createToken($user, bool $isNew = false, ?string $newUserRedirect = null)
   {

        if (!$user || $user->admin || (!$this->settings->magicLinkControlPanel && $user->can('accessCp')))
        {
            return false;
        }

        if (!$this->canSignInWithLink($user))
        {
            return false;
        }

        $this->invalidateTokens($user);

        $raw = Craft::$app->getSecurity()->generateRandomString(64);

        $record = new MagicLinkRecord();
        $record->userId = $user->id;

        // Only the digest is stored. The raw secret lives in the emailed link
        // and nowhere else, so a dump of this table is not a set of usable
        // sign in credentials.
        $record->token = $this->hashToken($raw);

        // Remembered so the link lands them where they asked from, rather
        // than guessing from whether they happen to have control panel access.
        $record->cpLogin = Craft::$app->getRequest()->getIsCpRequest();
        $record->newUser = $isNew;

        // Stored on the token rather than the session: the link is opened in
        // a fresh request, often on a different device to the one that asked
        // for it, so there's no session to carry it.
        $record->newUserRedirect = $isNew ? $newUserRedirect : null;

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {

            $success = $record->save(false);

            if ($success) {

                $transaction->commit();

                return $raw;

            }

        } catch (\Throwable $e) {

            $transaction->rollBack();
            throw $e;

        }

   }

   /**
    * Whether a magic link request may create a missing account.
    *
    * Gated on Craft's own public registration setting, so this can't be used
    * to create users on a site that has deliberately switched it off.
    */
   public function registrationEnabled(): bool
   {

      if (!$this->settings->magicLink || !$this->settings->magicLinkRegister)
      {
         return false;
      }

      $users = Craft::$app->getProjectConfig()->get('users') ?? [];

      return (bool) ($users['allowPublicRegistration'] ?? false);

   }

   /**
    * Creates an account for a passwordless sign up.
    *
    * Active rather than pending, because the emailed link is what verifies
    * the address, and canSignInWithLink() refuses pending accounts.
    */
   public function registerUser(string $email, $request = null): ?User
   {

      $user = new User();
      $user->email = $email;
      $user->username = $email;
      $user->active = true;
      $user->newPassword = $this->generatePassword();

      if ($request)
      {
         $user->setFieldValuesFromRequest('fields');
      }

      // Saved without validation on purpose: the password is machine
      // generated, so running it through the password policy would be
      // pointless, and would fire a Have I Been Pwned lookup on every sign up.
      if (!Craft::$app->getElements()->saveElement($user, false))
      {

         Porter::warn('[Magic Link] Couldn’t register ' . $email . ': ' . implode(' ', $user->getErrorSummary(true)));

         return null;

      }

      $groupIds = $this->registrationGroupIds();

      if ($groupIds)
      {
         Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
      }

      return $user;

   }

   /**
    * A password the user will never see or need.
    */
   public function generatePassword(): string
   {
      return Craft::$app->getSecurity()->generateRandomString(32);
   }

   /**
    * The groups new accounts are put in.
    */
   public function registrationGroupIds(): array
   {

      $uids = $this->settings->magicLinkRegisterGroups;

      if (!is_array($uids) || !$uids)
      {
         return [];
      }

      $ids = [];

      foreach ($uids as $uid)
      {

         $group = Craft::$app->getUserGroups()->getGroupByUid($uid);

         if ($group)
         {
            $ids[] = $group->id;
         }

      }

      return $ids;

   }

   /**
    * Whether this user may be signed in by a magic link.
    *
    * Craft's own login runs these checks in User::authenticate(). A magic
    * link calls Craft::$app->getUser()->login() directly, which only checks
    * the user agent and IP, so without this a link would sign in accounts
    * that are suspended, locked, pending, or flagged for a password reset.
    */
   public function canSignInWithLink(?User $user = null): bool
   {

      if (!$user)
      {
         return false;
      }

      // Mirrors the checks Craft runs in User::authenticate(). Not using
      // craft\helpers\User::getAuthStatus(), which only exists in later 5.x
      // releases and Porter supports ^5.0.
      //
      // Pending is allowed through: the link is sent to the address on the
      // account, so opening it proves the same thing Craft's own activation
      // email proves. The account is activated as the link is used.
      if (!in_array($user->getStatus(), [User::STATUS_ACTIVE, User::STATUS_PENDING], true))
      {
         // Inactive, archived, or suspended.
         return false;
      }

      if ($user->locked)
      {
         return false;
      }

      // Read through the service because UserQuery doesn't select
      // `passwordResetRequired`, so it's false on a user loaded by ID.
      // Without this a link would walk straight past password expiry.
      if (Porter::getInstance()->passwordRetention->resetRequired($user))
      {
         return false;
      }

      if (Craft::$app->getRequest()->getIsCpRequest() && !$user->can('accessCp'))
      {
         return false;
      }

      // A link can't present a second factor, so allowing one would step
      // around two-step verification entirely.
      $auth = Craft::$app->getAuth();

      if ($auth->hasActiveMethod($user) || $auth->is2faRequired($user))
      {
         return false;
      }

      return true;

   }

   /**
    * Activates a pending account whose holder has just proved the address.
    */
   private function activate(User $user): bool
   {

      try
      {
         Craft::$app->getUsers()->activateUser($user);
      }
      catch (\Throwable $e)
      {

         Porter::warn('[Magic Link] Couldn’t activate ' . $user->email . ': ' . $e->getMessage());

         return false;

      }

      return true;

   }

   public function validateToken($token)
   {

    $query = $token ? MagicLinkRecord::findOne(
        [
            'token' => $this->hashToken($token)
        ]
    ) : null;

    if ($query)
    {

        $user = Craft::$app->users->getUserById($query->userId);

        if (!$user) {
            $query->delete();
            Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_token_expired'));
            return;
        }

        $this->invalidateTokens($user);

        if (DateTimeHelper::currentTimeStamp() <= (DateTimeHelper::toDateTime($query->dateCreated)->format('U') + $this->settings->magicLinkExpirySeconds))
        {

            // Re-checked here, not just when the link was created: the account
            // could have been suspended, locked or flagged for a password
            // reset in the meantime.
            if (!$this->canSignInWithLink($user))
            {

                Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_not_allowed'));

                return;

            }

            // Finish what Craft's activation email would have done. Only
            // reached once canSignInWithLink() has cleared the account, so a
            // suspended or locked user never gets here - which matters,
            // because activateUser() would clear both of those flags.
            if ($user->getStatus() === User::STATUS_PENDING && !$this->activate($user))
            {

                Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_not_allowed'));

                return;

            }

            if (Craft::$app->getUser()->login($user))
            {

                if ($query->cpLogin)
                {
                    return 'cp';
                }

                $this->newUserRedirect = $query->newUserRedirect;

                return $query->newUser ? 'new' : true;

            }

        }

    }

    Craft::$app->getSession()->setFlash('porter', Craft::t('porter', 'porter_magic_link_token_expired'));

    return;

   }

}

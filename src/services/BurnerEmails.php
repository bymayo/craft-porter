<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;

/**
 * Blocks disposable and undeliverable email addresses.
 *
 * The checks run locally by default - a bundled list of disposable domains
 * and a DNS lookup - so the feature costs nothing and doesn't depend on a
 * third party staying free or staying up. An API key adds a further layer
 * on top for anyone who wants one.
 */
class BurnerEmails extends Component
{

   /**
    * Where the refreshed list is written. Preferred over the bundled copy.
    */
   private const STORAGE_FILE = 'porter/disposable-domains.txt';

   /**
    * Upstream source for `porter/burner-emails/update`.
    *
    * The GitHub Pages URL the list's own README publishes, rather than a raw
    * githubusercontent link - Pages is a CDN meant for serving, so it won't
    * rate limit.
    *
    * Only ever read by that command. Checking an address never leaves the
    * server: the list is a file inside the plugin.
    */
   private const LIST_URL = 'https://disposable.github.io/disposable-email-domains/domains.txt';

   private $settings;

   private ?array $domains = null;

   public function init(): void
   {
      $this->settings = Porter::getInstance()->helper->settings();
   }

   /**
    * Whether this save is worth checking.
    *
    * A DNS lookup and a possible API call are too expensive to run on every
    * user save, and pointless when the address hasn't moved - an admin
    * editing somebody's name shouldn't fire either.
    */
   public function shouldCheck(\craft\elements\User $user): bool
   {

      if (!$user->email)
      {
         return false;
      }

      if (!$user->id)
      {
         // A brand new account, which is the case that matters.
         return true;
      }

      $current = (new \craft\db\Query())
         ->select('email')
         ->from('{{%users}}')
         ->where(['id' => $user->id])
         ->scalar();

      return $current === false || mb_strtolower((string) $current) !== mb_strtolower($user->email);

   }

   /**
    * Every reason this address should be refused, or an empty array.
    */
   public function check(?string $email): array
   {

      if (!$this->settings->emailBurners || !$email)
      {
         return [];
      }

      $domain = $this->domain($email);

      if (!$domain)
      {
         return [Craft::t('porter', 'That doesn’t look like a valid email address.')];
      }

      if ($this->isDisposable($domain))
      {
         return [Craft::t('porter', 'Disposable email addresses are not allowed.')];
      }

      if (!$this->hasMx($domain))
      {
         return [Craft::t('porter', 'That email address’s domain can’t receive mail.')];
      }

      return [];

   }

   /**
    * The domain part, lowercased, or null when the address is malformed.
    */
   public function domain(string $email): ?string
   {

      if (!filter_var($email, FILTER_VALIDATE_EMAIL))
      {
         return null;
      }

      $at = strrpos($email, '@');

      if ($at === false)
      {
         return null;
      }

      return mb_strtolower(substr($email, $at + 1));

   }

   public function isDisposable(string $domain): bool
   {
      return isset($this->domains()[$domain]);
   }

   /**
    * Whether the domain can actually receive mail.
    *
    * Falls back to an A record, because a domain with no MX but a working
    * host still accepts mail under the SMTP spec.
    */
   public function hasMx(string $domain): bool
   {

      if (!function_exists('checkdnsrr'))
      {
         // Can't tell, so don't block on it.
         return true;
      }

      try
      {
         return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
      }
      catch (\Throwable $e)
      {
         // A resolver failure isn't the user's fault.
         return true;
      }

   }

   /**
    * Refreshes the list from upstream. Returns the new count, or null if it
    * couldn't be fetched.
    */
   public function updateList(): ?int
   {

      try
      {
         $response = Craft::createGuzzleClient(['timeout' => 30])->get(self::LIST_URL);
         $body = (string) $response->getBody();
      }
      catch (\Throwable $e)
      {
         Porter::warn('[Burner Emails] Couldn’t fetch the domain list: ' . $e->getMessage());

         return null;
      }

      $lines = $this->parse($body);

      // A truncated or error response would otherwise wipe the list and let
      // every disposable address through, so refuse anything implausible.
      if (count($lines) < 1000)
      {
         Porter::warn('[Burner Emails] Refused a list of only ' . count($lines) . ' domains - it looks wrong, so the old one is kept.');

         return null;
      }

      $path = $this->storagePath();

      try
      {
         FileHelper::writeToFile($path, implode("\n", array_keys($lines)) . "\n");
      }
      catch (\Throwable $e)
      {
         Porter::warn('[Burner Emails] Couldn’t write the domain list: ' . $e->getMessage());

         return null;
      }

      $this->domains = $lines;

      return count($lines);

   }

   /**
    * Where the list is fetched from, for display.
    */
   public function sourceUrl(): string
   {
      return self::LIST_URL;
   }

   /**
    * Whether a domain list has been downloaded yet.
    */
   public function hasList(): bool
   {
      return is_file($this->storagePath());
   }

   /**
    * How many domains the list holds and when it was fetched.
    */
   public function listInfo(): array
   {

      $path = $this->hasList() ? $this->storagePath() : $this->bundledPath();

      return [
         'count' => count($this->domains()),
         'updated' => is_file($path) ? filemtime($path) : null,
      ];

   }

   /**
    * The disposable domains, keyed for O(1) lookup.
    */
   private function domains(): array
   {

      if ($this->domains !== null)
      {
         return $this->domains;
      }

      // The downloaded copy if there is one, otherwise the snapshot that
      // ships with the plugin. The fallback is what stops an install with no
      // outbound network, or one whose first download failed, from quietly
      // blocking nothing.
      $path = $this->storagePath();

      if (!is_file($path))
      {
         $path = $this->bundledPath();
      }

      if (!is_file($path))
      {

         Porter::warn('[Burner Emails] No domain list available, so disposable domains aren’t being blocked. Run porter/burner-emails/update.');

         return $this->domains = [];

      }

      $contents = $this->read($path);

      return $this->domains = $contents === null ? [] : $this->parse($contents);

   }

   /**
    * One domain per line, keyed so lookups don't scan. Comments and blanks
    * are skipped so a hand-edited file still works.
    */
   private function parse(string $contents): array
   {

      $domains = [];

      foreach (explode("\n", $contents) as $line)
      {

         $line = mb_strtolower(trim($line));

         if ($line === '' || str_starts_with($line, '#'))
         {
            continue;
         }

         $domains[$line] = true;

      }

      return $domains;

   }

   /**
    * Reads a list file, gzipped or not.
    *
    * The bundled snapshot is compressed - it's a third of the size that way.
    * The downloaded copy in storage stays plain text so it can be opened and
    * edited.
    */
   private function read(string $path): ?string
   {

      $raw = @file_get_contents($path);

      if ($raw === false)
      {
         return null;
      }

      if (!str_ends_with($path, '.gz'))
      {
         return $raw;
      }

      if (!function_exists('gzdecode'))
      {

         Porter::warn('[Burner Emails] PHP has no zlib, so the bundled domain list can’t be read. Run porter/burner-emails/update to fetch an uncompressed copy.');

         return null;

      }

      $contents = @gzdecode($raw);

      return $contents === false ? null : $contents;

   }

   private function bundledPath(): string
   {
      return Porter::getInstance()->getBasePath() . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'disposable-domains.txt.gz';
   }

   private function storagePath(): string
   {
      return Craft::$app->getPath()->getStoragePath() . DIRECTORY_SEPARATOR . self::STORAGE_FILE;
   }


}

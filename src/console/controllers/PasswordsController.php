<?php

namespace bymayo\porter\console\controllers;

use bymayo\porter\Porter;

use Craft;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

/**
 * Porter password retention commands.
 */
class PasswordsController extends Controller
{

   /**
    * @var bool Push the resets onto the queue instead of running them now.
    */
   public bool $queue = false;

   /**
    * @var bool List every user as it's processed.
    */
   public bool $verbose = false;

   /**
    * @var bool Report what would happen without changing anything.
    */
   public bool $dryRun = false;

   /**
    * @var bool Treat users who have never changed their password as expired.
    */
   public bool $includeNeverChanged = false;

   public function options($actionID): array
   {

      $options = parent::options($actionID);

      $options[] = 'verbose';
      $options[] = 'dryRun';

      if ($actionID === 'force-reset' || $actionID === 'retention')
      {
         $options[] = 'queue';
         $options[] = 'includeNeverChanged';
      }

      return $options;

   }

   /**
    * Sends expiry warning emails, then force resets expired passwords.
    *
    * This is the one to put on a cron.
    */
   public function actionRetention(): int
   {

      $warned = $this->actionWarnExpiring();

      if ($warned !== ExitCode::OK)
      {
         return $warned;
      }

      return $this->actionForceReset();

   }

   /**
    * Emails users whose password is about to expire.
    */
   public function actionWarnExpiring(): int
   {

      if (!$this->_enabled())
      {
         return ExitCode::UNSPECIFIED_ERROR;
      }

      $warned = Porter::getInstance()->passwordRetention->warnExpiring($this->dryRun);

      $this->stdout(
         $this->dryRun
            ? "Would send {$warned} password expiry warning(s)." . PHP_EOL
            : "Sent {$warned} password expiry warning(s)." . PHP_EOL,
         BaseConsole::FG_GREEN
      );

      return ExitCode::OK;

   }

   /**
    * Flags every user with an expired password as needing a reset.
    */
   public function actionForceReset(): int
   {

      if (!$this->_enabled())
      {
         return ExitCode::UNSPECIFIED_ERROR;
      }

      $retention = Porter::getInstance()->passwordRetention;

      if ($this->queue && !$this->dryRun)
      {

         $retention->queueResets($this->includeNeverChanged);

         $this->stdout('Password resets queued.' . PHP_EOL, BaseConsole::FG_GREEN);

         return ExitCode::OK;

      }

      $ids = $retention->expiredUserIds($this->includeNeverChanged);

      if (!$ids)
      {

         $this->stdout('No users need a password reset.' . PHP_EOL, BaseConsole::FG_GREEN);

         return ExitCode::OK;

      }

      $reset = 0;

      // Hydrated a chunk at a time, so a large user table doesn't have to
      // fit in memory all at once.
      foreach (array_chunk($ids, 500) as $chunk)
      {

         foreach ($retention->usersById($chunk) as $user)
         {

            if ($this->dryRun)
            {

               if ($retention->resetRequired($user))
               {
                  continue;
               }

               if ($this->verbose)
               {
                  $this->stdout('  Would reset: ' . $user->email . PHP_EOL);
               }

               $reset++;

               continue;

            }

            if ($retention->forceReset($user))
            {

               if ($this->verbose)
               {
                  $this->stdout('  Reset required for: ' . $user->email . PHP_EOL);
               }

               $reset++;

            }

         }

      }

      $this->stdout(
         $this->dryRun
            ? "Would flag {$reset} user(s) for a password reset." . PHP_EOL
            : "Flagged {$reset} user(s) for a password reset." . PHP_EOL,
         BaseConsole::FG_GREEN
      );

      return ExitCode::OK;

   }

   private function _enabled(): bool
   {

      if (Porter::getInstance()->passwordRetention->isEnabled())
      {
         return true;
      }

      $this->stderr(
         'Password expiry is disabled, or the expiry period isn’t set. Enable it under Settings > Porter > Password Policy.' . PHP_EOL,
         BaseConsole::FG_RED
      );

      return false;

   }

}

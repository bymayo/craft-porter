<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\records\UserLoginRecord;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\Db;

class InactiveAccounts extends Component
{

   private $settings;

   public function init(): void
   {
      $this->settings = Porter::getInstance()->helper->settings();
   }

   public function cleanupInactive(): array
   {

      $stats = ['warned' => 0, 'deactivated' => 0];

      if (!$this->settings->inactiveAccountCleanup)
      {
         return $stats;
      }

      $reminderDays = max(1, (int) $this->settings->inactiveAccountReminderDays);
      $deactivateDays = max($reminderDays + 1, (int) $this->settings->inactiveAccountDeactivateDays);

      $now = new \DateTime();
      $reminderCutoff = (clone $now)->modify("-{$reminderDays} days");
      $deactivateCutoff = (clone $now)->modify("-{$deactivateDays} days");

      $candidates = User::find()
         ->status(User::STATUS_ACTIVE)
         ->admin(false)
         ->lastLoginDate('< ' . Db::prepareDateForDb($reminderCutoff))
         ->all();

      foreach ($candidates as $user)
      {

         if ($this->_canAccessCp($user))
         {
            continue;
         }

         if ($user->lastLoginDate < $deactivateCutoff)
         {
            Craft::$app->getUsers()->deactivateUser($user);
            $stats['deactivated']++;
            continue;
         }

         $record = UserLoginRecord::findOne(['userId' => $user->id]);

         if ($record && $record->inactiveReminderSentAt)
         {
            $sentAt = new \DateTime($record->inactiveReminderSentAt);
            if ($user->lastLoginDate && $sentAt > $user->lastLoginDate)
            {
               continue;
            }
         }

         Porter::getInstance()->emailNotifications->sendInactiveAccountReminder($user, $deactivateDays);

         if (!$record)
         {
            $record = new UserLoginRecord();
            $record->userId = $user->id;
         }

         $record->inactiveReminderSentAt = Db::prepareDateForDb($now);
         $record->save();

         $stats['warned']++;

      }

      return $stats;

   }

   private function _canAccessCp(User $user): bool
   {
      return $user->admin || $user->can('accessCp');
   }

}

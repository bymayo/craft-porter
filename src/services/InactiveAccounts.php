<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\records\UserLoginRecord;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
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

      $stats = ['warned' => 0, 'deleted' => 0];

      if (!$this->settings->inactiveAccountCleanup)
      {
         return $stats;
      }

      $reminderDays = max(1, (int) $this->settings->inactiveAccountReminderDays ?: 365);
      $deleteDays = max($reminderDays + 1, (int) $this->settings->inactiveAccountDeleteDays ?: 395);
      $graceDays = $deleteDays - $reminderDays;

      $now = new \DateTime();
      $reminderCutoff = (clone $now)->modify("-{$reminderDays} days");
      $graceCutoff = (clone $now)->modify("-{$graceDays} days");

      $candidateIds = User::find()
         ->status(User::STATUS_ACTIVE)
         ->admin(false)
         ->lastLoginDate('< ' . Db::prepareDateForDb($reminderCutoff))
         ->ids();

      foreach (array_chunk($candidateIds, 100) as $chunk)
      {

      foreach (User::find()->id($chunk)->status(null)->all() as $user)
      {

         if ($this->_canAccessCp($user))
         {
            continue;
         }

         $record = UserLoginRecord::findOne(['userId' => $user->id]);

         $remindedAt = $record && $record->inactiveReminderSentAt
            ? DateTimeHelper::toDateTime($record->inactiveReminderSentAt)
            : null;

         if ($remindedAt && $user->lastLoginDate && $remindedAt < $user->lastLoginDate)
         {
            $remindedAt = null;
         }

         if (!$remindedAt)
         {

            Porter::getInstance()->emailNotifications->sendInactiveAccountReminder($user, $graceDays);

            UserLoginRecord::stamp($user->id, 'inactiveReminderSentAt');

            $stats['warned']++;

            continue;

         }

         if ($remindedAt <= $graceCutoff)
         {

            // Soft delete, so the account lands in the trash and can be
            // restored until Craft's garbage collection purges it.
            if (Craft::$app->getElements()->deleteElement($user))
            {
               $stats['deleted']++;
            }

         }

      }

      }

      return $stats;

   }

   public function resetLastLoginForUser(User $user): void
   {

      if ($user->id === null)
      {
         return;
      }

      Craft::$app->getDb()->createCommand()->update(
         '{{%users}}',
         ['lastLoginDate' => null],
         ['id' => $user->id]
      )->execute();

      $user->lastLoginDate = null;

   }

   private function _canAccessCp(User $user): bool
   {
      return $user->admin || $user->can('accessCp');
   }

}

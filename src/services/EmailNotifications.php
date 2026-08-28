<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\records\UserLoginRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\User;

class EmailNotifications extends Component
{

   private $settings;

   private static array $pendingPasswordChanges = [];

   private static array $pendingEmailChanges = [];

   public function init(): void
   {
       $this->settings = Porter::getInstance()->helper->settings();
   }

   public function sendWelcome(User $user)
   {

      if (!$this->settings->emailWelcome)
      {
         return;
      }

      if (!$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_welcome_email',
         $user->email,
         array(
            'user' => $user
         )
      );

   }

   public function handleLogin(User $user)
   {

      if (!$user->id || !$user->email)
      {
         return;
      }

      $request = Craft::$app->getRequest();

      if ($request->getIsConsoleRequest())
      {
         return;
      }

      $ipAddress = $request->getUserIP() ?: '';
      $userAgent = $request->getUserAgent() ?: '';

      $ipHash = hash('sha256', $ipAddress);
      $uaHash = hash('sha256', $userAgent);

      $record = UserLoginRecord::findOne(['userId' => $user->id]);

      if (!$record)
      {
         $record = new UserLoginRecord();
         $record->userId = $user->id;
         $record->ipHash = $ipHash;
         $record->uaHash = $uaHash;
         $record->save();
         return;
      }

      if ($record->inactiveReminderSentAt)
      {
         $record->inactiveReminderSentAt = null;
         $record->save();
      }

      if ($record->ipHash === $ipHash && $record->uaHash === $uaHash)
      {
         return;
      }

      if ($this->settings->emailNewDeviceLogin)
      {

         Porter::getInstance()->helper->notify(
            'porter_new_device_login_email',
            $user->email,
            array(
               'user' => $user,
               'ipAddress' => $ipAddress,
               'userAgent' => $userAgent,
               'dateCreated' => new \DateTime()
            )
         );

      }

      $record->ipHash = $ipHash;
      $record->uaHash = $uaHash;
      $record->save();

   }

   public function capturePasswordChange(User $user)
   {

      if ($user->id !== null && !empty($user->newPassword))
      {
         self::$pendingPasswordChanges[$user->id] = true;
      }

   }

   public function sendPasswordChanged(User $user)
   {

      if (!$user->id || !isset(self::$pendingPasswordChanges[$user->id]))
      {
         return;
      }

      unset(self::$pendingPasswordChanges[$user->id]);

      if (!$this->settings->emailPasswordChanged || !$user->email)
      {
         return;
      }

      $ipAddress = '';

      $request = Craft::$app->getRequest();

      if (!$request->getIsConsoleRequest())
      {
         $ipAddress = $request->getUserIP() ?: '';
      }

      Porter::getInstance()->helper->notify(
         'porter_password_changed_email',
         $user->email,
         array(
            'user' => $user,
            'ipAddress' => $ipAddress,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function captureEmailChange(User $user)
   {

      if ($user->id === null || empty($user->email))
      {
         return;
      }

      $oldEmail = (new Query())
         ->select('email')
         ->from('{{%users}}')
         ->where(['id' => $user->id])
         ->scalar();

      if ($oldEmail && $oldEmail !== $user->email)
      {
         self::$pendingEmailChanges[$user->id] = $oldEmail;
      }

   }

   public function sendEmailAddressChanged(User $user)
   {

      if (!$user->id || !isset(self::$pendingEmailChanges[$user->id]))
      {
         return;
      }

      $oldEmail = self::$pendingEmailChanges[$user->id];

      unset(self::$pendingEmailChanges[$user->id]);

      if (!$this->settings->emailAddressChanged)
      {
         return;
      }

      $ipAddress = '';

      $request = Craft::$app->getRequest();

      if (!$request->getIsConsoleRequest())
      {
         $ipAddress = $request->getUserIP() ?: '';
      }

      Porter::getInstance()->helper->notify(
         'porter_email_address_changed_email',
         $oldEmail,
         array(
            'user' => $user,
            'oldEmail' => $oldEmail,
            'newEmail' => $user->email,
            'ipAddress' => $ipAddress,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function sendAccountSuspended(User $user)
   {

      if (!$this->settings->emailAccountSuspended || !$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_account_suspended_email',
         $user->email,
         array(
            'user' => $user,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function sendAccountUnsuspended(User $user)
   {

      if (!$this->settings->emailAccountUnsuspended || !$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_account_unsuspended_email',
         $user->email,
         array(
            'user' => $user,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function sendAccountDeactivated(User $user)
   {

      if (!$this->settings->emailAccountDeactivated || !$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_account_deactivated_email',
         $user->email,
         array(
            'user' => $user,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function sendAccountDeleted(User $user)
   {

      if (!$this->settings->emailAccountDeleted || !$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_account_deleted_email',
         $user->email,
         array(
            'user' => $user,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function sendInactiveAccountReminder(User $user, int $deactivateDays)
   {

      if (!$this->settings->emailInactiveAccountReminder || !$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_inactive_account_reminder_email',
         $user->email,
         array(
            'user' => $user,
            'lastLoginDate' => $user->lastLoginDate,
            'deactivateDays' => $deactivateDays,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function sendFailedLoginAttempts(User $user)
   {

      if (!$user->id || !$user->email)
      {
         return;
      }

      if (!$this->settings->emailFailedLoginAttempts)
      {
         return;
      }

      $threshold = max(1, (int) $this->settings->emailFailedLoginAttemptsThreshold);
      $count = (int) $user->invalidLoginCount;

      if ($count !== $threshold)
      {
         return;
      }

      $ipAddress = '';

      $request = Craft::$app->getRequest();

      if (!$request->getIsConsoleRequest())
      {
         $ipAddress = $request->getUserIP() ?: '';
      }

      Porter::getInstance()->helper->notify(
         'porter_failed_login_attempts_email',
         $user->email,
         array(
            'user' => $user,
            'attempts' => $count,
            'threshold' => $threshold,
            'ipAddress' => $ipAddress,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function sendPasswordExpiring(User $user, int $daysRemaining, ?\DateTime $expiryDate = null)
   {

      if (!$this->settings->emailPasswordExpiring || !$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_password_expiring_email',
         $user->email,
         array(
            'user' => $user,
            'daysRemaining' => $daysRemaining,
            'expiryDate' => $expiryDate,
            'dateCreated' => new \DateTime()
         )
      );

   }

   public function sendPasswordExpired(User $user)
   {

      if (!$this->settings->emailPasswordExpired || !$user->email)
      {
         return;
      }

      Porter::getInstance()->helper->notify(
         'porter_password_expired_email',
         $user->email,
         array(
            'user' => $user,
            'dateCreated' => new \DateTime()
         )
      );

   }

}

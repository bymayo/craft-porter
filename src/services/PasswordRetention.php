<?php

namespace bymayo\porter\services;

use bymayo\porter\Porter;
use bymayo\porter\jobs\PasswordResetJob;
use bymayo\porter\records\PasswordHistoryRecord;
use bymayo\porter\records\UserLoginRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Queue;

class PasswordRetention extends Component
{

   private $settings;

   private static array $pendingChanges = [];

   private array $meta = [];

   public function init(): void
   {
      $this->settings = Porter::getInstance()->helper->settings();
   }

   // Expiry
   // =========================================================================

   /**
    * Whether password expiry is switched on and actually configured.
    */
   public function isEnabled(): bool
   {
      return (bool) $this->settings->passwordExpiry && $this->_interval() !== null;
   }

   /**
    * When this user's password was last changed.
    *
    * Craft's UserQuery doesn't select `lastPasswordChangeDate`, so any user
    * loaded through User::find() — including the signed-in identity — reports
    * null for it. Fall back to reading the column directly.
    */
   public function lastPasswordChangeDate(?User $user = null): ?\DateTime
   {

      if (!$user || !$user->id)
      {
         return null;
      }

      if ($user->lastPasswordChangeDate)
      {
         return $user->lastPasswordChangeDate;
      }

      $meta = $this->_passwordMeta($user->id);

      return $meta['lastPasswordChangeDate'];

   }

   /**
    * Whether this user is flagged as needing a password reset.
    *
    * Same story as lastPasswordChangeDate() — UserQuery doesn't select
    * `passwordResetRequired` unless the caller explicitly adds it.
    */
   public function resetRequired(?User $user = null): bool
   {

      if (!$user || !$user->id)
      {
         return false;
      }

      if ($user->passwordResetRequired)
      {
         return true;
      }

      return $this->_passwordMeta($user->id)['passwordResetRequired'];

   }

   /**
    * When this user's password expires, or null if it never does.
    */
   public function expiresAt(?User $user = null): ?\DateTime
   {

      if (!$this->isEnabled() || !$user)
      {
         return null;
      }

      $changedAt = $this->lastPasswordChangeDate($user);

      if (!$changedAt)
      {
         return null;
      }

      if (Porter::getInstance()->passwordPolicy->isExempt($user))
      {
         return null;
      }

      $expires = clone $changedAt;

      return $expires->add(new \DateInterval($this->_interval()));

   }

   /**
    * Whether this user's password has expired.
    */
   public function isExpired(?User $user = null): bool
   {

      if ($this->resetRequired($user))
      {
         return true;
      }

      $expiresAt = $this->expiresAt($user);

      return $expiresAt !== null && $expiresAt < new \DateTime();

   }

   /**
    * Whole days until this user's password expires (negative if it already has).
    */
   public function daysUntilExpiry(?User $user = null): ?int
   {

      $expiresAt = $this->expiresAt($user);

      if ($expiresAt === null)
      {
         return null;
      }

      $diff = (new \DateTime())->diff($expiresAt);

      return (int) $diff->format('%r%a');

   }

   /**
    * IDs of users whose password has expired.
    *
    * IDs rather than elements, and a stable set: this deliberately doesn't
    * filter on `passwordResetRequired`, because the batched job re-runs this
    * between batches. If flagged users dropped out mid-run, the job's offset
    * would step past the same number of unprocessed users and silently skip
    * them. forceReset() no-ops on anyone already flagged, so paging over the
    * full set is both correct and cheap.
    */
   public function expiredUserIds(bool $includeNeverChanged = false): array
   {

      if (!$this->isEnabled())
      {
         return [];
      }

      return $this->_expiredQuery($includeNeverChanged)
         ->orderBy(['users.id' => SORT_ASC])
         ->ids();

   }

   /**
    * How many users still need flagging.
    *
    * Counted in SQL, and excludes anyone already flagged, so it's the number
    * a run would actually act on.
    */
   public function countExpired(bool $includeNeverChanged = false): int
   {

      if (!$this->isEnabled())
      {
         return 0;
      }

      return (int) $this->_expiredQuery($includeNeverChanged)
         ->andWhere(['users.passwordResetRequired' => false])
         ->count();

   }

   /**
    * IDs of users whose password expires within the warning window.
    */
   public function expiringSoonUserIds(): array
   {

      $query = $this->_expiringSoonQuery();

      return $query ? $query->orderBy(['users.id' => SORT_ASC])->ids() : [];

   }

   /**
    * How many users are inside the warning window.
    */
   public function countExpiringSoon(): int
   {

      $query = $this->_expiringSoonQuery();

      return $query ? (int) $query->count() : 0;

   }

   /**
    * Loads a slice of users by ID, in one query.
    */
   public function usersById(array $ids): array
   {

      if (!$ids)
      {
         return [];
      }

      return User::find()
         ->id($ids)
         ->status(null)
         ->limit(null)
         ->all();

   }

   /**
    * Flags a user as needing a password reset, and lets them know.
    */
   public function forceReset(User $user): bool
   {

      if (Porter::getInstance()->passwordPolicy->isExempt($user))
      {
         return false;
      }

      // Already flagged, so there's nothing to do and no second email to send.
      // This is what makes paging over a stable set safe.
      if ($this->resetRequired($user))
      {
         return false;
      }

      $user->passwordResetRequired = true;

      unset($this->meta[$user->id]);

      if (!Craft::$app->getElements()->saveElement($user, false))
      {

         Porter::warn('[Password Retention] Couldn’t flag user ' . $user->id . ' for a password reset.');

         return false;

      }

      Porter::getInstance()->emailNotifications->sendPasswordExpired($user);

      return true;

   }

   /**
    * Sends the "your password expires soon" email to everyone in the window.
    *
    * Sends once per expiry cycle — changing the password resets the clock.
    */
   public function warnExpiring(bool $dryRun = false): int
   {

      $warned = 0;

      foreach (array_chunk($this->expiringSoonUserIds(), 500) as $chunk)
      {

      foreach ($this->usersById($chunk) as $user)
      {

         if ($this->_expiryReminderAlreadySent($user))
         {
            continue;
         }

         if ($dryRun)
         {
            $warned++;
            continue;
         }

         Porter::getInstance()->emailNotifications->sendPasswordExpiring(
            $user,
            max(0, (int) $this->daysUntilExpiry($user)),
            $this->expiresAt($user)
         );

         $this->_markExpiryReminderSent($user);

         $warned++;

      }

      }

      return $warned;

   }

   /**
    * Pushes the expiry resets onto the queue rather than running them inline.
    */
   public function queueResets(bool $includeNeverChanged = false): void
   {

      // Priority and TTR live on the job itself so they survive into the
      // continuation batches BaseBatchedJob queues up.
      Queue::push(
         job: new PasswordResetJob([
            'description' => Craft::t('porter', 'Resetting expired passwords'),
            'includeNeverChanged' => $includeNeverChanged
         ])
      );

   }

   // History
   // =========================================================================

   /**
    * Whether password history is switched on.
    */
   public function historyEnabled(): bool
   {
      return Porter::getInstance()->passwordPolicy->historyEnabled();
   }

   /**
    * Notes that this save is changing the password.
    *
    * Craft nulls out `newPassword` once it's hashed, so we have to spot the
    * change on the way in and act on it on the way out. Keyed by object id
    * rather than user id, because a brand new user doesn't have one yet.
    */
   public function capturePasswordChange(User $user): void
   {

      if (!$this->historyEnabled())
      {
         return;
      }

      if ($user->newPassword !== null && $user->newPassword !== '')
      {
         self::$pendingChanges[spl_object_id($user)] = true;
      }

   }

   /**
    * Stores the new password hash, if this save actually changed it.
    */
   public function flushPasswordChange(User $user): void
   {

      $key = spl_object_id($user);

      if (empty(self::$pendingChanges[$key]))
      {
         return;
      }

      unset(self::$pendingChanges[$key]);

      $this->recordHistory($user);

   }

   /**
    * Whether this password matches one of the user's remembered passwords.
    */
   public function isReused($password, ?User $user = null): bool
   {

      if (!$this->historyEnabled() || !$user || !$user->id || $password === null || $password === '')
      {
         return false;
      }

      $security = Craft::$app->getSecurity();

      foreach ($this->recentHashes($user) as $hash)
      {

         try {

            if ($security->validatePassword($password, $hash))
            {
               return true;
            }

         } catch (\Throwable $e) {
            // A malformed stored hash shouldn't block a password change.
            continue;
         }

      }

      return false;

   }

   /**
    * The user's most recently stored password hashes, newest first.
    */
   public function recentHashes(User $user): array
   {

      return (new Query())
         ->select(['passwordHash'])
         ->from(['{{%porter_password_history}}'])
         ->where(['userId' => $user->id])
         ->orderBy(['dateCreated' => SORT_DESC, 'id' => SORT_DESC])
         ->limit(Porter::getInstance()->passwordPolicy->historyCount())
         ->column();

   }

   /**
    * Stores the user's current password hash in their history.
    *
    * Craft nulls out `newPassword` once it's hashed, so the hash is read
    * straight back out of the users table after the save.
    */
   public function recordHistory(User $user): void
   {

      if (!$this->historyEnabled() || !$user->id)
      {
         return;
      }

      $hash = (new Query())
         ->select(['password'])
         ->from([Table::USERS])
         ->where(['id' => $user->id])
         ->scalar();

      if (!$hash)
      {
         return;
      }

      $existing = PasswordHistoryRecord::find()
         ->where(['userId' => $user->id, 'passwordHash' => $hash])
         ->exists();

      if ($existing)
      {
         return;
      }

      $record = new PasswordHistoryRecord();
      $record->userId = $user->id;
      $record->passwordHash = $hash;
      $record->save();

      unset($this->meta[$user->id]);

      $this->pruneHistory($user);

   }

   /**
    * Trims the user's history down to the configured length.
    */
   public function pruneHistory(User $user): void
   {

      $keep = Porter::getInstance()->passwordPolicy->historyCount();

      $ids = (new Query())
         ->select(['id'])
         ->from(['{{%porter_password_history}}'])
         ->where(['userId' => $user->id])
         ->orderBy(['dateCreated' => SORT_DESC, 'id' => SORT_DESC])
         ->offset($keep)
         ->limit(1000)
         ->column();

      if (!$ids)
      {
         return;
      }

      Craft::$app->getDb()->createCommand()
         ->delete('{{%porter_password_history}}', ['id' => $ids])
         ->execute();

   }

   // Private Methods
   // =========================================================================

   /**
    * Reads the password columns UserQuery leaves out, once per user per request.
    */
   private function _passwordMeta(int $userId): array
   {

      if (isset($this->meta[$userId]))
      {
         return $this->meta[$userId];
      }

      $row = (new Query())
         ->select(['lastPasswordChangeDate', 'passwordResetRequired'])
         ->from([Table::USERS])
         ->where(['id' => $userId])
         ->one();

      $this->meta[$userId] = [
         'lastPasswordChangeDate' => !empty($row['lastPasswordChangeDate'])
            ? DateTimeHelper::toDateTime($row['lastPasswordChangeDate'], true) ?: null
            : null,
         'passwordResetRequired' => (bool) ($row['passwordResetRequired'] ?? false)
      ];

      return $this->meta[$userId];

   }

   /**
    * The expiry period as an ISO 8601 duration, or null if unusable.
    */
   private function _interval(): ?string
   {

      $amount = (int) $this->settings->passwordExpiryAmount;

      if ($amount < 1)
      {
         return null;
      }

      return match ($this->settings->passwordExpiryPeriod) {
         'days' => "P{$amount}D",
         'weeks' => "P{$amount}W",
         'months' => "P{$amount}M",
         'years' => "P{$amount}Y",
         default => null
      };

   }

   /**
    * Base query for users whose password has expired.
    */
   private function _expiredQuery(bool $includeNeverChanged): UserQuery
   {

      $cutoff = (new \DateTime())->sub(new \DateInterval($this->_interval()));

      $query = User::find()->status(User::STATUS_ACTIVE);

      if ($includeNeverChanged)
      {
         $query->andWhere([
            'or',
            ['<', 'users.lastPasswordChangeDate', Db::prepareDateForDb($cutoff)],
            ['users.lastPasswordChangeDate' => null]
         ]);
      }
      else
      {
         $query->andWhere(['<', 'users.lastPasswordChangeDate', Db::prepareDateForDb($cutoff)]);
      }

      return $this->_applyExemptions($query);

   }

   /**
    * Base query for users inside the expiry warning window, or null if the
    * window isn't configured.
    */
   private function _expiringSoonQuery(): ?UserQuery
   {

      if (!$this->isEnabled())
      {
         return null;
      }

      $warningDays = max(0, (int) $this->settings->passwordExpiryWarningDays);

      if ($warningDays < 1)
      {
         return null;
      }

      $now = new \DateTime();
      $expiredBefore = (clone $now)->sub(new \DateInterval($this->_interval()));
      $warnBefore = (clone $expiredBefore)->add(new \DateInterval("P{$warningDays}D"));

      $query = User::find()
         ->status(User::STATUS_ACTIVE)
         ->andWhere(['users.passwordResetRequired' => false])
         ->andWhere(['<', 'users.lastPasswordChangeDate', Db::prepareDateForDb($warnBefore)])
         ->andWhere(['>=', 'users.lastPasswordChangeDate', Db::prepareDateForDb($expiredBefore)]);

      return $this->_applyExemptions($query);

   }

   /**
    * Excludes exempt users in SQL.
    *
    * Doing this in the query rather than filtering afterwards avoids loading
    * every candidate, and avoids a getGroups() call per user.
    */
   private function _applyExemptions(UserQuery $query): UserQuery
   {

      if ($this->settings->passwordExemptAdmins)
      {
         $query->admin(false);
      }

      $uids = $this->settings->passwordExemptGroups;

      if (!is_array($uids) || !$uids)
      {
         return $query;
      }

      $groupIds = [];

      foreach ($uids as $uid)
      {

         $group = Craft::$app->getUserGroups()->getGroupByUid($uid);

         if ($group)
         {
            $groupIds[] = $group->id;
         }

      }

      if ($groupIds)
      {
         $query->andWhere(['not', ['users.id' => (new Query())
            ->select(['userId'])
            ->from([Table::USERGROUPS_USERS])
            ->where(['groupId' => $groupIds])
         ]]);
      }

      return $query;

   }

   private function _expiryReminderAlreadySent(User $user): bool
   {

      $record = UserLoginRecord::findOne(['userId' => $user->id]);

      if (!$record || !$record->passwordExpiryReminderSentAt)
      {
         return false;
      }

      $changedAt = $this->lastPasswordChangeDate($user);

      if (!$changedAt)
      {
         return true;
      }

      return new \DateTime($record->passwordExpiryReminderSentAt) > $changedAt;

   }

   private function _markExpiryReminderSent(User $user): void
   {

      $record = UserLoginRecord::findOne(['userId' => $user->id]);

      if (!$record)
      {
         // ipHash/uaHash are notNull on installs created by the
         // m260430 migration, so seed them rather than blow up here.
         $record = new UserLoginRecord();
         $record->userId = $user->id;
         $record->ipHash = '';
         $record->uaHash = '';
      }

      $record->passwordExpiryReminderSentAt = Db::prepareDateForDb(new \DateTime());
      $record->save();

   }

}

<?php

namespace bymayo\porter\records;

use Craft;
use craft\db\ActiveRecord;
use craft\helpers\Db;

/**
 * @property int $id
 * @property int $userId
 * @property string $ipHash
 * @property string $uaHash
 * @property string|null $inactiveReminderSentAt
 * @property string|null $passwordExpiryReminderSentAt
 */
class UserLoginRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%porter_user_logins}}';
    }

    public static function stamp(int $userId, string $column): void
    {
        $now = Db::prepareDateForDb(new \DateTime());

        Db::upsert(
            static::tableName(),
            ['userId' => $userId, 'ipHash' => '', 'uaHash' => '', $column => $now],
            [$column => $now]
        );
    }
}

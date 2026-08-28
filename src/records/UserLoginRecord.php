<?php

namespace bymayo\porter\records;

use Craft;
use craft\db\ActiveRecord;

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
}

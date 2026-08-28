<?php

namespace bymayo\porter\records;

use Craft;
use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $userId
 * @property string $passwordHash
 */
class PasswordHistoryRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%porter_password_history}}';
    }
}

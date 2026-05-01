<?php

namespace bymayo\porter\records;

use Craft;
use craft\db\ActiveRecord;

class UserLoginRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%porter_user_logins}}';
    }
}

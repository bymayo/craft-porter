<?php

namespace bymayo\porter\records;

use bymayo\porter\Porter;

use Craft;
use craft\db\ActiveRecord;


/**
 * @property int $id
 * @property int $userId
 * @property string $token
 * @property bool $cpLogin
 * @property bool $newUser
 * @property string|null $newUserRedirect
 */
class MagicLinkRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%porter_magiclink}}';
    }
}

<?php

namespace bymayo\porter\records;

use bymayo\porter\Porter;

use Craft;
use craft\db\ActiveRecord;


class MagicLinkRecord extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%porter_magiclink}}';
    }
}

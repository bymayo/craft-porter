<?php

namespace bymayo\porter\migrations;

use Craft;
use craft\db\Migration;

class m260828_000000_add_cp_login_to_magiclink extends Migration
{
    public function safeUp(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_magiclink}}');

        if ($tableSchema !== null && $tableSchema->getColumn('cpLogin') === null) {

            $this->addColumn(
                '{{%porter_magiclink}}',
                'cpLogin',
                $this->boolean()->defaultValue(false)->notNull()
            );

            Craft::$app->db->schema->refresh();

        }

        return true;
    }

    public function safeDown(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_magiclink}}');

        if ($tableSchema !== null && $tableSchema->getColumn('cpLogin') !== null) {
            $this->dropColumn('{{%porter_magiclink}}', 'cpLogin');
        }

        return true;
    }
}

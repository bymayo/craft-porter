<?php

namespace bymayo\porter\migrations;

use Craft;
use craft\db\Migration;

class m260901_000000_add_new_user_to_magiclink extends Migration
{
    public function safeUp(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_magiclink}}');

        if ($tableSchema !== null && $tableSchema->getColumn('newUser') === null) {

            $this->addColumn(
                '{{%porter_magiclink}}',
                'newUser',
                $this->boolean()->defaultValue(false)->notNull()
            );

            Craft::$app->db->schema->refresh();

        }

        if ($tableSchema !== null && $tableSchema->getColumn('newUserRedirect') === null) {

            $this->addColumn(
                '{{%porter_magiclink}}',
                'newUserRedirect',
                $this->string()
            );

            Craft::$app->db->schema->refresh();

        }

        return true;
    }

    public function safeDown(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_magiclink}}');

        if ($tableSchema !== null && $tableSchema->getColumn('newUser') !== null) {
            $this->dropColumn('{{%porter_magiclink}}', 'newUser');
        }

        if ($tableSchema !== null && $tableSchema->getColumn('newUserRedirect') !== null) {
            $this->dropColumn('{{%porter_magiclink}}', 'newUserRedirect');
        }

        return true;
    }
}

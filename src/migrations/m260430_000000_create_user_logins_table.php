<?php

namespace bymayo\porter\migrations;

use Craft;
use craft\db\Migration;

class m260430_000000_create_user_logins_table extends Migration
{
    public function safeUp(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_user_logins}}');

        if ($tableSchema === null) {

            $this->createTable(
                '{{%porter_user_logins}}',
                [
                    'id' => $this->primaryKey(),
                    'userId' => $this->integer()->notNull(),
                    'ipHash' => $this->string(64)->notNull(),
                    'uaHash' => $this->string(64)->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid()
                ]
            );

            $this->createIndex(
                $this->db->getIndexName(
                    '{{%porter_user_logins}}',
                    'userId',
                    true
                ),
                '{{%porter_user_logins}}',
                'userId',
                true
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%porter_user_logins}}', 'userId'),
                '{{%porter_user_logins}}',
                'userId',
                '{{%users}}',
                'id',
                'CASCADE',
                'CASCADE'
            );

            Craft::$app->db->schema->refresh();

        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%porter_user_logins}}');
        return true;
    }
}

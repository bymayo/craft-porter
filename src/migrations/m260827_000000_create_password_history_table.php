<?php

namespace bymayo\porter\migrations;

use Craft;
use craft\db\Migration;

class m260827_000000_create_password_history_table extends Migration
{
    public function safeUp(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_password_history}}');

        if ($tableSchema === null) {

            $this->createTable(
                '{{%porter_password_history}}',
                [
                    'id' => $this->primaryKey(),
                    'userId' => $this->integer()->notNull(),
                    'passwordHash' => $this->string(255)->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid()
                ]
            );

            $this->createIndex(
                $this->db->getIndexName(
                    '{{%porter_password_history}}',
                    'userId',
                    false
                ),
                '{{%porter_password_history}}',
                'userId',
                false
            );

            $this->addForeignKey(
                $this->db->getForeignKeyName('{{%porter_password_history}}', 'userId'),
                '{{%porter_password_history}}',
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
        $this->dropTableIfExists('{{%porter_password_history}}');
        return true;
    }
}

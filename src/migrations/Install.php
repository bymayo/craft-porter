<?php

namespace bymayo\porter\migrations;

use bymayo\porter\Porter;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

class Install extends Migration
{
    // Public Properties
    // =========================================================================

    public $driver;

    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_magiclink}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%porter_magiclink}}',
                [
                    'id' => $this->primaryKey(),
                    'userId' => $this->integer()->notNull(),
                    'token' => $this->string()->notNull(),
                    'cpLogin' => $this->boolean()->defaultValue(false)->notNull(),
                    'newUser' => $this->boolean()->defaultValue(false)->notNull(),
                    'newUserRedirect' => $this->string(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid()
                ]
            );
        }

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_user_logins}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%porter_user_logins}}',
                [
                    'id' => $this->primaryKey(),
                    'userId' => $this->integer()->notNull(),
                    'ipHash' => $this->string(64),
                    'uaHash' => $this->string(64),
                    'inactiveReminderSentAt' => $this->dateTime(),
                    'passwordExpiryReminderSentAt' => $this->dateTime(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid()
                ]
            );
        }

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_password_history}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
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
        }

        return $tablesCreated;
    }

    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%porter_magiclink}}',
                'userId',
                true
            ),
            '{{%porter_magiclink}}',
            'userId',
            true
        );

        $this->createIndex(
            $this->db->getIndexName(
                '{{%porter_magiclink}}',
                'token',
                true
            ),
            '{{%porter_magiclink}}',
            'token',
            true
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
    }

    protected function addForeignKeys()
    {

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%porter_magiclink}}', 'userId'),
            '{{%porter_magiclink}}',
            'userId',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
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

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%porter_password_history}}', 'userId'),
            '{{%porter_password_history}}',
            'userId',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

    }

    protected function insertDefaultData()
    {
    }

    protected function removeTables()
    {
        $this->dropTableIfExists('{{%porter_magiclink}}');
        $this->dropTableIfExists('{{%porter_user_logins}}');
        $this->dropTableIfExists('{{%porter_password_history}}');
    }
}

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

        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
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

    }

    protected function insertDefaultData()
    {
    }

    protected function removeTables()
    {
        $this->dropTableIfExists('{{%porter_magiclink}}');
    }
}

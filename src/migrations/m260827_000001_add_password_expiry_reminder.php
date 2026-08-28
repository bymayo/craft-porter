<?php

namespace bymayo\porter\migrations;

use Craft;
use craft\db\Migration;

class m260827_000001_add_password_expiry_reminder extends Migration
{
    public function safeUp(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_user_logins}}');

        if ($tableSchema !== null && $tableSchema->getColumn('passwordExpiryReminderSentAt') === null) {

            $this->addColumn(
                '{{%porter_user_logins}}',
                'passwordExpiryReminderSentAt',
                $this->dateTime()->null()
            );

            Craft::$app->db->schema->refresh();

        }

        return true;
    }

    public function safeDown(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_user_logins}}');

        if ($tableSchema !== null && $tableSchema->getColumn('passwordExpiryReminderSentAt') !== null) {
            $this->dropColumn('{{%porter_user_logins}}', 'passwordExpiryReminderSentAt');
        }

        return true;
    }
}

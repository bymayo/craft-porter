<?php

namespace bymayo\porter\migrations;

use Craft;
use craft\db\Migration;

class m260501_000000_add_inactive_reminder_to_user_logins extends Migration
{
    public function safeUp(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_user_logins}}');

        if ($tableSchema === null) {
            return true;
        }

        if (!isset($tableSchema->columns['inactiveReminderSentAt'])) {
            $this->addColumn(
                '{{%porter_user_logins}}',
                'inactiveReminderSentAt',
                $this->dateTime()->after('uaHash')
            );
        }

        $this->alterColumn('{{%porter_user_logins}}', 'ipHash', $this->string(64));
        $this->alterColumn('{{%porter_user_logins}}', 'uaHash', $this->string(64));

        Craft::$app->db->schema->refresh();

        return true;
    }

    public function safeDown(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_user_logins}}');

        if ($tableSchema === null) {
            return true;
        }

        if (isset($tableSchema->columns['inactiveReminderSentAt'])) {
            $this->dropColumn('{{%porter_user_logins}}', 'inactiveReminderSentAt');
        }

        return true;
    }
}

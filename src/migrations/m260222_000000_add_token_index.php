<?php

namespace bymayo\porter\migrations;

use craft\db\Migration;

class m260222_000000_add_token_index extends Migration
{
    public function safeUp(): bool
    {
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

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropIndex(
            $this->db->getIndexName(
                '{{%porter_magiclink}}',
                'token',
                true
            ),
            '{{%porter_magiclink}}'
        );

        return true;
    }
}

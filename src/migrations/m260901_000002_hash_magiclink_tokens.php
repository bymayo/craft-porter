<?php

namespace bymayo\porter\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

/**
 * Converts stored magic link tokens to their SHA-256 digests.
 *
 * Existing rows hold the raw token, which means a dump of the table is a set
 * of usable sign in links. Hashing in place keeps links that are already in
 * someone's inbox working, since the digest of the raw token is exactly what
 * the new lookup compares against.
 */
class m260901_000002_hash_magiclink_tokens extends Migration
{
    public function safeUp(): bool
    {

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_magiclink}}');

        if ($tableSchema === null) {
            return true;
        }

        $rows = (new Query())
            ->select(['id', 'token'])
            ->from('{{%porter_magiclink}}')
            ->all();

        foreach ($rows as $row) {

            // Raw tokens come from Security::generateRandomString(), whose
            // alphabet is mixed case plus `-` and `_`. A digest is lower case
            // hex and nothing else, so anything already hashed is left alone
            // and running this twice can't double hash.
            if (preg_match('/^[0-9a-f]{64}$/', (string) $row['token'])) {
                continue;
            }

            $this->update(
                '{{%porter_magiclink}}',
                ['token' => hash('sha256', (string) $row['token'])],
                ['id' => $row['id']]
            );

        }

        return true;
    }

    public function safeDown(): bool
    {

        // A digest can't be turned back into the token it came from, so the
        // only honest reversal is to drop the tokens. They're short lived and
        // a user can always request another link.
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%porter_magiclink}}');

        if ($tableSchema !== null) {
            $this->delete('{{%porter_magiclink}}');
        }

        return true;
    }
}

<?php

namespace weareferal\assetversioner\migrations;

use Craft;
use craft\db\Migration;


class Install extends Migration
{
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
    }

    public function safeDown()
    {
        $this->dropTables();
    }

    public function createTables() 
    {
        $this->createTable('{{%assetversioner_keystore}}', [
            'id' => $this->primaryKey(),
            'key' => $this->string(),
            'value' => $this->string()
        ]);
    }

    public function dropTables()
    {
        $this->dropTable('{{%assetversioner_keystore}}');
    }

    public function createIndexes()
    {
        $this->createIndex(null, '{{%assetversioner_keystore}}', 'id', false);
        $this->createIndex(null, '{{%assetversioner_keystore}}', 'key', false);
    }
}

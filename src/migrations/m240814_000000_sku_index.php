<?php
namespace verbb\giftvoucher\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

class m240814_000000_sku_index extends Migration
{
    public function safeUp(): bool
    {
        $this->dropIndexIfExists('{{%giftvoucher_vouchers}}', 'sku', true);
        $this->createIndex(null, '{{%giftvoucher_vouchers}}', 'sku', false);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240814_000000_sku_index cannot be reverted.\n";
        return false;
    }
}


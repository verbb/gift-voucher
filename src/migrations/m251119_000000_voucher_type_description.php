<?php
namespace verbb\giftvoucher\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

class m251119_000000_voucher_type_description extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%giftvoucher_vouchertypes}}', 'descriptionFormat')) {
            $this->addColumn('{{%giftvoucher_vouchertypes}}', 'descriptionFormat', $this->string()->after('skuFormat'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m251119_000000_voucher_type_description cannot be reverted.\n";
        return false;
    }
}


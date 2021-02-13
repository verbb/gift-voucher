<?php
namespace verbb\giftvoucher\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m210213_000000_promotable_available extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%giftvoucher_vouchers}}', 'promotable', $this->boolean()->after('expiryDate'));
        $this->addColumn('{{%giftvoucher_vouchers}}', 'availableForPurchase', $this->boolean()->after('promotable'));

        $this->update('{{%giftvoucher_vouchers}}', ['promotable' => true]);
        $this->update('{{%giftvoucher_vouchers}}', ['availableForPurchase' => true]);
    }

    public function safeDown()
    {
        echo "m210213_000000_promotable_available cannot be reverted.\n";

        return false;
    }
}

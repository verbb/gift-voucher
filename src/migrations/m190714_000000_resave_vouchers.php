<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\db\Migration;
use craft\queue\jobs\ResaveElements;

class m190714_000000_resave_vouchers extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => Voucher::class,
        ]));

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190714_000000_resave_vouchers cannot be reverted.\n";
        return false;
    }
}

<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\queue\jobs\ResaveElements;

class m190714_000000_resave_vouchers extends Migration
{
    public function safeUp()
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => Voucher::class,
        ]));
    }

    public function safeDown()
    {
        echo "m190714_000000_resave_vouchers cannot be reverted.\n";
        return false;
    }
}

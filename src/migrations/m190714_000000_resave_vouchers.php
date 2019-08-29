<?php
/**
 * Craft CMS Plugins for Craft CMS 3.x
 *
 * Created with PhpStorm.
 *
 * @link      https://github.com/Anubarak/
 * @email     anubarak1993@gmail.com
 * @copyright Copyright (c) 2019 Robin Schambach
 */

namespace verbb\giftvoucher\migrations;

use Craft;
use craft\db\Migration;
use craft\queue\jobs\ResaveElements;
use verbb\giftvoucher\elements\Voucher;

class m190714_000000_resave_vouchers extends Migration
{
    public function safeUp()
    {
        Craft::$app->getQueue()->push(
            new ResaveElements(
                [
                    'elementType' => Voucher::class,
                ]
            )
        );
    }

    public function safeDown()
    {
        echo "m190714_000000_resave_vouchers cannot be reverted.\n";

        return false;
    }
}

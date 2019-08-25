<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\queue\jobs\ResaveElements;

class m190725_100000_resave_codes extends Migration
{
    public function safeUp()
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => Code::class,
        ]));
    }

    public function safeDown()
    {
        echo "m190725_100000_resave_codes cannot be reverted.\n";
        return false;
    }
}
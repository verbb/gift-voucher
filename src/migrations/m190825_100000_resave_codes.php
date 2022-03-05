<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\db\Migration;
use craft\queue\jobs\ResaveElements;

class m190825_100000_resave_codes extends Migration
{
    public function safeUp(): bool
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => Code::class,
        ]));

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190825_100000_resave_codes cannot be reverted.\n";
        return false;
    }
}
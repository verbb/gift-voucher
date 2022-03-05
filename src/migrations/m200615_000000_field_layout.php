<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Code;

use craft\db\Migration;
use craft\records\FieldLayout;

class m200615_000000_field_layout extends Migration
{
    public function safeUp(): bool
    {
        $this->insert(FieldLayout::tableName(), ['type' => Code::class]);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m200615_000000_field_layout cannot be reverted.\n";

        return false;
    }
}

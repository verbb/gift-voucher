<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\db\Migration;
use craft\records\FieldLayout;

class m200622_000000_resave_plugin extends Migration
{
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('plugins.gift-voucher.schemaVersion', true);
        if (version_compare($schemaVersion, '2.0.6', '>=')) {
            return;
        }

        $plugin = Craft::$app->getPlugins()->getPlugin('gift-voucher');

        if ($plugin === null) {
            return;
        }

        $settings = $plugin->getSettings()->toArray();

        Craft::$app->getPlugins()->savePluginSettings($plugin, $settings);
    }

    public function safeDown()
    {
        echo "m200622_000000_resave_plugin cannot be reverted.\n";

        return false;
    }
}

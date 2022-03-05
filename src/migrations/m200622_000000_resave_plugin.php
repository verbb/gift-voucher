<?php
namespace verbb\giftvoucher\migrations;

use Craft;
use craft\db\Migration;

class m200622_000000_resave_plugin extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('plugins.gift-voucher.schemaVersion', true);
        if (version_compare($schemaVersion, '2.0.6', '>=')) {
            return true;
        }

        $plugin = Craft::$app->getPlugins()->getPlugin('gift-voucher');

        if ($plugin === null) {
            return true;
        }

        $settings = $plugin->getSettings()->toArray();

        Craft::$app->getPlugins()->savePluginSettings($plugin, $settings);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m200622_000000_resave_plugin cannot be reverted.\n";

        return false;
    }
}

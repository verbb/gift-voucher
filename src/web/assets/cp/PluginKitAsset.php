<?php
namespace verbb\giftvoucher\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Plugin Kit's components for the setup checklist and empty states, registered only on the pages that show them.
 */
class PluginKitAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/giftvoucher/web/assets/cp/dist/kit';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'plugin-kit.js',
        ];

        $this->jsOptions = ['type' => 'module'];

        parent::init();
    }
}

<?php
namespace verbb\giftvoucher\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\web\assets\cp\CpAsset as VerbbCpAsset;

class GiftVoucherAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/giftvoucher/web/assets/cp/dist';

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
        ];

        $this->css = [
            'gift-voucher.css',
        ];

        $this->js = [
            'gift-voucher.js',
        ];

        parent::init();
    }
}

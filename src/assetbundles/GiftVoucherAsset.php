<?php
namespace verbb\giftvoucher\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\assetbundles\CpAsset as VerbbCpAsset;

class GiftVoucherAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@verbb/giftvoucher/resources/dist";

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
        ];

        $this->css = [
            'css/gift-voucher.css',
        ];

        $this->js = [
            'js/gift-voucher.js',
        ];

        parent::init();
    }
}

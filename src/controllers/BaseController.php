<?php
namespace verbb\giftvoucher\controllers;

use Craft;
use craft\web\Controller;

use verbb\giftvoucher\GiftVoucher;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = GiftVoucher::$plugin->getSettings();

        $this->renderTemplate('gift-voucher/settings', array(
            'settings' => $settings,
        ));
    }

}
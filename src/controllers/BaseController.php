<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;

use craft\web\Controller;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings(): void
    {
        $settings = GiftVoucher::$plugin->getSettings();

        $this->renderTemplate('gift-voucher/settings', [
            'settings' => $settings,
        ]);
    }
}

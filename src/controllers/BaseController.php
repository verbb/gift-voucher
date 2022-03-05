<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;

use craft\web\Controller;

use yii\web\Response;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings(): Response
    {
        $settings = GiftVoucher::$plugin->getSettings();

        return $this->renderTemplate('gift-voucher/settings', [
            'settings' => $settings,
        ]);
    }
}

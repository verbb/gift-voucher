<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;

use Craft;
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
            'selectedTab' => Craft::$app->getRequest()->getSegment(3) ?: 'general',
        ]);
    }
}

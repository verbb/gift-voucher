<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;

use Craft;

use yii\web\Response;

use verbb\base\controllers\SettingsController as BaseSettingsController;

class SettingsController extends BaseSettingsController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('gift-voucher/settings', [
            'settings' => GiftVoucher::$plugin->getSettings(),
            'selectedTab' => Craft::$app->getRequest()->getSegment(3) ?: 'general',
        ]);
    }
}

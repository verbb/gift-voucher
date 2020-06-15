<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\GiftVoucher;

use Craft;
use craft\db\Table;
use craft\web\Controller;

use yii\web\Response;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = GiftVoucher::$plugin->getSettings();

        $this->renderTemplate('gift-voucher/settings', [
            'settings' => $settings,
        ]);
    }
}

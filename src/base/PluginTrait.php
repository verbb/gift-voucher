<?php
namespace verbb\giftvoucher\base;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\integrations\klaviyoconnect\KlaviyoConnect;
use verbb\giftvoucher\services\CodesService;
use verbb\giftvoucher\services\PdfService;
use verbb\giftvoucher\services\RedemptionsService;
use verbb\giftvoucher\services\VouchersService;
use verbb\giftvoucher\services\VoucherTypesService;
use verbb\giftvoucher\storage\CodeStorageInterface;

use Craft;
use craft\log\FileTarget;
use craft\web\View;

use yii\base\Event;
use yii\log\Logger;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

    public function getCodes()
    {
        return $this->get('codes');
    }

    public function getPdf()
    {
        return $this->get('pdf');
    }

    public function getRedemptions()
    {
        return $this->get('redemptions');
    }

    public function getVouchers()
    {
        return $this->get('vouchers');
    }

    public function getVoucherTypes()
    {
        return $this->get('voucherTypes');
    }

    public function getCodeStorage()
    {
        return $this->get('codeStorage');
    }

    public static function log($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'gift-voucher');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'gift-voucher');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $settings = $this->getSettings();

        $this->setComponents([
            'codes' => CodesService::class,
            'klaviyoConnect' => KlaviyoConnect::class,
            'pdf' => PdfService::class,
            'redemptions' => RedemptionsService::class,
            'vouchers' => VouchersService::class,
            'voucherTypes' => VoucherTypesService::class,
            'codeStorage' => $settings->codeStorage,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging()
    {
        Craft::getLogger()->dispatcher->targets[] = new FileTarget([
            'logFile' => Craft::getAlias('@storage/logs/gift-voucher.log'),
            'categories' => ['gift-voucher'],
        ]);
    }

}

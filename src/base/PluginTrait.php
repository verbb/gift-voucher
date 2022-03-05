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

use yii\log\Logger;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static GiftVoucher $plugin;


    // Public Methods
    // =========================================================================

    public function getCodes(): CodesService
    {
        return $this->get('codes');
    }

    public function getPdf(): PdfService
    {
        return $this->get('pdf');
    }

    public function getRedemptions(): RedemptionsService
    {
        return $this->get('redemptions');
    }

    public function getVouchers(): VouchersService
    {
        return $this->get('vouchers');
    }

    public function getVoucherTypes(): VoucherTypesService
    {
        return $this->get('voucherTypes');
    }

    public function getCodeStorage(): CodeStorageInterface
    {
        return $this->get('codeStorage');
    }
    
    public function getKlaviyoConnect(): KlaviyoConnect
    {
        return $this->get('klaviyoConnect');
    }

    public static function log($message): void
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'gift-voucher');
    }

    public static function error($message): void
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'gift-voucher');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents(): void
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

    private function _setLogging(): void
    {
        BaseHelper::setFileLogging('gift-voucher');
    }

}

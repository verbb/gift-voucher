<?php
namespace verbb\giftvoucher\base;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\integrations\klaviyoconnect\KlaviyoConnect;
use verbb\giftvoucher\services\Codes;
use verbb\giftvoucher\services\Pdf;
use verbb\giftvoucher\services\Redemptions;
use verbb\giftvoucher\services\Vouchers;
use verbb\giftvoucher\services\VoucherTypes;
use verbb\giftvoucher\storage\CodeStorageInterface;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static GiftVoucher $plugin;


    // Static Methods
    // =========================================================================

    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('gift-voucher', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'gift-voucher');
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('gift-voucher', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'gift-voucher');
    }


    // Public Methods
    // =========================================================================

    public function getCodes(): Codes
    {
        return $this->get('codes');
    }

    public function getPdf(): Pdf
    {
        return $this->get('pdf');
    }

    public function getRedemptions(): Redemptions
    {
        return $this->get('redemptions');
    }

    public function getVouchers(): Vouchers
    {
        return $this->get('vouchers');
    }

    public function getVoucherTypes(): VoucherTypes
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


    // Private Methods
    // =========================================================================

    private function _registerComponents(): void
    {
        $settings = $this->getSettings();

        $this->setComponents([
            'codes' => Codes::class,
            'klaviyoConnect' => KlaviyoConnect::class,
            'pdf' => Pdf::class,
            'redemptions' => Redemptions::class,
            'vouchers' => Vouchers::class,
            'voucherTypes' => VoucherTypes::class,
            'codeStorage' => $settings->codeStorage,
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('gift-voucher');
    }

}

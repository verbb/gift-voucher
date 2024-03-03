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

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?GiftVoucher $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('gift-voucher');

        return [
            'components' => [
                'codes' => Codes::class,
                'klaviyoConnect' => KlaviyoConnect::class,
                'pdf' => Pdf::class,
                'redemptions' => Redemptions::class,
                'vouchers' => Vouchers::class,
                'voucherTypes' => VoucherTypes::class,
            ],
        ];
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
}

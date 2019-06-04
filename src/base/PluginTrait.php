<?php
namespace verbb\giftvoucher\base;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\services\CodesService;
use verbb\giftvoucher\services\PdfService;
use verbb\giftvoucher\services\RedemptionsService;
use verbb\giftvoucher\services\VouchersService;
use verbb\giftvoucher\services\VoucherTypesService;

use verbb\giftvoucher\integrations\klaviyoconnect\KlaviyoConnect;

use Craft;

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

    public function getKlaviyoConnect()
    {
        return $this->get('klaviyoConnect');
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

    private function _setPluginComponents()
    {
        $this->setComponents([
            'codes' => CodesService::class,
            'klaviyoConnect' => KlaviyoConnect::class,
            'pdf' => PdfService::class,
            'redemptions' => RedemptionsService::class,
            'vouchers' => VouchersService::class,
            'voucherTypes' => VoucherTypesService::class,
        ]);
    }

}
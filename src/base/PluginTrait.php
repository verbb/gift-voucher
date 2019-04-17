<?php
namespace verbb\giftvoucher\base;

use verbb\giftvoucher\services\CodesService;
use verbb\giftvoucher\services\PdfService;
use verbb\giftvoucher\services\RedemptionsService;
use verbb\giftvoucher\services\VouchersService;
use verbb\giftvoucher\services\VoucherTypesService;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

    /**
     * Get Codes Service
     *
     * @return CodesService
     */
    public function getCodes(): CodesService
    {
        return $this->get('codes');
    }

    /**
     * Get the PDF Service
     *
     * @return PdfService
     */
    public function getPdf(): PdfService
    {
        return $this->get('pdf');
    }

    /**
     * Get the Redemption Service
     *
     * @return RedemptionsService
     */
    public function getRedemptions(): RedemptionsService
    {
        return $this->get('redemptions');
    }

    /**
     * Get the Voucher Service
     *
     * @return VouchersService
     */
    public function getVouchers(): VouchersService
    {
        return $this->get('vouchers');
    }

    /**
     * Get the VoucherTypes Service
     *
     * @return VoucherTypesService
     */
    public function getVoucherTypes(): VoucherTypesService
    {
        return $this->get('voucherTypes');
    }

    private function _setPluginComponents()
    {
        $this->setComponents([
            'codes' => CodesService::class,
            'pdf' => PdfService::class,
            'redemptions' => RedemptionsService::class,
            'vouchers' => VouchersService::class,
            'voucherTypes' => VoucherTypesService::class,
        ]);
    }

}

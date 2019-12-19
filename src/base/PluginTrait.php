<?php
namespace verbb\giftvoucher\base;

use verbb\giftvoucher\services\CodesService;
use verbb\giftvoucher\services\PdfService;
use verbb\giftvoucher\services\RedemptionsService;
use verbb\giftvoucher\services\VouchersService;
use verbb\giftvoucher\services\VoucherTypesService;
use verbb\giftvoucher\storage\CodeStorageInterface;

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

    /**
     * Get the code Storage
     *
     * @return \verbb\giftvoucher\storage\CodeStorageInterface
     *
     * @author Robin Schambach
     * @since  2.0.16
     */
    public function getCodeStorage(): CodeStorageInterface
    {
        return $this->get('codeStorage');
    }

    private function _setPluginComponents()
    {
        /** @var \verbb\giftvoucher\models\Settings $settings */
        $settings = $this->getSettings();

        $this->setComponents([
            'codes' => CodesService::class,
            'pdf' => PdfService::class,
            'redemptions' => RedemptionsService::class,
            'vouchers' => VouchersService::class,
            'voucherTypes' => VoucherTypesService::class,
            'codeStorage' => $settings->codeStorage,
        ]);
    }

}

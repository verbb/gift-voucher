<?php

namespace Craft;

/**
 * Class GiftVoucherHelper
 * Creates helper functions for plugin/services to provide code completion
 *
 * @package Craft
 */
class GiftVoucherHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @return GiftVoucherPlugin
     */
    public static function getPlugin()
    {
        return craft()->plugins->getPlugin('giftVoucher');
    }

    /**
     * @return GiftVoucherService
     */
    public static function getService()
    {
        return craft()->giftVoucher;
    }

    /**
     * @return GiftVoucher_PluginService
     */
    public static function getPluginService()
    {
        return craft()->giftVoucher_plugin;
    }

    /**
     * @return GiftVoucher_LicenseService
     */
    public static function getLicenseService()
    {
        return craft()->giftVoucher_license;
    }

    /**
     * @return GiftVoucher_VouchersService
     */
    public static function getVouchersService()
    {
        return craft()->giftVoucher_vouchers;
    }

    /**
     * @return GiftVoucher_VoucherTypesService
     */
    public static function getVoucherTypesService()
    {
        return craft()->giftVoucher_voucherTypes;
    }

    /**
     * @return GiftVoucher_CodesService
     */
    public static function getCodesService()
    {
        return craft()->giftVoucher_codes;
    }

    /**
     * @return GiftVoucher_PdfService
     */
    public static function getPdfService()
    {
        return craft()->giftVoucher_pdf;
    }

    /**
     * @return GiftVoucher_RedemptionService
     */
    public static function getRedemptionService()
    {
        return craft()->giftVoucher_redemption;
    }
}
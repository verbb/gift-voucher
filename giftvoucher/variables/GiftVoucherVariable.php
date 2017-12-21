<?php

namespace Craft;

use Commerce\Adjusters\Commerce_DiscountAdjuster;
use GiftVoucher\Adjusters\GiftVoucher_DiscountAdjuster;

class GiftVoucherVariable
{
    /**
     * @return GiftVoucherPlugin
     */
    public function getPlugin()
    {
        return GiftVoucherHelper::getPlugin();
    }

    /**
     * @return string
     */
    public function getPluginUrl()
    {
        return $this->getPlugin()->getPluginUrl();
    }

    /**
     * @return string
     */
    public function getPluginName()
    {
        return $this->getPlugin()->getName();
    }

    /**
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->getPlugin()->getVersion();
    }

    /**
     * @return bool
     */
    public function isLicensed()
    {
        return GiftVoucherHelper::getLicenseService()->isLicensed();
    }

    /**
     * @return mixed
     */
    public function getEdition()
    {
        return GiftVoucherHelper::getLicenseService()->getEdition();
    }

    /**
     * Return array of all Voucher Types.
     *
     * @return GiftVoucher_VoucherTypeModel[]
     */
    public function getAllVoucherTypes()
    {
        return GiftVoucherHelper::getVoucherTypesService()->getVoucherTypes();
    }

    /**
     * Get all Vouchers Codes.
     *
     * @return GiftVoucher_CodeModel[]
     */
    public function getAllCodes()
    {
        return GiftVoucherHelper::getCodesService()->getCodes();
    }

    /**
     * Get all Vouchers.
     *
     * @param array|null $criteria
     *
     * @return ElementCriteriaModel
     * @throws Exception
     */
    public function vouchers(array $criteria = null)
    {
        return craft()->elements->getCriteria('GiftVoucher_Voucher', $criteria);
    }

    /**
     * Get the Voucher Code stored in the session.
     *
     * @return string
     */
    public function getVoucherCode()
    {
        return craft()->httpSession->get('giftVoucher.giftVoucherCode');
    }

    /**
     * Get purchased codes
     *
     * @param array $attributes
     * @param array $options
     *
     * @return GiftVoucher_CodeModel[]
     */
    public function purchasedCodes(array $attributes = array(), array $options = array())
    {
        return GiftVoucherHelper::getCodesService()->getCodes($attributes, $options);
    }

    /**
     * Get the URL to the PDF file for all voucher codes’s of that order.
     *
     * @param string $orderNumber
     *
     * @return false|string
     * @throws Exception
     */
    public function getOrderPdfUrl($orderNumber)
    {
        return GiftVoucherHelper::getPdfService()->getPdfUrl($orderNumber);
    }

    /**
     * Get the URL to the PDF file for the voucher codes’s of that line item.
     *
     * @param Commerce_LineItemModel $lineItem
     *
     * @return null|string
     * @throws Exception
     */
    public function getPdfUrl(Commerce_LineItemModel $lineItem)
    {   
        if ($this->isVoucher($lineItem)) {
            $orderNumber = $lineItem->order->number;

            return GiftVoucherHelper::getPdfService()->getPdfUrl($orderNumber, $lineItem->id);
        }

        return null;
    }

    /**
     * Checks if the line item is a voucher
     *
     * @param Commerce_LineItemModel $lineItem
     *
     * @return bool
     */
    public function isVoucher(Commerce_LineItemModel $lineItem)
    {
        return ($lineItem->purchasable->elementType == 'GiftVoucher_Voucher');
    }

    /**
     * Checks if the order adjustment is a voucher
     *
     * @param Commerce_OrderAdjustmentModel $adjuster
     *
     * @return bool
     */
    public function isVoucherAdjustment(Commerce_OrderAdjustmentModel $adjuster)
    {
        return (isset($adjuster->optionsJson['type']) && $adjuster->optionsJson['type'] == 'GiftVoucher');
    }
}

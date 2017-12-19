<?php

namespace GiftVoucher\Adjusters;

use function Craft\craft;
use Commerce\Adjusters\Commerce_AdjusterInterface;
use Craft\Commerce_LineItemModel;
use Craft\Commerce_OrderAdjustmentModel;
use Craft\Commerce_OrderModel;
use Craft\DateTime;
use Craft\GiftVoucher_CodeModel;
use Craft\GiftVoucherHelper;

class GiftVoucher_DiscountAdjuster implements Commerce_AdjusterInterface
{
    const ADJUSTMENT_TYPE = 'Discount';

    /**
     * @param Commerce_OrderModel      $order
     * @param Commerce_LineItemModel[] $lineItems
     *
     * @return \Craft\Commerce_OrderAdjustmentModel[]
     */
    public function adjust(Commerce_OrderModel &$order, array $lineItems = [])
    {
        if (empty($lineItems)) {
            return [];
        }

        // Get code by session
        $code = craft()->httpSession->get('giftVoucher.giftVoucherCode');

        if (!$code) {
            return [];
        }

        $voucherCode = GiftVoucherHelper::getCodesService()->getCodeByCodeKey($code);

        if (!$voucherCode) {
            return [];
        }

        return [
            $this->_getAdjustment($order, $voucherCode, $code),
        ];
    }

    /**
     * @param Commerce_OrderModel   $order
     * @param GiftVoucher_CodeModel $voucherCode
     * @param string                $code
     *
     * @return Commerce_OrderAdjustmentModel|false
     */
    private function _getAdjustment(Commerce_OrderModel $order, GiftVoucher_CodeModel $voucherCode, $code)
    {
        //preparing model
        $adjustment = new Commerce_OrderAdjustmentModel;
        $adjustment->type = self::ADJUSTMENT_TYPE;
        $adjustment->name = $voucherCode->getVoucher()->title;
        $adjustment->orderId = $order->id;
        $adjustment->description = $voucherCode->getVoucher()->getDescription() ?: $voucherCode->amount;
        $adjustment->optionsJson = ['lineItemsAffected' => null, 'code' => $code];
        $adjustment->included = false;


        // Check if not redeemed yet
        if ($voucherCode->redeemed) {
            return false;
        }

        // Check for expiry date
        $today = new DateTime();
        if ($voucherCode->expiryDate && $voucherCode->expiryDate->format('Ymd') < $today->format('Ymd')) {
            return false;
        }

        $orderTotal = $order->itemTotal;
        $orderTotal += $order->getTotalTax();
        $orderTotal += $order->getTotalShippingCost();

        if ($orderTotal < $voucherCode->amount) {
            $adjustment->amount = $orderTotal * -1;
        } else {
            $adjustment->amount = (float)$voucherCode->amount * -1;
        }

        $order->baseDiscount += $adjustment->amount;

        return $adjustment;
    }
}

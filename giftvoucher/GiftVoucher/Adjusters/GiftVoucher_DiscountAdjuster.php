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
        $giftVoucherCodes = craft()->httpSession->get('giftVoucher.giftVoucherCodes');
        
        if (!$giftVoucherCodes || count($giftVoucherCodes) == 0) {
            return [];
        }

        $adjuster = [];

        foreach ($giftVoucherCodes as $giftVoucherCode) {
            $voucherCode = GiftVoucherHelper::getCodesService()->getCodeByCodeKey($giftVoucherCode);

            if ($voucherCode) {
                $adjuster[] = $this->_getAdjustment($order, $voucherCode);
            }
        }

        return $adjuster;
    }

    /**
     * @param Commerce_OrderModel   $order
     * @param GiftVoucher_CodeModel $voucherCode
     *
     * @return Commerce_OrderAdjustmentModel|false
     */
    private function _getAdjustment(Commerce_OrderModel $order, GiftVoucher_CodeModel $voucherCode)
    {
        //preparing model
        $adjustment = new Commerce_OrderAdjustmentModel;
        $adjustment->type = self::ADJUSTMENT_TYPE;
        $adjustment->name = $voucherCode->getVoucher()->title;
        $adjustment->orderId = $order->id;
        $adjustment->description = 'GiftVoucher discount using code ' . $voucherCode->codeKey;

        $lineItemsAffected = [];
        foreach ($order->getLineItems() as $lineItem) {
            $lineItemsAffected[] = $lineItem->id;
        }

        // Set adjustment options
        $options = [
            'lineItemsAffected' => $lineItemsAffected,
            'type' => 'GiftVoucher',
            'id' => $voucherCode->id,
            'name' => $adjustment->name,
            'description' => $adjustment->description,
            'code' => $voucherCode->codeKey,
            'originalAmount' => $voucherCode->originalAmount,
            'currentAmount' => $voucherCode->currentAmount,
            'expiryDate' => $voucherCode->expiryDate,
            'manually' => $voucherCode->manually,
        ];
        $adjustment->optionsJson = $options;
        $adjustment->included = false;

        // Check if there is a amount left
        if ($voucherCode->currentAmount <= 0) {
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
        $orderTotal += $order->getTotalDiscount();

        if ($orderTotal < $voucherCode->currentAmount) {
            $adjustment->amount = $orderTotal * -1;
        } else {
            $adjustment->amount = (float)$voucherCode->currentAmount * -1;
        }

        $order->baseDiscount += $adjustment->amount;

        return $adjustment;
    }
}

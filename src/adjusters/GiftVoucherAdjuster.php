<?php

namespace verbb\giftvoucher\adjusters;

use Craft;
use craft\base\Component;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\OrderAdjustment;
use craft\commerce\Plugin as Commerce;
use DateTime;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\events\VoucherAdjustmentsEvent;
use verbb\giftvoucher\GiftVoucher;

class GiftVoucherAdjuster extends Component implements AdjusterInterface
{
    // Constants
    // =========================================================================

    const EVENT_AFTER_VOUCHER_ADJUSTMENTS_CREATED = 'afterVoucherAdjustmentsCreated';
    const ADJUSTMENT_TYPE = 'discount';
    // Properties
    // =========================================================================

    private $_orderTotal;
    // Public Methods
    // =========================================================================

    public function adjust(Order $order): array
    {
        $adjustments = [];

        $this->_orderTotal = $order->getTotalPrice();
        $settings = GiftVoucher::getInstance()->getSettings();

        // Get code by session
        $giftVoucherCodes = [];
        // secure "resaveElements" because the QueueController does "$response->sendAndClose() -> the session is already closed
        if((bool)Craft::$app->getRequest()->getIsConsoleRequest() === false && Craft::$app->getSession()->getIsActive() === true){
            $giftVoucherCodes = Craft::$app->getSession()->get('giftVoucher.giftVoucherCodes');
        }

        if (!$giftVoucherCodes || count($giftVoucherCodes) == 0) {
            return [];
        }

        foreach ($giftVoucherCodes as $giftVoucherCode) {
            $voucherCode = Code::find()->where(['=', 'codeKey', $giftVoucherCode])->one();

            if ($voucherCode) {
                $adjustment = $this->_getAdjustment($order, $voucherCode);

                if ($adjustment) {
                    $adjustments[] = $adjustment;
                }
            }
        }

        // Check to see if there's any discounts that should stop processing
        if ($settings->stopProcessing) {
            $discounts = Commerce::getInstance()->getDiscounts()->getAllDiscounts();

            foreach ($discounts as $discount) {
                // Is this discount set to stop processing?
                if ($discount->stopProcessing) {
                    // Is this discount applied on the order?
                    if ($order->couponCode && (strcasecmp($order->couponCode, $discount->code) == 0)) {
                        return [];
                    }
                }
            }
        }

        // Raise the 'afterVoucherAdjustmentsCreated' event
        $event = new VoucherAdjustmentsEvent(
            [
                'order'            => $order,
                'giftVoucherCodes' => $giftVoucherCodes,
                'adjustments'      => $adjustments,
            ]
        );

        $this->trigger(self::EVENT_AFTER_VOUCHER_ADJUSTMENTS_CREATED, $event);

        if (!$event->isValid) {
            return [];
        }

        return $event->adjustments;
    }


    // Private Methods
    // =========================================================================

    private function _getAdjustment(Order $order, Code $voucherCode)
    {
        //preparing model
        $adjustment = new OrderAdjustment;
        $adjustment->type = self::ADJUSTMENT_TYPE;
        $adjustment->name = $voucherCode->getVoucher()->title;
        $adjustment->orderId = $order->id;
        $adjustment->description = Craft::t(
            'gift-voucher',
            'Gift Voucher discount using code {code}',
            [
                'code' => $voucherCode->codeKey
            ]
        );
        $adjustment->sourceSnapshot = $voucherCode->attributes;

        // Check if there is a amount left
        if ($voucherCode->currentAmount <= 0) {
            return false;
        }

        // Check for expiry date
        $today = new DateTime();
        if ($voucherCode->expiryDate && $voucherCode->expiryDate->format('Ymd') < $today->format('Ymd')) {
            return false;
        }

        // Make sure we don't go negative - also taking into account multiple vouchers on one order
        if ($this->_orderTotal < $voucherCode->currentAmount) {
            $adjustment->amount = $this->_orderTotal * -1;
        } else {
            $adjustment->amount = (float) $voucherCode->currentAmount * -1;
        }

        $this->_orderTotal += $adjustment->amount;

        return $adjustment;
    }
}

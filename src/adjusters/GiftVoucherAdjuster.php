<?php
namespace verbb\giftvoucher\adjusters;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\OrderAdjustment;

use DateTime;

class GiftVoucherAdjuster implements AdjusterInterface
{
    // Constants
    // =========================================================================

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

        // Get code by session
        $giftVoucherCodes = Craft::$app->getSession()->get('giftVoucher.giftVoucherCodes');
        
        if (!$giftVoucherCodes || count($giftVoucherCodes) == 0) {
            return [];
        }

        foreach ($giftVoucherCodes as $giftVoucherCode) {
            $voucherCode = Code::find()
                ->where(['=', 'codeKey', $giftVoucherCode])
                ->one();

            if ($voucherCode) {
                $adjustment = $this->_getAdjustment($order, $voucherCode);

                if ($adjustment) {
                    $adjustments[] = $adjustment;
                }
            }
        }

        return $adjustments;
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
        $adjustment->description = 'Gift Voucher discount using code ' . $voucherCode->codeKey;
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
            $adjustment->amount = (float)$voucherCode->currentAmount * -1;
        }

        $this->_orderTotal += $adjustment->amount;

        return $adjustment;
    }
}

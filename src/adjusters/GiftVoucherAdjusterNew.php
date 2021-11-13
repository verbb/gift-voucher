<?php
namespace verbb\giftvoucher\adjusters;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\events\VoucherAdjustmentsEvent;

use Craft;
use craft\base\Component;

use craft\commerce\Plugin as Commerce;
use craft\commerce\adjusters\Discount;
use craft\commerce\adjusters\Shipping;
use craft\commerce\adjusters\Tax;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\OrderAdjustment;

use DateTime;

class GiftVoucherAdjusterNew extends Component implements AdjusterInterface
{
    // Constants
    // =========================================================================

    const EVENT_AFTER_VOUCHER_ADJUSTMENTS_CREATED = 'afterVoucherAdjustmentsCreated';


    // Properties
    // =========================================================================

    // protected $_total;
    const ADJUSTMENT_TYPE = 'voucher';


    // Public Methods
    // =========================================================================

    public function adjust(Order $order): array
    {
        $adjustments = [];

        $settings = GiftVoucher::getInstance()->getSettings();

        // Get code by session
        $giftVoucherCodes = GiftVoucher::getInstance()->getCodeStorage()->getCodeKeys($order);

        if (!$giftVoucherCodes || count($giftVoucherCodes) == 0) {
            return [];
        }

        $voucherCode = null;
        $totalAvailableVoucher = null;

        // Validate all applied vouchers to get the total available amount to take off the order
        // which will be split over tax/shipping/discount adjusters
        foreach ($giftVoucherCodes as $giftVoucherCode) {
            $voucherCode = Code::find()->where(['=', 'codeKey', $giftVoucherCode])->one();

            if ($voucherCode) {
                $totalAvailableVoucher += $this->_getVoucherAmount($voucherCode);
            }
        }

        if (!$voucherCode) {
            return [];
        }

        // In order to properly discount the order's total, we need to negate 3 things, the line items
        // (which includes any core discounts already negated), any non-included Tax, and Shipping.
        // It would be SO much easier to just be able to take an amount off the order total, but we end up
        // with a strange UI that still shows the values for adjusters, despite the order being $0.
        // This also gets complicated with other taxes too (tax on shipping).

        // Save a flag on each adjustment to track that this is a Gift Voucher adjustment
        // because each snapshot's type is set to each core adjuster, so not an easy way to track
        // what is a Gift Voucher adjuster later on in the process when it comes to tracking redemptions.
        $sourceSnapshot = $voucherCode->attributes;
        $sourceSnapshot['giftVoucherPluginCode'] = true;

        $itemSubTotal = $order->getItemSubtotal();
        $discountTotal = $order->getTotalDiscount();

        // Get the total line item amount for the order (includes any discounts already applied)
        // And start with discounting that before moving on to discounting shipping and tax.
        $itemTotal = ($itemSubTotal + $discountTotal);

        if ($itemTotal) {
            $voucherAmount = ($itemTotal >= $totalAvailableVoucher) ? $totalAvailableVoucher : $itemTotal;
            $totalAvailableVoucher -= $voucherAmount;

            if ($voucherAmount) {
                $adjustment = new OrderAdjustment;
                $adjustment->name = $voucherCode->getVoucher()->title;
                $adjustment->amount = -$voucherAmount;
                $adjustment->orderId = $order->id;
                $adjustment->type = Discount::ADJUSTMENT_TYPE;
                $adjustment->sourceSnapshot = $sourceSnapshot;
                $adjustment->description = Craft::t('gift-voucher', 'Gift Voucher discount using code {code}', [
                    'code' => $voucherCode->codeKey,
                ]);

                $adjustments[] = $adjustment;
            }
        }

        // Handle discounting shipping next, if there's any more amount on the voucher
        $shippingAdjusters = $order->getAdjustmentsByType(Shipping::ADJUSTMENT_TYPE);

        foreach ($shippingAdjusters as $shippingAdjuster) {
            $shippingTotal = $shippingAdjuster->amount;
            $shippingAmount = ($shippingTotal >= $totalAvailableVoucher) ? $totalAvailableVoucher : $shippingTotal;
            $totalAvailableVoucher -= $shippingAmount;

            if ($shippingAmount) {
                $adjustment = new OrderAdjustment;
                $adjustment->name = Craft::t('gift-voucher', '{name} Removed', ['name' => $shippingAdjuster->name]);
                $adjustment->amount = -$shippingAmount;
                $adjustment->orderId = $order->id;
                $adjustment->type = Shipping::ADJUSTMENT_TYPE;
                $adjustment->sourceSnapshot = $sourceSnapshot;
                
                $adjustments[] = $adjustment;
            }
        }

        // Finally do the same thing with un-included tax
        $taxAdjusters = $order->getAdjustmentsByType(Tax::ADJUSTMENT_TYPE);

        foreach ($taxAdjusters as $taxAdjuster) {
            $taxTotal = $taxAdjuster->amount;
            $taxAmount = ($taxTotal >= $totalAvailableVoucher) ? $totalAvailableVoucher : $taxTotal;
            $totalAvailableVoucher -= $taxAmount;

            if ($taxAmount) {
                $adjustment = new OrderAdjustment;
                $adjustment->name = Craft::t('gift-voucher', '{name} Removed', ['name' => $taxAdjuster->name]);
                $adjustment->amount = -$taxAmount;
                $adjustment->orderId = $order->id;
                $adjustment->type = Tax::ADJUSTMENT_TYPE;
                $adjustment->sourceSnapshot = $sourceSnapshot;

                $adjustments[] = $adjustment;
            }
        }

        // Raise the 'afterVoucherAdjustmentsCreated' event
        $event = new VoucherAdjustmentsEvent([
            'order' => $order,
            'giftVoucherCodes' => $giftVoucherCodes,
            'adjustments' => $adjustments,
        ]);

        $this->trigger(self::EVENT_AFTER_VOUCHER_ADJUSTMENTS_CREATED, $event);

        if (!$event->isValid) {
            return [];
        }

        return $event->adjustments;
    }


    // Protected Methods
    // =========================================================================

    private function _getVoucherAmount($voucherCode)
    {
        // Check if there is a amount left
        if ($voucherCode->currentAmount <= 0) {
            return 0;
        }

        // Check for expiry date
        $today = new DateTime();

        if ($voucherCode->expiryDate && $voucherCode->expiryDate->format('Ymd') < $today->format('Ymd')) {
            return 0;
        }

        // Make sure we don't go negative - also taking into account multiple vouchers on one order
        return (float)$voucherCode->currentAmount;
    }
}

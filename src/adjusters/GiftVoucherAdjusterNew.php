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

        $sourceSnapshot = $voucherCode->attributes;
        $sourceSnapshot['giftVoucherPluginCode'] = true;

        $voucherAmount = ($order->total >= $totalAvailableVoucher) ? $totalAvailableVoucher : $order->total;

        $adjustment = new OrderAdjustment;
        $adjustment->name = 'Gift Voucher';
        $adjustment->amount = -$voucherAmount;
        $adjustment->orderId = $order->id;
        $adjustment->type = self::ADJUSTMENT_TYPE;
        $adjustment->sourceSnapshot = $sourceSnapshot;
        $adjustment->description = Craft::t('gift-voucher', 'Gift Voucher discount using code {code}', [
            'code' => $voucherCode->codeKey,
        ]);

        $adjustments[] = $adjustment;

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

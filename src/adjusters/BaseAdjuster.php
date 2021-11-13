<?php
namespace verbb\giftvoucher\adjusters;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\events\VoucherAdjustmentsEvent;

use Craft;
use craft\base\Component;

use craft\commerce\Plugin as Commerce;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\OrderAdjustment;

use DateTime;

class BaseAdjuster extends Component implements AdjusterInterface
{
    // Constants
    // =========================================================================

    const EVENT_AFTER_VOUCHER_ADJUSTMENTS_CREATED = 'afterVoucherAdjustmentsCreated';


    // Properties
    // =========================================================================

    protected $_total;


    // Public Methods
    // =========================================================================

    public function adjust(Order $order): array
    {
        $adjustments = [];

        // Allow adjusters to cancel the entire adjust depending on its init value
        // ie: when shipping isn't even set yet.
        if ($this->initTotal($order) === false) {
            return [];
        }
        
        $settings = GiftVoucher::getInstance()->getSettings();

        // Get code by session
        $giftVoucherCodes = GiftVoucher::getInstance()->getCodeStorage()->getCodeKeys($order);

        if (!$giftVoucherCodes || count($giftVoucherCodes) == 0) {
            return [];
        }

        foreach ($giftVoucherCodes as $giftVoucherCode) {
            $voucherCode = Code::find()->where(['=', 'codeKey', $giftVoucherCode])->one();

            if ($voucherCode) {
                $adjustment = $this->prepAdjustment($order, $voucherCode);

                if ($adjustment) {
                    $adjustments[] = $adjustment;
                }
            }
        }

        // Check to see if there's any discounts that should stop processing
        if ($settings->stopProcessing) {
            $discounts = Commerce::getInstance()->getDiscounts()->getAllDiscounts();

            foreach ($discounts as $discount) {
                // Is this discount applied on the order?
                if ($order->couponCode && (strcasecmp($order->couponCode, $discount->code) == 0)) {
                    return [];
                }
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

    public function prepAdjustment(Order $order, Code $voucherCode)
    {
        return $this->getAdjustment($order, $voucherCode);
    }


    // Protected Methods
    // =========================================================================

    public function initTotal(Order $order): bool
    {
        return true;
    }

    protected function getAdjustment(Order $order, Code $voucherCode)
    {
        $adjustment = new OrderAdjustment;
        $adjustment->type = static::ADJUSTMENT_TYPE;
        $adjustment->name = $voucherCode->getVoucher()->title;
        $adjustment->orderId = $order->id;
        $adjustment->description = Craft::t('gift-voucher', 'Gift Voucher discount using code {code}', [
            'code' => $voucherCode->codeKey
        ]);
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
        if ($this->_total < $voucherCode->currentAmount) {
            $adjustment->amount = $this->_total * -1;
        } else {
            $adjustment->amount = (float) $voucherCode->currentAmount * -1;
        }

        $this->_total += $adjustment->amount;

        return $adjustment;
    }
}

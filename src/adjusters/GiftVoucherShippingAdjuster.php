<?php
namespace verbb\giftvoucher\adjusters;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\commerce\elements\Order;

class GiftVoucherShippingAdjuster extends BaseAdjuster
{
    // Constants
    // =========================================================================

    const ADJUSTMENT_TYPE = 'shipping';


    // Public Methods
    // =========================================================================

    public function initTotal(Order $order): bool
    {
        $this->_total = $order->getTotalShippingCost();

        // If no shipping set yet, or if $0, no need to show a discount.
        return ($this->_total == 0) ? false : true;
    }

    public function prepAdjustment(Order $order, Code $voucherCode)
    {
        // Very important to get the already-applied value used in the discount
        // before proceeding. Otherwise, we end up with a used discount code but still get
        // the value applied to shipping - not what we want at all.
        $discountAmount = 0;

        foreach ($order->getAdjustments() as $adjustment) {
            if ($adjustment->type === 'voucher') {
                // Check for multiple codes, that this matches the correct one
                $codeKey = $adjustment->sourceSnapshot['codeKey'] ?? '';

                // Because the new method of discounts now spreads a discount over line items, we need to
                // ensure we accumulate the amount, which will be for multiple line items, not just the overall order.
                if ($codeKey === $voucherCode->codeKey) {
                    $discountAmount += $adjustment->amount;
                }
            }
        }

        // Update the voucher code's available amount to include what's already currently been applied
        // in the other, regular discount adjuster.
        $voucherCode->currentAmount += $discountAmount;

        return $this->getAdjustment($order, $voucherCode);
    }
    
}

<?php
namespace verbb\giftvoucher\adjusters;

use craft\commerce\elements\Order;

class GiftVoucherAdjuster extends BaseAdjuster
{
    // Constants
    // =========================================================================

    const ADJUSTMENT_TYPE = 'voucher';


    // Public Methods
    // =========================================================================

    public function initTotal(Order $order): bool
    {
        $this->_total = $order->getItemTotal();

        return true;
    }
    
}

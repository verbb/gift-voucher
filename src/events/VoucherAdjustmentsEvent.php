<?php
namespace verbb\giftvoucher\events;

use craft\events\CancelableEvent;

use craft\commerce\elements\Order;

class VoucherAdjustmentsEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public Order $order;
    public array $giftVoucherCodes = [];
    public array $adjustments = [];
}

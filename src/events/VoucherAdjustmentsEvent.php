<?php
namespace verbb\giftvoucher\events;

use craft\events\CancelableEvent;

class VoucherAdjustmentsEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $order;
    public $giftVoucherCodes;
    public $adjustments;
}

<?php
namespace verbb\giftvoucher\events;

use craft\commerce\elements\Order;
use craft\commerce\models\Discount;
use craft\commerce\models\OrderAdjustment;
use craft\events\CancelableEvent;

class VoucherAdjustmentsEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $order;
    public $giftVoucherCodes;
    public $adjustments;
}

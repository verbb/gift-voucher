<?php
namespace verbb\giftvoucher\events;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

use yii\base\Event;

class PopulateCodeFromLineItemEvent extends Event
{
    // Properties
    // =========================================================================

    public LineItem $lineItem;
    public Order $order;
    public Code $code;
    public array $customFields;
    public Voucher $voucher;
    
}

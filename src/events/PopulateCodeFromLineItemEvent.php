<?php
namespace verbb\giftvoucher\events;

use yii\base\Event;

class PopulateCodeFromLineItemEvent extends Event
{
    // Properties
    // =========================================================================

    public $lineItem;
    public $order;
    public $code;
    public $customFields;
    public $voucher;
    
}

<?php
namespace verbb\giftvoucher\events;

use yii\base\Event;

class VoucherTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public $voucherType;
    public $isNew = false;

}

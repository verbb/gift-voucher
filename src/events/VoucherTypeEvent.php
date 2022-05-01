<?php
namespace verbb\giftvoucher\events;

use verbb\giftvoucher\models\VoucherType;

use yii\base\Event;

class VoucherTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public VoucherType $voucherType;
    public bool $isNew = false;
    
}

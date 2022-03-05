<?php
namespace verbb\giftvoucher\events;

use verbb\giftvoucher\models\VoucherTypeModel;

use yii\base\Event;

class VoucherTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public VoucherTypeModel $voucherType;
    public bool $isNew = false;
    
}

<?php
namespace verbb\giftvoucher\events;

use verbb\giftvoucher\models\Redemption;

use yii\base\Event;

class RedemptionEvent extends Event
{
    // Properties
    // =========================================================================

    public Redemption $redemption;
    public bool $isNew = false;
    
}

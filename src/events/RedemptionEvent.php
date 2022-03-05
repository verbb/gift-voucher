<?php
namespace verbb\giftvoucher\events;

use yii\base\Event;

class RedemptionEvent extends Event
{
    // Properties
    // =========================================================================

    public $redemption;
    public bool $isNew = false;
    
}

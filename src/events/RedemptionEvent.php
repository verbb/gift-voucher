<?php
namespace verbb\giftvoucher\events;

use verbb\giftvoucher\models\RedemptionModel;

use yii\base\Event;

class RedemptionEvent extends Event
{
    // Properties
    // =========================================================================

    public RedemptionModel $redemption;
    public bool $isNew = false;
    
}

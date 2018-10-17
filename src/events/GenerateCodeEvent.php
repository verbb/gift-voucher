<?php
namespace verbb\giftvoucher\events;

use yii\base\Event;

class GenerateCodeEvent extends Event
{
    // Properties
    // =========================================================================

    public $code;
    public $codeKey;

}

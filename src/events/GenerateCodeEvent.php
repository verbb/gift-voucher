<?php
namespace verbb\giftvoucher\events;

use verbb\giftvoucher\elements\Code;

use yii\base\Event;

class GenerateCodeEvent extends Event
{
    // Properties
    // =========================================================================

    public Code $code;
    public ?string $codeKey = null;

}

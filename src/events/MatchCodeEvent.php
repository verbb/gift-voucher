<?php
namespace verbb\giftvoucher\events;

use craft\events\CancelableEvent;
use verbb\giftvoucher\elements\Code;

class MatchCodeEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /** @var Code */
    public Code $code;
    public $codeKey;
    public string $error = '';
}

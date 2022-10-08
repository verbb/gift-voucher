<?php
namespace verbb\giftvoucher\events;

use craft\events\CancelableEvent;
use verbb\giftvoucher\elements\Code;

class MatchCodeEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public ?Code $code = null;
    public ?string $codeKey = null;
    public ?string $error = null;
}

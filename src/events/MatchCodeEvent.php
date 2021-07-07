<?php
namespace verbb\giftvoucher\events;

use craft\events\CancelableEvent;
use verbb\giftvoucher\elements\Code;
use yii\base\Event;

class MatchCodeEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /** @var Code */
    public $code;
    public $codeKey;
    public $error = '';
}

<?php
namespace verbb\giftvoucher\events;

use yii\base\Event;

class BulkGenerateCodesEvent extends Event
{
    // Properties
    // =========================================================================

    public array $codes;

}

<?php
namespace verbb\giftvoucher\events;

use yii\base\Event;

class PdfRenderOptionsEvent extends Event
{
    public $options;
}

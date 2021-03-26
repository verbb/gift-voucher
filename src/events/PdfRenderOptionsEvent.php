<?php
namespace verbb\giftvoucher\events;

use Dompdf\Options;

use yii\base\Event;

class PdfRenderOptionsEvent extends Event
{
    public $options;
}

<?php
namespace verbb\giftvoucher\events;

use yii\base\Event;

class PdfEvent extends Event
{
    public $order;
    public $option;
    public $template;
    public $variables;
    public $pdf;
}

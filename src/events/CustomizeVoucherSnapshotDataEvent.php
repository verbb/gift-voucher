<?php
namespace verbb\giftvoucher\events;

use verbb\giftvoucher\elements\Voucher;

use yii\base\Event;

class CustomizeVoucherSnapshotDataEvent extends Event
{
    // Properties
    // =========================================================================

    public Voucher $voucher;
    public array $fieldData = [];
}

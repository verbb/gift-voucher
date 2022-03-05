<?php
namespace verbb\giftvoucher\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\records\Element;

use craft\commerce\records\Order;
use craft\commerce\records\LineItem;

class CodeRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_codes}}';
    }

    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getLineItem(): ActiveQuery
    {
        return $this->hasOne(LineItem::class, ['id' => 'lineItemId']);
    }

    public function getVoucher(): ActiveQuery
    {
        return $this->hasOne(VoucherRecord::class, ['id' => 'voucherId']);
    }

    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }
}

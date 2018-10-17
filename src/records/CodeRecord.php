<?php
namespace verbb\giftvoucher\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use craft\commerce\records\Order;
use craft\commerce\records\LineItem;

use yii\db\ActiveQueryInterface;

class CodeRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_codes}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getLineItem(): ActiveQueryInterface
    {
        return $this->hasOne(LineItem::class, ['id' => 'lineItemId']);
    }

    public function getVoucher(): ActiveQueryInterface
    {
        return $this->hasOne(VoucherRecord::class, ['id' => 'voucherId']);
    }

    public function getOrder(): ActiveQueryInterface
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }
}

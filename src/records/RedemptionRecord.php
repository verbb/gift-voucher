<?php
namespace verbb\giftvoucher\records;

use verbb\giftvoucher\records\CodeRecord;

use craft\db\ActiveRecord;

use craft\commerce\records\Order;

use yii\db\ActiveQueryInterface;

class RedemptionRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_redemptions}}';
    }

    public function getCode(): ActiveQueryInterface
    {
        return $this->hasOne(CodeRecord::class, ['id' => 'codeId']);
    }

    public function getOrder(): ActiveQueryInterface
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }
}

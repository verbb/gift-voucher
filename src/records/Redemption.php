<?php
namespace verbb\giftvoucher\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;

use craft\commerce\records\Order;

class Redemption extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_redemptions}}';
    }

    public function getCode(): ActiveQuery
    {
        return $this->hasOne(Code::class, ['id' => 'codeId']);
    }

    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }
}

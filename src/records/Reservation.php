<?php
namespace verbb\giftvoucher\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

class Reservation extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_reservations}}';
    }

    public function getCode(): ActiveQuery
    {
        return $this->hasOne(Code::class, ['id' => 'codeId']);
    }

    public function getOrder(): ?Order
    {
        if (!$this->orderId) {
            return null;
        }

        return Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
    }
}

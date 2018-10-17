<?php
namespace verbb\giftvoucher\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use craft\commerce\records\TaxCategory;
use craft\commerce\records\ShippingCategory;

use yii\db\ActiveQueryInterface;

class VoucherRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_vouchers}}';
    }

    public function getType(): ActiveQueryInterface
    {
        return $this->hasOne(VoucherTypeRecord::class, ['id' => 'typeId']);
    }

    public function getTaxCategory(): ActiveQueryInterface
    {
        return $this->hasOne(TaxCategory::class, ['id' => 'taxCategoryId']);
    }

    public function getShippingCategory(): ActiveQueryInterface
    {
        return $this->hasOne(ShippingCategory::class, ['id' => 'shippingCategoryId']);
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}

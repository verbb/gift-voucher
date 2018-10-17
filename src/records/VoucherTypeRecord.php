<?php
namespace verbb\giftvoucher\records;

use craft\db\ActiveRecord;
use craft\records\FieldLayout;

use yii\db\ActiveQueryInterface;

class VoucherTypeRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_vouchertypes}}';
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}

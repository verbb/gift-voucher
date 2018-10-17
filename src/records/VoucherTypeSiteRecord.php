<?php
namespace verbb\giftvoucher\records;

use craft\db\ActiveRecord;
use craft\records\Site;

use yii\db\ActiveQueryInterface;

class VoucherTypeSiteRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_vouchertypes_sites}}';
    }

    public function getVoucherType(): ActiveQueryInterface
    {
        return $this->hasOne(VoucherTypeRecord::class, ['id', 'voucherTypeId']);
    }

    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id', 'siteId']);
    }
}

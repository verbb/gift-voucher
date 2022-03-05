<?php
namespace verbb\giftvoucher\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\records\Site;

class VoucherTypeSiteRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%giftvoucher_vouchertypes_sites}}';
    }

    public function getVoucherType(): ActiveQuery
    {
        return $this->hasOne(VoucherTypeRecord::class, ['id', 'voucherTypeId']);
    }

    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id', 'siteId']);
    }
}

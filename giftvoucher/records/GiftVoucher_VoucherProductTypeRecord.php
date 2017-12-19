<?php

namespace Craft;

/**
 * GiftVoucher_VoucherProductTypeRecord
 *
 * This record is for later features. Can be ignored for now.
 */
class GiftVoucher_VoucherProductTypeRecord extends BaseRecord
{
    /**
     * @return string
     */
    public function getTableName()
    {
        return 'giftvoucher_voucher_producttypes';
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['voucherId', 'productTypeId'], 'unique' => true],
        ];
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return [
            'voucher'    => [
                static::BELONGS_TO,
                'GiftVoucher_VoucherRecord',
                'onDelete' => self::CASCADE,
                'onUpdate' => self::CASCADE,
                'required' => true
            ],
            'productType' => [
                static::BELONGS_TO,
                'Commerce_ProductTypeRecord',
                'onDelete' => self::CASCADE,
                'onUpdate' => self::CASCADE,
                'required' => true
            ],
        ];
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'voucherId'    => [AttributeType::Number, 'required' => true],
            'productTypeId' => [AttributeType::Number, 'required' => true],
        ];
    }

}
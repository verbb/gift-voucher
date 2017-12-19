<?php

namespace Craft;

/**
 * GiftVoucher_VoucherProductRecord
 *
 * This record is for later features. Can be ignored for now.
 */
class GiftVoucher_VoucherProductRecord extends BaseRecord
{
    /**
     * @return string
     */
    public function getTableName()
    {
        return 'giftvoucher_voucher_products';
    }

    /**
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['voucherId', 'productId'], 'unique' => true],
        ];
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return [
            'voucher' => [
                static::BELONGS_TO,
                'GiftVoucher_VoucherRecord',
                'onDelete' => self::CASCADE,
                'onUpdate' => self::CASCADE,
                'required' => true
            ],
            'product' => [
                static::BELONGS_TO,
                'Commerce_ProductRecord',
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
            'voucherId' => [AttributeType::Number, 'required' => true],
            'productId' => [AttributeType::Number, 'required' => true],
        ];
    }

}
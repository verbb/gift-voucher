<?php

namespace Craft;

/**
 * @property int      id
 * @property int      voucherId
 * @property int      orderId
 * @property int      lineItemId
 * @property string   codeKey
 * @property float    originAmount
 * @property float    currentAmount
 * @property DateTime expiryDate
 * @property bool     manually
 */
class GiftVoucher_CodeRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc BaseRecord::getTableName()
     *
     * @return string
     */
    public function getTableName()
    {
        return 'giftvoucher_codes';
    }

    /**
     * @inheritdoc BaseRecord::defineIndexes()
     *
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['codeKey'], 'unique' => true],
        ];
    }

    /**
     * @inheritdoc BaseRecord::defineRelations()
     *
     * @return array
     */
    public function defineRelations()
    {
        return [
            'voucher'  => [
                static::BELONGS_TO,
                'GiftVoucher_VoucherRecord',
                'required' => false,
                'onDelete' => static::SET_NULL,
            ],
            'order'    => [
                static::BELONGS_TO,
                'Commerce_OrderRecord',
                'required' => false,
                'onDelete' => static::SET_NULL,
            ],
            'lineItem' => [
                static::BELONGS_TO,
                'Commerce_LineItemRecord',
                'required' => false,
                'onDelete' => static::SET_NULL,
            ],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc BaseRecord::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'codeKey'       => [AttributeType::String, 'required' => true],
            'originAmount'  => [AttributeType::Number, 'decimals' => 2, 'required' => true],
            'currentAmount' => [AttributeType::Number, 'decimals' => 2, 'required' => true],
            'expiryDate'    => [AttributeType::DateTime, 'required' => false],
            'manually'      => [AttributeType::Bool, 'default' => false],
        ];
    }
}

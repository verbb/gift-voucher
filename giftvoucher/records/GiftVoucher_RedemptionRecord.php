<?php

namespace Craft;

/**
 * @property int id
 * @property int codeId
 * @property int orderId
 * @property int amount
 */
class GiftVoucher_RedemptionRecord extends BaseRecord
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
        return 'giftvoucher_redemptions';
    }

    /**
     * @inheritdoc BaseRecord::defineRelations()
     *
     * @return array
     */
    public function defineRelations()
    {
        return [
            'code'  => [
                static::BELONGS_TO,
                'GiftVoucher_CodeRecord',
                'required' => false,
                'onDelete' => static::SET_NULL,
            ],
            'order' => [
                static::BELONGS_TO,
                'Commerce_OrderRecord',
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
            'amount' => [AttributeType::Number, 'decimals' => 2, 'required' => true],
        ];
    }
}

<?php

namespace Craft;

/**
 * @property int    id
 * @property int    typeId
 * @property int    taxCategoryId
 * @property int    shippingCategoryId
 * @property string sku
 * @property float  price
 * @property bool   customAmount
 * @property int    expiry
 */
class GiftVoucher_VoucherRecord extends BaseRecord
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
        return 'giftvoucher_vouchers';
    }

    /**
     * @inheritdoc BaseRecord::defineIndexes()
     *
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['sku'], 'unique' => true],
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
            'element'          => [
                static::BELONGS_TO,
                'ElementRecord',
                'id',
                'required' => true,
                'onDelete' => static::CASCADE,
            ],
            'type'             => [
                static::BELONGS_TO,
                'GiftVoucher_VoucherTypeRecord',
                'onDelete' => static::CASCADE,
            ],
            'taxCategory'      => [
                static::BELONGS_TO,
                'Commerce_TaxCategoryRecord',
                'required' => true,
            ],
            'shippingCategory' => [
                static::BELONGS_TO,
                'Commerce_ShippingCategoryRecord',
                'required' => true,
            ],
//            'products' => [
//                static::MANY_MANY,
//                'Commerce_ProductRecord',
//                'giftvoucher_voucher_products(voucherId, productId)'
//            ],
//            'productTypes' => [
//                static::MANY_MANY,
//                'Commerce_ProductTypeRecord',
//                'giftvoucher_voucher_producttypes(voucherId, productTypeId)'
//            ],
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
            'sku'        => [AttributeType::String, 'required' => true],
            'price'      => [AttributeType::Number, 'decimals' => 2, 'required' => true],
            'customAmount' => [AttributeType::Bool, 'default' => false],

//            'description' => AttributeType::Mixed,
            'expiry'     => AttributeType::Number,
//            'purchaseTotal' => [
//                AttributeType::Number,
//                'required' => true,
//                'default' => 0
//            ],
//            'purchaseQty' => [
//                AttributeType::Number,
//                'required' => true,
//                'default' => 0
//            ],
//            'maxPurchaseQty' => [
//                AttributeType::Number,
//                'required' => true,
//                'default' => 0
//            ],
//            'excludeOnSale' => [
//                AttributeType::Bool,
//                'required' => true,
//                'default' => 0
//            ],
//            'freeShipping' => [
//                AttributeType::Bool,
//                'required' => true,
//                'default' => 0
//            ],
//            'allProducts' => [
//                AttributeType::Bool,
//                'required' => true,
//                'default' => 0
//            ],
//            'allProductTypes' => [
//                AttributeType::Bool,
//                'required' => true,
//                'default' => 0
//            ],
        ];
    }
}

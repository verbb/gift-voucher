<?php

namespace Craft;

use Commerce\Base\Purchasable as BasePurchasable;
use Commerce\Traits\Commerce_ModelRelationsTrait;

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
class GiftVoucher_VoucherModel extends BasePurchasable
{
    use Commerce_ModelRelationsTrait;

    /**
     * @var string
     */
    protected $elementType = 'GiftVoucher_Voucher';

    /**
     * @var GiftVoucher_VoucherTypeModel
     */
    private $_voucherType;


    // Public Methods
    // =========================================================================

    /**
     * @return null|string
     */
    public function __toString()
    {
        return (string)Craft::t($this->title);
    }

    /**
     * @inheritdoc BaseElementModel::isEditable()
     *
     * @return bool
     */
    public function isEditable()
    {
        return GiftVoucherHelper::getLicenseService()->isLicensed();
    }

    /**
     * @return bool
     */
    public function isLocalized()
    {
        return true;
    }

    /**
     * @inheritdoc BaseElementModel::getCpEditUrl()
     *
     * @return string
     */
    public function getCpEditUrl()
    {
        $voucherType = $this->getVoucherType();

        if ($voucherType) {
            $url = UrlHelper::getCpUrl('giftvoucher/vouchers/' . $voucherType->handle . '/' . $this->id);

            if (craft()->isLocalized() && $this->locale != craft()->language) {
                $url .= '/' . $this->locale;
            }

            return $url;
        }

        return null;
    }

    /**
     * @inheritdoc BaseElementModel::getFieldLayout()
     *
     * @return FieldLayoutModel|null
     */
    public function getFieldLayout()
    {
        $voucherType = $this->getVoucherType();

        if ($voucherType) {
            return $voucherType->asa('voucherFieldLayout')->getFieldLayout();
        }

        return null;
    }

    /**
     * @inheritdoc BaseElementModel::getUrlFormat()
     *
     * Returns the URL format used to generate this element's URL.
     *
     * @return string
     */
    public function getUrlFormat()
    {
        $voucherType = $this->getVoucherType();

        if ($voucherType && $voucherType->hasUrls) {
            $voucherTypeLocales = $voucherType->getLocales();

            if (isset($voucherTypeLocales[$this->locale])) {
                return $voucherTypeLocales[$this->locale]->urlFormat;
            }
        }

        return '';
    }

    /**
     * Returns the voucher's voucher type model.
     *
     * @return GiftVoucher_VoucherTypeModel|null
     */
    public function getVoucherType()
    {
        if ($this->_voucherType) {
            return $this->_voucherType;
        }

        return $this->_voucherType = GiftVoucherHelper::getVoucherTypesService()->getVoucherTypeById($this->typeId);
    }

    /**
     * Returns the voucher's voucher type model. Alias of ::getVoucherType()
     *
     * @return GiftVoucher_VoucherTypeModel
     */
    public function getType()
    {
        return $this->getVoucherType();
    }

    /**
     * Returns the voucher condition product types
     *
     * @return array
     */
    public function getProductTypeIds()
    {
        return array_map(function($type) {
            return $type->id;
        }, $this->productTypes);
    }

    /**
     * Return the voucher condition products
     *
     * @return array
     */
    public function getProductIds()
    {
        return array_map(function($product) {
            return $product->id;
        }, $this->products);
    }

    /**
     * Get the codes for a given line item
     *
     * @param Commerce_LineItemModel $lineItem
     *
     * @return GiftVoucher_CodeModel[]
     * @throws Exception
     */
    public function getCodesForLineItem(Commerce_LineItemModel $lineItem)
    {
        return GiftVoucherHelper::getCodesService()->getCodesForLineItem($lineItem);
    }

    /**
     * Return Voucher as the product because the purchasable item is a voucher code, treated as a variant of the Voucher
     *
     * @return GiftVoucher_VoucherModel
     */
    public function getProduct()
    {
        return $this;
    }


    // Implement Purchasable
    // =========================================================================
    /**
     * @inheritdoc Purchasable::getPurchasableId()
     *
     * @return int
     */
    public function getPurchasableId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc Purchasable::getSnapshot()
     *
     * @return array
     */
    public function getSnapshot()
    {
        return $this->getAttributes();
    }

    /**
     * @inheritdoc Purchasable::getPrice()
     *
     * @return float decimal(14,4)
     */
    public function getPrice()
    {
        return $this->getAttribute('price');
    }

    /**
     * @inheritdoc Purchasable::getSku()
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getAttribute('sku');
    }

    /**
     * @inheritdoc Purchasable::getDescription()
     *
     * @return string
     */
    public function getDescription()
    {
//        return $this->getAttribute('description');
        return $this->getTitle();
    }

    /**
     * @inheritdoc Purchasable::getTaxCategoryId()
     *
     * @return int
     */
    public function getTaxCategoryId()
    {
        return $this->getAttribute('taxCategoryId');
    }

    /**
     * @inheritdoc Purchasable::getShippingCategoryId()
     *
     * @return int
     */
    public function getShippingCategoryId()
    {
        return $this->getAttribute('shippingCategoryId');
    }

    /**
     * @inheritdoc Purchasable::validateLineItem()
     *
     * @param \Craft\Commerce_LineItemModel $lineItem
     *
     * @return mixed
     */
    public function validateLineItem(Commerce_LineItemModel $lineItem)
    {
        return null;
    }

    /**
     * @inheritdoc Purchasable::hasFreeShipping()
     *
     * @return bool
     */
    public function hasFreeShipping()
    {
        return true;
    }

    /**
     * @inheritdoc Purchasable::getIsPromotable()
     *
     * @return bool
     */
    public function getIsPromotable()
    {
        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc BaseElementModel::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'typeId'             => AttributeType::Number,
            'sku'                => [AttributeType::String, 'required' => true],
            'taxCategoryId'      => AttributeType::Number,
            'shippingCategoryId' => AttributeType::Number,
            'price'              => [AttributeType::Number, 'decimals' => 2, 'required' => true],
            'customAmount'         => [AttributeType::Bool, 'default' => false],

//            'description' => AttributeType::Mixed,
            'expiry'             => AttributeType::Number,
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
        ]);
    }
}

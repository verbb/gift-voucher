<?php

namespace Craft;

/**
 * @property int      id
 * @property int      voucherId
 * @property int      orderId
 * @property int      lineItemId
 * @property string   codeKey
 * @property float    originalAmount
 * @property float    currentAmount
 * @property DateTime expiryDate
 * @property bool     manually
 */
class GiftVoucher_CodeModel extends BaseModel
{

    /**
     * @var GiftVoucher_VoucherModel
     */
    private $_voucher;

    /**
     * @var Commerce_OrderModel
     */
    private $_order;

    /**
     * @var Commerce_LineItemModel
     */
    private $_lineItem;


    // Public Methods
    // =========================================================================

    /**
     * @return null|string
     */
    public function __toString()
    {
        return (string)$this->codeKey;
    }

    /**
     * Return the voucher tied to the code.
     *
     * @return GiftVoucher_VoucherModel
     */
    public function getVoucher()
    {
        if ($this->_voucher) {
            return $this->_voucher;
        }

        return $this->_voucher = GiftVoucherHelper::getVouchersService()->getVoucherById($this->voucherId);
    }

    /**
     * Return the order tied to the code.
     *
     * @return bool|Commerce_OrderModel
     */
    public function getOrder()
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->orderId) {
            return $this->_order = craft()->commerce_orders->getOrderById($this->orderId);
        }

        return false;
    }

    /**
     * Return the line item tied to the code.
     *
     * @return bool|Commerce_LineItemModel
     */
    public function getLineItem()
    {
        if ($this->_lineItem) {
            return $this->_lineItem;
        }

        if ($this->lineItemId) {
            return $this->_lineItem = craft()->commerce_lineItems->getLineItemById($this->lineItemId);
        }

        return false;
    }

    /**
     * Return the voucher type for the voucher tied to the code.
     *
     * @return GiftVoucher_VoucherTypeModel|null
     */
    public function getVoucherType()
    {
        $voucher = $this->getVoucher();

        if ($voucher) {
            return $voucher->getVoucherType();
        }

        return null;
    }

    /**
     * @inheritdoc BaseElementModel::getCpEditUrl()
     *
     * @return string
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('giftvoucher/codes/' . $this->id);
    }

    /**
     * Get the link for editing the order that purchased this license.
     *
     * @return string
     */
    public function getOrderEditUrl()
    {
        if ($this->orderId) {
            return UrlHelper::getCpUrl('commerce/orders/' . $this->orderId);
        }

        return '';
    }

    /**
     * Return all redemptions
     *
     * @return GiftVoucher_RedemptionModel[]
     */
    public function getRedemptions()
    {
        return GiftVoucherHelper::getRedemptionService()->getRedemptionsForCode($this->id);
    }

    /**
     * Model validation rules
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = ['voucherId', 'validateVoucherId'];

        return $rules;
    }

    public function validateVoucherId($attribute)
    {
        $value = $this->$attribute;

        if ($this->manually && !$value) {
            $this->addError($attribute, Craft::t('Please select a Voucher.'));
        }
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc BaseElementModel::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'id'             => AttributeType::Number,
            'voucherId'      => AttributeType::Number,
            'orderId'        => AttributeType::Number,
            'lineItemId'     => AttributeType::Number,
            'codeKey'        => AttributeType::String,
            'originalAmount' => [AttributeType::Number, 'decimals' => 2, 'required' => true],
            'currentAmount'  => [AttributeType::Number, 'decimals' => 2, 'required' => true],
            'expiryDate'     => AttributeType::DateTime,
            'manually'       => AttributeType::Bool,
        ]);
    }
}

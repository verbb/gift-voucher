<?php

namespace Craft;

/**
 * @property int   id
 * @property int   codeId
 * @property int   orderId
 * @property float amount
 */
class GiftVoucher_RedemptionModel extends BaseModel
{
    /**
     * @var GiftVoucher_CodeModel
     */
    private $_code;

    /**
     * @var Commerce_OrderModel
     */
    private $_order;


    // Public Methods
    // =========================================================================

    /**
     * Return the code tied to the redemption.
     *
     * @return GiftVoucher_CodeModel
     * @throws Exception
     */
    public function getCode()
    {
        if ($this->_code) {
            return $this->_code;
        }

        return $this->_code = GiftVoucherHelper::getCodesService()->getCodeById($this->codeId);
    }

    /**
     * Return the voucher tied to the code of the redemption.
     *
     * @return GiftVoucher_VoucherModel
     * @throws Exception
     */
    public function getVoucher()
    {
        $code = $this->getCode();

        if ($code) {
            return $code->getVoucher();
        }

        return null;
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
     * Return the voucher type for the voucher tied to the code.
     *
     * @return GiftVoucher_VoucherTypeModel|null
     * @throws Exception
     */
    public function getVoucherType()
    {
        $code = $this->getCode();

        if ($code) {
            return $code->getVoucherType();
        }

        return null;
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
            'id'      => AttributeType::Number,
            'codeId'  => AttributeType::Number,
            'orderId' => AttributeType::Number,
            'amount'  => [AttributeType::Number, 'decimals' => 2],
        ]);
    }
}

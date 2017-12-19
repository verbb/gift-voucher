<?php

namespace Craft;

/**
 * @property int    id
 * @property int    fieldLayoutId
 * @property string name
 * @property string handle
 * @property bool   hasUrls
 * @property string urlFormat
 * @property string skuFormat
 * @property string template
 */
class GiftVoucher_VoucherTypeModel extends BaseModel
{

    /**
     * @var LocaleModel[]
     */
    private $_locales;

    // Public Methods
    // =========================================================================

    /**
     * @return null|string
     */
    public function __toString()
    {
        return (string)Craft::t($this->handle);
    }

    /**
     * @inheritDoc BaseElementModel::getCpEditUrl()
     *
     * @return string|false
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('giftvoucher/vouchertypes/' . $this->id);
    }

    /**
     * Return locales defined for this Voucher by it's Voucher Type.
     *
     * @return array
     */
    public function getLocales()
    {
        if (!isset($this->_locales)) {
            if ($this->id) {
                $this->_locales = craft()->giftVoucher_voucherTypes->getVoucherTypeLocales($this->id, 'locale');
            } else {
                $this->_locales = [];
            }
        }

        return $this->_locales;
    }

    /**
     * Sets the locales on the voucher type
     *
     * @param $locales
     */
    public function setLocales($locales)
    {
        $this->_locales = $locales;
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            'voucherFieldLayout' => new FieldLayoutBehavior('GiftVoucher_Voucher',
                'fieldLayoutId'),
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc BaseModel::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'id'            => AttributeType::Number,
            'name'          => AttributeType::Name,
            'handle'        => AttributeType::Handle,
            'hasUrls'       => AttributeType::Bool,
            'urlFormat'     => AttributeType::String,
            'skuFormat'     => AttributeType::String,
            'template'      => AttributeType::Template,
            'fieldLayoutId' => AttributeType::Number,
        ];
    }
}

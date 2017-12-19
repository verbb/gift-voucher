<?php

namespace Craft;

/**
 * @property int    id
 * @property int    voucherTypeId
 * @property string locale
 * @property string urlFormat
 */
class GiftVoucher_VoucherTypeLocaleModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var bool
     */
    public $urlFormatIsRequired = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc BaseModel::rules()
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();

        if ($this->urlFormatIsRequired) {
            $rules[] = ['urlFormat', 'required'];
        }

        return $rules;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc BaseModel::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'id'            => AttributeType::Number,
            'voucherTypeId' => AttributeType::Number,
            'locale'        => AttributeType::Locale,
            'urlFormat'     => [AttributeType::UrlFormat, 'label' => 'URL Format'],
        ];
    }
}

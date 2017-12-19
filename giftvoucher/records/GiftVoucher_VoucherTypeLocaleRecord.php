<?php

namespace Craft;

/**
 * @property int    id
 * @property int    voucherTypeId
 * @property string locale
 * @property string urlFormat
 */
class GiftVoucher_VoucherTypeLocaleRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc BaseRecord::getTableName()
     *
     * @return string
     */
    public function getTableName()
    {
        return 'giftvoucher_vouchertypes_i18n';
    }

    /**
     * @inheritDoc BaseRecord::defineRelations()
     *
     * @return array
     */
    public function defineRelations()
    {
        return [
            'voucherType' => [static::BELONGS_TO, 'GiftVoucher_VoucherTypeRecord', 'required' => true, 'onDelete' => static::CASCADE],
            'locale'      => [static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE],
        ];
    }

    /**
     * @inheritDoc BaseRecord::defineIndexes()
     *
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['voucherTypeId', 'locale'], 'unique' => true],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc BaseRecord::defineAttributes()
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'locale'    => [AttributeType::Locale, 'required' => true],
            'urlFormat' => AttributeType::UrlFormat,
        ];
    }
}

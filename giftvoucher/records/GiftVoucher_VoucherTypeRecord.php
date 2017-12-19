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
class GiftVoucher_VoucherTypeRecord extends BaseRecord
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
        return 'giftvoucher_vouchertypes';
    }

    /**
     * @inheritDoc BaseRecord::defineIndexes()
     *
     * @return array
     */
    public function defineIndexes()
    {
        return [
            ['columns' => ['handle'], 'unique' => true],
        ];
    }

    /**
     * @inheritDoc BaseRecord::defineRelations()
     *
     * @return array
     */
    public function defineRelations()
    {
        return [
            'fieldLayout' => [
                static::BELONGS_TO,
                'FieldLayoutRecord',
                'onDelete' => static::SET_NULL,
            ],
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
            'name'      => [AttributeType::Name, 'required' => true],
            'handle'    => [AttributeType::Handle, 'required' => true],
            'hasUrls'   => AttributeType::Bool,
            'urlFormat' => AttributeType::String,
            'skuFormat' => AttributeType::String,
            'template'  => AttributeType::Template,
        ];
    }
}

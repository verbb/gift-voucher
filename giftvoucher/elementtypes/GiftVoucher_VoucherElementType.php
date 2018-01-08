<?php

namespace Craft;

class GiftVoucher_VoucherElementType extends BaseElementType
{

    // Public Methods
    // =========================================================================

    /**
     * @return null|string
     */
    public function getName()
    {
        return Craft::t('Gift Voucher');
    }

    /**
     * @inheritDoc BaseElementType::hasContent()
     *
     * @return bool
     */
    public function hasContent()
    {
        return true;
    }

    /**
     * @inheritDoc BaseElementType::hasTitles()
     *
     * @return bool
     */
    public function hasTitles()
    {
        return true;
    }

    /**
     * @inheritDoc BaseElementType::hasStatuses()
     *
     * @return bool
     */
    public function hasStatuses()
    {
        return true;
    }

    /**
     * @inheritDoc BaseElementType::isLocalized()
     *
     * @return bool
     */
    public function isLocalized()
    {
        return true;
    }

    /**
     * @inheritDoc BaseElementType::getSources()
     *
     * @param null $context
     *
     * @return array|bool|false
     */
    public function getSources($context = null)
    {
        if ($context == 'index') {
            $voucherTypes = GiftVoucherHelper::getVoucherTypesService()->getEditableVoucherTypes();
            $editable = true;
        } else {
            $voucherTypes = GiftVoucherHelper::getVoucherTypesService()->getAllVoucherTypes();
            $editable = false;
        }

        $voucherTypeIds = [];

        foreach ($voucherTypes as $voucherType) {
            $voucherTypeIds[] = $voucherType->id;
        }

        $sources = [
            '*' => [
                'label'    => Craft::t('All vouchers'),
                'criteria' => [
                    'typeId'   => $voucherTypeIds,
                    'editable' => $editable,
                ],
            ],
        ];

        $sources[] = ['heading' => Craft::t('Voucher Types')];

        foreach ($voucherTypes as $voucherType) {
            $key = 'voucherType:' . $voucherType->id;
            $canEditVouchers = true;

            $sources[$key] = [
                'label'    => $voucherType->name,
                'data'     => [
                    'handle'   => $voucherType->handle,
                    'editable' => $canEditVouchers,
                ],
                'criteria' => [
                    'typeId'   => $voucherType->id,
                    'editable' => $editable,
                ],
            ];
        }

        // Allow plugins to modify the sources
        craft()->plugins->call('giftVoucher_modifyVoucherSources', [
            &$sources,
            $context,
        ]);

        return $sources;
    }

    /**
     * @inheritDoc BaseElementType::defineAvailableTableAttributes()
     *
     * @return array
     */
    public function defineAvailableTableAttributes()
    {
        $attributes = [
            'title'           => ['label' => Craft::t('Title')],
            'type'            => ['label' => Craft::t('Type')],
            'slug'            => ['label' => Craft::t('Slug')],
            'sku'             => ['label' => Craft::t('SKU')],
            'price'           => ['label' => Craft::t('Price')],
            'expiry'          => ['label' => Craft::t('Expiry')],
            'purchaseTotal'   => ['label' => Craft::t('Purchase Total')],
            'purchaseQty'     => ['label' => Craft::t('Minimum Purchase Quantity')],
            'maxPurchaseQty'  => ['label' => Craft::t('Maximum Purchase Quantity')],
            'excludeOnSale'   => ['label' => Craft::t('Exclude On Sale')],
            'freeShipping'    => ['label' => Craft::t('Free Shipping')],
            'allProducts'     => ['label' => Craft::t('All Products')],
            'allProductTypes' => ['label' => Craft::t('All Product Types')],
        ];

        // Allow plugins to modify the attributes
        $pluginAttributes = craft()->plugins->call('giftVoucher_defineAdditionalVoucherTableAttributes', [], true);

        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        return $attributes;
    }

    /**
     * @inheritDoc BaseElementType::getDefaultTableAttributes()
     *
     * @param string|null $source
     *
     * @return array
     */
    public function getDefaultTableAttributes($source = null)
    {
        $attributes = [];

        if ($source == '*') {
            $attributes[] = 'type';
        }

        return $attributes;
    }

    /**
     * @inheritDoc BaseElementType::defineSearchableAttributes()
     *
     * @return array
     */
    public function defineSearchableAttributes()
    {
        return ['title'];
    }


    /**
     * @inheritDoc BaseElementType::getTableAttributeHtml()
     *
     * @param BaseElementModel $element
     * @param string           $attribute
     *
     * @return mixed|string
     */
    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        // First give plugins a chance to set this
        $pluginAttributeHtml = craft()->plugins->callFirst('giftVoucher_getVoucherTableAttributeHtml', [
            $element,
            $attribute,
        ], true);

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        /* @var $voucherType GiftVoucher_VoucherTypeModel */
        $voucherType = $element->getVoucherType();

        switch ($attribute) {
            case 'type':
                {
                    return ($voucherType ? Craft::t($voucherType->name) : '');
                }

            case 'taxCategory':
                {
                    $taxCategory = $element->getTaxCategory();

                    return ($taxCategory ? Craft::t($taxCategory->name) : '');
                }
            case 'shippingCategory':
                {
                    $shippingCategory = $element->getShippingCategory();

                    return ($shippingCategory ? Craft::t($shippingCategory->name) : '');
                }
            case 'defaultPrice':
                {
                    $code = craft()->commerce_paymentCurrencies->getPrimaryPaymentCurrencyIso();

                    return craft()->numberFormatter->formatCurrency($element->$attribute, strtoupper($code));
                }

            case 'promotable':
                {
                    return ($element->$attribute ? '<span data-icon="check" title="' . Craft::t('Yes') . '"></span>' : '');
                }
            default:
                {
                    return parent::getTableAttributeHtml($element, $attribute);
                }
        }
    }

    /**
     * @inheritDoc BaseElementType::defineSortableAttributes()
     *
     * @return array
     */
    public function defineSortableAttributes()
    {
        $attributes = [
            'title' => Craft::t('Title'),
            'price' => Craft::t('Price'),
        ];

        // Allow plugins to modify the attributes
//        craft()->plugins->call('giftVoucher_modifyVoucherSortableAttributes', [&$attributes]);

        return $attributes;
    }


    /**
     * @inheritDoc BaseElement::defineCriteriaAttributes()
     *
     * @return array
     */
    public function defineCriteriaAttributes()
    {
        return [
            'typeId'   => AttributeType::Mixed,
            'type'     => AttributeType::Mixed,
            'editable' => AttributeType::Bool,

            'description'     => AttributeType::Mixed,
            'expiry'          => AttributeType::Number,
            'purchaseTotal'   => [
                AttributeType::Number,
                'required' => true,
                'default'  => 0,
            ],
            'purchaseQty'     => [
                AttributeType::Number,
                'required' => true,
                'default'  => 0,
            ],
            'maxPurchaseQty'  => [
                AttributeType::Number,
                'required' => true,
                'default'  => 0,
            ],
            'excludeOnSale'   => [
                AttributeType::Bool,
                'required' => true,
                'default'  => 0,
            ],
            'freeShipping'    => [
                AttributeType::Bool,
                'required' => true,
                'default'  => 0,
            ],
            'allProducts'     => [
                AttributeType::Bool,
                'required' => true,
                'default'  => 0,
            ],
            'allProductTypes' => [
                AttributeType::Bool,
                'required' => true,
                'default'  => 0,
            ],
        ];
    }

    /**
     * @inheritDoc BaseElementType::modifyElementsQuery()
     *
     * @param DbCommand            $query
     * @param ElementCriteriaModel $criteria
     *
     * @return void
     */
    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query
            ->addSelect("vouchers.*")
            ->join('giftvoucher_vouchers vouchers', 'vouchers.id = elements.id')
            ->join('giftvoucher_vouchertypes vouchertypes', 'vouchertypes.id = vouchers.typeId');

        if ($criteria->expiry) {
            $query->andWhere(DbHelper::parseDateParam('vouchers.expiry', $criteria->expiry, $query->params));
        }

        if ($criteria->type) {
            if ($criteria->type instanceof GiftVoucher_VoucherTypeModel) {
                $criteria->typeId = $criteria->type->id;
                $criteria->type = null;
            } else {
                $query->andWhere(DbHelper::parseParam('vouchertypes.handle', $criteria->type, $query->params));
            }
        }

        if ($criteria->typeId) {
            $query->andWhere(DbHelper::parseParam('vouchers.typeId', $criteria->typeId, $query->params));
        }

        if ($criteria->editable) {
            $user = craft()->userSession->getUser();

            if (!$user) {
                return;
            }

            // Limit the query to only the sections the user has permission to edit
//            $editableVoucherTypeIds = GiftVoucherHelper::getVoucherTypesService()->getEditableVoucherTypeIds();
            $editableVoucherTypeIds = GiftVoucherHelper::getVoucherTypesService()->getAllVoucherTypeIds();

            if (!$editableVoucherTypeIds) {
                return;
            }

            $query->andWhere([
                'in',
                'vouchers.typeId',
                $editableVoucherTypeIds,
            ]);
        }
    }


    /**
     * @inheritDoc BaseElementType::populateElementModel()
     *
     * @param array $row
     *
     * @return BaseElementModel|void
     */
    public function populateElementModel($row)
    {
        return GiftVoucher_VoucherModel::populateModel($row);
    }

    /**
     * @inheritDoc BaseElementType::getEditorHtml()
     *
     * @param BaseElementModel $element
     *
     * @return string
     */
    public function getEditorHtml(BaseElementModel $element)
    {
        /** @ var Commerce_VoucherModel $element */
        $templatesService = craft()->templates;
        $html = $templatesService->renderMacro('giftvoucher/vouchers/_fields', 'titleField', [$element]);
        $html .= parent::getEditorHtml($element);
        $html .= $templatesService->renderMacro('giftvoucher/vouchers/_fields', 'enabledFields', [$element]);
        $html .= $templatesService->renderMacro('giftvoucher/vouchers/_fields', 'generalMetaFields', [$element]);
        $html .= $templatesService->renderMacro('giftvoucher/vouchers/_fields', 'behavioralMetaFields', [$element]);
        $html .= $templatesService->renderMacro('giftvoucher/vouchers/_fields', 'generalFields', [$element]);
        $html .= $templatesService->renderMacro('giftvoucher/vouchers/_fields', 'pricingFields', [$element]);

        return $html;
    }

    /**
     * @inheritdoc BaseElementType::saveElement()
     *
     * @param BaseElementModel|GiftVoucher_VoucherModel $element
     * @param array                                     $params
     *
     * @return bool
     * @throws Exception
     * @throws \Exception
     */
    public function saveElement(BaseElementModel $element, $params)
    {
        /** @var GiftVoucher_VoucherModel $element */
        $element->enabled = $params['enabled'] ? $params['enabled'] : null;

        $element->slug = $params['slug'] ? $params['slug'] : $element->slug;

        if ($params['taxCategoryId']) {
            $element->taxCategoryId = $params['taxCategoryId'];
        } elseif (!$element->taxCategoryId) {
            $element->taxCategoryId = craft()->commerce_taxCategories->getDefaultTaxCategory()->id;
        }

        if ($params['shippingCategoryId']) {
            $element->shippingCategoryId = $params['shippingCategoryId'];
        } elseif (!$element->shippingCategoryId) {
            $element->shippingCategoryId = craft()->commerce_shippingCategories->getDefaultShippingCategory()->id;
        }

        $element->sku = $params['sku'] ? $params['sku'] : $element->sku;

        $element->customAmount = $params['customAmount'] ? $params['customAmount'] : $element->customAmount;

        if ($element->customAmount) {
            $element->price = 0.00;
        } else {
            $element->price = $params['price'] ? (float)$params['price'] : $element->price;
        }

        return GiftVoucherHelper::getVouchersService()->saveVoucher($element);
    }

    /**
     * @inheritDoc BaseElementType::routeRequestForMatchedElement()
     *
     * @param BaseElementModel $element
     *
     * @return bool|mixed
     */
    public function routeRequestForMatchedElement(BaseElementModel $element)
    {
        /** @var Giftvoucher_VoucherModel $element */
        $voucherType = $element->getVoucherType();

        if ($voucherType && $voucherType->hasUrls) {
            return [
                'action' => 'templates/render',
                'params' => [
                    'template'  => $voucherType->template,
                    'variables' => [
                        'voucher' => $element,

                        // Provide the same element as `product` for easy implementation
                        // when using the demo Craft templates - it'll expect a `product` variable 
                        'product' => $element,
                    ],
                ],
            ];
        }

        return false;
    }
}

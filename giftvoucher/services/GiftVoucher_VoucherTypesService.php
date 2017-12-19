<?php
namespace Craft;

/**
 * Voucher Type.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2015, Pixel & Tonic, Inc.
 */
class GiftVoucher_VoucherTypesService extends BaseApplicationComponent
{
    /**
     * @var bool
     */
    private $_fetchedAllVoucherTypes = false;

    /**
     * @var
     */
    private $_voucherTypesById;

    /**
     * @var
     */
    private $_allVoucherTypeIds;

    /**
     * @var
     */
//    private $_editableVoucherTypeIds;

    // Public Methods
    // =========================================================================

    /**
     * Get Voucher Types.
     *
     * @param array|\CDbCriteria $criteria
     *
     * @return GiftVoucher_VoucherTypeModel[]
     */
    public function getVoucherTypes(array $criteria = [])
    {
        $results = GiftVoucher_VoucherTypeRecord::model()->findAll($criteria);

        return GiftVoucher_VoucherTypeModel::populateModels($results);
    }

    /**
     * Get a Voucher Type's locales by it's id.
     *
     * @param      $voucherTypeId
     * @param null $indexBy
     *
     * @return array
     */
    public function getVoucherTypeLocales($voucherTypeId, $indexBy = null)
    {
        $records = GiftVoucher_VoucherTypeLocaleRecord::model()->findAllByAttributes([
            'voucherTypeId' => $voucherTypeId
        ]);

        return GiftVoucher_VoucherTypeLocaleModel::populateModels($records, $indexBy);
    }

    /**
     * Returns all Voucher Types.
     *
     * @param string|null $indexBy
     *
     * @return GiftVoucher_VoucherTypeModel[]
     */
    public function getAllVoucherTypes($indexBy = null)
    {
        if (!$this->_fetchedAllVoucherTypes) {
            $results = GiftVoucher_VoucherTypeRecord::model()->findAll();

            if (!isset($this->_voucherTypesById)) {
                $this->_voucherTypesById = [];
            }

            foreach ($results as $result) {
                $voucherType = GiftVoucher_VoucherTypeModel::populateModel($result);
                $this->_voucherTypesById[$voucherType->id] = $voucherType;
            }

            $this->_fetchedAllVoucherTypes = true;
        }

        if ($indexBy == 'id') {
            $voucherTypes = $this->_voucherTypesById;
        } else if (!$indexBy) {
            $voucherTypes = array_values($this->_voucherTypesById);
        } else {
            $voucherTypes = [];
            foreach ($this->_voucherTypesById as $voucherType) {
                $voucherTypes[$voucherType->$indexBy] = $voucherType;
            }
        }

        return $voucherTypes;
    }

    /**
     * Returns all of the Voucher Type IDs.
     *
     * @return array
     */
    public function getAllVoucherTypeIds()
    {
        if (!isset($this->_allVoucherTypeIds)) {
            $this->_allVoucherTypeIds = [];

            foreach ($this->getAllVoucherTypes() as $voucherType) {
                $this->_allVoucherTypeIds[] = $voucherType->id;
            }
        }

        return $this->_allVoucherTypeIds;
    }

    /**
     * Returns all of the Voucher Type Ids that are editable by the current user.
     *
     * @return array
     */
//    public function getEditableVoucherTypeIds()
//    {
//        if (!isset($this->_editableVoucherTypeIds)) {
//            $this->_editableVoucherTypeIds = [];
//
//            foreach ($this->getAllVoucherTypeIds() as $voucherTypeId) {
//                if (craft()->userSession->checkPermission('digitalVouchers-manageVoucherType:'.$voucherTypeId)) {
//                    $this->_editableVoucherTypeIds[] = $voucherTypeId;
//                }
//            }
//        }
//
//        return $this->_editableVoucherTypeIds;
//    }

    /**
     * Returns all editable Voucher Types for the current user..
     *
     * @param string|null $indexBy
     *
     * @return Commerce_VoucherTypeModel[]
     */
    public function getEditableVoucherTypes($indexBy = null)
    {
//        $editableVoucherTypeIds = $this->getEditableVoucherTypeIds();
        $editableVoucherTypeIds = $this->getAllVoucherTypeIds();
        $editableVoucherTypes = [];

        foreach ($this->getAllVoucherTypes() as $voucherTypes) {
            if (in_array($voucherTypes->id, $editableVoucherTypeIds)) {
                if ($indexBy) {
                    $editableVoucherTypes[$voucherTypes->$indexBy] = $voucherTypes;
                } else {
                    $editableVoucherTypes[] = $voucherTypes;
                }
            }
        }

        return $editableVoucherTypes;
    }

    /**
     * Save a Voucher Type.
     *
     * @param GiftVoucher_VoucherTypeModel $voucherType
     *
     * @return bool
     * @throws Exception in case of invalid data.
     * @throws \Exception if saving of the Element failed causing a failed transaction
     */
    public function saveVoucherType(GiftVoucher_VoucherTypeModel $voucherType)
    {
        if ($voucherType->id) {
            $voucherTypeRecord = GiftVoucher_VoucherTypeRecord::model()->findById($voucherType->id);
            if (!$voucherTypeRecord) {
                throw new Exception(Craft::t('No voucher type exists with the ID “{id}”',
                    ['id' => $voucherType->id]));
            }

            /** @var GiftVoucher_VoucherTypeModel $oldVoucherType */
            $oldVoucherType = GiftVoucher_VoucherTypeModel::populateModel($voucherTypeRecord);
            $isNewVoucherType = false;
        } else {
            $voucherTypeRecord = new GiftVoucher_VoucherTypeRecord();
            $isNewVoucherType = true;
        }

        $voucherTypeRecord->name = $voucherType->name;
        $voucherTypeRecord->handle = $voucherType->handle;
        $voucherTypeRecord->hasUrls = $voucherType->hasUrls;
        $voucherTypeRecord->skuFormat = $voucherType->skuFormat;
        $voucherTypeRecord->template = $voucherType->template;

        // Make sure that all of the URL formats are set properly
        $voucherTypeLocales = $voucherType->getLocales();

        foreach ($voucherTypeLocales as $localeId => $voucherTypeLocale) {
            if ($voucherType->hasUrls) {
                $urlFormatAttributes = ['urlFormat'];
                $voucherTypeLocale->urlFormatIsRequired = true;

                foreach ($urlFormatAttributes as $attribute) {
                    if (!$voucherTypeLocale->validate([$attribute])) {
                        $voucherType->addError($attribute.'-'.$localeId, $voucherTypeLocale->getError($attribute));
                    }
                }
            } else {
                $voucherTypeLocale->urlFormat = null;
            }
        }

        $voucherTypeRecord->validate();
        $voucherType->addErrors($voucherTypeRecord->getErrors());

        if (!$voucherType->hasErrors()) {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            try {

                // Drop the old field layout
                craft()->fields->deleteLayoutById($voucherType->fieldLayoutId);

                // Save the new one
                $fieldLayout = $voucherType->asa('voucherFieldLayout')->getFieldLayout();
                craft()->fields->saveLayout($fieldLayout);
                $voucherType->fieldLayoutId = $fieldLayout->id;
                $voucherTypeRecord->fieldLayoutId = $fieldLayout->id;

                // Save it!
                $voucherTypeRecord->save(false);

                // Now that we have a voucher type ID, save it on the model
                if (!$voucherType->id) {
                    $voucherType->id = $voucherTypeRecord->id;
                }

                $newLocaleData = [];

                if (!$isNewVoucherType) {
                    // Get the old voucher type locales
                    $oldLocaleRecords = GiftVoucher_VoucherTypeLocaleRecord::model()->findAllByAttributes([
                        'voucherTypeId' => $voucherType->id
                    ]);
                    $oldLocales = GiftVoucher_VoucherTypeLocaleModel::populateModels($oldLocaleRecords, 'locale');

                    $changedLocaleIds = [];
                }


                foreach ($voucherTypeLocales as $localeId => $locale) {
                    // Was this already selected?
                    if (!$isNewVoucherType && isset($oldLocales[$localeId])) {
                        $oldLocale = $oldLocales[$localeId];

                        // Has the URL format changed?
                        if ($locale->urlFormat != $oldLocale->urlFormat) {
                            craft()->db->createCommand()->update('giftvoucher_vouchertypes_i18n', [
                                'urlFormat' => $locale->urlFormat
                            ], [
                                'id' => $oldLocale->id
                            ]);

                            $changedLocaleIds[] = $localeId;
                        }
                    } else {
                        $newLocaleData[] = [
                            $voucherType->id,
                            $localeId,
                            $locale->urlFormat
                        ];
                    }
                }

                // Insert the new locales
                craft()->db->createCommand()->insertAll('giftvoucher_vouchertypes_i18n',
                    ['voucherTypeId', 'locale', 'urlFormat'],
                    $newLocaleData
                );

                if (!$isNewVoucherType) {
                    // Drop any locales that are no longer being used, as well as the associated element
                    // locale rows

                    $droppedLocaleIds = array_diff(array_keys($oldLocales), array_keys($voucherTypeLocales));

                    if ($droppedLocaleIds) {
                        craft()->db->createCommand()->delete('giftvoucher_vouchertypes_i18n', [
                            'in',
                            'locale',
                            $droppedLocaleIds
                        ]);
                    }
                }

                if (!$isNewVoucherType) {
                    // Get all of the voucher IDs in this group
                    $criteria = craft()->elements->getCriteria('GiftVoucher_Voucher');
                    $criteria->typeId = $voucherType->id;
                    $criteria->status = null;
                    $criteria->limit = null;
                    $voucherIds = $criteria->ids();

                    // Should we be deleting
                    if ($voucherIds && $droppedLocaleIds) {
                        craft()->db->createCommand()->delete('elements_i18n', [
                            'and',
                            ['in', 'elementId', $voucherIds],
                            ['in', 'locale', $droppedLocaleIds]
                        ]);
                        craft()->db->createCommand()->delete('content', [
                            'and',
                            ['in', 'elementId', $voucherIds],
                            ['in', 'locale', $droppedLocaleIds]
                        ]);
                    }
                    // Are there any locales left?
                    if ($voucherTypeLocales) {
                        // Drop the old voucherType URIs if the voucher type no longer has URLs
                        if (!$voucherType->hasUrls && $oldVoucherType->hasUrls) {
                            craft()->db->createCommand()->update('elements_i18n',
                                ['uri' => null],
                                ['in', 'elementId', $voucherIds]
                            );
                        } else if ($changedLocaleIds) {
                            foreach ($voucherIds as $voucherId) {
                                craft()->config->maxPowerCaptain();

                                // Loop through each of the changed locales and update all of the vouchers’ slugs and
                                // URIs
                                foreach ($changedLocaleIds as $localeId) {
                                    $criteria = craft()->elements->getCriteria('GiftVoucher_Voucher');
                                    $criteria->id = $voucherId;
                                    $criteria->locale = $localeId;
                                    $criteria->status = null;
                                    $updateVoucher = $criteria->first();

                                    // @todo replace the getContent()->id check with 'strictLocale' param once it's added
                                    if ($updateVoucher && $updateVoucher->getContent()->id) {
                                        craft()->elements->updateElementSlugAndUri($updateVoucher, false, false);
                                    }
                                }
                            }
                        }
                    }
                }

                if ($transaction !== null) {
                    $transaction->commit();
                }
            } catch (\Exception $e) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                throw $e;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a Voucher Type by it's Id.
     *
     * @param int $voucherTypeId
     *
     * @return GiftVoucher_VoucherTypeModel|null
     */
    public function getVoucherTypeById($voucherTypeId)
    {
        if (!$this->_fetchedAllVoucherTypes &&
            (!isset($this->_voucherTypesById) || !array_key_exists($voucherTypeId, $this->_voucherTypesById))
        ) {
            $result = GiftVoucher_VoucherTypeRecord::model()->findById($voucherTypeId);

            if ($result) {
                $voucherType = GiftVoucher_VoucherTypeModel::populateModel($result);
            } else {
                $voucherType = null;
            }

            $this->_voucherTypesById[$voucherTypeId] = $voucherType;
        }

        if (isset($this->_voucherTypesById[$voucherTypeId])) {
            return $this->_voucherTypesById[$voucherTypeId];
        }

        return null;
    }

    /**
     * Get a Voucher Type by it's handle.
     *
     * @param string $handle
     *
     * @return GiftVoucher_VoucherTypeModel|null
     */
    public function getVoucherTypeByHandle($handle)
    {
        $result = GiftVoucher_VoucherTypeRecord::model()->findByAttributes(['handle' => $handle]);

        if ($result) {
            $voucherType = GiftVoucher_VoucherTypeModel::populateModel($result);
            $this->_voucherTypesById[$voucherType->id] = $voucherType;

            return $voucherType;
        }

        return null;
    }

    /**
     * Delete a Voucher Type by it's Id.
     *
     * @param $id
     *
     * @return bool
     * @throws \Exception if failed to delete the Voucher Type.
     */
    public function deleteVoucherTypeById($id)
    {
        try {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            $voucherType = $this->getVoucherTypeById($id);

            $criteria = craft()->elements->getCriteria('GiftVoucher_Voucher');
            $criteria->typeId = $voucherType->id;
            $criteria->status = null;
            $criteria->limit = null;
            $vouchers = $criteria->find();

            foreach ($vouchers as $voucher) {
                craft()->giftVoucher_vouchers->deleteVoucher($voucher);
            }

            $fieldLayoutId = $voucherType->asa('voucherFieldLayout')->getFieldLayout()->id;
            craft()->fields->deleteLayoutById($fieldLayoutId);

            $voucherTypeRecord = GiftVoucher_VoucherTypeRecord::model()->findById($voucherType->id);
            $affectedRows = $voucherTypeRecord->delete();

            if ($transaction !== null) {
                $transaction->commit();
            }

            return (bool)$affectedRows;
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }
            throw $e;
        }
    }

    /**
     * Returns true if Voucher Type has a valid template set.
     *
     * @param GiftVoucher_VoucherTypeModel $voucherType
     *
     * @return bool
     * @throws Exception
     */
    public function isVoucherTypeTemplateValid(GiftVoucher_VoucherTypeModel $voucherType)
    {
        if ($voucherType->hasUrls) {
            // Set Craft to the site template mode
            $templatesService = craft()->templates;
            $oldTemplateMode = $templatesService->getTemplateMode();
            $templatesService->setTemplateMode(TemplateMode::Site);

            // Does the template exist?
            $templateExists = $templatesService->doesTemplateExist($voucherType->template);

            // Restore the original template mode
            $templatesService->setTemplateMode($oldTemplateMode);

            if ($templateExists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a new locale to all Voucher Types if one is being added to Craft.
     * @param Event $event
     *
     * @return bool
     */
    public function addLocaleHandler(Event $event)
    {
        /** @var Commerce_OrderModel $order */
        $localeId = $event->params['localeId'];

        // Add this locale to each of the category groups
        $voucherTypeLocales = craft()->db->createCommand()
            ->select('voucherTypeId, urlFormat')
            ->from('giftvoucher_vouchertypes_i18n')
            ->where('locale = :locale', [':locale' => craft()->i18n->getPrimarySiteLocaleId()])
            ->queryAll();

        if ($voucherTypeLocales) {
            $newVoucherTypeLocales = [];

            foreach ($voucherTypeLocales as $voucherTypeLocale) {
                $newVoucherTypeLocales[] = [
                    $voucherTypeLocale['voucherTypeId'],
                    $localeId,
                    $voucherTypeLocale['urlFormat']
                ];
            }

            craft()->db->createCommand()->insertAll('giftvoucher_vouchertypes_i18n', [
                'voucherTypeId',
                'locale',
                'urlFormat'
            ], $newVoucherTypeLocales);
        }

        return true;
    }
}

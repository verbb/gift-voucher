<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\models\VoucherTypeModel;
use verbb\giftvoucher\models\VoucherTypeSiteModel;
use verbb\giftvoucher\records\VoucherTypeRecord;
use verbb\giftvoucher\records\VoucherTypeSiteRecord;

use Craft;
use craft\db\Query;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\queue\jobs\ResaveElements;

use yii\base\Component;
use yii\base\Exception;

class VoucherTypesService extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SAVE_VOUCHERTYPE = 'beforeSaveVoucherType';
    const EVENT_AFTER_SAVE_VOUCHERTYPE = 'afterSaveVoucherType';


    // Properties
    // =========================================================================

    private $_fetchedAllVoucherTypes = false;
    private $_voucherTypesById;
    private $_voucherTypesByHandle;
    private $_allVoucherTypeIds;
    private $_editableVoucherTypeIds;
    private $_siteSettingsByVoucherId = [];


    // Public Methods
    // =========================================================================

    public function getEditableVoucherTypes(): array
    {
        $editableVoucherTypeIds = $this->getEditableVoucherTypeIds();
        $editableVoucherTypes = [];

        foreach ($this->getAllVoucherTypes() as $voucherTypes) {
            if (in_array($voucherTypes->id, $editableVoucherTypeIds, false)) {
                $editableVoucherTypes[] = $voucherTypes;
            }
        }

        return $editableVoucherTypes;
    }

    public function getEditableVoucherTypeIds(): array
    {
        if (null === $this->_editableVoucherTypeIds) {
            $this->_editableVoucherTypeIds = [];
            $allVoucherTypeIds = $this->getAllVoucherTypeIds();

            foreach ($allVoucherTypeIds as $voucherTypeId) {
                if (Craft::$app->getUser()->checkPermission('giftVoucher-manageVoucherType:' . $voucherTypeId)) {
                    $this->_editableVoucherTypeIds[] = $voucherTypeId;
                }
            }
        }

        return $this->_editableVoucherTypeIds;
    }

    public function getAllVoucherTypeIds(): array
    {
        if (null === $this->_allVoucherTypeIds) {
            $this->_allVoucherTypeIds = [];
            $voucherTypes = $this->getAllVoucherTypes();

            foreach ($voucherTypes as $voucherType) {
                $this->_allVoucherTypeIds[] = $voucherType->id;
            }
        }

        return $this->_allVoucherTypeIds;
    }

    public function getAllVoucherTypes(): array
    {
        if (!$this->_fetchedAllVoucherTypes) {
            $results = $this->_createVoucherTypeQuery()->all();

            foreach ($results as $result) {
                $this->_memoizeVoucherType(new VoucherTypeModel($result));
            }

            $this->_fetchedAllVoucherTypes = true;
        }

        return $this->_voucherTypesById ?: [];
    }

    public function getVoucherTypeByHandle($handle)
    {
        if (isset($this->_voucherTypesByHandle[$handle])) {
            return $this->_voucherTypesByHandle[$handle];
        }

        if ($this->_fetchedAllVoucherTypes) {
            return null;
        }

        $result = $this->_createVoucherTypeQuery()
            ->where(['handle' => $handle])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeVoucherType(new VoucherTypeModel($result));

        return $this->_voucherTypesByHandle[$handle];
    }

    public function getVoucherTypeSites($voucherTypeId): array
    {
        if (!isset($this->_siteSettingsByVoucherId[$voucherTypeId])) {
            $rows = (new Query())
                ->select([
                    'id',
                    'voucherTypeId',
                    'siteId',
                    'uriFormat',
                    'hasUrls',
                    'template'
                ])
                ->from('{{%giftvoucher_vouchertypes_sites}}')
                ->where(['voucherTypeId' => $voucherTypeId])
                ->all();

            $this->_siteSettingsByVoucherId[$voucherTypeId] = [];

            foreach ($rows as $row) {
                $this->_siteSettingsByVoucherId[$voucherTypeId][] = new VoucherTypeSiteModel($row);
            }
        }

        return $this->_siteSettingsByVoucherId[$voucherTypeId];
    }

    public function saveVoucherType(VoucherTypeModel $voucherType, bool $runValidation = true): bool
    {
        $isNewVoucherType = !$voucherType->id;

        // Fire a 'beforeSaveVoucherType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_VOUCHERTYPE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_VOUCHERTYPE, new VoucherTypeEvent([
                'voucherType' => $voucherType,
                'isNew' => $isNewVoucherType,
            ]));
        }

        if ($runValidation && !$voucherType->validate()) {
            Craft::info('Voucher type not saved due to validation error.', __METHOD__);

            return false;
        }

        if (!$isNewVoucherType) {
            $voucherTypeRecord = VoucherTypeRecord::findOne($voucherType->id);

            if (!$voucherTypeRecord) {
                throw new Exception("No voucher type exists with the ID '{$voucherType->id}'");
            }

        } else {
            $voucherTypeRecord = new VoucherTypeRecord();
        }

        $voucherTypeRecord->name = $voucherType->name;
        $voucherTypeRecord->handle = $voucherType->handle;
        $voucherTypeRecord->skuFormat = $voucherType->skuFormat;

        // Get the site settings
        $allSiteSettings = $voucherType->getSiteSettings();

        // Make sure they're all there
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSiteSettings[$siteId])) {
                throw new Exception('Tried to save a voucher type that is missing site settings');
            }
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Voucher Field Layout
            $fieldLayout = $voucherType->getVoucherFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $voucherType->fieldLayoutId = $fieldLayout->id;
            $voucherTypeRecord->fieldLayoutId = $fieldLayout->id;

            // Save the voucher type
            $voucherTypeRecord->save(false);

            // Now that we have a voucher type ID, save it on the model
            if (!$voucherType->id) {
                $voucherType->id = $voucherTypeRecord->id;
            }

            // Might as well update our cache of the voucher type while we have it.
            $this->_voucherTypesById[$voucherType->id] = $voucherType;

            // Update the site settings
            // -----------------------------------------------------------------

            $sitesNowWithoutUrls = [];
            $sitesWithNewUriFormats = [];
            $allOldSiteSettingsRecords = [];

            if (!$isNewVoucherType) {
                // Get the old voucher type site settings
                $allOldSiteSettingsRecords = VoucherTypeSiteRecord::find()
                    ->where(['voucherTypeId' => $voucherType->id])
                    ->indexBy('siteId')
                    ->all();
            }

            foreach ($allSiteSettings as $siteId => $siteSettings) {
                // Was this already selected?
                if (!$isNewVoucherType && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new VoucherTypeSiteRecord();
                    $siteSettingsRecord->voucherTypeId = $voucherType->id;
                    $siteSettingsRecord->siteId = $siteId;
                }

                $siteSettingsRecord->hasUrls = $siteSettings->hasUrls;
                $siteSettingsRecord->uriFormat = $siteSettings->uriFormat;
                $siteSettingsRecord->template = $siteSettings->template;

                if (!$siteSettingsRecord->getIsNewRecord()) {
                    // Did it used to have URLs, but not anymore?
                    if ($siteSettingsRecord->isAttributeChanged('hasUrls', false) && !$siteSettings->hasUrls) {
                        $sitesNowWithoutUrls[] = $siteId;
                    }

                    // Does it have URLs, and has its URI format changed?
                    if ($siteSettings->hasUrls && $siteSettingsRecord->isAttributeChanged('uriFormat', false)) {
                        $sitesWithNewUriFormats[] = $siteId;
                    }
                }

                $siteSettingsRecord->save(false);

                // Set the ID on the model
                $siteSettings->id = $siteSettingsRecord->id;
            }

            if (!$isNewVoucherType) {
                // Drop any site settings that are no longer being used, as well as the associated voucher/element
                // site rows
                $siteIds = array_keys($allSiteSettings);

                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    if (!in_array($siteId, $siteIds, false)) {
                        $siteSettingsRecord->delete();
                    }
                }
            }

            if (!$isNewVoucherType) {
                foreach ($allSiteSettings as $siteId => $siteSettings) {
                    Craft::$app->getQueue()->push(new ResaveElements([
                        'description' => Craft::t('app', 'Resaving {type} vouchers ({site})', [
                            'type' => $voucherType->name,
                            'site' => $siteSettings->getSite()->name,
                        ]),
                        'elementType' => Voucher::class,
                        'criteria' => [
                            'siteId' => $siteId,
                            'typeId' => $voucherType->id,
                            'status' => null,
                            'enabledForSite' => false,
                        ]
                    ]));
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveVoucherType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_VOUCHERTYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_VOUCHERTYPE, new VoucherTypeEvent([
                'voucherType' => $voucherType,
                'isNew' => $isNewVoucherType,
            ]));
        }

        return true;
    }

    public function deleteVoucherTypeById(int $id): bool
    {
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $voucherType = $this->getVoucherTypeById($id);

            $criteria = Voucher::find();
            $criteria->typeId = $voucherType->id;
            $criteria->status = null;
            $criteria->limit = null;
            $vouchers = $criteria->all();

            foreach ($vouchers as $voucher) {
                Craft::$app->getElements()->deleteElement($voucher);
            }

            $fieldLayoutId = $voucherType->getVoucherFieldLayout()->id;
            Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);

            $voucherTypeRecord = VoucherTypeRecord::findOne($voucherType->id);
            $affectedRows = $voucherTypeRecord->delete();

            if ($affectedRows) {
                $transaction->commit();
            }

            return (bool)$affectedRows;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    public function getVoucherTypeById(int $voucherTypeId)
    {
        if (isset($this->_voucherTypesById[$voucherTypeId])) {
            return $this->_voucherTypesById[$voucherTypeId];
        }

        if ($this->_fetchedAllVoucherTypes) {
            return null;
        }

        $result = $this->_createVoucherTypeQuery()
            ->where(['id' => $voucherTypeId])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeVoucherType(new VoucherTypeModel($result));

        return $this->_voucherTypesById[$voucherTypeId];
    }

    public function isVoucherTypeTemplateValid(VoucherTypeModel $voucherType, int $siteId): bool
    {
        $voucherTypeSiteSettings = $voucherType->getSiteSettings();

        if (isset($voucherTypeSiteSettings[$siteId]) && $voucherTypeSiteSettings[$siteId]->hasUrls) {
            // Set Craft to the site template mode
            $view = Craft::$app->getView();
            $oldTemplateMode = $view->getTemplateMode();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            // Does the template exist?
            $templateExists = Craft::$app->getView()->doesTemplateExist((string)$voucherTypeSiteSettings[$siteId]->template);

            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            if ($templateExists) {
                return true;
            }
        }

        return false;
    }

    public function afterSaveSiteHandler(SiteEvent $event)
    {
        if ($event->isNew) {
            $primarySiteSettings = (new Query())
                ->select(['voucherTypeId', 'uriFormat', 'template', 'hasUrls'])
                ->from(['{{%giftvoucher_vouchertypes_sites}}'])
                ->where(['siteId' => $event->oldPrimarySiteId])
                ->one();

            if ($primarySiteSettings) {
                $newSiteSettings = [];

                $newSiteSettings[] = [
                    $primarySiteSettings['voucherTypeId'],
                    $event->site->id,
                    $primarySiteSettings['uriFormat'],
                    $primarySiteSettings['template'],
                    $primarySiteSettings['hasUrls']
                ];

                Craft::$app->getDb()->createCommand()
                    ->batchInsert(
                        '{{%giftvoucher_vouchertypes_sites}}',
                        ['voucherTypeId', 'siteId', 'uriFormat', 'template', 'hasUrls'],
                        $newSiteSettings)
                    ->execute();
            }
        }
    }

    // Private methods
    // =========================================================================

    private function _memoizeVoucherType(VoucherTypeModel $voucherType)
    {
        $this->_voucherTypesById[$voucherType->id] = $voucherType;
        $this->_voucherTypesByHandle[$voucherType->handle] = $voucherType;
    }

    private function _createVoucherTypeQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'fieldLayoutId',
                'name',
                'handle',
                'skuFormat'
            ])
            ->from(['{{%giftvoucher_vouchertypes}}']);
    }
}

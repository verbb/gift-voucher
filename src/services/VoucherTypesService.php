<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\errors\VoucherTypeNotFoundException;
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\models\VoucherTypeModel;
use verbb\giftvoucher\models\VoucherTypeSiteModel;
use verbb\giftvoucher\records\VoucherTypeRecord;
use verbb\giftvoucher\records\VoucherTypeSiteRecord;


use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\events\DeleteSiteEvent;
use craft\events\FieldEvent;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

use yii\base\Component;
use yii\base\Exception;

use Throwable;

class VoucherTypesService extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_VOUCHERTYPE = 'beforeSaveVoucherType';
    public const EVENT_AFTER_SAVE_VOUCHERTYPE = 'afterSaveVoucherType';
    public const CONFIG_VOUCHERTYPES_KEY = 'giftVoucher.voucherTypes';


    // Properties
    // =========================================================================

    private bool $_fetchedAllVoucherTypes = false;
    private ?array $_voucherTypesById = null;
    private ?array $_voucherTypesByHandle = null;
    private ?array $_allVoucherTypeIds = null;
    private ?array $_editableVoucherTypeIds = null;
    private ?array $_siteSettingsByVoucherId = null;
    private ?array $_savingVoucherTypes = null;


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

    public function getEditableVoucherTypeIds(): ?array
    {
        if (null === $this->_editableVoucherTypeIds) {
            $this->_editableVoucherTypeIds = [];
            $allVoucherTypes = $this->getAllVoucherTypes();

            foreach ($allVoucherTypes as $voucherType) {
                if (Craft::$app->getUser()->checkPermission('giftVoucher-manageVoucherType:' . $voucherType->uid)) {
                    $this->_editableVoucherTypeIds[] = $voucherType->id;
                }
            }
        }

        return $this->_editableVoucherTypeIds;
    }

    public function getAllVoucherTypeIds(): ?array
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
                    'template',
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

        if ($isNewVoucherType) {
            $voucherType->uid = StringHelper::UUID();
        } else {
            $existingVoucherTypeRecord = VoucherTypeRecord::find()
                ->where(['id' => $voucherType->id])
                ->one();

            if (!$existingVoucherTypeRecord) {
                throw new VoucherTypeNotFoundException("No voucher type exists with the ID '{$voucherType->id}'");
            }

            $voucherType->uid = $existingVoucherTypeRecord->uid;
        }

        $this->_savingVoucherTypes[$voucherType->uid] = $voucherType;

        $projectConfig = Craft::$app->getProjectConfig();

        $configData = [
            'name' => $voucherType->name,
            'handle' => $voucherType->handle,
            'skuFormat' => $voucherType->skuFormat,
            'siteSettings' => [],
        ];

        $generateLayoutConfig = function(FieldLayout $fieldLayout): array {
            $fieldLayoutConfig = $fieldLayout->getConfig();

            if ($fieldLayoutConfig) {
                if (empty($fieldLayout->id)) {
                    $layoutUid = StringHelper::UUID();
                    $fieldLayout->uid = $layoutUid;
                } else {
                    $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayout->id);
                }

                return [$layoutUid => $fieldLayoutConfig];
            }

            return [];
        };

        $configData['voucherFieldLayouts'] = $generateLayoutConfig($voucherType->getFieldLayout());

        // Get the site settings
        $allSiteSettings = $voucherType->getSiteSettings();

        // Make sure they're all there
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSiteSettings[$siteId])) {
                throw new Exception('Tried to save a voucher type that is missing site settings');
            }
        }

        foreach ($allSiteSettings as $siteId => $settings) {
            $siteUid = Db::uidById('{{%sites}}', $siteId);
            $configData['siteSettings'][$siteUid] = [
                'hasUrls' => $settings['hasUrls'],
                'uriFormat' => $settings['uriFormat'],
                'template' => $settings['template'],
            ];
        }

        $configPath = self::CONFIG_VOUCHERTYPES_KEY . '.' . $voucherType->uid;
        $projectConfig->set($configPath, $configData);

        if ($isNewVoucherType) {
            $voucherType->id = Db::idByUid('{{%giftvoucher_vouchertypes}}', $voucherType->uid);
        }

        return true;
    }

    public function handleChangedVoucherType(ConfigEvent $event): void
    {
        $voucherTypeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields and sites are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $siteData = $data['siteSettings'];

            // Basic data
            $voucherTypeRecord = $this->_getVoucherTypeRecord($voucherTypeUid);
            $isNewVoucherType = $voucherTypeRecord->getIsNewRecord();
            $fieldsService = Craft::$app->getFields();

            $voucherTypeRecord->uid = $voucherTypeUid;
            $voucherTypeRecord->name = $data['name'];
            $voucherTypeRecord->handle = $data['handle'];
            $voucherTypeRecord->skuFormat = $data['skuFormat'];

            if (!empty($data['voucherFieldLayouts']) && !empty($config = reset($data['voucherFieldLayouts']))) {
                // Save the main field layout
                $layout = FieldLayout::createFromConfig($config);
                $layout->id = $voucherTypeRecord->fieldLayoutId;
                $layout->type = Voucher::class;
                $layout->uid = key($data['voucherFieldLayouts']);

                $fieldsService->saveLayout($layout);

                $voucherTypeRecord->fieldLayoutId = $layout->id;
            } else if ($voucherTypeRecord->fieldLayoutId) {
                // Delete the main field layout
                $fieldsService->deleteLayoutById($voucherTypeRecord->fieldLayoutId);
                $voucherTypeRecord->fieldLayoutId = null;
            }

            $voucherTypeRecord->save(false);

            // Update the site settings
            // -----------------------------------------------------------------

            $sitesNowWithoutUrls = [];
            $sitesWithNewUriFormats = [];
            $allOldSiteSettingsRecords = [];

            if (!$isNewVoucherType) {
                // Get the old voucher type site settings
                $allOldSiteSettingsRecords = VoucherTypeSiteRecord::find()
                    ->where(['voucherTypeId' => $voucherTypeRecord->id])
                    ->indexBy('siteId')
                    ->all();
            }

            $siteIdMap = Db::idsByUids('{{%sites}}', array_keys($siteData));

            /** @var VoucherTypeSiteRecord $siteSettings */
            foreach ($siteData as $siteUid => $siteSettings) {
                $siteId = $siteIdMap[$siteUid];

                // Was this already selected?
                if (!$isNewVoucherType && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new VoucherTypeSiteRecord();
                    $siteSettingsRecord->voucherTypeId = $voucherTypeRecord->id;
                    $siteSettingsRecord->siteId = $siteId;
                }

                if ($siteSettingsRecord->hasUrls = $siteSettings['hasUrls']) {
                    $siteSettingsRecord->uriFormat = $siteSettings['uriFormat'];
                    $siteSettingsRecord->template = $siteSettings['template'];
                } else {
                    $siteSettingsRecord->uriFormat = null;
                    $siteSettingsRecord->template = null;
                }

                if (!$siteSettingsRecord->getIsNewRecord()) {
                    // Did it used to have URLs, but not anymore?
                    if ($siteSettingsRecord->isAttributeChanged('hasUrls', false) && !$siteSettings['hasUrls']) {
                        $sitesNowWithoutUrls[] = $siteId;
                    }

                    // Does it have URLs, and has its URI format changed?
                    if ($siteSettings['hasUrls'] && $siteSettingsRecord->isAttributeChanged('uriFormat', false)) {
                        $sitesWithNewUriFormats[] = $siteId;
                    }
                }

                $siteSettingsRecord->save(false);
            }

            if (!$isNewVoucherType) {
                // Drop any site settings that are no longer being used, as well as the associated voucher/element
                // site rows
                $affectedSiteUids = array_keys($siteData);

                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    $siteUid = array_search($siteId, $siteIdMap, false);
                    if (!in_array($siteUid, $affectedSiteUids, false)) {
                        $siteSettingsRecord->delete();
                    }
                }
            }

            // Finally, deal with the existing vouchers...
            // -----------------------------------------------------------------

            if (!$isNewVoucherType) {
                // Get all the voucher IDs in this group
                $voucherIds = Voucher::find()
                    ->typeId($voucherTypeRecord->id)
                    ->anyStatus()
                    ->limit(null)
                    ->ids();

                // Are there any sites left?
                if (!empty($siteData)) {
                    // Drop the old voucher URIs for any site settings that don't have URLs
                    if (!empty($sitesNowWithoutUrls)) {
                        $db->createCommand()
                            ->update(
                                '{{%elements_sites}}',
                                ['uri' => null],
                                [
                                    'elementId' => $voucherIds,
                                    'siteId' => $sitesNowWithoutUrls,
                                ])
                            ->execute();
                    } else if (!empty($sitesWithNewUriFormats)) {
                        foreach ($voucherIds as $voucherId) {
                            App::maxPowerCaptain();

                            // Loop through each of the changed sites and update all the vouchers’ slugs and URIs
                            foreach ($sitesWithNewUriFormats as $siteId) {
                                $voucher = Voucher::find()
                                    ->id($voucherId)
                                    ->siteId($siteId)
                                    ->anyStatus()
                                    ->one();

                                if ($voucher) {
                                    Craft::$app->getElements()->updateElementSlugAndUri($voucher, false, false);
                                }
                            }
                        }
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_allVoucherTypeIds = null;
        $this->_editableVoucherTypeIds = null;
        $this->_fetchedAllVoucherTypes = false;

        unset(
            $this->_voucherTypesById[$voucherTypeRecord->id],
            $this->_voucherTypesByHandle[$voucherTypeRecord->handle],
            $this->_siteSettingsByVoucherId[$voucherTypeRecord->id]
        );

        // Fire an 'afterSaveVoucherType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_VOUCHERTYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_VOUCHERTYPE, new VoucherTypeEvent([
                'voucherType' => $this->getVoucherTypeById($voucherTypeRecord->id),
                'isNew' => empty($this->_savingVoucherTypes[$voucherTypeUid]),
            ]));
        }
    }

    public function deleteVoucherTypeById(int $id): bool
    {
        $voucherType = $this->getVoucherTypeById($id);
        Craft::$app->getProjectConfig()->remove(self::CONFIG_VOUCHERTYPES_KEY . '.' . $voucherType->uid);
        return true;
    }

    public function handleDeletedVoucherType(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $voucherTypeRecord = $this->_getVoucherTypeRecord($uid);

        if (!$voucherTypeRecord->id) {
            return;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $vouchers = Voucher::find()
                ->typeId($voucherTypeRecord->id)
                ->anyStatus()
                ->limit(null)
                ->all();

            foreach ($vouchers as $voucher) {
                Craft::$app->getElements()->deleteElement($voucher);
            }

            $fieldLayoutId = $voucherTypeRecord->fieldLayoutId;
            Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);

            $voucherTypeRecord->delete();
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Clear caches
        $this->_allVoucherTypeIds = null;
        $this->_editableVoucherTypeIds = null;
        $this->_fetchedAllVoucherTypes = false;
        unset(
            $this->_voucherTypesById[$voucherTypeRecord->id],
            $this->_voucherTypesByHandle[$voucherTypeRecord->handle],
            $this->_siteSettingsByVoucherId[$voucherTypeRecord->id]
        );
    }

    public function pruneDeletedSite(DeleteSiteEvent $event): void
    {
        $siteUid = $event->site->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $voucherTypes = $projectConfig->get(self::CONFIG_VOUCHERTYPES_KEY);

        // Loop through the voucher types and prune the UID from field layouts.
        if (is_array($voucherTypes)) {
            foreach ($voucherTypes as $voucherTypeUid => $voucherType) {
                $projectConfig->remove(self::CONFIG_VOUCHERTYPES_KEY . '.' . $voucherTypeUid . '.siteSettings.' . $siteUid);
            }
        }
    }

    public function pruneDeletedField(FieldEvent $event): void
    {
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $voucherTypes = $projectConfig->get(self::CONFIG_VOUCHERTYPES_KEY);

        // Loop through the voucher types and prune the UID from field layouts.
        if (is_array($voucherTypes)) {
            foreach ($voucherTypes as $voucherTypeUid => $voucherType) {
                if (!empty($voucherType['voucherFieldLayouts'])) {
                    foreach ($voucherType['voucherFieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_VOUCHERTYPES_KEY . '.' . $voucherTypeUid . '.voucherFieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid);
                            }
                        }
                    }
                }
            }
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

    public function getVoucherTypeByUid(string $uid)
    {
        return ArrayHelper::firstWhere($this->getAllVoucherTypes(), 'uid', $uid, true);
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

    public function afterSaveSiteHandler(SiteEvent $event): void
    {
        if ($event->isNew) {
            $primarySiteSettings = (new Query())
                ->select([
                    'voucherTypes.uid voucherTypeUid',
                    'vouchertypes_sites.uriFormat',
                    'vouchertypes_sites.template',
                    'vouchertypes_sites.hasUrls',
                ])
                ->from(['{{%giftvoucher_vouchertypes_sites}} vouchertypes_sites'])
                ->innerJoin(['{{%giftvoucher_vouchertypes}} voucherTypes'], '[[vouchertypes_sites.voucherTypeId]] = [[voucherTypes.id]]')
                ->where(['siteId' => $event->oldPrimarySiteId])
                ->one();

            if ($primarySiteSettings) {
                $newSiteSettings = [
                    'uriFormat' => $primarySiteSettings['uriFormat'],
                    'template' => $primarySiteSettings['template'],
                    'hasUrls' => $primarySiteSettings['hasUrls'],
                ];

                Craft::$app->getProjectConfig()->set(self::CONFIG_VOUCHERTYPES_KEY . '.' . $primarySiteSettings['voucherTypeUid'] . '.siteSettings.' . $event->site->uid, $newSiteSettings);
            }
        }
    }

    // Private methods
    // =========================================================================

    private function _memoizeVoucherType(VoucherTypeModel $voucherType): void
    {
        $this->_voucherTypesById[$voucherType->id] = $voucherType;
        $this->_voucherTypesByHandle[$voucherType->handle] = $voucherType;
    }

    private function _createVoucherTypeQuery(): Query
    {
        return (new Query())
            ->select([
                'voucherTypes.id',
                'voucherTypes.fieldLayoutId',
                'voucherTypes.name',
                'voucherTypes.handle',
                'voucherTypes.skuFormat',
                'voucherTypes.uid',
            ])
            ->from(['{{%giftvoucher_vouchertypes}} voucherTypes']);
    }

    private function _getVoucherTypeRecord(string $uid): VoucherTypeRecord
    {
        return VoucherTypeRecord::findOne(['uid' => $uid]) ?? new VoucherTypeRecord();
    }
}

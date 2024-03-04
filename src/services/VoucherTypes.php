<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\errors\VoucherTypeNotFoundException;
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\models\VoucherType;
use verbb\giftvoucher\models\VoucherTypeSite;
use verbb\giftvoucher\records\VoucherType as VoucherTypeRecord;
use verbb\giftvoucher\records\VoucherTypeSite as VoucherTypeSiteRecord;

use Craft;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\db\Table;
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

class VoucherTypes extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_VOUCHERTYPE = 'beforeSaveVoucherType';
    public const EVENT_AFTER_SAVE_VOUCHERTYPE = 'afterSaveVoucherType';
    public const CONFIG_VOUCHERTYPES_KEY = 'giftVoucher.voucherTypes';


    // Properties
    // =========================================================================

    private ?MemoizableArray $_voucherTypes = null;


    // Public Methods
    // =========================================================================

    public function getAllVoucherTypes(): array
    {
        return $this->_voucherTypes()->all();
    }

    public function getAllVoucherTypeIds(): array
    {
        return ArrayHelper::getColumn($this->getAllVoucherTypes(), 'id', false);
    }

    public function getVoucherTypeByHandle(string $handle): ?VoucherType
    {
        return $this->_voucherTypes()->firstWhere('handle', $handle, true);
    }

    public function getVoucherTypeById(int $id): ?VoucherType
    {
        return $this->_voucherTypes()->firstWhere('id', $id);
    }

    public function getVoucherTypeByUid(string $uid): ?VoucherType
    {
        return $this->_voucherTypes()->firstWhere('uid', $uid, true);
    }

    public function getEditableVoucherTypes(): array
    {
        $userSession = Craft::$app->getUser();
        
        return ArrayHelper::where($this->getAllVoucherTypes(), function(VoucherType $voucherType) use ($userSession) {
            return $userSession->checkPermission("giftVoucher-manageVoucherType:$voucherType->uid");
        }, true, true, false);
    }

    public function getEditableVoucherTypeIds(): array
    {
        return ArrayHelper::getColumn($this->getEditableVoucherTypes(), 'id', false);
    }

    public function getVoucherTypeSites(int $voucherTypeId): array
    {
        $results = VoucherTypeSiteRecord::find()
            ->where(['voucherTypeId' => $voucherTypeId])
            ->all();

        $siteSettings = [];

        foreach ($results as $result) {
            $siteSettings[] = new VoucherTypeSite($result->toArray([
                'id',
                'voucherTypeId',
                'siteId',
                'uriFormat',
                'hasUrls',
                'template',
            ]));
        }

        return $siteSettings;
    }

    public function saveVoucherType(VoucherType $voucherType, bool $runValidation = true): bool
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
            GiftVoucher::info('Voucher type not saved due to validation error.');

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

        $configPath = self::CONFIG_VOUCHERTYPES_KEY . '.' . $voucherType->uid;
        Craft::$app->getProjectConfig()->set($configPath, $voucherType->getConfig());

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
                    ->status(null)
                    ->limit(null)
                    ->ids();

                // Are there any sites left?
                if (!empty($siteData)) {
                    // Drop the old voucher URIs for any site settings that don't have URLs
                    if (!empty($sitesNowWithoutUrls)) {
                        Db::update('{{%elements_sites}}', ['uri' => null], [
                            'elementId' => $voucherIds,
                            'siteId' => $sitesNowWithoutUrls,
                        ]);
                    } else if (!empty($sitesWithNewUriFormats)) {
                        foreach ($voucherIds as $voucherId) {
                            App::maxPowerCaptain();

                            // Loop through each of the changed sites and update all the vouchers’ slugs and URIs
                            foreach ($sitesWithNewUriFormats as $siteId) {
                                $voucher = Voucher::find()
                                    ->id($voucherId)
                                    ->siteId($siteId)
                                    ->status(null)
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
        $this->_voucherTypes = null;

        // Fire an 'afterSaveVoucherType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_VOUCHERTYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_VOUCHERTYPE, new VoucherTypeEvent([
                'voucherType' => $this->getVoucherTypeById($voucherTypeRecord->id),
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
                ->status(null)
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
        $this->_voucherTypes = null;
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

    public function isVoucherTypeTemplateValid(VoucherType $voucherType, int $siteId): bool
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
        $projectConfig = Craft::$app->getProjectConfig();

        if ($event->isNew) {
            $oldPrimarySiteUid = Db::uidById(Table::SITES, $event->oldPrimarySiteId);
            $existingVoucherTypeSettings = $projectConfig->get(self::CONFIG_VOUCHERTYPES_KEY);

            if (!$projectConfig->getIsApplyingYamlChanges() && is_array($existingVoucherTypeSettings)) {
                foreach ($existingVoucherTypeSettings as $voucherTypeUid => $settings) {
                    $primarySiteSettings = $settings['siteSettings'][$oldPrimarySiteUid];
                    $configPath = self::CONFIG_VOUCHERTYPES_KEY . '.' . $voucherTypeUid . '.siteSettings.' . $event->site->uid;
                    $projectConfig->set($configPath, $primarySiteSettings);
                }
            }
        }
    }

    // Private Methods
    // =========================================================================

    private function _voucherTypes(): MemoizableArray
    {
        if (!isset($this->_voucherTypes)) {
            $voucherTypes = [];

            foreach ($this->_createVoucherTypeQuery()->all() as $result) {
                $voucherTypes[] = new VoucherType($result);
            }

            $this->_voucherTypes = new MemoizableArray($voucherTypes);
        }

        return $this->_voucherTypes;
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

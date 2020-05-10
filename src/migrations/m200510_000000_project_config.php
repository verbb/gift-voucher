<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\services\VoucherTypesService;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\StringHelper;

class m200510_000000_project_config extends Migration
{
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('plugins.gift-voucher.schemaVersion', true);
        if (version_compare($schemaVersion, '2.0.4', '>=')) {
            return;
        }

        $voucherTypeData = $this->_getVoucherTypeData();
        $projectConfig->set(VoucherTypesService::CONFIG_VOUCHERTYPES_KEY, $voucherTypeData);
    }

    public function safeDown()
    {
        echo "m200510_000000_project_config cannot be reverted.\n";
        return false;
    }

    // Private methods
    // =========================================================================

    private function _getVoucherTypeData(): array
    {
        $voucherTypeRows = (new Query())
            ->select([
                'fieldLayoutId',
                'name',
                'handle',
                'skuFormat',
                'uid'
            ])
            ->from(['{{%giftvoucher_vouchertypes}} voucherTypes'])
            ->all();

        $typeData = [];

        foreach ($voucherTypeRows as $voucherTypeRow) {
            $rowUid = $voucherTypeRow['uid'];

            if (!empty($voucherTypeRow['fieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($voucherTypeRow['fieldLayoutId']);

                if ($layout) {
                    $voucherTypeRow['voucherFieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            unset($voucherTypeRow['uid'], $voucherTypeRow['fieldLayoutId']);

            $voucherTypeRow['siteSettings'] = [];
            $typeData[$rowUid] = $voucherTypeRow;
        }

        $voucherTypeSiteRows = (new Query())
            ->select([
                'vouchertypes_sites.hasUrls',
                'vouchertypes_sites.uriFormat',
                'vouchertypes_sites.template',
                'sites.uid AS siteUid',
                'vouchertypes.uid AS typeUid',
            ])
            ->from(['{{%giftvoucher_vouchertypes_sites}} vouchertypes_sites'])
            ->innerJoin('{{%sites}} sites', '[[sites.id]] = [[vouchertypes_sites.siteId]]')
            ->innerJoin('{{%giftvoucher_vouchertypes}} vouchertypes', '[[vouchertypes.id]] = [[vouchertypes_sites.voucherTypeId]]')
            ->all();

        foreach ($voucherTypeSiteRows as $voucherTypeSiteRow) {
            $typeUid = $voucherTypeSiteRow['typeUid'];
            $siteUid = $voucherTypeSiteRow['siteUid'];
            unset($voucherTypeSiteRow['siteUid'], $voucherTypeSiteRow['typeUid']);

            $voucherTypeSiteRow['hasUrls'] = (bool)$voucherTypeSiteRow['hasUrls'];

            $typeData[$typeUid]['siteSettings'][$siteUid] = $voucherTypeSiteRow;
        }

        return $typeData;
    }
}


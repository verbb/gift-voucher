<?php
namespace verbb\giftvoucher\helpers;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

class ProjectConfigData
{
    // Public Methods
    // =========================================================================


    // Project config rebuild methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $output = [];

        $output['voucherTypes'] = self::_getVoucherTypeData();

        return $output;
    }

    private static function _getVoucherTypeData(): array
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
            $typeData[$typeUid]['siteSettings'][$siteUid] = $voucherTypeSiteRow;
        }

        return $typeData;
    }
}

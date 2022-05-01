<?php
namespace verbb\giftvoucher\helpers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;

use Craft;
use craft\db\Query;

class ProjectConfigData
{
    // Static Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $configData = [];

        $configData['voucherTypes'] = self::_getVoucherTypeData();

        $codeFieldLayout = Craft::$app->getFields()->getLayoutByType(Code::class);

        if ($codeFieldLayout->uid) {
            $configData['codes'] = [
                'fieldLayouts' => [
                    $codeFieldLayout->uid => $codeFieldLayout->getConfig(),
                ],
            ];
        }

        return array_filter($configData);
    }

    
    // Private Methods
    // =========================================================================

    private static function _getVoucherTypeData(): array
    {
        $data = [];

        foreach (GiftVoucher::$plugin->getVoucherTypes()->getAllVoucherTypes() as $voucherType) {
            $data[$voucherType->uid] = $voucherType->getConfig();
        }

        return $data;
    }
}

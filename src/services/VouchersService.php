<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\events\SiteEvent;
use craft\queue\jobs\ResaveElements;

use yii\base\Component;

class VouchersService extends Component
{
    // Public Methods
    // =========================================================================

    public function getVoucherById(int $id, $siteId = null)
    {
        return Craft::$app->getElements()->getElementById($id, Voucher::class, $siteId);
    }

    public function afterSaveSiteHandler(SiteEvent $event)
    {
        $queue = Craft::$app->getQueue();
        $siteId = $event->oldPrimarySiteId;
        $elementTypes = [
            Voucher::class,
        ];

        foreach ($elementTypes as $elementType) {
            $queue->push(new ResaveElements([
                'elementType' => $elementType,
                'criteria' => [
                    'siteId' => $siteId,
                    'status' => null,
                    'enabledForSite' => false
                ]
            ]));
        }
    }
}

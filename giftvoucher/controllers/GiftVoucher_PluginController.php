<?php
namespace Craft;

class GiftVoucher_PluginController extends BaseController
{
    // Public Methods
    // =========================================================================

    public function actionCheckRequirements()
    {
        $dependencies = GiftVoucherHelper::getPluginService()->checkRequirements();

        if ($dependencies) {
            $this->renderTemplate('giftVoucher/dependencies', [
                'dependencies' => $dependencies,
            ]);
        }
    }

    public function actionSettings()
    {
        $this->renderTemplate('giftvoucher/settings/general', array(
            'settings' => GiftVoucherHelper::getPlugin()->getSettings()
        ));
    }
}
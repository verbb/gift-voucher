<?php

namespace Craft;


class GiftVoucherService extends BaseApplicationComponent
{

    // Public Methods
    // =========================================================================

    public function getPlugin()
    {
        return GiftVoucherHelper::getPlugin();
    }

    public function getSettings()
    {
        return $this->getPlugin()->getSettings();
    }

    public function isLicensed()
    {
        return GiftVoucherHelper::getLicenseService()->isLicensed();
    }
}
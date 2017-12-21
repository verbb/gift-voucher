<?php

namespace Craft;

use GiftVoucher\Adjusters\GiftVoucher_DiscountAdjuster;

require __DIR__ . '/vendor/autoload.php';

class GiftVoucherPlugin extends BasePlugin
{
    // =========================================================================
    // PLUGIN INFO
    // =========================================================================

    public function getName()
    {
        return 'Gift Voucher';
    }

    public function getVersion()
    {
        return '1.0.0';
    }

    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    public function getDeveloper()
    {
        return 'Verbb';
    }

    public function getDeveloperUrl()
    {
        return 'https://verbb.io';
    }

    public function getPluginUrl()
    {
        return 'https://github.com/verbb/gift-voucher';
    }

    public function getDocumentationUrl()
    {
        return 'https://verbb.io/craft-plugins/gift-voucher/docs';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/verbb/gift-voucher/craft-2/changelog.json';
    }

    /**
     * Check for requirements only after the plugin is installed (because onBeforeInstall the plugin resources are not available).
     * Redirect to welcome screen if all dependencies are installed.
     *
     * @throws \CHttpException
     */
    public function onAfterInstall()
    {
        $dependencies = GiftVoucherHelper::getPluginService()->checkRequirements();

        if ($dependencies) {
            craft()->runController('giftVoucher/plugin/checkRequirements');
        } else {
            craft()->request->redirect(UrlHelper::getCpUrl('giftvoucher/welcome'));
        }
    }

    public function getRequiredPlugins()
    {
        return [
            [
                'name'    => 'Commerce',
                'handle'  => 'commerce',
                'url'     => 'https://craftcommerce.com',
                'version' => '1.2.0',
            ],
        ];
    }

    public function hasCpSection()
    {
        return true;
    }

    public function getSettingsUrl()
    {
        if (!GiftVoucherHelper::getLicenseService()->isLicensed()) {
            return 'giftvoucher/settings/license';
        }

        return 'giftvoucher/settings/general';
    }

    public function init()
    {
        if (craft()->request->isCpRequest()) {
            GiftVoucherHelper::getLicenseService()->ping();
            craft()->templates->hook('giftVoucher.prepCpTemplate', [
                $this,
                'prepCpTemplate',
            ]);
            $this->_includeCpResources();
        }

        $this->_registerEventHandlers();
    }

    public function registerCpRoutes()
    {
        return [
            'giftvoucher/vouchertypes/new'                                                              => ['action' => 'giftVoucher/voucherTypes/edit'],
            'giftvoucher/vouchertypes/(?P<voucherTypeId>\d+)'                                           => ['action' => 'giftVoucher/voucherTypes/edit'],
            'giftvoucher/vouchers/(?P<voucherTypeHandle>{handle})'                                      => ['action' => 'giftVoucher/vouchers/index'],
            'giftvoucher/vouchers/(?P<voucherTypeHandle>{handle})/new'                                  => ['action' => 'giftVoucher/vouchers/edit'],
            'giftvoucher/vouchers/(?P<voucherTypeHandle>{handle})/new/(?P<localeId>\w+)'                => ['action' => 'giftVoucher/vouchers/edit'],
            'giftvoucher/vouchers/(?P<voucherTypeHandle>{handle})/(?P<voucherId>\d+)'                   => ['action' => 'giftVoucher/vouchers/edit'],
            'giftvoucher/vouchers/(?P<voucherTypeHandle>{handle})/(?P<voucherId>\d+)/(?P<localeId>\w+)' => ['action' => 'giftVoucher/vouchers/edit'],
            'giftvoucher/codes/new'                                                                     => ['action' => 'giftVoucher/codes/edit'],
            'giftvoucher/codes/(?P<codeId>\d+)'                                                         => ['action' => 'giftVoucher/codes/edit'],
            'giftvoucher/settings/license'                                                              => ['action' => 'giftVoucher/license/edit'],
            'giftvoucher/settings/general'                                                              => ['action' => 'giftVoucher/plugin/settings'],
        ];
    }

    /**
     * Prepares a CP template.
     *
     * @param array &$context The current template context
     */
    public function prepCpTemplate(&$context)
    {
        $context['subnav'] = [];

        $context['subnav']['vouchers'] = [
            'label' => Craft::t('Vouchers'),
            'url'   => 'giftvoucher/vouchers',
        ];

        $context['subnav']['voucherTypes'] = [
            'label' => Craft::t('Voucher Types'),
            'url'   => 'giftvoucher/vouchertypes',
        ];

        $context['subnav']['voucherCodes'] = [
            'label' => Craft::t('Voucher Codes'),
            'url'   => 'giftvoucher/codes',
        ];

        $settingsUrl = GiftVoucherHelper::getLicenseService()->isLicensed() ? 'general' : 'license';
        $context['subnav']['settings'] = [
            'label' => Craft::t('Settings'),
            'url'   => 'giftvoucher/settings/' . $settingsUrl,
        ];
    }

    /**
     * Register commerce order adjusters
     *
     * @return array
     */
    public function commerce_registerOrderAdjusters()
    {
        return [
            401 => new GiftVoucher_DiscountAdjuster(),
        ];
    }

    /**
     * Register CP alert
     *
     * @param $path
     * @param $fetch
     *
     * @return array|null
     */
    public function getCpAlerts($path, $fetch)
    {
        if ($path !== 'giftvoucher/settings/license' && !GiftVoucherHelper::getLicenseService()->isLicensed()) {
            $alert = 'You haven’t entered your Gift Voucher license key yet.';
            $alert .= '<a class="go" href="' . UrlHelper::getCpUrl('giftvoucher/settings/license') . '">Resolve</a>';

            return [$alert];
        }

        return null;
    }


    // Protected Methods
    // =========================================================================

    protected function defineSettings()
    {
        return [
            'expiry'                        => [
                AttributeType::Number,
                'required' => true,
                'default'  => 0,
            ],
            'codeKeyLength'                 => [
                AttributeType::Number,
                'required' => true,
                'default'  => 10,
            ],
            'voucherCodesPdfPath'           => [
                AttributeType::String,
                'required' => true,
                'default'  => 'shop/_pdf/voucher',
            ],
            'voucherCodesPdfFilenameFormat' => [
                AttributeType::String,
                'required' => true,
                'default'  => 'Voucher-{number}',
            ],
            'edition'                       => [AttributeType::Mixed],
        ];
    }


    // Private Methods
    // =========================================================================

    /**
     * Includes front end resources for Control Panel requests only on Gift Voucher pages
     */
    private function _includeCpResources()
    {
        if (craft()->request->getSegment(1) == 'giftvoucher') {
            craft()->templates->includeCssResource('giftvoucher/css/GiftVoucherPlugin.css');
            craft()->templates->includeJsResource('giftvoucher/js/GiftVoucher.js');
            craft()->templates->includeJsResource('giftvoucher/js/GiftVoucherVoucherIndex.js');
        }
    }

    /**
     * Register the event handlers.
     */
    private function _registerEventHandlers()
    {
        craft()->on('commerce_orders.onOrderComplete', [
//        craft()->on('commerce_orders.onBeforeOrderComplete', [
            GiftVoucherHelper::getCodesService(),
            'onOrderCompleteHandler',
        ]);

        craft()->on('commerce_lineItems.onPopulateLineItem', [
            GiftVoucherHelper::getVouchersService(),
            'onPopulateLineItemHandler',
        ]);
    }
}

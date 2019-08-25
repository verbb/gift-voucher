<?php
namespace verbb\giftvoucher;

use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\base\PluginTrait;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\fields\Vouchers;
use verbb\giftvoucher\models\Settings;
use verbb\giftvoucher\variables\GiftVoucherVariable;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use craft\commerce\services\Purchasables;
use craft\commerce\elements\Order;
use craft\commerce\services\OrderAdjustments;

use yii\base\Event;

use fostercommerce\klaviyoconnect\services\Track;
use fostercommerce\klaviyoconnect\models\EventProperties;

class GiftVoucher extends Plugin
{
    // Public Properties
    // =========================================================================

    public $schemaVersion = '2.0.2';
    public $hasCpSettings = true;
    public $hasCpSection = true;

    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_registerCpRoutes();
        $this->_registerElementTypes();
        $this->_registerFieldTypes();
        $this->_registerPurchasableTypes();
        $this->_registerVariable();
        $this->_registerEventHandlers();
        $this->_registerCpRoutes();
        $this->_registerPermissions();
        $this->_registerAdjusters();
    }

    public function afterInstall()
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->controller->redirect(UrlHelper::cpUrl('gift-voucher/welcome'))->send();
    }

    public function getSettingsUrl(): bool
    {
        return false;
    }

    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('gift-voucher/settings'));
    }

    public function getCpNavItem(): array
    {
        $navItems = parent::getCpNavItem();

        if (Craft::$app->getUser()->checkPermission('giftVoucher-manageVouchers')) {
            $navItems['subnav']['vouchers'] = [
                'label' => Craft::t('gift-voucher', 'Vouchers'),
                'url' => 'gift-voucher/vouchers',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('giftVoucher-manageVoucherTypes')) {
            $navItems['subnav']['voucherTypes'] = [
                'label' => Craft::t('gift-voucher', 'Voucher Types'),
                'url' => 'gift-voucher/voucher-types',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('giftVoucher-manageCodes')) {
            $navItems['subnav']['codes'] = [
                'label' => Craft::t('gift-voucher', 'Voucher Codes'),
                'url' => 'gift-voucher/codes',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin()) {
            $navItems['subnav']['settings'] = [
                'label' => Craft::t('gift-voucher', 'Settings'),
                'url' => 'gift-voucher/settings',
            ];
        }

        return $navItems;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerEventHandlers()
    {
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getVoucherTypes(), 'afterSaveSiteHandler']);
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getVouchers(), 'afterSaveSiteHandler']);
        // Event::on(Order::class, Order::EVENT_AFTER_ORDER_PAID, [$this->getCodes(), 'handlePaidOrder']);
        Event::on(Order::class, Order::EVENT_BEFORE_COMPLETE_ORDER, [$this->getCodes(), 'handleCompletedOrder']);

        // Klaviyo Connect Plugin
        if (class_exists(Track::class)) {
            Event::on(Track::class, Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES, [$this->getKlaviyoConnect(), 'addLineItemCustomProperties']);
        }
    }

    private function _registerElementTypes()
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Code::class;
            $event->types[] = Voucher::class;
        });
    }

    private function _registerFieldTypes()
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Vouchers::class;
        });
    }

    private function _registerPurchasableTypes()
    {
        Event::on(Purchasables::class, Purchasables::EVENT_REGISTER_PURCHASABLE_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Voucher::class;
        });
    }

    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $voucherTypes = $this->getVoucherTypes()->getAllVoucherTypes();

            $voucherTypePermissions = [];

            foreach ($voucherTypes as $id => $voucherType) {
                $suffix = ':' . $id;
                $voucherTypePermissions['giftVoucher-manageVoucherType' . $suffix] = ['label' => Craft::t('gift-voucher', 'Manage “{type}” vouchers', ['type' => $voucherType->name])];
            }

            $event->permissions[Craft::t('gift-voucher', 'Gift Vouchers')] = [
                'giftVoucher-manageVoucherTypes' => ['label' => Craft::t('gift-voucher', 'Manage voucher types')],
                'giftVoucher-manageVouchers' => ['label' => Craft::t('gift-voucher', 'Manage vouchers'), 'nested' => $voucherTypePermissions],
                'giftVoucher-manageCodes' => ['label' => Craft::t('gift-voucher', 'Manage codes')],
            ];
        });
    }

    private function _registerAdjusters()
    {
        Event::on(OrderAdjustments::class, OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS, function(RegisterComponentTypesEvent $event) {
            $event->types[] = GiftVoucherAdjuster::class;
        });
    }

    private function _registerVariable()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('giftVoucher', GiftVoucherVariable::class);
        });
    }

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'gift-voucher/voucher-types/new' => 'gift-voucher/voucher-types/edit',
                'gift-voucher/voucher-types/<voucherTypeId:\d+>' => 'gift-voucher/voucher-types/edit',
                
                'gift-voucher/vouchers/<voucherTypeHandle:{handle}>' => 'gift-voucher/vouchers/index',
                'gift-voucher/vouchers/<voucherTypeHandle:{handle}>/new' => 'gift-voucher/vouchers/edit',
                'gift-voucher/vouchers/<voucherTypeHandle:{handle}>/new/<siteHandle:\w+>' => 'gift-voucher/vouchers/edit',
                'gift-voucher/vouchers/<voucherTypeHandle:{handle}>/<voucherId:\d+>' => 'gift-voucher/vouchers/edit',
                'gift-voucher/vouchers/<voucherTypeHandle:{handle}>/<voucherId:\d+>/<siteHandle:\w+>' => 'gift-voucher/vouchers/edit',
                
                'gift-voucher/codes/new' => 'gift-voucher/codes/edit',
                'gift-voucher/codes/<codeId:\d+>' => 'gift-voucher/codes/edit',
                
                'gift-voucher/settings' => 'gift-voucher/base/settings',
            ]);
        });
    }
    
}

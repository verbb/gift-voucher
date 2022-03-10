<?php
namespace verbb\giftvoucher;

use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\assetbundles\GiftVoucherAsset;
use verbb\giftvoucher\base\PluginTrait;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\fields\Codes;
use verbb\giftvoucher\fields\Vouchers;
use verbb\giftvoucher\helpers\ProjectConfigData;
use verbb\giftvoucher\models\Settings;
use verbb\giftvoucher\services\CodesService;
use verbb\giftvoucher\services\VoucherTypesService as VoucherTypes;
use verbb\giftvoucher\variables\GiftVoucherVariable;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\console\Controller as ConsoleController;
use craft\console\controllers\ResaveController;
use craft\events\DefineConsoleActionsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\PluginEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;

use craft\commerce\adjusters\Tax;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\commerce\services\Emails;
use craft\commerce\services\OrderAdjustments;
use craft\commerce\services\Purchasables;

use yii\base\Event;

use fostercommerce\klaviyoconnect\services\Track;
use fostercommerce\klaviyoconnect\models\EventProperties;

class GiftVoucher extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '2.0.8';

    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerCpRoutes();
        $this->_registerElementTypes();
        $this->_registerFieldTypes();
        $this->_registerPurchasableTypes();
        $this->_registerVariable();
        $this->_registerEventHandlers();
        $this->_registerCpRoutes();
        $this->_registerPermissions();
        $this->_registerAdjusters();
        $this->_registerCraftEventListeners();
        $this->_registerProjectConfigEventListeners();
        $this->_defineFieldLayoutElements();
        $this->_registerResaveCommand();
    }

    public function afterInstall(): void
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

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('gift-voucher/settings'));
    }

    public function getCpNavItem(): ?array
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

        if (Craft::$app->getUser()->checkPermission('giftVoucher-bulkGenerateCodes')) {
            $navItems['subnav']['bulk-generate'] = [
                'label' => Craft::t('gift-voucher', 'Bulk Generate Codes'),
                'url' => 'gift-voucher/codes/bulk-generate',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $navItems['subnav']['settings'] = [
                'label' => Craft::t('gift-voucher', 'Settings'),
                'url' => 'gift-voucher/settings',
            ];
        }

        return $navItems;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerEventHandlers(): void
    {
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getVoucherTypes(), 'afterSaveSiteHandler']);
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getVouchers(), 'afterSaveSiteHandler']);
        // Event::on(Order::class, Order::EVENT_AFTER_ORDER_PAID, [$this->getCodes(), 'handlePaidOrder']);
        Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, [$this->getCodes(), 'handleCompletedOrder']);

        // Validate LineItems in case there are required fields in the Voucher Code that is generated by this LineItem
        Event::on(LineItem::class, LineItem::EVENT_BEFORE_VALIDATE, [$this->getCodes(), 'handleValidateLineItem']);

        // Potentially add the PDF to an email
        Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, [$this->getVouchers(), 'onBeforeSendEmail']);
        Event::on(Emails::class, Emails::EVENT_AFTER_SEND_MAIL, [$this->getVouchers(), 'onAfterSendEmail']);

        Event::on(View::class, View::EVENT_END_BODY, function($event) {
            $request = Craft::$app->getRequest();

            // Check if on the order overview screen, or editing an order
            if ($request->isCpRequest && str_contains($request->fullPath, '/commerce/orders')) {
                $event->sender->registerAssetBundle(GiftVoucherAsset::class);

                $routeParams = Craft::$app->getUrlManager()->getRouteParams();
                $orderId = $routeParams['orderId'] ?? '';

                if ($orderId) {
                    $order = Order::find()->id($orderId)->one();

                    // Only show for incompleted orders
                    if (!$order->isCompleted) {
                        $event->sender->registerJs("(function() {
                            new Craft.GiftVoucher.CpAddVoucher('" . $order->number . "');
                        })();");
                    }
                }
            }
        });

        // Klaviyo Connect Plugin
        if (Craft::$app->plugins->getPlugin('klaviyoconnect') && class_exists(Track::class)) {
            Event::on(Track::class, Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES, [$this->getKlaviyoConnect(), 'addLineItemCustomProperties']);
        }
    }

    private function _registerElementTypes(): void
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event): void {
            $event->types[] = Code::class;
            $event->types[] = Voucher::class;
        });
    }

    private function _registerFieldTypes(): void
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event): void {
            $event->types[] = Vouchers::class;
            $event->types[] = Codes::class;
        });
    }

    private function _registerPurchasableTypes(): void
    {
        Event::on(Purchasables::class, Purchasables::EVENT_REGISTER_PURCHASABLE_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event): void {
            $event->types[] = Voucher::class;
        });
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event): void {
            $voucherTypes = $this->getVoucherTypes()->getAllVoucherTypes();

            $voucherTypePermissions = [];

            foreach ($voucherTypes as $voucherType) {
                $suffix = ':' . $voucherType->uid;
                $voucherTypePermissions['giftVoucher-manageVoucherType' . $suffix] = ['label' => Craft::t('gift-voucher', 'Manage “{type}” vouchers', ['type' => $voucherType->name])];
            }

            $event->permissions[] = [
                'heading' => Craft::t('gift-voucher', 'Gift Vouchers'),
                'permissions' => [
                    'giftVoucher-manageVoucherTypes' => ['label' => Craft::t('gift-voucher', 'Manage voucher types')],
                    'giftVoucher-manageVouchers' => ['label' => Craft::t('gift-voucher', 'Manage vouchers'), 'nested' => $voucherTypePermissions],
                    'giftVoucher-manageCodes' => ['label' => Craft::t('gift-voucher', 'Manage codes')],
                    'giftVoucher-bulkGenerateCodes' => ['label' => Craft::t('gift-voucher', 'Bulk generate codes')],
                ],
            ];
        });
    }

    private function _registerAdjusters(): void
    {
        Event::on(OrderAdjustments::class, OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS, function(RegisterComponentTypesEvent $event): void {
            $settings = $this->getSettings();

            // Re-order the built-in adjusters to ensure gift vouchers are applied before tax.
            if ($settings->registerAdjuster === 'beforeTax') {
                $types = $event->types;

                // Find the Tax adjuster, it should go before that, but if it's not found (Commerce Lite), append
                $taxKey = array_search(Tax::class, $event->types);

                if ($taxKey) {
                    array_splice($types, $taxKey, 0, GiftVoucherAdjuster::class);
                } else {
                    $types[] = GiftVoucherAdjuster::class;
                }

                $event->types = $types;
            } else if ($settings->registerAdjuster === 'afterTax') {
                $event->types[] = GiftVoucherAdjuster::class;
            }
        });
    }

    private function _registerVariable(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event): void {
            $variable = $event->sender;
            $variable->set('giftVoucher', GiftVoucherVariable::class);
        });
    }

    private function _registerCraftEventListeners(): void
    {
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(Plugins::class, Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS, function(PluginEvent $event): void {
                if ($event->plugin === $this) {
                    $this->getCodes()->saveFieldLayout();
                }
            });
        }
    }

    private function _registerProjectConfigEventListeners(): void
    {
        $projectConfigService = Craft::$app->getProjectConfig();
        $voucherTypeService = $this->getVoucherTypes();
        $codesService = $this->getCodes();

        $projectConfigService->onAdd(VoucherTypes::CONFIG_VOUCHERTYPES_KEY . '.{uid}', [$voucherTypeService, 'handleChangedVoucherType'])
            ->onUpdate(VoucherTypes::CONFIG_VOUCHERTYPES_KEY . '.{uid}', [$voucherTypeService, 'handleChangedVoucherType'])
            ->onRemove(VoucherTypes::CONFIG_VOUCHERTYPES_KEY . '.{uid}', [$voucherTypeService, 'handleDeletedVoucherType']);

        $projectConfigService->onAdd(CodesService::CONFIG_FIELDLAYOUT_KEY, [$codesService, 'handleChangedFieldLayout'])
            ->onUpdate(CodesService::CONFIG_FIELDLAYOUT_KEY, [$codesService, 'handleChangedFieldLayout'])
            ->onRemove(CodesService::CONFIG_FIELDLAYOUT_KEY, [$codesService, 'handleDeletedFieldLayout']);

        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$voucherTypeService, 'pruneDeletedField']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$voucherTypeService, 'pruneDeletedSite']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event): void {
            $event->config['giftVoucher'] = ProjectConfigData::rebuildProjectConfig();
        });
    }

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event): void {
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

                'gift-voucher/codes/bulk-generate' => 'gift-voucher/codes/bulk-generate',

                'gift-voucher/settings' => 'gift-voucher/base/settings',
            ]);
        });
    }

    private function _defineFieldLayoutElements(): void
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS, function(DefineFieldLayoutFieldsEvent $e) {
            /** @var FieldLayout $fieldLayout */
            $fieldLayout = $e->sender;

            if ($fieldLayout->type == Voucher::class) {
                $e->fields[] = TitleField::class;
            }
        });
    }

    private function _registerResaveCommand(): void
    {
        if (!Craft::$app instanceof ConsoleApplication) {
            return;
        }

        Event::on(ResaveController::class, ConsoleController::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $e) {
            $e->actions['gift-voucher-vouchers'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;
                    $query = Voucher::find();
                    return $controller->resaveElements($query);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Gift Voucher vouchers.',
            ];

            $e->actions['gift-voucher-codes'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;
                    $query = Code::find();
                    return $controller->resaveElements($query);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Gift Voucher codes.',
            ];
        });
    }

}

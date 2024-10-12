<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\events\MatchCodeEvent;
use verbb\giftvoucher\events\PopulateCodeFromLineItemEvent;
use verbb\giftvoucher\models\Redemption;

use Craft;
use craft\base\Element;
use craft\events\ConfigEvent;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

use craft\commerce\elements\Order;
use craft\commerce\enums\LineItemType;
use craft\commerce\models\LineItem;

use yii\base\Component;
use yii\base\Event;
use yii\base\ModelEvent;

use DateTime;
use Throwable;

class Codes extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_MATCH_CODE = 'beforeMatchCode';

    /**
     * This event is fired when a new Code is created after an order is complete
     * to give users a chance to modify the field layout
     */
    public const EVENT_POPULATE_CODE_FROM_LINE_ITEM = 'populateCodeFromLineItem';

    public const CONFIG_FIELDLAYOUT_KEY = 'giftVoucher.codes.fieldLayouts';


    // Public Methods
    // =========================================================================

    public function getCodeById(int $codeId, ?int $siteId = null): ?Code
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($codeId, Code::class, $siteId);
    }

    public static function handleCompletedOrder(Event $event): void
    {
        try {
            /** @var Order $order */
            $order = $event->sender;

            foreach ($order->lineItems as $lineItem) {
                $purchasable = $lineItem->purchasable ?? null;
                $quantity = $lineItem->qty;

                if ($purchasable && $purchasable instanceof Voucher) {
                    for ($i = 0; $i < $quantity; $i++) {
                        GiftVoucher::info('Adding {id} to code storage', [
                            'id' => $lineItem->id,
                        ]);

                        $success = GiftVoucher::$plugin->getCodes()->codeVoucherByOrder($purchasable, $order, $lineItem);

                        if (!$success) {
                            $error = Craft::t('app', 'Unable to save voucher: “{errors}”.', [
                                'errors' => Json::encode($purchasable->getErrors()),
                            ]);

                            GiftVoucher::error($error);
                        }
                    }
                }
            }

            // Handle redemption of vouchers (when someone is using a code)
            $giftVoucherCodes = GiftVoucher::$plugin->getCodeStorage()->getCodeKeys($order);

            if ($giftVoucherCodes && count($giftVoucherCodes) > 0) {
                foreach ($order->getAdjustments() as $adjustment) {
                    if ($adjustment->type === GiftVoucherAdjuster::ADJUSTMENT_TYPE) {
                        $code = null;

                        if (isset($adjustment->sourceSnapshot['codeKey'])) {
                            $codeKey = $adjustment->sourceSnapshot['codeKey'];
                            $code = Code::findOne(['codeKey' => $codeKey]);
                        }

                        if ($code) {
                            $code->currentAmount += $adjustment->amount;
                            Craft::$app->getElements()->saveElement($code, false);

                            // Track code redemption
                            $redemption = new Redemption();
                            $redemption->codeId = $code->id;
                            $redemption->orderId = $order->id;
                            $redemption->amount = $adjustment->amount * -1;

                            if (!GiftVoucher::$plugin->getRedemptions()->saveRedemption($redemption)) {
                                $error = Craft::t('app', 'Unable to save redemption: “{errors}”.', [
                                    'errors' => Json::encode($redemption->getErrors()),
                                ]);

                                GiftVoucher::error($error);
                            }
                        } else {
                            $error = Craft::t('app', 'Unable to find matching code in adjustment snapshot: “{adjustment}”.', [
                                'adjustment' => Json::encode($adjustment),
                            ]);

                            GiftVoucher::error($error);
                        }
                    }
                }

                // Delete the code
                GiftVoucher::$plugin->getCodeStorage()->setCodes([], $order);
            } else {
                $error = Craft::t('app', 'No vouchers in code storage for order {id}', [
                    'id' => $order->id,
                ]);

                GiftVoucher::info($error);
            }
        } catch (Throwable $e) {
            $error = Craft::t('app', 'Unable to complete gift voucher order: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            GiftVoucher::error($error);
        }
    }

    public function isCodeKeyUnique(string $codeKey): bool
    {
        return !(bool)Code::findOne(['codeKey' => $codeKey]);
    }

    /**
     * Create a Code after an Order is completed
     *
     *
     * @throws Throwable
     */
    public function codeVoucherByOrder(Voucher $voucher, Order $order, LineItem $lineItem): bool
    {
        $success = false;

        try {
            $code = new Code();
            $code->voucherId = $voucher->id;
            $code->orderId = $order->id;
            $code->lineItemId = $lineItem->id;

            $code->originalAmount = $lineItem->price;
            $code->currentAmount = $lineItem->price;

            // add the field layout fields
            $customFields = $this->populateCodeByLineItem($code, $lineItem);

            // give plugins a chance to change/modify it
            if ($this->hasEventHandlers(self::EVENT_POPULATE_CODE_FROM_LINE_ITEM)) {
                $this->trigger(self::EVENT_POPULATE_CODE_FROM_LINE_ITEM, new PopulateCodeFromLineItemEvent([
                    'code' => $code,
                    'order' => $order,
                    'lineItem' => $lineItem,
                    'customFields' => $customFields,
                    'voucher' => $voucher,
                ]));
            }

            // TODO: Maybe set the scenario to live to validate the custom fields
            // we already validate the line item before it can be added to the cart -> they are fine the moment
            // they are added but what's the proper scenario if a client changes the required fields between creating a
            // line item and completing the order ¯\_(ツ)_/¯
            // $code->setScenario(Element::SCENARIO_LIVE);

            $success = Craft::$app->getElements()->saveElement($code, false);

            if (!$success) {
                GiftVoucher::error('Unable to save code: “{errors}”.', [
                    'errors' => Json::encode($code->getErrors()),
                ]);
            }
        } catch (Throwable $e) {
            $error = Craft::t('app', 'Unable to save voucher code for order: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            GiftVoucher::error($error);
        }

        return $success;
    }

    /**
     * Populates a Code by LineItem options and return the valid custom fields
     *
     *
     */
    public function populateCodeByLineItem(Code $code, LineItem $lineItem): array
    {
        $settings = GiftVoucher::$plugin->getSettings();

        $validFields = [];

        // Grab the options from the lineItems, those may contain the field values
        $options = $lineItem->getOptions() ?? [];

        if ($fieldLayout = $code->getFieldLayout()) {
            if ($fields = $fieldLayout->getCustomFields()) {
                foreach ($fields as $field) {
                    $fieldHandle = $field->handle;

                    if (isset($options[$fieldHandle])) {
                        $code->setFieldValue($fieldHandle, $options[$fieldHandle]);
                        $validFields[$fieldHandle] = $options[$fieldHandle];
                    }
                }
            }
        }

        return $validFields;
    }

    /**
     * Validate Line Items that are Vouchers based on required Field Layout Fields
     */
    public function handleValidateLineItem(ModelEvent $event): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $event->sender;

        if ($lineItem->type === LineItemType::Custom) {
            return;
        }

        $purchasable = $lineItem->getPurchasable();

        // make sure it's a Voucher
        if ($purchasable instanceof Voucher) {
            // try to create a new Code based on the LineItem
            $code = new Code();
            $code->voucherId = $purchasable->id;
            $this->populateCodeByLineItem($code, $lineItem);
            $code->setScenario(Element::SCENARIO_LIVE);

            // if validation fails it means you can't create a code with the current settings
            if ($code->validate() === false) {
                // invalidate it -> let users know about missing/wrong fields
                $lineItem->addErrors($code->getErrors());

                $event->isValid = false;
            }
        }
    }

    public function generateCodeKey(): string
    {
        $codeAlphabet = GiftVoucher::$plugin->getSettings()->codeKeyCharacters;
        $keyLength = GiftVoucher::$plugin->getSettings()->codeKeyLength;

        $codeKey = '';

        for ($i = 0; $i < $keyLength; $i++) {
            $codeKey .= $codeAlphabet[random_int(0, strlen($codeAlphabet) - 1)];
        }

        return $codeKey;
    }

    public function matchCode($codeKey, &$error = ''): bool
    {
        $code = Code::findOne(['codeKey' => $codeKey]);

        if ($this->hasEventHandlers(self::EVENT_BEFORE_MATCH_CODE)) {
            $event = new MatchCodeEvent([
                'code' => $code,
                'codeKey' => $codeKey,
            ]);
            $this->trigger(self::EVENT_BEFORE_MATCH_CODE, $event);

            if (!empty($event->error)) {
                $error = $event->error;

                return false;
            }
        }

        // Check if valid
        if (!$code) {
            $error = Craft::t('gift-voucher', 'Voucher code is not valid');

            return false;
        }

        // Check if voucher has an amount left
        if ($code->currentAmount <= 0) {
            $error = Craft::t('gift-voucher', 'Voucher code has no amount left');

            return false;
        }

        // Check for expiry date
        $today = new DateTime();
        if ($code->expiryDate && $code->expiryDate->format('Ymd') < $today->format('Ymd')) {
            $error = Craft::t('gift-voucher', 'Voucher code is out of date');

            return false;
        }

        return true;
    }

    public function handleChangedFieldLayout(ConfigEvent $event): void
    {
        $data = $event->newValue;

        ProjectConfigHelper::ensureAllFieldsProcessed();
        $fieldsService = Craft::$app->getFields();

        if (empty($data) || empty($config = reset($data))) {
            // Delete the field layout
            $fieldsService->deleteLayoutsByType(Code::class);
            return;
        }

        // Save the field layout
        $layout = FieldLayout::createFromConfig(reset($data));
        $layout->id = $fieldsService->getLayoutByType(Code::class)->id;
        $layout->type = Code::class;
        $layout->uid = key($data);
        $fieldsService->saveLayout($layout);
    }

    public function pruneDeletedField($event): void
    {
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $layoutData = $projectConfig->get(self::CONFIG_FIELDLAYOUT_KEY);

        // Prune the UID from field layouts.
        if (is_array($layoutData)) {
            foreach ($layoutData as $layoutUid => $layout) {
                if (!empty($layout['tabs'])) {
                    foreach ($layout['tabs'] as $tabUid => $tab) {
                        $projectConfig->remove(self::CONFIG_FIELDLAYOUT_KEY . '.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid);
                    }
                }
            }
        }
    }

    public function handleDeletedFieldLayout(ConfigEvent $event): void
    {
        Craft::$app->getFields()->deleteLayoutsByType(Code::class);
    }

    public function saveFieldLayout(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $fieldLayoutUid = StringHelper::UUID();

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost('settings');
        $layoutData = $projectConfig->get(self::CONFIG_FIELDLAYOUT_KEY) ?? [];

        if ($layoutData) {
            $fieldLayoutUid = array_keys($layoutData)[0];
        }

        $configData = [$fieldLayoutUid => $fieldLayout->getConfig()];

        $projectConfig->set(self::CONFIG_FIELDLAYOUT_KEY, $configData);
    }
}

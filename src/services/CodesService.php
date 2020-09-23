<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\events\PopulateCodeFromLineItemEvent;
use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\models\RedemptionModel;

use Craft;
use craft\base\Element;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

use yii\base\Component;
use yii\base\Event;
use yii\base\ModelEvent;

class CodesService extends Component
{
    /**
     * This event is fired when a new Code is created after an order is complete
     * to give users a chance to modify the field layout
     */
    const EVENT_POPULATE_CODE_FROM_LINE_ITEM = 'populateCodeFromLineItem';

    const CONFIG_FIELDLAYOUT_KEY = 'giftVoucher.codes.fieldLayouts';


    // Public Methods
    // =========================================================================

    public function isCodeKeyUnique(string $codeKey): bool
    {
        return !(bool)Code::findOne(['codeKey' => $codeKey]);
    }

    public static function handleCompletedOrder(Event $event)
    {
        /** @var Order $order */
        $order = $event->sender;
        $lineItems = $order->getLineItems();

        foreach ($lineItems as $lineItem) {
            $itemId = $lineItem->purchasableId;
            $element = Craft::$app->getElements()->getElementById($itemId);
            $quantity = $lineItem->qty;

            if ($element instanceof Voucher) {
                for ($i = 0; $i < $quantity; $i++) {
                   GiftVoucher::getInstance()->getCodes()->codeVoucherByOrder($element, $order, $lineItem);
                }
            }
        }

        // Handle redemption of vouchers (when someone is using a code)
        $giftVoucherCodes = GiftVoucher::getInstance()->getCodeStorage()->getCodeKeys($order);

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
                        Craft::$app->getElements()->saveElement($code);

                        // Track code redemption
                        $redemption = new RedemptionModel();
                        $redemption->codeId = $code->id;
                        $redemption->orderId = $order->id;
                        $redemption->amount = (float)$adjustment->amount * -1;
                        GiftVoucher::$plugin->getRedemptions()->saveRedemption($redemption);
                    }
                }
            }

            // Delete the code
            GiftVoucher::getInstance()->getCodeStorage()->setCodes([], $order);
        }
    }

    /**
     * Create a Code after an Order is completed
     *
     * @param \verbb\giftvoucher\elements\Voucher $voucher
     * @param \craft\commerce\elements\Order      $order
     * @param \craft\commerce\models\LineItem     $lineItem
     *
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     * @return bool
     */
    public function codeVoucherByOrder(Voucher $voucher, Order $order, LineItem $lineItem): bool
    {
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
                'code'          => $code,
                'order'         => $order,
                'lineItem'      => $lineItem,
                'customFields'  => $customFields,
                'voucher'       => $voucher
            ]));
        }

        // TODO: Maybe set the scenario to live to validate the custom fields
        // we already validate the line item before it can be added to the cart -> they are fine the moment
        // they are added but what's the proper scenario if a client changes the required fields between creating a
        // line item and completing the order ¯\_(ツ)_/¯
        // $code->setScenario(Element::SCENARIO_LIVE);

        return Craft::$app->getElements()->saveElement($code);
    }

    /**
     * Populates a Code by LineItem options and return the valid custom fields
     *
     * @param \verbb\giftvoucher\elements\Code $code
     * @param \craft\commerce\models\LineItem  $lineItem
     *
     * @return array
     */
    public function populateCodeByLineItem(Code $code, LineItem $lineItem): array
    {
        $settings = GiftVoucher::getInstance()->getSettings();
        $fieldLayoutId = $settings->fieldLayoutId;
        $validFields = [];

        if ($settings->fieldLayoutId !== null) {
            // Set the field layout id
            $code->fieldLayoutId = $fieldLayoutId;
            // Grab the options from the lineItems, those may contain the field values
            $options = $lineItem->getOptions();

            // okay that might seems a little bit creepy but imagine the case the field layout changes
            // between storing the line item and creating the code or if the user changes the `fieldsPath` setting
            // we need to make sure the fields inserted in the options contain valid, still existing fields
            // and only if that's the case we want to set them otherwise users will see exceptions
            // that's why we loop every valid field in the layout
            $fieldLayout = $code->getFieldLayout();

            if ($fieldLayout !== null && ($fields = $fieldLayout->getFields())) {
                /** @var \craft\base\Field $field */
                foreach ($fields as $field){
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
     *
     * @param \yii\base\ModelEvent $event
     */
    public function handleValidateLineItem(ModelEvent $event)
    {
        /** @var LineItem $lineItem */
        $lineItem = $event->sender;
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
        $codeAlphabet = GiftVoucher::getInstance()->getSettings()->codeKeyCharacters;
        $keyLength = GiftVoucher::getInstance()->getSettings()->codeKeyLength;

        $codeKey = '';

        for ($i = 0; $i < $keyLength; $i++) {
            $codeKey .= $codeAlphabet[random_int(0, strlen($codeAlphabet) - 1)];
        }

        return $codeKey;
    }

    public function matchCode($codeKey, &$error)
    {
        $code = Code::findOne(['codeKey' => $codeKey]);

        // Check if valid
        if (!$code) {
            $error = Craft::t('gift-voucher', 'Voucher code is not valid');

            return false;
        }

        // Check if has an amount left
        if ($code->currentAmount <= 0) {
            $error = Craft::t('gift-voucher', 'Voucher code has no amount left');

            return false;
        }

        // Check for expiry date
        $today = new \DateTime();
        if ($code->expiryDate && $code->expiryDate->format('Ymd') < $today->format('Ymd')) {
            $error = Craft::t('gift-voucher', 'Voucher code is out of date');

            return false;
        }

        return true;
    }

    public function handleChangedFieldLayout(ConfigEvent $event)
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

    public function pruneDeletedField(FieldEvent $event)
    {
        /** @var Field $field */
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

    public function handleDeletedFieldLayout(ConfigEvent $event)
    {
        Craft::$app->getFields()->deleteLayoutsByType(Code::class);
    }

    public function saveFieldLayout()
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

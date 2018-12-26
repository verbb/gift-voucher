<?php
namespace verbb\giftvoucher\elements;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\db\CodeQuery;
use verbb\giftvoucher\events\GenerateCodeEvent;
use verbb\giftvoucher\models\VoucherTypeModel;
use verbb\giftvoucher\records\CodeRecord;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Delete;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

use Exception;
use yii\base\InvalidConfigException;

class Code extends Element
{
    // Constants
    // =========================================================================

    const EVENT_GENERATE_CODE_KEY = 'beforeGenerateCodeKey';


    // Properties
    // =========================================================================

    public $id;
    public $voucherId;
    public $orderId;
    public $lineItemId;
    public $codeKey;
    public $originalAmount;
    public $currentAmount;
    public $expiryDate;

    private $_voucher;
    private $_order;
    private $_lineItem;


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return (string)$this->codeKey;
    }

    public function getVoucher()
    {
        if ($this->_voucher) {
            return $this->_voucher;
        }

        if ($this->voucherId) {
            return $this->_voucher = Voucher::findOne($this->voucherId);
        }

        return null;
    }

    public function getOrder()
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->orderId) {
            return $this->_order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return null;
    }

    public function getLineItem()
    {
        if ($this->_lineItem) {
            return $this->_lineItem;
        }

        if ($this->lineItemId) {
            return $this->_lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($this->lineItemId);
        }

        return null;
    }

    public function getVoucherType()
    {
        $voucher = $this->getVoucher();

        if ($voucher) {
            return $voucher->getType();
        }

        return null;
    }

    public function getVoucherName(): string
    {
        return (string) $this->getVoucher();
    }

    public function getAmount()
    {
        return $this->currentAmount;
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('gift-voucher/codes/' . $this->id);
    }

    public function getRedemptions()
    {
        if ($this->id) {
            return GiftVoucher::$plugin->getRedemptions()->getRedemptionsByCodeId($this->id);
        }
    }

    public function getName()
    {
        return Craft::t('gift-voucher', 'Code');
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public function getPdfUrl($option = null)
    {
        return GiftVoucher::$plugin->getPdf()->getPdfUrlForCode($this, $option = null);
    }

    public static function defineSources(string $context = null): array
    {
        $voucherTypes = GiftVoucher::getInstance()->getVoucherTypes()->getAllVoucherTypes();

        $voucherTypeIds = [];

        foreach ($voucherTypes as $voucherType) {
            $voucherTypeIds[] = $voucherType->id;
        }

        $sources = [
            '*' => [
                'label' => Craft::t('gift-voucher', 'All voucher types'),
                'criteria' => ['typeId' => $voucherTypeIds],
                'defaultSort' => ['dateCreated', 'desc']
            ]
        ];

        $sources[] = ['heading' => Craft::t('gift-voucher', 'Voucher Types')];

        foreach ($voucherTypes as $voucherType) {
            $key = 'voucherType:' . $voucherType->id;

            $sources[$key] = [
                'key' => $key,
                'label' => $voucherType->name,
                'data' => [
                    'handle' => $voucherType->handle
                ],
                'criteria' => ['typeId' => $voucherType->id]
            ];
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('gift-voucher', 'Are you sure you want to delete the selected codes?'),
            'successMessage' => Craft::t('gift-voucher', 'Codes deleted.'),
        ]);

        return $actions;
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle)
    {
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        if ($handle === 'voucher') {
            $map = (new Query())
                ->select('id as source, voucherId as target')
                ->from('{{%giftvoucher_codes}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return array(
                'elementType' => Voucher::class,
                'map' => $map
            );
        }

        if ($handle === 'order') {
            $map = (new Query())
                ->select('id as source, orderId as target')
                ->from('{{%giftvoucher_codes}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return array(
                'elementType' => Order::class,
                'map' => $map
            );
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle === 'voucher') {
            $this->_voucher = $elements[0] ?? null;

            return;
        }

        if ($handle === 'order') {
            $this->_order = $elements[0] ?? null;

            return;
        }

        parent::setEagerLoadedElements($handle, $elements);
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['voucherId'], 'required'];
        $rules[] = [['expiryDate'], DateTimeValidator::class];

        return $rules;
    }

    public static function find(): ElementQueryInterface
    {
        return new CodeQuery(static::class);
    }

    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'expiryDate';

        return $attributes;
    }

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $codeRecord = CodeRecord::findOne($this->id);

            if (!$codeRecord) {
                throw new InvalidConfigException('Invalid code id: ' . $this->id);
            }
        } else {
            $codeRecord = new CodeRecord();
            $codeRecord->id = $this->id;
        }

        if ($isNew) {
            $codeRecord->lineItemId = $this->lineItemId;
            $codeRecord->orderId = $this->orderId;
            $codeRecord->voucherId = $this->voucherId;
            $codeRecord->codeKey = $this->generateCodeKey();
            // set the codeKey to the Code as well to use it directly
            $this->codeKey = $codeRecord->codeKey;
        }
        
        $codeRecord->originalAmount = $this->originalAmount;
        $codeRecord->currentAmount = $this->currentAmount;
        $codeRecord->expiryDate = $this->expiryDate;

        $defaultExpiry = GiftVoucher::getInstance()->getSettings()->expiry;

        // If not specifying an expiry and we have a default expiry
        if ($isNew && !$codeRecord->expiryDate && $defaultExpiry) {
            $newExpiry = DateTimeHelper::toDateTime(new \DateTime);
            $newExpiry->modify('+' . $defaultExpiry . ' month');
            $newExpiry->setTime(0, 0, 0);

            $codeRecord->expiryDate = DateTimeHelper::toIso8601($newExpiry);
        }

        $codeRecord->save(false);
    }


    // Protected Methods
    // =========================================================================

    protected function generateCodeKey(): string
    {
        $generateCodeKeyEvent = new GenerateCodeEvent(['code' => $this]);

        // Raising the 'beforeGenerateCodeKey' event
        if ($this->hasEventHandlers(self::EVENT_GENERATE_CODE_KEY)) {
            $this->trigger(self::EVENT_GENERATE_CODE_KEY, $generateCodeKeyEvent);
        }

        // If a plugin provided the code key - use that.
        if ($generateCodeKeyEvent->codeKey !== null) {
            return $generateCodeKeyEvent->codeKey;
        }

        do {
            $codeKey = GiftVoucher::getInstance()->getCodes()->generateCodeKey();
        } while (!GiftVoucher::getInstance()->getCodes()->isCodeKeyUnique($codeKey));

        return $codeKey;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'code' => ['label' => Craft::t('gift-voucher', 'Code')],
            'voucher' => ['label' => Craft::t('gift-voucher', 'Voucher')],
            'voucherType' => ['label' => Craft::t('gift-voucher', 'Voucher Type')],
            'orderLink' => ['label' => Craft::t('gift-voucher', 'Order')],
            'currentAmount' => ['label' => Craft::t('gift-voucher', 'Amount')],
            'expiryDate' => ['label' => Craft::t('gift-voucher', 'Expiry Date')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source === '*') {
            $attributes[] = 'voucherType';
        }

        $attributes[] = 'code';
        $attributes[] = 'voucher';
        $attributes[] = 'dateCreated';
        $attributes[] = 'orderLink';
        $attributes[] = 'currentAmount';
        $attributes[] = 'expiryDate';

        return $attributes;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['voucherName'];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'voucher':
                if ($this->getVoucher()) {
                    return '<a href="' . $this->getVoucher()->getCpEditUrl() . '">' . $this->getVoucher() . '</a>';
                }
            case 'orderLink':
                if ($this->getOrder()) {
                    return '<a href="' . $this->getOrder()->getCpEditUrl() . '">' . $this->getOrder() . '</a>';
                }
            case 'currentAmount':
                return Craft::$app->getLocale()->getFormatter()->asCurrency($this->currentAmount);
            case 'expiryDate':
                return (!$this->expiryDate) ? '∞' : parent::tableAttributeHtml($attribute);
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }

    protected static function defineSortOptions(): array
    {
        return [
            'slug' => Craft::t('gift-voucher', 'Code'),
            'dateCreated' => Craft::t('gift-voucher', 'Date Created'),
        ];
    }


    // Protected methods
    // =========================================================================

    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute)
    {
        if ($attribute === 'voucher') {
            $with = $elementQuery->with ?: [];
            $with[] = 'voucher';
            $elementQuery->with = $with;
            return;
        }

        parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
    }
}

<?php
namespace verbb\giftvoucher\elements;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\db\CodeQuery;
use verbb\giftvoucher\events\GenerateCodeEvent;
use verbb\giftvoucher\records\Code as CodeRecord;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Delete;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

use yii\base\InvalidConfigException;

use DateTime;

class Code extends Element
{
    // Constants
    // =========================================================================

    public const EVENT_GENERATE_CODE_KEY = 'beforeGenerateCodeKey';


    // Static Methods
    // =========================================================================

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): CodeQuery
    {
        return new CodeQuery(static::class);
    }

    public static function defineSources(string $context = null): array
    {
        $voucherTypes = GiftVoucher::$plugin->getVoucherTypes()->getAllVoucherTypes();

        $voucherTypeIds = [];

        foreach ($voucherTypes as $voucherType) {
            $voucherTypeIds[] = $voucherType->id;
        }

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('gift-voucher', 'All voucher types'),
                'criteria' => ['typeId' => $voucherTypeIds],
                'defaultSort' => ['dateCreated', 'desc'],
            ],
        ];

        $sources[] = ['heading' => Craft::t('gift-voucher', 'Voucher Types')];

        foreach ($voucherTypes as $voucherType) {
            $key = 'voucherType:' . $voucherType->id;

            $sources[] = [
                'key' => $key,
                'label' => $voucherType->name,
                'data' => [
                    'handle' => $voucherType->handle,
                ],
                'criteria' => ['typeId' => $voucherType->id],
            ];
        }

        return $sources;
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

        if ($handle === 'voucher') {
            $map = (new Query())
                ->select('id as source, voucherId as target')
                ->from('{{%giftvoucher_codes}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => Voucher::class,
                'map' => $map,
            ];
        }

        if ($handle === 'order') {
            $map = (new Query())
                ->select('id as source, orderId as target')
                ->from('{{%giftvoucher_codes}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => Order::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
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

    protected static function defineTableAttributes(): array
    {
        return [
            'codeKey' => ['label' => Craft::t('gift-voucher', 'Code')],
            'voucher' => ['label' => Craft::t('gift-voucher', 'Voucher')],
            'voucherType' => ['label' => Craft::t('gift-voucher', 'Voucher Type')],
            'orderLink' => ['label' => Craft::t('gift-voucher', 'Order')],
            'originalAmount' => ['label' => Craft::t('gift-voucher', 'Original Amount')],
            'currentAmount' => ['label' => Craft::t('gift-voucher', 'Current Amount')],
            'expiryDate' => ['label' => Craft::t('gift-voucher', 'Expiry Date')],
            'dateCreated' => ['label' => Craft::t('gift-voucher', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('gift-voucher', 'Date Updated')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source === '*') {
            $attributes[] = 'voucherType';
        }

        $attributes[] = 'codeKey';
        $attributes[] = 'voucher';
        $attributes[] = 'dateCreated';
        $attributes[] = 'orderLink';
        $attributes[] = 'currentAmount';
        $attributes[] = 'expiryDate';

        return $attributes;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['voucherName', 'codeKey', 'orderReference'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'slug' => Craft::t('gift-voucher', 'Code'),
            'dateCreated' => Craft::t('gift-voucher', 'Date Created'),
        ];
    }

    protected static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        if ($attribute === 'voucher') {
            $with = $elementQuery->with ?: [];
            $with[] = 'voucher';
            $elementQuery->with = $with;
            return;
        }

        parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
    }


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $voucherId = null;
    public ?int $orderId = null;
    public ?int $lineItemId = null;
    public ?string $codeKey = null;
    public ?float $originalAmount = null;
    public ?float $currentAmount = null;
    public ?DateTime $expiryDate = null;

    private ?Voucher $_voucher = null;
    private ?Order $_order = null;
    private ?LineItem $_lineItem = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->codeKey;
    }

    public function getName(): string
    {
        return Craft::t('gift-voucher', 'Code');
    }

    public function setEagerLoadedElements(string $handle, array $elements): void
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

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('gift-voucher/codes/' . $this->id);
    }

    public function getVoucher(): ?Voucher
    {
        if ($this->_voucher) {
            return $this->_voucher;
        }

        if ($this->voucherId) {
            // find disabled vouchers as well, this is only for the CP
            $this->_voucher = Voucher::find()->id($this->voucherId)->status(null)->one();
            return $this->_voucher;
        }

        return null;
    }

    public function getOrder(): ?Order
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->orderId) {
            return $this->_order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return null;
    }

    public function getOrderReference(): ?string
    {
        if ($order = $this->getOrder()) {
            return $order->reference;
        }

        return null;
    }

    public function getLineItem(): ?LineItem
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
        return (string)$this->getVoucher();
    }

    public function getAmount(): ?float
    {
        return $this->currentAmount;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        return Craft::$app->getFields()->getLayoutByType(self::class);
    }

    public function getRedemptions(): array
    {
        if ($this->id) {
            return GiftVoucher::$plugin->getRedemptions()->getRedemptionsByCodeId($this->id);
        }

        return [];
    }

    public function getPdfUrl($option = null): string
    {
        return GiftVoucher::$plugin->getPdf()->getPdfUrlForCode($this, $option = null);
    }

    public function afterSave(bool $isNew): void
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

        $defaultExpiry = GiftVoucher::$plugin->getSettings()->expiry;

        // If not specifying an expiry, and we have a default expiry
        if ($isNew && !$codeRecord->expiryDate && $defaultExpiry) {
            $newExpiry = DateTimeHelper::toDateTime(new DateTime);
            $newExpiry->modify('+' . $defaultExpiry . ' month');
            $newExpiry->setTime(0, 0, 0);

            $codeRecord->expiryDate = DateTimeHelper::toIso8601($newExpiry);
        }

        $codeRecord->save(false);

        parent::afterSave($isNew);
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
            $codeKey = GiftVoucher::$plugin->getCodes()->generateCodeKey();
        } while (!GiftVoucher::$plugin->getCodes()->isCodeKeyUnique($codeKey));

        return $codeKey;
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'voucher':
            {
                if ($this->getVoucher()) {
                    return '<a href="' . $this->getVoucher()->getCpEditUrl() . '">' . $this->getVoucher() . '</a>';
                }

                return '-';
            }
            case 'voucherType':
            {
                if ($this->getVoucherType()) {
                    return '<a href="' . $this->getVoucherType()->getCpEditUrl() . '">' . $this->getVoucherType()->name . '</a>';
                }

                return '';
            }
            case 'orderLink':
            {

                if ($this->getOrder()) {
                    return '<a href="' . $this->getOrder()->getCpEditUrl() . '">' . $this->getOrder() . '</a>';
                }

                return '-';
            }
            case 'currentAmount':
            case 'originalAmount':
            {
                $code = Commerce::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();

                return Craft::$app->getLocale()->getFormatter()->asCurrency($this->$attribute, strtoupper($code));
            }
            case 'expiryDate':
            {
                return (!$this->expiryDate) ? '∞' : parent::tableAttributeHtml($attribute);
            }
            default:
            {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }
}

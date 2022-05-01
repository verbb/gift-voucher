<?php
namespace verbb\giftvoucher\elements;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\db\VoucherQuery;
use verbb\giftvoucher\events\CustomizeVoucherSnapshotDataEvent;
use verbb\giftvoucher\events\CustomizeVoucherSnapshotFieldsEvent;
use verbb\giftvoucher\models\VoucherType;
use verbb\giftvoucher\records\Voucher as VoucherRecord;

use Craft;
use craft\elements\db\ElementQueryInterface;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;

use craft\commerce\Plugin as Commerce;
use craft\commerce\base\Purchasable;
use craft\commerce\models\LineItem;
use craft\commerce\models\ShippingCategory;
use craft\commerce\models\TaxCategory;

use yii\base\Exception;
use yii\base\InvalidConfigException;

use DateTime;

class Voucher extends Purchasable
{
    // Constants
    // =========================================================================

    public const STATUS_LIVE = 'live';
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXPIRED = 'expired';

    public const EVENT_BEFORE_CAPTURE_VOUCHER_SNAPSHOT = 'beforeCaptureVoucherSnapshot';
    public const EVENT_AFTER_CAPTURE_VOUCHER_SNAPSHOT = 'afterCaptureVoucherSnapshot';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('gift-voucher', 'Gift Voucher');
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function find(): VoucherQuery
    {
        return new VoucherQuery(static::class);
    }

    public static function defineSources(string $context = null): array
    {
        if ($context === 'index') {
            $voucherTypes = GiftVoucher::$plugin->getVoucherTypes()->getEditableVoucherTypes();
            $editable = true;
        } else {
            $voucherTypes = GiftVoucher::$plugin->getVoucherTypes()->getAllVoucherTypes();
            $editable = false;
        }

        $voucherTypeIds = [];

        foreach ($voucherTypes as $voucherType) {
            $voucherTypeIds[] = $voucherType->id;
        }

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('gift-voucher', 'All vouchers'),
                'criteria' => [
                    'typeId' => $voucherTypeIds,
                    'editable' => $editable,
                ],
                'defaultSort' => ['postDate', 'desc'],
            ],
        ];

        $sources[] = ['heading' => Craft::t('gift-voucher', 'Voucher Types')];

        foreach ($voucherTypes as $voucherType) {
            $key = 'voucherType:' . $voucherType->id;
            $canEditVouchers = Craft::$app->getUser()->checkPermission('giftVoucher-manageVoucherType:' . $voucherType->id);

            $sources[$key] = [
                'key' => $key,
                'label' => $voucherType->name,
                'data' => [
                    'handle' => $voucherType->handle,
                    'editable' => $canEditVouchers,
                ],
                'criteria' => ['typeId' => $voucherType->id, 'editable' => $editable],
            ];
        }

        return $sources;
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if ($handle === 'existingCodes') {
            $userId = Craft::$app->getUser()->getId();

            if ($userId) {
                $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

                $map = (new Query())
                    ->select('voucherId as source, id as target')
                    ->from('{{%giftvoucher_codes}}')
                    ->where(['in', 'voucherId', $sourceElementIds])
                    ->andWhere(['userId' => $userId])
                    ->all();

                return [
                    'elementType' => Code::class,
                    'map' => $map,
                ];
            }
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('gift-voucher', 'Are you sure you want to delete the selected vouchers?'),
            'successMessage' => Craft::t('gift-voucher', 'Vouchers deleted.'),
        ]);

        return $actions;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('gift-voucher', 'Title')],
            'type' => ['label' => Craft::t('gift-voucher', 'Type')],
            'slug' => ['label' => Craft::t('gift-voucher', 'Slug')],
            'sku' => ['label' => Craft::t('gift-voucher', 'SKU')],
            'price' => ['label' => Craft::t('gift-voucher', 'Price')],
            'postDate' => ['label' => Craft::t('gift-voucher', 'Post Date')],
            'expiryDate' => ['label' => Craft::t('gift-voucher', 'Expiry Date')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source === '*') {
            $attributes[] = 'type';
        }

        $attributes[] = 'postDate';
        $attributes[] = 'expiryDate';

        return $attributes;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['title'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('gift-voucher', 'Title'),
            'postDate' => Craft::t('gift-voucher', 'Post Date'),
            'expiryDate' => Craft::t('gift-voucher', 'Expiry Date'),
            'price' => Craft::t('gift-voucher', 'Price'),
        ];
    }


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $typeId = null;
    public ?int $taxCategoryId = null;
    public ?int $shippingCategoryId = null;
    public ?DateTime $postDate = null;
    public ?DateTime $expiryDate = null;
    public ?string $sku = null;
    public ?float $price = null;
    public ?float $customAmount = null;
    public bool $promotable = true;
    public bool $availableForPurchase = true;

    private ?VoucherType $_voucherType = null;
    private ?array $_existingCodes = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->title;
    }

    public function getName(): ?string
    {
        return $this->title;
    }

    public function getStatuses(): array
    {
        return [
            self::STATUS_LIVE => Craft::t('gift-voucher', 'Live'),
            self::STATUS_PENDING => Craft::t('gift-voucher', 'Pending'),
            self::STATUS_EXPIRED => Craft::t('gift-voucher', 'Expired'),
            self::STATUS_DISABLED => Craft::t('gift-voucher', 'Disabled'),
        ];
    }

    public function setEagerLoadedElements(string $handle, array $elements): void
    {
        if ($handle === 'existingCodes') {
            $this->_existingCodes = $elements;

            return;
        }

        parent::setEagerLoadedElements($handle, $elements);
    }

    public function getIsAvailable(): bool
    {
        if (!$this->availableForPurchase) {
            return false;
        }

        return $this->getStatus() === static::STATUS_LIVE;
    }

    public function getStatus(): ?string
    {
        $status = parent::getStatus();

        if ($status === self::STATUS_ENABLED && $this->postDate) {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $postDate = $this->postDate->getTimestamp();
            $expiryDate = $this->expiryDate ? $this->expiryDate->getTimestamp() : null;

            if ($postDate <= $currentTime && (!$expiryDate || $expiryDate > $currentTime)) {
                return self::STATUS_LIVE;
            }

            if ($postDate > $currentTime) {
                return self::STATUS_PENDING;
            }

            return self::STATUS_EXPIRED;
        }

        return $status;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['typeId', 'sku', 'price'], 'required'];
        $rules[] = [['sku'], 'string'];
        $rules[] = [['postDate', 'expiryDate'], DateTimeValidator::class];

        return $rules;
    }

    public function getIsEditable(): bool
    {
        if ($this->getType()) {
            $id = $this->getType()->id;

            return Craft::$app->getUser()->checkPermission('giftVoucher-manageVoucherType:' . $id);
        }

        return false;
    }

    public function getCpEditUrl(): ?string
    {
        $voucherType = $this->getType();

        if ($voucherType) {
            return UrlHelper::cpUrl('gift-voucher/vouchers/' . $voucherType->handle . '/' . $this->id);
        }

        return null;
    }

    public function getPdfUrl(LineItem $lineItem, $option = null): string
    {
        return GiftVoucher::$plugin->getPdf()->getPdfUrl($lineItem->order, $lineItem);
    }

    public function getCodes(LineItem $lineItem)
    {
        return Code::find()
            ->orderId($lineItem->order->id)
            ->lineItemId($lineItem->id)
            ->all();
    }

    public function getProduct(): static
    {
        return $this;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        $voucherType = $this->getType();

        return $voucherType ? $voucherType->getVoucherFieldLayout() : null;
    }

    public function getUriFormat(): ?string
    {
        $voucherTypeSiteSettings = $this->getType()->getSiteSettings();

        if (!isset($voucherTypeSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('Voucher’s type (' . $this->getType()->id . ') is not enabled for site ' . $this->siteId);
        }

        return $voucherTypeSiteSettings[$this->siteId]->uriFormat;
    }

    public function getType()
    {
        if ($this->_voucherType) {
            return $this->_voucherType;
        }

        return $this->typeId ? $this->_voucherType = GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeById($this->typeId) : null;
    }

    public function getTaxCategory(): ?TaxCategory
    {
        if ($this->taxCategoryId) {
            return Commerce::getInstance()->getTaxCategories()->getTaxCategoryById($this->taxCategoryId);
        }

        return null;
    }


    // Implement Purchasable
    // =========================================================================

    public function getShippingCategory(): ?ShippingCategory
    {
        if ($this->shippingCategoryId) {
            return Commerce::getInstance()->getShippingCategories()->getShippingCategoryById($this->shippingCategoryId);
        }

        return null;
    }

    public function getExistingCodes(): array
    {
        if ($this->_existingCodes === null) {
            $this->_existingCodes = [];
            $userId = Craft::$app->getUser()->getId();

            if ($userId) {
                $this->_existingCodes = Code::find()->ownerId($userId)->all();
            }
        }

        return $this->_existingCodes;
    }

    public function beforeSave(bool $isNew): bool
    {
        if ($this->enabled && !$this->postDate) {
            // Default the post date to the current date/time
            $this->postDate = DateTimeHelper::currentUTCDateTime();
        }

        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $voucherRecord = VoucherRecord::findOne($this->id);

            if (!$voucherRecord) {
                throw new Exception('Invalid voucher id: ' . $this->id);
            }
        } else {
            $voucherRecord = new VoucherRecord();
            $voucherRecord->id = $this->id;
        }

        $voucherRecord->postDate = $this->postDate;
        $voucherRecord->expiryDate = $this->expiryDate;
        $voucherRecord->typeId = $this->typeId;
        $voucherRecord->promotable = $this->promotable;
        $voucherRecord->availableForPurchase = $this->availableForPurchase;
        $voucherRecord->taxCategoryId = $this->taxCategoryId;
        $voucherRecord->shippingCategoryId = $this->shippingCategoryId;
        $voucherRecord->price = $this->price;
        $voucherRecord->customAmount = $this->customAmount;

        // Generate SKU if empty
        if (empty($this->sku)) {
            try {
                $voucherType = GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeById($this->typeId);
                $this->sku = Craft::$app->getView()->renderObjectTemplate($voucherType->skuFormat, $this);
            } catch (\Exception) {
                $this->sku = '';
            }
        }

        $voucherRecord->sku = $this->sku;

        $voucherRecord->save(false);

        parent::afterSave($isNew);
    }

    public function getPurchasableId(): ?int
    {
        return $this->id;
    }

    public function getSnapshot(): array
    {
        $data = [];

        $data['type'] = self::class;

        // Default Voucher custom field handles
        $voucherFields = [];
        $voucherFieldsEvent = new CustomizeVoucherSnapshotFieldsEvent([
            'voucher' => $this,
            'fields' => $voucherFields,
        ]);

        // Allow plugins to modify fields to be fetched
        if ($this->hasEventHandlers(self::EVENT_BEFORE_CAPTURE_VOUCHER_SNAPSHOT)) {
            $this->trigger(self::EVENT_BEFORE_CAPTURE_VOUCHER_SNAPSHOT, $voucherFieldsEvent);
        }

        $voucherAttributes = $this->attributes();

        // Remove custom fields
        if (($fieldLayout = $this->getFieldLayout()) !== null) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                ArrayHelper::removeValue($voucherAttributes, $field->handle);
            }
        }

        // Add back the custom fields they want
        foreach ($voucherFieldsEvent->fields as $field) {
            $voucherAttributes[] = $field;
        }

        $variantData = $this->toArray($voucherAttributes, [], false);

        $voucherDataEvent = new CustomizeVoucherSnapshotDataEvent([
            'voucher' => $this,
            'fieldData' => $variantData,
        ]);

        // Allow plugins to modify captured Voucher data
        if ($this->hasEventHandlers(self::EVENT_AFTER_CAPTURE_VOUCHER_SNAPSHOT)) {
            $this->trigger(self::EVENT_AFTER_CAPTURE_VOUCHER_SNAPSHOT, $voucherDataEvent);
        }

        return array_merge($voucherDataEvent->fieldData, $data);
    }

    public function getPrice(): float
    {
        return (float)$this->price;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getDescription(): string
    {
        return $this->title;
    }

    public function getTaxCategoryId(): int
    {
        return $this->taxCategoryId;
    }


    // Protected methods
    // =========================================================================

    public function getShippingCategoryId(): int
    {
        return $this->shippingCategoryId;
    }

    public function hasFreeShipping(): bool
    {
        return true;
    }

    public function getIsPromotable(): bool
    {
        return $this->promotable;
    }

    public function populateLineItem(LineItem $lineItem): void
    {
        if ($lineItem->purchasable === $this && $lineItem->purchasable->customAmount) {
            $options = $lineItem->options;

            if (isset($options['amount'])) {
                $lineItem->price = $options['amount'];
                $lineItem->salePrice = $options['amount'];
            }
        }
    }

    protected function route(): array|string|null
    {
        // Make sure the voucher type is set to have URLs for this site
        $siteId = Craft::$app->getSites()->currentSite->id;
        $voucherTypeSiteSettings = $this->getType()->getSiteSettings();

        if (!isset($voucherTypeSiteSettings[$siteId]) || !$voucherTypeSiteSettings[$siteId]->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => $voucherTypeSiteSettings[$siteId]->template,
                'variables' => [
                    'voucher' => $this,
                    'product' => $this,
                ],
            ],
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        $voucherType = $this->getType();

        switch ($attribute) {
            case 'type':
            {
                return ($voucherType ? Craft::t('site', $voucherType->name) : '');
            }
            case 'taxCategory':
            {
                $taxCategory = $this->getTaxCategory();

                return ($taxCategory ? Craft::t('site', $taxCategory->name) : '');
            }
            case 'shippingCategory':
            {
                $shippingCategory = $this->getShippingCategory();

                return ($shippingCategory ? Craft::t('site', $shippingCategory->name) : '');
            }
            case 'defaultPrice':
            {
                $code = Commerce::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();

                return Craft::$app->getLocale()->getFormatter()->asCurrency($this->$attribute, strtoupper($code));
            }
            case 'availableForPurchase':
            case 'promotable':
            {
                return ($this->$attribute ? '<span data-icon="check" title="' . Craft::t('gift-voucher', 'Yes') . '"></span>' : '');
            }
            default:
            {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }
}

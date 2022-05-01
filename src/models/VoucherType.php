<?php
namespace verbb\giftvoucher\models;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\records\VoucherType as VoucherTypeRecord;

use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

class VoucherType extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $skuFormat = null;
    public ?string $template = null;
    public ?int $fieldLayoutId = null;
    public ?string $uid = null;

    private ?array $_siteSettings = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->handle;
    }

    public function rules(): array
    {
        return [
            [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['handle'], UniqueValidator::class, 'targetClass' => VoucherTypeRecord::class, 'targetAttribute' => ['handle'], 'message' => 'Not Unique'],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
        ];
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('gift-voucher/voucher-types/' . $this->id);
    }

    public function getSiteSettings(): array
    {
        if ($this->_siteSettings !== null) {
            return $this->_siteSettings;
        }

        if (!$this->id) {
            return [];
        }

        $this->setSiteSettings(ArrayHelper::index(GiftVoucher::$plugin->getVoucherTypes()->getVoucherTypeSites($this->id), 'siteId'));

        return $this->_siteSettings;
    }

    public function setSiteSettings(array $siteSettings): void
    {
        $this->_siteSettings = $siteSettings;

        foreach ($this->_siteSettings as $settings) {
            $settings->setVoucherType($this);
        }
    }

    public function getVoucherFieldLayout(): FieldLayout
    {
        return $this->getBehavior('voucherFieldLayout')->getFieldLayout();
    }

    public function behaviors(): array
    {
        return [
            'voucherFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Voucher::class,
                'idAttribute' => 'fieldLayoutId',
            ],
        ];
    }
}

<?php
namespace verbb\giftvoucher\fields;

use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\storage\Order as OrderStorage;

use Craft;
use craft\base\ElementInterface;
use craft\fields\BaseRelationField;

use craft\commerce\elements\Order as CommerceOrder;

class Codes extends BaseRelationField
{
    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('gift-voucher', 'Gift Voucher Code');
    }

    public static function icon(): string
    {
        return '@verbb/giftvoucher/icon-mask.svg';
    }

    public static function elementType(): string
    {
        return Code::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('gift-voucher', 'Add a gift voucher code');
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        $storage = GiftVoucher::$plugin->getCodeStorage();

        if (
            $element instanceof CommerceOrder &&
            $storage instanceof OrderStorage &&
            $storage->fieldHandle === $this->handle
        ) {
            // Order storage is server-owned; storefront requests must not replace its persisted code relationships.
            return $element->getFieldValue($this->handle);
        }

        return parent::normalizeValueFromRequest($value, $element);
    }
}

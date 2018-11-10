<?php
namespace verbb\giftvoucher\fields;

use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\fields\BaseRelationField;

class Vouchers extends BaseRelationField
{
    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('gift-voucher', 'Gift Vouchers');
    }

    protected static function elementType(): string
    {
        return Voucher::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('gift-voucher', 'Add a gift voucher');
    }
}

<?php
namespace verbb\giftvoucher\fields;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\fields\BaseRelationField;

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
}
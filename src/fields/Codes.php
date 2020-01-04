<?php
/**
 * Gift-Voucher for Craft CMS 3.x
 *
 * Created with PhpStorm.
 *
 * @link      https://github.com/Anubarak/
 * @email     anubarak1993@gmail.com
 * @copyright Copyright (c) 2019 Robin Schambach
 */

namespace verbb\giftvoucher\fields;

use Craft;
use craft\fields\BaseRelationField;
use verbb\giftvoucher\elements\Code;

/**
 * Class Orders
 * @package modules\myspa\fields
 * since 2.0.16
 */
class Codes extends BaseRelationField
{
    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('gift-voucher', 'Voucher Code');
    }

    /**
     * @return string
     */
    public static function defaultSelectionLabel(): string
    {
        return \Craft::t('gift-voucher', 'Add Code');
    }

    /**
     * @return string
     */
    protected static function elementType(): string
    {
        return Code::class;
    }
}
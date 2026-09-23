<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\models\VoucherType;

use verbb\base\services\Templates as BaseTemplates;

class Templates extends BaseTemplates
{
    // Properties
    // =========================================================================

    public string $pluginClass = GiftVoucher::class;
    public string|false|null $sandboxedAutoescape = false;


    // Public Methods
    // =========================================================================

    public function getSandboxedVariables(): array
    {
        return $this->getSiteTemplateVariables();
    }

    public function getDefaultSandboxedAllowedProperties(): array
    {
        return [
            Voucher::class => ['name', 'sku', 'price', 'customAmount', 'promotable', 'availableForPurchase', 'postDate', 'expiryDate', 'type', 'typeId', 'taxCategoryId', 'shippingCategoryId'],
            VoucherType::class => ['id', 'uid', 'name', 'handle'],
        ] + parent::getDefaultSandboxedAllowedProperties();
    }
}

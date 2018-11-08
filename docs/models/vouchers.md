# Vouchers

When you're on a single voucher template, or looping through vouchers using `craft.giftVoucher.vouchers()`, you're actually working with a `Voucher` element. This in turn extends Commerce's [Purchasable](https://docs.craftcms.com/commerce/v2/purchasables.html) object.

## Querying Vouchers

Vouchers can be queried using the following:

```twig
{% set vouchers = craft.giftVoucher.vouchers({
    type: 'giftCards',
}) %}

{% for voucher in vouchers %}
    {{ voucher.name }}
{% endfor %}
```

## Attributes

Attribute | Description
--- | ---
`id` | ID of the voucher.
`title` | The voucher name/title.
`name` | The voucher name/title.
`url` | The URL to this single voucher.
`purchasableId` | Returns this vouchers id - as vouchers are purchasables.
`type` | The voucher's product type.
`typeId` | The voucher's voucher type Id
`price` | The listing price of the voucher.
`customAmount` | Whether this voucher should have a custom (user-provided) amount.
`sku` | The sku of the voucher.
`status` | live, pending or expired based on postDate and expiryDate dates. Pending are vouchers with a future postDate date.
`enabled` | true or false
`taxCategoryId` | The ID for the tax category this voucher uses when their tax calculations are made.
`taxCategory` | The tax category this voucher uses when their tax calculations are made.
`shippingCategoryId` | The ID for the shipping category this voucher uses when their shipping calculations are made.
`shippingCategory` | The shipping category this voucher uses when their shipping calculations are made.
`postDate` | The date this voucher is available for sale.
`expiryDate` | The date this voucher will no longer be available for sale.

## Methods

Method | Description
--- | ---
`getCpEditUrl()` | The url to edit this voucher in the control panel.
`getPdfUrl(LineItem $lineItem, $option = null)` | Get the PDF URL for this voucher and [Line Item](https://docs.craftcms.com/commerce/api/v2/craft-commerce-models-lineitem.html).
`getCodes(LineItem $lineItem)` | Get all Code's for a provided [Line Item](https://docs.craftcms.com/commerce/api/v2/craft-commerce-models-lineitem.html) and voucher.
`getProduct()` | Convenience method for native Commerce product behaviour. 

# Voucher
Whenever you're dealing with a vouchers in your template, you're actually working with a `Voucher` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

ID of the voucher.
:::

::: reference
### `title`

**Type:** `string|null`

The voucher name/title.
:::

::: reference
### `name`

**Type:** `string|null`

The voucher name/title.
:::

::: reference
### `url`

**Type:** `string|null`

The URL to this single voucher.
:::

::: reference
### `purchasableId`

**Type:** `int|null`

Returns this vouchers id - as vouchers are purchasables.
:::

::: reference
### `type`

**Type:** `VoucherType|null`

The voucher's product type.
:::

::: reference
### `typeId`

**Type:** `int|null`

The voucher's voucher type ID
:::

::: reference
### `price`

**Type:** `float|null`

The listing price of the voucher.
:::

::: reference
### `customAmount`

**Type:** `float|null`

Whether this voucher should have a custom (user-provided) amount.
:::

::: reference
### `sku`

**Type:** `string|null`

The sku of the voucher.
:::

::: reference
### `status`

**Type:** `string|null`

live, pending or expired based on postDate and expiryDate dates. Pending are vouchers with a future postDate date.
:::

::: reference
### `enabled`

true or false
:::

::: reference
### `taxCategoryId`

**Type:** `int|null`

The ID for the tax category this voucher uses when their tax calculations are made.
:::

::: reference
### `taxCategory`

**Type:** `TaxCategory`

The tax category this voucher uses when their tax calculations are made.
:::

::: reference
### `shippingCategoryId`

**Type:** `int|null`

The ID for the shipping category this voucher uses when their shipping calculations are made.
:::

::: reference
### `shippingCategory`

**Type:** `ShippingCategory`

The shipping category this voucher uses when their shipping calculations are made.
:::

::: reference
### `postDate`

**Type:** `DateTime|null`

The date this voucher is available for sale.
:::

::: reference
### `expiryDate`

**Type:** `DateTime|null`

The date this voucher will no longer be available for sale.
:::


## Methods

::: reference
### `getCpEditUrl()`

The url to edit this voucher in the control panel.
:::

::: reference
### `getPdfUrl(LineItem $lineItem, $option = null)`

**Returns:** `string`

Get the PDF URL for this voucher and [Line Item](https://docs.craftcms.com/commerce/api/v2/craft-commerce-models-lineitem.html).
:::

::: reference
### `getCodes(LineItem $lineItem)`

Get all Code's for a provided [Line Item](https://docs.craftcms.com/commerce/api/v2/craft-commerce-models-lineitem.html) and voucher.
:::

::: reference
### `getProduct()`

**Returns:** `static`

Convenience method for native Commerce product behaviour.
:::

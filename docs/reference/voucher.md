# Voucher
Whenever you’re dealing with a voucher in your template, you’re working with a `Voucher` object. A voucher is the Commerce purchasable customers buy, and each purchase creates one or more [Code](docs:reference/code) objects that customers redeem. You can fetch vouchers with a [voucher query](docs:getting-elements/voucher-queries).

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the voucher.
:::

::: reference
### `title`

**Type:** `string|null`

The voucher’s title.
:::

::: reference
### `name`

**Type:** `string|null`

The voucher’s title. This is the same as `title`, and is available because Commerce refers to a purchasable’s name.
:::

::: reference
### `url`

**Type:** `string|null`

The URL of the voucher’s own page. This is `null` when its voucher type has no URI format.
:::

::: reference
### `purchasableId`

**Type:** `int|null`

The ID Commerce uses for the voucher as a purchasable, which is the same as its `id`. Use it when adding the voucher to the cart.
:::

::: reference
### `type`

**Type:** `VoucherType|null`

The [voucher type](docs:feature-tour/voucher-types) the voucher belongs to.
:::

::: reference
### `typeId`

**Type:** `int|null`

The ID of the voucher’s voucher type.
:::

::: reference
### `price`

**Type:** `float|null`

The price of the voucher. When the voucher has a custom amount, the customer’s chosen amount is used instead.
:::

::: reference
### `customAmount`

**Type:** `bool|null`

Whether customers choose the voucher’s amount when they buy it, rather than paying its set price. See [Custom Amounts](docs:feature-tour/custom-amounts).
:::

::: reference
### `sku`

**Type:** `string|null`

The voucher’s SKU, which identifies it in Commerce.
:::

::: reference
### `status`

**Type:** `string|null`

The voucher’s status, which is `live`, `pending`, `expired` or `disabled`. An enabled voucher is pending before its post date, expired after its expiry date and live in between.
:::

::: reference
### `enabled`

**Type:** `bool`

Whether the voucher is enabled.
:::

::: reference
### `taxCategoryId`

**Type:** `int|null`

The ID of the tax category Commerce uses when calculating tax on this voucher.
:::

::: reference
### `taxCategory`

**Type:** `TaxCategory`

The tax category Commerce uses when calculating tax on this voucher.
:::

::: reference
### `shippingCategoryId`

**Type:** `int|null`

The ID of the shipping category Commerce uses when calculating shipping for this voucher.
:::

::: reference
### `shippingCategory`

**Type:** `ShippingCategory`

The shipping category Commerce uses when calculating shipping for this voucher.
:::

::: reference
### `postDate`

**Type:** `DateTime|null`

The date the voucher goes on sale.
:::

::: reference
### `expiryDate`

**Type:** `DateTime|null`

The date the voucher stops being available for sale. This is `null` when it doesn’t expire.
:::


## Methods

::: reference
### `getCpEditUrl()`

**Returns:** `string|null`

Returns the URL to edit this voucher in the control panel.
:::

::: reference
### `getPdfUrl()`

**Arguments:** `LineItem $lineItem`, `mixed $option = null`

**Returns:** `string`

Returns the URL to download the PDF of the codes bought in a Commerce line item. Use it on an order confirmation page or email to link to the customer’s voucher PDF. See [PDF Vouchers](docs:feature-tour/pdf-vouchers).
:::

::: reference
### `getCodes()`

**Arguments:** `LineItem $lineItem`

**Returns:** `array`

Returns the [Code](docs:reference/code) objects created for a Commerce line item, one for each voucher bought in it.
:::

::: reference
### `getProduct()`

**Returns:** `static`

Returns the voucher itself. Commerce templates often call `purchasable.product`, so this lets the same template code work for vouchers.
:::

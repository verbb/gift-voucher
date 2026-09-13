# Code
Whenever you're dealing with a code in your template, you're actually working with a `Code` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

ID of the code.
:::

::: reference
### `voucherId`

**Type:** `int|null`

The [Voucher](docs:developers/voucher) ID the code is generated for.
:::

::: reference
### `voucher`

**Type:** `verbb\giftvoucher\elements\Voucher|null`

The [Voucher](docs:developers/voucher) the code is generated for.
:::

::: reference
### `voucherType`

The voucher's type the code is generated for.
:::

::: reference
### `orderId`

**Type:** `int|null`

The [Order](https://docs.craftcms.com/commerce/api/v2/craft-commerce-elements-order.html) ID where the parent voucher was originally purchased from.
:::

::: reference
### `order`

**Type:** `craft\commerce\elements\Order|null`

The [Order](https://docs.craftcms.com/commerce/api/v2/craft-commerce-elements-order.html) where the parent voucher was originally purchased from.
:::

::: reference
### `lineItemId`

**Type:** `int|null`

The [Line Item](https://docs.craftcms.com/commerce/api/v2/craft-commerce-models-lineitem.html) ID in the order where the parent voucher was originally purchased from.
:::

::: reference
### `lineItem`

**Type:** `craft\commerce\models\LineItem|null`

The [Line Item](https://docs.craftcms.com/commerce/api/v2/craft-commerce-models-lineitem.html) in the order where the parent voucher was originally purchased from.
:::

::: reference
### `codeKey`

**Type:** `string|null`

The generated, unique code used for redeeming this amount.
:::

::: reference
### `originalAmount`

**Type:** `float|null`

When the voucher is purchased initially, the amount is stored under this value. This cannot be updated.
:::

::: reference
### `currentAmount`

**Type:** `float|null`

The current amount of the code. Because vouchers can be redeemed multiple times, this amount can be used all at once, or over a few orders depending on the value. This value will be updated with each redemption.
:::

::: reference
### `expiryDate`

**Type:** `DateTime|null`

The date this code will no longer be available for use.
:::


## Methods

::: reference
### `getCpEditUrl()`

**Returns:** `string|null`

The url to edit this code in the control panel.
:::

::: reference
### `getRedemptions()`

**Returns:** `array`

Shows a list of all redemptions for that code. This keeps track of what orders and products this code has been used against.
:::

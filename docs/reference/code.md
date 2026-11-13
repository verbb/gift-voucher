# Code
Whenever you’re dealing with a code in your template, you’re working with a `Code` object. A code is what a customer enters at checkout to spend a voucher’s value, and it’s created when a [Voucher](docs:reference/voucher) is bought or generated in the control panel. You can fetch codes with a [code query](docs:getting-elements/code-queries).

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the code.
:::

::: reference
### `voucherId`

**Type:** `int|null`

The ID of the [Voucher](docs:reference/voucher) the code was created for.
:::

::: reference
### `voucher`

**Type:** `verbb\giftvoucher\elements\Voucher|null`

The [Voucher](docs:reference/voucher) the code was created for.
:::

::: reference
### `voucherType`

**Type:** `VoucherType|null`

The voucher type of the code’s voucher.
:::

::: reference
### `orderId`

**Type:** `int|null`

The ID of the Commerce order the voucher was bought in. This is `null` for a code generated in the control panel.
:::

::: reference
### `order`

**Type:** `craft\commerce\elements\Order|null`

The Commerce order the voucher was bought in. This is `null` for a code generated in the control panel.
:::

::: reference
### `lineItemId`

**Type:** `int|null`

The ID of the Commerce line item the voucher was bought in. This is `null` for a code generated in the control panel.
:::

::: reference
### `lineItem`

**Type:** `craft\commerce\models\LineItem|null`

The Commerce line item the voucher was bought in. This is `null` for a code generated in the control panel.
:::

::: reference
### `codeKey`

**Type:** `string|null`

The unique code a customer enters to redeem the voucher’s value.
:::

::: reference
### `originalAmount`

**Type:** `float|null`

The amount the code was worth when it was created. It doesn’t change as the code is redeemed.
:::

::: reference
### `currentAmount`

**Type:** `float|null`

The unredeemed amount recorded for the code. Because vouchers can be redeemed multiple times, this amount can be used all at once or across several orders. Active checkout holds reduce the amount currently available without changing this value until the order completes.
:::

::: reference
### `expiryDate`

**Type:** `DateTime|null`

The date the code can no longer be redeemed. This is `null` when it doesn’t expire.
:::


## Methods

::: reference
### `getCpEditUrl()`

**Returns:** `string|null`

Returns the URL to edit this code in the control panel.
:::

::: reference
### `getRedemptions()`

**Returns:** `array`

Returns the redemptions of this code, each recording an amount of the code used on an order.
:::

::: reference
### `getAvailableAmount()`

**Returns:** `float`

Returns the amount available to a new checkout after active holds are deducted from `currentAmount`.
:::

::: reference
### `getHeldAmount()`

**Returns:** `float`

Returns the total value currently held by unresolved checkouts.
:::

::: reference
### `getReservations()`

**Returns:** `array`

Returns the active checkout holds for the code. A hold remains active until its order completes, an explicit decline releases it or an administrator releases it after reviewing the payment state.
:::

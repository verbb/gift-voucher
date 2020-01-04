# Code

Whenever you're dealing with a code in your template, you're actually working with a `Code` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the code.
`voucherId` | The [Voucher](docs:developer/voucher) ID the code is generated for.
`voucher` | The [Voucher](docs:developer/voucher) the code is generated for.
`voucherType` | The voucher's type the code is generated for.
`orderId` | The [Order](https://docs.craftcms.com/commerce/api/v2/craft-commerce-elements-order.html) ID where the parent voucher was originally purchased from.
`order` | The [Order](https://docs.craftcms.com/commerce/api/v2/craft-commerce-elements-order.html) where the parent voucher was originally purchased from.
`lineItemId` | The [Line Item](https://docs.craftcms.com/commerce/api/v2/craft-commerce-models-lineitem.html) ID in the order where the parent voucher was originally purchased from.
`lineItem` | The [Line Item](https://docs.craftcms.com/commerce/api/v2/craft-commerce-models-lineitem.html) in the order where the parent voucher was originally purchased from.
`codeKey` | The generated, unique code used for redeeming this amount.
`originalAmount` | When the voucher is purchased initially, the amount is stored under this value. This cannot be updated.
`currentAmount` | The current amount of the code. Because vouchers can be redeemed multiple times, this amount can be used all at once, or over a few orders depending on the value. This value will be updated with each redemption.
`expiryDate` | The date this code will no longer be available for use.

## Methods

Method | Description
--- | ---
`getCpEditUrl()` | The url to edit this code in the control panel.
`getRedemptions()` | Shows a list of all redemptions for that code. This keeps track of what orders and products this code has been used against.
# Code Model

When a customer has purchased a gift voucher, a Code Model will be automatically generated for each voucher in their cart. This model contains the unique code for other customers to use to obtain a discount on their purchases.

As such, you'll need this reference in particular when templating your [PDF template](/craft-plugins/gift-voucher/docs/template-guide/pdf-template), or showing the resulting voucher in your order summary or email templates.

## Simple Output

Outputting a `GiftVoucher_CodeModel` object in your template without attaching a property or method will simply return the generated code key.

`<h1>{{ code }}</h1>`

Voucher Models have the following attributes and methods:

## Attributes

### id

The id of the code in the system.

### voucher

The [Voucher Model](/craft-plugins/gift-voucher/docs/models/voucher-model) the code is generated for.

### voucherType

The voucher's type the code is generated for.

### order

The [Order Model](https://craftcommerce.com/docs/order-model) where the parent voucher was originally purchased from.

### lineItem

The [Line Item Model](https://craftcommerce.com/docs/line-item-model) in the order where the parent voucher was originally purchased from.

### codeKey

The generated, unique code used for redeeming this amount.

### originalAmount

When the voucher is purchased initially, the amount is stored under this value. This cannot be updated.

### currentAmount

The current amount of the code. Because vouchers can be redeemed multiple times, this amount can be used all at once, or over a few orders depending on the value. This value will be updated with each redemption.

### expiryDate

The date this code will no longer be available for use.

### manually

This value indicates if the code was manually added.

### cpEditUrl

The url to edit this code.

### orderEditUrl

The url to edit the associated order.

### redemptions

Shows a list of all redemptions for that code. This keeps track of what orders and products this code has been used against.
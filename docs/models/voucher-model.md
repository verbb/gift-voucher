# Voucher Model

When you're on a single voucher template, or looping through vouchers using `craft.giftVoucher.vouchers()`, you're actually working with a `GiftVoucher_VoucherModel`. This in turn extends Commerce's [Purchasable](https://craftcommerce.com/docs/purchasables) object.

## Simple Output

Outputting a `GiftVoucher_VoucherModel` object in your template without attaching a property or method will return the product’s name:

`<h1>{{ product }}</h1>`

Voucher Models have the following attributes and methods:

## Attributes

### title

The voucher name/title.

### id

The id of the voucher in the system.

### purchasableId

Returns this vouchers id - as vouchers are purchasables.

### type

The voucher's product type.

### typeId

The voucher's voucher type Id

### price

The listing price of the voucher.

### sku

The sku of the voucher.

### status

live, pending or expired based on postDate and expiryDate dates. Pending are vouchers with a future postDate date.

### enabled

true or false

### taxCategory

The tax category this voucher uses when their tax calculations are made.

### shippingCategory

The shipping category this voucher uses when their shipping calculations are made.

### postDate

The date this voucher is available for sale.

### expiryDate

The date this voucher will no longer be available for sale.

### cpEditUrl

The url to edit this voucher.
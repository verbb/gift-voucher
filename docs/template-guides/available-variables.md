# Available Variables

The following are common methods you will want to call in your front end templates:

### `craft.giftVoucher.vouchers()`

See [Voucher Queries](docs:getting-elements/voucher-queries)

### `craft.giftVoucher.codes()`

See [Code Queries](docs:getting-elements/code-queries)

### `craft.giftVoucher.getVoucherTypes()`

Returns all Voucher Types available.

### `craft.giftVoucher.getVoucherCodes()`

Returns any currently-applied vouchers. This occurs when a customer apply's a voucher to their cart.

### `craft.giftVoucher.isVoucher(lineItem)`

Returns whether a provided Line Item object is a gift voucher or not.

### `craft.giftVoucher.getPdfUrl(lineItem)`

Returns a URL to the PDF for this gift voucher, for the provided Line Item object. This will only show vouchers for this line item.

### `craft.giftVoucher.getOrderPdfUrl(order)`

Returns a URL to the PDF for this gift voucher, for the provided Order object. This will show vouchers for the entire order.

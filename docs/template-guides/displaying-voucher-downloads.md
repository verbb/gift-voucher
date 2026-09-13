# Displaying Voucher Downloads

Use a Commerce order already authorised for the current customer. A line-item PDF includes the vouchers for that item, while the order PDF includes the order’s vouchers. Check the line-item type before showing a voucher link.

## Calls Used in This Task

### `craft.giftVoucher.isVoucher(lineItem)`
Returns whether a provided Line Item object is a gift voucher or not.

### `craft.giftVoucher.getPdfUrl(lineItem)`
Returns a URL to the PDF for this gift voucher, for the provided Line Item object. This will only show vouchers for this line item.

### `craft.giftVoucher.getOrderPdfUrl(order)`
Returns a URL to the PDF for this gift voucher, for the provided Order object. This will show vouchers for the entire order.

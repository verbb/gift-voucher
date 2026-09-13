# Displaying Voucher Discounts

Applied voucher codes and order adjustments describe discounts on the current cart. Check the adjustment type before reading its voucher code, so other Commerce discounts are not presented as vouchers.

## Calls Used in This Task

### `craft.giftVoucher.getVoucherCodes()`
Returns any currently-applied vouchers. This occurs when a customer applies a voucher to their cart.

### `craft.giftVoucher.isVoucherAdjustment(adjustment)`
Returns whether a provided Order Adjustment object is a gift voucher discount.

### `craft.giftVoucher.getVoucherCodeKey(adjustment)`
Returns the voucher code key for a gift voucher adjustment, or `null` if the adjustment is not a gift voucher.


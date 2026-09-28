# Code Storage
When a Gift Voucher Code has been applied to a cart, and the discount taken off the cart total, the plugin requires a mechanism to track it against the current cart for the user. This is until the cart has been completed and turned into a completed order.

## Session-Based
Gift Voucher achieves this through a Code Storage service. By default, this uses session-based storage to record all applied voucher codes against the current cart. These codes are then removed when the order is complete.

Session-based storage may be unavailable when an off-site gateway returns in another request. The completed order still redeems its saved voucher adjustments, but the returning browser may no longer be able to display or remove the original code from its session.

## Order-Based
Use order-based code storage when the applied-code relationship must remain visible throughout an off-site checkout. This option requires a custom field on the order, which temporarily stores the applied voucher codes instead of relying on the browser session.

To swap the code storage Gift Voucher uses, first create a custom field (type `Gift Voucher Code`) and add it to your order field layout. This field will be used to store the voucher codes applied on the order. For this example, it should have the handle `giftVoucherCodes`, as we'll refer to that later.

Then, add a `gift-voucher.php` [config](docs:get-started/configuration) file with the following:

```php
use verbb\giftvoucher\storage\Order;

return [
    'codeStorage' => ['class' => Order::class, 'fieldHandle' => 'giftVoucherCodes'],
];
```

This uses the `giftVoucherCodes` order field for code storage. Payment holds and completed redemptions remain durable regardless of which code-storage option you choose.

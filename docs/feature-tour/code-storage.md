# Code Storage
When a Gift Voucher Code has been applied to a cart, and the discount taken off the cart total, the plugin requires a mechanism to track it against the current cart for the user. This is until the cart has been completed and turned into a completed order.

## Session-based
Gift Voucher achieves this through a Code Storage service. By default, this uses session-based storage to record all applied voucher codes against the current cart. These codes are then removed when the order is complete.

There are some scenarios where session-based code storage will cause issues however. Most commonly, for offsite payment gateways, where the user is redirected away from your site to complete payment, then redirected back. Because this redirect will change the current session for the site, the applied voucher codes against a cart will be removed.

## Order-based
For this reason, we recommend using order-based code storage. When using this storage option we require a custom field to be added to an order, which is used to temporarily save the applied voucher codes on an order, instead of a session.

To swap the code storage Gift Voucher uses, first create a custom field (type `Gift Voucher Code`) and add it to your order field layout. This field will be used to store the voucher codes applied on the order. For this example, it should have the handle `giftVoucherCodes`, as we'll refer to that later.

Then, add a `gift-voucher.php` [config](docs:get-started/configuration) file with the following:

```php
use verbb\giftvoucher\storage\Order;

return [
    'codeStorage' => ['class' => Order::class, 'fieldHandle' => 'giftVoucherCodes'],
];
```

Which will set the code storage class to be order-based, and passing in the handle to the Gift Voucher Code custom field for the order.
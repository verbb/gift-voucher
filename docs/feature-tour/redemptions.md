# Redemptions

## Partial Redemption
A voucher code contributes its available balance towards an eligible order. If the order exceeds that balance, the customer pays the difference.

If the eligible order amount is less than the balance, only that amount is redeemed. Vouchers continue to be redeemable with the remaining amount until the expiry date is reached.

## Redemption Tracking
Redemptions are also tracked every time a voucher code is used in an order. A record with the associated code, order and amount is created, which can be viewed from a single voucher code page in the control panel.

## Checkout Holds

Gift Voucher holds the applied value when an order starts payment. The hold prevents another checkout from spending the same value while a direct or off-site payment is still being resolved. When the order completes, the hold becomes a redemption. An explicit declined payment releases the hold so the value becomes available again.

A missing callback, network error or abandoned browser does not prove that a payment failed, so these holds do not expire automatically. Open the voucher code in the control panel to review its active holds. A completed order can retry redemption from this screen. Release a hold manually only after checking the related Commerce transaction and confirming that it cannot still succeed.

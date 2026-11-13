# Displaying Voucher Discounts

Applied voucher codes and order adjustments describe discounts on the current cart. Check the adjustment type before reading its voucher code, so other Commerce discounts are not presented as vouchers.

Once a customer has [redeemed a voucher code](docs:template-guides/redeeming-voucher-codes), the voucher's value is applied to their cart as a discount adjustment. This example belongs in your cart template, where Commerce provides the current cart as `cart`. It lists the cart's adjustments and adds a remove button for each voucher discount.

```twig
{% for adjustment in cart.adjustments %}
    <p>
        {{ adjustment.name }}, {{ adjustment.amount|commerceCurrency(cart.currency) }}

        {% if craft.giftVoucher.isVoucherAdjustment(adjustment) %}
            {% set codeKey = craft.giftVoucher.getVoucherCodeKey(adjustment) %}

            <form method="post">
                {{ csrfInput() }}
                {{ actionInput('gift-voucher/cart/remove-code') }}
                {{ redirectInput('shop/cart') }}
                {{ hiddenInput('voucherCode', codeKey) }}

                <button type="submit">Remove {{ codeKey }}</button>
            </form>
        {% endif %}
    </p>
{% endfor %}
```

A voucher adjustment's `name` is the voucher's title, and its `description` reads "Gift Voucher discount using code" followed by the code. Coupon and sale discounts appear in the same list without a remove button.

## Listing Applied Voucher Codes

`craft.giftVoucher.getVoucherCodes()` returns an array of the voucher codes, as strings, that the customer has applied to their current cart. It takes no arguments and always reads the current cart, so use it on cart and checkout pages rather than for a completed order. The array is empty when no codes are applied.

```twig
{% set appliedCodes = craft.giftVoucher.getVoucherCodes() %}

{% if appliedCodes is not empty %}
    <p>Vouchers applied: {{ appliedCodes|join(', ') }}</p>
{% endif %}
```

[Removing Vouchers](docs:template-guides/redeeming-voucher-codes#removing-vouchers) shows how to pair each code with a remove form.

## Identifying Voucher Discounts

`craft.giftVoucher.isVoucherAdjustment(adjustment)` returns `true` when the given Commerce order adjustment is a gift voucher discount, and `false` for any other adjustment, such as a coupon, tax or shipping. `adjustment` is required. Use it inside a loop over `cart.adjustments` or `order.adjustments`.

`craft.giftVoucher.getVoucherCodeKey(adjustment)` returns the voucher code that a gift voucher adjustment was created from, or `null` if the adjustment isn't a gift voucher discount. `adjustment` is required. Use it to show which code a discount came from, or to fill the `voucherCode` value of a remove form.

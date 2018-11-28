# Redeeming Voucher Codes

When a customer is provided a gift voucher code, they'll obviously want to be able to use them on your store. The best way for them to enter this code is through a form at your cart.

Use the following template code and add it to your cart page (ideally near the coupon form if you're using that).

```twig
{% if cart.getFirstError('voucherCode') %}
    <span class="flash">{{ cart.getFirstError('voucherCode') }}</span>
{% endif %}

<form method="POST">
    <input type="hidden" name="action" value="gift-voucher/cart/add-code">
    {{ redirectInput('shop/cart') }}
    {{ csrfInput() }}

    <span class="{% if cart.getFirstError('voucherCode') %}has-error{% endif %}">
        <input type="text" name="voucherCode" width="11" class="{% if cart.getFirstError('voucherCode') %}has-error{% endif %}" value="{{ craft.giftVoucher.getVoucherCode }}" placeholder="Voucher Code"/>
    </span>

    <input type="submit" class="button" value="{% if craft.giftVoucher.getVoucherCode %}Change{% else %}Apply{% endif %} Voucher"/>
</form>
```

If successful in entering a valid gift voucher code, the users' cart will update, reflecting the discount added to their cart. From this point onwards, its treated as a [discount adjuster](https://docs.craftcms.com/commerce/v2/adjusters.html).

### Removing Vouchers

Gift Vouchers support for multiple vouchers needs a way to remove already entered voucher codes. In our cart template (`shop/cart.html` for example), we have the following code looping through the adjustment items for the order.

```twig
{% for code in craft.giftVoucher.getVoucherCodes() %}
    <form method="POST">
        <input type="hidden" name="action" value="gift-voucher/cart/remove-code">
        {{ redirectInput('shop/cart') }}
        {{ csrfInput() }}
        
        <input type="text" name="voucherCode" width="11" value="{{ code }}">

        <input type="submit" class="button" value="Remove Voucher">
    </form>
{% endfor %}
```

### Combining Coupons and Vouchers

Because they're often considered similar in the eyes of customers, you can use the same field in your templates to handle either a gift voucher, or a coupon code. Just use the previous examples, and it'll automatically check if the provided text is a coupon code, and if so, try and apply it against the order.

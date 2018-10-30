# Redeeming Voucher Codes

When a customer is provided a gift voucher code, they'll obviously want to be able to use them on your store. The best way for them to enter this code is through a form at your cart.

Use the following template code and add it to your cart page (ideally near the coupon form if you're using that).

```twig
{% if cart.getError('voucherCode') %}
    <span class="flash">{{ cart.getError('voucherCode') }}</span>
{% endif %}

<form method="POST">
    <input type="hidden" name="action" value="giftVoucher/cart/addCode">
    <input type="hidden" name="redirect" value="shop/cart">
    {{ getCsrfInput() }}

    <span class="{% if cart.getError('voucherCode') %}has-error{% endif %}">
        <input type="text" name="giftVoucherCode" width="11"
               class="{% if cart.getError('voucherCode') %}has-error{% endif %}"
               value="{{ craft.giftVoucher.getVoucherCode }}"
               placeholder="{{ "Voucher Code"|t }}"/>
    </span>

    <input type="submit" class="button" value="{% if craft.giftVoucher.getVoucherCode %}Change{% else %}Apply{% endif %} Voucher"/>
</form>
```

If successful in entering a valid gift voucher code, the users' cart will update, reflecting the discount added to their cart. From this point onwards, its treated as a [discount adjuster](https://craftcommerce.com/docs/adjusters).

### Removing Vouchers

Gift Vouchers support for multiple vouchers needs a way to remove already entered voucher codes. In our cart template (`shop/cart.html` for example), we have the following code looping through the adjustment items for the order.

```twig
{% if craft.giftVoucher.isVoucherAdjustment(adjustment) %}
    <form method="POST">
        <input type="hidden" name="action" value="giftVoucher/cart/removeCode"/>
        <input type="hidden" name="redirect" value="shop/cart"/>
        <input type="hidden" name="giftVoucherCode" value="{{ adjustment.optionsJson.code }}"/>
        {{ getCsrfInput() }}
        <tr>
            <td>
                {{ adjustment.type }}<br/>
                <input class="button link" type="submit" value="Remove"/>
            </td>
            <td><strong>{{ adjustment.name }}</strong><br>({{ adjustment.description }})</td>
            <td class="text-right">
                {{ adjustment.amount|commerceCurrency(cart.currency) }}
            </td>
        </tr>
    </form>
{% else %}
    <tr>
        <td>{{ adjustment.type }}</td>
        <td><strong>{{ adjustment.name }}</strong><br>({{ adjustment.description }})</td>
        <td class="text-right">
            {{ adjustment.amount|commerceCurrency(cart.currency) }}
        </td>
    </tr>
{% endif %}
```

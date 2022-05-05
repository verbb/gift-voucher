# Displaying Voucher Codes
Once a user has purchased a gift voucher, it's important to actually provide the voucher code for them to pass on to the required party. For example, on our order summary template (`shop/customer/order.html` for example), we have the following code looping through line items for the order.

```twig
{% if craft.giftVoucher.isVoucher(item) %}
    {% for code in item.purchasable.getCodes(item) %}
        Code: {{ code }}<br />
    {% endfor %}
{% endif %}
```

:::tip
Because multiple vouchers can be purchased for one line item, it's important to loop through potentially multiple unique voucher codes as above.
:::

If you're looking for something a little prettier than simply showing the code in your order confirmation page or email, you may want to look at generating a [PDF Template](docs:template-guides/pdf-template).
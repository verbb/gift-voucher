# Custom Amounts

You can enable a custom amount for vouchers, which allows the customer purchasing the voucher to set its value. Enable the **Custom amount?** option in the control panel for the voucher you want to support this.

You'll also need to update your front-end templates to allow customers to enter an amount when adding the voucher to their cart:

```twig
{% if voucher.customAmount %}
    <input type="text" name="options[amount]" placeholder="Amount">
{% endif %}
```

For a full single voucher template example, see the [Single Voucher](docs:template-guides/single-voucher) guide.

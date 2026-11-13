# Finding Vouchers

Use voucher and code queries to select the records needed by your template. A voucher defines the product, and a code identifies a redeemable voucher. Follow the linked query guides for filters and returned objects.

For example, a gift shop page might list every voucher customers can buy, grouped by voucher type.

```twig
{% for voucherType in craft.giftVoucher.getVoucherTypes() %}
    <h2>{{ voucherType.name }}</h2>

    {% set vouchers = craft.giftVoucher.vouchers()
        .type(voucherType)
        .availableForPurchase(true)
        .all() %}

    {% for voucher in vouchers %}
        <a href="{{ voucher.url }}">{{ voucher.title }}</a>
    {% endfor %}
{% endfor %}
```

## Querying Vouchers

`craft.giftVoucher.vouchers(criteria)` returns a [voucher query](docs:getting-elements/voucher-queries) for the vouchers you sell, such as on a [vouchers index](docs:template-guides/vouchers-index). Refine it with parameters and run it with `.all()`, `.one()` or `.count()`. `criteria` is an optional hash of parameters, so `craft.giftVoucher.vouchers({ type: 'christmas' })` is the same as `craft.giftVoucher.vouchers().type('christmas')`.

## Querying Voucher Codes

`craft.giftVoucher.codes(criteria)` returns a [code query](docs:getting-elements/code-queries) for the codes created when customers buy vouchers. `criteria` is an optional hash of parameters.

A code is what a customer redeems at checkout, so treat it like a password. The query isn't limited to the current customer, so filter it by an order the customer is allowed to see, and never show the results of an unfiltered query on the front end.

```twig
{# `order` is the current customer's completed order #}
{% set codes = craft.giftVoucher.codes()
    .orderId(order.id)
    .all() %}

{% for code in codes %}
    <p>{{ code.codeKey }}, {{ code.currentAmount|commerceCurrency(order.currency) }} remaining</p>
{% endfor %}
```

## Listing Voucher Types

`craft.giftVoucher.getVoucherTypes()` returns an array of every voucher type. It takes no arguments. Each voucher type has a `name`, `handle` and `id`, which you can pass to a voucher query's `type` or `typeId` parameter.

`craft.giftVoucher.getEditableVoucherTypes()` returns only the voucher types the signed-in user has permission to manage. It takes no arguments, and returns an empty array for guests. Use it for a front-end form where staff create vouchers, so the type choices match what they're allowed to save.

```twig
{% if currentUser %}
    <select name="typeId">
        {% for voucherType in craft.giftVoucher.getEditableVoucherTypes() %}
            <option value="{{ voucherType.id }}">{{ voucherType.name }}</option>
        {% endfor %}
    </select>
{% endif %}
```

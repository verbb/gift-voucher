# Single Voucher

Once you've got a list of vouchers, you'll want to allow your customers to drill-into a single voucher page for more detail. This takes many cues from the single Product page for Commerce.

You'll have access to a `voucher` variable, which represents the single voucher you're looking at. You can also interchangeably use `product` if you wish.

```twig
{% extends 'shop/_layouts/main' %}
{% block main %}

<div class="row product-details">
    <div class="six columns">
        <h1>{{ voucher.title }}</h1>
    </div>
    <div class="six columns">
        <form method="POST">
            <input type="hidden" name="action" value="commerce/cart/update-cart">
            <input type="hidden" name="qty" value="1">
            {{ redirectInput('shop/cart') }}
            {{ csrfInput() }}

            <input type="hidden" name="purchasableId" value="{{ voucher.purchasableId }}">

            {# Use custom amounts #}
            {% if voucher.customAmount %}
                <input type="text" name="options[amount]" placeholder="Amount">
            {% endif %}

            <input type="submit" value="Add to cart" class="button"/>
        </form>

        <p><a href="{{ url('shop/vouchers') }}">&larr; Back to all vouchers.</a></p>
    </div>
</div>

{% endblock %}
```

# Voucher Template

Create a file named `index.html` file in your `templates/shop/products/gift-voucher` folder. This folder may vary depending on your chosen site structure. Enter the content as below:

:::tip
We're just using the default Commerce templates here, so change this to your needs.
:::

```twig
{% extends 'shop/_layouts/main' %}
{% block main %}

{% for voucher in craft.giftVoucher.vouchers().limit(5).all() %}
    <div class="row product">
        <div class="two columns">
            {% include "shop/_images/product" with { class: 'u-max-full-width' } %}
        </div>
        <div class="ten columns">
            <h5>
                {% if voucher.url %}
                    {{ voucher.link }}
                {% else %}
                    {{ voucher.title }}
                {% endif %}
            </h5>
            <form method="POST">
                <input type="hidden" name="action" value="commerce/cart/update-cart">
                <input type="hidden" name="purchasableId" value="{{ voucher.purchasableId }}">
                <input type="hidden" name="qty" value="1">
                {{ redirectInput('shop/cart') }}
                {{ csrfInput() }}

                {# Use open amounts #}
                {% if voucher.customAmount %}
                    <input type="text" name="options[amount]" placeholder="Amount">
                {% endif %}

                <button type="submit">Add to cart</button>
            </form>
        </div>
    </div>
{% endfor %}

{% endblock %}
```

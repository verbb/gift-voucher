# Fetching Vouchers

Because vouchers are a custom [purchasable](https://craftcommerce.com/docs/purchasables), they won't automatically appear on your shop without a bit of templating. Essentially, this is because they exist outside of the native Commerce products/variants. Fortunately, implementing these templates are straightforward, and you'll find it quite similar to Commerce.

You can display a list of all vouchers via the following template snippet:

```twig
{% for voucher in craft.giftVoucher.vouchers.find() %}
```

Like [Variants](https://craftcommerce.com/docs/variant-model), vouchers are elements, meaning you have access to familiar querying via the [Craft Element Criteria Model](https://craftcms.com/docs/templating/elementcriteriamodel). For instance you can limit vouchers via `limit()`.

```twig
{% for voucher in craft.giftVoucher.vouchers.limit(5).find() %}
```

Or select vouchers of a specific `type`.

```twig
{% for voucher in craft.giftVoucher.vouchers.type('xmas').find() %}
```

### Templates

Create a file named `index.html` file in your `templates/shop/products/gift-voucher` folder. This folder may vary depending on your chosen site structure. Enter the content as below:

:::tip
We're just using the default Commerce templates here, so change this to your needs.
:::

```twig
{% extends 'shop/_layouts/main' %}
{% block main %}

{% for voucher in craft.giftVoucher.vouchers.limit(5).find() %}
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
                <input type="hidden" name="action" value="commerce/cart/updateCart">
                <input type="hidden" name="redirect" value="shop/cart">
                <input type="hidden" name="purchasableId" value="{{ voucher.purchasableId }}">
                <input type="hidden" name="qty" value="1">
                {{ getCsrfInput() }}

                {# Use open amounts #}
                {% if voucher.customAmount %}
                    <input type="text" name="options[amount]" placeholder="Amount">
                {% endif %}

                <button type="submit">{{ "Add to cart"|t }}</button>
            </form>
        </div>
    </div>
{% endfor %}

{% endblock %}
```

# Vouchers Index
Create a file named `index.html` file in your `templates/shop/products/gift-voucher` folder. This folder may vary depending on your chosen site structure. Enter the content as below:

:::tip
We're just using the default Commerce templates here, so change this to your needs.
:::

```twig
{% extends 'shop/_layouts/main' %}

{% block main %}
    {% for voucher in craft.giftVoucher.vouchers().limit(5).all() %}
        <div class="md:flex product bg-white mb-4 p-8 rounded items-center text-center md:text-left">
            <div class="md:w-2/6 md:p-4">
                <h3>{% if voucher.url %}{{ voucher.link }}{% else %}{{ voucher.title }}{% endif %}</h3>

                <form method="POST" class="add-to-cart-form">
                    <input type="hidden" name="action" value="commerce/cart/update-cart">
                    <input type="hidden" name="purchasableId" value="{{ voucher.purchasableId }}">
                    {{ redirectInput('shop/cart') }}
                    {{ csrfInput() }}

                    <input type="number" name="qty" value="1">

                    <button type="submit">Add to cart</button>
                </form>
            </div>
        </div>
    {% endfor %}
{% endblock %}
```

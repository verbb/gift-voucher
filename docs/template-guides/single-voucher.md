# Single Voucher
Once you've got a list of vouchers, you'll want to allow your customers to drill-into a single voucher page for more detail. This takes many cues from the single Product page for Commerce.

You'll have access to a `voucher` variable, which represents the single voucher you're looking at. You can also interchangeably use `product` if you wish.

```twig
{% extends 'shop/_layouts/main' %}

{% block main %}
    <div class="mt-8">
        <a href="{{ url('shop/products') }}">&larr; All products</a>
    </div>

    <div class="flex -mx-6 mt-8 product-details">
        <div class="w-1/2 mx-6 p-8">

        </div>
        <div class="w-1/2 mx-6 p-8">
            <h1>{{ voucher.title }}</h1>

            <form method="POST">
                <input type="hidden" name="action" value="commerce/cart/update-cart">
                <input type="hidden" name="purchasableId" value="{{ voucher.purchasableId }}">
                {{ redirectInput('shop/cart') }}
                {{ csrfInput() }}

                <input type="number" name="qty" value="1">

                <div class="buttons">
                    <input type="submit" value="Add to cart" class="button"/>
                </div>
            </form>
        </div>
    </div>
{% endblock %}
```

## Adding vouchers to your cart
Adding a voucher to your cart works in very much the same way as [Craft Commerce](https://docs.craftcms.com/commerce/v3/adding-to-and-updating-the-cart.html):

```twig
<form method="POST">
    <input type="hidden" name="action" value="commerce/cart/update-cart">
    <input type="hidden" name="purchasableId" value="{{ voucher.purchasableId }}">
    {{ redirectInput('shop/cart') }}
    {{ csrfInput() }}

    <input type="number" name="qty" value="1">

    <input type="submit" value="Add to cart" class="button">
</form>
```

### Setting a custom amount
You can also allow your customers to set a custom amount to purchase on their gift voucher. Be sure to check the `Custom amount?` option in the control panel for the voucher you want to enable this on. 

```twig
{% if voucher.customAmount %}
    <input type="text" name="options[amount]" placeholder="Amount">
{% endif %}
```

### Line item options
You can also set additional data through [line item options](https://docs.craftcms.com/commerce/v3/adding-to-and-updating-the-cart.html#line-item-options-and-notes). These values can be whatever you like, and very flexible.

```twig
<form method="POST">
    <input type="hidden" name="action" value="commerce/cart/update-cart">
    <input type="hidden" name="purchasableId" value="{{ voucher.purchasableId }}">
    {{ redirectInput('shop/cart') }}
    {{ csrfInput() }}

    <input type="number" name="qty" value="1">

    <input type="text" name="options[message]" value="Happy Birthday!">
    <input type="text" name="options[from]" value="Josh Crawford">
    <input type="text" name="options[to]" value="Bec Crawford">

    <input type="submit" value="Add to cart" class="button">
</form>
```

If you have any custom fields' setup in your Gift Voucher settings for Codes, you can also set content on those fields through your line item options, and they'll be automatically 'pushed' to the resulting [Code](docs:developers/code) generated at the end of checkout.

For example, let's say you have a 'Message' plain text field in your Code Field Layout, with the handle `message`. In this instance, you want to include additional information such as a personal message to be show on the PDF voucher to send to the voucher recipient. You include this field in your add-to-cart form:

```twig
<form method="POST">
    <input type="hidden" name="action" value="commerce/cart/update-cart">
    {{ redirectInput('shop/cart') }}
    {{ csrfInput() }}

    ...

    <input type="text" name="options[message]" value="Please use this to buy something awesome!">

    <input type="submit" value="Add to cart" class="button">
</form>
```

You can also automate this to show _all_ the custom fields assigned to the Code field layout.

```twig
<form method="POST">
    <input type="hidden" name="action" value="commerce/cart/update-cart">
    {{ redirectInput('shop/cart') }}
    {{ csrfInput() }}

    ...

    {% set codeFieldLayout = craft.app.fields.getLayoutByType('verbb\\giftvoucher\\elements\\Code') %}

    {% for field in codeFieldLayout.getFields() %}
        <label>{{ field.name }}</label>
        <input type="text" name="options[{{ field.handle }}]" value="">
    {% endfor %}

    <input type="submit" value="Add to cart" class="button">
</form>
```

After checkout has been completed, any line item option that matches a custom field on your Code field type settings will be copied across to the resulting [Code](docs:developers/code). You'll be able to easily see the values as provided in the control panel, when viewing your Voucher Codes.


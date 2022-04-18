# Displaying in your Cart
To show vouchers in your cart, you may want to treat them differently to other products.

```twig
<form method="post" action="">
    {{ actionInput('commerce/cart/update-cart') }}
    {{ csrfInput() }}

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            {% for item in cart.lineItems %}
                <tr>
                    <td>
                        {{ item.description }}: {{ item.sku }}
                    </td>
                    <td>
                        <input type="number" name="lineItems[{{ item.id }}][qty]" min="0" value="{{ item.qty }}">
                    </td>
                    <td>
                        Price: {{ item.price | commerceCurrency(cart.currency) }}
                    </td>
                </tr>
            {% endfor %}
        </tbody>
    </table>
</form>
```

The above shows a very simplified output of your cart items. To add special handling for Gift Vouchers, we can check if a line item is a Gift Voucher or not.

```twig
{% for item in cart.lineItems %}
    <tr>
        <td>
            {{ item.description }}: {{ item.sku }}

            {% if craft.giftVoucher.isVoucher(item) %}
                {# Maybe output the custom message we added to the voucher #}
                To: {{ item.options.to ?? '' }}

                {# Or, check if they added a custom price #}
                Price: {{ item.options.amount ?? item.price ?? '0' }}<br>
            {% endif %}
        </td>
    </tr>
{% endfor %}
```

Here, we're checking if the line item is a Gift Voucher, and then showing some content specifically for Gift Vouchers.

## Using Custom Amounts
If you are using custom amounts for your Gift Vouchers (users can set their own price), you'll need to be mindful when updating the cart. What can happen is the custom amount can be discarded, if you are updating other line item options at the same time.

For example, let's say we want to allow users to modify the "To" line item option on the cart:

```twig
{% for item in cart.lineItems %}
    <tr>
        <td>
            {{ item.description }}: {{ item.sku }}

            {% if craft.giftVoucher.isVoucher(item) %}
                <input type="text" name="lineItems[{{ item.id }}][options][to]" value="{{ item.options.to ?? '' }}">
            {% endif %}
        </td>
    </tr>
{% endfor %}
```

If we update the cart with _just_ this line item option, we will loose the `amount` line item option. So, the easiest method is to include it again. You can use a `text`, `number` or `hidden` input, depending on your needs.

```twig
{% for item in cart.lineItems %}
    <tr>
        <td>
            {{ item.description }}: {{ item.sku }}

            {% if craft.giftVoucher.isVoucher(item) %}
                {# Allow users to update the "To" value #}
                <input type="text" name="lineItems[{{ item.id }}][options][to]" value="{{ item.options.to ?? '' }}">

                {# Include the custom amount, if one is set and the voucher is a custom-priced one #}
                {% if item.purchasable.customAmount %}
                    <input type="hidden" name="lineItems[{{ item.id }}][options][amount]" value="{{ item.options.amount ?? '' }}">
                {% endif %}
            {% endif %}
        </td>
    </tr>
{% endfor %}
```

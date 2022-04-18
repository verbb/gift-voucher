# Auto Redeem Voucher Code
You might like to set up a particular URL on your site for the customer to visit, and have their gift voucher automatically redeemed (applied) to their cart. Commonly, this might be a URL in an email marketing mailout, or a link directly from their account.

You can facilitate this by creating a template that fetches the voucher code from the URL and applies it on the users' current cart.

```twig
{# Set the URL you want to redirect users to afterwards #}
{% set returnUrl = '/' %}

{# Look for a required query param. Feel free to rename this to your needs #}
{% set voucherCode = craft.app.request.getParam('voucherCode') %}

{% if not voucherCode %}
    {% redirect returnUrl %}
{% endif %}

{# Check to see if this voucher code is valid #}
{% set success = craft.giftVoucher.getPlugin().getCodes().matchCode(voucherCode, '') %}

{% if success %}
    {% set cart = craft.commerce.carts.cart %}

    {# Apply the voucher code on the cart #}
    {% do craft.giftVoucher.getPlugin().getCodeStorage().add(voucherCode, cart) %}

    {# Update the cart #}
    {% do craft.app.getElements().saveElement(cart, false) %}

    {% redirect returnUrl %}
{% else %}
    {# Depending on how you want to handle errors... #}
    {% redirect returnUrl %}
{% endif %}
```

The above shows an example template. For this example, let's say we've created this in `/templates/apply-voucher`, resulting in the URL `https://yoursite.craft/apply-voucher?voucherCode=XXXXXXX`. You'll notice we're supplying the voucher code as a `voucherCode` query param.

Stepping through the code, we first set a URL we want to redirect to, if all goes well. Then, check for the `voucherCode` query param in the URL, which we should check is actually provided.

Then, we use Gift Voucher's `matchCode` function to check the validity of the provided code. If all goes well, we apply the voucher to the cart using `add()` and then update the cart to reflect the discount. Finally, the user is redirected to the URL you specify.
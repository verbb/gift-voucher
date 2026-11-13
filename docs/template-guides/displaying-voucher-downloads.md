# Displaying Voucher Downloads

Use a Commerce order already authorised for the current customer. A line-item PDF includes the vouchers for that item, while the order PDF includes the order’s vouchers. Check the line-item type before showing a voucher link.

The download links contain the order's number, which is what gives access to the PDF, so only show them to the customer who placed the order, such as on the order confirmation page, in their account area or in an order email. The PDF itself is rendered from your [PDF template](docs:template-guides/pdf-template).

This example belongs on an order confirmation page, where Commerce provides the completed order as `order`.

```twig
<a href="{{ craft.giftVoucher.getOrderPdfUrl(order) }}">Download all vouchers</a>

<ul>
    {% for lineItem in order.lineItems %}
        {% if craft.giftVoucher.isVoucher(lineItem) %}
            <li>
                {{ lineItem.description }}
                <a href="{{ craft.giftVoucher.getPdfUrl(lineItem) }}">Download</a>
            </li>
        {% endif %}
    {% endfor %}
</ul>
```

The customer sees one link for every voucher in the order, followed by a link for each voucher line item. If an order also contains other products, only the voucher line items get a link.

## Checking for Vouchers

`craft.giftVoucher.isVoucher(lineItem)` returns `true` when the given Commerce line item is for a gift voucher, and `false` for any other purchasable or a line item whose purchasable no longer exists. `lineItem` is required. Use it inside a loop over `order.lineItems` or `cart.lineItems` to treat vouchers differently from other products.

## Linking to Voucher PDFs

`craft.giftVoucher.getPdfUrl(lineItem)` returns the URL of a PDF containing only the vouchers for the given line item, or `null` if the line item isn't a gift voucher. `lineItem` is required.

`craft.giftVoucher.getOrderPdfUrl(order)` returns the URL of a PDF containing every voucher in the given order. `order` is required. A URL is returned for any order, so only show the link when the order contains vouchers, for example by checking each line item with `craft.giftVoucher.isVoucher()`.

## Linking to a Single Code's PDF

`craft.giftVoucher.getPdfUrlForCode(code, option)` returns the URL of a PDF containing just one voucher code, such as one listed with `craft.giftVoucher.codes()`. `code` is required. `option` is optional, and is passed to your [PDF template](docs:template-guides/pdf-template) as `option` so the template can vary its output. `code.getPdfUrl()` returns the same URL.

The link identifies the code by its UID, so treat it like the code itself and only show it to the customer who bought it.

```twig
{# `order` is the current customer's completed order #}
{% for code in craft.giftVoucher.codes().orderId(order.id).all() %}
    <a href="{{ craft.giftVoucher.getPdfUrlForCode(code) }}">Download {{ code.codeKey }}</a>
{% endfor %}
```

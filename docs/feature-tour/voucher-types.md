# Voucher Types
Voucher types act to group your gift vouchers, and depending on your needs, you may only require a single voucher type. This is where you also define the templates and URL structure used by vouchers. Custom fields can be added to voucher types, providing each individual voucher product additional fields.

Go to the main section for Gift Voucher in your control panel main menu, and select **Voucher Types**. This will list all the voucher types you've created.

## Create a Voucher Type
Choose a name and stable handle first, then decide whether vouchers of this type need their own public pages.

- **Name** - What this voucher type will be called in the control panel.
- **Handle** - How you’ll refer to this voucher type in the templates.
- **Automatic SKU Format** - What the unique auto-generated SKUs should look like, when a SKU field is submitted without a value. You can include tags that output properties, such as `{slug}` or `{myCustomField}`

If you ticked **Vouchers of this type have their own URLs**, the following fields appear:

- **Voucher URL Format** - What the voucher URLs should look like. You can include tags that output voucher properties, such as `{slug}` or `{publishDate|date("Y")}`.
- **Voucher Template** - The template to use when a voucher’s URL is requested.

Be sure to check out our [Template Guide →](docs:template-guides/single-voucher) to get started quickly to show vouchers.
## Follow a Voucher Through a Purchase

For example, create a Gift Cards type and a voucher product worth 100 in your store's currency. The type defines shared fields and page settings; the voucher is the product customers purchase. Display it using the [Single Voucher template](docs:template-guides/single-voucher), add it to a cart and complete a test checkout.

Inspect the resulting [voucher code](docs:feature-tour/voucher-codes). The code carries the purchased value and is what its recipient redeems; it is distinct from the product another customer can still buy. If you use [PDF vouchers](docs:feature-tour/pdf-vouchers), check the delivered or downloaded PDF for that code and the expected value.

Redeem the code against an eligible order totalling 30, following [Redeeming Voucher Codes](docs:template-guides/redeeming-voucher-codes), and complete checkout. Inspect the code's redemption history and remaining balance: with 30 redeemed from 100, 70 remains. Test a second purchase with the same code to check partial redemption, and review [shipping treatment](docs:feature-tour/shipping-categories) if shipping changes the eligible amount.

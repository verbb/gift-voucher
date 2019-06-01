# Voucher Types

Voucher types act to group your gift vouchers, and depending on your needs, you may only require a single voucher type. This is where you also define the templates and URL structure used by vouchers. Custom fields can be added to voucher types, providing each individual voucher product additional fields.

Go to the main section for Gift Voucher in your control panel main menu, and select **Voucher Types**. This will list all the voucher types you've created.

![Voucher Types Overview](/docs/screenshots/voucher-types-overview.png)

### Create a Voucher Type

Each field is fairly self-explanatory, but any additional information is provided below.

![Voucher Types Edit](/docs/screenshots/voucher-types-edit.png)

- **Name** - What this voucher type will be called in the control panel.
- **Handle** - How you’ll refer to this voucher type in the templates.
- **Automatic SKU Format** - What the unique auto-generated SKUs should look like, when a SKU field is submitted without a value. You can include tags that output properties, such as `{slug}` or `{myCustomField}`

If you ticked **Vouchers of this type have their own URLs**, the following fields appear:

- **Voucher URL Format** - What the voucher URLs should look like. You can include tags that output voucher properties, such as such as `{slug}` or `{publishDate|date("Y")}`.
- **Voucher Template** - The template to use when a voucher’s URL is requested.

Be sure to check out our [Template Guide →](docs:template-guides/) to get started quickly to show vouchers.
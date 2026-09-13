# Configuration

You can customise Gift Voucher’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `gift-voucher.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will generate voucher code keys with 12 characters:

```php
<?php

return [
    'codeKeyLength' => 12,
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `expiry`

**Type:** `int` · **Default:** `0`

Set a default expiry (in months). 0 to disable.
:::


::: reference
### `codeKeyLength`

**Type:** `int` · **Default:** `10`

Set the number of characters for generated codes to be.
:::


::: reference
### `codeKeyCharacters`

**Type:** `string` · **Default:** `'ACDEFGHJKLMNPQRTUVWXYZ234679'`

Supply valid characters to be used in code generation.
:::


::: reference
### `voucherCodesPdfPath`

**Type:** `string` · **Default:** `'shop/_pdf/voucher'`

Set the path to your PDF.
:::


::: reference
### `voucherCodesPdfFilenameFormat`

**Type:** `string` · **Default:** `'Voucher-{number}'`

Set the default PDF filename format.
:::


::: reference
### `pdfAllowRemoteImages`

**Type:** `bool` · **Default:** `false`

Whether to allow remote images in the PDF.
:::


::: reference
### `pdfPaperSize`

**Type:** `string` · **Default:** `'letter'`

Sets the paper size for the PDF.
:::


::: reference
### `pdfPaperOrientation`

**Type:** `string` · **Default:** `'portrait'`

Sets the paper orientation for the PDF.
:::


::: reference
### `registerAdjuster`

**Type:** `string` · **Default:** `'beforeTax'`

Controls when the adjuster should be applied. Valid options are `beforeTax` (default) and `afterTax`.
:::


::: reference
### `includeShipping`

**Type:** `bool` · **Default:** `true`

Whether shipping costs should be included in voucher redemption.
:::


::: reference
### `attachPdfToEmails`

**Type:** `array` · **Default:** `[]`

A collection of Commerce Email UIDs that Gift Voucher should automatically attach the voucher PDF to.

To populate the `attachPdfToEmails` setting, you'll need the UIDs of Commerce Emails. To determine these, create your Commerce emails, then look up the `commerce_emails` table in your database. Use the values in the `uid` column in an array, passing `true/false` for whether the PDF should be attached to the email. For example:

```php
'attachPdfToEmails' => [
    'a27c0c16-71c8-422f-a09f-094264876319' => false,
    'd0b374ad-4394-48ea-8ecb-f0efc0bfeec5' => true,
],
```

:::

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Gift Voucher.

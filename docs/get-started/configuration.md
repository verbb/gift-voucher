# Configuration

Create an `gift-voucher.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'expiry' => 0,
        'codeKeyLength' => 10,
        'codeKeyCharacters' => 'ACDEFGHJKLMNPQRTUVWXYZ234679',
        'voucherCodesPdfPath' => 'shop/_pdf/voucher',
        'voucherCodesPdfFilenameFormat' => 'Voucher-{number}',
        'pdfAllowRemoteImages' => false,
        'pdfPaperSize' => 'letter',
        'pdfPaperOrientation' => 'portrait',
        'registerAdjuster' => 'beforeTax',
        'attachPdfToEmails' => [],
    ]
];
```

### Configuration options

- `expiry` - Set a default expiry (in months). 0 to disable.
- `codeKeyLength` - Set the number of characters for generated codes to be.
- `codeKeyCharacters` - Supply valid characters to be used in code generation.
- `voucherCodesPdfPath` - Set the path to your PDF.
- `voucherCodesPdfFilenameFormat` - Set the defaulf PDF filename format.
- `pdfAllowRemoteImages` - Whether to allow remote images in the PDF.
- `pdfPaperSize` - Sets the paper size for the PDF.
- `pdfPaperOrientation` - Sets the paper orientation for the PDF.
- `registerAdjuster` - Controls when the adjuster should be applied. Valid options are `beforeTax` (default) and `afterTax`.
- `attachPdfToEmails` - A collection of Commerce Email UIDs that Gift Voucher should automatically attach the voucher PDF to.

#### `attachPdfToEmails`
To populate the `attachPdfToEmails` setting, you'll need the UIDs of Commerce Emails. To determine these, create your Commerce emails, then look up the `commerce_emails` table in your database. Use the values in the `uid` column in an array, passing `true/false` as to whether the PDF should be attached to the email. For example:

```php
'attachPdfToEmails' => [
    'a27c0c16-71c8-422f-a09f-094264876319' => false,
    'd0b374ad-4394-48ea-8ecb-f0efc0bfeec5' => true,
],
```

## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Gift Voucher.

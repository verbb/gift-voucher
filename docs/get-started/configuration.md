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
    ]
];
```

### Configuration options

- `expiry` - Set a default expiry (in months). 0 to disable.
- `codeKeyLength` - Set the number of characters for generated codes to be.
- `codeKeyCharacters` - Supply valid characters to be used in code generation.
- `voucherCodesPdfPath` - Set the path to your PDF.
- `voucherCodesPdfFilenameFormat` - Set the defaulf PDF filename format.

## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Gift Voucher.

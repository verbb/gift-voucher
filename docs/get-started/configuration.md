# Configuration

Create an `gift-voucher.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        // 'letter', 'legal', 'A4', etc.
        'pdfPaperSize' => 'letter',

        // 'portrait' or 'landscape'
        'pdfPaperOrientation' => 'portrait',

        // true|false
        'pdfAllowRemoteImages' => true,
    ]
];
```

### Configuration options

- `pdfPaperSize` - handles the PDF paper size. You can find a full list of available sizes under [Dompdf\\Adapter\\CPDF::$PAPER\_SIZES](https://github.com/dompdf/dompdf/blob/master/src/Adapter/CPDF.php)
- `pdfPaperOrientation` - the PDF paper orientation, either `portrait` or `landscape`
- `pdfAllowRemoteImages` - option to enable/disable remote images in PDFs.

## Control Panel

You can also make change and configuration settings through the Control Panel by visiting Settings → Gift Voucher.

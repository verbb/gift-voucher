# Events

Events can be used to extend the functionality of Gift Voucher.

## Voucher related events

### The `beforeRenderPdf` event

Event handlers can override Gift Voucher’s PDF generation by setting the `pdf` property on the event to a custom-rendered PDF.
Plugins can get notified before the PDF or an order is being rendered.

```php
use craft\commerce\events\PdfEvent;
use verbb\giftvoucher\services\PdfService as Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $e) {
     // Roll out our own custom PDF
});
```

### The `afterRenderPdf` event

Plugins can get notified after the PDF or an order has been rendered.

```php
use craft\commerce\events\PdfEvent;
use verbb\giftvoucher\services\PdfService as Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER_PDF, function(PdfEvent $e) {
     // Add a watermark to the PDF or forward it to the accounting dpt.
});
```

### The `beforeSaveVoucherType` event

Plugins can get notified before a voucher type is being saved.

```php
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\services\VoucherTypesService as VoucherTypes;
use yii\base\Event;

Event::on(VoucherTypes::class, VoucherTypes::EVENT_BEFORE_SAVE_VOUCHERTYPE, function(VoucherTypeEvent $e) {
     // Maybe create an audit trail of this action.
});
```

### The `afterSaveVoucherType` event

Plugins can get notified after a voucher type has been saved.

```php
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\services\VoucherTypesService as VoucherTypes;
use yii\base\Event;

Event::on(VoucherTypes::class, VoucherTypes::EVENT_AFTER_SAVE_VOUCHERTYPE, function(VoucherTypeEvent $e) {
     // Maybe prepare some third party system for a new voucher type
});
```


## Code related events

### The `beforeGenerateCodeKey` event

Plugins get a chance to provide a code key instead of relying on Gift Voucher to generate one.

```php
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\events\GenerateCodeEvent;
use verbb\giftvoucher\GiftVoucher;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_GENERATE_CODE_KEY, function(GenerateCodeEvent $e) {
    do {
        $codeKey = // custom key generation logic...
    } while (!GiftVoucher::getInstance()->getCodes()->isCodeKeyUnique($codeKey));

    $e->codeKey = $codeKey;
});
```


## Redemption related events

### The `beforeSaveRedemption` event

Plugins can get notified before an redemption is saved

```php
use verbb\commerce\events\RedemptionEvent;
use verbb\commerce\services\RedemptionsService as Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_BEFORE_SAVE_REDEMPTION, function(RedemptionEvent $e) {
    // Do something
});
```

### The `afterSaveRedemption` event

Plugins can get notified after a redemption has been saved

```php
use verbb\commerce\events\RedemptionEvent;
use verbb\commerce\services\RedemptionsService as Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_AFTER_SAVE_REDEMPTION, function(RedemptionEvent $e) {
    // Do something
});
```

### The `beforeDeleteRedemption` event

Plugins can get notified before an redemption is deleted

```php
use verbb\commerce\events\RedemptionEvent;
use verbb\commerce\services\RedemptionsService as Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_BEFORE_DELETE_REDEMPTION, function(RedemptionEvent $e) {
    // Do something
});
```

### The `afterDeleteRedemption` event

Plugins can get notified after a redemption has been deleted

```php
use verbb\commerce\events\RedemptionEvent;
use verbb\commerce\services\RedemptionsService as Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_AFTER_DELETE_REDEMPTION, function(RedemptionEvent $e) {
    // Do something
});
```


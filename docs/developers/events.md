# Events
Events can be used to extend the functionality of Gift Voucher.

## Voucher related events

### The `beforeRenderPdf` event
Event handlers can override Gift Voucher’s PDF generation by setting the `pdf` property on the event to a custom-rendered PDF.
Plugins can get notified before the PDF or a voucher is being rendered.

```php
use craft\commerce\events\PdfEvent;
use verbb\giftvoucher\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $e) {
     // Roll out our own custom PDF
});
```

### The `afterRenderPdf` event
Plugins can get notified after the PDF or a voucher has been rendered.

```php
use craft\commerce\events\PdfEvent;
use verbb\giftvoucher\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER_PDF, function(PdfEvent $e) {
     // Add a watermark to the PDF or forward it to the accounting dpt.
});
```

### The `beforeSaveVoucher` event
Plugins can get notified before a voucher is saved. Event handlers can prevent the voucher from getting sent by setting `$event->isValid` to false.

```php
use verbb\giftvoucher\elements\Voucher;
use yii\base\Event;

Event::on(Voucher::class, Voucher::EVENT_BEFORE_SAVE, function(Event $e) {
    $voucher = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveVoucher` event
Plugins can get notified after a voucher has been saved

```php
use verbb\giftvoucher\elements\Voucher;
use yii\base\Event;

Event::on(Voucher::class, Voucher::EVENT_AFTER_SAVE, function(Event $e) {
    $voucher = $event->sender;
});
```

### The `beforeSaveVoucherType` event
Plugins can get notified before a voucher type is being saved.

```php
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\services\VoucherTypes;
use yii\base\Event;

Event::on(VoucherTypes::class, VoucherTypes::EVENT_BEFORE_SAVE_VOUCHERTYPE, function(VoucherTypeEvent $e) {
     // Maybe create an audit trail of this action.
});
```

### The `afterSaveVoucherType` event
Plugins can get notified after a voucher type has been saved.

```php
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\services\VoucherTypes;
use yii\base\Event;

Event::on(VoucherTypes::class, VoucherTypes::EVENT_AFTER_SAVE_VOUCHERTYPE, function(VoucherTypeEvent $e) {
     // Maybe prepare some third party system for a new voucher type
});
```

### The `beforeCaptureVoucherSnapshot` event
Plugins can get notified before we capture a voucher's field data, and customize which fields are included.

```php
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\events\CustomizeVoucherSnapshotFieldsEvent;

Event::on(Voucher::class, Voucher::EVENT_BEFORE_CAPTURE_VOUCHER_SNAPSHOT, function(CustomizeVoucherSnapshotFieldsEvent $e) {
    $voucher = $e->voucher;
    $fields = $e->fields;
    // Modify fields, or set to `null` to capture all.
});
```

### The `afterCaptureVoucherSnapshot` event
Plugins can get notified after we capture a voucher's field data, and customize, extend, or redact the data to be persisted.

```php
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\events\CustomizeVoucherSnapshotDataEvent;

Event::on(Voucher::class, Voucher::EVENT_AFTER_CAPTURE_VOUCHER_SNAPSHOT, function(CustomizeVoucherSnapshotFieldsEvent $e) {
    $voucher = $e->voucher;
    $data = $e->fieldData;
    // Modify or redact captured `$data`...
});
```

### The `afterVoucherAdjustmentsCreated` event
Plugins can get notified after the discount has been made on an order, and before it returns its adjustments. Event handlers can prevent the voucher from getting sent by setting `$event->isValid` to false.

```php
use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\events\VoucherAdjustmentsEvent;

Event::on(GiftVoucherAdjuster::class, GiftVoucherAdjuster::EVENT_AFTER_VOUCHER_ADJUSTMENTS_CREATED, function(VoucherAdjustmentsEvent $e) {

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
    } while (!GiftVoucher::$plugin->getCodes()->isCodeKeyUnique($codeKey));

    $e->codeKey = $codeKey;
});
```

### The `beforeSaveCode` event
Plugins can get notified before a code is saved. Event handlers can prevent the code from getting sent by setting `$event->isValid` to false.

```php
use verbb\giftvoucher\elements\Code;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_BEFORE_SAVE, function(Event $e) {
    $code = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveCode` event
Plugins can get notified after a code has been saved

```php
use verbb\giftvoucher\elements\Code;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_AFTER_SAVE, function(Event $e) {
    $code = $event->sender;
});
```


## Redemption related events

### The `beforeSaveRedemption` event
Plugins can get notified before a redemption is saved

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_BEFORE_SAVE_REDEMPTION, function(RedemptionEvent $e) {
    // Do something
});
```

### The `afterSaveRedemption` event
Plugins can get notified after a redemption has been saved

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_AFTER_SAVE_REDEMPTION, function(RedemptionEvent $e) {
    // Do something
});
```

### The `beforeDeleteRedemption` event
Plugins can get notified before a redemption is deleted

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_BEFORE_DELETE_REDEMPTION, function(RedemptionEvent $e) {
    // Do something
});
```

### The `afterDeleteRedemption` event
Plugins can get notified after a redemption has been deleted

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_AFTER_DELETE_REDEMPTION, function(RedemptionEvent $e) {
    // Do something
});
```

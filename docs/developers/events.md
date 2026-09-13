# Events
Events can be used to extend the functionality of Gift Voucher.


## Register a Listener

Register listeners from a custom module or plugin that is bootstrapped for the requests where the event occurs. Put the `use` imports at the top of its PHP file and the `Event::on(...)` call inside its `init()` method, after `parent::init()`. Do not place the listener in a Twig template or modify this plugin's source to register it.

Choose a hook whose timing matches your task. Cancellation depends on the particular event and emitter, as described for each hook below. Test a listener on the operation it affects, including any relevant queue or console path.

## Voucher Related Events

### The `beforeRenderPdf` Event
Event handlers can override Gift Voucher’s PDF generation by setting the `pdf` property on the event to a custom-rendered PDF.
The event that is triggered before the PDF or a voucher is being rendered.

```php
use verbb\giftvoucher\events\PdfEvent;
use verbb\giftvoucher\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $e) {
     // Roll out our own custom PDF
});
```

### The `afterRenderPdf` Event
The event that is triggered after the PDF or a voucher has been rendered.

```php
use verbb\giftvoucher\events\PdfEvent;
use verbb\giftvoucher\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER_PDF, function(PdfEvent $e) {
     // Add a watermark to the PDF or forward it to the accounting dpt.
});
```

### The `modifyRenderOptions` Event
Plugins can get modify the DomPDF render options

```php
use verbb\giftvoucher\events\PdfRenderOptionsEvent;
use verbb\giftvoucher\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_MODIFY_RENDER_OPTIONS, function(PdfRenderOptionsEvent $event) {

});
```

### The `beforeSaveVoucher` Event
The event that is triggered before a voucher is saved. Event handlers can prevent the voucher from being saved by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\giftvoucher\elements\Voucher;
use yii\base\Event;

Event::on(Voucher::class, Voucher::EVENT_BEFORE_SAVE, function(ModelEvent $e) {
    $voucher = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveVoucher` Event
The event that is triggered after a voucher has been saved

```php
use craft\events\ModelEvent;
use verbb\giftvoucher\elements\Voucher;
use yii\base\Event;

Event::on(Voucher::class, Voucher::EVENT_AFTER_SAVE, function(ModelEvent $e) {
    $voucher = $event->sender;
});
```

### The `beforeSaveVoucherType` Event
The event that is triggered before a voucher type is being saved.

```php
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\services\VoucherTypes;
use yii\base\Event;

Event::on(VoucherTypes::class, VoucherTypes::EVENT_BEFORE_SAVE_VOUCHERTYPE, function(VoucherTypeEvent $event) {
     // Maybe create an audit trail of this action.
});
```

### The `afterSaveVoucherType` Event
The event that is triggered after a voucher type has been saved.

```php
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\services\VoucherTypes;
use yii\base\Event;

Event::on(VoucherTypes::class, VoucherTypes::EVENT_AFTER_SAVE_VOUCHERTYPE, function(VoucherTypeEvent $event) {
     // Maybe prepare some third party system for a new voucher type
});
```

### The `beforeCaptureVoucherSnapshot` Event
The event that is triggered before we capture a voucher's field data, and customize which fields are included.

```php
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\events\CustomizeVoucherSnapshotFieldsEvent;

Event::on(Voucher::class, Voucher::EVENT_BEFORE_CAPTURE_VOUCHER_SNAPSHOT, function(CustomizeVoucherSnapshotFieldsEvent $event) {
    $voucher = $event->voucher;
    $fields = $event->fields;
    // Modify fields, or set to `null` to capture all.
});
```

### The `afterCaptureVoucherSnapshot` Event
The event that is triggered after we capture a voucher's field data, and customize, extend, or redact the data to be persisted.

```php
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\events\CustomizeVoucherSnapshotDataEvent;

Event::on(Voucher::class, Voucher::EVENT_AFTER_CAPTURE_VOUCHER_SNAPSHOT, function(CustomizeVoucherSnapshotFieldsEvent $event) {
    $voucher = $event->voucher;
    $data = $event->fieldData;
    // Modify or redact captured `$data`...
});
```

### The `afterVoucherAdjustmentsCreated` Event
The event that is triggered after voucher adjustments have been calculated and before they are returned to Commerce. You can modify `$event->adjustments`. Set `$event->isValid` to `false` to return no voucher adjustments for this calculation; this does not cancel voucher delivery.

```php
use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\events\VoucherAdjustmentsEvent;
use yii\base\Event;

Event::on(GiftVoucherAdjuster::class, GiftVoucherAdjuster::EVENT_AFTER_VOUCHER_ADJUSTMENTS_CREATED, function(VoucherAdjustmentsEvent $event) {

});
```


## Code Related Events

### The `beforeGenerateCodeKey` Event
Plugins get a chance to provide a code key instead of relying on Gift Voucher to generate one.

```php
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\events\GenerateCodeEvent;
use verbb\giftvoucher\GiftVoucher;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_GENERATE_CODE_KEY, function(GenerateCodeEvent $event) {
    do {
        $codeKey = strtoupper(bin2hex(random_bytes(8)));
    } while (!GiftVoucher::$plugin->getCodes()->isCodeKeyUnique($codeKey));

    $event->codeKey = $codeKey;
});
```

### The `afterBulkGenerateCodesEvent` Event
Plugins can get a list of all codes that were generated in a bulk operation.

```php
use verbb\giftvoucher\controllers\CodesController;
use verbb\giftvoucher\events\BulkGenerateCodesEvent;
use verbb\giftvoucher\GiftVoucher;
use yii\base\Event;

Event::on(CodesController::class, CodesController::EVENT_AFTER_BULK_GENERATE_CODES, function(BulkGenerateCodesEvent $event) {
    $codes = $event->codes;
});
```

### The `beforeSaveCode` Event
The event that is triggered before a code is saved. Event handlers can prevent the code from being saved by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\giftvoucher\elements\Code;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $code = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveCode` Event
The event that is triggered after a code has been saved

```php
use craft\events\ModelEvent;
use verbb\giftvoucher\elements\Code;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $code = $event->sender;
});
```


## Redemption Related Events

### The `beforeSaveRedemption` Event
The event that is triggered before a redemption is saved

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_BEFORE_SAVE_REDEMPTION, function(RedemptionEvent $event) {
    // Do something
});
```

### The `afterSaveRedemption` Event
The event that is triggered after a redemption has been saved

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_AFTER_SAVE_REDEMPTION, function(RedemptionEvent $event) {
    // Do something
});
```

### The `beforeDeleteRedemption` Event
The event that is triggered before a redemption is deleted

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_BEFORE_DELETE_REDEMPTION, function(RedemptionEvent $event) {
    // Do something
});
```

### The `afterDeleteRedemption` Event
The event that is triggered after a redemption has been deleted

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_AFTER_DELETE_REDEMPTION, function(RedemptionEvent $event) {
    // Do something
});
```

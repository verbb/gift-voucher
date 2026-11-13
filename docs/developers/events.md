# Events

Gift Voucher provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Gift Voucher’s behaviour.

## Voucher PDF Events

### The `beforeRenderPdf` Event
The event that is triggered before a voucher PDF is rendered.

Listeners can change `$event->template`, `$event->variables`, `$event->order` and `$event->option` before Gift Voucher renders the template. To replace the PDF entirely, set `$event->pdf` to your own rendered PDF string. Gift Voucher then returns it as-is, skipping its template and Dompdf, and the `afterRenderPdf` event doesn’t fire.

```php
use verbb\giftvoucher\events\PdfEvent;
use verbb\giftvoucher\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $event) {
    // Use a different template for a specific option
    if ($event->option === 'print') {
        $event->template = '_pdf/voucher-print';
    }
});
```

### The `afterRenderPdf` Event
The event that is triggered after a voucher PDF has been rendered.

`$event->pdf` holds the rendered PDF string. Listeners can replace it, and Gift Voucher returns whatever it holds when the listener finishes. This event doesn’t fire when the PDF is requested as plain HTML with `format=plain`.

```php
use verbb\giftvoucher\events\PdfEvent;
use verbb\giftvoucher\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER_PDF, function(PdfEvent $event) {
    $pdf = $event->pdf;
    $order = $event->order;

    // Store or forward a copy of the PDF
});
```

### The `modifyRenderOptions` Event
The event that is triggered before Gift Voucher passes its render options to Dompdf.

`$event->options` is a `Dompdf\Options` object, already set up with Gift Voucher’s temp and font folders and its remote image setting. Change it in place to adjust how Dompdf renders the PDF.

```php
use verbb\giftvoucher\events\PdfRenderOptionsEvent;
use verbb\giftvoucher\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_MODIFY_RENDER_OPTIONS, function(PdfRenderOptionsEvent $event) {
    $event->options->setDefaultFont('DejaVu Sans');
    $event->options->setDpi(150);
});
```

## Voucher Events

### The `beforeSave` Event
The event that is triggered before a voucher is saved.

This is Craft’s element event. Set `$event->isValid` to `false` to prevent the voucher from being saved.

```php
use craft\events\ModelEvent;
use verbb\giftvoucher\elements\Voucher;
use yii\base\Event;

Event::on(Voucher::class, Voucher::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $voucher = $event->sender;
    $isNew = $event->isNew;

    // Prevent the save
    $event->isValid = false;
});
```

### The `afterSave` Event
The event that is triggered after a voucher has been saved.

```php
use craft\events\ModelEvent;
use verbb\giftvoucher\elements\Voucher;
use yii\base\Event;

Event::on(Voucher::class, Voucher::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $voucher = $event->sender;
    $isNew = $event->isNew;
});
```

### The `beforeCaptureVoucherSnapshot` Event
The event that is triggered before a voucher’s data is captured for a line item snapshot.

When a voucher is added to a Commerce order, Commerce stores a snapshot of it on the line item. Gift Voucher includes the voucher’s attributes but not its custom fields by default. Add custom field handles to `$event->fields` to include those fields.

```php
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\events\CustomizeVoucherSnapshotFieldsEvent;
use yii\base\Event;

Event::on(Voucher::class, Voucher::EVENT_BEFORE_CAPTURE_VOUCHER_SNAPSHOT, function(CustomizeVoucherSnapshotFieldsEvent $event) {
    $voucher = $event->voucher;
    $event->fields[] = 'voucherImage';
});
```

### The `afterCaptureVoucherSnapshot` Event
The event that is triggered after a voucher’s data is captured for a line item snapshot.

`$event->fieldData` holds the captured voucher data. Listeners can add to it, change it or remove values before it’s stored.

```php
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\events\CustomizeVoucherSnapshotDataEvent;
use yii\base\Event;

Event::on(Voucher::class, Voucher::EVENT_AFTER_CAPTURE_VOUCHER_SNAPSHOT, function(CustomizeVoucherSnapshotDataEvent $event) {
    $voucher = $event->voucher;

    // Don't store the voucher's SKU
    unset($event->fieldData['sku']);
});
```

### The `afterVoucherAdjustmentsCreated` Event
The event that is triggered after Gift Voucher calculates the discounts that applied voucher codes make to an order, before they’re returned to Commerce.

`$event->order` is the order, `$event->giftVoucherCodes` holds the applied codes and `$event->adjustments` holds the Commerce adjustments. Listeners can change or remove adjustments. Set `$event->isValid` to `false` to apply no voucher discounts for this calculation. The codes stay applied to the order.

```php
use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\events\VoucherAdjustmentsEvent;
use yii\base\Event;

Event::on(GiftVoucherAdjuster::class, GiftVoucherAdjuster::EVENT_AFTER_VOUCHER_ADJUSTMENTS_CREATED, function(VoucherAdjustmentsEvent $event) {
    $order = $event->order;

    foreach ($event->adjustments as $adjustment) {
        $adjustment->description = 'Gift card';
    }
});
```

## Voucher Type Events

### The `beforeSaveVoucherType` Event
The event that is triggered before a voucher type is saved.

Listeners can change `$event->voucherType` before it’s validated and saved. `$event->isNew` tells you whether it’s a new voucher type. The event can’t cancel the save.

```php
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\services\VoucherTypes;
use yii\base\Event;

Event::on(VoucherTypes::class, VoucherTypes::EVENT_BEFORE_SAVE_VOUCHERTYPE, function(VoucherTypeEvent $event) {
    $voucherType = $event->voucherType;
    $isNew = $event->isNew;
});
```

### The `afterSaveVoucherType` Event
The event that is triggered after a voucher type has been saved.

```php
use verbb\giftvoucher\events\VoucherTypeEvent;
use verbb\giftvoucher\services\VoucherTypes;
use yii\base\Event;

Event::on(VoucherTypes::class, VoucherTypes::EVENT_AFTER_SAVE_VOUCHERTYPE, function(VoucherTypeEvent $event) {
    $voucherType = $event->voucherType;
    $isNew = $event->isNew;
});
```

## Code Events

### The `beforeGenerateCodeKey` Event
The event that is triggered before Gift Voucher generates the key for a new code, which is the code customers enter at checkout.

Set `$event->codeKey` to use your own key instead of a generated one. `$event->code` is the code being created. Gift Voucher uses your key as-is, so make sure it’s unique.

```php
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\events\GenerateCodeEvent;
use verbb\giftvoucher\GiftVoucher;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_GENERATE_CODE_KEY, function(GenerateCodeEvent $event) {
    do {
        $codeKey = 'GIFT-' . strtoupper(bin2hex(random_bytes(4)));
    } while (!GiftVoucher::$plugin->getCodes()->isCodeKeyUnique($codeKey));

    $event->codeKey = $codeKey;
});
```

### The `populateCodeFromLineItem` Event
The event that is triggered when Gift Voucher creates a code for a voucher bought in a completed order, before the code is saved.

`$event->code` is the new code, with its amount and any custom field values copied from the line item’s options. `$event->customFields` lists the field values that were copied. Listeners can change the code, such as setting its expiry date or other custom fields. `$event->order`, `$event->lineItem` and `$event->voucher` are the order, line item and voucher it was bought from.

```php
use verbb\giftvoucher\events\PopulateCodeFromLineItemEvent;
use verbb\giftvoucher\services\Codes;
use yii\base\Event;

Event::on(Codes::class, Codes::EVENT_POPULATE_CODE_FROM_LINE_ITEM, function(PopulateCodeFromLineItemEvent $event) {
    // Make codes bought online expire after a year
    $event->code->expiryDate = (new \DateTime())->modify('+1 year');
});
```

### The `beforeMatchCode` Event
The event that is triggered when a customer applies a voucher code to their cart, before Gift Voucher checks it.

`$event->codeKey` is the code the customer entered, and `$event->code` is the matching code, or `null` when none matches. Set `$event->isValid` to `false`, or set `$event->error` to a message, to reject the code. The customer sees your message, or “Voucher code is not valid” when you don’t set one. A code the listener accepts still goes through Gift Voucher’s own checks.

```php
use verbb\giftvoucher\events\MatchCodeEvent;
use verbb\giftvoucher\services\Codes;
use yii\base\Event;

Event::on(Codes::class, Codes::EVENT_BEFORE_MATCH_CODE, function(MatchCodeEvent $event) {
    // Only accept staff codes for logged-in users
    if (str_starts_with((string)$event->codeKey, 'STAFF-') && !\Craft::$app->getUser()->getIdentity()) {
        $event->error = 'Log in to use a staff voucher code.';
    }
});
```

### The `afterBulkGenerateCodesEvent` Event
The event that is triggered after codes are bulk-generated in the control panel.

`$event->codes` holds the saved codes.

```php
use verbb\giftvoucher\controllers\CodesController;
use verbb\giftvoucher\events\BulkGenerateCodesEvent;
use yii\base\Event;

Event::on(CodesController::class, CodesController::EVENT_AFTER_BULK_GENERATE_CODES, function(BulkGenerateCodesEvent $event) {
    foreach ($event->codes as $code) {
        $codeKey = $code->codeKey;
    }
});
```

### The `beforeSave` Event
The event that is triggered before a code is saved.

This is Craft’s element event. Set `$event->isValid` to `false` to prevent the code from being saved.

```php
use craft\events\ModelEvent;
use verbb\giftvoucher\elements\Code;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $code = $event->sender;
    $isNew = $event->isNew;

    // Prevent the save
    $event->isValid = false;
});
```

### The `afterSave` Event
The event that is triggered after a code has been saved.

```php
use craft\events\ModelEvent;
use verbb\giftvoucher\elements\Code;
use yii\base\Event;

Event::on(Code::class, Code::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $code = $event->sender;
    $isNew = $event->isNew;
});
```

## Redemption Events

A redemption records an amount of a code used on an order.

### The `beforeSaveRedemption` Event
The event that is triggered before a redemption is saved.

Listeners can change `$event->redemption` before it’s validated and saved. `$event->isNew` tells you whether it’s a new redemption. The event can’t cancel the save.

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_BEFORE_SAVE_REDEMPTION, function(RedemptionEvent $event) {
    $redemption = $event->redemption;
    $isNew = $event->isNew;
});
```

### The `afterSaveRedemption` Event
The event that is triggered after a redemption has been saved.

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_AFTER_SAVE_REDEMPTION, function(RedemptionEvent $event) {
    $redemption = $event->redemption;
    $amount = $redemption->amount;
});
```

### The `beforeDeleteRedemption` Event
The event that is triggered before a redemption is deleted.

The event can’t cancel the deletion.

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_BEFORE_DELETE_REDEMPTION, function(RedemptionEvent $event) {
    $redemption = $event->redemption;
});
```

### The `afterDeleteRedemption` Event
The event that is triggered after a redemption has been deleted.

```php
use verbb\giftvoucher\events\RedemptionEvent;
use verbb\giftvoucher\services\Redemptions;
use yii\base\Event;

Event::on(Redemptions::class, Redemptions::EVENT_AFTER_DELETE_REDEMPTION, function(RedemptionEvent $event) {
    $redemption = $event->redemption;
});
```

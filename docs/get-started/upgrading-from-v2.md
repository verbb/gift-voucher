# Upgrading from v2
While the [changelog](https://github.com/verbb/gift-voucher/blob/craft-5/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Renamed Classes
The following classes have been renamed.

Old | What to do instead
--- | ---
| `verbb\giftvoucher\models\RedemptionModel` | `verbb\giftvoucher\models\Redemption`
| `verbb\giftvoucher\models\VoucherTypeModel` | `verbb\giftvoucher\models\VoucherType`
| `verbb\giftvoucher\models\VoucherTypeSiteModel` | `verbb\giftvoucher\models\VoucherTypeSite`
| `verbb\giftvoucher\records\CodeRecord` | `verbb\giftvoucher\records\Code`
| `verbb\giftvoucher\records\RedemptionRecord` | `verbb\giftvoucher\records\Redemption`
| `verbb\giftvoucher\records\VoucherRecord` | `verbb\giftvoucher\records\Voucher`
| `verbb\giftvoucher\records\VoucherTypeRecord` | `verbb\giftvoucher\records\VoucherType`
| `verbb\giftvoucher\records\VoucherTypeSiteRecord` | `verbb\giftvoucher\records\VoucherTypeSite`
| `verbb\giftvoucher\services\CodesService` | `verbb\giftvoucher\services\Codes`
| `verbb\giftvoucher\services\PdfService` | `verbb\giftvoucher\services\Pdf`
| `verbb\giftvoucher\services\RedemptionsService` | `verbb\giftvoucher\services\Redemptions`
| `verbb\giftvoucher\services\VouchersService` | `verbb\giftvoucher\services\Vouchers`
| `verbb\giftvoucher\services\VoucherTypesService` | `verbb\giftvoucher\services\VoucherTypes`

# Changelog

## 2.0.6 - 2018-12-14

### Changed
- Use `beforeCompleteOrder` as the event to generate codes, ensuring they can be used in emails.

## 2.0.5 - 2018-12-14

### Added
- Add `afterVoucherAdjustmentsCreated`.

### Fixed
- Fix querying codes by voucherId not working.
- Fix codes not being generated correctly after checkout completion.
- Fix redemptions not being generated correctly after checkout completion.

## 2.0.4 - 2018-12-01

### Added
- Add `beforeCaptureVoucherSnapshot` and `afterCaptureVoucherSnapshot`.

### Fixed
- Fix permissions for vouchers and codes.
- Fix error occuring when installing plugin via command line

## 2.0.3 - 2018-11-10

### Added
- Allow PDFs to be generated for single voucher codes.
- Add type to snapshot data.

### Fixed
- Fix Gift Voucher element field name.
- Fix error when deleting a voucher.
- Fix querying by `voucherId` on a code.
- Fix error when querying and saving vouchers.

## 2.0.2 - 2018-10-26

### Added
- Add product method for voucher for easy commerce compatibility
- Add field data to line item snapshot

### Changed
- Codes are now generated after the order is paid, instead of when complete
- Bring back `getPdfUrl()` and `getOrderPdfUrl()`

### Fixed
- Fix welcome redirect
- Fix empty strings being able to validate vouchers (oops)

## 2.0.1 - 2018-10-18

### Fixed
- Fixed some deprecation notices
- Fix redemption error for new codes

## 2.0.0 - 2018-10-17

- Craft 3 release.

## 1.0.1 - 2017-01-17

### Added
- Redeeming multiple vouchers.
- Added possibility to return Ajax in frontend controller.

### Changed
- Redeemed code managing.
- Change DiscountAdjuster order to apply after tax discount.

### Fixed
- Adjuster showing proper discount amount.

## 1.0.0 - 2017-12-18

- Initial release.

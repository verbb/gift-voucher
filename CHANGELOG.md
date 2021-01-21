# Changelog

## 2.5.7 - 2021-01-22

### Fixed
- Fix gift vouchers attaching to emails, even when disabled.
- Change logging from `error` to `info` when an order doesn’t contain a gift voucher.

## 2.5.6 - 2020-12-12

### Added
- Add more logging around post-checkout functionality.

### Changed
- Post-checkout functionality is now performed on `EVENT_AFTER_COMPLETE_ORDER` rather than `EVENT_BEFORE_COMPLETE_ORDER`.

### Fixed
- Fix permission translation error.

## 2.5.5 - 2020-11-26

### Fixed
- Ensure critical errors are logged during the order complete event.
- Fix critical errors preventing orders from completing correctly.

## 2.5.4 - 2020-11-07

### Fixed
- Fix potential error with `isVoucher` and line item purchasables.

## 2.5.3 - 2020-11-06

### Added
- Add support to apply gift voucher from the control panel, when editing an incomplete order.
- Add ability to attach gift voucher PDFs to Commerce emails.

### Fixed
- Fix an error when no code is found when applying voucher codes.

## 2.5.2 - 2020-10-08

### Changed
- Custom fields are no longer serialized in the snapshot of a voucher. Please use `EVENT_AFTER_CAPTURE_VOUCHER_SNAPSHOT` and `EVENT_BEFORE_CAPTURE_VOUCHER_SNAPSHOT` events to opt-in any custom fields you want serialized in the snapshot. This follows Commerce's behaviour.

### Fixed
- Fix incorrectly serializing Super Table queries when taking a snapshot of Gift Vouchers when adding to the cart.
- Fix `EVENT_AFTER_CAPTURE_VOUCHER_SNAPSHOT` event not actually doing much.
- Prevent code field layout ID from being creating multiple times when saving plugin settings.

## 2.5.1 - 2020-09-18 [CRITICAL]

### Fixed
- Fix a potential security vulnerability where the last-created voucher could be redeemed by any user.
- Replace hard coded currency with order currency. (thanks @yingban).

## 2.5.0 - 2020-08-31

### Added
- Add support for Craft 3.5 field designer for code elements.
- Add support for Craft 3.5 field designer for voucher elements.

### Changed
- Now requires Craft 3.5+.
- Now requires Craft Commerce 3.2+.

## 2.4.3 - 2020-08-20

### Fixed
- Fix `getEditableVoucherTypeIds` not returning correctly.

## 2.4.2.1 - 2020-07-30

### Fixed
- Fix ErrorException from undefined variable uid. (thanks @jmauzyk).

## 2.4.2 - 2020-07-29

### Fixed
- Fix voucher site dropdown not working for multi-sites.
- Fix share and preview button alignment for vouchers.
- Fix live preview not working for vouchers.
- Fix save-as-new-voucher not working correctly.
- Fix voucher permissions to use UID instead of ID.

## 2.4.1 - 2020-06-22

### Fixed
- Add migration for potentially missing code field layout.
- Fix usage of deprecated `saleAmount`.
- Return cart errors for ajax-applying of voucher codes.

## 2.4.0 - 2020-06-18
> {warning} Please note a breaking change in removing `fieldsPath`. If you use this setting to add custom field content to your voucher codes when adding to the cart, you will need to change this. Please see the docs - https://verbb.io/craft-plugins/gift-voucher/docs/template-guides/single-voucher#line-item-options

### Changed
- Removed `fieldsPath` config setting. If you are using this setting for adding custom field content to your voucher codes, please update your templates. Use `input type="text" name="options[fieldHandle]"` instead of `input type="text" name="options[<fieldsPath>][fieldHandle]"`.

## 2.3.1 - 2020-06-15

### Fixed
- Fix potential error when trying to save a voucher code without an amount.
- Fix incorrect handling for code field layout.

## 2.3.0 - 2020-05-10

### Added
- Add project config support for voucher types.

### Fixed
- Check to ensure Klaviyo Connect plugin is installed before doing any more checks. (thanks @brianjhanson)

## 2.2.4 - 2020-04-16

### Fixed
- Fix logging error `Call to undefined method setFileLogging()`.

## 2.2.3 - 2020-04-15

### Changed
- File logging now checks if the overall Craft app uses file logging.
- Log files now only include `GET` and `POST` additional variables.

### Fixed 
- Only allow editing of voucher types if editable.

## 2.2.2 - 2020-03-16

### Fixed
- Fix order and voucher columns showing incorrect values in the voucher codes element index.
- Add “Original Amount” to voucher codes element index.
- Fix currency symbol in voucher codes element index in some cases.

## 2.2.1 - 2020-02-13

### Fixed
- Fix missing order properties for PDFs generated in CP. (thanks @jmauzyk).
- Fix `populateLineItem` to work with Commerce 3.x.

## 2.2.0 - 2020-01-29

### Added
- Craft 3.4 compatibility.
- Commerce 3.0 compatibility.

## 2.1.2 - 2020-01-09

### Fixed
- Fix `registerAdjuster` = `afterTax` not working correctly.

## 2.1.1 - 2020-01-09

### Added
- Add `registerAdjuster` plugin setting to control when the adjuster should be applied. Valid options are `beforeTax` (default) and `afterTax`.

## 2.1.0 - 2020-01-04

### Added
- Add the ability to set field layouts for Voucher Codes. (thanks @Anubarak).
- Add a field layout designer to the settings for Voucher Codes. (thanks @Anubarak).
- Add an event `PopulateCodeFromLineItemEvent` to set additional fields/properties when a new Code is created based on a Voucher/LineItem. (thanks @Anubarak).
- Add an event to validate LineItem options for custom fields in the Code. (thanks @Anubarak).
- Add a migration to include an initial empty `content` record for all existing Voucher Codes. (thanks @Anubarak).
- Add a new setting `fieldsPath` that represents the path of custom fields in the LineItems options. (thanks @Anubarak).
- Add additional Tabs for custom fields in the Voucher Code if there are any. (thanks @Anubarak).
- Add a component `codeStorage` for storing and receiving codes that are used. (thanks @Anubarak).
- Add an interface for `codeStorage` and the possibility to use custom classes/components. (thanks @Anubarak).
- Add a class `Session` for storing the used codes for an order in the session. (thanks @Anubarak).
- Add a class `Order` for storing the used codes for an order in the field layout in a Codes field. (thanks @Anubarak).
- Add a new Code relation field type. (thanks @Anubarak).
- Add template hook `cp.gift-voucher.voucher.edit.details` (thanks @samuelbirch).

### Changed
- All codeKeys are now grabbed via `GiftVoucher::getInstance()->getCodeStorage()->getCodeKeys($order);`. (thanks @Anubarak).
- All codeKeys are now set via `GiftVoucher::getInstance()->getCodeStorage()->set($codeKeys, $order);` or the `add` or `remove` function.
- This will make sure you can add a VoucherCode in the CP. (thanks @Anubarak).
- Use a custom controller to store the plugins settings ensuring the new Field Layout setting is stored properly. (thanks @Anubarak).

### Fixed
- Fixed minor session issue. (thanks @Anubarak).
- Display inactive Vouchers in the CP. (thanks @Anubarak).
- Fixed a Bug that could occur when re-saving an order via job, Craft already closes the session before running the job -> the adjuster has no valid session. (thanks @Anubarak).
- Fix missing date columns in code element index.
- Fix voucher discount being applied after tax.
- Fix incorrect multi-site URL redirection when switching sites for a voucher.

## 2.0.13 - 2019-08-25

### Fixed
- Fix incorrect migration.

## 2.0.12.1 - 2019-08-25

### Fixed
- Fix namespacing issue.

## 2.0.12 - 2019-08-25

### Added 
- Add support for Klaviyo Connect plugin.
- Add support for Commerce 3.

### Changed
- Adjust template functions `vouchers()` and `codes()` to allow criteria as params.

### Fixed
- Incorrect permission for voucher types.
- Fix anonymous requests to gift voucher previewing.
- Fix missing Commerce requirement.
- Fix missing expiryDate column.
- Fix voucher search indexes.
- Fix lack of registering Code as an element type.
- Add codeKey to searchable attributes.
- Fix legacy codes (from Craft 2) not correctly being elements.
- Fix lack of integrity constraint on codes.

## 2.0.11 - 2019-06-01

### Added
- Add override notice for settings fields.

### Fixed
- Fix missing `sku` and `price` query params.
- Fix error in element HUD.

## 2.0.10 - 2019-02-27

### Fixed
- Fix multiple tabs for voucher types not showing.
- Fix “New voucher” layout issue when switching voucher types

## 2.0.9 - 2019-02-19

### Added
- Added “Don’t allow voucher codes to be used if discounts are applied on the order”. By default, discount codes and voucher codes can be applied to an order together, which may not always be desired. This setting honours the "Don’t apply subsequent discounts if this discount is applied" for discounts.

## 2.0.8 - 2019-02-17

### Fixed
- Fix migration issue from Craft 2 to Craft 3.

## 2.0.7 - 2018-12-26

### Fixed
- Include translation of Adjuster description. Thanks (@Anubarak).
- Set the `codeKey` to the element after storing the record to use it directly after the Code is created. Thanks (@Anubarak).
- Add PDF config settings, rather than from Commerce.
- Fix PDF paper orientation and size not changing from defaults.
- Bring back missing `format` and `attach` params for PDF.

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

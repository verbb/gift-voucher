<?php
namespace verbb\giftvoucher\helpers;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Localization;
use craft\web\Request;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VoucherHelper
{
    public static function voucherFromPost(Request $request = null): Voucher
    {
        if ($request === null) {
            $request = Craft::$app->getRequest();
        }

        $voucherId = $request->getBodyParam('voucherId');
        $siteId = $request->getBodyParam('siteId');

        if ($voucherId) {
            $voucher = GiftVoucher::$plugin->getVouchers()->getVoucherById($voucherId, $siteId);

            if (!$voucher) {
                throw new NotFoundHttpException(Craft::t('gift-voucher', 'No voucher with the ID “{id}”', ['id' => $voucherId]));
            }
        } else {
            $voucher = new Voucher();
            $voucher->typeId = $request->getBodyParam('typeId');
            $voucher->siteId = $siteId ?? $voucher->siteId;
        }

        return $voucher;
    }

    public static function populateVoucherFromPost(Voucher $voucher = null, Request $request = null): Voucher
    {
        if ($request === null) {
            $request = Craft::$app->getRequest();
        }

        if ($voucher === null) {
            $voucher = static::voucherFromPost($request);
        }

        $voucher->enabled = (bool)$request->getBodyParam('enabled');

        $voucher->price = Localization::normalizeNumber($request->getBodyParam('price'));
        $voucher->sku = $request->getBodyParam('sku');

        $voucher->customAmount = $request->getBodyParam('customAmount');

        if ($voucher->customAmount) {
            $voucher->price = 0;
        }

        if (($postDate = $request->getBodyParam('postDate')) !== null) {
            $voucher->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }

        if (($expiryDate = $request->getBodyParam('expiryDate')) !== null) {
            $voucher->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $voucher->promotable = (bool)$request->getBodyParam('promotable');
        $voucher->availableForPurchase = (bool)$request->getBodyParam('availableForPurchase');
        $voucher->taxCategoryId = $request->getBodyParam('taxCategoryId');
        $voucher->shippingCategoryId = $request->getBodyParam('shippingCategoryId');
        $voucher->slug = $request->getBodyParam('slug');

        $voucher->enabledForSite = (bool)$request->getBodyParam('enabledForSite', $voucher->enabledForSite);
        $voucher->title = $request->getBodyParam('title', $voucher->title);

        $voucher->setFieldValuesFromRequest('fields');

        // Last checks
        if (empty($voucher->sku)) {
            $voucherType = $voucher->getType();
            $voucher->sku = Craft::$app->getView()->renderObjectTemplate($voucherType->skuFormat, $voucher);
        }

        return $voucher;
    }
}

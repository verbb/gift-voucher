<?php

namespace Craft;

class GiftVoucher_CartController extends BaseController
{
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Frontend controller for matching the voucher code and store it in a
     * session so that our discount adjuster can deal with it
     *
     * @throws HttpException
     */
    public function actionCode()
    {
        $this->requirePostRequest();

        /** @var Commerce_OrderModel $cart */
        $cart = craft()->commerce_cart->getCart();

        $voucherCode = craft()->request->getPost('giftVoucherCode');

        $error = '';

        if ($voucherCode != null) {
            GiftVoucherHelper::getCodesService()->matchCode($voucherCode, $error);
        }

        if ($error !== '') {
            $updateErrors['voucherCode'] = $error;

            $cart->addErrors($updateErrors);

            // Delete voucher code in session
            craft()->httpSession->add('giftVoucher.giftVoucherCode', '');

        } else {

            // Store voucher code in session
            craft()->httpSession->add('giftVoucher.giftVoucherCode', $voucherCode);

            craft()->userSession->setNotice(Craft::t('Cart updated.'));

            craft()->commerce_orders->saveOrder($cart);

            $this->redirectToPostedUrl();
        }

    }
}

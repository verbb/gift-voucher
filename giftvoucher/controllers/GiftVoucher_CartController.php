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
    public function actionAddCode()
    {
        $this->requirePostRequest();

        /** @var Commerce_OrderModel $cart */
        $cart = craft()->commerce_cart->getCart();

        $voucherCode = craft()->request->getPost('giftVoucherCode');

        $error = '';

        if ($voucherCode && $voucherCode != '') {
            GiftVoucherHelper::getCodesService()->matchCode($voucherCode, $error);

            if ($error !== '') {
                $updateErrors['voucherCode'] = $error;

                $cart->addErrors($updateErrors);

                // Delete voucher code in session
    //            craft()->httpSession->add('giftVoucher.giftVoucherCode', '');
    //            $giftVoucherCodes = craft()->httpSession->get('giftVoucher.giftVoucherCodes');
    //            if(count($giftVoucherCodes) > 0) {
    //                foreach ($giftVoucherCodes as $codes) {
    //                    if ($codes == )
    //                }
    //            }

            } else {

                // Get already stored voucher codes
                $giftVoucherCodes = craft()->httpSession->get('giftVoucher.giftVoucherCodes');

                if (!$giftVoucherCodes) {
                    $giftVoucherCodes = [];
                }

                // Add voucher code to session array
                if (!in_array($voucherCode, $giftVoucherCodes, false)) {
                    $giftVoucherCodes[] = $voucherCode;
                    craft()->httpSession->add('giftVoucher.giftVoucherCodes', $giftVoucherCodes);
                }

                craft()->userSession->setNotice(Craft::t('Cart updated.'));
                craft()->commerce_orders->saveOrder($cart);
                $this->redirectToPostedUrl();
            }
        }
    }

    /**
     * Frontend controller to remove a particular voucher code from the session and update cart.
     *
     * @throws HttpException
     */
    public function actionRemoveCode()
    {
        $this->requirePostRequest();

        /** @var Commerce_OrderModel $cart */
        $cart = craft()->commerce_cart->getCart();

        $voucherCode = craft()->request->getPost('giftVoucherCode');

        // Get session array
        $giftVoucherCodes = craft()->httpSession->get('giftVoucher.giftVoucherCodes');

        // Search for the key in array
        $key = array_search($voucherCode, $giftVoucherCodes, false);

        // Delete particular voucher code from session array via key
        if ($giftVoucherCodes && isset($giftVoucherCodes[$key])) {
            unset($giftVoucherCodes[$key]);
        }

        // Store the updated session array
        craft()->httpSession->add('giftVoucher.giftVoucherCodes', $giftVoucherCodes);

        craft()->userSession->setNotice(Craft::t('Cart updated.'));
        craft()->commerce_orders->saveOrder($cart);
        $this->redirectToPostedUrl();

//        if(count($giftVoucherCodes) > 0) {
//            foreach ($giftVoucherCodes as $giftVoucherCode) {
//                if ($giftVoucherCode == $voucherCode) {
//
//                }
//            }
//        }
    }
}

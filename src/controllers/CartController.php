<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\controllers\BaseFrontEndController;

class CartController extends BaseFrontEndController
{
    // Properties
    // =========================================================================

    private $_cart;
    private $_cartVariable;


    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->_cart = Commerce::getInstance()->getCarts()->getCart();
        $this->_cartVariable = Commerce::getInstance()->getSettings()->cartVariable;

        parent::init();
    }

    public function actionAddCode()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $voucherCode = $request->getParam('voucherCode');

        if (!$voucherCode || trim($voucherCode) == '') {
            $this->_cart->addErrors(['voucherCode' => Craft::t('gift-voucher', 'No voucher code provided.')]);

            return $this->_returnCart();
        }

        $error = '';

        // Check to see if this is a Gift Voucher code
        GiftVoucher::$plugin->getCodes()->matchCode($voucherCode, $error);

        if ($error) {
            // Check to see if its a Coupon code
            $isCouponCode = Commerce::getInstance()->getDiscounts()->getDiscountByCode($voucherCode);

            if ($isCouponCode) {
                $couponError = '';

                // Try and apply the coupon code
                $this->_cart->couponCode = $voucherCode;
                $couponCode = Commerce::getInstance()->getDiscounts()->orderCouponAvailable($this->_cart, $couponError);

                if ($couponError) {
                    $this->_cart->addErrors(['couponCode' => $couponError]);

                    return $this->_returnCart();
                }

                return $this->_returnCart();
            }

            $this->_cart->addErrors(['voucherCode' => $error]);

            return $this->_returnCart();
        }

        // Get already stored voucher codes
        // $giftVoucherCodes = $session->get('giftVoucher.giftVoucherCodes');
        GiftVoucher::getInstance()->getCodeStorage()->add($voucherCode, $this->_cart);

        return $this->_returnCart();
    }

    public function actionRemoveCode()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $voucherCode = $request->getParam('voucherCode');
        GiftVoucher::getInstance()->getCodeStorage()->remove($voucherCode, $this->_cart);

        return $this->_returnCart();
    }


    // Private Methods
    // =========================================================================

    private function _returnCart()
    {
        $request = Craft::$app->getRequest();

        // Test if we've already set errors on the cart - below we're saving the order again, wiping out current errors
        if ($this->_cart->hasErrors()) {
            $error = Craft::t('commerce', 'Unable to update cart.');

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'error' => $error,
                    'errors' => $this->_cart->getErrors(),
                    'success' => !$this->_cart->hasErrors(),
                    $this->_cartVariable => $this->cartArray($this->_cart)
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                $this->_cartVariable => $this->_cart
            ]);

            Craft::$app->getSession()->setError($error);

            return null;
        }

        //
        // Straight from Commerce
        //
        if (!$this->_cart->validate() || !Craft::$app->getElements()->saveElement($this->_cart, false)) {
            $error = Craft::t('commerce', 'Unable to update cart.');

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'error' => $error,
                    'errors' => $this->_cart->getErrors(),
                    'success' => !$this->_cart->hasErrors(),
                    $this->_cartVariable => $this->cartArray($this->_cart)
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                $this->_cartVariable => $this->_cart
            ]);

            Craft::$app->getSession()->setError($error);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => !$this->_cart->hasErrors(),
                $this->_cartVariable => $this->cartArray($this->_cart)
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('commerce', 'Cart updated.'));

        Craft::$app->getUrlManager()->setRouteParams([
            $this->_cartVariable => $this->_cart
        ]);

        return $this->redirectToPostedUrl();
    }
}

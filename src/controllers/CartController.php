<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;

use Craft;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\controllers\BaseFrontEndController;

use yii\web\Response;

class CartController extends BaseFrontEndController
{
    // Properties
    // =========================================================================

    private Order $_cart;
    private string $_cartVariable;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        $this->_cart = Commerce::getInstance()->getCarts()->getCart();
        $this->_cartVariable = Commerce::getInstance()->getSettings()->cartVariable;

        $request = Craft::$app->getRequest();

        // Allow passing in a specific Order
        if ($orderId = $request->getParam('orderId')) {
            $this->_cart = Commerce::getInstance()->getOrders()->getOrderById($orderId);
        }
    }

    public function actionAddCode(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $voucherCode = $request->getParam('voucherCode');

        if (!$voucherCode || trim($voucherCode) == '') {
            $this->_cart->addErrors(['voucherCode' => Craft::t('gift-voucher', 'No voucher code provided.')]);

            return $this->_returnCart();
        }

        $error = '';

        // Check to see if this is a Gift Voucher code
        GiftVoucher::$plugin->getCodes()->matchCode($voucherCode, $error);

        if ($error) {
            // Check to see if it's a Coupon code
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
        GiftVoucher::$plugin->getCodeStorage()->add($voucherCode, $this->_cart);

        return $this->_returnCart();
    }

    public function actionRemoveCode(): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $voucherCode = $request->getParam('voucherCode');
        GiftVoucher::$plugin->getCodeStorage()->remove($voucherCode, $this->_cart);

        return $this->_returnCart();
    }


    // Private Methods
    // =========================================================================

    private function _returnCart(): ?Response
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
                    $this->_cartVariable => $this->cartArray($this->_cart),
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                $this->_cartVariable => $this->_cart,
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
                    $this->_cartVariable => $this->cartArray($this->_cart),
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                $this->_cartVariable => $this->_cart,
            ]);

            Craft::$app->getSession()->setError($error);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => !$this->_cart->hasErrors(),
                $this->_cartVariable => $this->cartArray($this->_cart),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('commerce', 'Cart updated.'));

        Craft::$app->getUrlManager()->setRouteParams([
            $this->_cartVariable => $this->_cart,
        ]);

        return $this->redirectToPostedUrl();
    }
}

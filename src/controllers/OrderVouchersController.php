<?php
namespace verbb\giftvoucher\controllers;

use verbb\giftvoucher\GiftVoucher;

use Craft;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class OrderVouchersController extends CartController
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->requirePermission('giftVoucher-manageVouchers');

        parent::init();
    }


    // Protected Methods
    // =========================================================================

    protected function resolveOrder(): Order
    {
        $orderId = filter_var($this->request->getRequiredBodyParam('orderId'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($orderId === false) {
            throw new BadRequestHttpException('Invalid order ID.');
        }

        $order = Commerce::getInstance()->getOrders()->getOrderById($orderId);

        if (!$order) {
            throw new NotFoundHttpException('Order not found.');
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user || !GiftVoucher::$plugin->getOrderPermissions()->canManage($order, $user)) {
            throw new ForbiddenHttpException('You are not authorized to manage vouchers for this order.');
        }

        return $order;
    }
}

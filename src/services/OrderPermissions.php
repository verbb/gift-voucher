<?php
namespace verbb\giftvoucher\services;

use Craft;
use craft\elements\User;

use craft\commerce\elements\Order;

use yii\base\Component;

class OrderPermissions extends Component
{
    // Public Methods
    // =========================================================================

    public function canManage(Order $order, User $user): bool
    {
        if ($order->isCompleted || !$user->can('giftVoucher-manageVouchers')) {
            return false;
        }

        return Craft::$app->getElements()->canSave($order, $user);
    }
}

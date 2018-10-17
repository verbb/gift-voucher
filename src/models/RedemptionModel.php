<?php
namespace verbb\giftvoucher\models;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\base\Model;

use craft\commerce\Plugin as Commerce;

class RedemptionModel extends Model
{
    // Properties
    // =========================================================================

    public $id;
    public $codeId;
    public $orderId;
    public $amount;

    // Public Methods
    // =========================================================================

    public function getCode()
    {
        if ($this->codeId) {
            return Craft::$app->getElements()->getElementById($this->codeId, Code::class);
        }

        return null;
    }

    public function getOrder()
    {
        if ($this->orderId) {
            return Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return null;
    }
    
}

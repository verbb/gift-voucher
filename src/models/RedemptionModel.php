<?php
namespace verbb\giftvoucher\models;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\base\ElementInterface;
use craft\base\Model;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;

class RedemptionModel extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $codeId = null;
    public ?int $orderId = null;
    public ?float $amount = null;

    // Public Methods
    // =========================================================================

    public function getCode(): ?ElementInterface
    {
        if ($this->codeId) {
            return Craft::$app->getElements()->getElementById($this->codeId, Code::class);
        }

        return null;
    }

    public function getOrder(): ?Order
    {
        if ($this->orderId) {
            return Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return null;
    }

}

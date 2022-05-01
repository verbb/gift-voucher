<?php
namespace verbb\giftvoucher\models;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\base\ElementInterface;
use craft\base\Model;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use verbb\giftvoucher\GiftVoucher;

class Redemption extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $codeId = null;
    public ?int $orderId = null;
    public ?float $amount = null;

    // Public Methods
    // =========================================================================

    public function getCode(): ?Code
    {
        if ($this->codeId) {
            return GiftVoucher::$plugin->getCodes()->getCodeById($this->codeId);
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

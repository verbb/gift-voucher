<?php
namespace verbb\giftvoucher\variables;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;

use Craft;
use craft\elements\db\ElementQueryInterface;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

class GiftVoucherVariable
{
    // Public Methods
    // =========================================================================

    public function getPlugin(): GiftVoucher
    {
        return GiftVoucher::$plugin;
    }

    public function getVoucherTypes(): array
    {
        return GiftVoucher::$plugin->getVoucherTypes()->getAllVoucherTypes();
    }

    public function getEditableVoucherTypes(): array
    {
        return GiftVoucher::$plugin->getVoucherTypes()->getEditableVoucherTypes();
    }

    public function vouchers($criteria = null): ElementQueryInterface
    {
        $query = Voucher::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }

    public function codes($criteria = null): ElementQueryInterface
    {
        $query = Code::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }

    public function getVoucherCodes(): array
    {
        $cart = Commerce::getInstance()->getCarts()->getCart();
        return GiftVoucher::$plugin->getCodeStorage()->getCodeKeys($cart);
    }

    public function isVoucher(LineItem $lineItem): bool
    {
        if ($lineItem->purchasable) {
            return $lineItem->purchasable::class === Voucher::class;
        }

        return false;
    }

    public function isVoucherAdjustment($adjuster)
    {
        return $adjuster->sourceSnapshot['codeKey'] ?? false;
    }

    public function getPdfUrl(LineItem $lineItem): ?string
    {
        if ($this->isVoucher($lineItem)) {
            $order = $lineItem->order;

            return GiftVoucher::$plugin->getPdf()->getPdfUrl($order, $lineItem);
        }

        return null;
    }

    public function getOrderPdfUrl(Order $order): string
    {
        return GiftVoucher::$plugin->getPdf()->getPdfUrl($order);
    }
}

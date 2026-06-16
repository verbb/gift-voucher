<?php
namespace verbb\giftvoucher\variables;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\elements\db\VoucherQuery;
use verbb\giftvoucher\elements\db\CodeQuery;

use Craft;
use craft\elements\db\ElementQueryInterface;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\commerce\models\OrderAdjustment;

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

    public function vouchers($criteria = null): VoucherQuery
    {
        $query = Voucher::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }

    public function codes($criteria = null): CodeQuery
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

    public function isVoucherAdjustment(OrderAdjustment $adjustment): bool
    {
        return $adjustment->type === GiftVoucherAdjuster::ADJUSTMENT_TYPE;
    }

    public function getVoucherCodeKey(OrderAdjustment $adjustment): ?string
    {
        if (!$this->isVoucherAdjustment($adjustment)) {
            return null;
        }

        return $adjustment->sourceSnapshot['codeKey'] ?? null;
    }

    public function getPdfUrl(LineItem $lineItem): ?string
    {
        if ($this->isVoucher($lineItem)) {
            $order = $lineItem->order;

            return GiftVoucher::$plugin->getPdf()->getPdfUrl($order, $lineItem);
        }

        return null;
    }

    public function getPdfUrlForCode(Code $code, mixed $option = null): string
    {
        return GiftVoucher::$plugin->getPdf()->getPdfUrlForCode($code, $option);
    }

    public function getOrderPdfUrl(Order $order): string
    {
        return GiftVoucher::$plugin->getPdf()->getPdfUrl($order);
    }
}

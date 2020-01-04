<?php

namespace verbb\giftvoucher\variables;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as Commerce;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\db\CodeQuery;
use verbb\giftvoucher\elements\db\VoucherQuery;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\GiftVoucher;

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

    public function getVoucherCodes()
    {
        $cart = Commerce::getInstance()->getCarts()->getCart();
        return GiftVoucher::getInstance()->getCodeStorage()->getCodeKeys($cart);
    }

    public function isVoucher(LineItem $lineItem)
    {
        return (bool) (get_class($lineItem->purchasable) === Voucher::class);
    }

    public function isVoucherAdjustment($adjuster)
    {
        return $adjuster->sourceSnapshot['codeKey'] ?? false;
    }

    public function isVoucherAdjustment($adjuster)
    {
        return $adjuster->sourceSnapshot['codeKey'] ?? false;
    }

    public function getPdfUrl(LineItem $lineItem)
    {
        if ($this->isVoucher($lineItem)) {
            $order = $lineItem->order;

            return GiftVoucher::$plugin->getPdf()->getPdfUrl($order, $lineItem);
        }

        return null;
    }

    public function getOrderPdfUrl(Order $order)
    {
        return GiftVoucher::$plugin->getPdf()->getPdfUrl($order);
    }
}

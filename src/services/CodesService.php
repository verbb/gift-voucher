<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\models\RedemptionModel;

use Craft;
use craft\events\UserEvent;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

use yii\base\Component;
use yii\base\Event;

class CodesService extends Component
{
    // Public Methods
    // =========================================================================

    public function isCodeKeyUnique(string $codeKey): bool
    {
        return !(bool)Code::findOne(['codeKey' => $codeKey]);
    }

    public static function handlePaidOrder(Event $event)
    {
        $order = $event->sender;
        $lineItems = $order->getLineItems();

        foreach ($lineItems as $lineItem) {
            $itemId = $lineItem->purchasableId;
            $element = Craft::$app->getElements()->getElementById($itemId);
            $quantity = $lineItem->qty;

            if ($element instanceof Voucher) {
                for ($i = 0; $i < $quantity; $i++) {
                   GiftVoucher::getInstance()->getCodes()->codeVoucherByOrder($element, $order, $lineItem);
                }
            }
        }
    }

    public static function handleCompletedOrder(Event $event)
    {
        $order = $event->sender;
        $lineItems = $order->getLineItems();

        // Handle redemption of vouchers (when someone is using a code)
        $giftVoucherCodes = Craft::$app->getSession()->get('giftVoucher.giftVoucherCodes');

        if ($giftVoucherCodes && count($giftVoucherCodes) > 0) {
            foreach ($order->getAdjustments() as $adjustment) {
                if ($adjustment->type === GiftVoucherAdjuster::ADJUSTMENT_TYPE) {
                    $code = null;

                    if (isset($adjustment->sourceSnapshot['codeKey'])) {
                        $codeKey = $adjustment->sourceSnapshot['codeKey'];
                        $code = Code::findOne(['codeKey' => $codeKey]);
                    }

                    if ($code) {
                        $code->currentAmount += $adjustment->amount;
                        Craft::$app->getElements()->saveElement($code);

                        // Track code redemption
                        $redemption = new RedemptionModel();
                        $redemption->codeId = $code->id;
                        $redemption->orderId = $order->id;
                        $redemption->amount = (float)$adjustment->amount * -1;
                        GiftVoucher::$plugin->getRedemptions()->saveRedemption($redemption);
                    }
                }
            }

            // Delete session code 'giftVoucher.giftVoucherCode'
            Craft::$app->getSession()->set('giftVoucher.giftVoucherCodes', null);
        }
    }

    public function codeVoucherByOrder(Voucher $voucher, Order $order, LineItem $lineItem): bool
    {
        $code = new Code();
        $code->voucherId = $voucher->id;
        $code->orderId = $order->id;
        $code->lineItemId = $lineItem->id;

        $code->originalAmount = $lineItem->price;
        $code->currentAmount = $lineItem->price;

        return Craft::$app->getElements()->saveElement($code);
    }

    public function generateCodeKey(): string
    {
        $codeAlphabet = GiftVoucher::getInstance()->getSettings()->codeKeyCharacters;
        $keyLength = GiftVoucher::getInstance()->getSettings()->codeKeyLength;

        $codeKey = '';

        for ($i = 0; $i < $keyLength; $i++) {
            $codeKey .= $codeAlphabet[random_int(0, strlen($codeAlphabet) - 1)];
        }

        return $codeKey;
    }

    public function matchCode($codeKey, &$error)
    {
        $code = Code::findOne(['codeKey' => $codeKey]);

        // Check if valid
        if (!$code) {
            $error = Craft::t('gift-voucher', 'Voucher code is not valid');

            return false;
        }

        // Check if has an amount left
        if ($code->currentAmount <= 0) {
            $error = Craft::t('gift-voucher', 'Voucher code has no amount left');

            return false;
        }

        // Check for expiry date
        $today = new \DateTime();
        if ($code->expiryDate && $code->expiryDate->format('Ymd') < $today->format('Ymd')) {
            $error = Craft::t('gift-voucher', 'Voucher code is out of date');

            return false;
        }

        return true;
    }
}

<?php
// Run against a development Craft application with Commerce and Gift Voucher installed.
$craftPath = getenv('GIFT_VOUCHER_TEST_CRAFT');
$voucherId = (int)getenv('GIFT_VOUCHER_TEST_VOUCHER_ID');
$purchasableId = (int)getenv('GIFT_VOUCHER_TEST_PURCHASABLE_ID');
if (!$craftPath || !$voucherId || !$purchasableId) {
    throw new RuntimeException('Set GIFT_VOUCHER_TEST_CRAFT, GIFT_VOUCHER_TEST_VOUCHER_ID, and GIFT_VOUCHER_TEST_PURCHASABLE_ID for a development site.');
}
require $craftPath . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\gateways\Dummy;
use craft\commerce\models\OrderAdjustment;
use craft\commerce\models\payments\DummyPaymentForm;
use craft\db\Query;
use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\records\Reservation;
use verbb\giftvoucher\storage\Session;

function check(string $name, bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException('Failed: ' . $name);
    }
    echo $name . ": PASS\n";
}

$plugin = GiftVoucher::$plugin;
$commerce = Commerce::getInstance();
$originalStorage = $plugin->getCodeStorage();
$storage = new class extends Session {
    public array $keys = [];

    public function getCodeKeys(Order $order): array
    {
        return $this->keys;
    }
};
$plugin->set('codeStorage', $storage);
$gateway = null;
foreach ($commerce->getGateways()->getAllGateways() as $candidate) {
    if ($candidate instanceof Dummy) {
        $gateway = $candidate;
        break;
    }
}
if (!$gateway) {
    throw new RuntimeException('The development site must have a Dummy gateway.');
}

// Keep fixtures, order completion, ledger writes, and queued jobs inside one rollback.
$transaction = Craft::$app->getDb()->beginTransaction();
try {
    $newCode = static function(float $amount) use ($voucherId): Code {
        $code = new Code([
            'voucherId' => $voucherId,
            'codeKey' => 'ZERO-CHECKOUT-' . bin2hex(random_bytes(8)),
            'originalAmount' => $amount,
            'currentAmount' => $amount,
            'enabled' => true,
        ]);
        if (!Craft::$app->getElements()->saveElement($code)) {
            throw new RuntimeException(json_encode($code->getErrors()));
        }
        return $code;
    };
    $newOrder = static function(array $codes, float $price = 100) use ($commerce, $gateway, $storage, $purchasableId): Order {
        $storage->keys = array_map(static fn(Code $code): string => $code->codeKey, $codes);
        $order = new Order([
            'number' => bin2hex(random_bytes(16)),
            'storeId' => $commerce->getStores()->getPrimaryStore()->id,
            'gatewayId' => $gateway->id,
            'email' => 'zero-value-checkout@example.test',
            'suppressEmails' => true,
        ]);
        $item = $commerce->getLineItems()->create($order, ['purchasableId' => $purchasableId]);
        // Fix the line price per scenario while running the real order adjusters.
        $item->setPrice($price);
        $item->setPromotionalPrice(null);
        $order->setRecalculationMode(Order::RECALCULATION_MODE_ADJUSTMENTS_ONLY);
        $order->addLineItem($item);
        if (!Craft::$app->getElements()->saveElement($order)) {
            throw new RuntimeException(json_encode($order->getErrors()));
        }
        // Payment consumes persisted adjustments even when browser code storage has gone away.
        $order = Order::find()->id($order->id)->one();
        $order->setRecalculationMode(Order::RECALCULATION_MODE_NONE);
        $storage->keys = [];
        return $order;
    };
    $pay = static function(Order $order) use ($commerce): void {
        $form = new DummyPaymentForm();
        $form->setAttributes(['firstName' => 'Test', 'lastName' => 'Shopper', 'number' => '4242424242424242', 'expiry' => '12/2030', 'cvv' => '123'], false);
        $redirect = null;
        $payment = null;
        $commerce->getPayments()->processPayment($order, $form, $redirect, $payment);
    };
    $balance = static fn(Code $code): float => (float)(new Query())->select('currentAmount')->from('{{%giftvoucher_codes}}')->where(['id' => $code->id])->scalar();
    $redemptions = static fn(Order $order): int => (int)(new Query())->from('{{%giftvoucher_redemptions}}')->where(['orderId' => $order->id])->count();

    foreach ([
        'exact' => [[100], [0]],
        'excess' => [[150], [50]],
        'unused extra' => [[150, 50], [50, 50]],
        'split exact' => [[40, 60], [0, 0]],
        'split plus unused extra' => [[40, 60, 50], [0, 0, 50]],
    ] as $name => [$amounts, $expectedBalances]) {
        $codes = array_map($newCode, $amounts);
        $order = $newOrder($codes);
        check($name . ': voucher covers the order', $order->getTotalPrice() === 0.0);
        $adjustments = array_values(array_filter($order->getAdjustments(), static fn($a): bool => $a->type === 'voucher'));
        if (str_contains($name, 'unused extra')) {
            check($name . ': persisted unused adjustment is zero', (float)end($adjustments)->amount === 0.0);
        }
        $pay($order);
        check($name . ': Commerce completes the order', $order->isCompleted);
        $expectedRedemptions = 0;
        foreach ($codes as $index => $code) {
            $spent = $amounts[$index] - $expectedBalances[$index];
            $expectedRedemptions += $spent > 0 ? 1 : 0;
            check($name . ': balance ' . $index, $balance($code) === (float)$expectedBalances[$index]);
            check($name . ': reservation ' . $index, (int)Reservation::find()->where(['orderId' => $order->id, 'codeId' => $code->id, 'status' => 'spent'])->count() === ($spent > 0 ? 1 : 0));
        }
        $count = $redemptions($order);
        check($name . ': only used vouchers are redeemed', $count === $expectedRedemptions);
        $plugin->getReservations()->redeemOrder($order);
        check($name . ': redemption replay is idempotent', $redemptions($order) === $count && array_sum(array_map($balance, $codes)) === (float)(array_sum($amounts) - 100));
    }

    $code = $newCode(40);
    $order = $newOrder([$code]);
    $pay($order);
    check('Partial voucher: dummy payment completes the order', $order->isCompleted && $order->getTotalPaid() > 0);
    check('Partial voucher: value debited once', $balance($code) === 0.0 && $redemptions($order) === 1);

    $code = $newCode(100);
    $order = $newOrder([$code], 0);
    $pay($order);
    check('Already-free order: completes without voucher accounting', $order->isCompleted && $balance($code) === 100.0 && $redemptions($order) === 0 && !(bool)Reservation::find()->where(['orderId' => $order->id])->exists());

    $code = $newCode(100);
    $order = $newOrder([$code]);
    $plugin->getReservations()->reserveOrder($order, true);
    try {
        $plugin->getReservations()->reserveOrder($order, true);
        throw new LogicException('Overlapping payment claim was accepted.');
    } catch (RuntimeException $e) {
        check('Overlapping payment claims still blocked', str_contains($e->getMessage(), 'Another payment attempt'));
    }
    $otherOrder = $newOrder([]);
    $adjustment = new OrderAdjustment(['type' => 'voucher', 'amount' => -100, 'sourceSnapshot' => ['id' => $code->id, 'codeKey' => $code->codeKey]]);
    $otherOrder->setAdjustments([$adjustment]);
    try {
        $plugin->getReservations()->reserveOrder($otherOrder);
        throw new LogicException('Held value was reused.');
    } catch (RuntimeException $e) {
        check('Other orders cannot spend held value', str_contains($e->getMessage(), 'enough available value'));
    }
    foreach (['positive amount' => ['amount' => 10], 'wrong identity' => ['amount' => 0, 'sourceSnapshot' => ['id' => $code->id, 'codeKey' => 'wrong']]] as $name => $changes) {
        $adjustment->setAttributes($changes, false);
        try {
            $plugin->getReservations()->reserveOrder($otherOrder);
            throw new LogicException('Invalid voucher adjustment accepted.');
        } catch (RuntimeException $e) {
            check($name . ': invalid adjustment rejected', str_contains($e->getMessage(), 'saved gift voucher adjustment'));
        }
    }
} finally {
    $plugin->set('codeStorage', $originalStorage);
    $transaction->rollBack();
}

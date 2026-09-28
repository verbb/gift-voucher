<?php
namespace verbb\giftvoucher\services;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\adjusters\GiftVoucherAdjuster;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\models\Redemption;
use verbb\giftvoucher\records\Reservation;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Db;

use yii\base\Event;
use yii\db\Expression;
use yii\db\Transaction;

use DateTime;
use RuntimeException;
use Throwable;

use craft\commerce\Plugin as Commerce;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\events\ProcessPaymentEvent;
use craft\commerce\events\TransactionEvent;
use craft\commerce\records\Transaction as TransactionRecord;

class Reservations extends Component
{
    // Constants
    // =========================================================================

    public const STATUS_HELD = 'held';
    public const STATUS_REVIEW = 'review';
    public const STATUS_SPENT = 'spent';
    public const STATUS_RELEASED = 'released';


    // Public Methods
    // =========================================================================

    public function getActiveReservationsByCodeId(int $codeId): array
    {
        return Reservation::find()
            ->where(['codeId' => $codeId])
            ->andWhere(['status' => $this->_activeStatuses()])
            ->orderBy(['dateCreated' => SORT_ASC])
            ->all();
    }

    public function getActiveReservationById(int $reservationId): ?Reservation
    {
        return Reservation::find()
            ->where(['id' => $reservationId])
            ->andWhere(['status' => $this->_activeStatuses()])
            ->one();
    }

    public function getAvailableAmount(Code $code, ?Order $order = null): float
    {
        if (!$code->id) {
            return (float)$code->currentAmount;
        }

        $heldAmount = $this->getHeldAmount($code->id);

        if ($order?->id) {
            $reservation = $this->_getOrderReservation($order, $code->id);

            if ($reservation && $this->_isActive($reservation)) {
                $heldAmount -= (float)$reservation->amount;
            }
        }

        return round((float)$code->currentAmount - $heldAmount, 2);
    }

    public function getHeldAmount(int $codeId): float
    {
        return round((float)(new Query())
            ->from('{{%giftvoucher_reservations}}')
            ->where([
                'codeId' => $codeId,
                'status' => $this->_activeStatuses(),
            ])
            ->sum('amount'), 2);
    }

    public function reserveOrder(Order $order, bool $claimPayment = false): void
    {
        if (!$order->id || !$order->number) {
            throw new RuntimeException('Gift voucher value cannot be held for an unsaved order.');
        }

        $adjustments = $this->_getVoucherAdjustments($order);
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction(Transaction::READ_COMMITTED);

        try {
            $existingReservations = $this->_getActiveOrderReservations($order);
            $codeIds = array_unique(array_merge(array_keys($adjustments), array_keys($existingReservations)));
            sort($codeIds, SORT_NUMERIC);

            foreach ($codeIds as $codeId) {
                $this->_lockCode((int)$codeId);
            }

            // Re-read after locking so a concurrent checkout cannot pass the same balance check.
            $existingReservations = $this->_getActiveOrderReservations($order);

            if ($claimPayment && $existingReservations) {
                throw new RuntimeException('Another payment attempt is already using this order’s gift voucher hold.');
            }

            foreach ($existingReservations as $codeId => $reservation) {
                $amount = $adjustments[$codeId]['amount'] ?? null;

                if ($amount === null || !$this->_amountsMatch((float)$reservation->amount, $amount)) {
                    throw new RuntimeException('This order has a gift voucher hold that no longer matches its saved discount.');
                }
            }

            foreach ($adjustments as $codeId => $adjustment) {
                if (isset($existingReservations[$codeId])) {
                    continue;
                }

                $existingReservation = Reservation::findOne([
                    'codeId' => $codeId,
                    'orderNumber' => $order->number,
                ]);

                if ($existingReservation?->status === self::STATUS_SPENT) {
                    if (!$this->_amountsMatch((float)$existingReservation->amount, $adjustment['amount'])) {
                        throw new RuntimeException('A completed gift voucher redemption no longer matches its saved discount.');
                    }

                    continue;
                }

                $currentAmount = (float)(new Query())
                    ->select('currentAmount')
                    ->from('{{%giftvoucher_codes}}')
                    ->where(['id' => $codeId])
                    ->scalar();
                $heldAmount = (float)(new Query())
                    ->from('{{%giftvoucher_reservations}}')
                    ->where([
                        'codeId' => $codeId,
                        'status' => $this->_activeStatuses(),
                    ])
                    ->sum('amount');
                $availableAmount = round($currentAmount - $heldAmount, 2);

                if (!$this->_hasEnoughBalance($availableAmount, $adjustment['amount'])) {
                    throw new RuntimeException('A gift voucher no longer has enough available value for this order.');
                }

                $reservation = $existingReservation ?? new Reservation();

                $reservation->codeId = $codeId;
                $reservation->orderId = $order->id;
                $reservation->orderNumber = $order->number;
                $reservation->amount = $adjustment['amount'];
                $reservation->status = self::STATUS_HELD;
                $reservation->transactionHash = null;
                $reservation->message = null;
                $reservation->dateResolved = null;
                $reservation->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function redeemOrder(Order $order): void
    {
        $this->reserveOrder($order);

        $adjustments = $this->_getVoucherAdjustments($order);

        if (!$adjustments) {
            return;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction(Transaction::READ_COMMITTED);

        try {
            $codeIds = array_keys($adjustments);
            sort($codeIds, SORT_NUMERIC);

            foreach ($codeIds as $codeId) {
                $this->_lockCode((int)$codeId);
            }

            $reservations = $this->_getActiveOrderReservations($order);

            foreach ($adjustments as $codeId => $adjustment) {
                $reservation = $reservations[$codeId] ?? Reservation::findOne([
                    'codeId' => $codeId,
                    'orderNumber' => $order->number,
                ]);

                if ($reservation?->status === self::STATUS_SPENT) {
                    $redemptionExists = (new Query())
                        ->from('{{%giftvoucher_redemptions}}')
                        ->where(['reservationId' => $reservation->id])
                        ->exists();

                    if ($redemptionExists && $this->_amountsMatch((float)$reservation->amount, $adjustment['amount'])) {
                        continue;
                    }
                }

                if (!$reservation || !$this->_isActive($reservation) || !$this->_amountsMatch((float)$reservation->amount, $adjustment['amount'])) {
                    throw new RuntimeException('Gift voucher redemption does not match the value held for this order.');
                }

                $redemptionExists = (new Query())
                    ->from('{{%giftvoucher_redemptions}}')
                    ->where(['reservationId' => $reservation->id])
                    ->exists();

                if (!$redemptionExists) {
                    $currentAmount = (float)(new Query())
                        ->select('currentAmount')
                        ->from('{{%giftvoucher_codes}}')
                        ->where(['id' => $codeId])
                        ->scalar();

                    if (!$this->_hasEnoughBalance($currentAmount, (float)$reservation->amount)) {
                        throw new RuntimeException('A gift voucher no longer has enough balance to complete its held redemption.');
                    }

                    $this->_updateCodeBalance($codeId, (float)$reservation->amount * -1);

                    $redemption = new Redemption();
                    $redemption->reservationId = $reservation->id;
                    $redemption->codeId = $codeId;
                    $redemption->orderId = $order->id;
                    $redemption->amount = $adjustment['amount'];

                    if (!GiftVoucher::$plugin->getRedemptions()->saveRedemption($redemption)) {
                        throw new RuntimeException('Unable to save the gift voucher redemption.');
                    }
                }

                $reservation->status = self::STATUS_SPENT;
                $reservation->message = null;
                $reservation->dateResolved = Db::prepareDateForDb(new DateTime());
                $reservation->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function releaseReservation(int $reservationId, string $message): bool
    {
        $reservation = Reservation::findOne($reservationId);

        if (!$reservation || !$this->_isActive($reservation)) {
            return false;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction(Transaction::READ_COMMITTED);

        try {
            $this->_lockCode((int)$reservation->codeId);
            $reservation->refresh();

            if (!$this->_isActive($reservation)) {
                $transaction->rollBack();
                return false;
            }

            $reservation->status = self::STATUS_RELEASED;
            $reservation->message = $message;
            $reservation->dateResolved = Db::prepareDateForDb(new DateTime());
            $reservation->save(false);

            $transaction->commit();

            return true;
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function handleBeforeProcessPayment(ProcessPaymentEvent $event): void
    {
        try {
            // Claim a new hold atomically so two gateway requests cannot overlap before Commerce persists either transaction.
            $this->reserveOrder($event->order, true);
        } catch (Throwable $e) {
            $event->order->addError('giftVoucher', Craft::t('gift-voucher', 'Unable to reserve the applied gift voucher value. Please review the order and try again.'));
            $event->isValid = false;

            GiftVoucher::error('Unable to hold gift voucher value for order {id}: {message}', [
                'id' => $event->order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function handleAfterProcessPayment(ProcessPaymentEvent $event): void
    {
        $this->_recordTransaction($event->order, $event->transaction->hash ?? null);

        if ($this->_isDefinitiveFailure($event->response) && !$this->_hasOtherLivePayment($event->order, $event->transaction->id)) {
            $this->_releaseOrderReservations($event->order, 'Payment was declined before completion.');
        }
    }

    public function handleAfterCompletePayment(TransactionEvent $event): void
    {
        $transaction = $event->transaction;
        $children = Commerce::getInstance()->getTransactions()->getChildrenByTransactionId($transaction->id);
        usort($children, static fn(mixed $first, mixed $second): int => $first->id <=> $second->id);
        $child = $children ? end($children) : null;

        if ($child && $child->status === TransactionRecord::STATUS_FAILED && !$this->_hasOtherLivePayment($transaction->getOrder(), $transaction->id)) {
            $this->_releaseOrderReservations($transaction->getOrder(), 'Off-site payment was declined before completion.');
        }
    }

    public function handleAfterSaveTransaction(TransactionEvent $event): void
    {
        $transaction = $event->transaction;
        $order = $transaction->getOrder();

        if ($transaction->status === TransactionRecord::STATUS_FAILED) {
            $this->_markOrderForReview($order, $transaction->hash, $transaction->message);
            return;
        }

        $this->_recordTransaction($order, $transaction->hash);
    }

    public function handleBeforeCompleteOrder(Event $event): void
    {
        /** @var Order $order */
        $order = $event->sender;
        $this->reserveOrder($order);
    }


    // Private Methods
    // =========================================================================

    private function _getVoucherAdjustments(Order $order): array
    {
        $adjustments = [];

        foreach ($order->getAdjustments() as $adjustment) {
            if ($adjustment->type !== GiftVoucherAdjuster::ADJUSTMENT_TYPE) {
                continue;
            }

            $snapshot = $adjustment->sourceSnapshot;
            $codeId = filter_var($snapshot['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $codeKey = $snapshot['codeKey'] ?? null;
            $amount = round((float)$adjustment->amount * -1, 2);

            if (!$codeId || !is_string($codeKey) || $codeKey === '' || $amount <= 0) {
                throw new RuntimeException('A saved gift voucher adjustment is missing its code identity or amount.');
            }

            $storedCodeKey = (new Query())
                ->select('codeKey')
                ->from('{{%giftvoucher_codes}}')
                ->where(['id' => $codeId])
                ->scalar();

            if (!is_string($storedCodeKey) || !hash_equals($storedCodeKey, $codeKey)) {
                throw new RuntimeException('A saved gift voucher adjustment no longer matches its code.');
            }

            $adjustments[$codeId] ??= [
                'amount' => 0.0,
                'codeKey' => $codeKey,
            ];
            $adjustments[$codeId]['amount'] = round($adjustments[$codeId]['amount'] + $amount, 2);
        }

        return $adjustments;
    }

    private function _getActiveOrderReservations(Order $order): array
    {
        $reservations = Reservation::find()
            ->where(['orderNumber' => $order->number])
            ->andWhere(['status' => $this->_activeStatuses()])
            ->all();

        $indexed = [];

        foreach ($reservations as $reservation) {
            $indexed[(int)$reservation->codeId] = $reservation;
        }

        return $indexed;
    }

    private function _getOrderReservation(Order $order, int $codeId): ?Reservation
    {
        if (!$order->number) {
            return null;
        }

        return Reservation::findOne([
            'codeId' => $codeId,
            'orderNumber' => $order->number,
        ]);
    }

    private function _releaseOrderReservations(Order $order, string $message): void
    {
        foreach ($this->_getActiveOrderReservations($order) as $reservation) {
            $this->releaseReservation((int)$reservation->id, $message);
        }
    }

    private function _markOrderForReview(Order $order, ?string $transactionHash, ?string $message): void
    {
        Reservation::updateAll([
            'status' => self::STATUS_REVIEW,
            'transactionHash' => $transactionHash,
            'message' => $message ?: 'The payment result was ambiguous and requires review.',
        ], [
            'orderNumber' => $order->number,
            'status' => $this->_activeStatuses(),
        ]);
    }

    private function _recordTransaction(Order $order, ?string $transactionHash): void
    {
        if (!$transactionHash) {
            return;
        }

        Reservation::updateAll([
            'transactionHash' => $transactionHash,
        ], [
            'orderNumber' => $order->number,
            'status' => $this->_activeStatuses(),
        ]);
    }

    private function _lockCode(int $codeId): void
    {
        Craft::$app->getDb()->createCommand()
            ->update('{{%giftvoucher_codes}}', [
                'currentAmount' => new Expression('[[currentAmount]]'),
            ], ['id' => $codeId])
            ->execute();

        $exists = (new Query())
            ->from('{{%giftvoucher_codes}}')
            ->where(['id' => $codeId])
            ->exists();

        if (!$exists) {
            throw new RuntimeException('Unable to lock the gift voucher balance.');
        }
    }

    private function _updateCodeBalance(int $codeId, float $change): void
    {
        Craft::$app->getDb()->createCommand()
            ->update('{{%giftvoucher_codes}}', [
                'currentAmount' => new Expression('[[currentAmount]] + :change', [':change' => $change]),
                'dateUpdated' => Db::prepareDateForDb(new DateTime()),
            ], ['id' => $codeId])
            ->execute();
    }

    private function _hasEnoughBalance(float $available, float $required): bool
    {
        return round($available - $required, 2) >= 0;
    }

    private function _amountsMatch(float $first, float $second): bool
    {
        return abs(round($first - $second, 2)) < 0.01;
    }

    private function _activeStatuses(): array
    {
        return [self::STATUS_HELD, self::STATUS_REVIEW];
    }

    private function _isActive(Reservation $reservation): bool
    {
        return in_array($reservation->status, $this->_activeStatuses(), true);
    }

    private function _isDefinitiveFailure(RequestResponseInterface $response): bool
    {
        return !$response->isSuccessful() && !$response->isProcessing() && !$response->isRedirect();
    }

    private function _hasOtherLivePayment(Order $order, ?int $excludedTransactionId): bool
    {
        $transactions = Commerce::getInstance()->getTransactions()->getAllTopLevelTransactionsByOrderId($order->id);

        foreach ($transactions as $transaction) {
            if ($transaction->id === $excludedTransactionId) {
                continue;
            }

            // A separate attempt may still settle, or may already have settled before order completion finished.
            if (in_array($transaction->status, [TransactionRecord::STATUS_PENDING, TransactionRecord::STATUS_REDIRECT, TransactionRecord::STATUS_PROCESSING, TransactionRecord::STATUS_SUCCESS], true)) {
                return true;
            }
        }

        return false;
    }
}

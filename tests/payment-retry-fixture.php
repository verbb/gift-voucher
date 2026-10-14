<?php
namespace {
    $vendor = getenv('GIFT_VOUCHER_TEST_VENDOR') ?: dirname(__DIR__) . '/vendor';
    require $vendor . '/yiisoft/yii2/Yii.php';

    set_error_handler(static function(int $severity, string $message, string $file, int $line): never {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
}

namespace craft\base {
    class Component extends \yii\base\Component
    {
    }
}

namespace {
    use verbb\giftvoucher\services\Reservations;

    require __DIR__ . '/../src/services/Reservations.php';

    function check(string $name, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('Failed: ' . $name);
        }

        echo $name . ": PASS\n";
    }

    function reservation(string $status, ?string $transactionHash): object
    {
        return (object)[
            'status' => $status,
            'transactionHash' => $transactionHash,
        ];
    }

    $service = new Reservations();
    $method = new ReflectionMethod($service, '_hasBlockingPaymentClaim');

    check('A new order has no blocking payment claim', !$method->invoke($service, []));
    check('An unrecorded payment claim blocks an overlapping request', $method->invoke($service, [reservation(Reservations::STATUS_HELD, null)]));
    check('A recorded incomplete attempt allows the order to retry', !$method->invoke($service, [reservation(Reservations::STATUS_HELD, 'recorded-transaction')]));
    check('A payment marked for review still blocks retries', $method->invoke($service, [reservation(Reservations::STATUS_REVIEW, 'ambiguous-transaction')]));
    check('Any unrecorded claim blocks a multi-voucher retry', $method->invoke($service, [
        reservation(Reservations::STATUS_HELD, 'recorded-transaction'),
        reservation(Reservations::STATUS_HELD, null),
    ]));
}

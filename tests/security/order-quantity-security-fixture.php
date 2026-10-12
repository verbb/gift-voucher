<?php
namespace {
    // Use the Craft application's installed Yii so cancellation and validation use real event dispatch.
    $vendor = getenv('GIFT_VOUCHER_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
    require $vendor . '/yiisoft/yii2/Yii.php';

    set_error_handler(static function(int $severity, string $message, string $file, int $line): never {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    class Craft
    {
        public static function t(string $category, string $message, array $params = []): string
        {
            foreach ($params as $key => $value) {
                $message = str_replace('{' . $key . '}', (string)$value, $message);
            }

            return $message;
        }
    }
}

namespace craft\base {
    class Model extends \yii\base\Model
    {
        public function rules(): array
        {
            return $this->defineRules();
        }

        protected function defineRules(): array
        {
            return [];
        }
    }
}

namespace craft\commerce\enums {
    enum LineItemType
    {
        case Purchasable;
        case Custom;
    }
}

namespace craft\commerce\models {
    class LineItem
    {
        public int $id = 1;
        public float $price = 0;

        public function __construct(
            public int $qty,
            public mixed $purchasable,
            public \craft\commerce\enums\LineItemType $type = \craft\commerce\enums\LineItemType::Purchasable,
        ) {
        }

        public function getPurchasable(): mixed
        {
            if ($this->type === \craft\commerce\enums\LineItemType::Custom) {
                throw new \RuntimeException('Custom line items do not have purchasables.');
            }

            return $this->purchasable;
        }
    }
}

namespace craft\commerce\elements {
    class Order extends \yii\base\Model
    {
        public bool $isCompleted = false;
        public array $lineItems = [];
        public static array $orders = [];

        public function getLineItems(): array
        {
            return $this->lineItems;
        }

        public static function find(): object
        {
            return new class {
                private mixed $number;

                public function number(mixed $number): static
                {
                    $this->number = $number;
                    return $this;
                }

                public function isCompleted(bool $completed): static
                {
                    return $this;
                }

                public function one(): ?Order
                {
                    return Order::$orders[$this->number] ?? null;
                }
            };
        }
    }
}

namespace craft\commerce\events {
    class ProcessPaymentEvent extends \yii\base\Event
    {
        public bool $isValid = true;
        public \craft\commerce\elements\Order $order;
    }
}

namespace craft\commerce {
    class Plugin
    {
        public static mixed $instance;

        public static function getInstance(): mixed
        {
            return self::$instance;
        }
    }
}

namespace verbb\giftvoucher {
    class GiftVoucher
    {
        public static mixed $plugin;

        public static function info(string $message, array $params = []): void
        {
        }

        public static function error(string $message, array $params = []): void
        {
            throw new \RuntimeException($message);
        }
    }
}

namespace verbb\giftvoucher\elements {
    class Voucher
    {
    }
}

namespace {
    use craft\commerce\elements\Order;
    use craft\commerce\enums\LineItemType;
    use craft\commerce\events\ProcessPaymentEvent;
    use craft\commerce\models\LineItem;
    use craft\commerce\Plugin as Commerce;
    use verbb\giftvoucher\elements\Voucher;
    use verbb\giftvoucher\GiftVoucher;
    use verbb\giftvoucher\models\Settings;
    use verbb\giftvoucher\services\Codes;
    use yii\base\Action;
    use yii\base\ActionEvent;
    use yii\base\Component;
    use yii\base\Event;

    require __DIR__ . '/../../src/models/Settings.php';
    require __DIR__ . '/../../src/services/Codes.php';

    function check(string $name, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException('Failed: ' . $name);
        }

        echo $name . ": PASS\n";
    }

    function order(array $quantities): Order
    {
        $order = new Order();
        $order->lineItems = array_map(fn(int $qty) => new LineItem($qty, new Voucher()), $quantities);
        return $order;
    }

    $codes = new class extends Codes {
        public int $issued = 0;

        public function codeVoucherByOrder(Voucher $voucher, Order $order, LineItem $lineItem): bool
        {
            $this->issued++;
            return true;
        }
    };
    $plugin = new class($codes) {
        public Settings $settings;
        public int $settled = 0;
        public int $cleared = 0;

        public function __construct(public Codes $codes)
        {
            $this->settings = new Settings();
        }

        public function getSettings(): Settings { return $this->settings; }
        public function getCodes(): Codes { return $this->codes; }
        public function getReservations(): static { return $this; }
        public function getCodeStorage(): static { return $this; }
        public function redeemOrder(Order $order): void { $this->settled++; }
        public function setCodes(array $codes, Order $order): void { $this->cleared++; }
    };
    GiftVoucher::$plugin = $plugin;

    // On the unfixed revision there is no quantity listener and the excessive order validates.
    if (method_exists($codes, 'handleValidateOrder')) {
        Event::on(Order::class, Order::EVENT_BEFORE_VALIDATE, [$codes, 'handleValidateOrder']);
    }

    check('An ordinary order validates', order([2])->validate());
    check('An excessive zero-price voucher quantity is rejected', !order([100000])->validate());
    check('Default limit is 500', $plugin->settings->maxVoucherCodesPerOrder === 500);
    check('Exactly 500 vouchers are allowed', order([500])->validate());
    check('501 vouchers are rejected', !order([501])->validate());
    check('The limit covers all products and option rows', !order([250, 251])->validate());
    check('Two rows totalling the limit are allowed', order([250, 250])->validate());
    $updated = order([250, 250]);
    $updated->lineItems[0]->qty++;
    check('Updating an existing quantity cannot bypass the limit', !$updated->validate());
    check('Negative quantities cannot offset an excessive quantity', !order([-100, 501])->validate());
    check('Very large quantities do not overflow the counter', !order([PHP_INT_MAX, PHP_INT_MAX])->validate());
    $mixed = order([500]);
    $mixed->lineItems[] = new LineItem(PHP_INT_MAX, new stdClass());
    $mixed->lineItems[] = new LineItem(PHP_INT_MAX, null, LineItemType::Custom);
    check('Non-voucher and custom line items do not consume the limit', $mixed->validate());
    $plugin->settings->maxVoucherCodesPerOrder = 600;
    check('A configured larger limit is honoured', order([600])->validate());
    $plugin->settings->maxVoucherCodesPerOrder = 1;
    check('A configured smaller limit is honoured', !order([2])->validate());
    foreach ([0, -1] as $invalidLimit) {
        $plugin->settings->maxVoucherCodesPerOrder = $invalidLimit;
        check('Invalid limit fails settings validation: ' . $invalidLimit, !$plugin->settings->validate());
    }
    $plugin->settings->maxVoucherCodesPerOrder = 500;

    $payments = new Component();
    $reservationCalls = 0;
    $payments->on('beforeProcessPayment', [$codes, 'handleBeforeProcessPayment']);
    $payments->on('beforeProcessPayment', function() use (&$reservationCalls) { $reservationCalls++; });
    $rejected = new ProcessPaymentEvent(['order' => order([501])]);
    $payments->trigger('beforeProcessPayment', $rejected);
    check('Payment is cancelled before subsequent reservation handling', !$rejected->isValid && $reservationCalls === 0);
    check('Rejected payment carries a useful order error', str_contains($rejected->order->getFirstError('giftVoucher'), '500'));
    $accepted = new ProcessPaymentEvent(['order' => order([500])]);
    $payments->trigger('beforeProcessPayment', $accepted);
    check('Allowed payment still reaches reservations', $accepted->isValid && $reservationCalls === 1);

    $historic = order([601]);
    $historic->isCompleted = true;
    check('Historic completed orders remain valid', $historic->validate());
    $payments->trigger('beforeProcessPayment', $historicPayment = new ProcessPaymentEvent(['order' => $historic]));
    check('Outstanding payments on completed orders remain possible', $historicPayment->isValid && $reservationCalls === 2);

    $sessionCart = order([501]);
    Commerce::$instance = new class($sessionCart) {
        public function __construct(public Order $cart) {}
        public function getCarts(): static { return $this; }
        public function getCart(): Order { return $this->cart; }
    };
    $controller = new class extends Component {
        public object $request;
    };
    $controller->request = new class {
        public array $body = [];
        public bool $post = true;
        public function getBodyParam(string $name): mixed { return $this->body[$name] ?? null; }
        public function getIsPost(): bool { return $this->post; }
    };
    $controller->on('beforeAction', [$codes, 'handleBeforeCartAction']);
    $dispatch = function(string $action = 'complete') use ($controller): void {
        $controller->trigger('beforeAction', new ActionEvent(new Action($action, $controller)));
    };
    foreach (['session', 'number'] as $source) {
        $controller->request->body = $source === 'number' ? ['number' => 'secret-order-number'] : [];
        Order::$orders['secret-order-number'] = order([501]);
        try {
            $dispatch();
            check('Oversized ' . $source . ' cart is rejected before completion', false);
        } catch (\yii\web\BadRequestHttpException $e) {
            check('Oversized ' . $source . ' cart is rejected before completion', str_contains($e->getMessage(), '500'));
        }
    }
    $dispatch('update-cart');
    check('Cart updates remain available to reduce excessive quantities', true);
    Order::$orders['secret-order-number'] = order([500]);
    $dispatch();
    check('An allowed posted-number checkout can complete', true);
    $controller->request->body = [];
    Commerce::$instance->cart = order([500]);
    $dispatch();
    check('An allowed session checkout can complete', true);

    // Completion itself stays untouched, including callbacks for payments begun before the limit changed.
    Codes::handleCompletedOrder(new Event(['sender' => $historic]));
    check('An already-started oversized order receives every purchased code', $codes->issued === 601);
    check('Completion still settles applied value and clears storage once', $plugin->settled === 1 && $plugin->cleared === 1);
}

<?php
namespace {
    $vendor = getenv('GIFT_VOUCHER_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
    require $vendor . '/yiisoft/yii2/Yii.php';
    set_error_handler(static function($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    class Craft
    {
        public static mixed $app;
        public static function t($category, $message): string { return $message; }
    }
}

namespace craft\base {
    class Component extends \yii\base\Component {}
    class Element { public const STATUS_ENABLED = 'enabled'; }
}

namespace craft\elements\db {
    class ElementQuery
    {
        public \yii\db\Query $query;
        public \yii\db\Query $subQuery;
        public function __construct($type, $config = [])
        {
            $this->query = new \yii\db\Query();
            $this->subQuery = new \yii\db\Query();
        }
        protected function beforePrepare(): bool { return true; }
        protected function joinElementTable($table): void {}
    }
}

namespace craft\helpers {
    class Session
    {
        public static array $data = [];
        public static function exists(): bool { return true; }
        public static function get($key, $default) { return self::$data[$key] ?? $default; }
        public static function set($key, $value): void { self::$data[$key] = $value; }
    }
}

namespace craft\commerce\elements {
    class Order
    {
        public int $id = 9;
        public array $errors = [];
        public array $ids = [];
        public ?string $couponCode = null;
        public function addErrors($errors): void { $this->errors += $errors; }
        public function hasErrors(): bool { return $this->errors !== []; }
        public function getErrors(): array { return $this->errors; }
        public function validate(): bool { return true; }
        public function setFieldValue($handle, $ids): void { $this->ids = $ids; }
        public function getFieldValue($handle): object
        {
            return new class($this->ids) {
                public function __construct(private array $ids) {}
                public function ids(): array { return $this->ids; }
                public function all(): array { return array_map(fn($id) => \verbb\giftvoucher\elements\Code::$records[$id], $this->ids); }
            };
        }
    }
}

namespace craft\commerce\controllers {
    class BaseFrontEndController
    {
        public mixed $request;
        public function init(): void {}
        protected function requirePostRequest(): void {}
        protected function requirePermission($permission): void {}
        protected function asJson($data): \yii\web\Response { return new \yii\web\Response(['data' => $data, 'charset' => 'UTF-8']); }
        protected function cartArray($cart): array { return []; }
    }
}

namespace craft\commerce {
    class Plugin
    {
        public static mixed $instance;
        public static function getInstance(): mixed { return self::$instance; }
    }
}

namespace verbb\giftvoucher {
    class GiftVoucher { public static mixed $plugin; }
}

namespace verbb\giftvoucher\elements {
    class Code
    {
        public static array $records = [];
        public static array $lookups = [];
        public ?\DateTime $expiryDate = null;
        public int $available = 10;
        public function __construct(public int $id, public string $codeKey) {}
        public static function findOne($criteria): ?self
        {
            self::$lookups[] = $criteria;
            if (!is_array($criteria)) {
                return self::$records[$criteria] ?? null;
            }
            // This fixture uses literal key comparison; database coercion is checked separately.
            foreach (self::$records as $code) {
                if ($code->codeKey === (string)$criteria['codeKey']) {
                    return $code;
                }
            }
            return null;
        }
    }
}

namespace {
    use craft\commerce\elements\Order;
    use craft\commerce\Plugin as Commerce;
    use verbb\giftvoucher\elements\Code;
    use verbb\giftvoucher\GiftVoucher;

    foreach (['controllers/CartController', 'controllers/OrderVouchersController', 'services/Codes', 'helpers/CodeHelper', 'storage/CodeStorageInterface', 'storage/Session', 'storage/Order', 'elements/db/CodeQuery'] as $file) {
        require __DIR__ . '/../../src/' . $file . '.php';
    }

    class FixtureRequest
    {
        public function __construct(public mixed $value) {}
        public function getParam($name): mixed { return $name === 'voucherCode' ? $this->value : null; }
        public function getRequiredBodyParam($name): int { return 9; }
        public function getAcceptsJson(): bool { return true; }
        public function getIsConsoleRequest(): bool { return false; }
    }

    class FixtureApp
    {
        public Order $cart;
        public mixed $storage;
        public array $couponLookups = [];
        public function getSettings(): object { return (object)['cartVariable' => 'cart']; }
        public function getCarts(): self { return $this; }
        public function getCart(): Order { return $this->cart; }
        public function getOrders(): self { return $this; }
        public function getOrderById($id): Order { return $this->cart; }
        public function getUser(): self { return $this; }
        public function getIdentity(): object { return new \stdClass(); }
        public function getOrderPermissions(): self { return $this; }
        public function canManage($order, $user): bool { return true; }
        public function getElements(): self { return $this; }
        public function saveElement($element, $validate = true): bool { return true; }
        public function getRequest(): FixtureRequest { return new FixtureRequest(null); }
        public function getCodeStorage(): mixed { return $this->storage; }
        public function getCodes(): \verbb\giftvoucher\services\Codes { return new \verbb\giftvoucher\services\Codes(); }
        public function getReservations(): self { return $this; }
        public function getAvailableAmount($code, $order): int { return $code->available; }
        public function getDiscounts(): self { return $this; }
        public function getDiscountByCode($code): bool { $this->couponLookups[] = $code; return in_array($code, ['COUPON', '456'], true); }
        public function orderCouponAvailable($order, &$error): bool { return true; }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) { throw new \RuntimeException('Failed: ' . $label); }
        echo $label . ": PASS\n";
    }

    Craft::$app = Commerce::$instance = GiftVoucher::$plugin = $app = new FixtureApp();
    foreach ([123 => 'SOMEONE-ELSES-KEY', 200 => '123', 201 => '123ABC', 202 => '0', 203 => '00123', 204 => '1e3', 205 => ' 123 '] as $id => $key) {
        Code::$records[$id] = new Code($id, $key);
    }
    foreach ([\verbb\giftvoucher\storage\Session::class, \verbb\giftvoucher\storage\Order::class] as $storageClass) {
        foreach ([\verbb\giftvoucher\controllers\CartController::class, \verbb\giftvoucher\controllers\OrderVouchersController::class] as $controllerClass) {
            $app->storage = new $storageClass();
            if ($app->storage instanceof \verbb\giftvoucher\storage\Order) { $app->storage->fieldHandle = 'codes'; }
            $controller = new $controllerClass();
            $controller->init();
            foreach ([123, '123', '123ABC', 0, '0', '00123', '1e3', ' 123 '] as $input) {
                $app->cart = new Order();
                \craft\helpers\Session::$data = [];
                Code::$lookups = [];
                $controller->request = new FixtureRequest($input);
                $response = $controller->actionAddCode();
                check("$storageClass $controllerClass add " . var_export($input, true), $response->data['success'] && array_values($app->storage->getCodeKeys($app->cart)) === [(string)$input] && Code::$lookups === [['codeKey' => (string)$input], ['codeKey' => (string)$input]]);
                $app->storage->add(Code::$records[123], $app->cart);
                $controller->actionRemoveCode();
                check('Removal uses the same literal key and leaves the unrelated ID intact', array_values($app->storage->getCodeKeys($app->cart)) === ['SOMEONE-ELSES-KEY']);
            }
            foreach ([null, '', '  ', true, false, 123.0, 1.23, [], ['123'], (object)['code' => '123']] as $input) {
                foreach (['actionAddCode', 'actionRemoveCode'] as $action) {
                    $app->cart = new Order();
                    \craft\helpers\Session::$data = [];
                    Code::$lookups = $app->couponLookups = [];
                    $controller->request = new FixtureRequest($input);
                    $response = $controller->$action();
                    check('Malformed input rejected before lookup: ' . $action . ' ' . get_debug_type($input), !$response->data['success'] && isset($response->data['errors']['voucherCode']) && Code::$lookups === [] && $app->couponLookups === [] && $app->storage->getCodeKeys($app->cart) === []);
                }
            }
            foreach (['COUPON', 456] as $input) {
                $app->cart = new Order();
                $app->couponLookups = [];
                $controller->request = new FixtureRequest($input);
                $response = $controller->actionAddCode();
                check('Coupon fallback retains a literal string', $response->data['success'] && $app->cart->couponCode === (string)$input && $app->couponLookups === [(string)$input]);
            }
        }
        $app->cart = new Order();
        \craft\helpers\Session::$data = [];
        $app->storage->setCodes([123, Code::$records[200]], $app->cart);
        check('Internal IDs and Code objects remain supported', array_values($app->storage->getCodeKeys($app->cart)) === ['SOMEONE-ELSES-KEY', '123']);
        $app->storage->remove(123, $app->cart);
        check('Internal ID removal remains supported', array_values($app->storage->getCodeKeys($app->cart)) === ['123']);
    }

    $prepare = new \ReflectionMethod(\verbb\giftvoucher\elements\db\CodeQuery::class, 'beforePrepare');
    foreach ([null, '0', 0, '', [], ['123', '0']] as $key) {
        $query = new \verbb\giftvoucher\elements\db\CodeQuery(Code::class);
        $query->codeKey($key);
        $prepare->invoke($query);
        check('Only null omits the code-key filter: ' . var_export($key, true), $query->subQuery->where === ($key === null ? null : ['giftvoucher_codes.codeKey' => $key]));
    }

    foreach (['missing', 'exhausted', 'expired'] as $state) {
        $app->cart = new Order();
        $controller->request = new FixtureRequest($state === 'missing' ? 'UNKNOWN' : '123');
        Code::$records[200]->available = $state === 'exhausted' ? 0 : 10;
        Code::$records[200]->expiryDate = $state === 'expired' ? new \DateTime('yesterday') : null;
        check('Existing voucher validation remains: ' . $state, !$controller->actionAddCode()->data['success']);
    }
}

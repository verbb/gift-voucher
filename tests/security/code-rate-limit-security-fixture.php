<?php
namespace {
    $vendor = getenv('GIFT_VOUCHER_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
    require $vendor . '/yiisoft/yii2/Yii.php';
    set_error_handler(static function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) { return false; }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    class Craft
    {
        public static mixed $app;
        public static function t($category, $message): string { return $message; }
    }
}
namespace craft\commerce\elements {
    class Order
    {
        public array $errors = [];
        public ?string $couponCode = null;
        public function addErrors($errors): void { $this->errors += $errors; }
        public function hasErrors(): bool { return $this->errors !== []; }
        public function getErrors(): array { return $this->errors; }
        public function validate(): bool { return true; }
    }
}
namespace craft\commerce\controllers {
    class BaseFrontEndController extends \yii\base\Controller
    {
        public function init(): void { $this->request = \Craft::$app->getRequest(); $this->response = \Craft::$app->getResponse(); }
        protected function requirePostRequest(): void {}
        protected function requirePermission($permission): void {}
        protected function asJson($data): \yii\web\Response { return new \yii\web\Response(['data' => $data, 'charset' => 'UTF-8']); }
        protected function cartArray($cart): array { return []; }
    }
}
namespace craft\commerce {
    class Plugin { public static function getInstance(): mixed { return \Craft::$app; } }
}
namespace verbb\giftvoucher {
    class GiftVoucher { public static mixed $plugin; }
}
namespace {
    use verbb\giftvoucher\controllers\CartController;
    use verbb\giftvoucher\controllers\OrderVouchersController;
    use craft\commerce\elements\Order;

    spl_autoload_register(static function($class) {
        $prefix = 'verbb\\giftvoucher\\';
        if (str_starts_with($class, $prefix)) {
            require __DIR__ . '/../../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        }
    });
    class FixtureRequest extends \yii\web\Request
    {
        public function getAcceptsJson(): bool { return true; }
        public function getParam($name): mixed { return $this->getBodyParam($name) ?? $this->getQueryParam($name); }
        public function getRequiredBodyParam($name): mixed { return $this->getBodyParam($name); }
    }
    class FixtureCache extends \yii\caching\ArrayCache
    {
        public bool $failWrite = false;
        public bool $throwRead = false;
        public array $keys = [];
        public function get($key)
        {
            $this->keys[] = $key;
            if ($this->throwRead) { throw new \RuntimeException('Cache unavailable'); }
            return parent::get($key);
        }
        public function set($key, $value, $duration = null, $dependency = null)
        {
            return !$this->failWrite && parent::set($key, $value, $duration, $dependency);
        }
    }
    class FixtureMutex
    {
        public array $held = [];
        public bool $busy = false;
        public function acquire($key, $timeout = 0): bool
        {
            if ($this->busy || isset($this->held[$key])) { return false; }
            $this->held[$key] = true;
            return true;
        }
        public function release($key): bool { unset($this->held[$key]); return true; }
    }
    class FixtureApp
    {
        public string $language = 'en';
        public function getI18n(): self { return $this; }
        public function translate($category, $message, $params, $language): string { return $message; }
        public FixtureRequest $request;
        public \yii\web\Response $response;
        public \yii\caching\Cache $cache;
        public mixed $mutex;
        public Order $cart;
        public int $lookups = 0;
        public int $added = 0;
        public int $removed = 0;
        public function getRequest(): FixtureRequest { return $this->request; }
        public function getResponse(): \yii\web\Response { return $this->response; }
        public function getCache(): \yii\caching\Cache { return $this->cache; }
        public function getMutex(): mixed { return $this->mutex; }
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
        public function saveElement($order, $validate = true): bool { return true; }
        public function getCodes(): self { return $this; }
        public function matchCode($code, &$error, $order): bool
        {
            $this->lookups++;
            $error = $code === 'VOUCHER' ? '' : 'Voucher code is not valid';
            return $error === '';
        }
        public function getDiscounts(): self { return $this; }
        public function getDiscountByCode($code): bool { return $code === 'COUPON'; }
        public function orderCouponAvailable($order, &$error): bool { return true; }
        public function getCodeStorage(): self { return $this; }
        public function add($code, $order): void { $this->added++; }
        public function remove($code, $order): void { $this->removed++; }
    }
    function check(string $label, bool $condition): void
    {
        if (!$condition) { throw new \RuntimeException('Failed: ' . $label); }
        echo $label . ": PASS\n";
    }
    function submit(string $ip, mixed $code = 'UNKNOWN', string $controllerClass = CartController::class, string $actionId = 'add-code', bool $query = false): int
    {
        $app = Craft::$app;
        $_SERVER['REMOTE_ADDR'] = $ip;
        $app->request = new FixtureRequest(['enableCookieValidation' => false, 'trustedHosts' => []]);
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.99';
        $app->request->setBodyParams(['orderId' => $controllerClass === OrderVouchersController::class ? 9 : null] + ($query ? [] : ['voucherCode' => $code]));
        $app->request->setQueryParams($query ? ['voucherCode' => $code] : []);
        $app->response = new \yii\web\Response(['charset' => 'UTF-8']);
        $app->cart = new Order();
        $controller = new $controllerClass('fixture', null);
        $action = new \yii\base\Action($actionId, $controller);
        try {
            if ($controller->beforeAction($action)) {
                $actionId === 'add-code' ? $controller->actionAddCode() : $controller->actionRemoveCode();
            }
        } catch (\yii\web\HttpException $e) {
            return $e->statusCode;
        }
        return 200;
    }
    Craft::$app = \Yii::$app = \verbb\giftvoucher\GiftVoucher::$plugin = $app = new FixtureApp();
    $app->cache = new FixtureCache();
    $app->mutex = new FixtureMutex();
    check('First unknown code retains its validation response', submit('192.0.2.1') === 200 && $app->cart->errors['voucherCode'] === 'Voucher code is not valid');
    check('Second same-IP attempt is blocked before lookup', submit('192.0.2.1') === 429 && $app->lookups === 1);
    check('429 supplies a retry delay', $app->response->headers->get('Retry-After') === '1');
    check('CP route shares the storefront allowance', submit('192.0.2.1', 'COUPON', OrderVouchersController::class) === 429);
    check('Removal remains usable after exhaustion', submit('192.0.2.1', 'VOUCHER', CartController::class, 'remove-code') === 200 && $app->removed === 1);
    check('Different IP can apply a voucher', submit('192.0.2.2', 'VOUCHER') === 200 && $app->added === 1);
    check('Different IP can apply a coupon', submit('192.0.2.3', 'COUPON') === 200 && $app->cart->couponCode === 'COUPON');
    foreach ([null, [], 123, 'COUPON'] as $value) {
        check('Alternate body values cannot bypass exhausted allowance', submit('192.0.2.3', $value) === 429);
    }
    check('Query parameters cannot bypass exhausted allowance', submit('192.0.2.3', 'COUPON', CartController::class, 'add-code', true) === 429);
    $key = end($app->cache->keys);
    $app->cache->set($key, [0, time() - 2], 10);
    check('Allowance refills after the window', submit('192.0.2.3', 'COUPON') === 200);
    check('IPv6 is accepted', submit('2001:db8::1') === 200);
    check('Equivalent IPv6 spelling shares the allowance', submit('2001:0db8:0000:0000:0000:0000:0000:0001') === 429);
    $app->mutex->busy = true;
    $lookups = $app->lookups;
    check('Concurrent lock contention blocks before lookup', submit('192.0.2.10') === 429 && $app->lookups === $lookups);
    $app->mutex->busy = false;
    $app->cache->failWrite = true;
    check('Failed cache writes block before lookup', submit('192.0.2.11') === 503 && $app->lookups === $lookups);
    $app->cache->failWrite = false;
    check('All successful and rejected paths release their lock', $app->mutex->held === []);
    $app->cache->throwRead = true;
    try { submit('192.0.2.12'); } catch (\RuntimeException $e) {
        check('Cache exceptions propagate and release the lock', $e->getMessage() === 'Cache unavailable' && $app->mutex->held === []);
    }

    if (!function_exists('pcntl_fork')) {
        echo "Concurrent worker check: SKIP (pcntl unavailable)\n";
    } else {
        $directory = sys_get_temp_dir() . '/gift-voucher-rate-limit-' . bin2hex(random_bytes(8));
        mkdir($directory);
        $app->cache = new \yii\caching\FileCache(['cachePath' => $directory, 'directoryLevel' => 0]);
        $app->mutex = new \yii\mutex\FileMutex(['mutexPath' => $directory, 'autoRelease' => false]);
        $start = ceil(microtime(true)) + 0.1;
        $workers = [];
        for ($i = 0; $i < 8; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) { throw new \RuntimeException('Cannot fork rate-limit test worker.'); }
            if ($pid === 0) {
                usleep((int)max(0, ($start - microtime(true)) * 1000000));
                $status = submit('192.0.2.20', 'COUPON', $i % 2 ? CartController::class : OrderVouchersController::class);
                exit($status === 200 ? 0 : ($status === 429 ? 10 : 20));
            }
            $workers[] = $pid;
        }
        $results = [];
        foreach ($workers as $pid) {
            pcntl_waitpid($pid, $status);
            $results[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : 20;
        }
        foreach (glob($directory . '/*') as $file) { unlink($file); }
        rmdir($directory);
        check('Concurrent storefront and CP workers share one allowance', count(array_filter($results, fn($code) => $code === 0)) === 1 && count(array_filter($results, fn($code) => $code === 10)) === 7);
    }
}

<?php
namespace yii\base {
    class Exception extends \RuntimeException
    {
    }
}

namespace yii\web {
    class BadRequestHttpException extends \RuntimeException
    {
    }

    class Response
    {
        public function __construct(
            public ?string $url = null,
            public ?string $template = null,
            public array $variables = [],
        ) {
        }
    }
}

namespace craft\base {
    class Element
    {
        public const SCENARIO_LIVE = 'live';
    }
}

namespace craft\helpers {
    class DateTimeHelper
    {
        public static function toDateTime(mixed $value): mixed
        {
            return $value;
        }
    }

    class UrlHelper
    {
        public static function url(string $path, array $params = []): string
        {
            return $path . '?' . http_build_query($params);
        }
    }
}

namespace craft\web {
    use yii\web\Response;

    class Controller
    {
        public mixed $request = null;
        public int $bulkEventCount = 0;
        public int $bulkEventCodeCount = 0;

        public function init(): void
        {
        }

        public function requirePermission(string $permission): void
        {
        }

        public function requirePostRequest(): void
        {
        }

        public function hasEventHandlers(string $name): bool
        {
            return true;
        }

        public function trigger(string $name, mixed $event): void
        {
            $this->bulkEventCount++;
            $this->bulkEventCodeCount = count($event->codes);
        }

        public function renderTemplate(string $template, array $variables): Response
        {
            return new Response(template: $template, variables: $variables);
        }

        public function redirect(string $url): Response
        {
            return new Response(url: $url);
        }
    }
}

namespace verbb\giftvoucher {
    class GiftVoucher
    {
        public static mixed $plugin = null;
    }
}

namespace verbb\giftvoucher\elements {
    class Code
    {
        public ?int $id = null;
        public mixed $voucherId = null;
        public bool $enabled = false;
        public float $currentAmount = 0;
        public float $originalAmount = 0;
        public mixed $expiryDate = null;
    }

    class Voucher
    {
    }
}

namespace verbb\giftvoucher\events {
    class BulkGenerateCodesEvent
    {
        public array $codes;

        public function __construct(array $config = [])
        {
            $this->codes = $config['codes'] ?? [];
        }
    }
}

namespace {
    class Craft
    {
        public static mixed $app = null;

        public static function t(string $category, string $message, array $params = []): string
        {
            return strtr($message, array_combine(
                array_map(fn(string $name): string => '{' . $name . '}', array_keys($params)),
                array_values($params),
            ));
        }
    }

    require __DIR__ . '/../../src/controllers/CodesController.php';

    use verbb\giftvoucher\controllers\CodesController;
    use verbb\giftvoucher\GiftVoucher;
    use yii\web\Response;

    class FixtureRequest
    {
        public function __construct(private array $params)
        {
        }

        public function getBodyParam(string $name): mixed
        {
            return $this->params[$name] ?? null;
        }
    }

    class FixtureElements
    {
        public int $saveCount = 0;

        public function saveElement(object $element): bool
        {
            $this->saveCount++;
            $element->id = $this->saveCount;

            return true;
        }
    }

    class FixtureSession
    {
        public array $errors = [];
        public array $notices = [];

        public function setError(string $message): void
        {
            $this->errors[] = $message;
        }

        public function setNotice(string $message): void
        {
            $this->notices[] = $message;
        }
    }

    class FixtureUrlManager
    {
        public array $routeParams = [];

        public function getRouteParams(): array
        {
            return [];
        }

        public function setRouteParams(array $params): void
        {
            $this->routeParams = $params;
        }
    }

    class FixtureApp
    {
        public FixtureElements $elements;
        public FixtureSession $session;
        public FixtureUrlManager $urlManager;

        public function __construct()
        {
            $this->elements = new FixtureElements();
            $this->session = new FixtureSession();
            $this->urlManager = new FixtureUrlManager();
        }

        public function getElements(): FixtureElements
        {
            return $this->elements;
        }

        public function getSession(): FixtureSession
        {
            return $this->session;
        }

        public function getUrlManager(): FixtureUrlManager
        {
            return $this->urlManager;
        }
    }

    class FixtureVouchers
    {
        public function getVoucherById(mixed $id): ?object
        {
            return $id === '7' ? (object)['id' => 7] : null;
        }
    }

    class FixturePlugin
    {
        private FixtureVouchers $vouchers;

        public function __construct()
        {
            $this->vouchers = new FixtureVouchers();
        }

        public function getVouchers(): FixtureVouchers
        {
            return $this->vouchers;
        }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    function runSubmission(mixed $amount): array
    {
        Craft::$app = new FixtureApp();
        GiftVoucher::$plugin = new FixturePlugin();

        $controller = new CodesController();
        $controller->request = new FixtureRequest([
            'amount' => $amount,
            'voucherAmount' => '25',
            'voucher' => ['7'],
            'expiryDate' => null,
        ]);
        $response = $controller->actionBulkGenerateSubmit();

        return [$controller, Craft::$app, $response];
    }

    Craft::$app = new FixtureApp();
    $pageController = new CodesController();
    $page = $pageController->actionBulkGenerate();
    check('Bulk generation page exposes the server-side maximum',
        $page->variables['maxBulkGenerateCodes'] === CodesController::MAX_BULK_GENERATE_CODES);

    foreach (['0', '-1', '501', '1000000', '500.99', '500junk', [], true] as $invalidAmount) {
        [$controller, $app, $response] = runSubmission($invalidAmount);

        check('Invalid amount performs no element saves: ' . var_export($invalidAmount, true),
            $app->elements->saveCount === 0);
        check('Invalid amount returns through the form error path: ' . var_export($invalidAmount, true),
            $response === null && isset($app->urlManager->routeParams['errors']['amount']));
        check('Invalid amount triggers no bulk event: ' . var_export($invalidAmount, true),
            $controller->bulkEventCount === 0);
    }

    [$controller, $app, $response] = runSubmission('1');
    check('A one-code batch remains valid',
        $response instanceof Response && $app->elements->saveCount === 1);
    check('A one-code batch preserves the bulk event',
        $controller->bulkEventCount === 1 && $controller->bulkEventCodeCount === 1);

    [$controller, $app, $response] = runSubmission('5e2');
    check('An exact exponent representation at the maximum remains valid',
        $response instanceof Response && $app->elements->saveCount === 500);
    check('An exact exponent representation preserves the bulk event',
        $controller->bulkEventCount === 1 && $controller->bulkEventCodeCount === 500);

    [$controller, $app, $response] = runSubmission('500');
    check('The maximum batch remains valid',
        $response instanceof Response && $app->elements->saveCount === 500);
    check('The maximum batch preserves the bulk event and success notice',
        $controller->bulkEventCount === 1 &&
        $controller->bulkEventCodeCount === 500 &&
        $app->session->notices === ['Voucher codes generated.']);

    $template = file_get_contents(__DIR__ . '/../../src/templates/codes/_bulk-generate.html');
    check('The amount field exposes matching browser constraints',
        str_contains($template, "type: 'number'") &&
        str_contains($template, 'min: 1') &&
        str_contains($template, 'max: maxBulkGenerateCodes') &&
        str_contains($template, 'step: 1'));
}

<?php
namespace craft\commerce {
    class Plugin
    {
        public static mixed $instance = null;

        public static function getInstance(): mixed
        {
            return self::$instance;
        }
    }
}

namespace craft\commerce\db {
    class Table
    {
        public const LINEITEMS = 'lineItems';
    }
}

namespace craft\commerce\models {
    class LineItem
    {
        public ?int $id = null;
        public ?int $orderId = null;
        public ?string $uid = null;

        public function __construct(array $config = [])
        {
            foreach ($config as $name => $value) {
                if (property_exists($this, $name)) {
                    $this->$name = $value;
                }
            }
        }
    }
}

namespace craft\db {
    class Query
    {
        public static ?array $row = null;

        public function select(array $columns): self
        {
            return $this;
        }

        public function from(array $tables): self
        {
            return $this;
        }

        public function orderBy(string $columns): self
        {
            return $this;
        }

        public function where(array $condition): self
        {
            return $this;
        }

        public function one(): ?array
        {
            return self::$row;
        }
    }
}

namespace craft\helpers {
    class Json
    {
        public static function decodeIfJson(mixed $value): mixed
        {
            return $value;
        }
    }
}

namespace yii\web {
    class BadRequestHttpException extends \RuntimeException
    {
    }

    class NotFoundHttpException extends \RuntimeException
    {
    }

    class Response
    {
        public function __construct(
            public string $content,
            public string $filename,
            public array $options,
        ) {
        }
    }
}

namespace craft\web {
    class Controller
    {
        public mixed $request = null;

        public function getView(): mixed
        {
            return \Craft::$app->getView();
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
        public function __construct(
            public string $uid,
            public string $codeKey,
            private mixed $order = null,
        ) {
        }

        public function getOrder(): mixed
        {
            return $this->order;
        }
    }
}

namespace verbb\giftvoucher\helpers {
    class Locale
    {
        public static function switchAppLanguage(string $language, ?string $formattingLocale = null): void
        {
        }
    }
}

namespace {
    class Craft
    {
        public static mixed $app = null;
    }

    require __DIR__ . '/../../src/controllers/DownloadsController.php';

    use craft\commerce\Plugin as Commerce;
    use craft\db\Query;
    use verbb\giftvoucher\controllers\DownloadsController;
    use verbb\giftvoucher\elements\Code;
    use verbb\giftvoucher\GiftVoucher;
    use yii\web\BadRequestHttpException;
    use yii\web\NotFoundHttpException;
    use yii\web\Response;

    class FixtureRequest
    {
        public function __construct(private array $params = [])
        {
        }

        public function getParam(string $name, mixed $defaultValue = null): mixed
        {
            return $this->params[$name] ?? $defaultValue;
        }
    }

    class FixtureSites
    {
        private object $primarySite;

        public function __construct()
        {
            $this->primarySite = (object)[
                'handle' => 'default',
                'language' => 'en',
            ];
        }

        public function getPrimarySite(): object
        {
            return $this->primarySite;
        }

        public function getSiteByHandle(string $handle): ?object
        {
            return $handle === $this->primarySite->handle ? $this->primarySite : null;
        }
    }

    class FixtureElements
    {
        public mixed $element = null;
        public ?string $requestedType = null;

        public function getElementByUid(string $uid, ?string $elementType = null): mixed
        {
            $this->requestedType = $elementType;

            return $this->element;
        }
    }

    class FixtureView
    {
        public function renderObjectTemplate(string $template, mixed $object, array $variables): string
        {
            return $variables['codeKey'] ?? ('Voucher-' . ($object->number ?? 'download'));
        }
    }

    class FixtureResponse
    {
        public function sendContentAsFile(string $content, string $filename, array $options): Response
        {
            return new Response($content, $filename, $options);
        }
    }

    class FixtureApp
    {
        public string $language = 'en';
        public string $formattingLocale = 'en-US';
        public FixtureElements $elements;

        private FixtureSites $sites;
        private FixtureView $view;
        private FixtureResponse $response;

        public function __construct()
        {
            $this->elements = new FixtureElements();
            $this->sites = new FixtureSites();
            $this->view = new FixtureView();
            $this->response = new FixtureResponse();
        }

        public function getElements(): FixtureElements
        {
            return $this->elements;
        }

        public function getResponse(): FixtureResponse
        {
            return $this->response;
        }

        public function getSites(): FixtureSites
        {
            return $this->sites;
        }

        public function getView(): FixtureView
        {
            return $this->view;
        }
    }

    class FixtureOrders
    {
        public mixed $order = null;

        public function getOrderByNumber(string $number): mixed
        {
            return $this->order;
        }
    }

    class FixtureCommerce
    {
        public FixtureOrders $orders;

        public function __construct()
        {
            $this->orders = new FixtureOrders();
        }

        public function getOrders(): FixtureOrders
        {
            return $this->orders;
        }
    }

    class FixturePdf
    {
        public int $renderCount = 0;

        public function renderPdf(array $codes, mixed $order, mixed $lineItem, mixed $option): string
        {
            $this->renderCount++;

            return 'pdf-content';
        }
    }

    class FixtureGiftVoucher
    {
        public FixturePdf $pdf;

        public function __construct()
        {
            $this->pdf = new FixturePdf();
        }

        public function getPdf(): FixturePdf
        {
            return $this->pdf;
        }

        public function getSettings(): object
        {
            return (object)['voucherCodesPdfFilenameFormat' => 'Voucher-{number}'];
        }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    function expectException(string $label, string $exceptionClass, array $params): void
    {
        $controller = new DownloadsController();
        $controller->request = new FixtureRequest($params);
        $renderCount = GiftVoucher::$plugin->pdf->renderCount;

        try {
            $controller->actionPdf();
        } catch (Throwable $exception) {
            check("{$label} returns {$exceptionClass}", $exception instanceof $exceptionClass);
            check("{$label} does not render a PDF", GiftVoucher::$plugin->pdf->renderCount === $renderCount);

            return;
        }

        throw new RuntimeException("Failed: {$label} did not throw");
    }

    function download(array $params): Response|string
    {
        $controller = new DownloadsController();
        $controller->request = new FixtureRequest($params);

        return $controller->actionPdf();
    }

    Craft::$app = new FixtureApp();
    Commerce::$instance = new FixtureCommerce();
    GiftVoucher::$plugin = new FixtureGiftVoucher();

    foreach ([
        'Missing identifiers' => [],
        'Empty identifiers' => ['number' => '', 'codeUid' => ''],
        'Modifier-only request' => ['option' => 'email'],
        'Line-item-only request' => ['lineItemUid' => 'line-item-uid'],
        'Array-encoded order number' => ['number' => ['order-number']],
        'Array-encoded code UID' => ['codeUid' => ['code-uid']],
        'Ambiguous order and code request' => ['number' => 'order-number', 'codeUid' => 'code-uid'],
    ] as $label => $params) {
        expectException($label, BadRequestHttpException::class, $params);
    }

    expectException('Unknown order', NotFoundHttpException::class, ['number' => 'unknown-order']);
    expectException('Unknown voucher code', NotFoundHttpException::class, ['codeUid' => 'unknown-code']);

    Craft::$app->elements->element = new Code('actual-code-uid', 'GIFT-CODE');
    expectException('Wildcard voucher code UID', NotFoundHttpException::class, ['codeUid' => '*']);
    Craft::$app->elements->element = null;

    $order = (object)[
        'id' => 10,
        'number' => 'order-number',
    ];
    Commerce::$instance->orders->order = $order;

    $renderCount = GiftVoucher::$plugin->pdf->renderCount;
    $response = download(['number' => 'order-number']);
    check('Order PDFs remain available', $response instanceof Response);
    check('Order PDFs render once', GiftVoucher::$plugin->pdf->renderCount === $renderCount + 1);

    Query::$row = [
        'id' => 20,
        'orderId' => 10,
        'uid' => 'line-item-uid',
        'snapshot' => [],
    ];
    $renderCount = GiftVoucher::$plugin->pdf->renderCount;
    $response = download(['number' => 'order-number', 'lineItemUid' => 'line-item-uid']);
    check('Order line-item PDFs remain available', $response instanceof Response);
    check('Order line-item PDFs render once', GiftVoucher::$plugin->pdf->renderCount === $renderCount + 1);

    Query::$row['orderId'] = 11;
    expectException('Cross-order line item', NotFoundHttpException::class, [
        'number' => 'order-number',
        'lineItemUid' => 'line-item-uid',
    ]);

    Craft::$app->elements->element = new Code('code-uid', 'GIFT-CODE');
    $renderCount = GiftVoucher::$plugin->pdf->renderCount;
    $response = download(['codeUid' => 'code-uid']);
    check('Standalone voucher-code PDFs remain available', $response instanceof Response);
    check('Voucher-code lookups are restricted to Code elements', Craft::$app->elements->requestedType === Code::class);
    check('Standalone voucher-code PDFs render once', GiftVoucher::$plugin->pdf->renderCount === $renderCount + 1);
}

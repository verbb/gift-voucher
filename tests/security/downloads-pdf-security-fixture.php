<?php
namespace craft\base {
    class Model
    {
        public function __construct(array $config = [])
        {
            foreach ($config as $name => $value) {
                $this->$name = $value;
            }
        }
    }
}

namespace yii\base {
    class Component
    {
    }
}

namespace craft\commerce\enums {
    enum LineItemType
    {
        case Purchasable;
    }
}

namespace craft\commerce\events {
    class MailEvent
    {
        public mixed $order;
        public mixed $commerceEmail;
        public mixed $craftEmail;
    }
}

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
    class Assets
    {
        public static function tempFilePath(string $extension): string
        {
            return tempnam(sys_get_temp_dir(), 'gift-voucher-filename-');
        }
    }

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

        public static function error(string $message): void
        {
            throw new \RuntimeException($message);
        }
    }
}

namespace verbb\giftvoucher\elements {
    class Voucher
    {
    }

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

        public static function t(string $category, string $message, array $params = []): string
        {
            return $message;
        }
    }

    require __DIR__ . '/../../src/controllers/DownloadsController.php';
    require __DIR__ . '/../../src/models/Settings.php';
    require __DIR__ . '/../../src/services/Vouchers.php';

    use craft\commerce\Plugin as Commerce;
    use craft\db\Query;
    use verbb\giftvoucher\controllers\DownloadsController;
    use verbb\giftvoucher\elements\Code;
    use verbb\giftvoucher\GiftVoucher;
    use verbb\giftvoucher\models\Settings;
    use verbb\giftvoucher\services\Vouchers;
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
        public function renderObjectTemplate(string $template, mixed $object, array $variables = []): string
        {
            return strtr($template, [
                '{number}' => $object->number ?? '',
                '{codeKey}' => $variables['codeKey'] ?? '',
            ]);
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
        public array $codes = [];
        public mixed $order = null;

        public function renderPdf(array $codes, mixed $order, mixed $lineItem, mixed $option): string
        {
            $this->renderCount++;
            $this->codes = $codes;
            $this->order = $order;

            return 'pdf-content';
        }
    }

    class FixtureGiftVoucher
    {
        public FixturePdf $pdf;
        public Settings $settings;

        public function __construct()
        {
            $this->pdf = new FixturePdf();
            $this->settings = new Settings();
        }

        public function getPdf(): FixturePdf
        {
            return $this->pdf;
        }

        public function getSettings(): object
        {
            return $this->settings;
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

    $code = new Code('code-uid', 'GIFT-CODE', $order);
    Craft::$app->elements->element = $code;
    $response = download(['codeUid' => 'code-uid']);
    check('An order-backed code uses a credential-free default filename', $response->filename === 'Gift-Voucher.pdf');
    check('Code downloads preserve the exact code and parent order for PDF templates',
        GiftVoucher::$plugin->pdf->codes === [$code] && GiftVoucher::$plugin->pdf->order === $order
    );

    Query::$row['orderId'] = $order->id;

    foreach ([
        'default' => [(new Settings())->voucherCodesPdfFilenameFormat, 'Gift-Voucher.pdf'],
        'empty' => ['', 'Gift-Voucher.pdf'],
        'custom' => ['Custom-{number}', 'Custom-order-number.pdf'],
        'explicit previous default' => ['Voucher-{number}', 'Voucher-order-number.pdf'],
    ] as $label => [$format, $expectedFilename]) {
        GiftVoucher::$plugin->settings->voucherCodesPdfFilenameFormat = $format;

        foreach ([
            'order' => ['number' => 'order-number'],
            'line item' => ['number' => 'order-number', 'lineItemUid' => 'line-item-uid'],
            'code' => ['codeUid' => 'code-uid'],
        ] as $scope => $params) {
            $response = download($params);
            check("{$scope} download respects {$label} filename", $response->filename === $expectedFilename);
        }
    }

    GiftVoucher::$plugin->settings->voucherCodesPdfFilenameFormat = 'Voucher-{codeKey}';
    check('Custom formats retain the codeKey variable', download(['codeUid' => 'code-uid'])->filename === 'Voucher-GIFT-CODE.pdf');
    GiftVoucher::$plugin->settings->voucherCodesPdfFilenameFormat = '';
    Craft::$app->elements->element = new Code('manual-code-uid', 'MANUAL-CODE');
    check('A standalone code uses a credential-free fallback', download(['codeUid' => 'manual-code-uid'])->filename === 'Gift-Voucher.pdf');
    check('Plain output remains available', download(['codeUid' => 'manual-code-uid', 'format' => 'plain']) === 'pdf-content');
    check('Inline downloads retain their response option', download(['codeUid' => 'manual-code-uid', 'attach' => true])->options['inline'] === true);

    $order->lineItems = [(object)[
        'type' => \craft\commerce\enums\LineItemType::Purchasable,
        'purchasable' => new \verbb\giftvoucher\elements\Voucher(),
    ]];
    GiftVoucher::$plugin->settings->attachPdfToEmails = ['email-uid' => true];

    foreach ([
        'default' => [(new Settings())->voucherCodesPdfFilenameFormat, 'Gift-Voucher.pdf'],
        'empty' => ['', 'Gift-Voucher.pdf'],
        'custom' => ['Custom-{number}', 'Custom-order-number.pdf'],
    ] as $label => [$format, $expectedFilename]) {
        GiftVoucher::$plugin->settings->voucherCodesPdfFilenameFormat = $format;
        $event = new \craft\commerce\events\MailEvent();
        $event->order = $order;
        $event->commerceEmail = (object)['uid' => 'email-uid'];
        $event->craftEmail = new class {
            public ?string $path = null;
            public array $options = [];

            public function attach(string $path, array $options): void
            {
                $this->path = $path;
                $this->options = $options;
            }
        };
        $vouchers = new Vouchers();

        try {
            $vouchers->onBeforeSendEmail($event);
            check("Email attachment respects {$label} filename", ($event->craftEmail->options['fileName'] ?? null) === $expectedFilename);
            check('Email attachment retains PDF content', file_get_contents($event->craftEmail->path) === 'pdf-content');
        } finally {
            $vouchers->onAfterSendEmail($event);
        }

        check('Email attachment temporary file is removed', !file_exists($event->craftEmail->path));
    }
}

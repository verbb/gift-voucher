<?php
namespace yii\base {
    class Component
    {
        public function hasEventHandlers(string $name): bool
        {
            return false;
        }

        public function trigger(string $name, object $event): void
        {
        }
    }

    class ErrorException extends \Exception
    {
    }

    class Exception extends \Exception
    {
    }
}

namespace craft\commerce\elements {
    class Order
    {
    }
}

namespace craft\commerce\models {
    class LineItem
    {
    }
}

namespace craft\helpers {
    class FileHelper
    {
        public static function createDirectory(string $path): void
        {
        }

        public static function isWritable(string $path): bool
        {
            return true;
        }
    }

    class UrlHelper
    {
    }
}

namespace craft\web {
    class View
    {
        public const TEMPLATE_MODE_SITE = 'site';
    }
}

namespace Dompdf {
    class Options
    {
        public function setTempDir(string $path): void
        {
        }

        public function setFontCache(string $path): void
        {
        }

        public function setLogOutputFile(string $path): void
        {
        }

        public function setIsRemoteEnabled(bool $enabled): void
        {
        }
    }

    class Dompdf
    {
        public function setOptions(Options $options): void
        {
        }

        public function setPaper(string $size, string $orientation): void
        {
        }

        public function loadHtml(string $html): void
        {
        }

        public function render(): void
        {
        }

        public function output(): string
        {
            return 'pdf-output';
        }
    }
}

namespace verbb\giftvoucher {
    class GiftVoucher
    {
        public static mixed $plugin = null;
        public static array $logs = [];

        public static function error(string $message, array $params = []): void
        {
            self::$logs[] = $message;
        }
    }
}

namespace verbb\giftvoucher\elements {
    class Code
    {
        public function __construct(
            public ?int $id,
            public string $codeKey,
        ) {
        }
    }
}

namespace verbb\giftvoucher\events {
    class PdfEvent
    {
        public mixed $order = null;
        public mixed $option = null;
        public mixed $template = null;
        public array $variables = [];
        public ?string $pdf = null;

        public function __construct(array $config = [])
        {
            foreach ($config as $name => $value) {
                $this->$name = $value;
            }
        }
    }

    class PdfRenderOptionsEvent
    {
        public function __construct(array $config = [])
        {
        }
    }
}

namespace {
    class Craft
    {
        public static mixed $app = null;

        public static function t(string $category, string $message): string
        {
            return $message;
        }
    }

    require __DIR__ . '/../../src/services/Pdf.php';

    use verbb\giftvoucher\elements\Code;
    use verbb\giftvoucher\GiftVoucher;
    use verbb\giftvoucher\services\Pdf;

    class FixtureRequest
    {
        public function getIsConsoleRequest(): bool
        {
            return false;
        }

        public function getParam(string $name): mixed
        {
            return $name === 'format' ? 'plain' : null;
        }
    }

    class FixtureView
    {
        private string $templateMode = 'cp';

        public function getTemplateMode(): string
        {
            return $this->templateMode;
        }

        public function setTemplateMode(string $mode): void
        {
            $this->templateMode = $mode;
        }

        public function doesTemplateExist(string $template): bool
        {
            return true;
        }

        public function renderTemplate(string $template, array $variables): string
        {
            throw new Exception('Template rendering failed.');
        }
    }

    class FixtureErrorHandler
    {
        public array $exceptions = [];

        public function logException(Throwable $exception): void
        {
            $this->exceptions[] = $exception;
        }
    }

    class FixturePath
    {
        public function getTempPath(): string
        {
            return sys_get_temp_dir();
        }
    }

    class FixtureApp
    {
        public FixtureErrorHandler $errorHandler;

        private FixtureRequest $request;
        private FixtureView $view;
        private FixturePath $path;

        public function __construct()
        {
            $this->request = new FixtureRequest();
            $this->view = new FixtureView();
            $this->errorHandler = new FixtureErrorHandler();
            $this->path = new FixturePath();
        }

        public function getRequest(): FixtureRequest
        {
            return $this->request;
        }

        public function getView(): FixtureView
        {
            return $this->view;
        }

        public function getErrorHandler(): FixtureErrorHandler
        {
            return $this->errorHandler;
        }

        public function getPath(): FixturePath
        {
            return $this->path;
        }
    }

    class FixturePlugin
    {
        public function getSettings(): object
        {
            return (object)[
                'voucherCodesPdfPath' => 'voucher-template',
                'pdfAllowRemoteImages' => false,
                'pdfPaperSize' => 'A4',
                'pdfPaperOrientation' => 'portrait',
            ];
        }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    function renderFailure(Code $code): array
    {
        Craft::$app = new FixtureApp();
        GiftVoucher::$plugin = new FixturePlugin();
        GiftVoucher::$logs = [];

        $output = (new Pdf())->renderPdf([$code], templatePath: 'voucher-template');
        $exceptionMessages = array_map(
            fn(Throwable $exception): string => $exception->getMessage(),
            Craft::$app->errorHandler->exceptions,
        );

        return [$output, array_merge(GiftVoucher::$logs, $exceptionMessages)];
    }

    $codeKey = 'SECRET-REDEEM-1234';
    [$output, $logs] = renderFailure(new Code(42, $codeKey));
    $joinedLogs = implode("\n", $logs);

    check('PDF render failures retain the generic customer-safe output',
        $output === 'An error occurred while generating this PDF.');
    check('PDF render failures retain exception logging',
        count(Craft::$app->errorHandler->exceptions) === 1);
    check('PDF render logs retain a non-secret diagnostic identifier',
        str_contains($joinedLogs, 'Code ID: 42'));
    check('PDF render logs omit the complete bearer code',
        !str_contains($joinedLogs, $codeKey));
    check('PDF render logs do not intentionally retain bearer fragments',
        !str_contains($joinedLogs, 'SECRET') &&
        !str_contains($joinedLogs, 'REDEEM') &&
        !str_contains($joinedLogs, '1234'));

    [$output, $logs] = renderFailure(new Code(null, 'SECOND-SECRET-5678'));
    $joinedLogs = implode("\n", $logs);

    check('Unsaved codes use a non-secret fallback identifier',
        str_contains($joinedLogs, 'Code ID: unknown'));
    check('Unsaved code failures also omit the bearer code',
        !str_contains($joinedLogs, 'SECOND-SECRET-5678'));
}

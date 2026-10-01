<?php
namespace yii\base {
    class ActionEvent
    {
        public mixed $action = null;
        public mixed $sender = null;

        public function __construct(array $config = [])
        {
            $this->action = $config['action'] ?? null;
            $this->sender = $config['sender'] ?? null;
        }
    }

    class Event
    {
        public static array $handlers = [];

        public static function on(string $class, string $name, callable $handler): void
        {
            self::$handlers[$class][$name][] = $handler;
        }
    }
}

namespace craft\base {
    class Element
    {
        public const STATUS_ENABLED = 'enabled';

        public function getUiLabel(): string
        {
            return (string)$this;
        }
    }

    class Model
    {
    }

    class Plugin
    {
        public const EVENT_BEFORE_ACTION = 'beforeAction';

        public function init(): void
        {
        }
    }
}

namespace craft\web {
    class Controller
    {
        public array $guardCalls = [];
        public mixed $request = null;

        public function requireCpRequest(): void
        {
            $this->guardCalls[] = 'cp';
        }

        public function requirePermission(string $permission): void
        {
            $this->guardCalls[] = 'permission:' . $permission;
        }
    }
}

namespace craft\controllers {
    class AppController extends \craft\web\Controller
    {
        public const EVENT_BEFORE_ACTION = 'beforeAction';
    }

    class ElementIndexesController extends \craft\web\Controller
    {
        public const EVENT_BEFORE_ACTION = 'beforeAction';
    }

    class ElementSearchController extends \craft\web\Controller
    {
        public const EVENT_BEFORE_ACTION = 'beforeAction';
    }

    class ElementSelectorModalsController extends \craft\web\Controller
    {
        public const EVENT_BEFORE_ACTION = 'beforeAction';
    }

    class RelationalFieldsController extends \craft\web\Controller
    {
        public const EVENT_BEFORE_ACTION = 'beforeAction';
    }
}

namespace craft\elements {
    class User
    {
        public function can(string $permission): bool
        {
            return false;
        }
    }
}

namespace verbb\giftvoucher\base {
    trait PluginTrait
    {
        public static mixed $plugin = null;
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

    require __DIR__ . '/../../src/elements/Code.php';
    require __DIR__ . '/../../src/GiftVoucher.php';

    use craft\controllers\AppController;
    use craft\controllers\ElementIndexesController;
    use craft\controllers\ElementSearchController;
    use craft\controllers\ElementSelectorModalsController;
    use craft\controllers\RelationalFieldsController;
    use verbb\giftvoucher\elements\Code;
    use verbb\giftvoucher\GiftVoucher;
    use yii\base\ActionEvent;
    use yii\base\Event;

    class FixtureAction
    {
        public function __construct(public string $id)
        {
        }
    }

    class FixtureRequest
    {
        public function __construct(private array $params = [], private bool $cpRequest = true)
        {
        }

        public function getParam(string $name): mixed
        {
            return $this->params[$name] ?? null;
        }

        public function getBodyParam(string $name, mixed $defaultValue = null): mixed
        {
            return $this->params[$name] ?? $defaultValue;
        }

        public function getIsCpRequest(): bool
        {
            return $this->cpRequest;
        }
    }

    class FixtureUserSession
    {
        public function __construct(private bool $manageCodes)
        {
        }

        public function checkPermission(string $permission): bool
        {
            return $permission === 'giftVoucher-manageCodes' && $this->manageCodes;
        }
    }

    class FixtureApp
    {
        public function __construct(private FixtureRequest $request, private FixtureUserSession $user)
        {
        }

        public function getRequest(): FixtureRequest
        {
            return $this->request;
        }

        public function getUser(): FixtureUserSession
        {
            return $this->user;
        }
    }

    class FixtureCode extends Code
    {
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    $plugin = new GiftVoucher();
    $registerCodePermissions = new ReflectionMethod($plugin, '_registerCodePermissions');
    $registerCodePermissions->invoke($plugin);

    $genericElementControllers = [
        ElementIndexesController::class,
        ElementSearchController::class,
        ElementSelectorModalsController::class,
        RelationalFieldsController::class,
    ];

    foreach ($genericElementControllers as $controllerClass) {
        $handler = Event::$handlers[$controllerClass][ElementIndexesController::EVENT_BEFORE_ACTION][0] ?? null;
        check("The {$controllerClass} guard is registered", is_callable($handler));

        foreach ([Code::class, FixtureCode::class] as $elementType) {
            $controller = new $controllerClass();
            $controller->request = new FixtureRequest(['elementType' => $elementType]);
            $handler(new ActionEvent([
                'action' => new FixtureAction('fixture'),
                'sender' => $controller,
            ]));
            check("{$controllerClass} protects {$elementType}",
                $controller->guardCalls === ['cp', 'permission:giftVoucher-manageCodes']
            );
        }

        $controller = new $controllerClass();
        $controller->request = new FixtureRequest(['elementType' => stdClass::class]);
        $handler(new ActionEvent([
            'action' => new FixtureAction('fixture'),
            'sender' => $controller,
        ]));
        check("{$controllerClass} leaves unrelated element types unchanged", $controller->guardCalls === []);
    }

    $appHandler = Event::$handlers[AppController::class][AppController::EVENT_BEFORE_ACTION][0] ?? null;
    check('The element-rendering guard is registered', is_callable($appHandler));

    $appController = new AppController();
    $appController->request = new FixtureRequest([
        'elements' => [
            ['type' => stdClass::class],
            ['type' => Code::class],
        ],
    ]);
    $appHandler(new ActionEvent([
        'action' => new FixtureAction('render-elements'),
        'sender' => $appController,
    ]));
    check('Generic Code chip and card rendering requires code-management permission',
        $appController->guardCalls === ['cp', 'permission:giftVoucher-manageCodes']
    );

    $appController = new AppController();
    $appController->request = new FixtureRequest([
        'elements' => [['type' => Code::class]],
    ]);
    $appHandler(new ActionEvent([
        'action' => new FixtureAction('render-components'),
        'sender' => $appController,
    ]));
    check('Unrelated App controller actions are unchanged', $appController->guardCalls === []);

    $testCodeKey = 'GV-SECURITY-TEST-0001';
    $code = new Code();
    $code->codeKey = $testCodeKey;

    Craft::$app = new FixtureApp(new FixtureRequest(cpRequest: true), new FixtureUserSession(false));
    check('Unauthorized CP labels do not contain the bearer code', $code->getUiLabel() === 'Voucher code');

    Craft::$app = new FixtureApp(new FixtureRequest(cpRequest: true), new FixtureUserSession(true));
    check('Authorized CP labels retain the bearer code', $code->getUiLabel() === $testCodeKey);

    Craft::$app = new FixtureApp(new FixtureRequest(cpRequest: false), new FixtureUserSession(false));
    check('Site templates retain existing string and UI-label behavior',
        (string)$code === $testCodeKey && $code->getUiLabel() === $testCodeKey
    );
}

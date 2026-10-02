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

    class ForbiddenHttpException extends \RuntimeException
    {
    }

    class Response
    {
    }
}

namespace craft\web {
    use yii\web\ForbiddenHttpException;
    use yii\web\Response;

    class Controller
    {
        public array $grantedPermissions = [];
        public array $guardCalls = [];
        public bool $stopAtPostRequest = false;

        public function init(): void
        {
        }

        public function requirePermission(string $permission): void
        {
            $this->guardCalls[] = $permission;

            if (!in_array($permission, $this->grantedPermissions, true)) {
                throw new ForbiddenHttpException("Missing permission: {$permission}");
            }
        }

        public function requirePostRequest(): void
        {
            $this->guardCalls[] = 'post';

            if ($this->stopAtPostRequest) {
                throw new \BoundaryReached();
            }
        }

        public function renderTemplate(string $template, array $variables): Response
        {
            return new Response();
        }
    }
}

namespace verbb\giftvoucher {
    class GiftVoucher
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

    class BoundaryReached extends RuntimeException
    {
    }

    require __DIR__ . '/../../src/controllers/CodesController.php';

    use verbb\giftvoucher\controllers\CodesController;
    use yii\web\ForbiddenHttpException;
    use yii\web\Response;

    class FixtureUrlManager
    {
        public function getRouteParams(): array
        {
            return [];
        }
    }

    class FixtureApp
    {
        public function getUrlManager(): FixtureUrlManager
        {
            return new FixtureUrlManager();
        }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    function controllerWithPermissions(array $permissions): CodesController
    {
        $controller = new CodesController();
        $controller->grantedPermissions = $permissions;

        return $controller;
    }

    function expectForbidden(string $label, string $action): void
    {
        $controller = controllerWithPermissions(['giftVoucher-manageCodes']);
        $controller->init();

        try {
            $controller->$action();
        } catch (ForbiddenHttpException) {
            check("{$label} requires the dedicated permission", $controller->guardCalls === [
                'giftVoucher-manageCodes',
                'giftVoucher-bulkGenerateCodes',
            ]);

            return;
        }

        throw new RuntimeException("Failed: {$label} was not forbidden");
    }

    Craft::$app = new FixtureApp();

    $ordinaryController = controllerWithPermissions(['giftVoucher-manageCodes']);
    $ordinaryController->init();
    check('Ordinary code management still requires only its existing permission', $ordinaryController->guardCalls === [
        'giftVoucher-manageCodes',
    ]);

    $bulkOnlyController = controllerWithPermissions(['giftVoucher-bulkGenerateCodes']);

    try {
        $bulkOnlyController->init();
        throw new RuntimeException('Failed: bulk-only access did not require code management');
    } catch (ForbiddenHttpException) {
        check('Bulk generation remains additive to code management', $bulkOnlyController->guardCalls === [
            'giftVoucher-manageCodes',
        ]);
    }

    expectForbidden('Bulk generation page', 'actionBulkGenerate');
    expectForbidden('Bulk generation submission', 'actionBulkGenerateSubmit');

    $pageController = controllerWithPermissions([
        'giftVoucher-manageCodes',
        'giftVoucher-bulkGenerateCodes',
    ]);
    $pageController->init();
    check('Users with both permissions can open bulk generation', $pageController->actionBulkGenerate() instanceof Response);
    check('Bulk generation page checks both permissions', $pageController->guardCalls === [
        'giftVoucher-manageCodes',
        'giftVoucher-bulkGenerateCodes',
    ]);

    $submitController = controllerWithPermissions([
        'giftVoucher-manageCodes',
        'giftVoucher-bulkGenerateCodes',
    ]);
    $submitController->stopAtPostRequest = true;
    $submitController->init();

    try {
        $submitController->actionBulkGenerateSubmit();
    } catch (BoundaryReached) {
        check('Authorized bulk submissions reach POST validation', $submitController->guardCalls === [
            'giftVoucher-manageCodes',
            'giftVoucher-bulkGenerateCodes',
            'post',
        ]);
    }
}

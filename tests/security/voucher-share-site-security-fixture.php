<?php
namespace craft\base {
    class Element
    {
        public const SCENARIO_LIVE = 'live';
    }
}

namespace craft\helpers {
    class UrlHelper
    {
        public static function urlWithToken(string $url, string|false $token): string
        {
            return $url . '?token=' . $token;
        }
    }
}

namespace yii\web {
    class ForbiddenHttpException extends \RuntimeException
    {
    }

    class HttpException extends \RuntimeException
    {
        public function __construct(public int $statusCode)
        {
            parent::__construct((string)$statusCode, $statusCode);
        }
    }

    class Response
    {
        public function __construct(public string $url)
        {
        }
    }

    class ServerErrorHttpException extends \RuntimeException
    {
    }
}

namespace craft\web {
    use yii\web\ForbiddenHttpException;
    use yii\web\Response;

    class Controller
    {
        public array $grantedPermissions = [];
        public array $requiredPermissions = [];

        public function requirePermission(string $permission): void
        {
            $this->requiredPermissions[] = $permission;

            if (!in_array($permission, $this->grantedPermissions, true)) {
                throw new ForbiddenHttpException("Missing permission: {$permission}");
            }
        }

        public function redirect(string $url): Response
        {
            return new Response($url);
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
    class Voucher
    {
        public function __construct(
            public int $id,
            public int $siteId,
            private string $typeUid = 'type-uid',
        ) {
        }

        public function getType(): object
        {
            return (object)['uid' => $this->typeUid];
        }

        public function getUrl(): string
        {
            return 'https://example.test/voucher';
        }
    }
}

namespace {
    class Craft
    {
        public static mixed $app = null;
    }

    require __DIR__ . '/../../src/controllers/VouchersPreviewController.php';

    use verbb\giftvoucher\controllers\VouchersPreviewController;
    use verbb\giftvoucher\elements\Voucher;
    use verbb\giftvoucher\GiftVoucher;
    use yii\web\ForbiddenHttpException;
    use yii\web\Response;

    class FixtureSites
    {
        public function __construct(private array $editableSiteIds)
        {
        }

        public function getEditableSiteIds(): array
        {
            return $this->editableSiteIds;
        }
    }

    class FixtureTokens
    {
        public array $routes = [];

        public function createToken(array $route): string
        {
            $this->routes[] = $route;

            return 'share-token';
        }
    }

    class FixtureApp
    {
        public FixtureSites $sites;
        public FixtureTokens $tokens;

        public function __construct(array $editableSiteIds)
        {
            $this->sites = new FixtureSites($editableSiteIds);
            $this->tokens = new FixtureTokens();
        }

        public function getSites(): FixtureSites
        {
            return $this->sites;
        }

        public function getTokens(): FixtureTokens
        {
            return $this->tokens;
        }
    }

    class FixtureVouchers
    {
        public array $lookups = [];
        public ?Voucher $voucher = null;

        public function getVoucherById(mixed $voucherId, mixed $siteId): ?Voucher
        {
            $this->lookups[] = [$voucherId, $siteId];

            return $this->voucher;
        }
    }

    class FixtureVoucherTypes
    {
        public int $validationCount = 0;

        public function isVoucherTypeTemplateValid(object $type, int $siteId): bool
        {
            $this->validationCount++;

            return true;
        }
    }

    class FixturePlugin
    {
        public FixtureVouchers $vouchers;
        public FixtureVoucherTypes $voucherTypes;

        public function __construct()
        {
            $this->vouchers = new FixtureVouchers();
            $this->voucherTypes = new FixtureVoucherTypes();
        }

        public function getVouchers(): FixtureVouchers
        {
            return $this->vouchers;
        }

        public function getVoucherTypes(): FixtureVoucherTypes
        {
            return $this->voucherTypes;
        }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    function fixtureController(array $editableSiteIds, Voucher $voucher): VouchersPreviewController
    {
        Craft::$app = new FixtureApp($editableSiteIds);
        GiftVoucher::$plugin = new FixturePlugin();
        GiftVoucher::$plugin->vouchers->voucher = $voucher;

        $controller = new VouchersPreviewController();
        $controller->grantedPermissions = ['giftVoucher-manageVoucherType:type-uid'];

        return $controller;
    }

    $controller = fixtureController([1], new Voucher(10, 2));

    try {
        $controller->actionShareVoucher(10, 2);
        throw new RuntimeException('Failed: restricted site created a token');
    } catch (ForbiddenHttpException) {
        check('Restricted voucher sites cannot issue share tokens', Craft::$app->tokens->routes === []);
        check('Restricted sites are rejected before template validation', GiftVoucher::$plugin->voucherTypes->validationCount === 0);
    }

    $controller = fixtureController([1], new Voucher(10, 2));

    try {
        $controller->actionShareVoucher(10, '*');
        throw new RuntimeException('Failed: alternate selector created a token for a restricted site');
    } catch (ForbiddenHttpException) {
        check('Alternate selectors cannot bypass resolved-site authorization', Craft::$app->tokens->routes === []);
    }

    $controller = fixtureController([2], new Voucher(10, 2));
    $response = $controller->actionShareVoucher(10, '*');
    $route = Craft::$app->tokens->routes[0] ?? null;

    check('Editable voucher sites can still issue share tokens', $response instanceof Response);
    check('Share issuance retains the voucher type permission', $controller->requiredPermissions === [
        'giftVoucher-manageVoucherType:type-uid',
    ]);
    check('Share tokens use the resolved voucher site instead of the raw selector', $route === [
        'gift-voucher/vouchers-preview/view-shared-voucher',
        ['voucherId' => 10, 'siteId' => 2],
    ]);

    $viewAction = new ReflectionMethod(VouchersPreviewController::class, 'actionViewSharedVoucher');
    check('The shared-view action consumes the stored site parameter',
        $viewAction->getParameters()[1]->getName() === 'siteId');
}

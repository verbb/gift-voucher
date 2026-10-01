<?php
namespace craft\web {
    class Request
    {
        public function __construct(private array $params = [])
        {
        }

        public function getBodyParam(string $name, mixed $defaultValue = null): mixed
        {
            return $this->params[$name] ?? $defaultValue;
        }
    }
}

namespace yii\web {
    class BadRequestHttpException extends \RuntimeException
    {
    }

    class ForbiddenHttpException extends \RuntimeException
    {
    }

    class NotFoundHttpException extends \RuntimeException
    {
    }
}

namespace verbb\giftvoucher\elements {
    class Voucher
    {
        public ?int $siteId = 1;
        public mixed $typeId = null;
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

        public static function t(string $category, string $message, array $params = []): string
        {
            return strtr($message, $params);
        }
    }

    require __DIR__ . '/../../src/helpers/VoucherHelper.php';

    use craft\web\Request;
    use verbb\giftvoucher\elements\Voucher;
    use verbb\giftvoucher\GiftVoucher;
    use verbb\giftvoucher\helpers\VoucherHelper;
    use yii\web\BadRequestHttpException;
    use yii\web\ForbiddenHttpException;

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

    class FixtureApp
    {
        public function __construct(private FixtureSites $sites)
        {
        }

        public function getSites(): FixtureSites
        {
            return $this->sites;
        }
    }

    class FixtureVouchers
    {
        public function __construct(private array $voucherSites)
        {
        }

        public function getVoucherById(mixed $voucherId, mixed $siteId): ?Voucher
        {
            if (!isset($this->voucherSites[$voucherId])) {
                return null;
            }

            $voucher = new Voucher();
            $voucher->siteId = $siteId ?? $this->voucherSites[$voucherId];

            return $voucher;
        }
    }

    class FixturePlugin
    {
        public function __construct(private FixtureVouchers $vouchers)
        {
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

    function throws(string $exceptionClass, callable $callback): bool
    {
        try {
            $callback();
        } catch (Throwable $e) {
            return $e instanceof $exceptionClass;
        }

        return false;
    }

    Craft::$app = new FixtureApp(new FixtureSites([1, 2]));
    GiftVoucher::$plugin = new FixturePlugin(new FixtureVouchers([
        10 => 1,
        20 => 3,
    ]));

    $voucher = VoucherHelper::voucherFromPost(new Request([
        'voucherId' => 10,
        'siteId' => '2',
    ]));
    check('Editable posted site IDs are normalized and retained', $voucher->siteId === 2);

    check(
        'Posted restricted site IDs are rejected',
        throws(ForbiddenHttpException::class, fn() => VoucherHelper::voucherFromPost(new Request([
            'voucherId' => 10,
            'siteId' => '3',
        ]))),
    );

    check(
        'Omitting siteId cannot load a restricted site copy',
        throws(ForbiddenHttpException::class, fn() => VoucherHelper::voucherFromPost(new Request([
            'voucherId' => 20,
        ]))),
    );

    check(
        'Malformed site IDs are rejected before lookup',
        throws(BadRequestHttpException::class, fn() => VoucherHelper::voucherFromPost(new Request([
            'voucherId' => 10,
            'siteId' => '2 OR 3',
        ]))),
    );

    $voucher = VoucherHelper::voucherFromPost(new Request([
        'typeId' => 5,
    ]));
    check('Single-site-style creates without siteId retain the default site', $voucher->siteId === 1);
}

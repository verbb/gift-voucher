<?php
namespace craft\commerce\base {
    class Purchasable
    {
        public const STATUS_ENABLED = 'enabled';
        public const STATUS_DISABLED = 'disabled';

        public bool $previewing = false;
        public ?int $siteId = null;
        public mixed $title = null;
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

    require __DIR__ . '/../../src/elements/Voucher.php';

    use verbb\giftvoucher\elements\Voucher;

    class FixtureSites
    {
        public object $currentSite;

        public function __construct(int $siteId)
        {
            $this->currentSite = (object)['id' => $siteId];
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

    class FixtureVoucherType
    {
        public function __construct(private array $siteSettings)
        {
        }

        public function getSiteSettings(): array
        {
            return $this->siteSettings;
        }
    }

    class FixtureVoucher extends Voucher
    {
        public function __construct(private string $status, private FixtureVoucherType $voucherType)
        {
        }

        public function getStatus(): ?string
        {
            return $this->status;
        }

        public function getType(): FixtureVoucherType
        {
            return $this->voucherType;
        }

        public function resolveRoute(): array|string|null
        {
            return $this->route();
        }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    Craft::$app = new FixtureApp(new FixtureSites(1));

    $voucherType = new FixtureVoucherType([
        1 => (object)[
            'hasUrls' => true,
            'template' => 'gift-voucher/voucher',
        ],
    ]);

    $liveVoucher = new FixtureVoucher(Voucher::STATUS_LIVE, $voucherType);
    $liveRoute = $liveVoucher->resolveRoute();
    check('Live vouchers remain publicly routable', is_array($liveRoute));
    check('Live vouchers retain their configured template', $liveRoute[1]['template'] === 'gift-voucher/voucher');

    foreach ([Voucher::STATUS_PENDING, Voucher::STATUS_EXPIRED, Voucher::STATUS_DISABLED] as $status) {
        $voucher = new FixtureVoucher($status, $voucherType);
        check("{$status} vouchers are not publicly routable", $voucher->resolveRoute() === null);

        $voucher->previewing = true;
        check("{$status} vouchers remain routable in preview mode", is_array($voucher->resolveRoute()));
    }

    $voucherTypeWithoutUrls = new FixtureVoucherType([
        1 => (object)[
            'hasUrls' => false,
            'template' => 'gift-voucher/voucher',
        ],
    ]);
    $liveVoucherWithoutUrls = new FixtureVoucher(Voucher::STATUS_LIVE, $voucherTypeWithoutUrls);
    check('Live vouchers without configured URLs remain unroutable', $liveVoucherWithoutUrls->resolveRoute() === null);
}

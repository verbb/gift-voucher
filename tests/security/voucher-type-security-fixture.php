<?php
namespace craft\web {
    class Controller
    {
        public array $guardCalls = [];

        public function __construct(
            private bool $cpRequest = true,
            private bool $admin = true,
            private bool $allowAdminChanges = true,
        ) {
        }

        public function init(): void
        {
        }

        public function requireCpRequest(): void
        {
            $this->guardCalls[] = 'cp';

            if (!$this->cpRequest) {
                throw new \RuntimeException('Request must be a control panel request.');
            }
        }

        public function requireAdmin(bool $requireAdminChanges = true): void
        {
            $this->guardCalls[] = 'admin';

            if (!$this->admin) {
                throw new \RuntimeException('User must be an administrator.');
            }

            if ($requireAdminChanges && !$this->allowAdminChanges) {
                throw new \RuntimeException('Administrative changes are disabled.');
            }
        }
    }
}

namespace {
    require __DIR__ . '/../../src/controllers/VoucherTypesController.php';

    use verbb\giftvoucher\controllers\VoucherTypesController;

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    function controllerThrows(bool $cpRequest, bool $admin, bool $allowAdminChanges): bool
    {
        $controller = new VoucherTypesController($cpRequest, $admin, $allowAdminChanges);

        try {
            $controller->init();
        } catch (RuntimeException) {
            return true;
        }

        return false;
    }

    check(
        'Generic site action routes cannot reach voucher type actions',
        controllerThrows(false, true, true),
    );

    check(
        'Delegated non-admin users cannot reach voucher type actions',
        controllerThrows(true, false, true),
    );

    check(
        'Read-only environments cannot mutate voucher types',
        controllerThrows(true, true, false),
    );

    $controller = new VoucherTypesController(true, true, true);
    $controller->init();
    check(
        'Administrators can manage voucher types when admin changes are allowed',
        $controller->guardCalls === ['cp', 'admin'],
    );

    $indexTemplate = file_get_contents(__DIR__ . '/../../src/templates/voucher-types/index.html');
    check(
        'Read-only voucher type listings require an administrator',
        str_contains($indexTemplate, '{% requireAdmin false %}'),
    );
}

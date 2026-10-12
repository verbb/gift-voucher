<?php
namespace {
    $vendor = getenv('GIFT_VOUCHER_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
    require $vendor . '/yiisoft/yii2/Yii.php';
    set_error_handler(static function($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    class Craft { public static mixed $app; }
}

namespace craft\base {
    interface ElementInterface {}
    class Element extends \yii\base\Model implements ElementInterface
    {
        public const SCENARIO_LIVE = 'live';
        public int $siteId = 1;
        public bool $enabled = false;
        public function rules(): array { return $this->defineRules(); }
        protected function defineRules(): array { return []; }
        public function scenarios(): array { return array_merge(parent::scenarios(), ['live' => parent::scenarios()['default']]); }
        public function canView(\craft\elements\User $user): bool { return $user->parentAuthorized; }
        public function canSave(\craft\elements\User $user): bool { return $user->parentAuthorized; }
        public function canDuplicate(\craft\elements\User $user): bool { return $user->parentAuthorized; }
        public function canDelete(\craft\elements\User $user): bool { return $user->parentAuthorized; }
        public function getIsDraft(): bool { return false; }
        public function getIsRevision(): bool { return false; }
        public function setAttributesFromRequest(array $values): void { $this->setAttributes($values); }
        public function setFieldValuesFromRequest($location): void {}
    }
}

namespace craft\commerce\base {
    class Purchasable extends \craft\base\Element {}
}

namespace craft\elements {
    class User
    {
        public bool $parentAuthorized = false;
        public function __construct(public array $allowed) {}
        public function can($permission): bool { return in_array($permission, $this->allowed, true); }
    }
}

namespace craft\validators {
    class DateTimeValidator extends \yii\validators\SafeValidator {}
}

namespace craft\helpers {
    class Component
    {
        public static function validateComponentClass($class, $interface): bool { return is_subclass_of($class, $interface); }
    }
}

namespace craft\elements\db {
    interface ElementQueryInterface {}
}

namespace verbb\giftvoucher\elements\db {
    class VoucherQuery implements \craft\elements\db\ElementQueryInterface
    {
        public function __construct($type) {}
        public function __call($name, $arguments): self { return $this; }
        public function one(): mixed { return \Craft::$app->voucher; }
    }
}

namespace verbb\giftvoucher\models {
    class VoucherType
    {
        public function __construct(public int $id, public string $uid) {}
    }
}

namespace verbb\giftvoucher {
    class GiftVoucher { public static mixed $plugin; }
}

namespace craft\web {
    class Controller
    {
        public mixed $request;
        protected function requirePostRequest(): void {}
        protected static function currentUser(): \craft\elements\User { return \Craft::$app->user; }
    }
}

namespace {
    use craft\controllers\ElementsController;
    use craft\elements\User;
    use verbb\giftvoucher\elements\Voucher;
    use verbb\giftvoucher\GiftVoucher;
    use verbb\giftvoucher\models\VoucherType;

    require __DIR__ . '/../../src/elements/Voucher.php';
    require $vendor . '/craftcms/cms/src/controllers/ElementsController.php';

    class FixtureSaved extends \RuntimeException {}
    class FixtureApp
    {
        public Voucher $voucher;
        public User $user;
        public array $types;
        public array $lookups = [];
        public array $permissionChecks = [];
        public ?int $savedType = null;
        public function getVoucherTypes(): self { return $this; }
        public function getVoucherTypeById($id): ?VoucherType { $this->lookups[] = $id; return $this->types[$id] ?? null; }
        public function getElements(): self { return $this; }
        public function getSites(): self { return $this; }
        public function getSiteById($id, $withDisabled): object { return (object)['id' => 1]; }
        public function getIsMultiSite(): bool { return false; }
        public function canView($voucher, $user = null): bool
        {
            $this->permissionChecks[] = ['view', $voucher->typeId];
            return $voucher->canView($user ?? $this->user);
        }
        public function canSave($voucher, $user = null): bool
        {
            $this->permissionChecks[] = ['save', $voucher->typeId];
            return $voucher->canSave($user ?? $this->user);
        }
        public function getMutex(): self { return $this; }
        public function acquire($key, $timeout): bool { return true; }
        public function release($key): void {}
        public function saveElement($voucher, $crossSiteValidate = false): never
        {
            $this->savedType = $voucher->typeId;
            // Stop at the persistence boundary; no application database is used.
            throw new FixtureSaved();
        }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) { throw new \RuntimeException('Failed: ' . $label); }
        echo $label . ": PASS\n";
    }

    function voucher(?int $typeId = 1): Voucher
    {
        return new Voucher(['id' => 123, 'typeId' => $typeId, 'sku' => 'SKU', 'price' => 10]);
    }

    function genericSave(Voucher $voucher, User $user, mixed $destination): bool
    {
        $app = Craft::$app;
        $app->voucher = $voucher;
        $app->user = $user;
        $app->savedType = null;
        $app->permissionChecks = [];
        $controller = new ElementsController();
        $controller->request = new class {
            public function getHeaders(): \yii\web\HeaderCollection { return new \yii\web\HeaderCollection(); }
        };
        // Supply parsed request state, then execute Craft's real action and attribute assignment.
        foreach (['_elementType' => Voucher::class, '_elementId' => 123, '_siteId' => 1, '_provisional' => false, '_applyParams' => true, '_fresh' => false, '_fieldsLocation' => 'fields', '_attributes' => ['typeId' => $destination]] as $name => $value) {
            (new \ReflectionProperty(ElementsController::class, $name))->setValue($controller, $value);
        }
        try {
            $controller->actionSave();
        } catch (\yii\web\ForbiddenHttpException) {
            return false;
        } catch (FixtureSaved) {
            return true;
        }
        throw new \RuntimeException('Unexpected save result.');
    }

    Craft::$app = GiftVoucher::$plugin = $app = new FixtureApp();
    $app->types = [1 => new VoucherType(1, 'a'), 2 => new VoucherType(2, 'b')];
    $aOnly = new User(['giftVoucher-manageVoucherType:a']);
    $both = new User(['giftVoucher-manageVoucherType:a', 'giftVoucher-manageVoucherType:b']);
    $bOnly = new User(['giftVoucher-manageVoucherType:b']);

    check('Generic save blocks A-only staff moving A to B', !genericSave(voucher(), $aOnly, '2') && $app->savedType === null);
    check('Generic save retains authorised A to B changes', genericSave(voucher(), $both, '2') && $app->savedType === 2);
    check('Generic save checks both source and destination', $app->permissionChecks === [['view', 1], ['save', 1], ['view', 2], ['save', 2]]);
    check('Generic save retains ordinary same-type editing', genericSave(voucher(), $aOnly, '1') && $app->savedType === 1);
    check('Destination permission cannot bypass source permission', !genericSave(voucher(), $bOnly, 2));
    foreach ([null, 0, 999] as $id) {
        check('Generic save denies a missing destination: ' . var_export($id, true), !genericSave(voucher(), $aOnly, $id) && $app->savedType === null);
    }

    foreach (['canView', 'canSave', 'canDuplicate', 'canDelete', 'canDeleteForSite'] as $method) {
        $element = voucher();
        check("$method permits the original type", $element->$method($aOnly));
        $element->typeId = 2;
        check("$method refreshes a changed type", !$element->$method($aOnly) && $element->$method($both));
        foreach ([null, 0, 999] as $id) {
            $element->typeId = $id;
            check("$method denies an unavailable type " . var_export($id, true), !$element->$method($aOnly));
        }
        $element->typeId = 1;
        check("$method recovers when a valid type is restored", $element->$method($aOnly));
    }

    $element = voucher();
    $app->lookups = [];
    $element->getType();
    $element->getType();
    check('Unchanged type still uses its cache', $app->lookups === [1]);
    $clone = clone $element;
    $clone->typeId = 2;
    check('A copied cache follows the clone type', $clone->getType()->id === 2 && $element->getType()->id === 1);
    $element->typeId = null;
    check('Clearing typeId clears the cached type', $element->getType() === null);
    $element->typeId = 2;
    check('Programmatic type changes remain available', $element->getType()->id === 2);
    $element = voucher();
    $element->id = null;
    check('New vouchers retain type permissions', $element->canSave($aOnly));
    $user = new User([]);
    $user->parentAuthorized = true;
    foreach (['canView', 'canSave', 'canDuplicate', 'canDelete'] as $method) {
        check('Parent authorization remains supported: ' . $method, $element->$method($user));
    }
}

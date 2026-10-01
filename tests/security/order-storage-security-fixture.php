<?php
namespace craft\base {
    interface ElementInterface
    {
    }
}

namespace craft\fields {
    use craft\base\ElementInterface;

    class BaseRelationField
    {
        public ?string $handle = null;

        public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
        {
            return ['source' => 'value', 'value' => $value];
        }

        public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
        {
            return ['source' => 'request', 'value' => $value];
        }
    }
}

namespace craft\commerce\elements {
    use craft\base\ElementInterface;

    class Order implements ElementInterface
    {
        public function __construct(private mixed $fieldValue = null)
        {
        }

        public function getFieldValue(string $fieldHandle): mixed
        {
            return $this->fieldValue;
        }
    }
}

namespace verbb\giftvoucher\elements {
    class Code
    {
    }
}

namespace verbb\giftvoucher\storage {
    class Order
    {
        public ?string $fieldHandle = null;
    }

    class Session
    {
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
        public static function t(string $category, string $message): string
        {
            return $message;
        }
    }

    require __DIR__ . '/../../src/fields/Codes.php';

    use craft\base\ElementInterface;
    use craft\commerce\elements\Order as CommerceOrder;
    use verbb\giftvoucher\fields\Codes;
    use verbb\giftvoucher\GiftVoucher;
    use verbb\giftvoucher\storage\Order as OrderStorage;
    use verbb\giftvoucher\storage\Session;

    class FixtureElement implements ElementInterface
    {
    }

    class FixturePlugin
    {
        public function __construct(private mixed $storage)
        {
        }

        public function getCodeStorage(): mixed
        {
            return $this->storage;
        }
    }

    function check(string $label, bool $condition): void
    {
        if (!$condition) {
            throw new RuntimeException("Failed: {$label}");
        }

        echo "{$label}: PASS\n";
    }

    $field = new Codes();
    $field->handle = 'giftVoucherCodes';
    $storedCodes = ['persisted-code-relation'];
    $order = new CommerceOrder($storedCodes);
    $submittedIds = [101, 202];

    $orderStorage = new OrderStorage();
    $orderStorage->fieldHandle = 'giftVoucherCodes';
    GiftVoucher::$plugin = new FixturePlugin($orderStorage);

    check(
        'Posted IDs cannot replace the configured Order storage relationship',
        $field->normalizeValueFromRequest($submittedIds, $order) === $storedCodes
    );

    check(
        'Alternate posted field representations cannot replace the configured relationship',
        $field->normalizeValueFromRequest('101', $order) === $storedCodes
    );

    check(
        'Programmatic Order storage writes retain the standard normalization path',
        $field->normalizeValue($submittedIds, $order) === ['source' => 'value', 'value' => $submittedIds]
    );

    $orderStorage->fieldHandle = 'otherVoucherCodes';
    check(
        'Unconfigured Gift Voucher Code fields retain request population',
        $field->normalizeValueFromRequest($submittedIds, $order) === ['source' => 'request', 'value' => $submittedIds]
    );

    GiftVoucher::$plugin = new FixturePlugin(new Session());
    check(
        'Session storage does not change Gift Voucher Code field behavior',
        $field->normalizeValueFromRequest($submittedIds, $order) === ['source' => 'request', 'value' => $submittedIds]
    );

    $orderStorage->fieldHandle = 'giftVoucherCodes';
    GiftVoucher::$plugin = new FixturePlugin($orderStorage);
    check(
        'Non-order elements retain Gift Voucher Code field behavior',
        $field->normalizeValueFromRequest($submittedIds, new FixtureElement()) === ['source' => 'request', 'value' => $submittedIds]
    );
}

<?php

declare(strict_types=1);

namespace verbb\giftvoucher\elements\conditions;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use verbb\giftvoucher\elements\db\CodeQuery;
use verbb\giftvoucher\elements\Voucher as ElementsVoucher;

/**
 * Voucher element condition rule
 */
final class Voucher extends BaseElementSelectConditionRule implements ElementConditionRuleInterface {
    public function getLabel(): string {
        return Craft::t('gift-voucher', 'Gift Voucher');
    }

    public function getExclusiveQueryParams(): array {
        return ['voucherId'];
    }

    public function modifyQuery(ElementQueryInterface $query): void {
        /** @var CodeQuery $query */
        $query->voucherId($this->getElementId());
    }

    public function matchElement(ElementInterface $element): bool {
        /** @var ElementsVoucher $element */
        return $this->matchValue($element->id);
    }

    protected function elementType(): string {
        return ElementsVoucher::class;
    }

    protected function sources(): ?array {
        return null;
    }

    protected function criteria(): ?array {
        return null;
    }
}

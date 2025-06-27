<?php

declare(strict_types=1);

namespace verbb\giftvoucher\elements\conditions;

use craft\elements\conditions\ElementCondition;

/**
 * Code conditions
 */
final class CodeConditions extends ElementCondition {
    protected function selectableConditionRules(): array {
        return array_merge(parent::selectableConditionRules(), [
            Voucher::class,
        ]);
    }
}

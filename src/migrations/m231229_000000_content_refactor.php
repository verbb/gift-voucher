<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\elements\Voucher;

use craft\db\Query;
use craft\migrations\BaseContentRefactorMigration;

class m231229_000000_content_refactor extends BaseContentRefactorMigration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach (GiftVoucher::$plugin->getVoucherTypes()->getAllVoucherTypes() as $type) {
            $this->updateElements(
                (new Query())->from('{{%giftvoucher_vouchers}}')->where(['typeId' => $type->id]),
                $type->getFieldLayout(),
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231229_000000_content_refactor cannot be reverted.\n";

        return false;
    }
}

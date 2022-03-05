<?php
namespace verbb\giftvoucher\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m200729_000000_permissions_to_uid extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from([Table::USERPERMISSIONS])
            ->pairs();

        $productTypeMap = (new Query())
            ->select(['id', 'uid'])
            ->from('{{%giftvoucher_vouchertypes}}')
            ->pairs();

        $relations = [
            'giftVoucher-manageVoucherType' => $productTypeMap,
        ];

        foreach ($permissions as $id => $permission) {
            if (
                preg_match('/([\w]+)(:|-)([\d]+)/i', $permission, $matches) &&
                array_key_exists(strtolower($matches[1]), $relations) &&
                !empty($relations[strtolower($matches[1])][$matches[3]])
            ) {
                $permission = $matches[1] . $matches[2] . $relations[strtolower($matches[1])][$matches[3]];
                $this->update(Table::USERPERMISSIONS, ['name' => $permission], ['id' => $id]);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m200729_000000_permissions_to_uid cannot be reverted.\n";

        return false;
    }
}

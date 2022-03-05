<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Voucher;
use verbb\giftvoucher\records\VoucherTypeSiteRecord;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

class m181017_000000_craft3_version extends Migration
{
    public function safeUp(): bool
    {
        // Update all the Element references
        $this->update('{{%elements}}', ['type' => Voucher::class], ['type' => 'GiftVoucher_Voucher']);

        if ($this->db->tableExists('{{%giftvoucher_vouchertypes_i18n}}')) {
            // Before messing with columns, it's much safer to drop all the FKs and indexes
            MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_vouchertypes_i18n}}');
            MigrationHelper::dropAllIndexesOnTable('{{%giftvoucher_vouchertypes_i18n}}');

            // Drop the old locale FK column and rename the new siteId FK column
            $this->dropColumn('{{%giftvoucher_vouchertypes_i18n}}', 'locale');
            MigrationHelper::renameColumn('{{%giftvoucher_vouchertypes_i18n}}', 'locale__siteId', 'siteId', $this);

            // And then just recreate them.
            $this->createIndex($this->db->getIndexName(), '{{%giftvoucher_vouchertypes_i18n}}', 'voucherTypeId,siteId', true);
            $this->createIndex($this->db->getIndexName(), '{{%giftvoucher_vouchertypes_i18n}}', 'siteId', false);
            $this->addForeignKey($this->db->getForeignKeyName(), '{{%giftvoucher_vouchertypes_i18n}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
            $this->addForeignKey($this->db->getForeignKeyName(), '{{%giftvoucher_vouchertypes_i18n}}', 'voucherTypeId', '{{%giftvoucher_vouchertypes}}', 'id', 'CASCADE', null);

            $this->addColumn('{{%giftvoucher_vouchertypes_i18n}}', 'template', $this->string(500));
            $this->addColumn('{{%giftvoucher_vouchertypes_i18n}}', 'hasUrls', $this->boolean());

            // Migrate hasUrls to be site specific
            $voucherTypes = (new Query())->select('id, hasUrls, template')->from('{{%giftvoucher_vouchertypes}}')->all();

            foreach ($voucherTypes as $voucherType) {
                $voucherTypeSites = (new Query())->select('*')->from('{{%giftvoucher_vouchertypes_i18n}}')->all();

                foreach ($voucherTypeSites as $voucherTypeSite) {
                    $voucherTypeSite['template'] = $voucherType['template'];
                    $voucherTypeSite['hasUrls'] = $voucherType['hasUrls'];
                    $this->update('{{%giftvoucher_vouchertypes_i18n}}', $voucherTypeSite, ['id' => $voucherTypeSite['id']]);
                }
            }
        }

        if ($this->db->columnExists('{{%giftvoucher_vouchertypes}}', 'template')) {
            $this->dropColumn('{{%giftvoucher_vouchertypes}}', 'template');
        }

        if ($this->db->columnExists('{{%giftvoucher_vouchertypes}}', 'hasUrls')) {
            $this->dropColumn('{{%giftvoucher_vouchertypes}}', 'hasUrls');
        }

        if ($this->db->tableExists('{{%giftvoucher_vouchertypes_i18n}}')) {
            MigrationHelper::renameTable('{{%giftvoucher_vouchertypes_i18n}}', VoucherTypeSiteRecord::tableName(), $this);
            MigrationHelper::renameColumn(VoucherTypeSiteRecord::tableName(), 'urlFormat', 'uriFormat', $this);
        }

        if ($this->db->columnExists('{{%giftvoucher_codes}}', 'manually')) {
            $this->dropColumn('{{%giftvoucher_codes}}', 'manually');
        }

        if ($this->db->columnExists('{{%giftvoucher_codes}}', 'redeemed')) {
            $this->dropColumn('{{%giftvoucher_codes}}', 'redeemed');
        }


        $this->addColumn('{{%giftvoucher_vouchers}}', 'postDate', $this->dateTime());
        $this->addColumn('{{%giftvoucher_vouchers}}', 'expiryDate', $this->dateTime());
        $this->dropColumn('{{%giftvoucher_vouchers}}', 'expiry');


        $this->dropTable('{{%giftvoucher_voucher_products}}');
        $this->dropTable('{{%giftvoucher_voucher_producttypes}}');

        return true;
    }

    public function safeDown(): bool
    {
        echo "m181017_000000_craft3_version cannot be reverted.\n";
        return false;
    }
}

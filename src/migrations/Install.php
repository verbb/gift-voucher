<?php
namespace verbb\giftvoucher\migrations;

use verbb\giftvoucher\elements\Code;

use Craft;
use craft\db\Migration;
use craft\helpers\Db;
use craft\helpers\MigrationHelper;
use craft\records\FieldLayout;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        // Don't make the same config changes twice
        $installed = (Craft::$app->projectConfig->get('plugins.gift-voucher', true) !== null);
        $configExists = (Craft::$app->projectConfig->get('gift-voucher', true) !== null);

        if (!$installed && !$configExists) {
            $this->insert(FieldLayout::tableName(), ['type' => Code::class]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropProjectConfig();
        $this->dropForeignKeys();
        $this->dropTables();

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%giftvoucher_codes}}');
        $this->createTable('{{%giftvoucher_codes}}', [
            'id' => $this->primaryKey(),
            'voucherId' => $this->integer(),
            'orderId' => $this->integer(),
            'lineItemId' => $this->integer(),
            'codeKey' => $this->string()->notNull(),
            'originalAmount' => $this->decimal(12, 2)->notNull(),
            'currentAmount' => $this->decimal(12, 2)->notNull(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%giftvoucher_redemptions}}');
        $this->createTable('{{%giftvoucher_redemptions}}', [
            'id' => $this->primaryKey(),
            'codeId' => $this->integer(),
            'orderId' => $this->integer(),
            'amount' => $this->decimal(12, 2)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%giftvoucher_vouchers}}');
        $this->createTable('{{%giftvoucher_vouchers}}', [
            'id' => $this->primaryKey(),
            'typeId' => $this->integer(),
            'taxCategoryId' => $this->integer()->notNull(),
            'shippingCategoryId' => $this->integer()->notNull(),
            'postDate' => $this->dateTime(),
            'expiryDate' => $this->dateTime(),
            'promotable' => $this->boolean(),
            'availableForPurchase' => $this->boolean(),
            'sku' => $this->string()->notNull(),
            'price' => $this->decimal(12, 2)->notNull(),
            'customAmount' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%giftvoucher_vouchertypes}}');
        $this->createTable('{{%giftvoucher_vouchertypes}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'skuFormat' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%giftvoucher_vouchertypes_sites}}');
        $this->createTable('{{%giftvoucher_vouchertypes_sites}}', [
            'id' => $this->primaryKey(),
            'voucherTypeId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'hasUrls' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%giftvoucher_codes}}', 'codeKey', true);
        $this->createIndex(null, '{{%giftvoucher_codes}}', 'voucherId', false);
        $this->createIndex(null, '{{%giftvoucher_codes}}', 'orderId', false);
        $this->createIndex(null, '{{%giftvoucher_codes}}', 'lineItemId', false);

        $this->createIndex(null, '{{%giftvoucher_redemptions}}', 'codeId', false);
        $this->createIndex(null, '{{%giftvoucher_redemptions}}', 'orderId', false);

        $this->createIndex(null, '{{%giftvoucher_vouchers}}', 'sku', true);
        $this->createIndex(null, '{{%giftvoucher_vouchers}}', 'typeId', false);
        $this->createIndex(null, '{{%giftvoucher_vouchers}}', 'taxCategoryId', false);
        $this->createIndex(null, '{{%giftvoucher_vouchers}}', 'shippingCategoryId', false);

        $this->createIndex(null, '{{%giftvoucher_vouchertypes}}', 'handle', true);
        $this->createIndex(null, '{{%giftvoucher_vouchertypes}}', 'fieldLayoutId', false);

        $this->createIndex(null, '{{%giftvoucher_vouchertypes_sites}}', ['voucherTypeId', 'siteId'], true);
        $this->createIndex(null, '{{%giftvoucher_vouchertypes_sites}}', 'siteId', false);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%giftvoucher_codes}}', 'id', '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%giftvoucher_codes}}', 'lineItemId', '{{%commerce_lineitems}}', ['id'], 'SET NULL');
        $this->addForeignKey(null, '{{%giftvoucher_codes}}', 'orderId', '{{%commerce_orders}}', ['id'], 'SET NULL');
        $this->addForeignKey(null, '{{%giftvoucher_codes}}', 'voucherId', '{{%giftvoucher_vouchers}}', ['id'], 'SET NULL');

        $this->addForeignKey(null, '{{%giftvoucher_redemptions}}', 'codeId', '{{%giftvoucher_codes}}', ['id'], 'SET NULL');
        $this->addForeignKey(null, '{{%giftvoucher_redemptions}}', 'orderId', '{{%commerce_orders}}', ['id'], 'SET NULL');

        $this->addForeignKey(null, '{{%giftvoucher_vouchers}}', ['id'], '{{%elements}}', ['id'], 'CASCADE');
        $this->addForeignKey(null, '{{%giftvoucher_vouchers}}', ['shippingCategoryId'], '{{%commerce_shippingcategories}}', ['id']);
        $this->addForeignKey(null, '{{%giftvoucher_vouchers}}', ['taxCategoryId'], '{{%commerce_taxcategories}}', ['id']);
        $this->addForeignKey(null, '{{%giftvoucher_vouchers}}', ['typeId'], '{{%giftvoucher_vouchertypes}}', ['id'], 'CASCADE');

        $this->addForeignKey(null, '{{%giftvoucher_vouchertypes}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL');

        $this->addForeignKey(null, '{{%giftvoucher_vouchertypes_sites}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%giftvoucher_vouchertypes_sites}}', ['voucherTypeId'], '{{%giftvoucher_vouchertypes}}', ['id'], 'CASCADE');
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists('{{%giftvoucher_codes}}');
        $this->dropTableIfExists('{{%giftvoucher_redemptions}}');
        $this->dropTableIfExists('{{%giftvoucher_vouchers}}');
        $this->dropTableIfExists('{{%giftvoucher_vouchertypes}}');
        $this->dropTableIfExists('{{%giftvoucher_vouchertypes_sites}}');
    }

    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists('{{%giftvoucher_codes}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_codes}}', $this);
        }

        if ($this->db->tableExists('{{%giftvoucher_redemptions}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_redemptions}}', $this);
        }

        if ($this->db->tableExists('{{%giftvoucher_vouchers}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_vouchers}}', $this);
        }

        if ($this->db->tableExists('{{%giftvoucher_vouchertypes}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_vouchertypes}}', $this);
        }

        if ($this->db->tableExists('{{%giftvoucher_vouchertypes_sites}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_vouchertypes_sites}}', $this);
        }
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->projectConfig->remove('gift-voucher');
    }
}

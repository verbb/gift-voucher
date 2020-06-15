<?php
namespace verbb\giftvoucher\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown()
    {
        $this->dropForeignKeys();
        $this->dropTables();
        $this->dropProjectConfig();

        return true;
    }

    // Protected Methods
    // =========================================================================

    protected function createTables()
    {
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

        $this->createTable('{{%giftvoucher_redemptions}}', [
            'id' => $this->primaryKey(),
            'codeId' => $this->integer(),
            'orderId' => $this->integer(),
            'amount' => $this->decimal(12, 2)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%giftvoucher_vouchers}}', [
            'id' => $this->primaryKey(),
            'typeId' => $this->integer(),
            'taxCategoryId' => $this->integer()->notNull(),
            'shippingCategoryId' => $this->integer()->notNull(),
            'postDate' => $this->dateTime(),
            'expiryDate' => $this->dateTime(),
            'sku' => $this->string()->notNull(),
            'price' => $this->decimal(12, 2)->notNull(),
            'customAmount' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

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

    protected function dropTables()
    {
        $this->dropTable('{{%giftvoucher_codes}}');
        $this->dropTable('{{%giftvoucher_redemptions}}');
        $this->dropTable('{{%giftvoucher_vouchers}}');
        $this->dropTable('{{%giftvoucher_vouchertypes}}');
        $this->dropTable('{{%giftvoucher_vouchertypes_sites}}');
    }

    protected function createIndexes()
    {
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_codes}}', 'codeKey', true), '{{%giftvoucher_codes}}', 'codeKey', true);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_codes}}', 'voucherId', false), '{{%giftvoucher_codes}}', 'voucherId', false);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_codes}}', 'orderId', false), '{{%giftvoucher_codes}}', 'orderId', false);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_codes}}', 'lineItemId', false), '{{%giftvoucher_codes}}', 'lineItemId', false);

        $this->createIndex($this->db->getIndexName('{{%giftvoucher_redemptions}}', 'codeId', false), '{{%giftvoucher_redemptions}}', 'codeId', false);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_redemptions}}', 'orderId', false), '{{%giftvoucher_redemptions}}', 'orderId', false);

        $this->createIndex($this->db->getIndexName('{{%giftvoucher_vouchers}}', 'sku', true), '{{%giftvoucher_vouchers}}', 'sku', true);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_vouchers}}', 'typeId', false), '{{%giftvoucher_vouchers}}', 'typeId', false);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_vouchers}}', 'taxCategoryId', false), '{{%giftvoucher_vouchers}}', 'taxCategoryId', false);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_vouchers}}', 'shippingCategoryId', false), '{{%giftvoucher_vouchers}}', 'shippingCategoryId', false);

        $this->createIndex($this->db->getIndexName('{{%giftvoucher_vouchertypes}}', 'handle', true), '{{%giftvoucher_vouchertypes}}', 'handle', true);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_vouchertypes}}', 'fieldLayoutId', false), '{{%giftvoucher_vouchertypes}}', 'fieldLayoutId', false);

        $this->createIndex($this->db->getIndexName('{{%giftvoucher_vouchertypes_sites}}', ['voucherTypeId', 'siteId'], true), '{{%giftvoucher_vouchertypes_sites}}', ['voucherTypeId', 'siteId'], true);
        $this->createIndex($this->db->getIndexName('{{%giftvoucher_vouchertypes_sites}}', 'siteId', false), '{{%giftvoucher_vouchertypes_sites}}', 'siteId', false);
    }

    protected function addForeignKeys()
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

    protected function dropForeignKeys()
    {
        MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_codes}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_redemptions}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_vouchers}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_vouchertypes}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%giftvoucher_vouchertypes_sites}}', $this);
    }

    public function dropProjectConfig()
    {
        Craft::$app->projectConfig->remove('gift-voucher');
    }
}

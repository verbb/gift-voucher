<?php
namespace verbb\giftvoucher\migrations;

use craft\db\Migration;

class m260928_000000_voucher_reservations extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%giftvoucher_reservations}}')) {
            $this->createTable('{{%giftvoucher_reservations}}', [
                'id' => $this->primaryKey(),
                'codeId' => $this->integer()->notNull(),
                'orderId' => $this->integer(),
                'orderNumber' => $this->string()->notNull(),
                'amount' => $this->decimal(12, 2)->notNull(),
                'status' => $this->string()->notNull(),
                'transactionHash' => $this->string(),
                'message' => $this->text(),
                'dateResolved' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%giftvoucher_reservations}}', ['codeId', 'orderNumber'], true);
            $this->createIndex(null, '{{%giftvoucher_reservations}}', 'orderId', false);
            $this->createIndex(null, '{{%giftvoucher_reservations}}', 'status', false);

            $this->addForeignKey(null, '{{%giftvoucher_reservations}}', 'codeId', '{{%giftvoucher_codes}}', 'id', 'CASCADE');
            $this->addForeignKey(null, '{{%giftvoucher_reservations}}', 'orderId', '{{%commerce_orders}}', 'id', 'SET NULL');
        }

        if (!$this->db->columnExists('{{%giftvoucher_redemptions}}', 'reservationId')) {
            $this->addColumn('{{%giftvoucher_redemptions}}', 'reservationId', $this->integer()->after('id'));
            $this->createIndex(null, '{{%giftvoucher_redemptions}}', 'reservationId', true);
            $this->addForeignKey(null, '{{%giftvoucher_redemptions}}', 'reservationId', '{{%giftvoucher_reservations}}', 'id', 'SET NULL');
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->columnExists('{{%giftvoucher_redemptions}}', 'reservationId')) {
            $this->dropForeignKeyIfExists('{{%giftvoucher_redemptions}}', 'reservationId');
            $this->dropIndexIfExists('{{%giftvoucher_redemptions}}', 'reservationId', true);
            $this->dropColumn('{{%giftvoucher_redemptions}}', 'reservationId');
        }

        $this->dropTableIfExists('{{%giftvoucher_reservations}}');

        return true;
    }
}

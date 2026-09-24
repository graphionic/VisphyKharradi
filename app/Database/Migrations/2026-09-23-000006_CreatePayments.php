<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreatePayments
 *
 * One order may have multiple payment attempts (retry). Only one captured per order.
 * Amount is DECIMAL. No secrets stored (only filtered gateway response).
 */
class CreatePayments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'order_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'razorpay_order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'razorpay_payment_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'razorpay_signature' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'currency' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'null'       => false,
                'default'    => 'INR',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'created',
                'comment'    => 'created, attempted, captured, failed, cancelled — app constants',
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'failure_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'webhook_verified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
            'gateway_response' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Filtered, sanitized JSON as TEXT — not full secrets dump; TEXT for MySQL compat',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id', false, false, 'idx_payments_order_id');
        $this->forge->addUniqueKey('razorpay_payment_id', 'uq_payments_rpay_payment_id');
        $this->forge->addKey('razorpay_order_id', false, false, 'idx_payments_rpay_order_id');
        $this->forge->addKey('status', false, false, 'idx_payments_status');

        // No FK — order_id is indexed BIGINT UNSIGNED logical reference to orders.id.
        // Application must never hard-delete orders (historical); explicit service cleanup only.

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('payments', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('payments', true);
    }
}

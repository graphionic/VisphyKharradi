<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateOrders
 *
 * Historical purchase contract — immutable snapshots. Financial history must
 * survive package edits/deletion. RESTRICT on package FK preserves history.
 * Order numbers are public, human-readable FTP-YYYY-000001 via order_sequences.
 */
class CreateOrders extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'order_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'package_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'package_name_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'package_slug_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => false,
            ],
            'package_price_snapshot' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'package_duration_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'customer_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'customer_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => false,
            ],
            'customer_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'null'       => false,
            ],
            'currency' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'null'       => false,
                'default'    => 'INR',
            ],
            'subtotal' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'discount_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => '0.00',
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'pending',
                'comment'    => 'App constants: pending, paid, failed, cancelled — no ENUM for migration ease',
            ],
            'razorpay_order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addUniqueKey('order_number', 'uq_orders_order_number');
        $this->forge->addUniqueKey('razorpay_order_id', 'uq_orders_rpay_order_id');
        $this->forge->addKey('package_id', false, false, 'idx_orders_package_id');
        $this->forge->addKey('status', false, false, 'idx_orders_status');
        $this->forge->addKey('customer_email', false, false, 'idx_orders_customer_email');
        $this->forge->addKey('created_at', false, false, 'idx_orders_created_at');

        // No FK — package_id is indexed BIGINT UNSIGNED logical reference to packages.id.
        // History preservation via snapshots + app rule: never hard-delete referenced package (soft archive).

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('orders', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('orders', true);
    }
}

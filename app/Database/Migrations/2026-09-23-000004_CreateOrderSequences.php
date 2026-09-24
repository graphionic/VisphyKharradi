<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateOrderSequences
 *
 * LOCKED Phase 1 — FTP-YYYY-000001 via year-scoped counter.
 * Single row per year, last_number increments atomically in transaction
 * via INSERT ... ON DUPLICATE KEY UPDATE last_number = last_number + 1
 * then SELECT. Row-level lock guarantees concurrency safety.
 */
class CreateOrderSequences extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'year' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'last_number' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 0,
            ],
        ]);

        $this->forge->addKey('year', true);

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('order_sequences', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('order_sequences', true);
    }
}

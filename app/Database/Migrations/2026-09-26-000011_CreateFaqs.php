<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateFaqs Migration — Phase 06: Dynamic FAQ System
 *
 * Stores FAQ items with display ordering, categorization, and active toggle.
 * ZERO FOREIGN KEYS — follows mandatory project security architecture.
 */
class CreateFaqs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'question' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'answer' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'display_order' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
                'default'    => 100,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'display_order'], false, false, 'idx_faqs_active_order');
        $this->forge->addKey('category', false, false, 'idx_faqs_category');

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('faqs', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('faqs', true);
    }
}

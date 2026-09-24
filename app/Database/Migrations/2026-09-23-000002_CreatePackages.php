<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreatePackages
 *
 * Phase 2 — Only dynamic content in V1 (plus package_features).
 * Soft delete enabled — archive instead of destroy when orders exist.
 */
class CreatePackages extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => false,
            ],
            'short_description' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'null'       => true,
            ],
            'full_description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'regular_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'selling_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'duration_value' => [
                'type'       => 'SMALLINT',
                'unsigned'   => true,
                'null'       => true,
            ],
            'duration_unit' => [
                'type'       => 'ENUM',
                'constraint' => ['days', 'weeks', 'months'],
                'null'       => true,
                'default'    => null,
            ],
            'badge' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'cta_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'Get Started',
            ],
            'google_form_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 2048,
                'null'       => true,
            ],
            'whatsapp_template' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Plain text with {package_name} {order_number} {customer_name} — never wa.me URL',
            ],
            'display_order' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
                'default'    => 100,
            ],
            'is_featured' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
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
        $this->forge->addUniqueKey('slug', 'uq_packages_slug');
        $this->forge->addKey(['is_active', 'display_order'], false, false, 'idx_packages_active_order');
        $this->forge->addKey('is_featured', false, false, 'idx_packages_is_featured');

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('packages', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('packages', true);
    }
}

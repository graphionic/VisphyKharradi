<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreatePackageFeatures
 *
 * Normalized features — never JSON/CSV. Ordered via display_order + is_active flag.
 */
class CreatePackageFeatures extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'package_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'feature_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'null'       => false,
            ],
            'display_order' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 100,
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['package_id', 'display_order'], false, false, 'idx_pf_package_order');
        $this->forge->addKey('package_id', false, false, 'idx_pf_package_id');

        // No FK — relationship is logical/application-managed (see PROJECT_CONSTITUTION).
        // package_id is indexed BIGINT UNSIGNED reference to packages.id.

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('package_features', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('package_features', true);
    }
}

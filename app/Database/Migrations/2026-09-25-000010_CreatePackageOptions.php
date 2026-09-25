<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreatePackageOptions
 *
 * Adds dynamic duration & pricing options for packages.
 * ZERO DATABASE FK CONSTRAINTS — logical references only using indexed BIGINT UNSIGNED fields.
 */
class CreatePackageOptions extends Migration
{
    public function up(): void
    {
        // 1. package_options
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'duration_value' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'duration_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'month',
            ],
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'short_description' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'unsigned'   => true,
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
        $this->forge->addKey('package_id', false, false, 'idx_package_options_package_id');
        $this->forge->addKey(['package_id', 'is_active', 'sort_order'], false, false, 'idx_package_options_lookup');

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('package_options', false, $attributes);

        // 2. package_option_features
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'package_option_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'feature_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'null'       => false,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
                'default'    => 0,
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
        $this->forge->addKey('package_option_id', false, false, 'idx_option_features_option_id');

        $this->forge->createTable('package_option_features', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('package_option_features', true);
        $this->forge->dropTable('package_options', true);
    }
}

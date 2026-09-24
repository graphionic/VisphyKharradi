<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateSettings
 *
 * Key-value store — never for secrets (secrets stay in .env).
 */
class CreateSettings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'setting_type' => [
                'type'       => 'ENUM',
                'constraint' => ['string', 'text', 'url', 'number', 'boolean', 'json'],
                'null'       => false,
                'default'    => 'string',
            ],
            'is_system' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
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
        $this->forge->addUniqueKey('setting_key', 'uq_settings_key');

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('settings', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('settings', true);
    }
}

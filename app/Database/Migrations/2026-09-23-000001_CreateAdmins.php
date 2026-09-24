<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateAdmins
 *
 * Phase 2 — Admins table (single-role V1)
 * No deleted_at — deactivation via is_active preserves audit logs (SET NULL).
 */
class CreateAdmins extends Migration
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
                'constraint' => 100,
                'null'       => false,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => false,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
                'comment'    => 'password_hash() bcrypt/argon2 - never plaintext',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 1,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addUniqueKey('email', 'uq_admins_email');
        $this->forge->addKey('is_active', false, false, 'idx_admins_is_active');

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('admins', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('admins', true);
    }
}

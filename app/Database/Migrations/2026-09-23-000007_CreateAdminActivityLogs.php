<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateAdminActivityLogs
 *
 * Append-only audit trail. admin_id SET NULL so history survives admin deletion.
 */
class CreateAdminActivityLogs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'admin_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'entity_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Sanitized JSON as TEXT — never secrets',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('admin_id', false, false, 'idx_logs_admin_id');
        $this->forge->addKey('action', false, false, 'idx_logs_action');
        $this->forge->addKey(['entity_type', 'entity_id'], false, false, 'idx_logs_entity');
        $this->forge->addKey('created_at', false, false, 'idx_logs_created_at');

        // No FK — admin_id is indexed BIGINT UNSIGNED logical reference to admins.id.
        // Prefer is_active=false over hard delete; audit history remains.

        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('admin_activity_logs', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('admin_activity_logs', true);
    }
}

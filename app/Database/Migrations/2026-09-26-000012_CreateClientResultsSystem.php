<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * CreateClientResultsSystem — Phase 07A: Client Results / Proof System Foundation
 *
 * Creates normalized tables for Client Results, Metrics, Media, and Reports.
 * MANDATORY SECURITY RULE: ZERO FOREIGN KEYS. All relationships are application-level logical references.
 */
class CreateClientResultsSystem extends Migration
{
    public function up(): void
    {
        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];

        // 1. Table: client_results
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'package_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'comment'  => 'Logical reference to packages.id (NO FK)',
            ],
            'program_name_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
                'comment'    => 'Historical program display name snapshot',
            ],
            'client_display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'client_subtitle' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
            ],
            'short_testimonial' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'full_story' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'journey_duration' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'cover_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'display_order' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 100,
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
        $this->forge->addKey('package_id', false, false, 'idx_client_results_package_id');
        $this->forge->addKey(['is_active', 'display_order'], false, false, 'idx_client_results_active_order');
        $this->forge->addKey('is_featured', false, false, 'idx_client_results_is_featured');
        $this->forge->createTable('client_results', false, $attributes);

        // 2. Table: client_result_metrics
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'client_result_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => 'Logical reference to client_results.id (NO FK)',
            ],
            'metric_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'before_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'comment'    => 'Flexible baseline value string (numeric, formatted, or ratio)',
            ],
            'after_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'comment'    => 'Flexible post-program value string',
            ],
            'unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'context' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'measurement_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'measurement_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'display_order' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 100,
            ],
            'is_public' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'comment'    => 'Privacy control: 0=private, 1=publicly visible in drawer',
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
        $this->forge->addKey('client_result_id', false, false, 'idx_crm_client_result_id');
        $this->forge->addKey(['client_result_id', 'is_public', 'display_order'], false, false, 'idx_crm_result_public_order');
        $this->forge->createTable('client_result_metrics', false, $attributes);

        // 3. Table: client_result_media
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'client_result_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => 'Logical reference to client_results.id (NO FK)',
            ],
            'media_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'comment'    => 'Whitelisted: before, after, progress, gallery',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'thumbnail_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'caption' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'null'       => true,
                'default'    => null,
            ],
            'media_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'display_order' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 100,
            ],
            'is_public' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'comment'    => 'Privacy control: 0=private, 1=publicly visible in drawer',
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
        $this->forge->addKey('client_result_id', false, false, 'idx_crmedia_client_result_id');
        $this->forge->addKey(['client_result_id', 'media_type'], false, false, 'idx_crmedia_type');
        $this->forge->addKey(['client_result_id', 'is_public', 'display_order'], false, false, 'idx_crmedia_public_order');
        $this->forge->createTable('client_result_media', false, $attributes);

        // 4. Table: client_result_reports
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'client_result_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => 'Logical reference to client_results.id (NO FK)',
            ],
            'report_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'report_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'report_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'file_mime' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'file_size' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
            ],
            'display_order' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 100,
            ],
            'is_public' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'comment'    => 'MANDATORY PRIVACY DEFAULT: 0=private, 1=publicly visible in drawer',
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
        $this->forge->addKey('client_result_id', false, false, 'idx_crreports_client_result_id');
        $this->forge->addKey(['client_result_id', 'is_public', 'display_order'], false, false, 'idx_crreports_public_order');
        $this->forge->createTable('client_result_reports', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('client_result_reports', true);
        $this->forge->dropTable('client_result_media', true);
        $this->forge->dropTable('client_result_metrics', true);
        $this->forge->dropTable('client_results', true);
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * AddPackageMedia — Phase 5D
 * Adds featured_image to packages and creates package_gallery_images (logical FK only)
 */
class AddPackageMedia extends Migration
{
    public function up(): void
    {
        // packages.featured_image
        $this->forge->addColumn('packages', [
            'featured_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'whatsapp_template',
            ],
        ]);

        // package_gallery_images
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
            'image_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'original_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'alt_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'null'       => true,
            ],
            'display_order' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 0,
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
        $this->forge->addKey('package_id', false, false, 'idx_pkg_gallery_package_id');
        $this->forge->addKey(['package_id', 'display_order'], false, false, 'idx_pkg_gallery_order');
        // No FK — logical only
        $attributes = ['ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci'];
        $this->forge->createTable('package_gallery_images', false, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('package_gallery_images', true);
        $this->forge->dropColumn('packages', 'featured_image');
    }
}

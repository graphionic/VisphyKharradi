<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * AdminSeeder — Secure initial admin
 *
 * Reads credentials from environment:
 *   ADMIN_SEED_NAME
 *   ADMIN_SEED_EMAIL
 *   ADMIN_SEED_PASSWORD
 *
 * Fails safely if any required value is missing — never uses hardcoded
 * defaults like admin@example.com / password123.
 * Password is hashed with password_hash().
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $name     = env('ADMIN_SEED_NAME');
        $email    = env('ADMIN_SEED_EMAIL');
        $password = env('ADMIN_SEED_PASSWORD');

        if (empty($name) || empty($email) || empty($password)) {
            // Fail safe — do not seed with defaults
            echo "AdminSeeder: Missing ADMIN_SEED_NAME / ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD in .env — aborting.\n";
            echo "Set them and re-run: php spark db:seed AdminSeeder\n";
            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "AdminSeeder: ADMIN_SEED_EMAIL '{$email}' is not a valid email — aborting.\n";
            return;
        }

        if (strlen($password) < 10) {
            echo "AdminSeeder: ADMIN_SEED_PASSWORD must be at least 10 characters — aborting.\n";
            return;
        }

        // Check if admin already exists
        $existing = $this->db->table('admins')->where('email', $email)->get()->getRowArray();
        if ($existing) {
            echo "AdminSeeder: Admin with email '{$email}' already exists — skipping.\n";
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            echo "AdminSeeder: password_hash() failed — aborting.\n";
            return;
        }

        $this->db->table('admins')->insert([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $hash,
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        echo "AdminSeeder: Admin '{$email}' created successfully.\n";
    }
}

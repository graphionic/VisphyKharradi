<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * AdminSeederTest — Seeder Verification
 *
 * Verifies:
 * - AdminSeeder creates new admin from environment variables
 * - AdminSeeder synchronizes existing admin when re-run
 * - AdminSeeder fails safely on missing env or short password
 * - Plaintext password is never persisted or logged
 *
 * @internal
 */
final class AdminSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->envBackup = [
            'name'  => getenv('ADMIN_SEED_NAME') ?: ($_ENV['ADMIN_SEED_NAME'] ?? ''),
            'email' => getenv('ADMIN_SEED_EMAIL') ?: ($_ENV['ADMIN_SEED_EMAIL'] ?? ''),
            'pass'  => getenv('ADMIN_SEED_PASSWORD') ?: ($_ENV['ADMIN_SEED_PASSWORD'] ?? ''),
        ];
    }

    protected function tearDown(): void
    {
        $name  = $this->envBackup['name'] ?? '';
        $email = $this->envBackup['email'] ?? '';
        $pass  = $this->envBackup['pass'] ?? '';

        putenv("ADMIN_SEED_NAME={$name}");
        $_ENV['ADMIN_SEED_NAME'] = $name;
        $_SERVER['ADMIN_SEED_NAME'] = $name;

        putenv("ADMIN_SEED_EMAIL={$email}");
        $_ENV['ADMIN_SEED_EMAIL'] = $email;
        $_SERVER['ADMIN_SEED_EMAIL'] = $email;

        putenv("ADMIN_SEED_PASSWORD={$pass}");
        $_ENV['ADMIN_SEED_PASSWORD'] = $pass;
        $_SERVER['ADMIN_SEED_PASSWORD'] = $pass;

        parent::tearDown();
    }

    private function runSeederWithEnv(?string $name, ?string $email, ?string $password): string
    {
        $nameVal  = $name ?? '';
        $emailVal = $email ?? '';
        $passVal  = $password ?? '';

        putenv("ADMIN_SEED_NAME={$nameVal}");
        $_ENV['ADMIN_SEED_NAME'] = $nameVal;
        $_SERVER['ADMIN_SEED_NAME'] = $nameVal;

        putenv("ADMIN_SEED_EMAIL={$emailVal}");
        $_ENV['ADMIN_SEED_EMAIL'] = $emailVal;
        $_SERVER['ADMIN_SEED_EMAIL'] = $emailVal;

        putenv("ADMIN_SEED_PASSWORD={$passVal}");
        $_ENV['ADMIN_SEED_PASSWORD'] = $passVal;
        $_SERVER['ADMIN_SEED_PASSWORD'] = $passVal;

        ob_start();
        $seeder = Database::seeder();
        $seeder->call('App\Database\Seeds\AdminSeeder');
        return (string) ob_get_clean();
    }

    public function testSeederCreatesAdminFromEnv(): void
    {
        $email = 'seed-create-' . uniqid() . '@example.com';
        $pass  = 'SecureSeedPass123!';
        $name  = 'Seed Created Admin';

        $output = $this->runSeederWithEnv($name, $email, $pass);

        $this->assertStringContainsString('created successfully', $output);

        $model = new \App\Models\AdminModel();
        $admin = $model->where('email', $email)->first();

        $this->assertNotNull($admin, 'Admin record should exist in database');
        $this->assertSame($name, $admin['name']);
        $this->assertSame($email, $admin['email']);
        $this->assertSame(1, (int) $admin['is_active']);
        $this->assertTrue(password_verify($pass, $admin['password_hash']));
        $this->assertStringNotContainsString($pass, $admin['password_hash']);
    }

    public function testSeederSynchronizesExistingAdmin(): void
    {
        $email   = 'seed-sync-' . uniqid() . '@example.com';
        $pass1   = 'InitialSeedPass123!';
        $name1   = 'Initial Name';

        $this->runSeederWithEnv($name1, $email, $pass1);

        $pass2   = 'UpdatedSeedPass456!';
        $name2   = 'Updated Name';

        $output2 = $this->runSeederWithEnv($name2, $email, $pass2);

        $this->assertStringContainsString('synchronized successfully', $output2);

        $model  = new \App\Models\AdminModel();
        $admins = $model->where('email', $email)->findAll();

        $this->assertCount(1, $admins, 'Should be exactly 1 admin with this email (no duplicates)');
        $admin = $admins[0];
        $this->assertSame($name2, $admin['name']);
        $this->assertTrue(password_verify($pass2, $admin['password_hash']));
        $this->assertFalse(password_verify($pass1, $admin['password_hash']));
    }

    public function testSeederAbortsOnMissingEnv(): void
    {
        $output = $this->runSeederWithEnv('', '', '');

        $this->assertStringContainsString('Missing ADMIN_SEED_NAME', $output);
        $this->assertStringContainsString('aborting', $output);
    }

    public function testSeederAbortsOnShortPassword(): void
    {
        $output = $this->runSeederWithEnv('Short Pass Admin', 'shortpass@example.com', '12345');

        $this->assertStringContainsString('must be at least 8 characters', $output);
        $this->assertStringContainsString('aborting', $output);

        $model = new \App\Models\AdminModel();
        $admin = $model->where('email', 'shortpass@example.com')->first();
        $this->assertNull($admin, 'Admin should not be created with short password');
    }
}

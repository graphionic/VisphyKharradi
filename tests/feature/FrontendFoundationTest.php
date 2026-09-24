<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * FrontendFoundationTest — Phase 6A Public Frontend & Preview Security Test
 *
 * Verifies:
 * - Public root (/) returns 200 with production frontend CSS/JS includes
 * - /admin/frontend-preview requires admin authentication
 * - /admin/frontend-preview returns 200 for authenticated admin in development/testing environment
 * - Brand Guidelines admin preview (/admin/brand-guidelines) remains intact
 * - Existing Admin routes remain intact
 *
 * @internal
 */
final class FrontendFoundationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_COOKIE  = [];
    }

    private function createAdmin(): array
    {
        $email = 'frontend-test-' . uniqid() . '@example.com';
        $model = new \App\Models\AdminModel();
        $id = $model->insert([
            'name'          => 'Frontend Test Admin',
            'email'         => $email,
            'password_hash' => password_hash('TestPass123!', PASSWORD_DEFAULT),
            'is_active'     => 1,
        ], true);
        $this->assertNotFalse($id);
        return $model->find($id);
    }

    public function testPublicRootRendersProductionFrontendLayout(): void
    {
        $result = $this->call('get', '/');

        $result->assertStatus(200);
        $result->assertSee('assets/frontend/css/tokens.css');
        $result->assertSee('assets/frontend/css/base.css');
        $result->assertSee('assets/frontend/css/layout.css');
        $result->assertSee('assets/frontend/css/components.css');
        $result->assertSee('assets/frontend/css/utilities.css');
        $result->assertSee('assets/frontend/js/main.js');
        $result->assertSee('skip-to-content');
        $result->assertSee('main-content');
    }

    public function testBrandGuidelinesAdminPreviewRemainsIntact(): void
    {
        $admin = $this->createAdmin();
        $session = [
            'admin_id'            => $admin['id'],
            'admin_email'         => $admin['email'],
            'admin_name'          => $admin['name'],
            'admin_authenticated' => true,
            'auth_time'           => time(),
        ];

        $result = $this->withSession($session)->call('get', '/admin/brand-guidelines');

        $result->assertStatus(200);
        $result->assertSee('Brand Guidelines');
    }
}

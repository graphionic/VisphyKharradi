<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AdminDesignSystemTest — Phase 4
 * Verifies premium shell, design tokens, layout, preview restrictions, and escaping.
 *
 * @internal
 */
final class AdminDesignSystemTest extends CIUnitTestCase
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
        try { cache()->clean(); $th = service('throttler'); foreach (['127.0.0.1','0.0.0.0','::1'] as $ip) $th->remove('admin_login_ip_'.hash('sha256', $ip)); } catch (\Throwable $e) {}
        $_SESSION = [];
        $_COOKIE = [];
        $this->session = [];
        try { service('superglobals')->setGlobalArray('cookie', []); $sec = service('security'); $sec->generateHash(); $_COOKIE[$sec->getCookieName()] = $sec->getHash(); service('superglobals')->setGlobalArray('cookie', $_COOKIE); } catch (\Throwable $e) {}
    }

    protected function tearDown(): void
    {
        try { cache()->clean(); } catch (\Throwable $e) {}
        parent::tearDown();
    }

    private function createAdmin(string $email, string $pass, string $name = 'Design Admin'): array
    {
        $m = new \App\Models\AdminModel();
        $id = $m->insert(['name' => $name, 'email' => strtolower(trim($email)), 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'is_active' => 1], true);
        $this->assertNotFalse($id);
        return $m->find($id);
    }

    private function csrf(): array
    {
        $sec = service('security'); $hash = $sec->getHash() ?? $sec->generateHash(); $tn = $sec->getTokenName(); $cn = $sec->getCookieName(); $_COOKIE[$cn] = $hash; try { service('superglobals')->setGlobalArray('cookie', $_COOKIE); } catch (\Throwable $e) {}
        return [$tn => $hash];
    }

    private function login(string $email, string $pass): array
    {
        $csrf = $this->csrf();
        $res = $this->call('post', '/admin/login', array_merge(['email' => $email, 'password' => $pass], $csrf));
        return [$res, $_SESSION];
    }

    public function testAuthenticatedLayoutRendersSidebarAndTopbar(): void
    {
        $email = 'design-layout-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $this->createAdmin($email, $pass);
        [$res, $sess] = $this->login($email, $pass);
        $res->assertRedirect();
        $session = $_SESSION;
        $res2 = $this->withSession($session)->call('get', '/admin');
        $res2->assertStatus(200);
        $body = (string) $res2->response()->getBody();
        // Shell structure
        $this->assertStringContainsString('admin-shell', $body);
        $this->assertStringContainsString('admin-sidebar', $body);
        $this->assertStringContainsString('admin-topbar', $body);
        $this->assertStringContainsString('admin-main', $body);
        // Sidebar groups
        $this->assertStringContainsString('Overview', $body);
        $this->assertStringContainsString('Management', $body);
        $this->assertStringContainsString('System', $body);
        $this->assertStringContainsString('Account', $body);
        // Topbar account dropdown exists
        $this->assertStringContainsString('data-dropdown', $body);
        // Page header
        $this->assertStringContainsString('page-header', $body);
        // Assets linked via tokens
        $this->assertStringContainsString('tokens.css', $body);
        $this->assertStringContainsString('components.css', $body);
        // Premium feel: no emoji in body
        $this->assertStringNotContainsString('📦', $body);
        $this->assertStringNotContainsString('🔄', $body);
    }

    public function testLoginPageUsesAuthLayout(): void
    {
        $res = $this->call('get', '/admin/login');
        $res->assertStatus(200);
        $body = (string) $res->response()->getBody();
        $this->assertStringContainsString('auth-layout', $body);
        $this->assertStringContainsString('auth-card', $body);
        $this->assertStringContainsString('Admin Sign In', $body);
        // No inline event handlers
        $this->assertStringNotContainsString('onclick=', $body);
        $this->assertStringNotContainsString('onchange=', $body);
    }

    public function testProfilePageUsesShell(): void
    {
        $email = 'design-profile-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $this->createAdmin($email, $pass);
        [$res, $sess] = $this->login($email, $pass);
        $session = $_SESSION;
        $res2 = $this->withSession($session)->call('get', '/admin/profile');
        $res2->assertStatus(200);
        $body = (string) $res2->response()->getBody();
        $this->assertStringContainsString('admin-shell', $body);
        $this->assertStringContainsString('Profile', $body);
        $this->assertStringContainsString('Change Password', $body);
        // Password toggle present
        $this->assertStringContainsString('data-password-toggle', $body);
    }

    public function testUiPreviewRequiresAuth(): void
    {
        // Unauthenticated -> redirect to login
        $res = $this->call('get', '/admin/ui-preview');
        $res->assertRedirect();
        $this->assertStringContainsString('admin/login', $res->getRedirectUrl());
    }

    public function testUiPreviewAccessibleWhenAuthenticated(): void
    {
        // In testing env (not production), authenticated should get 200 and see components
        $email = 'design-preview-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $this->createAdmin($email, $pass);
        [$res, $sess] = $this->login($email, $pass);
        $session = $_SESSION;
        $res2 = $this->withSession($session)->call('get', '/admin/ui-preview');
        // Should be 200 in testing (dev)
        $res2->assertStatus(200);
        $body = (string) $res2->response()->getBody();
        $this->assertStringContainsString('UI Preview', $body);
        $this->assertStringContainsString('UI DEMO', $body);
        // Components demonstrated
        $this->assertStringContainsString('Buttons', $body);
        $this->assertStringContainsString('Form Controls', $body);
        $this->assertStringContainsString('Badges', $body);
        $this->assertStringContainsString('Alerts', $body);
        $this->assertStringContainsString('Table', $body);
        $this->assertStringContainsString('Pagination', $body);
        $this->assertStringContainsString('Empty State', $body);
        $this->assertStringContainsString('Modal', $body);
        $this->assertStringContainsString('Dropdown Menu', $body);
        // Should not have leaked real secrets, but demo data is clearly marked
        $this->assertStringContainsString('Demo Package', $body);
    }

    public function testUiPreviewBlockedInProduction(): void
    {
        // Verify controller contains production guard
        $path = APPPATH . 'Controllers/Admin/UiPreviewController.php';
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString("ENVIRONMENT === 'production'", $content);
        $this->assertStringContainsString('PageNotFoundException', $content);
        // Also verify route exists
        $routes = file_get_contents(APPPATH . 'Config/Routes.php');
        $this->assertStringContainsString('admin/ui-preview', $routes);
    }

    public function testAdminNameEscaped(): void
    {
        $email = 'design-escape-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $xssName = '<script>alert("xss")</script>';
        $this->createAdmin($email, $pass, $xssName);
        [$res, $sess] = $this->login($email, $pass);
        $session = $_SESSION;
        $res2 = $this->withSession($session)->call('get', '/admin');
        $res2->assertStatus(200);
        $body = (string) $res2->response()->getBody();
        // Raw script should not appear unescaped
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $body);
        // Escaped should appear
        $this->assertStringContainsString('&lt;script&gt;', $body);
        // Also check profile page
        $res3 = $this->withSession($session)->call('get', '/admin/profile');
        $body3 = (string) $res3->response()->getBody();
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $body3);
    }

    public function testCssAssetsExist(): void
    {
        $base = FCPATH . 'assets/admin/css/';
        // FCPATH is public/, so adjust
        $publicCss = __DIR__ . '/../../public/assets/admin/css/';
        // Try both
        $candidates = [
            $publicCss . 'tokens.css',
            $base . 'tokens.css',
            ROOTPATH . 'public/assets/admin/css/tokens.css',
        ];
        $found = false;
        foreach ($candidates as $p) {
            if (is_file($p)) { $found = true; break; }
        }
        $this->assertTrue($found, 'tokens.css should exist on filesystem');
        // Check all expected files via ROOTPATH
        $expected = ['tokens.css','base.css','layout.css','components.css','utilities.css'];
        foreach ($expected as $f) {
            $this->assertFileExists(ROOTPATH . 'public/assets/admin/css/' . $f);
        }
        $jsExpected = ['admin.js','navigation.js','dropdown.js','modal.js'];
        foreach ($jsExpected as $f) {
            $this->assertFileExists(ROOTPATH . 'public/assets/admin/js/' . $f);
        }
    }

    public function testNoInlineHandlersInAdminViews(): void
    {
        $views = [
            APPPATH . 'Views/admin/layouts/app.php',
            APPPATH . 'Views/admin/layouts/auth.php',
            APPPATH . 'Views/admin/partials/sidebar.php',
            APPPATH . 'Views/admin/partials/topbar.php',
            APPPATH . 'Views/admin/dashboard/index.php',
            APPPATH . 'Views/admin/profile/index.php',
        ];
        foreach ($views as $v) {
            $this->assertFileExists($v);
            $content = file_get_contents($v);
            $this->assertStringNotContainsString('onclick=', $content, "View {$v} should not contain inline onclick");
            $this->assertStringNotContainsString('onchange=', $content, "View {$v} should not contain inline onchange");
        }
    }

    public function testZeroForeignKeysStill(): void
    {
        $db = \Config\Database::connect('tests');
        $cnt = $db->query("SELECT COUNT(*) as c FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE='FOREIGN KEY'")->getRowArray();
        $this->assertEquals(0, (int)($cnt['c'] ?? -1));
    }
}

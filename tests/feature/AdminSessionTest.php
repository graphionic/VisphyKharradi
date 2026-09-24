<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AdminSessionTest — Phase 3 Session Security
 *
 * Verifies session creation, regeneration, active validation, logout clearing, no secrets.
 *
 * @internal
 */
final class AdminSessionTest extends CIUnitTestCase
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
        try {
            cache()->clean();
            $throttler = service('throttler');
            foreach (['127.0.0.1','0.0.0.0','::1'] as $ip) $throttler->remove('admin_login_ip_'.hash('sha256', $ip));
        } catch (\Throwable $e) {}
        $_SESSION = [];
        $_COOKIE = [];
        try {
            service('superglobals')->setGlobalArray('cookie', []);
            $sec = service('security');
            $sec->generateHash();
            $_COOKIE[$sec->getCookieName()] = $sec->getHash();
            service('superglobals')->setGlobalArray('cookie', $_COOKIE);
        } catch (\Throwable $e) {}
    }

    protected function tearDown(): void { try { cache()->clean(); } catch (\Throwable $e) {} parent::tearDown(); }

    private function createAdmin(string $email, string $password, bool $active=true): array
    {
        $m = new \App\Models\AdminModel();
        $id = $m->insert([
            'name' => 'Sess Admin',
            'email' => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active'=> $active?1:0,
        ], true);
        $this->assertNotFalse($id);
        return $m->find($id);
    }

    private function csrf(): array
    {
        $sec = service('security');
        $hash = $sec->getHash() ?? $sec->generateHash();
        $tn = $sec->getTokenName();
        $cn = $sec->getCookieName();
        $_COOKIE[$cn] = $hash;
        try { service('superglobals')->setGlobalArray('cookie', $_COOKIE); } catch (\Throwable $e) {}
        return [$tn => $hash];
    }

    private function login(string $email, string $pass): array
    {
        $csrf = $this->csrf();
        $res = $this->call('post', '/admin/login', array_merge(['email'=>$email,'password'=>$pass], $csrf));
        return [$res, $_SESSION];
    }

    public function testSessionCreatedOnlyAfterValidLogin(): void
    {
        $email = 'sess1-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $this->createAdmin($email,$pass,true);

        // Before login, no auth
        $this->assertArrayNotHasKey('admin_id', $_SESSION);

        // Wrong password — should not create session
        $csrf = $this->csrf();
        $this->call('post','/admin/login', array_merge(['email'=>$email,'password'=>'Wrong123!'], $csrf));
        $this->assertArrayNotHasKey('admin_id', $_SESSION);

        // Clear for next
        $_SESSION = [];
        try { service('superglobals')->setGlobalArray('cookie', [service('security')->getCookieName() => service('security')->getHash()]); } catch (\Throwable $e){}

        // Correct login — should create
        [$res, $sess] = $this->login($email,$pass);
        $res->assertRedirect();
        $this->assertArrayHasKey('admin_id', $_SESSION);
        $this->assertSame(true, $_SESSION['admin_authenticated'] ?? null);
        $this->assertArrayHasKey('auth_time', $_SESSION);
    }

    public function testSessionRegenerationOnLogin(): void
    {
        $email = 'sess-regen-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $this->createAdmin($email,$pass,true);

        // Ensure session has a marker before login
        $_SESSION = ['pre_login_marker' => 'abc'];
        // Need to set via withSession? Simpler to just test that after login, __ci_last_regenerate exists
        // Clean first
        $_SESSION = [];
        try { service('superglobals')->setGlobalArray('cookie', [service('security')->getCookieName()=>service('security')->getHash()]); } catch(\Throwable $e){}

        [$res, $sess] = $this->login($email,$pass);
        $res->assertRedirect();
        // CodeIgniter sets __ci_last_regenerate on regenerate
        $this->assertArrayHasKey('__ci_last_regenerate', $_SESSION, 'Session should have regeneration marker after login');
        $this->assertIsInt($_SESSION['__ci_last_regenerate']);
    }

    public function testInactiveAdminInvalidatesProtectedAccess(): void
    {
        $email = 'sess-inactive-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $admin = $this->createAdmin($email,$pass,true);

        [$res, $sess] = $this->login($email,$pass);
        $res->assertRedirect();
        $session = $_SESSION;
        $this->assertArrayHasKey('admin_id', $session);

        // Deactivate admin
        $m = new \App\Models\AdminModel();
        $m->update($admin['id'], ['is_active'=>0]);

        // Try to access protected route with stale session
        $res2 = $this->withSession($session)->call('get','/admin');
        $res2->assertRedirect();
        $this->assertStringContainsString('admin/login', $res2->getRedirectUrl());
        // Session should have been cleared
        $this->assertArrayNotHasKey('admin_id', $_SESSION);
    }

    public function testDeletedAdminInvalidates(): void
    {
        $email = 'sess-deleted-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $admin = $this->createAdmin($email,$pass,true);
        [$res, $sess] = $this->login($email,$pass);
        $session = $_SESSION;
        // Hard delete admin (though app prefers deactivation, test the invalidation path)
        $m = new \App\Models\AdminModel();
        $m->delete($admin['id'], true);

        $res2 = $this->withSession($session)->call('get','/admin');
        $res2->assertRedirect();
        $this->assertStringContainsString('admin/login', $res2->getRedirectUrl());
    }

    public function testLogoutRemovesAuthState(): void
    {
        $email = 'sess-logout-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $this->createAdmin($email,$pass,true);
        [$res, $sess] = $this->login($email,$pass);
        $session = $_SESSION;
        $csrf = $this->csrf();
        $res2 = $this->withSession($session)->call('post','/admin/logout', $csrf);
        $res2->assertRedirect();
        $this->assertArrayNotHasKey('admin_id', $_SESSION);
        $this->assertArrayNotHasKey('admin_authenticated', $_SESSION);
        $this->assertArrayNotHasKey('admin_name', $_SESSION);
    }

    public function testPasswordHashNeverInSession(): void
    {
        $email = 'sess-hash-'.uniqid().'@example.com';
        $pass = 'SecurePass123!';
        $admin = $this->createAdmin($email,$pass,true);
        [$res, $sess] = $this->login($email,$pass);
        $this->assertArrayNotHasKey('password_hash', $_SESSION);
        $this->assertArrayNotHasKey('password', $_SESSION);
        // Ensure no hash in session values
        foreach ($_SESSION as $k=>$v) {
            if (is_string($v)) {
                $this->assertStringNotContainsString('$2y$', $v, "Session key {$k} should not contain hash");
            }
        }
        // Also ensure admin data in session is minimal
        $this->assertArrayHasKey('admin_id', $_SESSION);
        $this->assertArrayHasKey('admin_authenticated', $_SESSION);
        $this->assertArrayHasKey('auth_time', $_SESSION);
        $this->assertArrayNotHasKey('admin', $_SESSION); // not whole record
    }
}

<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use CodeIgniter\Security\Exceptions\SecurityException;

/**
 * AdminAuthTest — Phase 3 Authentication
 *
 * Covers:
 * - successful login
 * - wrong password generic error
 * - unknown email generic error
 * - inactive admin rejected
 * - protected route unauthenticated redirect
 * - protected route authenticated 200
 * - login page authenticated redirects
 * - logout clears session
 *
 * @internal
 */
final class AdminAuthTest extends CIUnitTestCase
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
        // Clean throttler cache between tests — file cache + throttler buckets
        try {
            cache()->clean();
        } catch (\Throwable $e) {
        }
        try {
            $throttler = service('throttler');
            // Common IPs seen in CLI testing
            foreach (['127.0.0.1', '0.0.0.0', '::1', 'unknown'] as $ip) {
                $throttler->remove('admin_login_ip_'.hash('sha256', $ip));
            }
            // Also try to clear via cache directly for throttler keys
            // File cache keys are prefixed with throttler_
            $cache = service('cache');
            // Attempt to delete any throttler keys left
            // (file handler stores as files; clean should have removed, but be explicit)
        } catch (\Throwable $e) {
        }
        // Ensure clean session and cookies
        $_SESSION = [];
        $_COOKIE = [];
        try {
            service('superglobals')->setGlobalArray('cookie', []);
            service('superglobals')->setGlobalArray('post', []);
            service('superglobals')->setGlobalArray('get', []);
        } catch (\Throwable $e) {
        }
        // Reset security singleton hash to avoid cross-test stale hash (force regeneration)
        try {
            $security = service('security');
            // Generate new hash to reset state
            $security->generateHash();
            // Sync cookie to new hash for next request
            $hash = $security->getHash();
            $cookieName = $security->getCookieName();
            $_COOKIE[$cookieName] = $hash;
            service('superglobals')->setGlobalArray('cookie', $_COOKIE);
        } catch (\Throwable $e) {
        }
    }

    protected function tearDown(): void
    {
        try {
            cache()->clean();
        } catch (\Throwable $e) {
        }
        try {
            $throttler = service('throttler');
            foreach (['127.0.0.1', '0.0.0.0', '::1', 'unknown'] as $ip) {
                $throttler->remove('admin_login_ip_'.hash('sha256', $ip));
            }
        } catch (\Throwable $e) {
        }
        parent::tearDown();
    }

    private function createAdmin(string $email, string $password, bool $isActive = true): array
    {
        $normalized = strtolower(trim($email));
        $model = new \App\Models\AdminModel();
        $id = $model->insert([
            'name'          => 'Test Admin ' . substr(md5($email), 0, 6),
            'email'         => $normalized,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active'     => $isActive ? 1 : 0,
        ], true);
        $this->assertNotFalse($id, 'createAdmin failed: ' . json_encode($model->errors()));
        $admin = $model->find($id);
        $this->assertNotNull($admin);
        return $admin;
    }

    /**
     * Generate valid CSRF token for feature tests (cookie mode).
     * Uses current Security hash to ensure match with singleton.
     * Sets cookie so next request's Security (shared) matches token.
     */
    private function csrf(): array
    {
        $security = service('security');
        // Ensure security has a hash (generate if null)
        $hash = $security->getHash();
        if ($hash === null) {
            $hash = $security->generateHash();
        }
        $tokenName = $security->getTokenName();
        $cookieName = $security->getCookieName();

        // Sync cookie to current hash — make IncomingRequest see it
        $_COOKIE[$cookieName] = $hash;
        try {
            service('superglobals')->setGlobalArray('cookie', $_COOKIE);
        } catch (\Throwable $e) {
        }
        // Also set via superglobals cookie helper if available
        try {
            service('superglobals')->setCookie($cookieName, $hash);
        } catch (\Throwable $e) {
        }

        return [$tokenName => $hash];
    }

    /**
     * Helper to perform login POST with valid CSRF and return TestResponse + session.
     */
    private function doLogin(string $email, string $password): object
    {
        $csrf = $this->csrf();
        $data = array_merge([
            'email'    => $email,
            'password' => $password,
        ], $csrf);

        return $this->call('post', '/admin/login', $data);
    }

    public function testSuccessfulLogin(): void
    {
        $email = 'success-' . uniqid() . '@example.com';
        $pass  = 'SecurePass123!';
        $this->createAdmin($email, $pass, true);

        $result = $this->doLogin($email, $pass);

        // Should redirect to /admin
        $result->assertRedirect();
        $this->assertStringContainsString('/admin', $result->getRedirectUrl());

        // Session should contain auth state
        $this->assertArrayHasKey('admin_id', $_SESSION, 'admin_id should be in session after successful login');
        $this->assertSame(true, $_SESSION['admin_authenticated'] ?? null);
        $this->assertNotEmpty($_SESSION['auth_time'] ?? null);

        // Verify admin can access protected route using persisted session
        $session = $_SESSION;
        $result2 = $this->withSession($session)->call('get', '/admin');
        $result2->assertStatus(200);
        $result2->assertSee('Authenticated successfully');
        $result2->assertSee($email);
    }

    public function testWrongPasswordRejectedGeneric(): void
    {
        $email = 'wrongpass-' . uniqid() . '@example.com';
        $pass  = 'CorrectPass123!';
        $this->createAdmin($email, $pass, true);

        $result = $this->doLogin($email, 'WrongPass123!');

        $result->assertStatus(200); // stays on login page
        $result->assertSee('Invalid email or password');
        // No authenticated session
        $this->assertArrayNotHasKey('admin_id', $_SESSION);
        $this->assertArrayNotHasKey('admin_authenticated', $_SESSION);
    }

    public function testUnknownEmailRejectedGeneric(): void
    {
        $result = $this->doLogin('unknown-' . uniqid() . '@example.com', 'AnyPass123!');

        $result->assertStatus(200);
        $result->assertSee('Invalid email or password');
        $this->assertArrayNotHasKey('admin_id', $_SESSION);

        // Also verify wrong password and unknown email produce same external message (no enumeration)
        // We already check same string in both cases
    }

    public function testInactiveAdminRejected(): void
    {
        $email = 'inactive-' . uniqid() . '@example.com';
        $pass  = 'SecurePass123!';
        $this->createAdmin($email, $pass, false);

        $result = $this->doLogin($email, $pass);

        $result->assertStatus(200);
        $result->assertSee('Invalid email or password');
        $this->assertArrayNotHasKey('admin_id', $_SESSION);
    }

    public function testProtectedRouteUnauthenticatedRedirects(): void
    {
        $result = $this->call('get', '/admin');
        $result->assertRedirect();
        $this->assertStringContainsString('admin/login', $result->getRedirectUrl());
    }

    public function testProtectedRouteAuthenticatedReturns200(): void
    {
        $email = 'protected-' . uniqid() . '@example.com';
        $pass  = 'SecurePass123!';
        $this->createAdmin($email, $pass, true);

        $login = $this->doLogin($email, $pass);
        $login->assertRedirect();
        $session = $_SESSION;

        $result = $this->withSession($session)->call('get', '/admin');
        $result->assertStatus(200);
        $result->assertSee('Authenticated successfully');
        // Cache-Control no-store for admin — check contains no-store (header order may vary)
        $result->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
        $result->assertHeader('Pragma', 'no-cache');
    }

    public function testLoginPageAuthenticatedRedirectsToAdmin(): void
    {
        $email = 'guest-' . uniqid() . '@example.com';
        $pass  = 'SecurePass123!';
        $this->createAdmin($email, $pass, true);

        $login = $this->doLogin($email, $pass);
        $session = $_SESSION;

        $result = $this->withSession($session)->call('get', '/admin/login');
        $result->assertRedirect();
        $this->assertStringContainsString('/admin', $result->getRedirectUrl());
        // Should not be login page
        $this->assertStringNotContainsString('login', strtolower($result->getRedirectUrl() ?? ''));
    }

    public function testLogoutClearsSession(): void
    {
        $email = 'logout-' . uniqid() . '@example.com';
        $pass  = 'SecurePass123!';
        $this->createAdmin($email, $pass, true);

        $login = $this->doLogin($email, $pass);
        $login->assertRedirect();
        $session = $_SESSION;
        $this->assertArrayHasKey('admin_id', $session);

        // Need valid CSRF for logout POST — generate from current security hash (after login regenerate)
        $csrf = $this->csrf();

        $result = $this->withSession($session)->call('post', '/admin/logout', $csrf);
        $result->assertRedirect();
        $this->assertStringContainsString('admin/login', $result->getRedirectUrl());

        // Session should be cleared
        $this->assertArrayNotHasKey('admin_id', $_SESSION);
        $this->assertArrayNotHasKey('admin_authenticated', $_SESSION);

        // Protected route should now redirect (explicit empty session to avoid trait persistence)
        $result2 = $this->withSession([])->call('get', '/admin');
        $result2->assertRedirect();
        $this->assertStringContainsString('admin/login', $result2->getRedirectUrl());
    }

    public function testEmailNormalization(): void
    {
        $email = '  ADMIN-NORMALIZE-' . uniqid() . '@Example.COM  ';
        $normalized = strtolower(trim($email));
        $pass = 'SecurePass123!';
        $this->createAdmin($normalized, $pass, true);

        // Try login with whitespace and uppercase — should succeed due to normalization
        $result = $this->doLogin($email, $pass);
        $result->assertRedirect();
        $this->assertArrayHasKey('admin_id', $_SESSION);
    }
}

<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Security\Exceptions\SecurityException;

/**
 * AdminCsrfTest — Phase 3 CSRF
 *
 * Verifies CSRF protection on admin POST routes.
 *
 * @internal
 */
final class AdminCsrfTest extends CIUnitTestCase
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
            foreach (['127.0.0.1','0.0.0.0','::1','unknown'] as $ip) {
                $throttler->remove('admin_login_ip_'.hash('sha256', $ip));
            }
        } catch (\Throwable $e) {}
        $_SESSION = [];
        $_COOKIE = [];
        try {
            service('superglobals')->setGlobalArray('cookie', []);
            $security = service('security');
            $security->generateHash();
            $_COOKIE[$security->getCookieName()] = $security->getHash();
            service('superglobals')->setGlobalArray('cookie', $_COOKIE);
        } catch (\Throwable $e) {}
    }

    protected function tearDown(): void
    {
        try { cache()->clean(); } catch (\Throwable $e) {}
        parent::tearDown();
    }

    private function createAdmin(string $email, string $password): array
    {
        $model = new \App\Models\AdminModel();
        $id = $model->insert([
            'name' => 'CSRF Admin',
            'email' => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => 1,
        ], true);
        $this->assertNotFalse($id);
        return $model->find($id);
    }

    private function validCsrf(): array
    {
        $security = service('security');
        $hash = $security->getHash();
        if ($hash === null) $hash = $security->generateHash();
        $tokenName = $security->getTokenName();
        $cookieName = $security->getCookieName();
        $_COOKIE[$cookieName] = $hash;
        try { service('superglobals')->setGlobalArray('cookie', $_COOKIE); } catch (\Throwable $e) {}
        return [$tokenName => $hash];
    }

    public function testLoginWithoutCsrfRejected(): void
    {
        $this->expectException(SecurityException::class);
        $this->call('post', '/admin/login', [
            'email' => 'test@example.com',
            'password' => 'whatever12345',
            // no csrf
        ]);
    }

    public function testLoginWithInvalidCsrfRejected(): void
    {
        $this->expectException(SecurityException::class);
        $security = service('security');
        $tokenName = $security->getTokenName();
        // Provide garbage token, but also need cookie set to real hash so mismatch occurs
        $realHash = $security->getHash() ?? $security->generateHash();
        $cookieName = $security->getCookieName();
        $_COOKIE[$cookieName] = $realHash;
        try { service('superglobals')->setGlobalArray('cookie', $_COOKIE); } catch (\Throwable $e) {}
        $this->call('post', '/admin/login', [
            'email' => 'test@example.com',
            'password' => 'whatever12345',
            $tokenName => 'invalid_csrf_token_1234567890abcdef',
        ]);
    }

    public function testLogoutWithoutCsrfRejected(): void
    {
        // Need authenticated session first
        $email = 'csrf-logout-' . uniqid() . '@example.com';
        $pass = 'SecurePass123!';
        $admin = $this->createAdmin($email, $pass);
        // Login with valid csrf
        $csrf = $this->validCsrf();
        $login = $this->call('post', '/admin/login', array_merge([
            'email' => $email,
            'password' => $pass,
        ], $csrf));
        // Should have succeeded (redirect)
        $session = $_SESSION;
        $this->assertArrayHasKey('admin_id', $session);

        // Now attempt logout without csrf — should throw
        $this->expectException(SecurityException::class);
        $this->withSession($session)->call('post', '/admin/logout', [
            // missing csrf
        ]);
    }

    public function testPasswordChangeWithoutCsrfRejected(): void
    {
        $email = 'csrf-pass-' . uniqid() . '@example.com';
        $pass = 'SecurePass123!';
        $admin = $this->createAdmin($email, $pass);
        $csrf = $this->validCsrf();
        $login = $this->call('post', '/admin/login', array_merge([
            'email' => $email,
            'password' => $pass,
        ], $csrf));
        $session = $_SESSION;
        $this->assertArrayHasKey('admin_id', $session);

        $this->expectException(SecurityException::class);
        $this->withSession($session)->call('post', '/admin/profile/password', [
            'current_password' => $pass,
            'new_password' => 'NewSecurePass123!',
            'confirm_password' => 'NewSecurePass123!',
            // missing csrf
        ]);
    }

    public function testValidCsrfAllowsLogin(): void
    {
        $email = 'csrf-valid-' . uniqid() . '@example.com';
        $pass = 'SecurePass123!';
        $this->createAdmin($email, $pass);
        $csrf = $this->validCsrf();
        $result = $this->call('post', '/admin/login', array_merge([
            'email' => $email,
            'password' => $pass,
        ], $csrf));
        $result->assertRedirect();
        $this->assertStringContainsString('/admin', $result->getRedirectUrl());
    }
}

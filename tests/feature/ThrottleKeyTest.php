<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * ThrottleKeyTest — Phase 3 Security Bugfix Regression
 *
 * Verifies that throttler cache keys are safe for CI4 FileHandler.
 * CI4 reserved chars: {}()/\@:  — IPv6 ":" would previously throw InvalidArgumentException.
 * Fix: SHA-256 hex digest (0-9a-f) for both IP and email, centralized via throttleKey().
 *
 * @internal
 */
final class ThrottleKeyTest extends CIUnitTestCase
{
    private function throttleKey(string $type, string $identity): string
    {
        // Mirror AuthController::throttleKey — keep in sync
        if (! in_array($type, ['ip', 'email'], true)) {
            throw new \InvalidArgumentException('Invalid throttle type');
        }
        return 'admin_login_' . $type . '_' . hash('sha256', $identity);
    }

    private function assertSafeKey(string $key): void
    {
        // CI4 BaseHandler reserved chars: {}()/\@:
        $this->assertDoesNotMatchRegularExpression('/[\{\}\(\)\/\\\\@:]/', $key, "Key contains reserved chars: $key");
        $this->assertMatchesRegularExpression('/^admin_login_(ip|email)_[0-9a-f]{64}$/', $key);
    }

    public function testIpv4KeyIsSafeAndDeterministic(): void
    {
        $ip = '192.168.1.10';
        $k1 = $this->throttleKey('ip', $ip);
        $k2 = $this->throttleKey('ip', $ip);
        $this->assertSame($k1, $k2, 'Same IP must give same key');
        $this->assertSafeKey($k1);
        $this->assertStringNotContainsString($ip, $k1, 'Raw IP must not appear');
        $this->assertStringNotContainsString(':', $k1);
        // Throttler must accept it — no InvalidArgumentException
        $throttler = service('throttler');
        $this->assertTrue($throttler->check($k1, 5, 900));
        $throttler->remove($k1);
    }

    public function testIpv6KeyIsSafe(): void
    {
        foreach (['2001:db8::1', '::1', '2001:0db8:85a3:0000:0000:8a2e:0370:7334'] as $ip) {
            $key = $this->throttleKey('ip', $ip);
            $this->assertSafeKey($key);
            $this->assertStringNotContainsString($ip, $key);
            $this->assertStringNotContainsString(':', $key);
            $this->assertStringNotContainsString('{', $key);
            $throttler = service('throttler');
            // Must not throw
            try {
                $ok = $throttler->check($key, 5, 900);
                $this->assertIsBool($ok);
            } catch (\Throwable $e) {
                $this->fail("Throttler threw for IPv6 $ip with key $key: " . $e->getMessage());
            }
            $throttler->remove($key);
        }
    }

    public function testEmailNormalizationAndKey(): void
    {
        $e1 = 'Admin@Ftpreneur.Local';
        $e2 = 'admin@ftpreneur.local';
        $n1 = \App\Services\AdminAuthService::normalizeEmail($e1);
        $n2 = \App\Services\AdminAuthService::normalizeEmail($e2);
        $this->assertSame($n1, $n2);
        $k1 = $this->throttleKey('email', $n1);
        $k2 = $this->throttleKey('email', $n2);
        $this->assertSame($k1, $k2);
        $this->assertSafeKey($k1);
        $this->assertStringNotContainsString('@', $k1);
        $this->assertStringNotContainsString('Admin', $k1);
        $throttler = service('throttler');
        $this->assertTrue($throttler->check($k1, 5, 900));
        $throttler->remove($k1);
    }

    public function testRawIpWouldBeInvalidButHashedIsValid(): void
    {
        $ip = '2001:db8::1';
        $rawKey = 'admin_login_ip_' . $ip;
        // Raw key must contain reserved char ":"
        $this->assertMatchesRegularExpression('/:/', $rawKey);
        // Direct use with throttler should throw InvalidArgumentException (CI4 FileHandler)
        $throttler = service('throttler');
        $threw = false;
        try {
            $throttler->check($rawKey, 5, 900);
        } catch (\CodeIgniter\Exceptions\InvalidArgumentException $e) {
            $threw = true;
            $this->assertStringContainsString('reserved characters', $e->getMessage());
        } catch (\Throwable $e) {
            // Some environments may wrap differently, but should contain message
            $threw = str_contains($e->getMessage(), 'reserved');
        }
        $this->assertTrue($threw, 'Raw IPv6 key should throw');
        // Hashed must not throw
        $safe = $this->throttleKey('ip', $ip);
        $throttler->check($safe, 5, 900); // no throw
        $throttler->remove($safe);
        // Also clean raw if it was created (file may not exist)
        try { cache()->delete($rawKey); } catch (\Throwable $e) {}
    }

    public function testLongUnusualIdentifierIsBounded(): void
    {
        $long = str_repeat('A', 500) . '::ffff:192.168.1.1' . str_repeat('B', 500);
        $key = $this->throttleKey('ip', $long);
        $this->assertSafeKey($key);
        // Fixed length: admin_login_ip_ (15) + 64 =79, admin_login_email_ (18)+64=82
        $this->assertSame(79, strlen($key));
        $this->assertSame(79, strlen('admin_login_ip_'.hash('sha256','x')));
        $this->assertSame(82, strlen('admin_login_email_'.hash('sha256','x')));
        $this->assertLessThan(100, strlen($key));
        $throttler = service('throttler');
        $throttler->check($key, 5, 900);
        $throttler->remove($key);
    }

    public function testControllerThrottleKeyViaReflection(): void
    {
        $controller = new \App\Controllers\Admin\AuthController();
        $ref = new \ReflectionMethod($controller, 'throttleKey');
        $ref->setAccessible(true);
        $k = $ref->invoke($controller, 'ip', '2001:db8::1');
        $this->assertSafeKey($k);
        $this->assertSame($this->throttleKey('ip', '2001:db8::1'), $k);

        $k2 = $ref->invoke($controller, 'email', 'admin@ftpreneur.local');
        $this->assertSafeKey($k2);

        // Invalid type should throw
        $this->expectException(\InvalidArgumentException::class);
        $ref->invoke($controller, 'evil', 'foo');
    }

    public function testAllReservedCharsCovered(): void
    {
        // Verify that typical reserved chars never appear in hashed keys
        $samples = ['127.0.0.1','192.168.1.10','::1','2001:db8::1','10.0.0.1','::ffff:127.0.0.1','admin@ftpreneur.local','Test@Example.COM'];
        foreach ($samples as $id) {
            $type = str_contains($id,'@') ? 'email' : 'ip';
            // normalize email if needed
            if ($type==='email') $id = \App\Services\AdminAuthService::normalizeEmail($id);
            $key = $this->throttleKey($type, $id);
            $this->assertSafeKey($key);
            foreach (['{','}','(',')','/','\\','@',':'] as $c) {
                $this->assertStringNotContainsString($c, $key, "Key for $id contains reserved $c");
            }
        }
    }
}

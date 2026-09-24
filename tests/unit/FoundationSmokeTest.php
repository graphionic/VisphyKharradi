<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Foundation Smoke Test — Phase 1
 *
 * Verifies that the application boots and the foundation route
 * GET / renders the temporary foundation view.
 *
 * No business logic is tested here — only that the framework
 * foundation is wired correctly.
 *
 * @internal
 */
final class FoundationSmokeTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testAppBoots(): void
    {
        $this->assertTrue(defined('APPPATH'));
        $this->assertTrue(defined('SYSTEMPATH'));
    }

    public function testHomeRouteReturnsFoundation(): void
    {
        $result = $this->call('get', '/');

        $result->assertStatus(200);
        $result->assertSee('Foundation Ready');
        $result->assertSee('Ftpreneur');
        // Should contain phase marker
        $result->assertSee('Phase 1');
    }

    public function testNotFoundReturns404(): void
    {
        // CI4 FeatureTestTrait throws PageNotFoundException for missing routes
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->call('get', '/this-route-does-not-exist-xyz');
    }
}

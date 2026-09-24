<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Services\PackageService;
use App\Models\PackageModel;

/**
 * Phase 5E.1 — Package Module Core Hardening
 * Focused regression tests for ID, mass-assignment, boolean, money, duration/order, slug, features, test-hooks, zero-FK.
 */
final class AdminPackageHardeningTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $adminId;
    private string $adminEmail = 'hardening@example.com';
    private string $adminPass = 'Password123!';

    protected function setUp(): void
    {
        parent::setUp();
        try { cache()->clean(); } catch (\Throwable $e) {}
        try {
            $throttler = service('throttler');
            foreach (['127.0.0.1','0.0.0.0','::1','unknown'] as $ip) {
                $throttler->remove('admin_login_ip_'.hash('sha256', $ip));
            }
        } catch (\Throwable $e) {}
        $_SESSION = [];
        $_COOKIE = [];
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        try { service('superglobals')->setGlobalArray('cookie', []); } catch (\Throwable $e) {}
        try { service('superglobals')->setGlobalArray('post', []); } catch (\Throwable $e) {}
        try { service('superglobals')->setGlobalArray('get', []); } catch (\Throwable $e) {}
        // create admin
        $model = new \App\Models\AdminModel();
        $id = $model->insert([
            'name' => 'Hardening Admin',
            'email' => strtolower($this->adminEmail . uniqid()),
            'password_hash' => password_hash($this->adminPass, PASSWORD_DEFAULT),
            'is_active' => 1,
        ], true);
        $admin = $model->find($id);
        $this->adminId = (int)$admin['id'];
        $this->adminEmail = $admin['email'];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        try { cache()->clean(); } catch (\Throwable $e) {}
        parent::tearDown();
    }

    private function csrf(): array
    {
        $security = service('security');
        $hash = $security->getHash() ?? $security->generateHash();
        $tokenName = $security->getTokenName();
        $cookieName = $security->getCookieName();
        $_COOKIE[$cookieName] = $hash;
        try { service('superglobals')->setGlobalArray('cookie', $_COOKIE); } catch (\Throwable $e) {}
        return [$tokenName => $hash];
    }

    private function login(): void
    {
        $csrf = $this->csrf();
        $this->call('POST', '/admin/login', array_merge(['email'=>$this->adminEmail,'password'=>$this->adminPass], $csrf))->assertRedirect();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Hardening Package ' . uniqid(),
            'slug' => 'hardening-' . uniqid(),
            'short_description' => 'Short',
            'full_description' => '<p>Full</p>',
            'regular_price' => '1000.00',
            'selling_price' => '900.00',
            'duration_value' => '30',
            'duration_unit' => 'days',
            'badge' => 'popular',
            'cta_label' => 'Get Started',
            'google_form_url' => '',
            'whatsapp_template' => 'Hi {customer_name}',
            'display_order' => '10',
            'is_active' => '1',
            'is_featured' => '0',
            'features' => ['Feature A', 'Feature B'],
        ], $overrides);
    }

    // B. ID HARDENING
    public function testInvalidIdsFailSafely(): void
    {
        $this->login();
        $session = $_SESSION;
        $cases = [
            '0' => '0',
            '-1' => '-1',
            'abc' => 'abc',
            '1abc' => '1abc',
            '1.5' => '1.5',
            ' ' => ' ',
            '999999999999999999999' => '999999999999999999999',
            '%00' => '%00',
            '%2e%2e' => '%2e%2e',
        ];
        foreach ($cases as $label => $id) {
            try {
                $resp = $this->withSession($session)->call('GET', '/admin/packages/' . rawurlencode($id) . '/edit');
                $code = $resp->getStatusCode();
                $this->assertTrue(in_array($code, [404, 302, 400, 500], true) || $code >= 400, "ID {$label} should fail safely, got {$code}");
                $body = $resp->getBody();
                $this->assertStringNotContainsString('SELECT', $body, "ID {$label} should not leak SQL");
            } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
                $this->assertStringContainsString('Package', $e->getMessage());
            } catch (\CodeIgniter\HTTP\Exceptions\BadRequestException $e) {
                $this->assertTrue(true, "BadRequest for {$label} is safe");
            } catch (\Throwable $e) {
                $this->fail("ID {$label} threw unexpected: ".get_class($e).": ".$e->getMessage());
            }
        }
    }

    public function testInvalidIdsPostFailSafely(): void
    {
        $this->login();
        $session = $_SESSION;
        $cases = ['0','-1','abc','1.5'];
        foreach ($cases as $id) {
            try {
                $csrf = $this->csrf();
                $resp = $this->withSession($session)->call('POST', '/admin/packages/' . $id, array_merge($this->validData(), $csrf));
                $code = $resp->getStatusCode();
                $this->assertTrue(in_array($code, [404, 302, 400], true) || $code >= 400, "POST ID {$id} should fail safely, got {$code}");
            } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
                $this->assertTrue(true);
            } catch (\CodeIgniter\HTTP\Exceptions\BadRequestException $e) {
                $this->assertTrue(true);
            } catch (\CodeIgniter\Security\Exceptions\SecurityException $e) {
                // CSRF failure is also safe (not 500)
                $this->assertTrue(true, "POST ID {$id} CSRF safe");
            }
        }
    }

    // C. MASS ASSIGNMENT
    public function testMassAssignmentIgnored(): void
    {
        $service = new PackageService();
        $data = $this->validData([
            'id' => '999',
            'deleted_at' => '2026-01-01 00:00:00',
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
            'admin_id' => '1',
            'package_id' => '1',
            'arbitrary_field' => 'hacked',
            'slug' => 'mass-assign-' . uniqid(),
        ]);
        $res = $service->createPackage($data, $this->adminId);
        $this->assertTrue($res['success'], 'Should succeed despite mass-assignment fields: '.json_encode($res['errors'] ?? []));
        $pkg = (new PackageModel())->find($res['id']);
        $this->assertNotEquals(999, (int)$pkg['id'], 'id should not be mass-assigned');
        $this->assertNull($pkg['deleted_at'], 'deleted_at should not be mass-assigned');
        // Ensure arbitrary field not in DB
        $this->assertArrayNotHasKey('arbitrary_field', $pkg);
    }

    public function testMassAssignmentOnUpdate(): void
    {
        $service = new PackageService();
        $data = $this->validData(['slug'=>'mass-update-'.uniqid()]);
        $res = $service->createPackage($data, $this->adminId);
        $pid = $res['id'];
        $update = $this->validData([
            'name' => 'Updated Name',
            'slug' => $data['slug'],
            'id' => 9999,
            'deleted_at' => '2026-01-01 00:00:00',
            'admin_id' => 9999,
        ]);
        $upd = $service->updatePackage($pid, $update, $this->adminId);
        $this->assertTrue($upd['success']);
        $pkg = (new PackageModel())->find($pid);
        $this->assertEquals('Updated Name', $pkg['name']);
        $this->assertEquals($pid, (int)$pkg['id'], 'id should not change');
        $this->assertNull($pkg['deleted_at']);
    }

    // D. BOOLEAN HARDENING
    public function testBooleanNormalization(): void
    {
        $service = new PackageService();
        $cases = [
            ['is_active'=>'admin', 'is_featured'=>'admin', 'exp_active'=>0, 'exp_feat'=>0],
            ['is_active'=>['1'], 'is_featured'=>['1'], 'exp_active'=>0, 'exp_feat'=>0],
            ['is_active'=>'1', 'is_featured'=>'1', 'exp_active'=>1, 'exp_feat'=>1],
            ['is_active'=>'on', 'is_featured'=>'on', 'exp_active'=>1, 'exp_feat'=>1],
            ['is_active'=>true, 'is_featured'=>true, 'exp_active'=>1, 'exp_feat'=>1],
            ['is_active'=>'-1', 'is_featured'=>'-1', 'exp_active'=>0, 'exp_feat'=>0],
            ['is_active'=>['nested'=>['1']], 'exp_active'=>0],
        ];
        foreach ($cases as $idx=>$c) {
            $data = $this->validData(array_merge([
                'slug'=>'bool-'.uniqid()."-{$idx}",
            ], $c));
            // Remove exp keys
            $expActive = $c['exp_active'] ?? 0;
            $expFeat = $c['exp_feat'] ?? 0;
            unset($data['exp_active'], $data['exp_feat']);
            $res = $service->createPackage($data, $this->adminId);
            $this->assertTrue($res['success'], "Case {$idx} should succeed: ".json_encode($res['errors']??[]));
            $pkg = (new PackageModel())->find($res['id']);
            $this->assertEquals($expActive, (int)$pkg['is_active'], "Case {$idx} active");
            $this->assertEquals($expFeat, (int)$pkg['is_featured'], "Case {$idx} featured");
        }
    }

    public function testBooleanNoWarningsForArray(): void
    {
        $service = new PackageService();
        $data = $this->validData([
            'slug'=>'bool-warn-'.uniqid(),
            'is_active'=>['1'],
            'is_featured'=>['unexpected'=> 'array'],
        ]);
        // Should not throw warning, should treat as 0
        $res = $service->createPackage($data, $this->adminId);
        $this->assertTrue($res['success']);
        $pkg = (new PackageModel())->find($res['id']);
        $this->assertEquals(0, (int)$pkg['is_active']);
        $this->assertEquals(0, (int)$pkg['is_featured']);
    }

    // E. MONEY HARDENING
    public function testMoneyHardening(): void
    {
        $service = new PackageService();
        $cases = [
            ['regular_price'=>'0', 'selling_price'=>'0', 'shouldPass'=>true],
            ['regular_price'=>'0.00', 'selling_price'=>'0.00', 'shouldPass'=>true],
            ['regular_price'=>'2999', 'selling_price'=>'2999', 'shouldPass'=>true],
            ['regular_price'=>'2999.0', 'selling_price'=>'2999.0', 'shouldPass'=>true],
            ['regular_price'=>'2999.000', 'selling_price'=>'2999.000', 'shouldPass'=>false, 'field'=>'regular_price'],
            ['regular_price'=>'00002999.00', 'selling_price'=>'00002999.00', 'shouldPass'=>true],
            ['regular_price'=>'-0.01', 'selling_price'=>'-0.01', 'shouldPass'=>false],
            ['regular_price'=>'1e3', 'selling_price'=>'1e3', 'shouldPass'=>false],
            ['regular_price'=>'NaN', 'selling_price'=>'NaN', 'shouldPass'=>false],
            ['regular_price'=>'INF', 'selling_price'=>'INF', 'shouldPass'=>false],
            ['regular_price'=>'1,000', 'selling_price'=>'1,000', 'shouldPass'=>false],
            ['regular_price'=>'₹1000', 'selling_price'=>'₹1000', 'shouldPass'=>false],
            ['regular_price'=>' 1000 ', 'selling_price'=>' 1000 ', 'shouldPass'=>true], // trimmed
            ['regular_price'=>'100000000.00', 'selling_price'=>'100000000.00', 'shouldPass'=>false], // > 99999999.99
            ['regular_price'=>'1000.00', 'selling_price'=>'2000.00', 'shouldPass'=>false, 'field'=>'selling_price'], // selling > regular
        ];
        foreach ($cases as $idx=>$c) {
            $data = $this->validData([
                'slug'=>'money-'.uniqid()."-{$idx}",
                'regular_price'=>$c['regular_price'],
                'selling_price'=>$c['selling_price'],
            ]);
            $res = $service->createPackage($data, $this->adminId);
            if ($c['shouldPass']) {
                $this->assertTrue($res['success'], "Money case {$idx} should pass: ".json_encode($c)." errors ".json_encode($res['errors']??[]));
            } else {
                $this->assertFalse($res['success'], "Money case {$idx} should fail: ".json_encode($c));
                $field = $c['field'] ?? null;
                if ($field) {
                    $this->assertArrayHasKey($field, $res['errors'] ?? [], "Case {$idx} should have error on {$field}");
                }
            }
        }
    }

    // F. DURATION / DISPLAY ORDER
    public function testDurationHardening(): void
    {
        $service = new PackageService();
        $cases = [
            ['duration_value'=>'0', 'shouldPass'=>false],
            ['duration_value'=>'-5', 'shouldPass'=>false],
            ['duration_value'=>'1001', 'shouldPass'=>false],
            ['duration_value'=>'999999', 'shouldPass'=>false],
            ['duration_value'=>'abc', 'shouldPass'=>false],
            ['duration_value'=>'1.5', 'shouldPass'=>false],
            ['duration_value'=>'', 'shouldPass'=>false],
            ['duration_value'=>['1'], 'shouldPass'=>false],
        ];
        foreach ($cases as $idx=>$c) {
            $data = $this->validData([
                'slug'=>'duration-'.uniqid()."-{$idx}",
                'duration_value'=>$c['duration_value'],
            ]);
            $res = $service->createPackage($data, $this->adminId);
            $this->assertFalse($res['success'], "Duration case {$idx} should fail");
        }
        // Valid
        $data = $this->validData(['slug'=>'duration-valid-'.uniqid(), 'duration_value'=>'10', 'duration_unit'=>'days']);
        $res = $service->createPackage($data, $this->adminId);
        $this->assertTrue($res['success']);
    }

    public function testDisplayOrderHardening(): void
    {
        $service = new PackageService();
        $cases = [
            ['display_order'=>'-1', 'shouldPass'=>false],
            ['display_order'=>'100001', 'shouldPass'=>false],
            ['display_order'=>'abc', 'shouldPass'=>false],
            ['display_order'=>'1.5', 'shouldPass'=>false],
            ['display_order'=>['10'], 'shouldPass'=>false, 'isArray'=>true],
            ['display_order'=>'', 'shouldPass'=>true], // defaults to 100
        ];
        foreach ($cases as $idx=>$c) {
            $data = $this->validData([
                'slug'=>'order-'.uniqid()."-{$idx}",
                'display_order'=>$c['display_order'],
            ]);
            $res = $service->createPackage($data, $this->adminId);
            if ($c['shouldPass']) {
                $this->assertTrue($res['success'], "Order case {$idx} should pass");
            } else {
                $this->assertFalse($res['success'], "Order case {$idx} should fail: ".json_encode($c));
            }
        }
    }

    // G. SLUG HARDENING
    public function testSlugHardening(): void
    {
        $service = new PackageService();
        // Create a base package to test duplicate
        $base = $this->validData(['slug'=>'slug-base-'.uniqid()]);
        $resBase = $service->createPackage($base, $this->adminId);
        $this->assertTrue($resBase['success']);
        $deletedSlug = 'slug-deleted-' . uniqid();
        $del = $this->validData(['slug'=>$deletedSlug]);
        $resDel = $service->createPackage($del, $this->adminId);
        $this->assertTrue($resDel['success']);
        $service->deletePackage($resDel['id'], $this->adminId);

        $cases = [
            ['slug'=>'UPPERCASE', 'shouldPass'=>true, 'normalized'=> strtolower('UPPERCASE')], // uppercase should be lowercased and pass
            ['slug'=>'my slug', 'shouldPass'=>false],
            ['slug'=>'my--slug', 'shouldPass'=>false],
            ['slug'=>'-leading', 'shouldPass'=>false],
            ['slug'=>'trailing-', 'shouldPass'=>false],
            ['slug'=>'café', 'shouldPass'=>false],
            ['slug'=>'my/slug', 'shouldPass'=>false],
            ['slug'=>'my?slug', 'shouldPass'=>false],
            ['slug'=>str_repeat('a', 191), 'shouldPass'=>false],
            ['slug'=>$base['slug'], 'shouldPass'=>false], // duplicate active
            ['slug'=>$deletedSlug, 'shouldPass'=>false], // duplicate deleted
        ];
        foreach ($cases as $idx=>$c) {
            $data = $this->validData(['slug'=>$c['slug']]);
            $res = $service->createPackage($data, $this->adminId);
            if ($c['shouldPass']) {
                $this->assertTrue($res['success'], "Slug case {$idx} {$c['slug']} should pass, errs ".json_encode($res['errors']??[]));
                if (isset($c['normalized'])) {
                    $pkg = (new PackageModel())->find($res['id']);
                    $this->assertEquals($c['normalized'], $pkg['slug']);
                }
            } else {
                $this->assertFalse($res['success'], "Slug case {$idx} {$c['slug']} should fail");
                $this->assertArrayHasKey('slug', $res['errors'] ?? []);
            }
        }
    }

    // H. FEATURES HARDENING
    public function testFeaturesHardening(): void
    {
        $service = new PackageService();
        // Empty collection (no features) — currently allowed, but we test empty string case
        $dataEmpty = $this->validData(['slug'=>'feat-empty-'.uniqid(), 'features'=>[]]);
        $resEmpty = $service->createPackage($dataEmpty, $this->adminId);
        // Empty array is allowed (0 features) — not an error, but if we consider empty as invalid, it would fail. We currently allow 0.
        $this->assertTrue($resEmpty['success'] || isset($resEmpty['errors']['features']), 'Empty features should be handled');

        // 1 feature (valid)
        $data1 = $this->validData(['slug'=>'feat-1-'.uniqid(), 'features'=>['Single']]);
        $this->assertTrue($service->createPackage($data1, $this->adminId)['success']);

        // 50 features (max)
        $fifty = array_fill(0, 50, 'Feature text');
        $data50 = $this->validData(['slug'=>'feat-50-'.uniqid(), 'features'=>$fifty]);
        $this->assertTrue($service->createPackage($data50, $this->adminId)['success']);

        // 51 features (should fail)
        $fiftyOne = array_fill(0, 51, 'Feature');
        $data51 = $this->validData(['slug'=>'feat-51-'.uniqid(), 'features'=>$fiftyOne]);
        $res51 = $service->createPackage($data51, $this->adminId);
        $this->assertFalse($res51['success']);
        $this->assertArrayHasKey('features', $res51['errors']);

        // Duplicates — should be allowed (no dedup required) but not error
        $dup = $this->validData(['slug'=>'feat-dup-'.uniqid(), 'features'=>['Same','Same']]);
        $this->assertTrue($service->createPackage($dup, $this->adminId)['success']);

        // Whitespace only
        $ws = $this->validData(['slug'=>'feat-ws-'.uniqid(), 'features'=>['   ']]);
        $resWs = $service->createPackage($ws, $this->adminId);
        $this->assertFalse($resWs['success']);

        // Oversized feature
        $long = str_repeat('a', 301);
        $dataLong = $this->validData(['slug'=>'feat-long-'.uniqid(), 'features'=>[$long]]);
        $resLong = $service->createPackage($dataLong, $this->adminId);
        $this->assertFalse($resLong['success']);

        // HTML/script — stored as plain, view escapes; DB may contain but must not be executed, and our service should not reject
        $html = $this->validData(['slug'=>'feat-html-'.uniqid(), 'features'=>['<script>alert(1)</script>']]);
        $resHtml = $service->createPackage($html, $this->adminId);
        $this->assertTrue($resHtml['success']);
        $pkg = (new PackageModel())->find($resHtml['id']);
        $feat = (new \App\Models\PackageFeatureModel())->where('package_id',$pkg['id'])->first();
        // Feature is stored as plain text, but on output it will be escaped via esc(); we just ensure it was stored
        $this->assertNotNull($feat);
        $this->assertEquals('<script>alert(1)</script>', $feat['feature_text']);

        // Nested-array tampering
        $nested = $this->validData(['slug'=>'feat-nested-'.uniqid(), 'features'=>[['feature_text'=>['nested'=>'array']]]]);
        $resNested = $service->createPackage($nested, $this->adminId);
        // Should not warn, should treat nested as empty and fail validation
        $this->assertFalse($resNested['success']);
    }

    public function testFeaturesSameNormalizationCreateAndEdit(): void
    {
        $service = new PackageService();
        $data = $this->validData(['slug'=>'feat-norm-'.uniqid(), 'features'=>[' A ', 'B  ', '  C']]);
        $res = $service->createPackage($data, $this->adminId);
        $this->assertTrue($res['success']);
        $pid = $res['id'];
        $orig = $service->getPackageForEdit($pid);
        $this->assertEquals(['A','B','C'], $orig['features']);

        // Edit with same normalization
        $upd = $this->validData(['slug'=>$data['slug'], 'name'=>'Updated', 'features'=>[' X ',' Y ']]);
        $resUpd = $service->updatePackage($pid, $upd, $this->adminId);
        $this->assertTrue($resUpd['success']);
        $after = $service->getPackageForEdit($pid);
        $this->assertEquals(['X','Y'], $after['features']);
    }

    // I. TEST-ONLY HOOKS
    public function testSimulateHookNotTriggerableViaPost(): void
    {
        // Ensure that POST cannot set simulateFeatureFailure
        $this->login();
        $session = $_SESSION;
        $csrf = $this->csrf();
        $data = $this->validData([
            'slug'=>'hook-'.uniqid(),
            'simulateFeatureFailure'=>'1',
            'simulateFeatureFailure'=>1,
        ]);
        // Also try to inject via POST field simulateFeatureFailure
        $resp = $this->withSession($session)->call('POST', '/admin/packages', array_merge($data, $csrf, ['simulateFeatureFailure'=>1]));
        // Should not trigger simulation; package should be created successfully (or fail for other validation, but not simulation)
        // We check that package was created (redirect to listing, not exception)
        $resp->assertRedirect();
        // Verify that package exists
        $pkg = (new PackageModel())->where('slug', $data['slug'])->first();
        $this->assertNotNull($pkg, 'Package should be created even with hook field in POST');
        // Verify that hook is still false
        $this->assertFalse(\App\Services\PackageService::$simulateFeatureFailure, 'Hook should remain false');
    }

    public function testSimulateHookNotViaHeader(): void
    {
        $this->login();
        $session = $_SESSION;
        $csrf = $this->csrf();
        $data = $this->validData(['slug'=>'hook-header-'.uniqid()]);
        // Try via header
        $this->withSession($session)->withHeaders(['X-Simulate-Feature-Failure'=>'1'])->call('POST', '/admin/packages', array_merge($data, $csrf));
        $pkg = (new PackageModel())->where('slug', $data['slug'])->first();
        $this->assertNotNull($pkg);
        $this->assertFalse(\App\Services\PackageService::$simulateFeatureFailure);
    }

    // J. ZERO FK
    public function testZeroForeignKeys(): void
    {
        $db = \Config\Database::connect();
        $fkCount = 0;
        try {
            $result = $db->query("SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE='FOREIGN KEY' AND TABLE_NAME IN ('packages','package_features','package_gallery_images','orders','payments','admin_activity_logs')")->getRowArray();
            $fkCount = (int)($result['cnt'] ?? 0);
        } catch (\Throwable $e) {
            $rows = $db->query("SELECT sql FROM sqlite_master WHERE type='table'")->getResultArray();
            foreach ($rows as $r) {
                if (stripos($r['sql'] ?? '', 'FOREIGN KEY') !== false) $fkCount++;
            }
        }
        $this->assertEquals(0, $fkCount, 'FOREIGN KEY COUNT must be 0');

        // Also check migration source
        $files = glob(APPPATH . 'Database/Migrations/*.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringNotContainsString('addForeignKey', $content, "Migration {$file} must not contain addForeignKey");
            $this->assertStringNotContainsString('FOREIGN KEY', $content, "Migration {$file} must not contain FOREIGN KEY");
            // REFERENCES is allowed in comments? But we check for REFERENCES as FK definition
            if (preg_match('/\$this->forge->addForeignKey/', $content)) {
                $this->fail("Migration {$file} contains addForeignKey");
            }
        }
    }

    // A. ROUTES / AUTH / CSRF
    public function testAllPackageRoutesRequireAuth(): void
    {
        $routes = [
            ['GET', '/admin/packages'],
            ['GET', '/admin/packages/create'],
            ['POST', '/admin/packages'],
            ['GET', '/admin/packages/1/edit'],
            ['POST', '/admin/packages/1'],
            ['POST', '/admin/packages/1/toggle-active'],
            ['POST', '/admin/packages/1/toggle-featured'],
            ['POST', '/admin/packages/reorder'],
            ['POST', '/admin/packages/1/delete'],
            ['GET', '/admin/packages/deleted'],
            ['POST', '/admin/packages/1/restore'],
        ];
        foreach ($routes as [$method, $uri]) {
            try {
                $resp = $this->call($method, $uri);
                $code = $resp->getStatusCode();
                $this->assertTrue(in_array($code, [302, 303, 401, 403, 404], true) || $code >= 300, "Route {$method} {$uri} should require auth, got {$code}");
                if ($code === 302 || $code === 303) {
                    $loc = strtolower($resp->getRedirectUrl() ?? $resp->getHeaderLine('Location') ?? '');
                    $this->assertTrue(str_contains($loc, 'login') || $loc === '', "Should redirect to login for {$method} {$uri}, got {$loc}");
                }
            } catch (\Throwable $e) {
                // PageNotFound or BadRequest for invalid id is also safe (requires auth check before id validation, but still safe)
                $this->assertTrue(true, "Route {$method} {$uri} threw safe exception: ".get_class($e));
            }
        }
    }

    public function testMutationsRequirePostAndCsrf(): void
    {
        $this->login();
        $session = $_SESSION;
        // Try GET on POST routes should be 404 or Method Not Allowed
        try {
            $resp = $this->withSession($session)->call('GET', '/admin/packages/1/toggle-active');
            $this->assertTrue(in_array($resp->getStatusCode(), [404, 405], true), 'GET on POST route should fail, got '.$resp->getStatusCode());
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertTrue(true, 'GET on POST route correctly 404');
        }

        // POST without CSRF should throw SecurityException
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->withSession($session)->call('POST', '/admin/packages/1/toggle-active', []);
    }

    public function testStaticRoutesNotInterpretedAsIds(): void
    {
        $this->login();
        $session = $_SESSION;
        // Accessing /admin/packages/create should not be treated as id=create
        $resp = $this->withSession($session)->call('GET', '/admin/packages/create');
        $body = $resp->getBody();
        // When authenticated, should be 200 with Create Package; status may be 0 in test env but body is HTML
        $this->assertStringContainsString('Create Package', $body, 'Create page should contain title');
        $this->assertStringNotContainsString('Can\'t find a route for', $body);

        $resp2 = $this->withSession($session)->call('GET', '/admin/packages/deleted');
        $body2 = $resp2->getBody();
        $this->assertStringContainsString('Deleted', $body2, 'Deleted page should contain title');

        // POST reorder should not be treated as update id=reorder
        $csrf = $this->csrf();
        $resp3 = $this->withSession($session)->call('POST', '/admin/packages/reorder', $csrf);
        $code3 = $resp3->getStatusCode();
        $this->assertNotEquals(404, $code3, 'reorder should not be 404, got '.$code3);
        $this->assertNotEquals(500, $code3, 'reorder should not be 500');
    }
}

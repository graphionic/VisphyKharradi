<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Services\PackageService;

/**
 * AdminPackageEditTest — Phase 5C
 */
final class AdminPackageEditTest extends CIUnitTestCase
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
        try { cache()->clean(); } catch (\Throwable $e) {}
        try {
            $throttler = service('throttler');
            foreach (['127.0.0.1','0.0.0.0','::1','unknown'] as $ip) {
                $throttler->remove('admin_login_ip_'.hash('sha256', $ip));
            }
        } catch (\Throwable $e) {}
        $_SESSION = [];
        $_COOKIE = [];
        try {
            service('superglobals')->setGlobalArray('cookie', []);
            service('superglobals')->setGlobalArray('post', []);
            service('superglobals')->setGlobalArray('get', []);
            $security = service('security');
            $security->generateHash();
            $_COOKIE[$security->getCookieName()] = $security->getHash();
            service('superglobals')->setGlobalArray('cookie', $_COOKIE);
        } catch (\Throwable $e) {}
        // Ensure simulate failure flag reset
        \App\Services\PackageService::$simulateFeatureFailure = false;
    }

    protected function tearDown(): void
    {
        \App\Services\PackageService::$simulateFeatureFailure = false;
        try { cache()->clean(); } catch (\Throwable $e) {}
        try {
            $throttler = service('throttler');
            foreach (['127.0.0.1','0.0.0.0','::1','unknown'] as $ip) {
                $throttler->remove('admin_login_ip_'.hash('sha256', $ip));
            }
        } catch (\Throwable $e) {}
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
        $this->assertNotFalse($id);
        return $model->find($id);
    }

    private function csrf(): array
    {
        $security = service('security');
        $hash = $security->getHash();
        if ($hash === null) $hash = $security->generateHash();
        $tokenName = $security->getTokenName();
        $cookieName = $security->getCookieName();
        $_COOKIE[$cookieName] = $hash;
        try { service('superglobals')->setGlobalArray('cookie', $_COOKIE); } catch (\Throwable $e) {}
        try { service('superglobals')->setCookie($cookieName, $hash); } catch (\Throwable $e) {}
        return [$tokenName => $hash];
    }

    private function doLogin(string $email, string $password): object
    {
        $csrf = $this->csrf();
        return $this->call('post', '/admin/login', array_merge(['email'=>$email,'password'=>$password], $csrf));
    }

    private function loginAndGetSession(string $email = null, string $pass = 'SecurePass123!'): array
    {
        if ($email === null) $email = 'pkgedit-' . uniqid() . '@example.com';
        $this->createAdmin($email, $pass, true);
        $login = $this->doLogin($email, $pass);
        $login->assertRedirect();
        return $_SESSION;
    }

    private function validPackageData(array $overrides = []): array
    {
        $base = [
            'name'              => 'Valid Package ' . uniqid(),
            'slug'              => 'valid-package-' . uniqid(),
            'short_description' => 'Short desc',
            'full_description'  => 'Full desc',
            'regular_price'     => '5000.00',
            'selling_price'     => '2999.00',
            'duration_value'    => '30',
            'duration_unit'     => 'days',
            'cta_label'         => 'Get Started',
            'google_form_url'   => 'https://docs.google.com/forms/d/e/test/viewform',
            'whatsapp_template' => 'Hi {customer_name} package {package_name} order {order_number}',
            'badge'             => 'popular',
            'display_order'     => '10',
            'is_active'         => '1',
            'is_featured'       => '1',
            'features'          => ['Feature One', 'Feature Two', 'Feature Three'],
        ];
        return array_merge($base, $overrides);
    }

    private function createPackageDirect(array $overrides = []): array
    {
        // Create via service or direct model for test setup
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData($overrides), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $model = new \App\Models\PackageModel();
        $pkg = $model->where('slug', $data['slug'])->first();
        $this->assertNotNull($pkg);
        // Return pkg and session for further use
        // Need fresh session after creation (csrf rotated)
        $model2 = new \App\Models\PackageFeatureModel();
        $feats = $model2->where('package_id', $pkg['id'])->orderBy('display_order','ASC')->findAll();
        return [$pkg, $session, $data];
    }

    // Helper to create package via Model directly (skip service validation) for prefill tests with specific values
    private function createPackageViaModel(array $overrides = []): array
    {
        $model = new \App\Models\PackageModel();
        $defaults = [
            'name'              => 'Pkg ' . uniqid(),
            'slug'              => 'pkg-' . uniqid(),
            'short_description' => 'Short',
            'full_description'  => 'Full',
            'regular_price'     => '5000.00',
            'selling_price'     => '2999.00',
            'duration_value'    => 30,
            'duration_unit'     => 'days',
            'badge'             => 'popular',
            'cta_label'         => 'Get Started',
            'google_form_url'   => 'https://docs.google.com/forms/d/e/test/viewform',
            'whatsapp_template' => 'Hi {customer_name}',
            'display_order'     => 10,
            'is_active'         => 1,
            'is_featured'       => 1,
        ];
        $data = array_merge($defaults, $overrides);
        $id = $model->insert($data, true);
        $this->assertNotFalse($id);
        $pkg = $model->find($id);
        // Features
        $featModel = new \App\Models\PackageFeatureModel();
        $features = $overrides['features'] ?? ['Feature One','Feature Two'];
        if (isset($overrides['features'])) {
            // overrides contains features array, insert them
            $seq=1;
            foreach ($features as $ft) {
                $featModel->insert(['package_id'=>$id,'feature_text'=>$ft,'display_order'=>$seq++,'is_active'=>1], true);
            }
        } else {
            // default features
            $featModel->insert(['package_id'=>$id,'feature_text'=>'Feature One','display_order'=>1,'is_active'=>1], true);
            $featModel->insert(['package_id'=>$id,'feature_text'=>'Feature Two','display_order'=>2,'is_active'=>1], true);
        }
        return $pkg;
    }

    // --- 21. TESTS — ACCESS

    public function testUnauthenticatedEditGetRejected(): void
    {
        $pkg = $this->createPackageViaModel();
        $res = $this->call('get', '/admin/packages/' . $pkg['id'] . '/edit');
        $res->assertRedirect();
        $this->assertStringContainsString('admin/login', $res->getRedirectUrl());
    }

    public function testAuthenticatedEditGetWorks(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel(['name'=>'Edit Test Pkg','slug'=>'edit-test-pkg-'.uniqid()]);
        $res = $this->withSession($session)->call('get', '/admin/packages/' . $pkg['id'] . '/edit');
        $res->assertStatus(200);
        $res->assertSee('Edit Package');
        $res->assertSee('Edit Test Pkg');
    }

    public function testUnauthenticatedUpdatePostRejected(): void
    {
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(), $csrf);
        $res = $this->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('admin/login', $res->getRedirectUrl());
    }

    public function testPostWithoutCsrfRejected(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $data = $this->validPackageData();
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
    }

    public function testInactiveAdminRejected(): void
    {
        $email = 'active-then-inactive-' . uniqid() . '@example.com';
        $pass = 'SecurePass123!';
        $this->createAdmin($email, $pass, true);
        $login = $this->doLogin($email, $pass);
        $login->assertRedirect();
        $session = $_SESSION;
        $adminModel = new \App\Models\AdminModel();
        $found = $adminModel->where('email', strtolower($email))->first();
        $adminModel->update($found['id'], ['is_active'=>0]);
        $pkg = $this->createPackageViaModel();
        $res = $this->withSession($session)->call('get', '/admin/packages/' . $pkg['id'] . '/edit');
        $res->assertRedirect();
        $this->assertStringContainsString('admin/login', $res->getRedirectUrl());
    }

    public function testNonexistentIdReturns404(): void
    {
        $session = $this->loginAndGetSession();
        // GET
        try {
            $res = $this->withSession($session)->call('get', '/admin/packages/999999/edit');
            $this->assertEquals(404, $res->response()->getStatusCode());
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertStringContainsString('Package not found', $e->getMessage());
        }
        // POST
        $csrf = $this->csrf();
        try {
            $res2 = $this->withSession($session)->call('post', '/admin/packages/999999', array_merge($this->validPackageData(), $csrf));
            $this->assertEquals(404, $res2->response()->getStatusCode());
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertStringContainsString('Package not found', $e->getMessage());
        }
    }

    public function testSoftDeletedReturns404(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $model = new \App\Models\PackageModel();
        $model->delete($pkg['id']);
        try {
            $res = $this->withSession($session)->call('get', '/admin/packages/' . $pkg['id'] . '/edit');
            $this->assertEquals(404, $res->response()->getStatusCode());
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertStringContainsString('Package not found', $e->getMessage());
        }
        $csrf = $this->csrf();
        try {
            $res2 = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], array_merge($this->validPackageData(), $csrf));
            $this->assertEquals(404, $res2->response()->getStatusCode());
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertStringContainsString('Package not found', $e->getMessage());
        }
    }

    public function testInvalidIdHandling(): void
    {
        $session = $this->loginAndGetSession();
        $cases = ['abc','0','-1','999999999999','notfound123'];
        foreach ($cases as $bad) {
            try {
                $res = $this->withSession($session)->call('get', '/admin/packages/' . $bad . '/edit');
                $code = $res->response()->getStatusCode();
                $this->assertTrue(in_array($code, [404, 400], "Invalid id $bad should be 404/400 got " . $code), "Failed for $bad");
                $body = $res->getBody();
                $this->assertStringNotContainsString('SELECT', $body);
                $this->assertStringNotContainsString('SQL', $body);
            } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
                $this->assertStringContainsString('Package', $e->getMessage());
                $this->assertStringNotContainsString('SELECT', $e->getMessage());
            } catch (\CodeIgniter\HTTP\Exceptions\BadRequestException $e) {
                // Disallowed characters are also safe 400 handling
                $this->assertStringNotContainsString('SELECT', $e->getMessage());
            }
        }
    }

    // --- 22. TESTS — PREFILL

    public function testPrefillContainsCurrentValues(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel([
            'name'=>'Prefill Name <b>',
            'slug'=>'prefill-slug-'.uniqid(),
            'short_description'=>'Short <script>',
            'full_description'=>'Full desc with <b>html</b>',
            'regular_price'=>'8000.00',
            'selling_price'=>'4500.00',
            'duration_value'=>45,
            'duration_unit'=>'weeks',
            'badge'=>'recommended',
            'cta_label'=>'Join Now',
            'google_form_url'=>'https://forms.gle/test123',
            'whatsapp_template'=>'Hi {customer_name} your {package_name}',
            'display_order'=>77,
            'is_active'=>1,
            'is_featured'=>1,
            'features'=>['Alpha Feature','Beta Feature','Gamma Feature']
        ]);
        $res = $this->withSession($session)->call('get', '/admin/packages/' . $pkg['id'] . '/edit');
        $res->assertStatus(200);
        // Check escaped name not raw
        $res->assertDontSee('<b>');
        $res->assertSee('&lt;b&gt;');
        $res->assertSee('Prefill Name');
        $res->assertSee($pkg['slug']);
        $res->assertSee('Short');
        $res->assertSee('Full desc');
        $res->assertSee('8000');
        $res->assertSee('4500');
        $res->assertSee('45');
        $res->assertSee('weeks');
        $body = $res->getBody();
        $this->assertStringContainsString('Alpha Feature', $body);
        $this->assertStringContainsString('Beta Feature', $body);
        $this->assertStringContainsString('Gamma Feature', $body);
        $res->assertSee('recommended');
        $res->assertSee('Join Now');
        $res->assertSee('https://forms.gle/test123');
        $res->assertSee('Hi {customer_name}');
        // Check duration select selected, badge, display_order, active/featured checked
        $body = $res->getBody();
        $this->assertStringContainsString('value="45"', $body);
        $this->assertStringContainsString('value="77"', $body);
        // Features order
        $posAlpha = strpos($body, 'Alpha Feature');
        $posBeta = strpos($body, 'Beta Feature');
        $posGamma = strpos($body, 'Gamma Feature');
        $this->assertTrue($posAlpha < $posBeta && $posBeta < $posGamma, 'Features order not preserved');
        // Ensure output is escaped (user payload not raw)
        $this->assertStringNotContainsString('Short <script>', $body);
        $this->assertStringContainsString('Short &lt;script&gt;', $body);
    }

    // --- 23. TESTS — SUCCESSFUL UPDATE

    public function testSuccessfulUpdateEveryCategory(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel([
            'name'=>'Original Name',
            'slug'=>'original-slug-'.uniqid(),
            'short_description'=>'orig short',
            'full_description'=>'orig full',
            'regular_price'=>'5000.00',
            'selling_price'=>'3000.00',
            'duration_value'=>30,
            'duration_unit'=>'days',
            'badge'=>'popular',
            'cta_label'=>'Get Started',
            'google_form_url'=>'https://docs.google.com/forms/d/e/orig/viewform',
            'whatsapp_template'=>'Hi {customer_name}',
            'display_order'=>10,
            'is_active'=>1,
            'is_featured'=>0,
            'features'=>['One','Two']
        ]);
        $csrf = $this->csrf();
        $newData = $this->validPackageData([
            'name'=>'Updated Name',
            'slug'=>'updated-slug-'.uniqid(),
            'short_description'=>'Updated short',
            'full_description'=>'Updated full',
            'regular_price'=>'9000.00',
            'selling_price'=>'6500.00',
            'duration_value'=>'6',
            'duration_unit'=>'months',
            'cta_label'=>'Enroll Now',
            'google_form_url'=>'https://forms.gle/updated123',
            'whatsapp_template'=>'Hello {customer_name} package {package_name} order {order_number}',
            'badge'=>'best_value',
            'display_order'=>'99',
            'is_active'=>'0',
            'is_featured'=>'1',
            'features'=>['New One','New Two','New Three']
        ]);
        $data = array_merge($newData, $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res->getRedirectUrl());
        $this->assertStringNotContainsString('/edit', $res->getRedirectUrl());

        // Capture session flash
        $session2 = $_SESSION;

        // DB reflects changes
        $model = new \App\Models\PackageModel();
        $updated = $model->find($pkg['id']);
        $this->assertSame('Updated Name', $updated['name']);
        $this->assertSame($newData['slug'], $updated['slug']);
        $this->assertSame('Updated short', $updated['short_description']);
        $this->assertSame('Updated full', $updated['full_description']);
        $this->assertSame('9000.00', $updated['regular_price']);
        $this->assertSame('6500.00', $updated['selling_price']);
        $this->assertSame('6', (string)$updated['duration_value']);
        $this->assertSame('months', $updated['duration_unit']);
        $this->assertSame('best_value', $updated['badge']);
        $this->assertSame('Enroll Now', $updated['cta_label']);
        $this->assertSame('https://forms.gle/updated123', $updated['google_form_url']);
        $this->assertStringContainsString('{customer_name}', $updated['whatsapp_template']);
        $this->assertSame('99', (string)$updated['display_order']);
        $this->assertSame('0', (string)$updated['is_active']);
        $this->assertSame('1', (string)$updated['is_featured']);

        $featModel = new \App\Models\PackageFeatureModel();
        $feats = $featModel->where('package_id', $pkg['id'])->orderBy('display_order','ASC')->findAll();
        $this->assertCount(3, $feats);
        $this->assertSame('New One', $feats[0]['feature_text']);
        $this->assertSame('New Two', $feats[1]['feature_text']);
        $this->assertSame('New Three', $feats[2]['feature_text']);

        // Flash
        $res2 = $this->withSession($session2)->call('get', '/admin/packages');
        $res2->assertSee('Package updated successfully');

        // Audit
        $logModel = new \App\Models\AdminActivityLogModel();
        $log = $logModel->where('action','package.updated')->where('entity_id',$pkg['id'])->orderBy('id','DESC')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('csrf', strtolower(json_encode($log)));
        $meta = json_decode($log['metadata'] ?? '{}', true);
        $this->assertArrayHasKey('changed_fields', $meta ?? []);
        $this->assertContains('name', $meta['changed_fields']);
    }

    // --- 24. TESTS — SLUG

    public function testUnchangedOwnSlugAllowed(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel(['slug'=>'own-slug-'.uniqid()]);
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['name'=>'New Name','slug'=>$pkg['slug']]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $updated = $model->find($pkg['id']);
        $this->assertSame('New Name', $updated['name']);
        $this->assertSame($pkg['slug'], $updated['slug']);
    }

    public function testNewUniqueSlugAllowed(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel(['slug'=>'old-slug-'.uniqid()]);
        $newSlug = 'new-unique-slug-'.uniqid();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['slug'=>$newSlug]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $updated = $model->find($pkg['id']);
        $this->assertSame($newSlug, $updated['slug']);
    }

    public function testAnotherPackagesSlugRejected(): void
    {
        $session = $this->loginAndGetSession();
        $pkgA = $this->createPackageViaModel(['slug'=>'slug-a-'.uniqid()]);
        $pkgB = $this->createPackageViaModel(['slug'=>'slug-b-'.uniqid()]);
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['slug'=>$pkgA['slug']]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkgB['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $stillB = $model->find($pkgB['id']);
        $this->assertSame($pkgB['slug'], $stillB['slug']);
    }

    public function testRaceDuplicateSafeResponse(): void
    {
        $session = $this->loginAndGetSession();
        $pkgA = $this->createPackageViaModel(['slug'=>'race-a-'.uniqid()]);
        $pkgB = $this->createPackageViaModel(['slug'=>'race-b-'.uniqid()]);
        // Simulate race: validation passes but DB unique fails — we test service directly by forcing duplicate via raw insert? 
        // Instead we directly use PackageService to update B to A's slug, which should be caught as validation error, not exception.
        $service = new PackageService();
        $result = $service->updatePackage($pkgB['id'], $this->validPackageData(['slug'=>$pkgA['slug']]), 1);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('slug', $result['errors']);
        $this->assertStringNotContainsString('SQL', json_encode($result));
    }

    // --- 25. TESTS — FEATURES

    public function testFeaturesAddEditRemoveReorder(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel(['features'=>['A','B','C']]);
        $featModel = new \App\Models\PackageFeatureModel();
        $orig = $featModel->where('package_id',$pkg['id'])->orderBy('display_order','ASC')->findAll();
        $this->assertCount(3, $orig);
        $this->assertSame('A', $orig[0]['feature_text']);
        $this->assertSame('B', $orig[1]['feature_text']);
        $this->assertSame('C', $orig[2]['feature_text']);

        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['features'=>['C edited','D','A']]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res->getRedirectUrl());

        $feats = $featModel->where('package_id',$pkg['id'])->orderBy('display_order','ASC')->findAll();
        $this->assertCount(3, $feats);
        $this->assertSame('C edited', $feats[0]['feature_text']);
        $this->assertSame('1', (string)$feats[0]['display_order']);
        $this->assertSame('D', $feats[1]['feature_text']);
        $this->assertSame('2', (string)$feats[1]['display_order']);
        $this->assertSame('A', $feats[2]['feature_text']);
        $this->assertSame('3', (string)$feats[2]['display_order']);
        // B must be gone
        $texts = array_column($feats,'feature_text');
        $this->assertNotContains('B', $texts);
        $this->assertNotContains('C', $texts); // original C replaced
    }

    // --- 26. TEST — ROLLBACK

    public function testRollbackRestoresOriginal(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel([
            'name'=>'Rollback Orig',
            'slug'=>'rollback-orig-'.uniqid(),
            'regular_price'=>'6000.00',
            'selling_price'=>'4000.00',
            'features'=>['Keep1','Keep2','Keep3']
        ]);
        $origPkg = (new \App\Models\PackageModel())->find($pkg['id']);
        $featModel = new \App\Models\PackageFeatureModel();
        $origFeats = $featModel->where('package_id',$pkg['id'])->orderBy('display_order','ASC')->findAll();
        $origTexts = array_column($origFeats,'feature_text');

        // Force failure
        PackageService::$simulateFeatureFailure = true;
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData([
            'name'=>'Rollback Changed',
            'slug'=>'rollback-changed-'.uniqid(),
            'regular_price'=>'7000.00',
            'selling_price'=>'5000.00',
            'features'=>['New1','New2']
        ]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        // Should be back to edit with error (exception), not to list
        $loc = $res->getRedirectUrl();
        // Either edit or list? Our service returns exception error, controller redirects to edit
        $this->assertStringContainsString('/edit', $loc);

        $afterPkg = (new \App\Models\PackageModel())->find($pkg['id']);
        $this->assertSame($origPkg['name'], $afterPkg['name']);
        $this->assertSame($origPkg['slug'], $afterPkg['slug']);
        $this->assertSame($origPkg['regular_price'], $afterPkg['regular_price']);
        $this->assertSame($origPkg['selling_price'], $afterPkg['selling_price']);

        $afterFeats = $featModel->where('package_id',$pkg['id'])->orderBy('display_order','ASC')->findAll();
        $afterTexts = array_column($afterFeats,'feature_text');
        $this->assertSame($origTexts, $afterTexts);

        // No audit for failed update? Actually service returns error, no audit. Check no new audit
        $logModel = new \App\Models\AdminActivityLogModel();
        $logs = $logModel->where('action','package.updated')->where('entity_id',$pkg['id'])->findAll();
        $this->assertCount(0, $logs);

        PackageService::$simulateFeatureFailure = false;
    }

    // --- 27. VALIDATION REGRESSION

    public function testValidationMissingName(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['name'=>'']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $after = $model->find($pkg['id']);
        $this->assertSame($pkg['name'], $after['name']);
    }

    public function testValidationInvalidSlug(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['slug'=>'Invalid Slug!']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationNegativePrice(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['regular_price'=>'-10','selling_price'=>'-5']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationSellingGreaterThanRegular(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['regular_price'=>'1000.00','selling_price'=>'2000.00']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationInvalidDuration(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['duration_value'=>'0']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationInvalidUnit(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['duration_unit'=>'years']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationEmptyFeature(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['features'=>['']]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationTooManyFeatures(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $many = array_fill(0,51,'Feature text');
        $data = array_merge($this->validPackageData(['features'=>$many]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationInvalidBadge(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['badge'=>'<script>']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationInvalidGoogleUrl(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['google_form_url'=>'http://docs.google.com/forms/d/test']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationDeceptiveGoogleHost(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['google_form_url'=>'https://docs.google.com.evil.example/forms']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationUnknownWhatsapp(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['whatsapp_template'=>'Hi {unknown}']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationInvalidDisplayOrder(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['display_order'=>'not-a-number']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
    }

    public function testValidationPreservesSubmittedValues(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel(['name'=>'Orig Name']);
        $csrf = $this->csrf();
        $badName = ''; // missing name triggers validation
        $submitted = $this->validPackageData(['name'=>$badName, 'features'=>['Submitted1','Submitted2'], 'is_featured'=>'1']);
        $data = array_merge($submitted, $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/edit', $res->getRedirectUrl());
        $session2 = $_SESSION;
        // GET edit again, should show submitted features not DB ones (via withInput flash)
        $res2 = $this->withSession($session2)->call('get', '/admin/packages/' . $pkg['id'] . '/edit');
        $body = $res2->getBody();
        // Should contain submitted features, not replaced with DB
        $this->assertStringContainsString('Submitted1', $body);
        $this->assertStringContainsString('Submitted2', $body);
        // DB should still have original
        $model = new \App\Models\PackageModel();
        $after = $model->find($pkg['id']);
        $this->assertSame('Orig Name', $after['name']);
    }

    // --- 28. XSS

    public function testXssEscapedOnEditAndListing(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel(['name'=>'Safe Name']);
        $xssName = '<script>alert(1)</script>';
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['name'=>$xssName, 'slug'=>'xss-edit-'.uniqid(), 'short_description'=>'<img src=x onerror=alert(1)>']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res->getRedirectUrl());
        $session2 = $_SESSION;
        $resEdit = $this->withSession($session2)->call('get', '/admin/packages/' . $pkg['id'] . '/edit');
        $resEdit->assertDontSee('<script>alert(1)</script>');
        $resEdit->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;');
        $resList = $this->withSession($session2)->call('get', '/admin/packages');
        $resList->assertDontSee('<script>alert(1)</script>');
        $resList->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;');
    }

    // --- 29. LISTING INTEGRATION

    public function testListingReflectsUpdate(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel([
            'name'=>'List Orig',
            'slug'=>'list-orig-'.uniqid(),
            'regular_price'=>'5000.00',
            'selling_price'=>'3000.00',
            'duration_value'=>10,
            'duration_unit'=>'days',
            'badge'=>null,
            'is_active'=>1,
            'is_featured'=>0,
            'features'=>['One']
        ]);
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData([
            'name'=>'List Updated',
            'slug'=>'list-updated-'.uniqid(),
            'regular_price'=>'8000.00',
            'selling_price'=>'6000.00',
            'duration_value'=>2,
            'duration_unit'=>'months',
            'badge'=>'popular',
            'is_active'=>'0',
            'is_featured'=>'1',
            'features'=>['A','B','C']
        ]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $session2 = $_SESSION;
        $list = $this->withSession($session2)->call('get', '/admin/packages');
        $list->assertSee('List Updated');
        $list->assertSee('₹6,000');
        $list->assertSee('2 Months');
        $list->assertSee('Popular');
        $list->assertSee('3 features');
        $list->assertSee('Inactive');
        $list->assertSee('Featured');
        // Search still works
        $search = $this->withSession($session2)->call('get', '/admin/packages?q=List Updated');
        $search->assertSee('List Updated');
        // Filter still works
        $filter = $this->withSession($session2)->call('get', '/admin/packages?status=inactive');
        $filter->assertSee('List Updated');
    }

    // --- 30. ZERO-FK

    public function testForeignKeyCountZero(): void
    {
        $db = \Config\Database::connect();
        $result = $db->query("SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_TYPE='FOREIGN KEY' AND TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('packages','package_features')");
        $row = $result->getRowArray();
        $this->assertSame('0', (string)$row['c']);
    }

    // --- Shared form architecture check (light)

    public function testEditUsesSharedFormArchitecture(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        $res = $this->withSession($session)->call('get', '/admin/packages/' . $pkg['id'] . '/edit');
        $res->assertSee('Save Changes');
        $res->assertSee('Cancel');
        $res->assertDontSee('Create Package</button>'); // button label should be Save Changes not Create Package
        // Ensure form action points to /admin/packages/{id}
        $this->assertStringContainsString('/admin/packages/' . $pkg['id'], $res->getBody());
    }

    public function testNoChangeUpdateAllowed(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel([
            'name'=>'NoChange',
            'slug'=>'nochange-'.uniqid(),
            'features'=>['One','Two']
        ]);
        // Fetch current to submit same
        $featModel = new \App\Models\PackageFeatureModel();
        $feats = $featModel->where('package_id',$pkg['id'])->orderBy('display_order','ASC')->findAll();
        $texts = array_column($feats,'feature_text');
        $csrf = $this->csrf();
        // Build data matching current package
        $data = array_merge($this->validPackageData([
            'name'=>$pkg['name'],
            'slug'=>$pkg['slug'],
            'short_description'=>$pkg['short_description'] ?? '',
            'full_description'=>$pkg['full_description'] ?? '',
            'regular_price'=>$pkg['regular_price'],
            'selling_price'=>$pkg['selling_price'],
            'duration_value'=>(string)$pkg['duration_value'],
            'duration_unit'=>$pkg['duration_unit'],
            'badge'=>$pkg['badge'] ?? '',
            'cta_label'=>$pkg['cta_label'],
            'google_form_url'=>$pkg['google_form_url'] ?? '',
            'whatsapp_template'=>$pkg['whatsapp_template'] ?? '',
            'display_order'=>(string)$pkg['display_order'],
            'is_active'=>(string)$pkg['is_active'],
            'is_featured'=>(string)$pkg['is_featured'],
            'features'=>$texts
        ]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res->getRedirectUrl());
        $logModel = new \App\Models\AdminActivityLogModel();
        $log = $logModel->where('action','package.updated')->where('entity_id',$pkg['id'])->orderBy('id','DESC')->first();
        $this->assertNotNull($log);
        $meta = json_decode($log['metadata'] ?? '{}', true);
        // Either empty changed_fields or omitted, but should have key
        $this->assertArrayHasKey('changed_fields', $meta);
    }

    public function testConcurrentStalePackageHandled(): void
    {
        $session = $this->loginAndGetSession();
        $pkg = $this->createPackageViaModel();
        // Simulate concurrent delete between GET and POST
        $model = new \App\Models\PackageModel();
        $model->delete($pkg['id']);
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(), $csrf);
        try {
            $res = $this->withSession($session)->call('post', '/admin/packages/' . $pkg['id'], $data);
            $this->assertEquals(404, $res->response()->getStatusCode());
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertStringContainsString('Package', $e->getMessage());
        }
        // No orphan features created
        $featModel = new \App\Models\PackageFeatureModel();
        $feats = $featModel->where('package_id',$pkg['id'])->findAll();
        $this->assertCount(2, $feats); // original 2 remain, no new orphan
    }
}

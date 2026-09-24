<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AdminPackageCreateTest — Phase 5B
 */
final class AdminPackageCreateTest extends CIUnitTestCase
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
    }

    protected function tearDown(): void
    {
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
        if ($email === null) $email = 'pkgcreate-' . uniqid() . '@example.com';
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

    // — Access

    public function testUnauthenticatedCreateGetRedirects(): void
    {
        $res = $this->call('get', '/admin/packages/create');
        $res->assertRedirect();
        $this->assertStringContainsString('admin/login', $res->getRedirectUrl());
    }

    public function testUnauthenticatedPostRedirects(): void
    {
        $csrf = $this->csrf();
        $res = $this->call('post', '/admin/packages', array_merge($this->validPackageData(), $csrf));
        // Without auth, should redirect to login (adminAuth filter)
        $res->assertRedirect();
        $this->assertStringContainsString('admin/login', $res->getRedirectUrl());
    }

    public function testAuthenticatedCreatePageWorks(): void
    {
        $session = $this->loginAndGetSession();
        $res = $this->withSession($session)->call('get', '/admin/packages/create');
        $res->assertStatus(200);
        $res->assertSee('Create Package');
        $res->assertSee('Package Name');
        $res->assertSee('Slug');
        $res->assertSee('Regular Price');
        $res->assertSee('Selling Price');
        $res->assertSee('Duration Value');
        $res->assertSee('Duration Unit');
        $res->assertSee('Package Features');
        $res->assertSee('Add Feature');
        $res->assertSee('Cancel');
        $res->assertSee('Create Package');
        // Publishing columns
        $res->assertSee('Publishing');
        $res->assertSee('Badge');
        $res->assertSee('Display Order');
        // Check no Reset button
        $res->assertDontSee('>Reset<');
        $res->assertDontSee('type="reset"');
        // Check slug JS exists
        $res->assertSee('slugify');
    }

    public function testPostRequiresCsrf(): void
    {
        $session = $this->loginAndGetSession();
        $data = $this->validPackageData();
        // Do not provide CSRF — should throw SecurityException
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->withSession($session)->call('post', '/admin/packages', $data);
    }

    public function testValidPackageCreatesSuccessfully(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res->getRedirectUrl());
        // Check not redirect back to create (should not contain create)
        $this->assertStringNotContainsString('/create', $res->getRedirectUrl());

        // Follow redirect: check flash
        $session2 = $_SESSION;
        // Package should exist
        $model = new \App\Models\PackageModel();
        $pkg = $model->where('slug', $data['slug'])->first();
        $this->assertNotNull($pkg, 'Package should be created');
        $this->assertSame($data['name'], $pkg['name']);
        // Check audit
        $logModel = new \App\Models\AdminActivityLogModel();
        $log = $logModel->where('action', 'package.created')->where('entity_id', $pkg['id'])->first();
        $this->assertNotNull($log, 'Audit package.created should exist');
        $this->assertStringNotContainsString('csrf', json_encode($log['metadata'] ?? ''));

        // Check listing shows flash? Use follow-up GET
        $res2 = $this->withSession($session2)->call('get', '/admin/packages');
        $res2->assertSee('Package created successfully');
    }

    public function testPackageFeaturesCreated(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['features'=>['Alpha','Beta','Gamma']]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $model = new \App\Models\PackageModel();
        $pkg = $model->where('slug', $data['slug'])->first();
        $this->assertNotNull($pkg);
        $featModel = new \App\Models\PackageFeatureModel();
        $feats = $featModel->where('package_id', $pkg['id'])->orderBy('display_order','ASC')->findAll();
        $this->assertCount(3, $feats);
        $this->assertSame('Alpha', $feats[0]['feature_text']);
        $this->assertSame('Beta', $feats[1]['feature_text']);
        $this->assertSame('Gamma', $feats[2]['feature_text']);
        $this->assertSame('1', (string)$feats[0]['display_order']);
        $this->assertSame('2', (string)$feats[1]['display_order']);
        $this->assertSame('3', (string)$feats[2]['display_order']);
    }

    public function testFeatureOrderPreserved(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['features'=>['Zebra','Apple','Mango']]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $model = new \App\Models\PackageModel();
        $pkg = $model->where('slug', $data['slug'])->first();
        $featModel = new \App\Models\PackageFeatureModel();
        $feats = $featModel->where('package_id', $pkg['id'])->orderBy('display_order','ASC')->findAll();
        $this->assertSame('Zebra', $feats[0]['feature_text']);
        $this->assertSame('Apple', $feats[1]['feature_text']);
        $this->assertSame('Mango', $feats[2]['feature_text']);
    }

    public function testTransactionRollback(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        // Second feature will be empty -> validation should fail, and no package should be created (rollback)
        // But to test transactional atomicity when feature insert fails, we need to simulate DB failure.
        // We test that when validation fails for features, no package exists.
        $data = array_merge($this->validPackageData(['features'=>['Valid','']]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        // Should not redirect to list, but back
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages/create', $res->getRedirectUrl() ?? $res->getHeaderLine('Location') ?? '');
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults(), 'Package should not be created when feature invalid — rollback');
    }

    public function testDuplicateSlugRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $slug = 'duplicate-slug-'.uniqid();
        $data1 = array_merge($this->validPackageData(['slug'=>$slug]), $csrf);
        $res1 = $this->withSession($session)->call('post', '/admin/packages', $data1);
        $res1->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res1->getRedirectUrl());
        // Need new CSRF for second request (generate new)
        $csrf2 = $this->csrf();
        $data2 = array_merge($this->validPackageData(['slug'=>$slug, 'name'=>'Another Name']), $csrf2);
        $session2 = $_SESSION; // after first, session has new hash
        // Get fresh CSRF from service again
        $res2 = $this->withSession($session2)->call('post', '/admin/packages', $data2);
        // Should fail validation and redirect back to create
        $res2->assertRedirect();
        // Count should still be 1
        $model = new \App\Models\PackageModel();
        $this->assertSame(1, $model->countAllResults());
        // Check that second response is back to create (not to list)
        $loc = $res2->getRedirectUrl() ?? '';
        $this->assertStringContainsString('create', $loc);
    }

    public function testMissingNameRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['name'=>'']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testInvalidSlugRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['slug'=>'Invalid Slug With Spaces!']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testNegativePriceRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['regular_price'=>'-10', 'selling_price'=>'-5']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testSellingPriceGreaterThanRegularRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['regular_price'=>'1000.00', 'selling_price'=>'2000.00']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testInvalidDurationRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        // Missing unit
        $data = array_merge($this->validPackageData(['duration_value'=>'', 'duration_unit'=>'']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        // Invalid unit
        $csrf2 = $this->csrf();
        $data2 = array_merge($this->validPackageData(['duration_value'=>'10', 'duration_unit'=>'years']), $csrf2);
        $session2 = $_SESSION;
        $res2 = $this->withSession($session2)->call('post', '/admin/packages', $data2);
        $res2->assertRedirect();
        $this->assertStringContainsString('create', $res2->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testEmptyFeaturesRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['features'=>['']]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testTooManyFeaturesRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $many = array_fill(0, 51, 'Feature text');
        $data = array_merge($this->validPackageData(['features'=>$many]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testInvalidBadgeRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['badge'=>'<script>alert(1)</script>']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testInvalidGoogleFormUrlRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['google_form_url'=>'http://docs.google.com/forms/d/test']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testDeceptiveGoogleHostRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['google_form_url'=>'https://docs.google.com.evil.example/forms']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testUnknownWhatsappPlaceholderRejected(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['whatsapp_template'=>'Hi {unknown_placeholder}']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testXssEscaped(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $xssName = '<script>alert(1)</script>';
        $data = array_merge($this->validPackageData(['name'=>$xssName, 'slug'=>'xss-slug-'.uniqid(), 'short_description'=>'<img src=x onerror=alert(1)>']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $res->getRedirectUrl());
        // Check listing escapes
        $session2 = $_SESSION;
        $res2 = $this->withSession($session2)->call('get', '/admin/packages');
        $res2->assertDontSee('<script>alert(1)</script>');
        $res2->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;');
        $res2->assertDontSee('<img src=x');
    }

    public function testBooleanNormalized(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        // No is_active / is_featured sent => should be 0
        $data = $this->validPackageData();
        unset($data['is_active'], $data['is_featured']);
        $data = array_merge($data, $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $model = new \App\Models\PackageModel();
        $pkg = $model->where('slug', $data['slug'])->first();
        $this->assertNotNull($pkg);
        $this->assertSame('0', (string)$pkg['is_active']);
        $this->assertSame('0', (string)$pkg['is_featured']);
        // Now with 1
        $csrf2 = $this->csrf();
        $session2 = $_SESSION;
        $data2 = $this->validPackageData(['slug'=>'bool-test-'.uniqid(), 'is_active'=>'1', 'is_featured'=>'1']);
        $data2 = array_merge($data2, $csrf2);
        $res2 = $this->withSession($session2)->call('post', '/admin/packages', $data2);
        $res2->assertRedirect();
        $pkg2 = $model->where('slug', $data2['slug'])->first();
        $this->assertSame('1', (string)$pkg2['is_active']);
        $this->assertSame('1', (string)$pkg2['is_featured']);
    }

    public function testDisplayOrderValidated(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['display_order'=>'not-a-number']), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $this->assertStringContainsString('create', $res->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(0, $model->countAllResults());
    }

    public function testAuditExists(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $model = new \App\Models\PackageModel();
        $pkg = $model->where('slug', $data['slug'])->first();
        $logModel = new \App\Models\AdminActivityLogModel();
        $log = $logModel->where('action', 'package.created')->where('entity_id', $pkg['id'])->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('csrf', strtolower(json_encode($log)));
        $this->assertStringNotContainsString('secret', strtolower(json_encode($log)));
    }

    public function testDuplicateSubmissionCannotCreateDuplicateSlug(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $slug = 'dup-submission-'.uniqid();
        $data = array_merge($this->validPackageData(['slug'=>$slug]), $csrf);
        $res = $this->withSession($session)->call('post', '/admin/packages', $data);
        $res->assertRedirect();
        $session2 = $_SESSION;
        // Try again with same slug but new CSRF
        $csrf2 = $this->csrf();
        $data2 = array_merge($this->validPackageData(['slug'=>$slug]), $csrf2);
        $res2 = $this->withSession($session2)->call('post', '/admin/packages', $data2);
        $res2->assertRedirect();
        $this->assertStringContainsString('create', $res2->getRedirectUrl());
        $model = new \App\Models\PackageModel();
        $this->assertSame(1, $model->where('slug', $slug)->countAllResults());
    }

    public function testListingStillWorks(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(), $csrf);
        $this->withSession($session)->call('post', '/admin/packages', $data);
        $session2 = $_SESSION;
        $res = $this->withSession($session2)->call('get', '/admin/packages');
        $res->assertStatus(200);
        $res->assertSee($data['name']);
    }

    public function testForeignKeyCountZero(): void
    {
        $db = \Config\Database::connect();
        $result = $db->query("SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_TYPE='FOREIGN KEY' AND TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('packages','package_features')");
        $row = $result->getRowArray();
        $this->assertSame('0', (string)$row['c'], 'Zero FK violation');
    }
}

<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\PackageModel;
use App\Services\PackageService;
use App\Services\HtmlSanitizerService;

/**
 * 5E.2C — Rich Text Validation Redisplay Regression
 * Verifies old() full_description is sanitized before Quill receives it.
 */
final class PackageRichTextRedisplayTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $adminId;
    private string $adminEmail;
    private string $adminPass = 'Password123!';

    protected function setUp(): void
    {
        parent::setUp();
        HtmlSanitizerService::reset();
        @mkdir(WRITEPATH . 'htmlpurifier', 0755, true);
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
        $this->adminEmail = 'rich-redisplay-' . uniqid() . '@example.com';
        $admin = $this->createAdmin($this->adminEmail, $this->adminPass, true);
        $this->adminId = (int)$admin['id'];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        $_SESSION = [];
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
            'name' => 'Test Admin ' . substr(md5($email), 0, 6),
            'email' => $normalized,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => $isActive ? 1 : 0,
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

    private function loginAndGetSession(): array
    {
        $login = $this->doLogin($this->adminEmail, $this->adminPass);
        $login->assertRedirect();
        return $_SESSION;
    }

    private function validPackageData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pkg ' . uniqid(),
            'slug' => 'pkg-' . uniqid(),
            'short_description' => 'Short desc',
            'full_description' => '<p>Full <strong>bold</strong> and <em>italic</em></p>',
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

    /**
     * Extract decoded textarea value as Quill would receive via textarea.value
     */
    private function extractDecodedFullDescription(string $body): string
    {
        if (preg_match('/<textarea[^>]*id="full_description"[^>]*>(.*?)<\/textarea>/s', $body, $m)) {
            $inner = $m[1];
            return html_entity_decode($inner, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return '';
    }

    private function extractRawTextarea(string $body): string
    {
        if (preg_match('/<textarea[^>]*id="full_description"[^>]*>(.*?)<\/textarea>/s', $body, $m)) {
            return $m[1];
        }
        return '';
    }

    // ========== CREATE REDISPLAY SANITIZES ==========

    public function testCreateRedisplaySanitizesUnsafeBeforeQuill(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $malicious = '<p>Hello</p><script>alert(1)</script><p onclick="alert(1)">click</p><iframe src="https://evil.com"></iframe><a href="javascript:alert(1)">bad</a><a href="data:text/html,alert(1)">bad2</a><style>evil</style><object></object><form><input></form>';
        $data = array_merge($this->validPackageData([
            'name' => '', // trigger validation error
            'slug' => 'create-malicious-'.uniqid(),
            'full_description' => $malicious,
        ]), $csrf);

        $post = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $post->assertRedirect();
        $this->assertStringContainsString('/admin/packages/create', $post->getRedirectUrl());

        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/create');
        $get->assertStatus(200);
        $body = $get->getBody();
        $decoded = $this->extractDecodedFullDescription($body);
        $raw = $this->extractRawTextarea($body);

        // Raw source must not contain literal unsanitized script (escaped)
        $this->assertStringNotContainsString('<script>alert(1)</script>', $raw, 'Raw textarea should not contain literal script');
        // Decoded value as Quill receives it must be sanitized
        $this->assertStringNotContainsString('<script>', $decoded, 'Decoded should not contain script');
        $this->assertStringNotContainsString('<iframe', $decoded, 'Decoded should not contain iframe');
        $this->assertStringNotContainsString('onclick', $decoded, 'Decoded should not contain onclick');
        $this->assertStringNotContainsString('javascript:', $decoded, 'Decoded should not contain javascript:');
        $this->assertStringNotContainsString('data:', $decoded, 'Decoded should not contain data:');
        $this->assertStringNotContainsString('<style>', $decoded, 'Decoded should not contain style');
        $this->assertStringNotContainsString('<object', $decoded, 'Decoded should not contain object');
        $this->assertStringNotContainsString('<form', $decoded, 'Decoded should not contain form');
        // Legit part survives
        $this->assertStringContainsString('<p>Hello</p>', $decoded, 'Safe p should survive');
    }

    public function testEditRedisplaySanitizesUnsafeBeforeQuill(): void
    {
        // Create a valid package first via service
        $service = new PackageService();
        $created = $service->createPackage($this->validPackageData(['slug'=>'edit-base-'.uniqid(), 'full_description'=>'<p>Original</p>']), $this->adminId);
        $this->assertTrue($created['success']);
        $pid = $created['id'];

        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $malicious = '<p>Keep</p><script>alert(1)</script><p onclick="alert(2)">click</p><a href="javascript:alert(3)">x</a><img src=x onerror=alert(4)>';

        $data = array_merge($this->validPackageData([
            'name' => '', // validation fail
            'slug' => 'edit-malicious-'.uniqid(),
            'full_description' => $malicious,
        ]), $csrf);

        $post = $this->withSession($session)->call('POST', '/admin/packages/'.$pid, $data);
        $post->assertRedirect();
        $this->assertStringContainsString('/admin/packages/'.$pid.'/edit', $post->getRedirectUrl());

        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/'.$pid.'/edit');
        $get->assertStatus(200);
        $body = $get->getBody();
        $decoded = $this->extractDecodedFullDescription($body);

        $this->assertStringNotContainsString('<script>', $decoded);
        $this->assertStringNotContainsString('onclick', $decoded);
        $this->assertStringNotContainsString('javascript:', $decoded);
        $this->assertStringNotContainsString('onerror', $decoded);
        $this->assertStringContainsString('<p>Keep</p>', $decoded);

        // DB must not have been mutated with malicious
        $after = (new PackageModel())->find($pid);
        $this->assertStringNotContainsString('<script>', $after['full_description'] ?? '');
        $this->assertStringContainsString('<p>Original</p>', $after['full_description'] ?? '');
    }

    public function testCreateRedisplayPreservesLegitFormatting(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $legit = '<p>Hello <strong>bold</strong> <em>italic</em> <u>underline</u></p><h2>Title</h2><h3>Sub</h3><ul><li>One</li><li>Two</li></ul><ol><li>Num</li></ol><blockquote>Quote</blockquote><a href="https://example.com" target="_blank" rel="noopener">Link</a>';
        $data = array_merge($this->validPackageData([
            'name' => '', // force validation error
            'slug' => 'create-legit-'.uniqid(),
            'full_description' => $legit,
        ]), $csrf);

        $post = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $post->assertRedirect();
        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/create');
        $body = $get->getBody();
        $decoded = $this->extractDecodedFullDescription($body);

        $this->assertStringContainsString('<strong>bold</strong>', $decoded);
        $this->assertStringContainsString('<em>italic</em>', $decoded);
        $this->assertStringContainsString('<h2>Title</h2>', $decoded);
        $this->assertStringContainsString('<ul>', $decoded);
        $this->assertStringContainsString('<blockquote>', $decoded);
        $this->assertStringContainsString('href="https://example.com"', $decoded);
    }

    public function testEditRedisplayPreservesLegitFormatting(): void
    {
        $service = new PackageService();
        $created = $service->createPackage($this->validPackageData(['slug'=>'edit-legit-base-'.uniqid(), 'full_description'=>'<p>Orig</p>']), $this->adminId);
        $pid = $created['id'];

        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $legit = '<p>Keep <strong>bold</strong></p><h2>Head</h2><a href="https://example.com">good</a>';
        $data = array_merge($this->validPackageData([
            'name' => '', // validation fail
            'slug' => 'edit-legit-'.uniqid(),
            'full_description' => $legit,
        ]), $csrf);

        $post = $this->withSession($session)->call('POST', '/admin/packages/'.$pid, $data);
        $post->assertRedirect();
        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/'.$pid.'/edit');
        $decoded = $this->extractDecodedFullDescription($get->getBody());

        $this->assertStringContainsString('<strong>bold</strong>', $decoded);
        $this->assertStringContainsString('<h2>Head</h2>', $decoded);
        $this->assertStringContainsString('href="https://example.com"', $decoded);
    }

    public function testExecutableMarkupCannotSurviveRedisplay(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $payloads = [
            '<p onclick="alert(1)">x</p>',
            '<p ONCLICK="alert(1)">x</p>',
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)><circle/>',
            '<div onmouseover="alert(1)">hi</div>',
            '<a href="https://example.com" onclick="alert(1)">x</a>',
        ];
        $combined = implode('', $payloads);
        $data = array_merge($this->validPackageData([
            'name' => '',
            'slug' => 'exec-'.uniqid(),
            'full_description' => $combined,
        ]), $csrf);
        $post = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $post->assertRedirect();
        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/create');
        $decoded = $this->extractDecodedFullDescription($get->getBody());
        $this->assertStringNotContainsString('onclick', strtolower($decoded));
        $this->assertStringNotContainsString('onerror', strtolower($decoded));
        $this->assertStringNotContainsString('onload', strtolower($decoded));
        $this->assertStringNotContainsString('onmouseover', strtolower($decoded));
        // Should not contain img/svg tags at all (not allowed)
        $this->assertStringNotContainsString('<img', strtolower($decoded));
        $this->assertStringNotContainsString('<svg', strtolower($decoded));
    }

    public function testUnsafeLinkSchemesCannotSurviveRedisplay(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $payload = '<a href="javascript:alert(1)">bad1</a> <a href="JaVaScRiPt:alert(2)">bad2</a> <a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">bad3</a> <a href="vbscript:msgbox(1)">bad4</a> <a href="https://example.com">good</a> <a href="http://example.com">good2</a>';
        $data = array_merge($this->validPackageData([
            'name' => '',
            'slug' => 'scheme-'.uniqid(),
            'full_description' => $payload,
        ]), $csrf);
        $post = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $post->assertRedirect();
        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/create');
        $decoded = $this->extractDecodedFullDescription($get->getBody());
        $this->assertStringNotContainsString('javascript:', strtolower($decoded));
        $this->assertStringNotContainsString('data:', strtolower($decoded));
        $this->assertStringNotContainsString('vbscript:', strtolower($decoded));
        // good https/http should survive
        $this->assertStringContainsString('href="https://example.com"', $decoded);
        $this->assertStringContainsString('href="http://example.com"', $decoded);
    }

    public function testForgedPostStillSanitized(): void
    {
        $service = new PackageService();
        $malicious = '<p>Safe</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a><p onclick="alert(1)">x</p>';
        $result = $service->createPackage($this->validPackageData([
            'slug' => 'forged-'.uniqid(),
            'full_description' => $malicious,
        ]), $this->adminId);
        $this->assertTrue($result['success'], json_encode($result['errors'] ?? []));
        $pkg = (new PackageModel())->find($result['id']);
        $this->assertStringNotContainsString('<script>', $pkg['full_description']);
        $this->assertStringNotContainsString('javascript:', $pkg['full_description']);
        $this->assertStringNotContainsString('onclick', $pkg['full_description']);
        $this->assertStringContainsString('<p>Safe</p>', $pkg['full_description']);

        // Forged update
        $mal2 = '<h2>Head</h2><iframe src="https://evil.com"></iframe>';
        $upd = $service->updatePackage($pkg['id'], $this->validPackageData(['slug'=>$pkg['slug'], 'full_description'=>$mal2]), $this->adminId);
        $this->assertTrue($upd['success']);
        $after = (new PackageModel())->find($pkg['id']);
        $this->assertStringNotContainsString('<iframe', $after['full_description']);
        $this->assertStringContainsString('<h2>Head</h2>', $after['full_description']);
    }

    public function testStoredSanitizedStillLoadsCorrectly(): void
    {
        $service = new PackageService();
        $html = '<p>Hello <strong>bold</strong> <em>italic</em></p><h2>Title</h2><ul><li>One</li></ul><blockquote>Quote</blockquote><a href="https://example.com">link</a>';
        $created = $service->createPackage($this->validPackageData([
            'slug'=>'stored-'.uniqid(),
            'full_description'=>$html,
        ]), $this->adminId);
        $this->assertTrue($created['success']);
        $pid = $created['id'];
        $stored = (new PackageModel())->find($pid);
        $this->assertStringContainsString('<strong>bold</strong>', $stored['full_description']);
        $this->assertStringContainsString('<h2>Title</h2>', $stored['full_description']);

        $session = $this->loginAndGetSession();
        $get = $this->withSession($session)->call('GET', '/admin/packages/'.$pid.'/edit');
        $get->assertStatus(200);
        $decoded = $this->extractDecodedFullDescription($get->getBody());
        // When loading without validation error, $pkg value is used (already sanitized)
        $this->assertStringContainsString('<strong>bold</strong>', $decoded);
        $this->assertStringContainsString('<h2>Title</h2>', $decoded);
        $this->assertStringContainsString('<blockquote>', $decoded);
        $this->assertStringContainsString('href="https://example.com"', $decoded);
    }

    public function testDoNotInjectRawPostIntoQuill(): void
    {
        // Ensure raw POST not present even escaped then decoded is safe: direct check that decoded != raw
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $raw = '<p>Test</p><script>alert(document.cookie)</script>';
        $data = array_merge($this->validPackageData([
            'name' => '',
            'slug' => 'rawcheck-'.uniqid(),
            'full_description' => $raw,
        ]), $csrf);
        $post = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $post->assertRedirect();
        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/create');
        $body = $get->getBody();
        $decoded = $this->extractDecodedFullDescription($body);
        // Decoded must equal sanitized version, not raw
        $expected = HtmlSanitizerService::sanitize($raw) ?? '';
        $this->assertEquals($expected, $decoded, 'Decoded textarea should equal sanitized, not raw');
        $this->assertStringNotContainsString('<script>', $decoded);
    }
}

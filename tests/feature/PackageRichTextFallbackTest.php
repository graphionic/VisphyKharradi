<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\PackageModel;
use App\Services\PackageService;
use App\Services\HtmlSanitizerService;

/**
 * 5E.2C — Rich Text Fallback / No-JS / Quill Availability
 * Verifies textarea usable by default, wrapper hidden until Quill succeeds.
 */
final class PackageRichTextFallbackTest extends CIUnitTestCase
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
        $this->adminEmail = 'fallback-' . uniqid() . '@example.com';
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
            'full_description' => '<p>Full <strong>bold</strong></p>',
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
            'features' => ['Feature A'],
        ], $overrides);
    }

    private function extractTextareaTag(string $body): string
    {
        if (preg_match('/<textarea[^>]*id="full_description"[^>]*>.*?<\/textarea>/s', $body, $m)) {
            return $m[0];
        }
        return '';
    }

    private function extractWrapperTag(string $body): string
    {
        if (preg_match('/<div[^>]*id="quill-wrapper"[^>]*>/s', $body, $m)) {
            return $m[0];
        }
        return '';
    }

    public function testCreateHasVisibleTextareaHiddenWrapperByDefault(): void
    {
        $session = $this->loginAndGetSession();
        $resp = $this->withSession($session)->call('GET', '/admin/packages/create');
        $resp->assertStatus(200);
        $body = $resp->getBody();

        $textarea = $this->extractTextareaTag($body);
        $this->assertNotEmpty($textarea, 'Textarea must exist in create');
        // Visible by default: must have field__input class, rows, no display:none
        $this->assertStringContainsString('class="field__input', $textarea, 'Textarea should have field__input class for fallback');
        $this->assertStringContainsString('rows="8"', $textarea, 'Textarea should have rows for usable fallback');
        $this->assertStringNotContainsString('display:none', $textarea, 'Textarea must be visible by default (no display:none)');
        $this->assertStringContainsString('name="full_description"', $textarea);

        $wrapper = $this->extractWrapperTag($body);
        $this->assertNotEmpty($wrapper, 'Quill wrapper must exist');
        $this->assertStringContainsString('display:none', $wrapper, 'Wrapper must be hidden by default');
        $this->assertStringContainsString('id="quill-wrapper"', $wrapper);

        // Quill elements still present inside wrapper
        $this->assertStringContainsString('id="quill-toolbar"', $body);
        $this->assertStringContainsString('id="quill-editor"', $body);

        // No-JS hint via noscript
        $this->assertStringContainsString('<noscript>', $body, 'Noscript fallback hint should be present');
        $this->assertStringContainsString('plain textarea is active', $body);
    }

    public function testEditHasVisibleTextareaHiddenWrapperByDefault(): void
    {
        $service = new PackageService();
        $html = '<p>Hello <strong>bold</strong></p><h2>Title</h2>';
        $created = $service->createPackage($this->validPackageData(['slug'=>'edit-fallback-'.uniqid(), 'full_description'=>$html]), $this->adminId);
        $this->assertTrue($created['success']);
        $pid = $created['id'];

        $session = $this->loginAndGetSession();
        $resp = $this->withSession($session)->call('GET', '/admin/packages/'.$pid.'/edit');
        $resp->assertStatus(200);
        $body = $resp->getBody();

        $textarea = $this->extractTextareaTag($body);
        $this->assertNotEmpty($textarea);
        $this->assertStringNotContainsString('display:none', $textarea, 'Edit textarea must be visible by default');
        $this->assertStringContainsString('class="field__input', $textarea);

        $wrapper = $this->extractWrapperTag($body);
        $this->assertStringContainsString('display:none', $wrapper);

        // Existing content must be in textarea (escaped)
        $this->assertStringContainsString('&lt;p&gt;Hello', $body, 'Existing sanitized content must be escaped inside textarea');
        $this->assertStringContainsString('&lt;strong&gt;bold&lt;/strong&gt;', $body);
    }

    public function testCreateEditParityStructure(): void
    {
        $service = new PackageService();
        $created = $service->createPackage($this->validPackageData(['slug'=>'parity-'.uniqid(), 'full_description'=>'<p>Parity</p>']), $this->adminId);
        $pid = $created['id'];

        $session = $this->loginAndGetSession();
        $createBody = $this->withSession($session)->call('GET', '/admin/packages/create')->getBody();
        $editBody = $this->withSession($session)->call('GET', '/admin/packages/'.$pid.'/edit')->getBody();

        foreach (['createBody' => $createBody, 'editBody' => $editBody] as $name => $body) {
            $this->assertStringContainsString('id="quill-wrapper"', $body, "$name must have wrapper");
            $this->assertStringContainsString('id="full_description"', $body, "$name must have textarea");
            $this->assertStringContainsString('id="quill-editor"', $body, "$name must have editor");
            $this->assertStringContainsString('id="quill-toolbar"', $body, "$name must have toolbar");
            $this->assertStringContainsString('<noscript>', $body, "$name must have noscript");
        }

        // Both should have same wrapper hidden and textarea visible pattern
        $this->assertStringContainsString('id="quill-wrapper" style="display:none;', $createBody);
        $this->assertStringContainsString('id="quill-wrapper" style="display:none;', $editBody);

        $createTextarea = $this->extractTextareaTag($createBody);
        $editTextarea = $this->extractTextareaTag($editBody);
        $this->assertStringNotContainsString('display:none', $createTextarea);
        $this->assertStringNotContainsString('display:none', $editTextarea);
    }

    public function testExistingContentAvailableInFallback(): void
    {
        $service = new PackageService();
        $html = '<p>Fallback <em>content</em></p><blockquote>Quote</blockquote><a href="https://example.com">link</a>';
        $created = $service->createPackage($this->validPackageData(['slug'=>'fallback-content-'.uniqid(), 'full_description'=>$html]), $this->adminId);
        $this->assertTrue($created['success']);
        $pid = $created['id'];
        $stored = (new PackageModel())->find($pid);
        $this->assertStringContainsString('<em>content</em>', $stored['full_description']);

        $session = $this->loginAndGetSession();
        $body = $this->withSession($session)->call('GET', '/admin/packages/'.$pid.'/edit')->getBody();

        // Textarea should contain escaped stored HTML so plain textarea editing works
        $this->assertStringContainsString('&lt;p&gt;Fallback', $body);
        $this->assertStringContainsString('&lt;em&gt;content&lt;/em&gt;', $body);
        $this->assertStringContainsString('&lt;blockquote&gt;Quote&lt;/blockquote&gt;', $body);
        // Verify textarea is usable (no display:none)
        $textarea = $this->extractTextareaTag($body);
        $this->assertStringContainsString('Fallback', html_entity_decode($textarea));
        $this->assertStringNotContainsString('display:none', $textarea);
    }

    public function testFallbackSubmitsThroughServerSanitization(): void
    {
        // Simulate no-JS submission: plain textarea HTML with malicious payload
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $malicious = '<p>Good</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a>';
        $data = array_merge($this->validPackageData([
            'slug'=>'fallback-submit-'.uniqid(),
            'full_description'=>$malicious,
        ]), $csrf);

        // POST as fallback textarea would (plain POST, same as forged)
        $resp = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $resp->assertRedirect();
        // Find created package
        $model = new PackageModel();
        $pkg = $model->where('slug', $data['slug'])->first();
        $this->assertNotNull($pkg, 'Package should be created via fallback textarea submission');
        $this->assertStringNotContainsString('<script>', $pkg['full_description'], 'Server must sanitize fallback submission');
        $this->assertStringNotContainsString('javascript:', $pkg['full_description']);
        $this->assertStringContainsString('<p>Good</p>', $pkg['full_description']);

        // Edit fallback submission
        $session2 = $this->loginAndGetSession();
        // create a package to edit via fallback
        $service = new PackageService();
        $orig = $service->createPackage($this->validPackageData(['slug'=>'fallback-edit-'.uniqid(), 'full_description'=>'<p>Orig</p>']), $this->adminId);
        $pid = $orig['id'];
        $csrf2 = $this->csrf();
        $mal2 = '<h2>Head</h2><iframe src="https://evil.com"></iframe><p onclick="alert(1)">x</p>';
        $editData = array_merge($this->validPackageData(['slug'=>'fallback-edit-'.uniqid(), 'full_description'=>$mal2]), $csrf2);
        $resp2 = $this->withSession($session2)->call('POST', '/admin/packages/'.$pid, $editData);
        $resp2->assertRedirect();
        $after = $model->find($pid);
        $this->assertStringNotContainsString('<iframe', $after['full_description']);
        $this->assertStringNotContainsString('onclick', $after['full_description']);
        $this->assertStringContainsString('<h2>Head</h2>', $after['full_description']);
    }

    public function testQuillWrapperHiddenTextareaVisibleJsLogicPresent(): void
    {
        $session = $this->loginAndGetSession();
        $createBody = $this->withSession($session)->call('GET', '/admin/packages/create')->getBody();
        // JS should contain progressive enhancement logic: show wrapper / hide textarea on success, try/catch
        $this->assertStringContainsString('quillWrapper', $createBody, 'JS should reference quillWrapper');
        $this->assertStringContainsString("textarea.style.display", $createBody, 'JS should hide textarea on Quill success');
        $this->assertStringContainsString("'none'", $createBody);
        $this->assertStringContainsString("quillWrapper.style.display", $createBody, 'JS should show wrapper on success');
        $this->assertStringContainsString('try {', $createBody, 'JS should have try/catch for Quill init failure');
        $this->assertStringContainsString("window.Quill", $createBody);

        $service = new PackageService();
        $created = $service->createPackage($this->validPackageData(['slug'=>'jslogic-'.uniqid()]), $this->adminId);
        $editBody = $this->withSession($session)->call('GET', '/admin/packages/'.$created['id'].'/edit')->getBody();
        $this->assertStringContainsString('quillWrapper', $editBody);
        $this->assertStringContainsString("textarea.style.display", $editBody);
        $this->assertStringContainsString("quillWrapper.style.display", $editBody);
    }

    public function testNoJsTextareaStillSubmittableStructure(): void
    {
        $session = $this->loginAndGetSession();
        $body = $this->withSession($session)->call('GET', '/admin/packages/create')->getBody();
        // Textarea must have name attribute so form submits without JS
        $this->assertStringContainsString('name="full_description"', $body);
        // Must be inside form
        $this->assertMatchesRegularExpression('/<form[^>]*id="pkg-create-form"[^>]*>.*?<textarea[^>]*id="full_description".*?<\/textarea>.*?<\/form>/s', $body);
        // Wrapper hidden should not affect textarea submission
        $this->assertStringContainsString('id="quill-wrapper" style="display:none;', $body);
    }
}

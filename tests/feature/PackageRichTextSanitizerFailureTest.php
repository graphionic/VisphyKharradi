<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\PackageModel;
use App\Services\PackageService;
use App\Services\HtmlSanitizerService;

/**
 * 5E.2C — Sanitizer failure handling (fail-closed)
 * Verifies sanitizer exception never stores raw, never exposes details, logs without body,
 * and preserves existing description on Edit.
 */
final class PackageRichTextSanitizerFailureTest extends CIUnitTestCase
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
        HtmlSanitizerService::$simulateFailure = false;
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
        $this->adminEmail = 'sanitizer-fail-' . uniqid() . '@example.com';
        $admin = $this->createAdmin($this->adminEmail, $this->adminPass, true);
        $this->adminId = (int)$admin['id'];
    }

    protected function tearDown(): void
    {
        HtmlSanitizerService::$simulateFailure = false;
        HtmlSanitizerService::reset();
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

    private function currentLogPath(): string
    {
        return WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
    }

    private function readCurrentLog(): string
    {
        $path = $this->currentLogPath();
        if (!is_file($path)) return '';
        return (string)file_get_contents($path);
    }

    // ========== CREATE FAILS SAFELY, RAW NEVER STORED ==========

    public function testCreateFailsSafelyWhenPurificationFailsAndRawNotStored(): void
    {
        $unique = 'FAILCLOSED_CREATE_' . uniqid() . '_9f3a7c';
        $malicious = '<p>' . $unique . '</p><script>alert(1)</script>';

        HtmlSanitizerService::$simulateFailure = true;

        $service = new PackageService();
        $data = $this->validPackageData(['slug' => 'fail-create-' . uniqid(), 'full_description' => $malicious]);
        $result = $service->createPackage($data, $this->adminId);

        $this->assertFalse($result['success'], 'Create must fail when sanitizer throws');
        $this->assertArrayHasKey('full_description', $result['errors'] ?? [], 'Should have full_description validation error');
        $this->assertStringNotContainsString('Simulated', $result['errors']['full_description'] ?? '', 'Error must not expose exception details');
        $this->assertStringNotContainsString($unique, $result['errors']['full_description'] ?? '', 'Error must not expose submitted content');
        $this->assertStringContainsString('could not be processed', strtolower($result['errors']['full_description'] ?? ''), 'Should be generic processing error');

        // DB must not contain raw — no package created
        $model = new PackageModel();
        $found = $model->where('slug', $data['slug'])->first();
        $this->assertNull($found, 'Package must not be persisted when sanitization fails');

        // Also via direct DB search for raw payload — ensure nowhere stored
        $all = $model->findAll();
        foreach ($all as $row) {
            $this->assertStringNotContainsString($unique, $row['full_description'] ?? '', 'Raw payload must never be stored');
        }

        HtmlSanitizerService::$simulateFailure = false;

        // Normal behavior after reset still works
        $okData = $this->validPackageData(['slug' => 'fail-create-ok-' . uniqid(), 'full_description' => '<p>Ok <strong>bold</strong></p>']);
        $okResult = $service->createPackage($okData, $this->adminId);
        $this->assertTrue($okResult['success'], 'Normal create should succeed after simulation disabled');
        $stored = $model->find($okResult['id']);
        $this->assertStringContainsString('<strong>bold</strong>', $stored['full_description'] ?? '');
    }

    public function testCreateViaHttpFailsSafelyAndDoesNotExposeDetails(): void
    {
        $unique = 'FAILCLOSED_HTTP_CREATE_' . uniqid() . '_x7k9';
        $payload = '<p>' . $unique . '</p><p>test</p>';

        HtmlSanitizerService::$simulateFailure = true;

        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['slug'=>'http-fail-create-'.uniqid(), 'full_description'=>$payload]), $csrf);

        $resp = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $resp->assertRedirect();
        $this->assertStringContainsString('/admin/packages/create', $resp->getRedirectUrl() ?? '');

        // Follow redirect to form and ensure error displayed is generic, no exception details or payload
        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/create');
        $get->assertStatus(200);
        $body = $get->getBody();

        $this->assertStringNotContainsString('Simulated', $body, 'Must not expose exception class');
        $this->assertStringNotContainsString('RuntimeException', $body);
        $this->assertStringNotContainsString($unique, $body, 'Must not expose submitted payload');
        $this->assertStringNotContainsString('HtmlSanitizerService', $body);
        $this->assertStringNotContainsString('purification failed', strtolower($body));
        $this->assertStringNotContainsString('trycloudflare', strtolower($body));
        // Should contain generic validation message
        $this->assertStringContainsString('could not be processed', strtolower($body));

        // DB not persisted
        $model = new PackageModel();
        $found = $model->where('slug', $data['slug'])->first();
        $this->assertNull($found);

        HtmlSanitizerService::$simulateFailure = false;
    }

    // ========== EDIT FAILS SAFELY, PRESERVES EXISTING ==========

    public function testEditFailsSafelyWhenPurificationFailsAndPreservesExisting(): void
    {
        $service = new PackageService();
        $origHtml = '<p>Original <strong>keep</strong></p><h2>Head</h2>';
        $created = $service->createPackage($this->validPackageData(['slug'=>'orig-edit-'.uniqid(), 'full_description'=>$origHtml]), $this->adminId);
        $this->assertTrue($created['success']);
        $pid = $created['id'];
        $before = (new PackageModel())->find($pid);
        $this->assertStringContainsString('Original', $before['full_description']);

        $unique = 'FAILCLOSED_EDIT_' . uniqid() . '_m4p2';
        $maliciousEdit = '<p>' . $unique . ' New</p><script>alert(1)</script>';

        HtmlSanitizerService::$simulateFailure = true;

        $editData = $this->validPackageData(['slug'=>$before['slug'], 'full_description'=>$maliciousEdit, 'name'=>$before['name']]);
        $result = $service->updatePackage($pid, $editData, $this->adminId);

        $this->assertFalse($result['success'], 'Edit must fail when sanitizer throws');
        $this->assertArrayHasKey('full_description', $result['errors'] ?? []);
        $this->assertStringContainsString('could not be processed', strtolower($result['errors']['full_description'] ?? ''));

        $after = (new PackageModel())->find($pid);
        $this->assertEquals($before['full_description'], $after['full_description'], 'Existing description must be preserved');
        $this->assertStringNotContainsString($unique, $after['full_description'] ?? '');

        // Via HTTP as well
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $httpData = array_merge($this->validPackageData(['slug'=>$before['slug'], 'full_description'=>'<p>'.$unique.'_http</p>']), $csrf);
        $resp = $this->withSession($session)->call('POST', '/admin/packages/'.$pid, $httpData);
        $resp->assertRedirect();
        $this->assertStringContainsString('/admin/packages/'.$pid.'/edit', $resp->getRedirectUrl() ?? '');
        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/'.$pid.'/edit');
        $body = $get->getBody();
        $this->assertStringNotContainsString($unique, $body);
        $this->assertStringNotContainsString('Simulated', $body);
        $this->assertStringContainsString('could not be processed', strtolower($body));

        $after2 = (new PackageModel())->find($pid);
        $this->assertEquals($before['full_description'], $after2['full_description']);

        HtmlSanitizerService::$simulateFailure = false;

        // Normal edit after should succeed
        $okEdit = $this->validPackageData(['slug'=>$before['slug'], 'full_description'=>'<p>Updated <em>ok</em></p>']);
        $okResult = $service->updatePackage($pid, $okEdit, $this->adminId);
        $this->assertTrue($okResult['success']);
        $updated = (new PackageModel())->find($pid);
        $this->assertStringContainsString('<em>ok</em>', $updated['full_description']);
    }

    public function testEditPreservesDescriptionWhenSanitizerFailsViaMediaPath(): void
    {
        // Ensure createPackageWithMedia also fails closed
        $unique = 'FAILCLOSED_MEDIA_' . uniqid();
        HtmlSanitizerService::$simulateFailure = true;

        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'media-fail-'.uniqid(), 'full_description'=>'<p>'.$unique.'</p>']);
        $result = $service->createPackageWithMedia($data, $this->adminId, null, [], []);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('full_description', $result['errors'] ?? []);

        $found = (new PackageModel())->where('slug', $data['slug'])->first();
        $this->assertNull($found, 'Media create must not persist when sanitizer fails');

        HtmlSanitizerService::$simulateFailure = false;
    }

    // ========== EXCEPTION DETAILS NOT EXPOSED ==========

    public function testExceptionDetailsNotExposedAndNoStackTrace(): void
    {
        $unique = 'FAILCLOSED_EXPOSE_' . uniqid() . '_q8w2';
        HtmlSanitizerService::$simulateFailure = true;

        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $data = array_merge($this->validPackageData(['slug'=>'expose-'.uniqid(), 'full_description'=>'<p>'.$unique.'</p>']), $csrf);

        $resp = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $resp->assertRedirect();
        $sess2 = $_SESSION;
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/create');
        $body = $get->getBody();

        // Must not contain exception details, paths, traces
        $this->assertStringNotContainsString('Exception', $body);
        $this->assertStringNotContainsString('RuntimeException', $body);
        $this->assertStringNotContainsString('Simulated', $body);
        $this->assertStringNotContainsString('Stack trace', $body);
        $this->assertStringNotContainsString('htmlpurifier', strtolower($body));
        $this->assertStringNotContainsString('/var/', $body);
        $this->assertStringNotContainsString('/home/', $body);
        $this->assertStringNotContainsString('WRITEPATH', $body);
        // Must not contain payload
        $this->assertStringNotContainsString($unique, $body);
        // Must not contain file path like .php
        $this->assertStringNotContainsString('HtmlSanitizerService.php', $body);

        HtmlSanitizerService::$simulateFailure = false;
    }

    // ========== LOGGING WITHOUT BODY ==========

    public function testSubmittedContentNotWrittenToLogsAndFailureIsLogged(): void
    {
        $unique = 'FAILCLOSED_LOG_' . uniqid() . '_z4x9y2';
        $payload = '<p>'.$unique.'_PAYLOAD_SECRET_'.uniqid().'</p>';

        // Capture log size before
        $logPath = $this->currentLogPath();
        $beforeSize = is_file($logPath) ? filesize($logPath) : 0;

        HtmlSanitizerService::$simulateFailure = true;

        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'log-test-'.uniqid(), 'full_description'=>$payload]);
        $result = $service->createPackage($data, $this->adminId);
        $this->assertFalse($result['success']);

        HtmlSanitizerService::$simulateFailure = false;

        // Read log increment
        $log = $this->readCurrentLog();
        $newContent = $beforeSize > 0 ? substr($log, $beforeSize) : $log;
        // Alternatively check whole log for payload
        $this->assertStringNotContainsString($unique, $log, 'Submitted rich-text body must not be written to logs');
        $this->assertStringNotContainsString($payload, $log, 'Full payload must not be logged');
        // Should contain our failure marker without body
        $this->assertStringContainsString('HtmlSanitizerService purification failed', $log, 'Failure must be logged for diagnosis');
        $this->assertStringContainsString('PackageService full_description sanitization failed', $log, 'PackageService should log sanitization failure');

        // Also ensure normal sanitization does NOT log failure
        $beforeLog2 = $this->readCurrentLog();
        $okData = $this->validPackageData(['slug'=>'log-ok-'.uniqid(), 'full_description'=>'<p>Normal <strong>ok</strong></p>']);
        $okResult = $service->createPackage($okData, $this->adminId);
        $this->assertTrue($okResult['success']);
        $afterLog2 = $this->readCurrentLog();
        // New log increment should not contain failure marker for this ok operation (we just check payload not leaked, but failure not re-logged for ok)
        // We can't assert exact, but ensure our unique not in log
        $this->assertStringNotContainsString($unique, $afterLog2);
    }

    // ========== NORMAL BEHAVIOR UNCHANGED ==========

    public function testNormalSanitizerBehaviorRemainsUnchanged(): void
    {
        HtmlSanitizerService::$simulateFailure = false;
        HtmlSanitizerService::reset();

        $service = new PackageService();

        // Allowlist preserves
        $html = '<p>Hello <strong>bold</strong> <em>italic</em> <u>underline</u></p><h2>Title</h2><h3>Sub</h3><ul><li>One</li></ul><ol><li>Num</li></ol><blockquote>Quote</blockquote><a href="https://example.com">link</a>';
        $result = $service->createPackage($this->validPackageData(['slug'=>'normal-'.uniqid(), 'full_description'=>$html]), $this->adminId);
        $this->assertTrue($result['success'], json_encode($result['errors'] ?? []));
        $stored = (new PackageModel())->find($result['id']);
        $this->assertStringContainsString('<strong>bold</strong>', $stored['full_description']);
        $this->assertStringContainsString('<h2>Title</h2>', $stored['full_description']);
        $this->assertStringContainsString('<blockquote>', $stored['full_description']);
        $this->assertStringContainsString('href="https://example.com"', $stored['full_description']);

        // Dangerous stripped
        $mal = '<p>Safe</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a><p onclick="alert(1)">x</p><iframe></iframe><style>evil</style>';
        $result2 = $service->createPackage($this->validPackageData(['slug'=>'normal-mal-'.uniqid(), 'full_description'=>$mal]), $this->adminId);
        $this->assertTrue($result2['success']);
        $stored2 = (new PackageModel())->find($result2['id']);
        $this->assertStringNotContainsString('<script>', $stored2['full_description']);
        $this->assertStringNotContainsString('javascript:', $stored2['full_description']);
        $this->assertStringNotContainsString('onclick', $stored2['full_description']);
        $this->assertStringContainsString('<p>Safe</p>', $stored2['full_description']);

        // Direct sanitize allowlist
        $direct = HtmlSanitizerService::sanitize('<a href="https://example.com" title="t">a</a><a href="data:text/html,evil">b</a>');
        $this->assertStringContainsString('href="https://example.com"', $direct);
        $this->assertStringNotContainsString('data:', $direct);
    }

    // ========== SIMULATION NOT REMOTELY TRIGGERABLE ==========

    public function testSimulationNotTriggerableViaHttpParameter(): void
    {
        HtmlSanitizerService::$simulateFailure = false;

        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        // Attempt to inject simulate flags via POST — must be ignored
        $data = array_merge($this->validPackageData(['slug'=>'no-trigger-'.uniqid(), 'full_description'=>'<p>Safe content</p>']), $csrf, [
            'simulateFailure' => '1',
            'HtmlSanitizerService' => ['simulateFailure' => true],
            'full_description_simulate' => '1',
            '_simulate' => '1',
        ]);

        // Even with injection attempt, normal create should succeed (sanitizer not forced)
        $resp = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $resp->assertRedirect();
        // Should go to listing on success, not back to create with error
        $this->assertStringContainsString('/admin/packages', $resp->getRedirectUrl() ?? '');
        $this->assertStringNotContainsString('/admin/packages/create', $resp->getRedirectUrl() ?? '');

        $model = new PackageModel();
        $found = $model->where('slug', $data['slug'])->first();
        $this->assertNotNull($found, 'Package should be created — HTTP params must not trigger failure simulation');
        $this->assertStringContainsString('<p>Safe content</p>', $found['full_description']);

        // Also verify that directly setting static flag outside testing guard is ignored if env not testing?
        // In this test env IS testing, so flag would work if set directly — but HTTP params must not set it
        $this->assertFalse(HtmlSanitizerService::$simulateFailure, 'Static flag should remain false after HTTP attempt');
    }

    public function testRedisplaySanitizationFailsClosedAndDoesNotExposeRaw(): void
    {
        // Create a package normally
        $service = new PackageService();
        $created = $service->createPackage($this->validPackageData(['slug'=>'redisplay-fail-'.uniqid(), 'full_description'=>'<p>Orig</p>']), $this->adminId);
        $pid = $created['id'];

        // Now simulate failure during redisplay (old input)
        HtmlSanitizerService::$simulateFailure = true;

        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        // First, POST invalid that will cause validation error and store old input with malicious payload
        // We need to trigger validation error on another field so old input is saved, then GET will render with sanitization failure
        $unique = 'REDISPLAY_FAIL_' . uniqid();
        $malicious = '<p>'.$unique.'</p><script>alert(1)</script>';
        // Invalid name to trigger validation, with full_description malicious
        $invalidData = array_merge($this->validPackageData(['name'=>'', 'slug'=>'redisplay-'.uniqid(), 'full_description'=>$malicious]), $csrf);
        $post = $this->withSession($session)->call('POST', '/admin/packages', $invalidData);
        $post->assertRedirect();

        $sess2 = $_SESSION;
        // Now GET create with old input — sanitization will fail, should return empty not raw
        $get = $this->withSession($sess2)->call('GET', '/admin/packages/create');
        $get->assertStatus(200);
        $body = $get->getBody();
        $this->assertStringNotContainsString($unique, $body, 'Redisplay must not expose raw when sanitizer fails');
        // Should not contain exception details — check that redisplay does not leak simulated failure message
        $this->assertStringNotContainsString('Simulated', $body);
        $this->assertStringNotContainsString('purification failed', strtolower($body));

        // Textarea should be empty or not contain unsanitized — check decoded value only (page legitimately contains <script src> for Quill)
        if (preg_match('/<textarea[^>]*id="full_description"[^>]*>(.*?)<\/textarea>/s', $body, $m)) {
            $inner = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $this->assertStringNotContainsString($unique, $inner);
            $this->assertStringNotContainsString('<script>', $inner);
        }

        HtmlSanitizerService::$simulateFailure = false;
    }
}

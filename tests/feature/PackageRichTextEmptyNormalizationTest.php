<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\PackageModel;
use App\Services\PackageService;
use App\Services\HtmlSanitizerService;

/**
 * 5E.2C — Logical empty-content normalization for Full Description
 * Quill empty markup, whitespace-only, br-only, etc. must become NULL.
 * Meaningful content must be preserved.
 */
final class PackageRichTextEmptyNormalizationTest extends CIUnitTestCase
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
        $this->adminEmail = 'empty-norm-' . uniqid() . '@example.com';
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

    // ========== Helpers ==========

    private function assertStoredIsNull(string $slug, string $message = ''): void
    {
        $model = new PackageModel();
        $pkg = $model->where('slug', $slug)->first();
        $this->assertNotNull($pkg, 'Package should exist: ' . $message);
        $this->assertNull($pkg['full_description'], 'Full description should be NULL (normalized empty): ' . $message . ' got: ' . var_export($pkg['full_description'], true));
    }

    private function assertStoredNotNullAndContains(string $slug, string $needle): void
    {
        $model = new PackageModel();
        $pkg = $model->where('slug', $slug)->first();
        $this->assertNotNull($pkg);
        $this->assertNotNull($pkg['full_description'], 'Should be stored, not NULL');
        $this->assertStringContainsString($needle, $pkg['full_description']);
    }

    // ========== Quill empty editor content becomes NULL ==========

    public function testQuillEmptyEditorContentBecomesNull(): void
    {
        $service = new PackageService();
        $empties = [
            '<p><br></p>',               // classic Quill empty
            '<p><br></p><p><br></p>',   // multiple
            '<p><br/></p>',              // self-closing
            '<p><br /></p>',
            '<div><br></div>',           // div not allowed but after purify may become ?
            '<p></p>',                   // empty paragraph
            '<p>   </p>',                // whitespace paragraph
            '<p><br>   </p>',
        ];
        foreach ($empties as $idx => $html) {
            $slug = 'quill-empty-' . uniqid() . '-' . $idx;
            $result = $service->createPackage($this->validPackageData(['slug'=>$slug, 'full_description'=>$html]), $this->adminId);
            $this->assertTrue($result['success'], "Create should succeed for Quill empty variant $idx: $html - " . json_encode($result['errors'] ?? []));
            $this->assertStoredIsNull($slug, "Quill empty variant $idx: " . $html);
            // Also check service's returned normalized data is null
            $this->assertNull($result['data']['full_description'] ?? null, "Service data should be null for empty variant $idx");
        }
    }

    public function testWhitespaceOnlyContentBecomesNull(): void
    {
        $service = new PackageService();
        $whites = [
            '   ',
            "\n\n   \n",
            "\t  \n",
            '    <p>   </p>   ',
            ' &nbsp; ',
            '<p>&nbsp;</p>',
            '<p> &#160; </p>',
            '<p>    <br>   </p>',
        ];
        foreach ($whites as $idx => $html) {
            $slug = 'ws-empty-' . uniqid() . '-' . $idx;
            $result = $service->createPackage($this->validPackageData(['slug'=>$slug, 'full_description'=>$html]), $this->adminId);
            $this->assertTrue($result['success'], "Whitespace variant $idx should succeed");
            $this->assertStoredIsNull($slug, "Whitespace variant $idx: " . var_export($html,true));
        }
    }

    public function testEquivalentEmptySanitizedMarkupBecomesNull(): void
    {
        // Various empty sanitized markups that are not exact string but logically empty
        $service = new PackageService();
        $cases = [
            '<p><br></p>', // base
            '<p> <br> </p>',
            '<p></p><p><br></p>',
            '<h2>   </h2>',
            '<h3><br></h3>',
            '<ul><li>   </li></ul>',
            '<ol><li>  <br>  </li></ol>',
            '<blockquote>   </blockquote>',
            '<blockquote><br></blockquote>',
            '<a href="https://example.com">   </a>', // link with whitespace only
            '<p><strong>   </strong></p>',
            '<p><em> </em> <u>  </u></p>',
            '<p><strong><em>   <br>  </em></strong></p>',
        ];
        foreach ($cases as $idx => $html) {
            $slug = 'equiv-empty-' . uniqid() . '-' . $idx;
            $result = $service->createPackage($this->validPackageData(['slug'=>$slug, 'full_description'=>$html]), $this->adminId);
            $this->assertTrue($result['success'], "Equiv empty case $idx: $html");
            $this->assertStoredIsNull($slug, "Equiv empty case $idx: $html");
        }
    }

    // ========== Create stores NULL for empty description ==========

    public function testCreateStoresNullForEmptyDescriptionViaService(): void
    {
        $service = new PackageService();
        $slug = 'create-null-' . uniqid();
        $result = $service->createPackage($this->validPackageData(['slug'=>$slug, 'full_description'=>'<p><br></p>']), $this->adminId);
        $this->assertTrue($result['success']);
        $this->assertStoredIsNull($slug);
        $this->assertNull($result['data']['full_description']);
    }

    public function testCreateStoresNullForEmptyDescriptionViaHttp(): void
    {
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $slug = 'create-http-null-' . uniqid();
        $data = array_merge($this->validPackageData(['slug'=>$slug, 'full_description'=>'<p><br></p>']), $csrf);
        $resp = $this->withSession($session)->call('POST', '/admin/packages', $data);
        $resp->assertRedirect();
        $this->assertStringContainsString('/admin/packages', $resp->getRedirectUrl() ?? '');
        $this->assertStoredIsNull($slug);
    }

    // ========== Edit can clear an existing description to NULL ==========

    public function testEditCanClearExistingDescriptionToNull(): void
    {
        $service = new PackageService();
        $slug = 'edit-clear-' . uniqid();
        $orig = '<p>Original <strong>keep</strong></p><h2>Title</h2>';
        $created = $service->createPackage($this->validPackageData(['slug'=>$slug, 'full_description'=>$orig]), $this->adminId);
        $this->assertTrue($created['success']);
        $pid = $created['id'];
        $before = (new PackageModel())->find($pid);
        $this->assertNotNull($before['full_description']);
        $this->assertStringContainsString('Original', $before['full_description']);

        // Clear via empty Quill
        $editData = $this->validPackageData(['slug'=>$slug, 'full_description'=>'<p><br></p>', 'name'=>$before['name']]);
        $result = $service->updatePackage($pid, $editData, $this->adminId);
        $this->assertTrue($result['success'], json_encode($result['errors'] ?? []));
        $after = (new PackageModel())->find($pid);
        $this->assertNull($after['full_description'], 'Clearing should persist NULL, got: ' . var_export($after['full_description'], true));

        // Clear via whitespace
        $created2 = $service->createPackage($this->validPackageData(['slug'=>'edit-clear2-'.uniqid(), 'full_description'=>'<p>To be cleared</p>']), $this->adminId);
        $pid2 = $created2['id'];
        $edit2 = $this->validPackageData(['slug'=>$created2['data']['slug'], 'full_description'=>'   ']);
        $result2 = $service->updatePackage($pid2, $edit2, $this->adminId);
        $this->assertTrue($result2['success']);
        $after2 = (new PackageModel())->find($pid2);
        $this->assertNull($after2['full_description']);

        // Clear via HTTP (Quill empty)
        $session = $this->loginAndGetSession();
        $csrf = $this->csrf();
        $created3 = $service->createPackage($this->validPackageData(['slug'=>'http-clear-'.uniqid(), 'full_description'=>'<p>Will clear http</p>']), $this->adminId);
        $pid3 = $created3['id'];
        $row3 = (new PackageModel())->find($pid3);
        $this->assertNotNull($row3['full_description']);
        $httpEdit = array_merge($this->validPackageData(['slug'=>$row3['slug'], 'full_description'=>'<p><br></p>']), $csrf);
        $resp = $this->withSession($session)->call('POST', '/admin/packages/'.$pid3, $httpEdit);
        $resp->assertRedirect();
        $after3 = (new PackageModel())->find($pid3);
        $this->assertNull($after3['full_description'], 'HTTP clear via Quill empty should persist NULL');

        // Clear via HTTP with empty string — fresh session/csrf
        $session2 = $this->loginAndGetSession();
        $csrf2 = $this->csrf();
        $created4 = $service->createPackage($this->validPackageData(['slug'=>'http-clear4-'.uniqid(), 'full_description'=>'<p>Also clear</p>']), $this->adminId);
        $pid4 = $created4['id'];
        $httpEdit2 = array_merge($this->validPackageData(['slug'=>(new PackageModel())->find($pid4)['slug'], 'full_description'=>'']), $csrf2);
        $resp2 = $this->withSession($session2)->call('POST', '/admin/packages/'.$pid4, $httpEdit2);
        $resp2->assertRedirect();
        $after4 = (new PackageModel())->find($pid4);
        $this->assertNull($after4['full_description'], 'HTTP clear via empty string should persist NULL');
    }

    // ========== Meaningful formatted text remains stored ==========

    public function testMeaningfulFormattedTextRemainsStored(): void
    {
        $service = new PackageService();
        $cases = [
            '<p>Normal text</p>' => '<p>Normal text</p>',
            '<p>Hello <strong>bold</strong> and <em>italic</em></p>' => '<strong>bold</strong>',
            '<p><u>underline</u> text</p>' => '<u>underline</u>',
            '<h2>Heading Text</h2>' => '<h2>Heading Text</h2>',
            '<h3>Subheading</h3>' => '<h3>Subheading</h3>',
            '<ul><li>Item one</li><li>Item two</li></ul>' => '<li>Item one</li>',
            '<ol><li>Numbered</li></ol>' => 'Numbered',
            '<blockquote>Quote with text</blockquote>' => 'Quote with text',
            '<a href="https://example.com">click here</a>' => 'href="https://example.com"',
            '<p>Text with <a href="https://example.com">link</a> inside</p>' => 'href="https://example.com"',
            '<p>  Hello with spaces  </p>' => 'Hello with spaces',
            '<p>Zero: 0</p>' => '0',
        ];
        foreach ($cases as $html => $needle) {
            $slug = 'meaningful-' . uniqid();
            $result = $service->createPackage($this->validPackageData(['slug'=>$slug, 'full_description'=>$html]), $this->adminId);
            $this->assertTrue($result['success'], "Meaningful case failed: $html - " . json_encode($result['errors'] ?? []));
            $this->assertStoredNotNullAndContains($slug, $needle);
        }
    }

    public function testMeaningfulLinksListHeadingsNotIncorrectlyRemoved(): void
    {
        $service = new PackageService();
        // Ensure headings/list/links with text are not considered empty
        $checks = [
            '<h2>  Title with spaces  </h2>' => 'Title',
            '<ul><li>  spaced  </li></ul>' => 'spaced',
            '<blockquote>   Quote   </blockquote>' => 'Quote',
            '<a href="https://example.com">   visible   </a>' => 'visible',
            '<p><strong>  bold text  </strong></p>' => 'bold text',
        ];
        foreach ($checks as $html => $expectedText) {
            $sanitized = HtmlSanitizerService::sanitize($html);
            $this->assertNotNull($sanitized);
            $this->assertFalse(HtmlSanitizerService::isLogicallyEmpty($sanitized), "Should NOT be empty: $html -> $sanitized");
            $slug = 'not-empty-' . uniqid();
            $result = $service->createPackage($this->validPackageData(['slug'=>$slug, 'full_description'=>$html]), $this->adminId);
            $this->assertTrue($result['success']);
            $stored = (new PackageModel())->where('slug',$slug)->first();
            $this->assertNotNull($stored['full_description'], "Should not be null for meaningful: $html");
            // Ensure text preserved after round-trip
            $this->assertStringContainsString($expectedText, strip_tags($stored['full_description']));
        }

        // Direct helper checks
        $this->assertFalse(HtmlSanitizerService::isLogicallyEmpty('<p>hi</p>'));
        $this->assertFalse(HtmlSanitizerService::isLogicallyEmpty('<a href="https://example.com">x</a>'));
        $this->assertTrue(HtmlSanitizerService::isLogicallyEmpty('<p><br></p>'));
        $this->assertTrue(HtmlSanitizerService::isLogicallyEmpty('<p>   </p>'));
        $this->assertTrue(HtmlSanitizerService::isLogicallyEmpty('<ul><li>   </li></ul>'));
        $this->assertTrue(HtmlSanitizerService::isLogicallyEmpty('   '));
        $this->assertTrue(HtmlSanitizerService::isLogicallyEmpty(null));
        $this->assertTrue(HtmlSanitizerService::isLogicallyEmpty(''));
        $this->assertTrue(HtmlSanitizerService::isLogicallyEmpty('<p>&nbsp;</p>'));
    }

    // ========== Forged input continues through existing sanitizer ==========

    public function testForgedInputContinuesThroughExistingSanitizer(): void
    {
        $service = new PackageService();
        // Script etc. should be stripped but meaningful part kept, not normalized to null incorrectly
        $mal1 = '<p>Safe</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a>';
        $slug1 = 'forged-'.uniqid();
        $result1 = $service->createPackage($this->validPackageData(['slug'=>$slug1, 'full_description'=>$mal1]), $this->adminId);
        $this->assertTrue($result1['success']);
        $stored1 = (new PackageModel())->where('slug',$slug1)->first();
        $this->assertStringContainsString('<p>Safe</p>', $stored1['full_description']);
        $this->assertStringNotContainsString('<script>', $stored1['full_description']);
        $this->assertStringNotContainsString('javascript:', $stored1['full_description']);
        $this->assertNotNull($stored1['full_description'], 'Should not be null — meaningful part remains');

        // Only malicious content should become NULL (after stripping, empty)
        $malOnly = '<script>alert(1)</script><p onclick="alert(1)">   </p><iframe></iframe>';
        $slug2 = 'forged-only-'.uniqid();
        $result2 = $service->createPackage($this->validPackageData(['slug'=>$slug2, 'full_description'=>$malOnly]), $this->adminId);
        $this->assertTrue($result2['success']);
        $stored2 = (new PackageModel())->where('slug',$slug2)->first();
        $this->assertNull($stored2['full_description'], 'Only-malicious that sanitizes to empty should become NULL');

        // Meaningful plus malicious should keep meaningful
        $malMixed = '<h2>Head</h2><script>alert(1)</script><p>Text</p>';
        $slug3 = 'forged-mixed-'.uniqid();
        $result3 = $service->createPackage($this->validPackageData(['slug'=>$slug3, 'full_description'=>$malMixed]), $this->adminId);
        $this->assertTrue($result3['success']);
        $stored3 = (new PackageModel())->where('slug',$slug3)->first();
        $this->assertStringContainsString('<h2>Head</h2>', $stored3['full_description']);
        $this->assertStringContainsString('<p>Text</p>', $stored3['full_description']);
    }

    // ========== Normal sanitized rich content unchanged ==========

    public function testNormalSanitizedRichContentUnchanged(): void
    {
        $html = '<p>Hello <strong>bold</strong> <em>italic</em> <u>underline</u></p><h2>Title</h2><h3>Sub</h3><ul><li>One</li><li>Two</li></ul><ol><li>Num</li></ol><blockquote>Quote</blockquote><a href="https://example.com" title="t">Link</a>';
        $sanitized = HtmlSanitizerService::sanitize($html);
        // Direct sanitize should preserve meaningful content exactly (no empty normalization at sanitizer level)
        $this->assertStringContainsString('<strong>bold</strong>', $sanitized);
        $this->assertStringContainsString('<h2>Title</h2>', $sanitized);

        $service = new PackageService();
        $slug = 'normal-unchanged-'.uniqid();
        $result = $service->createPackage($this->validPackageData(['slug'=>$slug, 'full_description'=>$html]), $this->adminId);
        $this->assertTrue($result['success']);
        $stored = (new PackageModel())->where('slug',$slug)->first();
        $this->assertStringContainsString('<strong>bold</strong>', $stored['full_description']);
        $this->assertStringContainsString('<h2>Title</h2>', $stored['full_description']);
        $this->assertStringContainsString('<blockquote>', $stored['full_description']);
        $this->assertEquals($sanitized, $stored['full_description'], 'Stored should equal sanitized when meaningful');
    }

    // ========== Create/Edit same normalization behavior ==========

    public function testCreateAndEditUseSameNormalization(): void
    {
        $service = new PackageService();
        $emptyHtml = '<p><br></p>';
        $meaningfulHtml = '<p>Keep <strong>me</strong></p>';

        // Create with empty -> NULL
        $slug1 = 'same-norm-create-' . uniqid();
        $r1 = $service->createPackage($this->validPackageData(['slug'=>$slug1, 'full_description'=>$emptyHtml]), $this->adminId);
        $this->assertNull((new PackageModel())->where('slug',$slug1)->first()['full_description']);

        // Create with meaningful -> stored
        $slug2 = 'same-norm-create2-' . uniqid();
        $r2 = $service->createPackage($this->validPackageData(['slug'=>$slug2, 'full_description'=>$meaningfulHtml]), $this->adminId);
        $this->assertNotNull((new PackageModel())->where('slug',$slug2)->first()['full_description']);

        // Edit the meaningful one with empty -> becomes NULL (same as create)
        $pid = $r2['id'];
        $editEmpty = $this->validPackageData(['slug'=>$slug2, 'full_description'=>$emptyHtml]);
        $re = $service->updatePackage($pid, $editEmpty, $this->adminId);
        $this->assertTrue($re['success']);
        $this->assertNull((new PackageModel())->find($pid)['full_description'], 'Edit empty should normalize same as create');

        // Edit the empty one with meaningful -> becomes stored
        $pid2 = $r1['id'];
        $editMean = $this->validPackageData(['slug'=>$slug1, 'full_description'=>$meaningfulHtml]);
        $re2 = $service->updatePackage($pid2, $editMean, $this->adminId);
        $this->assertTrue($re2['success']);
        $this->assertNotNull((new PackageModel())->find($pid2)['full_description']);
    }
}

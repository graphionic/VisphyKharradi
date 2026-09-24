<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\PackageModel;
use App\Models\PackageFeatureModel;
use App\Models\PackageGalleryImageModel;
use App\Services\PackageService;
use App\Services\HtmlSanitizerService;

/**
 * Phase 5D — Package Media, Rich Content & Lifecycle
 */
final class AdminPackageMediaTest extends CIUnitTestCase
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
        $this->cleanUploads();
        // Create admin
        $this->adminEmail = 'media-' . uniqid() . '@example.com';
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
        $this->cleanUploads();
        parent::tearDown();
    }

    private function cleanUploads(): void
    {
        $dir = FCPATH . 'uploads/packages';
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $file) {
                if ($file->isDir()) @rmdir($file->getPathname());
                else @unlink($file->getPathname());
            }
        }
        @mkdir(FCPATH . 'uploads/packages', 0755, true);
        $ht = FCPATH . 'uploads/packages/.htaccess';
        $hardened = <<<'HT'
# 5E.2A SECURITY: prevent PHP/script execution inside uploads/packages
# Compatible with mod_php (Apache) and CGI/FastCGI (no php_flag crash)
Options -Indexes -ExecCGI

RemoveHandler .php .phtml .php3 .php4 .php5 .phar .pl .py .pyc .pyo .cgi .sh
RemoveType .php .phtml .php3 .php4 .php5 .phar .pl .py .pyc .pyo

<FilesMatch "\.(php|phtml|phar|php3|php4|php5|pl|py|cgi|sh|exe)$">
  Require all denied
</FilesMatch>
<FilesMatch "\.(php|phtml|phar)$">
  <IfModule mod_php.c>
    php_flag engine off
  </IfModule>
  <IfModule mod_php7.c>
    php_flag engine off
  </IfModule>
  <IfModule mod_php8.c>
    php_flag engine off
  </IfModule>
  <IfModule mod_php5.c>
    php_flag engine off
  </IfModule>
</FilesMatch>

<Files ".htaccess">
  Require all denied
</Files>

HT;
        @file_put_contents($ht, $hardened);
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

    private function loginAsAdmin(): void
    {
        $this->doLogin($this->adminEmail, $this->adminPass)->assertRedirect();
    }

    private function makeTempImage(string $type = 'jpeg', int $w = 10, int $h = 10): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        @unlink($tmp);
        $tmp .= '.' . $type;
        $im = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($im, 100, 150, 200);
        imagefill($im, 0, 0, $bg);
        if ($type === 'jpeg' || $type === 'jpg') {
            imagejpeg($im, $tmp, 85);
        } elseif ($type === 'png') {
            imagepng($im, $tmp);
        } elseif ($type === 'webp') {
            if (function_exists('imagewebp')) imagewebp($im, $tmp, 85);
            else imagejpeg($im, $tmp, 85);
        } else {
            imagejpeg($im, $tmp, 85);
        }
        imagedestroy($im);
        return $tmp;
    }

    private function makeTempFile(string $content, string $ext = 'txt'): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'file_') . '.' . $ext;
        file_put_contents($tmp, $content);
        return $tmp;
    }

    private function makeUploadedFile(string $tmpPath, string $clientName, string $mime): \CodeIgniter\HTTP\Files\UploadedFile
    {
        $size = filesize($tmpPath);
        return new \CodeIgniter\HTTP\Files\UploadedFile($tmpPath, $clientName, $mime, $size, UPLOAD_ERR_OK);
    }

    private function validPackageData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Media Package ' . uniqid(),
            'slug' => 'media-package-' . uniqid(),
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

    // ==================== MIGRATION & SCHEMA ====================

    public function testMigrationAddsFeaturedAndGallery(): void
    {
        $db = \Config\Database::connect();
        $this->assertTrue($db->fieldExists('featured_image', 'packages'), 'featured_image column should exist');
        $this->assertTrue($db->tableExists('package_gallery_images'), 'gallery table should exist');
        $fkCount = 0;
        try {
            $result = $db->query("SELECT COUNT(*) as cnt FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE='FOREIGN KEY' AND TABLE_NAME IN ('packages','package_gallery_images','package_features')")->getRowArray();
            $fkCount = (int)($result['cnt'] ?? 0);
        } catch (\Throwable $e) {
            $rows = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name IN ('packages','package_gallery_images','package_features')")->getResultArray();
            foreach ($rows as $r) {
                if (stripos($r['sql'] ?? '', 'FOREIGN KEY') !== false) $fkCount++;
            }
        }
        $this->assertEquals(0, $fkCount, 'ZERO FK requirement');
        $fields = $db->getIndexData('package_gallery_images');
        $hasPackageId = false;
        $hasComposite = false;
        foreach ($fields as $idx) {
            $f = $idx->fields;
            if (count($f)===1 && $f[0]==='package_id') $hasPackageId=true;
            if (in_array('package_id',$f,true) && in_array('display_order',$f,true)) $hasComposite=true;
        }
        $this->assertTrue($hasPackageId, 'package_id index');
        $this->assertTrue($hasComposite, 'package_id_display_order index');
    }

    public function testFeaturedValidJpegAcceptedViaService(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $file = $this->makeUploadedFile($tmp, 'photo.jpg', 'image/jpeg');
        $err = $service->validateImageFile($file);
        $this->assertNull($err, 'JPEG should be valid: '.$err);
        @unlink($tmp);
    }

    public function testFeaturedValidPngAccepted(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('png');
        $file = $this->makeUploadedFile($tmp, 'image.png', 'image/png');
        $err = $service->validateImageFile($file);
        $this->assertNull($err, 'PNG should be valid');
        @unlink($tmp);
    }

    public function testFeaturedValidWebpAccepted(): void
    {
        if (!function_exists('imagewebp')) $this->markTestSkipped('webp not supported');
        $service = new PackageService();
        $tmp = $this->makeTempImage('webp');
        $file = $this->makeUploadedFile($tmp, 'photo.webp', 'image/webp');
        $err = $service->validateImageFile($file);
        $this->assertNull($err, 'WEBP should be valid: '.$err);
        @unlink($tmp);
    }

    public function testInvalidMimeSvgRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>', 'svg');
        $file = $this->makeUploadedFile($tmp, 'evil.svg', 'image/svg+xml');
        $err = $service->validateImageFile($file);
        $this->assertNotNull($err, 'SVG should be rejected');
        @unlink($tmp);
    }

    public function testInvalidMimePhpRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('<?php echo "hack"; ?>', 'php');
        $file = $this->makeUploadedFile($tmp, 'shell.php', 'application/x-php');
        $err = $service->validateImageFile($file);
        $this->assertNotNull($err, 'PHP should be rejected');
        @unlink($tmp);
    }

    public function testDoubleExtensionRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $file = $this->makeUploadedFile($tmp, 'photo.jpg.php', 'image/jpeg');
        $err = $service->validateImageFile($file);
        $this->assertNotNull($err, 'Double extension should be rejected');
        @unlink($tmp);
    }

    public function testOversizeRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $file = new \CodeIgniter\HTTP\Files\UploadedFile($tmp, 'big.jpg', 'image/jpeg', 5242881, UPLOAD_ERR_OK);
        $err = $service->validateImageFile($file);
        $this->assertNotNull($err, 'Oversize should be rejected');
        @unlink($tmp);
    }

    public function testGenerateSafeFilenameRandomAndRelative(): void
    {
        $service = new PackageService();
        $name1 = $service->generateSafeFilename('image/jpeg');
        $name2 = $service->generateSafeFilename('image/jpeg');
        $this->assertNotEquals($name1, $name2, 'Random filenames should differ');
        $this->assertStringEndsWith('.jpg', $name1);
        $this->assertStringNotContainsString('/', $name1, 'Filename should not contain path');
        $this->assertStringNotContainsString('..', $name1);
    }

    public function testCreateWithFeaturedAndGalleryStoresFiles(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData();
        $tmpFeat = $this->makeTempImage('jpeg');
        $featFile = $this->makeUploadedFile($tmpFeat, 'featured.jpg', 'image/jpeg');
        $tmpG1 = $this->makeTempImage('png');
        $tmpG2 = $this->makeTempImage('jpeg');
        $g1 = $this->makeUploadedFile($tmpG1, 'g1.png', 'image/png');
        $g2 = $this->makeUploadedFile($tmpG2, 'g2.jpg', 'image/jpeg');

        $result = $service->createPackageWithMedia($data, $this->adminId, $featFile, [$g1, $g2]);
        $this->assertTrue($result['success'], 'Create with media should succeed: '.json_encode($result['errors'] ?? []));
        $pid = $result['id'];
        $pkg = (new PackageModel())->find($pid);
        $this->assertNotEmpty($pkg['featured_image'], 'featured_image should be set');
        $this->assertStringStartsWith('uploads/packages/'.$pid.'/', $pkg['featured_image'], 'Relative path only');
        $this->assertStringNotContainsString('//', $pkg['featured_image']);
        $this->assertFileExists(FCPATH . $pkg['featured_image'], 'Featured file should exist on disk');
        $this->assertFileExists(FCPATH . 'uploads/packages/.htaccess');

        $gallery = (new PackageGalleryImageModel())->where('package_id', $pid)->orderBy('display_order','ASC')->findAll();
        $this->assertCount(2, $gallery, 'Two gallery images should be stored');
        foreach ($gallery as $idx=>$g) {
            $this->assertEquals($idx+1, (int)$g['display_order'], 'display_order 1..n');
            $this->assertStringStartsWith('uploads/packages/'.$pid.'/', $g['image_path']);
            $this->assertFileExists(FCPATH . $g['image_path']);
        }
        $this->assertStringNotContainsString(FCPATH, $pkg['featured_image']);

        @unlink($tmpFeat); @unlink($tmpG1); @unlink($tmpG2);
    }

    public function testGalleryMaxTenEnforced(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'max10-'.uniqid()]);
        $files = [];
        $tmps = [];
        for ($i=0;$i<11;$i++) {
            $tmp = $this->makeTempImage('jpeg');
            $tmps[] = $tmp;
            $files[] = $this->makeUploadedFile($tmp, "g{$i}.jpg", 'image/jpeg');
        }
        $result = $service->createPackageWithMedia($data, $this->adminId, null, $files);
        $this->assertFalse($result['success'], 'Should reject >10 gallery');
        $this->assertArrayHasKey('gallery_images', $result['errors']);
        foreach ($tmps as $t) @unlink($t);
    }

    public function testCreateTransactionCompensatingCleanupOnDbFailure(): void
    {
        $service = new PackageService();
        $tmpFeat = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmpFeat, 'feat.jpg', 'image/jpeg');
        $data = $this->validPackageData(['slug'=>'cleanup-'.uniqid()]);
        PackageService::$simulateFeatureFailure = true;
        $result = $service->createPackageWithMedia($data, $this->adminId, $feat, []);
        PackageService::$simulateFeatureFailure = false;
        $this->assertFalse($result['success'], 'Should fail due to simulated failure');
        $pkg = (new PackageModel())->where('slug', $data['slug'])->first();
        $this->assertNull($pkg, 'Package should not exist after rollback');
        $dir = FCPATH . 'uploads/packages';
        $files = [];
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && $f->getFilename() !== '.htaccess') $files[] = $f->getPathname();
            }
        }
        $this->assertCount(0, $files, 'New files should be cleaned on DB failure');
        @unlink($tmpFeat);
    }

    public function testRichTextAllowedTagsSurvive(): void
    {
        $html = '<p>Hello <strong>bold</strong> <em>italic</em> <u>underline</u></p><h2>Title</h2><h3>Sub</h3><ul><li>One</li><li>Two</li></ul><ol><li>Num</li></ol><blockquote>Quote</blockquote><a href="https://example.com" target="_blank" rel="noopener">Link</a>';
        $clean = HtmlSanitizerService::sanitize($html);
        $this->assertStringContainsString('<strong>bold</strong>', $clean);
        $this->assertStringContainsString('<em>italic</em>', $clean);
        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<ul>', $clean);
        $this->assertStringContainsString('<blockquote>', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
    }

    public function testRichTextDangerousRemoved(): void
    {
        $cases = [
            '<p>Hi</p><script>alert(1)</script>' => '<script>',
            '<iframe src="https://evil.com"></iframe>' => '<iframe',
            '<p onclick="alert(1)">click</p>' => 'onclick',
            '<a href="javascript:alert(1)">x</a>' => 'javascript:',
            '<a href="data:text/html,alert(1)">x</a>' => 'data:',
            '<style>body{}</style>' => '<style>',
            '<object></object>' => '<object',
            '<form><input></form>' => '<form',
        ];
        foreach ($cases as $input => $needle) {
            $clean = HtmlSanitizerService::sanitize($input);
            $this->assertStringNotContainsString($needle, $clean, "Should remove {$needle}");
        }
    }

    public function testRichTextStoredSanitizedNeverExecutes(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData([
            'slug' => 'rich-'.uniqid(),
            'full_description' => '<p>Safe</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a>',
        ]);
        $result = $service->createPackage($data, $this->adminId);
        $this->assertTrue($result['success']);
        $pkg = (new PackageModel())->find($result['id']);
        $this->assertStringNotContainsString('<script>', $pkg['full_description']);
        $this->assertStringNotContainsString('javascript:', $pkg['full_description']);
        $this->assertStringContainsString('<p>Safe</p>', $pkg['full_description']);
    }

    public function testFullDescriptionPersistsAndEscapedOnError(): void
    {
        $this->loginAsAdmin();
        $resp = $this->call('GET', '/admin/packages/create');
        $csrf = $this->csrf();
        $data = $this->validPackageData([
            'name' => '',
            'slug' => 'err-'.uniqid(),
            'full_description' => '<script>alert(1)</script><p>Hello</p>',
        ]);
        $post = $this->call('POST', '/admin/packages', array_merge($data, $csrf));
        $body = $post->getBody();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $body, 'Raw script should not appear');
    }

    public function testUpdateReplaceFeaturedOldOnlyAfterCommit(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'upd-feat-'.uniqid()]);
        $tmp1 = $this->makeTempImage('jpeg');
        $feat1 = $this->makeUploadedFile($tmp1, 'feat1.jpg', 'image/jpeg');
        $res = $service->createPackageWithMedia($data, $this->adminId, $feat1, []);
        $this->assertTrue($res['success']);
        $pid = $res['id'];
        $pkgBefore = (new PackageModel())->find($pid);
        $oldPath = $pkgBefore['featured_image'];
        $this->assertFileExists(FCPATH . $oldPath);

        $tmp2 = $this->makeTempImage('png');
        $feat2 = $this->makeUploadedFile($tmp2, 'feat2.png', 'image/png');
        $updateData = $this->validPackageData(['name'=>'Updated', 'slug'=>$pkgBefore['slug'], 'display_order'=>$pkgBefore['display_order']]);
        $result = $service->updatePackageWithMedia($pid, $updateData, $this->adminId, $feat2, false, [], [], [], []);
        $this->assertTrue($result['success']);
        $pkgAfter = (new PackageModel())->find($pid);
        $newPath = $pkgAfter['featured_image'];
        $this->assertNotEquals($oldPath, $newPath);
        $this->assertFileExists(FCPATH . $newPath);
        $this->assertFileDoesNotExist(FCPATH . $oldPath, 'Old file should be deleted after commit');
        @unlink($tmp1); @unlink($tmp2);
    }

    public function testUpdateAddGalleryAndReorder(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'gallery-reorder-'.uniqid()]);
        $tmp1 = $this->makeTempImage('jpeg');
        $tmp2 = $this->makeTempImage('png');
        $g1 = $this->makeUploadedFile($tmp1, 'g1.jpg', 'image/jpeg');
        $g2 = $this->makeUploadedFile($tmp2, 'g2.png', 'image/png');
        $res = $service->createPackageWithMedia($data, $this->adminId, null, [$g1, $g2]);
        $this->assertTrue($res['success']);
        $pid = $res['id'];
        $galleryBefore = (new PackageGalleryImageModel())->where('package_id',$pid)->orderBy('display_order','ASC')->findAll();
        $this->assertCount(2, $galleryBefore);
        $ids = array_column($galleryBefore, 'id');
        $reversed = array_reverse($ids);
        $updateData = $this->validPackageData(['name'=>'Reordered', 'slug'=> (new PackageModel())->find($pid)['slug']]);
        $result = $service->updatePackageWithMedia($pid, $updateData, $this->adminId, null, false, [], $reversed, [], []);
        $this->assertTrue($result['success']);
        $galleryAfter = (new PackageGalleryImageModel())->where('package_id',$pid)->orderBy('display_order','ASC')->findAll();
        $afterIds = array_column($galleryAfter, 'id');
        $this->assertEquals($reversed, $afterIds, 'Gallery order should be reversed');
        foreach ($galleryAfter as $idx=>$g) {
            $this->assertEquals($idx+1, (int)$g['display_order']);
            $this->assertFileExists(FCPATH . $g['image_path']);
        }
        @unlink($tmp1); @unlink($tmp2);
    }

    public function testUpdateRemoveGallery(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'gallery-remove-'.uniqid()]);
        $tmp1 = $this->makeTempImage('jpeg');
        $tmp2 = $this->makeTempImage('jpeg');
        $g1 = $this->makeUploadedFile($tmp1, 'a.jpg', 'image/jpeg');
        $g2 = $this->makeUploadedFile($tmp2, 'b.jpg', 'image/jpeg');
        $res = $service->createPackageWithMedia($data, $this->adminId, null, [$g1, $g2]);
        $pid = $res['id'];
        $gallery = (new PackageGalleryImageModel())->where('package_id',$pid)->findAll();
        $ids = array_column($gallery, 'id');
        $paths = array_column($gallery, 'image_path');
        $updateData = $this->validPackageData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $result = $service->updatePackageWithMedia($pid, $updateData, $this->adminId, null, false, [], [$ids[0]], [], []);
        $this->assertTrue($result['success']);
        $remaining = (new PackageGalleryImageModel())->where('package_id',$pid)->findAll();
        $this->assertCount(1, $remaining);
        $this->assertEquals($ids[0], (int)$remaining[0]['id']);
        $this->assertFileDoesNotExist(FCPATH . $paths[1]);
        $this->assertFileExists(FCPATH . $paths[0]);
        @unlink($tmp1); @unlink($tmp2);
    }

    public function testUpdateNewFilesCleanedOnFailure(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'fail-new-'.uniqid()]);
        $tmpOrig = $this->makeTempImage('jpeg');
        $orig = $this->makeUploadedFile($tmpOrig, 'orig.jpg', 'image/jpeg');
        $res = $service->createPackageWithMedia($data, $this->adminId, $orig, []);
        $pid = $res['id'];
        $pkgBefore = (new PackageModel())->find($pid);
        $oldPath = $pkgBefore['featured_image'];
        $this->assertFileExists(FCPATH . $oldPath);

        $tmpNew = $this->makeTempImage('png');
        $newFeat = $this->makeUploadedFile($tmpNew, 'new.png', 'image/png');
        $updateData = $this->validPackageData(['slug'=>$pkgBefore['slug']]);
        PackageService::$simulateFeatureFailure = true;
        $result = $service->updatePackageWithMedia($pid, $updateData, $this->adminId, $newFeat, false, [], [], [], []);
        PackageService::$simulateFeatureFailure = false;
        $this->assertFalse($result['success']);
        $pkgAfter = (new PackageModel())->find($pid);
        $this->assertEquals($oldPath, $pkgAfter['featured_image'], 'Old should remain after failure');
        $this->assertFileExists(FCPATH . $oldPath, 'Old file should still exist');
        $dir = FCPATH . 'uploads/packages/' . $pid;
        $files = is_dir($dir) ? scandir($dir) : [];
        $countFiles = count(array_filter($files, fn($f)=> $f !== '.' && $f !== '..' && $f !== '.htaccess'));
        $this->assertEquals(1, $countFiles, 'New file should be cleaned, only old remains');
        @unlink($tmpOrig); @unlink($tmpNew);
    }

    public function testSoftDeleteOnly(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'softdel-'.uniqid()]);
        $tmp = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmp, 'feat.jpg', 'image/jpeg');
        $tmpG = $this->makeTempImage('png');
        $g = $this->makeUploadedFile($tmpG, 'g.png', 'image/png');
        $res = $service->createPackageWithMedia($data, $this->adminId, $feat, [$g]);
        $pid = $res['id'];
        $featPath = (new PackageModel())->find($pid)['featured_image'];
        $gallery = (new PackageGalleryImageModel())->where('package_id',$pid)->findAll();
        $galleryPath = $gallery[0]['image_path'];
        $featureCount = (new PackageFeatureModel())->where('package_id',$pid)->countAllResults();

        $del = $service->deletePackage($pid, $this->adminId);
        $this->assertTrue($del['success']);
        $found = (new PackageModel())->find($pid);
        $this->assertNull($found, 'find should not return soft deleted');
        $deleted = (new PackageModel())->onlyDeleted()->find($pid);
        $this->assertNotNull($deleted, 'onlyDeleted should find it');
        $this->assertEquals(0, (int)$deleted['is_active'], 'is_active should be 0 after delete');
        $this->assertNotNull($deleted['deleted_at'], 'deleted_at should be set');
        $this->assertEquals($featureCount, (new PackageFeatureModel())->where('package_id',$pid)->countAllResults(), 'Features should remain');
        $this->assertCount(1, (new PackageGalleryImageModel())->where('package_id',$pid)->findAll(), 'Gallery rows should remain');
        $this->assertFileExists(FCPATH . $featPath, 'Featured file should be preserved after soft delete');
        $this->assertFileExists(FCPATH . $galleryPath, 'Gallery file preserved');
        @unlink($tmp); @unlink($tmpG);
    }

    public function testDeletedListingExcludesAndIncludes(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'list-del-'.uniqid()]);
        $res = $service->createPackage($data, $this->adminId);
        $pid = $res['id'];
        $listBefore = $service->getAdminPackageList(['q'=>'','status'=>'all','sort'=>'display_order','page'=>1,'perPage'=>100]);
        $idsBefore = array_column($listBefore['packages'], 'id');
        $this->assertTrue(in_array($pid, array_map('intval', $idsBefore), true), 'Should be in active listing before delete');

        $service->deletePackage($pid, $this->adminId);
        $listAfter = $service->getAdminPackageList(['q'=>'','status'=>'all','sort'=>'display_order','page'=>1,'perPage'=>100]);
        $idsAfter = array_column($listAfter['packages'], 'id');
        $this->assertFalse(in_array($pid, array_map('intval', $idsAfter), true), 'Should be excluded after soft delete');

        $deleted = $service->getDeletedPackages();
        $deletedIds = array_column($deleted, 'id');
        $this->assertTrue(in_array($pid, array_map('intval', $deletedIds), true), 'Should be in deleted listing');
    }

    public function testRestoreClearsDeletedAndKeepsInactiveEndOrder(): void
    {
        $service = new PackageService();
        $d1 = $this->validPackageData(['slug'=>'restore-a-'.uniqid(), 'display_order'=>'1']);
        $r1 = $service->createPackage($d1, $this->adminId);
        $d2 = $this->validPackageData(['slug'=>'restore-b-'.uniqid(), 'display_order'=>'2']);
        $r2 = $service->createPackage($d2, $this->adminId);
        $pid2 = $r2['id'];

        $tmp = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmp, 'feat.jpg', 'image/jpeg');
        $service->updatePackageWithMedia($pid2, $this->validPackageData(['slug'=> (new PackageModel())->find($pid2)['slug']]), $this->adminId, $feat, false, [], [], [], []);
        $featPathBefore = (new PackageModel())->find($pid2)['featured_image'];
        $service->deletePackage($pid2, $this->adminId);
        $rest = $service->restorePackage($pid2, $this->adminId);
        $this->assertTrue($rest['success']);
        $restored = (new PackageModel())->find($pid2);
        $this->assertNotNull($restored, 'Should be findable after restore');
        $this->assertNull($restored['deleted_at'], 'deleted_at should be null after restore');
        $this->assertEquals(0, (int)$restored['is_active'], 'is_active should remain 0 after restore');
        $all = $service->getAdminPackageList(['q'=>'','status'=>'all','sort'=>'display_order','page'=>1,'perPage'=>100]);
        $orders = array_column($all['packages'], 'display_order');
        $restoredOrder = (int)$restored['display_order'];
        $this->assertEquals(max($orders), $restoredOrder, 'Restored should be at end');
        $this->assertEquals($featPathBefore, $restored['featured_image'], 'Featured should be preserved');
        $this->assertFileExists(FCPATH . $restored['featured_image']);
        @unlink($tmp);
    }

    public function testHistoricalOrdersImmutableThroughDeleteRestore(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'order-immut-'.uniqid()]);
        $res = $service->createPackage($data, $this->adminId);
        $pid = $res['id'];
        $pkg = (new PackageModel())->find($pid);
        $db = \Config\Database::connect();
        $durationSnap = $pkg['duration_value'] . ' ' . $pkg['duration_unit'];
        $db->table('orders')->insert([
            'order_number' => 'ORD'.uniqid(),
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'customer_phone' => '9999999999',
            'package_id' => $pid,
            'package_name_snapshot' => $pkg['name'],
            'package_slug_snapshot' => $pkg['slug'],
            'package_price_snapshot' => $pkg['selling_price'],
            'package_duration_snapshot' => $durationSnap,
            'currency' => 'INR',
            'subtotal' => $pkg['selling_price'],
            'total_amount' => $pkg['selling_price'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $orderId = $db->insertID();
        $orderBefore = $db->table('orders')->where('id', $orderId)->get()->getRowArray();
        $service->deletePackage($pid, $this->adminId);
        $service->restorePackage($pid, $this->adminId);
        $orderAfter = $db->table('orders')->where('id', $orderId)->get()->getRowArray();
        $this->assertEquals($orderBefore['package_name_snapshot'], $orderAfter['package_name_snapshot'], 'Order snapshot immutable');
        $this->assertEquals($orderBefore['package_slug_snapshot'], $orderAfter['package_slug_snapshot']);
        $this->assertEquals($orderBefore['total_amount'], $orderAfter['total_amount']);
    }

    public function testToggleActiveAndFeatured(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'toggle-'.uniqid(), 'is_active'=>'1', 'is_featured'=>'0']);
        $res = $service->createPackage($data, $this->adminId);
        $pid = $res['id'];
        $pkg = (new PackageModel())->find($pid);
        $this->assertEquals(1, (int)$pkg['is_active']);
        $this->assertEquals(0, (int)$pkg['is_featured']);
        $ta = $service->toggleActive($pid, $this->adminId);
        $this->assertTrue($ta['success']);
        $this->assertEquals(0, $ta['is_active']);
        $pkg2 = (new PackageModel())->find($pid);
        $this->assertEquals(0, (int)$pkg2['is_active']);
        $tf = $service->toggleFeatured($pid, $this->adminId);
        $this->assertTrue($tf['success']);
        $this->assertEquals(1, $tf['is_featured']);
        $pkg3 = (new PackageModel())->find($pid);
        $this->assertEquals(1, (int)$pkg3['is_featured']);
        $this->assertEquals(1, (int)$pkg3['is_featured']);
    }

    public function testReorderTransactional(): void
    {
        $service = new PackageService();
        $p1 = $service->createPackage($this->validPackageData(['slug'=>'reorder1-'.uniqid(), 'display_order'=>'10']), $this->adminId)['id'];
        $p2 = $service->createPackage($this->validPackageData(['slug'=>'reorder2-'.uniqid(), 'display_order'=>'20']), $this->adminId)['id'];
        $p3 = $service->createPackage($this->validPackageData(['slug'=>'reorder3-'.uniqid(), 'display_order'=>'30']), $this->adminId)['id'];
        $all = $service->getAdminPackageList(['q'=>'','status'=>'all','sort'=>'display_order','page'=>1,'perPage'=>100]);
        $ids = array_column($all['packages'], 'id');
        $reversed = array_reverse($ids);
        $res = $service->reorderPackages($reversed, $this->adminId);
        $this->assertTrue($res['success'], 'Reorder should succeed: '.json_encode($res['errors'] ?? []));
        $allAfter = $service->getAdminPackageList(['q'=>'','status'=>'all','sort'=>'display_order','page'=>1,'perPage'=>100]);
        $idsAfter = array_column($allAfter['packages'], 'id');
        $this->assertEquals($reversed, $idsAfter, 'Order should be reversed');
        $orders = array_column($allAfter['packages'], 'display_order');
        foreach ($orders as $idx=>$o) {
            $this->assertEquals($idx+1, (int)$o, 'display_order should be 1..n');
        }
    }

    public function testReorderRejectsIncomplete(): void
    {
        $service = new PackageService();
        // Ensure at least 2 packages exist
        $service->createPackage($this->validPackageData(['slug'=>'reorder-inc1-'.uniqid()]), $this->adminId);
        $service->createPackage($this->validPackageData(['slug'=>'reorder-inc2-'.uniqid()]), $this->adminId);
        $all = $service->getAdminPackageList(['q'=>'','status'=>'all','sort'=>'display_order','page'=>1,'perPage'=>100]);
        $ids = array_column($all['packages'], 'id');
        $this->assertGreaterThanOrEqual(2, count($ids), 'Need at least 2 packages');
        $incomplete = array_slice($ids, 0, count($ids)-1);
        $res = $service->reorderPackages($incomplete, $this->adminId);
        $this->assertFalse($res['success']);
        $this->assertArrayHasKey('order', $res['errors']);
    }

    public function testControllerCsrfAndAuthForOps(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'csrf-'.uniqid()]);
        $res = $service->createPackage($data, $this->adminId);
        $pid = $res['id'];
        // Authenticated without CSRF should be rejected
        $this->loginAsAdmin();
        $session = $_SESSION;
        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);
        $this->withSession($session)->call('POST', '/admin/packages/'.$pid.'/toggle-active', []);
    }

    public function testUnauthenticatedOpsRedirect(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'unauth-'.uniqid()]);
        $res = $service->createPackage($data, $this->adminId);
        $pid = $res['id'];
        // GET should redirect to login when unauthenticated (CSRF not involved)
        $resp = $this->call('GET', '/admin/packages');
        $resp->assertRedirect();
    }

    public function testListingThumbnailAndDeletedPageViaHttp(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmp, 'thumb.jpg', 'image/jpeg');
        $data = $this->validPackageData(['slug'=>'thumb-list-'.uniqid()]);
        $res = $service->createPackageWithMedia($data, $this->adminId, $feat, []);
        $pid = $res['id'];
        $pkg = (new PackageModel())->find($pid);
        $featPath = $pkg['featured_image'];

        $this->loginAsAdmin();
        $session = $_SESSION;
        $resp = $this->withSession($session)->call('GET', '/admin/packages');
        $resp->assertStatus(200);
        $body = $resp->getBody();
        $this->assertStringContainsString($featPath, $body, 'Listing should contain thumbnail path');
        $data2 = $this->validPackageData(['slug'=>'no-thumb-'.uniqid()]);
        $service->createPackage($data2, $this->adminId);
        $resp2 = $this->withSession($session)->call('GET', '/admin/packages');
        $resp2->assertStatus(200);
        $body2 = $resp2->getBody();
        $this->assertStringContainsString($data2['name'], $body2);
        $service->deletePackage($pid, $this->adminId);
        $respDel = $this->withSession($session)->call('GET', '/admin/packages/deleted');
        $respDel->assertStatus(200);
        $bodyDel = $respDel->getBody();
        $this->assertStringContainsString($data['name'], $bodyDel, 'Deleted page should contain deleted package');
        $this->assertStringContainsString('Restore', $bodyDel);
        $this->assertStringNotContainsString('/admin/packages/'.$pid.'/edit', $bodyDel, 'Deleted page should not have Edit');
        @unlink($tmp);
    }

    public function testAuditForOps(): void
    {
        $service = new PackageService();
        $data = $this->validPackageData(['slug'=>'audit-'.uniqid()]);
        $res = $service->createPackage($data, $this->adminId);
        $pid = $res['id'];
        $service->toggleActive($pid, $this->adminId);
        $service->toggleFeatured($pid, $this->adminId);
        $service->deletePackage($pid, $this->adminId);
        $service->restorePackage($pid, $this->adminId);
        $db = \Config\Database::connect();
        $logs = $db->table('admin_activity_logs')->where('entity_type','package')->where('entity_id',$pid)->get()->getResultArray();
        $actions = array_column($logs, 'action');
        $this->assertTrue(in_array('package.activated', $actions) || in_array('package.deactivated', $actions));
        $this->assertTrue(in_array('package.featured', $actions) || in_array('package.unfeatured', $actions));
        $this->assertTrue(in_array('package.deleted', $actions) || in_array('package.archived', $actions), 'Delete audit should exist');
        $this->assertContains('package.restored', $actions);
        $all = $service->getAdminPackageList(['q'=>'','status'=>'all','sort'=>'display_order','page'=>1,'perPage'=>100]);
        $ids = array_column($all['packages'], 'id');
        $service->reorderPackages($ids, $this->adminId);
        $reorderLogs = $db->table('admin_activity_logs')->where('action','packages.reordered')->get()->getResultArray();
        $this->assertNotEmpty($reorderLogs, 'Reorder should be audited');
    }
}

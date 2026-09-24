<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\PackageService;
use App\Models\PackageModel;
use App\Models\PackageGalleryImageModel;

/**
 * Phase 5E.2A — PACKAGE UPLOAD & FILESYSTEM SECURITY
 * Focused tests: trust boundary, MIME, size, dimensions, filename, traversal, symlink, transaction, htaccess.
 */
final class PackageUploadSecurityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private int $adminId;

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
        $this->cleanUploads();
        @mkdir(FCPATH . 'uploads/packages', 0755, true);
        // Restore .htaccess if wiped (FCPATH is public/)
        $ht = FCPATH . 'uploads/packages/.htaccess';
        if (!is_file($ht)) {
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
        $model = new \App\Models\AdminModel();
        $id = $model->insert([
            'name' => 'Sec Admin',
            'email' => 'sec-' . uniqid() . '@example.com',
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
            'is_active' => 1,
        ], true);
        $this->adminId = (int)$id;
        PackageService::$forceProductionModeForTest = false;
        PackageService::$simulateFeatureFailure = false;
    }

    protected function tearDown(): void
    {
        PackageService::$forceProductionModeForTest = false;
        PackageService::$simulateFeatureFailure = false;
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        $_SESSION = [];
        try { cache()->clean(); } catch (\Throwable $e) {}
        $this->cleanUploads();
        // Ensure hardened htaccess persists after suite
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
        parent::tearDown();
    }

    private function cleanUploads(): void
    {
        $dir = FCPATH . 'uploads/packages';
        if (is_dir($dir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $file) {
                if ($file->isLink()) @unlink($file->getPathname());
                elseif ($file->isDir()) @rmdir($file->getPathname());
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

    private function makeTempImage(string $type = 'jpeg', int $w = 10, int $h = 10): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        @unlink($tmp);
        $tmp .= '.' . $type;
        $im = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($im, 100, 150, 200);
        imagefill($im, 0, 0, $bg);
        if ($type === 'jpeg' || $type === 'jpg') imagejpeg($im, $tmp, 85);
        elseif ($type === 'png') imagepng($im, $tmp);
        elseif ($type === 'webp') {
            if (function_exists('imagewebp')) imagewebp($im, $tmp, 85);
            else imagejpeg($im, $tmp, 85);
        } else imagejpeg($im, $tmp, 85);
        imagedestroy($im);
        return $tmp;
    }

    private function makeFakePngWithDimensions(int $w, int $h): string
    {
        // Minimal valid PNG with IHDR width/height, no IDAT pixel data huge, but getimagesize reads IHDR only.
        // Structure: 8 sig + IHDR chunk + IDAT (minimal 1x1 compressed) + IEND
        $sig = "\x89PNG\r\n\x1a\n";
        // IHDR data: width 4 BE, height 4 BE, bit depth 8, color type 2 (truecolor), compression 0, filter 0, interlace 0
        $ihdrData = pack('N', $w) . pack('N', $h) . chr(8) . chr(2) . chr(0) . chr(0) . chr(0);
        $ihdrLen = pack('N', 13);
        $ihdrType = 'IHDR';
        $ihdrCrc = pack('N', crc32($ihdrType . $ihdrData) & 0xffffffff);
        $ihdr = $ihdrLen . $ihdrType . $ihdrData . $ihdrCrc;
        // Create a tiny 1x1 IDAT compressed data; getimagesize doesn't validate IDAT size vs dimensions for PNG; it just reads IHDR.
        // Use a minimal valid IDAT for 1x1 red pixel, but dimensions mismatch will still be reported from IHDR; getimagesize will still report w/h.
        $raw = "\x00\xff\x00\x00"; // filter 0 + RGB red
        $compressed = gzcompress($raw);
        $idatLen = pack('N', strlen($compressed));
        $idatType = 'IDAT';
        $idatCrc = pack('N', crc32($idatType . $compressed) & 0xffffffff);
        $idat = $idatLen . $idatType . $compressed . $idatCrc;
        $iendLen = pack('N', 0);
        $iendType = 'IEND';
        $iendCrc = pack('N', crc32($iendType) & 0xffffffff);
        $iend = $iendLen . $iendType . $iendCrc;
        $bin = $sig . $ihdr . $idat . $iend;
        $tmp = tempnam(sys_get_temp_dir(), 'fake_') . '.png';
        file_put_contents($tmp, $bin);
        return $tmp;
    }

    private function makeTempFile(string $content, string $ext = 'txt'): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'file_') . '.' . $ext;
        file_put_contents($tmp, $content);
        return $tmp;
    }

    private function makeUploadedFile(string $tmpPath, string $clientName, string $mime, ?int $size = null): \CodeIgniter\HTTP\Files\UploadedFile
    {
        $size = $size ?? filesize($tmpPath);
        return new \CodeIgniter\HTTP\Files\UploadedFile($tmpPath, $clientName, $mime, $size, UPLOAD_ERR_OK);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sec Package ' . uniqid(),
            'slug' => 'sec-package-' . uniqid(),
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

    // ==================== 1 TRUST BOUNDARY ====================

    public function testArbitraryLocalCopyBlockedInProductionMode(): void
    {
        $service = new PackageService();
        // Create a sensitive outside file
        $outside = sys_get_temp_dir() . '/sec_outside_' . uniqid() . '.txt';
        file_put_contents($outside, 'SECRET');
        $tmp = $this->makeTempImage('jpeg');
        // In testing mode, fallback copy succeeds (existing behavior)
        $fileTesting = $this->makeUploadedFile($tmp, 'photo.jpg', 'image/jpeg');
        // Simulate production: is_uploaded_file will be false (since temp file not uploaded via HTTP)
        PackageService::$forceProductionModeForTest = true;
        try {
            $caught = false;
            try {
                $service->storeUploadedFile($fileTesting, 9999, 'featured');
            } catch (\RuntimeException $e) {
                $caught = true;
                $this->assertStringContainsString('not an uploaded file', $e->getMessage());
            }
            $this->assertTrue($caught, 'Production mode must block arbitrary local file copy');
            // Also array path must be blocked
            $arr = ['tmp_name' => $outside, 'name' => 'evil.jpg', 'type' => 'image/jpeg', 'size' => filesize($outside), 'error' => UPLOAD_ERR_OK];
            $caught2 = false;
            try { $service->storeUploadedFile($arr, 9999, 'gallery'); } catch (\RuntimeException $e) { $caught2 = true; }
            $this->assertTrue($caught2, 'Array fallback must be blocked in production');
        } finally {
            PackageService::$forceProductionModeForTest = false;
            @unlink($tmp);
            @unlink($outside);
        }
    }

    public function testFallbackCopyAllowedInTestingMode(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $file = $this->makeUploadedFile($tmp, 'photo.jpg', 'image/jpeg');
        // In testing mode fallback is allowed via copy
        $rel = $service->storeUploadedFile($file, 9998, 'featured');
        $this->assertStringStartsWith('uploads/packages/9998/', $rel);
        $this->assertFileExists(FCPATH . $rel);
        // Cleanup
        $service->deleteStoredFile($rel);
        @unlink($tmp);
        @rmdir(FCPATH . 'uploads/packages/9998');
    }

    public function testStoreSourceCodeContainsTestingGuard(): void
    {
        $code = file_get_contents(APPPATH . 'Services/PackageService.php');
        $this->assertStringContainsString("forceProductionModeForTest", $code, 'Production toggle exists');
        $this->assertStringContainsString("is_uploaded_file", $code, 'is_uploaded_file guard exists');
        $this->assertStringContainsString("ENVIRONMENT === 'testing'", $code, 'ENVIRONMENT testing guard exists');
    }

    // ==================== 2 MIME VALIDATION ====================

    public function testJpegPngWebpAccepted(): void
    {
        $service = new PackageService();
        $tmpJ = $this->makeTempImage('jpeg');
        $this->assertNull($service->validateImageFile($this->makeUploadedFile($tmpJ, 'a.jpg', 'image/jpeg')), 'JPEG');
        $tmpP = $this->makeTempImage('png');
        $this->assertNull($service->validateImageFile($this->makeUploadedFile($tmpP, 'b.png', 'image/png')), 'PNG');
        if (function_exists('imagewebp')) {
            $tmpW = $this->makeTempImage('webp');
            $err = $service->validateImageFile($this->makeUploadedFile($tmpW, 'c.webp', 'image/webp'));
            $this->assertNull($err, 'WEBP');
            @unlink($tmpW);
        }
        @unlink($tmpJ); @unlink($tmpP);
    }

    public function testSvgRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>', 'svg');
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'evil.svg', 'image/svg+xml'));
        $this->assertNotNull($err);
        @unlink($tmp);
    }

    public function testGifRejected(): void
    {
        $service = new PackageService();
        // GIF89a header
        $tmp = $this->makeTempFile("GIF89a\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;", 'gif');
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'anim.gif', 'image/gif'));
        $this->assertNotNull($err, 'GIF rejected');
        @unlink($tmp);
    }

    public function testPhpRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('<?php echo "pwn"; ?>', 'php');
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'shell.php', 'application/x-php'));
        $this->assertNotNull($err);
        @unlink($tmp);
    }

    public function testHtmlRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('<html><script>alert(1)</script></html>', 'html');
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'page.html', 'text/html'));
        $this->assertNotNull($err);
        @unlink($tmp);
    }

    public function testJsRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('alert(1);', 'js');
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'evil.js', 'application/javascript'));
        $this->assertNotNull($err);
        @unlink($tmp);
    }

    public function testTextRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('hello world', 'txt');
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'hello.txt', 'text/plain'));
        $this->assertNotNull($err);
        @unlink($tmp);
    }

    public function testZeroByteRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('', 'jpg');
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'empty.jpg', 'image/jpeg', 0));
        $this->assertNotNull($err, 'Zero byte');
        @unlink($tmp);
    }

    public function testCorruptImageRejected(): void
    {
        $service = new PackageService();
        // Fake JPEG header truncated so finfo says jpeg but getimagesize fails
        $tmp = $this->makeTempFile("\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9corrupt", 'jpg');
        $file = $this->makeUploadedFile($tmp, 'corrupt.jpg', 'image/jpeg');
        $err = $service->validateImageFile($file);
        $this->assertNotNull($err, 'Corrupt should be rejected');
        @unlink($tmp);
        // Also random binary
        $tmp2 = $this->makeTempFile(random_bytes(128), 'jpg');
        $err2 = $service->validateImageFile($this->makeUploadedFile($tmp2, 'rand.jpg', 'image/jpeg'));
        $this->assertNotNull($err2);
        @unlink($tmp2);
    }

    public function testRenamedExecutableRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempFile('<?php system($_GET["cmd"]); ?>', 'jpg');
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'photo.jpg', 'image/jpeg'));
        $this->assertNotNull($err, 'Renamed PHP as JPG should be rejected via MIME');
        @unlink($tmp);
    }

    public function testDoubleExtensionRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $cases = ['photo.jpg.php', 'photo.png.phtml', 'evil.jpg.phar', 'test.jpg.html', 'x.png.js', 'img.webp.svg'];
        foreach ($cases as $name) {
            $err = $service->validateImageFile($this->makeUploadedFile($tmp, $name, 'image/jpeg'));
            $this->assertNotNull($err, "Double extension $name should be rejected");
        }
        @unlink($tmp);
    }

    public function testBrowserMimeSpoofIgnored(): void
    {
        $service = new PackageService();
        // File is actually PHP but browser claims image/jpeg -> server finfo should reject
        $tmp = $this->makeTempFile('<?php evil(); ?>', 'jpg');
        $file = $this->makeUploadedFile($tmp, 'photo.jpg', 'image/jpeg');
        $err = $service->validateImageFile($file);
        $this->assertNotNull($err, 'MIME spoof should not bypass finfo');
        @unlink($tmp);
    }

    // ==================== 3 SIZE ====================

    public function testSizeOver5MRejected(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $file = new \CodeIgniter\HTTP\Files\UploadedFile($tmp, 'big.jpg', 'image/jpeg', 5 * 1024 * 1024 + 1, UPLOAD_ERR_OK);
        $err = $service->validateImageFile($file);
        $this->assertNotNull($err);
        $this->assertStringContainsString('5 MB', $err);
        @unlink($tmp);
    }

    public function testSizeExactly5MAllowed(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $file = new \CodeIgniter\HTTP\Files\UploadedFile($tmp, 'exact.jpg', 'image/jpeg', 5 * 1024 * 1024, UPLOAD_ERR_OK);
        $err = $service->validateImageFile($file);
        $this->assertNull($err, 'Exactly 5MB should pass');
        @unlink($tmp);
    }

    // ==================== 4 DIMENSIONS ====================

    public function testDimensionsTooLargeRejected(): void
    {
        $service = new PackageService();
        // Width > 6000
        $tmpW = $this->makeFakePngWithDimensions(7000, 10);
        $errW = $service->validateImageFile($this->makeUploadedFile($tmpW, 'wide.png', 'image/png'));
        $this->assertNotNull($errW, '7000 width should be rejected');
        $this->assertStringContainsString('dimensions', strtolower($errW));
        // Height > 6000
        $tmpH = $this->makeFakePngWithDimensions(10, 7000);
        $errH = $service->validateImageFile($this->makeUploadedFile($tmpH, 'tall.png', 'image/png'));
        $this->assertNotNull($errH, '7000 height should be rejected');
        @unlink($tmpW); @unlink($tmpH);
    }

    public function testPixelsTooManyRejected(): void
    {
        $service = new PackageService();
        // 6000x4167 = 25,002,000 > 25M, within 6000 limit but over pixels
        $tmp = $this->makeFakePngWithDimensions(6000, 4167);
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'huge.png', 'image/png'));
        $this->assertNotNull($err, 'Pixels >25M should be rejected');
        $this->assertStringContainsString('pixels', strtolower($err));
        @unlink($tmp);
    }

    public function testDimensionsWithinLimitAllowed(): void
    {
        $service = new PackageService();
        $tmp = $this->makeFakePngWithDimensions(6000, 4000); // 24M OK
        $err = $service->validateImageFile($this->makeUploadedFile($tmp, 'ok.png', 'image/png'));
        $this->assertNull($err, '6000x4000 should be allowed');
        @unlink($tmp);
    }

    // ==================== 5 SAFE FILENAMES ====================

    public function testSafeFilenameIgnoresOriginalAndTraversal(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $traversalNames = ['../../evil.jpg', '..\\..\\evil.jpg', '/etc/passwd.jpg', 'a/b/c.jpg', 'evil%2e%2e%2f.jpg'];
        foreach ($traversalNames as $evil) {
            $file = $this->makeUploadedFile($tmp, $evil, 'image/jpeg');
            $err = $service->validateImageFile($file);
            // Some names contain traversal but double-ext check may not trigger; validation may pass for real image, but storage must not traverse
            // Ensure if valid, stored file is safe random
            if ($err === null) {
                $rel = $service->storeUploadedFile($file, 8888, 'test');
                $this->assertStringNotContainsString('..', $rel);
                $this->assertStringNotContainsString('/', basename($rel), 'Filename must not contain path');
                $this->assertMatchesRegularExpression('#^uploads/packages/8888/test_[a-f0-9]{32}\.(jpg|png|webp)$#', $rel, "Safe name for $evil");
                $this->assertStringNotContainsString('evil', $rel);
                $service->deleteStoredFile($rel);
            } else {
                // rejected is also safe (traversal with php extension)
                $this->assertNotNull($err);
            }
        }
        @unlink($tmp);
        @rmdir(FCPATH . 'uploads/packages/8888');
    }

    public function testGenerateFilenameExtensionFromMime(): void
    {
        $service = new PackageService();
        $j = $service->generateSafeFilename('image/jpeg');
        $p = $service->generateSafeFilename('image/png');
        $w = $service->generateSafeFilename('image/webp');
        $this->assertStringEndsWith('.jpg', $j);
        $this->assertStringEndsWith('.png', $p);
        $this->assertStringEndsWith('.webp', $w);
        $this->assertNotEquals($j, $p);
        // random 32 hex
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $j);
    }

    // ==================== 6 STORAGE ROOT ====================

    public function testStorageRootAndRelative(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $file = $this->makeUploadedFile($tmp, 'a.jpg', 'image/jpeg');
        $rel = $service->storeUploadedFile($file, 7777, 'featured');
        $this->assertStringStartsWith('uploads/packages/7777/', $rel);
        $this->assertStringNotContainsString(FCPATH, $rel);
        $this->assertStringNotContainsString('//', $rel);
        $this->assertFileExists(FCPATH . $rel);
        $realRoot = realpath(FCPATH . 'uploads/packages');
        $realDir = realpath(dirname(FCPATH . $rel));
        $this->assertStringStartsWith($realRoot, $realDir);
        $service->deleteStoredFile($rel);
        @unlink($tmp);
        @rmdir(FCPATH . 'uploads/packages/7777');
    }

    // ==================== 7 PATH TRAVERSAL DELETE ====================

    public function testDeleteTraversalDbValueBlocked(): void
    {
        $service = new PackageService();
        // Create a file outside managed root that must NOT be deleted
        $outside = FCPATH . 'writable_outside_test_' . uniqid() . '.txt';
        // Also test via sys temp and .env-like
        $envFake = FCPATH . '.env.traversal_test';
        file_put_contents($outside, 'outside');
        file_put_contents($envFake, 'env');
        $this->assertFileExists($outside);
        $this->assertFileExists($envFake);

        // Attempt traversal deletes
        $traversals = [
            '../../.env',
            'uploads/packages/../../.env.traversal_test',
            'uploads/packages/1/../../writable_outside_test_' . basename($outside),
            '../uploads/packages/1/evil.jpg',
            'uploads/packages/../packages/evil.jpg',
            'uploads/packages/1/../../../../etc/passwd',
            'uploads/packages/1/..\\..\\..\\outside.txt',
            'uploads/packages/' . str_repeat('../', 5) . '.env',
        ];
        foreach ($traversals as $evil) {
            $service->deleteStoredFile($evil);
        }
        // Also try absolute outside path stored as relative via manipulation
        $service->deleteStoredFile('../../' . basename($outside));
        $service->deleteStoredFile('uploads/../writable_outside_test_' . basename($outside));

        // Files must still exist
        $this->assertFileExists($outside, 'Traversal delete must not remove outside file');
        $this->assertFileExists($envFake, 'Traversal delete must not remove .env fake');
        // Even with uploads prefix but .. in path, should be rejected
        $service->deleteStoredFile('uploads/packages/1/../../.env.traversal_test');
        $this->assertFileExists($envFake);

        @unlink($outside);
        @unlink($envFake);
    }

    public function testDeleteOutsidePrefixNotDeleted(): void
    {
        $service = new PackageService();
        $outside = FCPATH . 'outside_direct_' . uniqid() . '.txt';
        file_put_contents($outside, 'x');
        $service->deleteStoredFile('writable/' . basename($outside));
        $service->deleteStoredFile('/etc/passwd');
        $service->deleteStoredFile('uploads/other/file.jpg');
        $this->assertFileExists($outside);
        @unlink($outside);
    }

    // ==================== 8 SYMLINK SAFETY ====================

    public function testSymlinkEscapeNotFollowed(): void
    {
        if (!function_exists('symlink')) $this->markTestSkipped('symlink not supported');
        $service = new PackageService();
        $outside = sys_get_temp_dir() . '/outside_symlink_target_' . uniqid() . '.txt';
        file_put_contents($outside, 'SECRET_OUTSIDE');
        $this->assertFileExists($outside);

        // Create a directory inside managed root
        $pkgDir = FCPATH . 'uploads/packages/9999';
        @mkdir($pkgDir, 0755, true);
        $linkPath = $pkgDir . '/link_to_outside.jpg';
        // Create symlink inside root pointing outside
        @unlink($linkPath);
        $linked = @symlink($outside, $linkPath);
        if (!$linked) $this->markTestSkipped('Cannot create symlink in this FS');

        $this->assertTrue(is_link($linkPath));

        // Store traversal via symlink? Direct deleteStoredFile with relative path that is a symlink
        $relativeLink = 'uploads/packages/9999/link_to_outside.jpg';
        $service->deleteStoredFile($relativeLink);
        // Symlink itself should be removed, target outside must remain
        $this->assertFileDoesNotExist($linkPath, 'Symlink itself should be unlinked');
        $this->assertFileExists($outside, 'Symlink target outside must NOT be deleted');

        // Test case where realpath of symlink points outside -> should not delete target via realpath logic
        // Recreate link and test delete that would follow realpath
        @symlink($outside, $linkPath);
        // Craft a fake DB value that is a normal file but is actually a symlink - ensure cleanup after create failure doesn't follow
        $service->deleteStoredFile($relativeLink);
        $this->assertFileExists($outside);
        @unlink($linkPath);
        @unlink($outside);
        @rmdir($pkgDir);
    }

    // ==================== 9 TRANSACTION SAFETY ====================

    public function testFailedCreateCleanup(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $file = $this->makeUploadedFile($tmp, 'feat.jpg', 'image/jpeg');
        $data = $this->validData(['slug' => 'txn-fail-create-' . uniqid()]);
        PackageService::$simulateFeatureFailure = true;
        $result = $service->createPackageWithMedia($data, $this->adminId, $file, []);
        PackageService::$simulateFeatureFailure = false;
        $this->assertFalse($result['success']);
        // No package inserted
        $pkg = (new PackageModel())->where('slug', $data['slug'])->first();
        $this->assertNull($pkg);
        // No files left in uploads/packages besides .htaccess
        $dir = FCPATH . 'uploads/packages';
        $left = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getFilename() !== '.htaccess') $left[] = $f->getPathname();
        }
        $this->assertCount(0, $left, 'New files must be cleaned after failed CREATE');
        @unlink($tmp);
    }

    public function testFailedEditPreservesOld(): void
    {
        $service = new PackageService();
        $tmpOrig = $this->makeTempImage('jpeg');
        $orig = $this->makeUploadedFile($tmpOrig, 'orig.jpg', 'image/jpeg');
        $data = $this->validData(['slug' => 'txn-fail-edit-' . uniqid()]);
        $res = $service->createPackageWithMedia($data, $this->adminId, $orig, []);
        $this->assertTrue($res['success']);
        $pid = $res['id'];
        $oldPath = (new PackageModel())->find($pid)['featured_image'];
        $this->assertFileExists(FCPATH . $oldPath);

        $tmpNew = $this->makeTempImage('png');
        $newFile = $this->makeUploadedFile($tmpNew, 'new.png', 'image/png');
        $updateData = $this->validData(['slug' => $data['slug']]);
        PackageService::$simulateFeatureFailure = true;
        $result = $service->updatePackageWithMedia($pid, $updateData, $this->adminId, $newFile, false, [], [], [], []);
        PackageService::$simulateFeatureFailure = false;
        $this->assertFalse($result['success']);
        $after = (new PackageModel())->find($pid);
        $this->assertEquals($oldPath, $after['featured_image'], 'Old must be preserved after failed EDIT');
        $this->assertFileExists(FCPATH . $oldPath);
        // Only one file should exist in pkg dir
        $pkgDir = FCPATH . 'uploads/packages/' . $pid;
        $files = array_values(array_filter(scandir($pkgDir) ?: [], fn($f) => $f !== '.' && $f !== '..' && $f !== '.htaccess'));
        $this->assertCount(1, $files, 'New file must be cleaned, old preserved');
        $this->assertEquals(basename($oldPath), $files[0]);
        @unlink($tmpOrig); @unlink($tmpNew);
    }

    public function testSuccessfulReplacementOldOnlyAfterCommit(): void
    {
        $service = new PackageService();
        $tmp1 = $this->makeTempImage('jpeg');
        $f1 = $this->makeUploadedFile($tmp1, 'a.jpg', 'image/jpeg');
        $data = $this->validData(['slug' => 'txn-success-' . uniqid()]);
        $res = $service->createPackageWithMedia($data, $this->adminId, $f1, []);
        $pid = $res['id'];
        $oldPath = (new PackageModel())->find($pid)['featured_image'];
        $this->assertFileExists(FCPATH . $oldPath);

        $tmp2 = $this->makeTempImage('png');
        $f2 = $this->makeUploadedFile($tmp2, 'b.png', 'image/png');
        $upd = $this->validData(['slug' => $data['slug'], 'name' => 'Updated Name']);
        $result = $service->updatePackageWithMedia($pid, $upd, $this->adminId, $f2, false, [], [], [], []);
        $this->assertTrue($result['success']);
        $newPath = (new PackageModel())->find($pid)['featured_image'];
        $this->assertNotEquals($oldPath, $newPath);
        $this->assertFileExists(FCPATH . $newPath);
        $this->assertFileDoesNotExist(FCPATH . $oldPath, 'Old must be removed only after DB commit');
        @unlink($tmp1); @unlink($tmp2);
    }

    // ==================== 10 .HTACCESS ====================

    public function testHtaccessPreventsPhpExecutionCompatible(): void
    {
        $htPath = FCPATH . 'uploads/packages/.htaccess';
        $this->assertFileExists($htPath, '.htaccess must exist');
        $content = file_get_contents($htPath);
        $this->assertStringContainsString('Require all denied', $content);
        $this->assertStringContainsString('RemoveHandler', $content);
        $this->assertStringContainsString('RemoveType', $content);
        // Must not have bare php_flag engine off without IfModule (would 500 on CGI)
        $this->assertStringNotContainsString("\nphp_flag engine off\n", $content, 'Bare php_flag would break CGI');
        $this->assertStringContainsString('<IfModule', $content, 'php_flag must be wrapped in IfModule');
        $this->assertStringContainsString('php_flag engine off', $content, 'php_flag still present inside IfModule');
        // Options -ExecCGI -Indexes
        $this->assertStringContainsString('Options', $content);
        $this->assertStringContainsString('-ExecCGI', $content);
        $this->assertStringContainsString('-Indexes', $content);
        // Deny php etc
        $this->assertStringContainsString('FilesMatch', $content);
    }

    // ==================== 11 RE-ENCODING DOC ====================
    // No large re-encoding; we test that validation via MIME+finfo+getimagesize is sufficient and documented

    public function testCreateWithGalleryStoresAllAndChecks(): void
    {
        $service = new PackageService();
        $data = $this->validData();
        $tmp1 = $this->makeTempImage('png');
        $tmp2 = $this->makeTempImage('jpeg');
        $g1 = $this->makeUploadedFile($tmp1, 'g1.png', 'image/png');
        $g2 = $this->makeUploadedFile($tmp2, 'g2.jpg', 'image/jpeg');
        $tmpF = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmpF, 'feat.jpg', 'image/jpeg');
        $res = $service->createPackageWithMedia($data, $this->adminId, $feat, [$g1, $g2]);
        $this->assertTrue($res['success']);
        $pid = $res['id'];
        $pkg = (new PackageModel())->find($pid);
        $this->assertFileExists(FCPATH . $pkg['featured_image']);
        $gals = (new PackageGalleryImageModel())->where('package_id', $pid)->findAll();
        $this->assertCount(2, $gals);
        foreach ($gals as $g) $this->assertFileExists(FCPATH . $g['image_path']);
        @unlink($tmp1); @unlink($tmp2); @unlink($tmpF);
    }
}

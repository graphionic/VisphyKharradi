<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Services\PackageService;
use App\Models\PackageModel;
use App\Models\PackageGalleryImageModel;

/**
 * 5E.2B — GALLERY & MEDIA OWNERSHIP INTEGRITY (hardening only)
 */
final class PackageGalleryOwnershipTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

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
        try { service('superglobals')->setGlobalArray('get', []); } catch (\Throwable $e) {}
        try { service('superglobals')->setGlobalArray('post', []); } catch (\Throwable $e) {}
        $this->cleanUploads();
        @mkdir(FCPATH . 'uploads/packages', 0755, true);
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
            'name' => 'Gallery Admin',
            'email' => 'gallery-' . uniqid() . '@example.com',
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT),
            'is_active' => 1,
        ], true);
        $this->adminId = (int)$id;
        PackageService::$simulateFeatureFailure = false;
        PackageService::$forceProductionModeForTest = false;
    }

    protected function tearDown(): void
    {
        PackageService::$simulateFeatureFailure = false;
        PackageService::$forceProductionModeForTest = false;
        $_POST = [];
        $_GET = [];
        $_FILES = [];
        $_SESSION = [];
        try { cache()->clean(); } catch (\Throwable $e) {}
        $this->cleanUploads();
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

    private function makeUploadedFile(string $tmpPath, string $clientName, string $mime): \CodeIgniter\HTTP\Files\UploadedFile
    {
        return new \CodeIgniter\HTTP\Files\UploadedFile($tmpPath, $clientName, $mime, filesize($tmpPath), UPLOAD_ERR_OK);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Gallery Package ' . uniqid(),
            'slug' => 'gallery-pkg-' . uniqid(),
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

    private function createPackageWithGallery(int $count, array $overrides = []): array
    {
        $service = new PackageService();
        $data = $this->validData($overrides);
        $files = [];
        $tmps = [];
        for ($i=0;$i<$count;$i++) {
            $tmp = $this->makeTempImage('jpeg', 10+$i, 10);
            $tmps[] = $tmp;
            $files[] = $this->makeUploadedFile($tmp, "g{$i}.jpg", 'image/jpeg');
        }
        $res = $service->createPackageWithMedia($data, $this->adminId, null, $files);
        $this->assertTrue($res['success'], 'create '.$count.' gallery should succeed: '.json_encode($res['errors'] ?? []));
        foreach ($tmps as $t) @unlink($t);
        $pid = $res['id'];
        $gallery = (new PackageGalleryImageModel())->where('package_id', $pid)->orderBy('display_order','ASC')->findAll();
        return [$pid, $gallery, $data];
    }

    private function login(): void
    {
        $admin = (new \App\Models\AdminModel())->find($this->adminId);
        $csrf = $this->csrf();
        $this->call('POST', '/admin/login', array_merge(['email'=>$admin['email'], 'password'=>'Password123!'], $csrf))->assertRedirect();
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

    // 1. Cross-package keep attack
    public function testCrossPackageGalleryKeepAttack(): void
    {
        [$pidA, $galleryA] = $this->createPackageWithGallery(2, ['slug'=>'a-keep-'.uniqid()]);
        [$pidB, $galleryB] = $this->createPackageWithGallery(1, ['slug'=>'b-keep-'.uniqid()]);
        $idB = (int)$galleryB[0]['id'];
        $idsA = array_column($galleryA, 'id');
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pidA)['slug']]);
        // Attack: include B's ID while editing A
        $maliciousIds = array_merge($idsA, [$idB]);
        $result = $service->updatePackageWithMedia($pidA, $data, $this->adminId, null, false, [], $maliciousIds, [], []);
        $this->assertFalse($result['success'], 'Cross-package keep must be rejected');
        $this->assertArrayHasKey('gallery_images', $result['errors']);
        // B unchanged
        $galleryBAfter = (new PackageGalleryImageModel())->where('package_id', $pidB)->findAll();
        $this->assertCount(1, $galleryBAfter);
        $this->assertEquals($idB, (int)$galleryBAfter[0]['id']);
        // A unchanged (still 2)
        $galleryAAfter = (new PackageGalleryImageModel())->where('package_id', $pidA)->findAll();
        $this->assertCount(2, $galleryAAfter);
        foreach ($galleryAAfter as $g) $this->assertFileExists(FCPATH . $g['image_path']);
    }

    public function testCrossPackageGalleryRemoveAttack(): void
    {
        [$pidA, $galleryA] = $this->createPackageWithGallery(2, ['slug'=>'a-rem-'.uniqid()]);
        [$pidB, $galleryB] = $this->createPackageWithGallery(2, ['slug'=>'b-rem-'.uniqid()]);
        $idB = (int)$galleryB[0]['id'];
        $pathB = $galleryB[0]['image_path'];
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pidA)['slug']]);
        // While editing A, try to delete B's image by submitting B's ID as if it were A's (ownership bypass)
        // Our service validates ownership: B's ID not in A's map => error, so deletion must not happen
        $result = $service->updatePackageWithMedia($pidA, $data, $this->adminId, null, false, [], [$idB], [], []);
        $this->assertFalse($result['success']);
        $this->assertFileExists(FCPATH . $pathB, 'B image must remain');
        $this->assertCount(2, (new PackageGalleryImageModel())->where('package_id', $pidB)->findAll());
    }

    public function testCrossPackageGalleryReorderAttack(): void
    {
        [$pidA, $galleryA] = $this->createPackageWithGallery(2, ['slug'=>'a-reord-'.uniqid()]);
        [$pidB, $galleryB] = $this->createPackageWithGallery(2, ['slug'=>'b-reord-'.uniqid()]);
        $idB = (int)$galleryB[0]['id'];
        $idsA = array_column($galleryA, 'id');
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pidA)['slug']]);
        // Try reorder A with B's ID in order
        $result = $service->updatePackageWithMedia($pidA, $data, $this->adminId, null, false, [], $idsA, [], array_merge([$idB], $idsA));
        $this->assertFalse($result['success'], 'Reorder with cross-package ID must be rejected');
        // Verify no cross mutation
        $galleryBAfter = (new PackageGalleryImageModel())->where('package_id', $pidB)->orderBy('display_order','ASC')->findAll();
        $this->assertCount(2, $galleryBAfter);
        // A order unchanged
        $galleryAAfter = (new PackageGalleryImageModel())->where('package_id', $pidA)->orderBy('display_order','ASC')->findAll();
        $this->assertEquals($idsA, array_column($galleryAAfter, 'id'));
    }

    public function testCrossPackageAltTextAttack(): void
    {
        [$pidA, $galleryA] = $this->createPackageWithGallery(1, ['slug'=>'a-alt-'.uniqid()]);
        [$pidB, $galleryB] = $this->createPackageWithGallery(1, ['slug'=>'b-alt-'.uniqid()]);
        $idB = (int)$galleryB[0]['id'];
        $origAltB = $galleryB[0]['alt_text'];
        $idsA = array_column($galleryA, 'id');
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pidA)['slug']]);
        // Try to update B's alt via A edit by submitting B's ID with alt
        $result = $service->updatePackageWithMedia($pidA, $data, $this->adminId, null, false, [], [$idB], ['hacked alt'], []);
        $this->assertFalse($result['success']);
        $afterB = (new PackageGalleryImageModel())->find($idB);
        $this->assertEquals($origAltB, $afterB['alt_text'], 'B alt must remain unchanged');
    }

    // 2. Client path distrust
    public function testClientControlledPathIgnored(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmp, 'feat.jpg', 'image/jpeg');
        $data = $this->validData(['slug'=>'path-ignore-'.uniqid()]);
        $res = $service->createPackageWithMedia($data, $this->adminId, $feat, []);
        $pid = $res['id'];
        $oldFeat = (new PackageModel())->find($pid)['featured_image'];
        $this->assertFileExists(FCPATH . $oldFeat);
        // Try malicious POST path keys
        $maliciousInput = $this->validData(['slug'=> $data['slug']]);
        $maliciousInput['remove_path'] = '../../.env';
        $maliciousInput['image_path'] = 'uploads/packages/'.$pid.'/evil.jpg';
        $maliciousInput['featured_image'] = '../../.env';
        $maliciousInput['featured_image_path'] = $oldFeat; // try to spoof
        $result = $service->updatePackageWithMedia($pid, $maliciousInput, $this->adminId, null, true, [], [], [], []);
        // removeFeatured=true should use DB path, not submitted path, and succeed
        $this->assertTrue($result['success']);
        $after = (new PackageModel())->find($pid);
        $this->assertNull($after['featured_image'], 'Featured should be removed via intent, not path');
        $this->assertFileDoesNotExist(FCPATH . $oldFeat);
        // Ensure outside .env not touched
        $this->assertFalse(is_file(FCPATH . '../../.env'));
        // Test via controller: submit remove_path via HTTP and ensure ignored
        $this->login();
        $session = $_SESSION;
        $csrf = $this->csrf();
        // Need another package for controller test
        $tmp2 = $this->makeTempImage('jpeg');
        $feat2 = $this->makeUploadedFile($tmp2, 'f2.jpg', 'image/jpeg');
        $data2 = $this->validData(['slug'=>'ctrl-path-'.uniqid()]);
        $res2 = $service->createPackageWithMedia($data2, $this->adminId, $feat2, []);
        $pid2 = $res2['id'];
        $old2 = (new PackageModel())->find($pid2)['featured_image'];
        $post = array_merge($this->validData(['slug'=>$data2['slug']]), $csrf, [
            'remove_featured_image' => '1',
            'remove_path' => '../../.env',
            'image_path' => 'uploads/packages/'.$pid2.'/evil.jpg',
            'featured_image' => $old2, // spoof
        ]);
        // Existing gallery ids must be sent to preserve? For this package with 0 gallery, empty is fine
        $resp = $this->withSession($session)->call('POST', '/admin/packages/'.$pid2, $post);
        $resp->assertRedirect();
        $after2 = (new PackageModel())->find($pid2);
        $this->assertNull($after2['featured_image']);
        $this->assertFileDoesNotExist(FCPATH . $old2);
        @unlink($tmp); @unlink($tmp2);
    }

    // 3. Gallery limit
    public function testGalleryLimit9Plus1Allowed(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(9, ['slug'=>'limit9-'.uniqid()]);
        $ids = array_column($gallery, 'id');
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $tmp = $this->makeTempImage('jpeg');
        $newFile = $this->makeUploadedFile($tmp, 'new.jpg', 'image/jpeg');
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [$newFile], $ids, [], []);
        $this->assertTrue($result['success'], '9+1 should be allowed: '.json_encode($result['errors'] ?? []));
        $after = (new PackageGalleryImageModel())->where('package_id', $pid)->findAll();
        $this->assertCount(10, $after);
        @unlink($tmp);
    }

    public function testGalleryLimit10Plus1Rejected(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(10, ['slug'=>'limit10p1-'.uniqid()]);
        $ids = array_column($gallery, 'id');
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $tmp = $this->makeTempImage('jpeg');
        $newFile = $this->makeUploadedFile($tmp, 'new.jpg', 'image/jpeg');
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [$newFile], $ids, [], []);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('gallery_images', $result['errors']);
        $this->assertCount(10, (new PackageGalleryImageModel())->where('package_id', $pid)->findAll());
        @unlink($tmp);
    }

    public function testGalleryLimit10Minus2Plus2Allowed(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(10, ['slug'=>'10m2p2-'.uniqid()]);
        $ids = array_column($gallery, 'id');
        $keep = array_slice($ids, 0, 8);
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $tmp1 = $this->makeTempImage('jpeg');
        $tmp2 = $this->makeTempImage('png');
        $f1 = $this->makeUploadedFile($tmp1, 'a.jpg', 'image/jpeg');
        $f2 = $this->makeUploadedFile($tmp2, 'b.png', 'image/png');
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [$f1,$f2], $keep, [], []);
        $this->assertTrue($result['success'], '10-2+2 should be allowed: '.json_encode($result['errors'] ?? []));
        $this->assertCount(10, (new PackageGalleryImageModel())->where('package_id', $pid)->findAll());
        @unlink($tmp1); @unlink($tmp2);
    }

    public function testGalleryLimit10Minus1Plus2Rejected(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(10, ['slug'=>'10m1p2-'.uniqid()]);
        $ids = array_column($gallery, 'id');
        $keep = array_slice($ids, 0, 9);
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $tmp1 = $this->makeTempImage('jpeg');
        $tmp2 = $this->makeTempImage('jpeg');
        $f1 = $this->makeUploadedFile($tmp1, 'a.jpg', 'image/jpeg');
        $f2 = $this->makeUploadedFile($tmp2, 'b.jpg', 'image/jpeg');
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [$f1,$f2], $keep, [], []);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('gallery_images', $result['errors']);
        $this->assertCount(10, (new PackageGalleryImageModel())->where('package_id', $pid)->findAll());
        @unlink($tmp1); @unlink($tmp2);
    }

    // 4. Duplicate / malformed
    public function testDuplicateIdsRejected(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(2, ['slug'=>'dup-'.uniqid()]);
        $ids = array_column($gallery, 'id');
        $dup = [$ids[0], $ids[0]];
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [], $dup, [], []);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Duplicate', $result['errors']['gallery_images']);
        $this->assertCount(2, (new PackageGalleryImageModel())->where('package_id', $pid)->findAll());
    }

    public function testMalformedIdsSafe(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(2, ['slug'=>'malf-'.uniqid()]);
        $ids = array_column($gallery, 'id');
        $service = new PackageService();
        $cases = [
            'zero' => [0],
            'minus1' => [-1],
            'abc' => ['abc'],
            'float' => ['1.5'],
            'nested' => [[(string)$ids[0]]],
            'unknown' => [999999],
        ];
        foreach ($cases as $label => $malformed) {
            $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
            try {
                $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [], $malformed, [], []);
            } catch (\Throwable $e) {
                $this->fail("Malformed $label threw exception: ".get_class($e).": ".$e->getMessage());
            }
            // Should be rejected or safely ignored, but never mutate other packages nor warnings
            $this->assertFalse($result['success'], "Malformed $label should be rejected");
            $this->assertArrayHasKey('gallery_images', $result['errors'], "Malformed $label error key");
            // Ensure no SQL error leaked
            $this->assertStringNotContainsString('SQL', json_encode($result['errors']));
            // Gallery unchanged
            $this->assertCount(2, (new PackageGalleryImageModel())->where('package_id', $pid)->findAll(), "Malformed $label should not mutate gallery");
        }
        // Empty string case: filtered to empty, should be treated as safe (remove all) without warnings, not as hard error but also not mutate incorrectly
        $dataEmpty = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        try {
            $resultEmpty = $service->updatePackageWithMedia($pid, $dataEmpty, $this->adminId, null, false, [], [''], [], []);
            // Empty string filtered => becomes [] => means remove all gallery (allowed). Ensure no warnings and either success or graceful handling
            $this->assertIsArray($resultEmpty);
            // Should not throw, and should be either success (remove all) or at least not corrupt
            if ($resultEmpty['success']) {
                // If it removed all, count should be 0, then restore for next test
                $afterEmpty = (new PackageGalleryImageModel())->where('package_id', $pid)->findAll();
                $this->assertCount(0, $afterEmpty, 'Empty string filtered to remove all is allowed');
                // Restore gallery for remaining tests: re-add 2 images
                $tmpR1 = $this->makeTempImage('jpeg');
                $tmpR2 = $this->makeTempImage('jpeg');
                $fR1 = $this->makeUploadedFile($tmpR1, 'restore1.jpg', 'image/jpeg');
                $fR2 = $this->makeUploadedFile($tmpR2, 'restore2.jpg', 'image/jpeg');
                $dataRestore = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
                $service->updatePackageWithMedia($pid, $dataRestore, $this->adminId, null, false, [$fR1,$fR2], [], [], []);
                @unlink($tmpR1); @unlink($tmpR2);
            } else {
                $this->assertArrayHasKey('gallery_images', $resultEmpty['errors']);
            }
        } catch (\Throwable $e) {
            $this->fail("Empty string case threw: ".$e->getMessage());
        }
        // Test deleted/nonexistent via soft-deleted package's gallery? Create and delete another package's gallery then use its ID
        $service2 = new PackageService();
        [$pidDel, $galleryDel] = $this->createPackageWithGallery(1, ['slug'=>'delmal-'.uniqid()]);
        $idDel = (int)$galleryDel[0]['id'];
        $service2->deletePackage($pidDel, $this->adminId);
        // Now try to use deleted package's gallery ID while editing pid
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [], [$idDel], [], []);
        $this->assertFalse($result['success'], 'Deleted record ID should be invalid for other package');
    }

    // 5. Gallery order sequential
    public function testGalleryOrderSequentialServerControlled(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(3, ['slug'=>'order-seq-'.uniqid()]);
        $ids = array_column($gallery, 'id'); // [1,2,3]
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $reversed = array_reverse($ids);
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [], $ids, [], $reversed);
        $this->assertTrue($result['success']);
        $after = (new PackageGalleryImageModel())->where('package_id', $pid)->orderBy('display_order','ASC')->findAll();
        $this->assertEquals($reversed, array_column($after, 'id'), 'Order should be reversed as submitted');
        foreach ($after as $idx => $row) {
            $this->assertEquals($idx+1, (int)$row['display_order'], 'Sequential 1,2,3');
        }
        // Test that posted display_order is ignored: try to inject display_order via alt? Not applicable, but ensure server derives.
        // Submit order with duplicate should be rejected, not silently deduped to sequential
        $dupOrder = [$ids[0], $ids[0], $ids[1]];
        $data2 = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $result2 = $service->updatePackageWithMedia($pid, $data2, $this->adminId, null, false, [], $ids, [], $dupOrder);
        $this->assertFalse($result2['success'], 'Duplicate order must be rejected');
    }

    // 6. Alt text
    public function testAltTextXssEscapedAndPlain(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(1, ['slug'=>'alt-xss-'.uniqid()]);
        $id = (int)$gallery[0]['id'];
        $service = new PackageService();
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $xss = '<script>alert(1)</script>"\' unicode 🚀 <b>bold</b>';
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [], [$id], [$xss], []);
        $this->assertTrue($result['success'], json_encode($result['errors'] ?? []));
        $row = (new PackageGalleryImageModel())->find($id);
        $this->assertEquals($xss, $row['alt_text'], 'Stored plain, not stripped (escape on view)');
        // Verify escaping as would be in view
        $escaped = esc($row['alt_text']);
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
        $this->assertStringNotContainsString('<b>', $escaped);
        // Second alt with unicode and quotes
        $service2 = new PackageService();
        $unicodeAlt = 'Test "quotes" and \'single\' and unicode — 中文 🚀';
        $data2 = $this->validData(['slug'=> $row['alt_text'] ? (new PackageModel())->find($pid)['slug'] : $data['slug']]);
        // Need to fetch current slug again
        $data2 = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $result2 = $service2->updatePackageWithMedia($pid, $data2, $this->adminId, null, false, [], [$id], [$unicodeAlt], []);
        $this->assertTrue($result2['success']);
        $row2 = (new PackageGalleryImageModel())->find($id);
        $this->assertEquals(mb_substr($unicodeAlt,0,300), $row2['alt_text']);
    }

    public function testAltTextMaxLengthAndTampering(): void
    {
        [$pid, $gallery] = $this->createPackageWithGallery(1, ['slug'=>'alt-max-'.uniqid()]);
        $id = (int)$gallery[0]['id'];
        $service = new PackageService();
        $long = str_repeat('a', 500);
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $result = $service->updatePackageWithMedia($pid, $data, $this->adminId, null, false, [], [$id], [$long], []);
        $this->assertTrue($result['success']);
        $row = (new PackageGalleryImageModel())->find($id);
        $this->assertEquals(300, mb_strlen($row['alt_text']), 'Max 300 enforced');
        $this->assertEquals(mb_substr($long,0,300), $row['alt_text']);

        // Array/object tampering should not cause warnings and should be sanitized to null
        $data2 = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $result2 = $service->updatePackageWithMedia($pid, $data2, $this->adminId, null, false, [], [$id], [['array']], []);
        $this->assertTrue($result2['success'], 'Array alt should be sanitized, not throw');
        $row2 = (new PackageGalleryImageModel())->find($id);
        $this->assertNull($row2['alt_text']);

        $data3 = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        $obj = new \stdClass();
        $result3 = $service->updatePackageWithMedia($pid, $data3, $this->adminId, null, false, [], [$id], [$obj], []);
        $this->assertTrue($result3['success']);
        $row3 = (new PackageGalleryImageModel())->find($id);
        $this->assertNull($row3['alt_text']);
    }

    // 7. Original name
    public function testOriginalNameUntrustedMetadata(): void
    {
        $service = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        // Client submits original_name with traversal and php
        $file = $this->makeUploadedFile($tmp, '../../evil.php.jpg', 'image/jpeg');
        $data = $this->validData(['slug'=>'orig-'.uniqid()]);
        $res = $service->createPackageWithMedia($data, $this->adminId, null, [$file]);
        $this->assertTrue($res['success']);
        $pid = $res['id'];
        $gallery = (new PackageGalleryImageModel())->where('package_id', $pid)->findAll();
        $this->assertCount(1, $gallery);
        $original = $gallery[0]['original_name'];
        $this->assertEquals('../../evil.php.jpg', $original, 'Original stored as metadata');
        $path = $gallery[0]['image_path'];
        $this->assertStringStartsWith('uploads/packages/'.$pid.'/', $path);
        $this->assertStringNotContainsString('evil', $path);
        $this->assertStringEndsWith('.jpg', $path);
        // Deletion uses image_path not original_name: delete should remove file via image_path
        $service2 = new PackageService();
        $data2 = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        // Remove gallery
        $result = $service2->updatePackageWithMedia($pid, $data2, $this->adminId, null, false, [], [], [], []);
        $this->assertTrue($result['success']);
        $this->assertFileDoesNotExist(FCPATH . $path);
        // Original path traversal not used
        $this->assertFalse(is_file(FCPATH . '../../evil.php.jpg'));
        @unlink($tmp);
    }

    // 8. Featured ownership
    public function testFeaturedImageCannotAffectAnotherPackage(): void
    {
        $service = new PackageService();
        $tmpA = $this->makeTempImage('jpeg');
        $featA = $this->makeUploadedFile($tmpA, 'a.jpg', 'image/jpeg');
        $dataA = $this->validData(['slug'=>'feat-a-'.uniqid()]);
        $resA = $service->createPackageWithMedia($dataA, $this->adminId, $featA, []);
        $pidA = $resA['id'];
        $featPathA = (new PackageModel())->find($pidA)['featured_image'];
        $this->assertFileExists(FCPATH . $featPathA);

        $tmpB = $this->makeTempImage('png');
        $featB = $this->makeUploadedFile($tmpB, 'b.png', 'image/png');
        $dataB = $this->validData(['slug'=>'feat-b-'.uniqid()]);
        $resB = $service->createPackageWithMedia($dataB, $this->adminId, $featB, []);
        $pidB = $resB['id'];
        $featPathB = (new PackageModel())->find($pidB)['featured_image'];
        $this->assertFileExists(FCPATH . $featPathB);

        // Try editing A to replace its featured, ensure B unchanged
        $tmpNew = $this->makeTempImage('png');
        $newFeat = $this->makeUploadedFile($tmpNew, 'new.png', 'image/png');
        $dataA2 = $this->validData(['slug'=> $dataA['slug']]);
        $result = $service->updatePackageWithMedia($pidA, $dataA2, $this->adminId, $newFeat, false, [], [], [], []);
        $this->assertTrue($result['success']);
        $this->assertFileExists(FCPATH . (new PackageModel())->find($pidA)['featured_image']);
        $this->assertFileDoesNotExist(FCPATH . $featPathA);
        // B still has its original
        $this->assertEquals($featPathB, (new PackageModel())->find($pidB)['featured_image']);
        $this->assertFileExists(FCPATH . $featPathB);

        // Try remove featured of A via path injection: submit featured_image string path of B, but service should ignore and only use boolean
        $dataA3 = $this->validData(['slug'=> $dataA['slug']]);
        $dataA3['featured_image'] = $featPathB; // try to spoof path
        $result2 = $service->updatePackageWithMedia($pidA, $dataA3, $this->adminId, null, true, [], [], [], []);
        $this->assertTrue($result2['success']); // removeFeatured true should clear A's featured
        $this->assertNull((new PackageModel())->find($pidA)['featured_image']);
        // B unchanged
        $this->assertEquals($featPathB, (new PackageModel())->find($pidB)['featured_image']);
        $this->assertFileExists(FCPATH . $featPathB);

        @unlink($tmpA); @unlink($tmpB); @unlink($tmpNew);
    }

    // 9. Zero-FK orphan detection
    public function testTrueOrphanDetection(): void
    {
        $service = new PackageService();
        [$pid, $gallery] = $this->createPackageWithGallery(1, ['slug'=>'orph-'.uniqid()]);
        // Ensure no orphans initially
        $this->assertCount(0, $service->findOrphanGalleryRows(), 'No orphans initially');
        // Manually insert orphan via DB (bypass model)
        $db = \Config\Database::connect();
        $orphanId = 9999999;
        $db->table('package_gallery_images')->insert([
            'package_id' => $orphanId,
            'image_path' => 'uploads/packages/'.$orphanId.'/orphan.jpg',
            'original_name' => 'orphan.jpg',
            'alt_text' => null,
            'display_order' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $orphans = $service->findOrphanGalleryRows();
        $this->assertCount(1, $orphans, 'One true orphan detected');
        $this->assertEquals($orphanId, (int)$orphans[0]['package_id']);
        // Cleanup orphan
        $db->table('package_gallery_images')->where('package_id', $orphanId)->delete();
        $this->assertCount(0, $service->findOrphanGalleryRows(), 'Cleaned orphan should be 0');
        // Deleted package media is NOT orphan: create, delete, check
        $db->table('package_gallery_images')->where('package_id', $pid)->delete(); // clean previous to avoid confusion
        // Recreate gallery for pid then delete package
        $service2 = new PackageService();
        $tmp = $this->makeTempImage('jpeg');
        $f = $this->makeUploadedFile($tmp, 'g.jpg', 'image/jpeg');
        $data = $this->validData(['slug'=> (new PackageModel())->find($pid)['slug']]);
        // Instead, create fresh package with gallery for deletion test
        [$pidDel, $galleryDel] = $this->createPackageWithGallery(1, ['slug'=>'orph-del-'.uniqid()]);
        $service2->deletePackage($pidDel, $this->adminId);
        $this->assertCount(0, $service2->findOrphanGalleryRows(), 'Deleted package media not orphan');
        @unlink($tmp);
    }

    // 10 & 11 Soft delete and restore
    public function testSoftDeleteMediaPreservationAndRestoreExact(): void
    {
        $service = new PackageService();
        $tmpFeat = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmpFeat, 'feat.jpg', 'image/jpeg');
        $tmp1 = $this->makeTempImage('jpeg');
        $tmp2 = $this->makeTempImage('png');
        $g1 = $this->makeUploadedFile($tmp1, 'g1.jpg', 'image/jpeg');
        $g2 = $this->makeUploadedFile($tmp2, 'g2.png', 'image/png');
        $data = $this->validData(['slug'=>'del-pres-'.uniqid()]);
        $res = $service->createPackageWithMedia($data, $this->adminId, $feat, [$g1,$g2]);
        $pid = $res['id'];
        // Set alt texts via update
        $galleryBefore = (new PackageGalleryImageModel())->where('package_id', $pid)->orderBy('display_order','ASC')->findAll();
        $ids = array_column($galleryBefore, 'id');
        $alts = ['Alt one', 'Alt two'];
        $data2 = $this->validData(['slug'=> $data['slug']]);
        $service->updatePackageWithMedia($pid, $data2, $this->adminId, null, false, [], $ids, $alts, []);
        $galleryWithAlts = (new PackageGalleryImageModel())->where('package_id', $pid)->orderBy('display_order','ASC')->findAll();
        $featPath = (new PackageModel())->find($pid)['featured_image'];
        $this->assertFileExists(FCPATH . $featPath);
        foreach ($galleryWithAlts as $g) $this->assertFileExists(FCPATH . $g['image_path']);

        // Delete
        $service->deletePackage($pid, $this->adminId);
        $deletedPkg = (new PackageModel())->onlyDeleted()->find($pid);
        $this->assertNotNull($deletedPkg);
        $this->assertEquals(0, (int)$deletedPkg['is_active']);
        $this->assertNotNull($deletedPkg['deleted_at']);
        $this->assertEquals($featPath, $deletedPkg['featured_image'], 'Featured DB preserved');
        $galleryAfterDel = (new PackageGalleryImageModel())->where('package_id', $pid)->orderBy('display_order','ASC')->findAll();
        $this->assertCount(2, $galleryAfterDel, 'Gallery rows preserved');
        $this->assertEquals($galleryWithAlts[0]['image_path'], $galleryAfterDel[0]['image_path']);
        $this->assertEquals('Alt one', $galleryAfterDel[0]['alt_text']);
        $this->assertEquals(1, (int)$galleryAfterDel[0]['display_order']);
        $this->assertEquals(2, (int)$galleryAfterDel[1]['display_order']);
        $this->assertFileExists(FCPATH . $featPath);
        foreach ($galleryAfterDel as $g) $this->assertFileExists(FCPATH . $g['image_path']);
        // Not orphan
        $this->assertCount(0, $service->findOrphanGalleryRows());

        // Restore
        $service->restorePackage($pid, $this->adminId);
        $restored = (new PackageModel())->find($pid);
        $this->assertNotNull($restored);
        $this->assertNull($restored['deleted_at']);
        $this->assertEquals(0, (int)$restored['is_active'], 'Remains inactive per sealed lifecycle');
        $this->assertEquals($featPath, $restored['featured_image'], 'Featured exact');
        $this->assertFileExists(FCPATH . $restored['featured_image']);
        $galleryRestored = (new PackageGalleryImageModel())->where('package_id', $pid)->orderBy('display_order','ASC')->findAll();
        $this->assertCount(2, $galleryRestored);
        $this->assertEquals($galleryWithAlts[0]['image_path'], $galleryRestored[0]['image_path']);
        $this->assertEquals('Alt one', $galleryRestored[0]['alt_text']);
        $this->assertEquals(1, (int)$galleryRestored[0]['display_order']);
        @unlink($tmpFeat); @unlink($tmp1); @unlink($tmp2);
    }

    // 12 Missing file
    public function testMissingPhysicalFileSafe(): void
    {
        $service = new PackageService();
        $tmpFeat = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmpFeat, 'feat.jpg', 'image/jpeg');
        $tmpG = $this->makeTempImage('png');
        $g = $this->makeUploadedFile($tmpG, 'g.png', 'image/png');
        $data = $this->validData(['slug'=>'missing-'.uniqid()]);
        $res = $service->createPackageWithMedia($data, $this->adminId, $feat, [$g]);
        $pid = $res['id'];
        $featPath = (new PackageModel())->find($pid)['featured_image'];
        $gallery = (new PackageGalleryImageModel())->where('package_id', $pid)->findAll();
        $gPath = $gallery[0]['image_path'];
        // Delete physical files
        @unlink(FCPATH . $featPath);
        @unlink(FCPATH . $gPath);
        $this->assertFileDoesNotExist(FCPATH . $featPath);
        $this->assertFileDoesNotExist(FCPATH . $gPath);
        // Listing must not crash, GET must not mutate DB
        $this->login();
        $session = $_SESSION;
        $resp = $this->withSession($session)->call('GET', '/admin/packages');
        $resp->assertStatus(200);
        $body = $resp->getBody();
        $this->assertStringContainsString($data['name'], $body);
        // Edit page must not crash
        $resp2 = $this->withSession($session)->call('GET', '/admin/packages/'.$pid.'/edit');
        $resp2->assertStatus(200);
        $this->assertStringContainsString('Edit Package', $resp2->getBody());
        // Deleted page after soft delete with missing files
        $service->deletePackage($pid, $this->adminId);
        $resp3 = $this->withSession($session)->call('GET', '/admin/packages/deleted');
        $resp3->assertStatus(200);
        // DB must still have paths (GET didn't mutate)
        $pkgAfter = (new PackageModel())->onlyDeleted()->find($pid);
        $this->assertEquals($featPath, $pkgAfter['featured_image'], 'GET must not mutate DB even if file missing');
        $galleryAfter = (new PackageGalleryImageModel())->where('package_id', $pid)->findAll();
        $this->assertEquals($gPath, $galleryAfter[0]['image_path']);
        @unlink($tmpFeat); @unlink($tmpG);
    }

    // 13 Transaction rollback
    public function testGalleryTransactionRollback(): void
    {
        $service = new PackageService();
        $tmpFeat = $this->makeTempImage('jpeg');
        $feat = $this->makeUploadedFile($tmpFeat, 'feat.jpg', 'image/jpeg');
        $tmpG = $this->makeTempImage('jpeg');
        $g = $this->makeUploadedFile($tmpG, 'g.jpg', 'image/jpeg');
        $data = $this->validData(['slug'=>'txn-rollback-'.uniqid()]);
        $res = $service->createPackageWithMedia($data, $this->adminId, $feat, [$g]);
        $pid = $res['id'];
        $featPath = (new PackageModel())->find($pid)['featured_image'];
        $galleryBefore = (new PackageGalleryImageModel())->where('package_id', $pid)->orderBy('display_order','ASC')->findAll();
        $idBefore = (int)$galleryBefore[0]['id'];
        $altBefore = $galleryBefore[0]['alt_text'];
        // Prepare update that will fail mid-transaction via simulateFeatureFailure (features deletion then throw)
        $tmpNew = $this->makeTempImage('png');
        $newFile = $this->makeUploadedFile($tmpNew, 'new.png', 'image/png');
        $ids = [$idBefore];
        $newAlt = 'New Alt';
        PackageService::$simulateFeatureFailure = true;
        $data2 = $this->validData(['slug'=> $data['slug']]);
        $result = $service->updatePackageWithMedia($pid, $data2, $this->adminId, null, false, [$newFile], $ids, [$newAlt], []);
        PackageService::$simulateFeatureFailure = false;
        $this->assertFalse($result['success']);
        // Gallery DB must be rolled back consistently: still 1 row, alt unchanged, order 1
        $galleryAfter = (new PackageGalleryImageModel())->where('package_id', $pid)->findAll();
        $this->assertCount(1, $galleryAfter);
        $this->assertEquals($idBefore, (int)$galleryAfter[0]['id']);
        $this->assertEquals($altBefore, $galleryAfter[0]['alt_text'], 'Alt rollback');
        $this->assertEquals(1, (int)$galleryAfter[0]['display_order']);
        $this->assertFileExists(FCPATH . $galleryAfter[0]['image_path']);
        // New file must be cleaned (filesystem compensating from 5E.2A)
        $dir = FCPATH . 'uploads/packages/'.$pid;
        $files = array_values(array_filter(scandir($dir) ?: [], fn($f)=> $f !== '.' && $f !== '..' && $f !== '.htaccess'));
        // Should have original featured + original gallery only (2 files)
        $this->assertCount(2, $files, 'New gallery file cleaned, old preserved');
        $this->assertFileExists(FCPATH . $featPath);
        @unlink($tmpFeat); @unlink($tmpG); @unlink($tmpNew);
    }
}

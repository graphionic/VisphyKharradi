<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AdminPasswordChangeTest — Phase 3
 *
 * Verifies secure password change.
 *
 * @internal
 */
final class AdminPasswordChangeTest extends CIUnitTestCase
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
        try { cache()->clean(); $th = service('throttler'); foreach(['127.0.0.1','0.0.0.0','::1'] as $ip) $th->remove('admin_login_ip_'.hash('sha256', $ip)); } catch(\Throwable $e){}
        $_SESSION=[]; $_COOKIE=[];
        $this->session = [];
        try { service('superglobals')->setGlobalArray('cookie', []); $sec=service('security'); $sec->generateHash(); $_COOKIE[$sec->getCookieName()]=$sec->getHash(); service('superglobals')->setGlobalArray('cookie', $_COOKIE);} catch(\Throwable $e){}
    }
    protected function tearDown(): void { try{cache()->clean();}catch(\Throwable $e){} parent::tearDown(); }

    private function createAdmin(string $email, string $pass): array
    {
        $m=new \App\Models\AdminModel();
        $id=$m->insert(['name'=>'Pass Admin','email'=>strtolower(trim($email)),'password_hash'=>password_hash($pass,PASSWORD_DEFAULT),'is_active'=>1],true);
        $this->assertNotFalse($id);
        return $m->find($id);
    }
    private function csrf(): array
    {
        $sec=service('security'); $hash=$sec->getHash() ?? $sec->generateHash(); $tn=$sec->getTokenName(); $cn=$sec->getCookieName(); $_COOKIE[$cn]=$hash; try{service('superglobals')->setGlobalArray('cookie',$_COOKIE);}catch(\Throwable $e){}
        return [$tn=>$hash];
    }
    private function login(string $email,string $pass): array
    {
        $csrf=$this->csrf();
        $res=$this->call('post','/admin/login',array_merge(['email'=>$email,'password'=>$pass],$csrf));
        return [$res, $_SESSION];
    }

    public function testPasswordChangeSuccess(): void
    {
        $email='pass-success-'.uniqid().'@example.com';
        $old='OldSecurePass123!';
        $new='NewSecurePass456!';
        $admin=$this->createAdmin($email,$old);
        [$res,$sess]=$this->login($email,$old);
        $res->assertRedirect();
        $session=$_SESSION;
        // Change password
        $csrf=$this->csrf();
        $res2=$this->withSession($session)->call('post','/admin/profile/password', array_merge([
            'current_password'=>$old,
            'new_password'=>$new,
            'confirm_password'=>$new,
        ], $csrf));
        $res2->assertStatus(200);
        $res2->assertSee('Password changed successfully');

        // Verify DB hash is updated
        $m=new \App\Models\AdminModel();
        $fresh=$m->find($admin['id']);
        $this->assertNotEquals($new, $fresh['password_hash']);
        $this->assertTrue(password_verify($new, $fresh['password_hash']));
        $this->assertFalse(password_verify($old, $fresh['password_hash']));

        // Verify via service directly (no HTTP session/CSRF complexity)
        $svc = new \App\Services\AdminAuthService();
        // Need to clear throttle that may have been hit during earlier logins
        try {
            $th = service('throttler');
            $th->remove('admin_login_email_'.hash('sha256', strtolower(trim($email))));
            foreach(['127.0.0.1','0.0.0.0','::1','unknown'] as $ip) $th->remove('admin_login_ip_'.hash('sha256', $ip));
        } catch(\Throwable $e){}
        $oldResult = $svc->authenticate($email, $old);
        $this->assertFalse($oldResult['success'], 'Old password should fail after change');

        // Clear throttle again before new (authenticate increments on failure)
        try {
            $th = service('throttler');
            $th->remove('admin_login_email_'.hash('sha256', strtolower(trim($email))));
            foreach(['127.0.0.1','0.0.0.0','::1','unknown'] as $ip) $th->remove('admin_login_ip_'.hash('sha256', $ip));
        } catch(\Throwable $e){}
        $newResult = $svc->authenticate($email, $new);
        $this->assertTrue($newResult['success'], 'New password should succeed after change');

        // Also verify via HTTP that old fails and new succeeds
        // Must clear FeatureTestTrait session via withSession([])
        $this->withSession([]);
        $_SESSION=[]; $_COOKIE=[];
        try{ $sec=service('security'); $sec->generateHash(); $_COOKIE[$sec->getCookieName()]=$sec->getHash(); service('superglobals')->setGlobalArray('cookie',$_COOKIE);}catch(\Throwable $e){}
        try {
            $th = service('throttler');
            $th->remove('admin_login_email_'.hash('sha256', strtolower(trim($email))));
            foreach(['127.0.0.1','0.0.0.0','::1','unknown'] as $ip) $th->remove('admin_login_ip_'.hash('sha256', $ip));
        } catch(\Throwable $e){}

        [$resOldHttp,$sessOldHttp] = $this->login($email,$old);
        $resOldHttp->assertStatus(200);
        $resOldHttp->assertSee('Invalid email or password');

        // Clear throttle again before new HTTP (old attempt consumed token)
        try {
            $th = service('throttler');
            $th->remove('admin_login_email_'.hash('sha256', strtolower(trim($email))));
            foreach(['127.0.0.1','0.0.0.0','::1','unknown'] as $ip) $th->remove('admin_login_ip_'.hash('sha256', $ip));
        } catch(\Throwable $e){}
        $this->withSession([]);
        $_SESSION=[]; $_COOKIE=[];
        try{ $sec=service('security'); $sec->generateHash(); $_COOKIE[$sec->getCookieName()]=$sec->getHash(); service('superglobals')->setGlobalArray('cookie',$_COOKIE);}catch(\Throwable $e){}

        [$resNew,$sessNew] = $this->login($email,$new);
        $resNew->assertRedirect();
    }

    public function testPasswordChangeWrongCurrentRejected(): void
    {
        $email='pass-wrongcur-'.uniqid().'@example.com';
        $old='OldSecurePass123!';
        $this->createAdmin($email,$old);
        [$res,$sess]=$this->login($email,$old);
        $session=$_SESSION;
        $csrf=$this->csrf();
        $res2=$this->withSession($session)->call('post','/admin/profile/password', array_merge([
            'current_password'=>'WrongCurrent123!',
            'new_password'=>'NewSecurePass456!',
            'confirm_password'=>'NewSecurePass456!',
        ], $csrf));
        $res2->assertStatus(200);
        $res2->assertSee('Current password is incorrect');
        $this->assertArrayHasKey('admin_id', $_SESSION);
    }

    public function testPasswordChangeMismatchRejected(): void
    {
        $email='pass-mismatch-'.uniqid().'@example.com';
        $old='OldSecurePass123!';
        $this->createAdmin($email,$old);
        [$res,$sess]=$this->login($email,$old);
        $session=$_SESSION;
        $csrf=$this->csrf();
        $res2=$this->withSession($session)->call('post','/admin/profile/password', array_merge([
            'current_password'=>$old,
            'new_password'=>'NewSecurePass456!',
            'confirm_password'=>'Different456!',
        ], $csrf));
        $res2->assertStatus(200);
        $res2->assertSee('confirm_password');
    }

    public function testPasswordChangeTooShortRejected(): void
    {
        $email='pass-short-'.uniqid().'@example.com';
        $old='OldSecurePass123!';
        $this->createAdmin($email,$old);
        [$res,$sess]=$this->login($email,$old);
        $session=$_SESSION;
        $csrf=$this->csrf();
        $res2=$this->withSession($session)->call('post','/admin/profile/password', array_merge([
            'current_password'=>$old,
            'new_password'=>'Short1!',
            'confirm_password'=>'Short1!',
        ], $csrf));
        $res2->assertStatus(200);
        $res2->assertSee('at least 10');
        $m=new \App\Models\AdminModel();
        $fresh=$m->where('email', strtolower($email))->first();
        $this->assertTrue(password_verify($old, $fresh['password_hash']));
    }

    public function testPasswordHashStoredSecurely(): void
    {
        $email='pass-hash-'.uniqid().'@example.com';
        $old='OldSecurePass123!';
        $new='NewSecurePass789!';
        $admin=$this->createAdmin($email,$old);
        [$res,$sess]=$this->login($email,$old);
        $session=$_SESSION;
        $csrf=$this->csrf();
        $this->withSession($session)->call('post','/admin/profile/password', array_merge([
            'current_password'=>$old,
            'new_password'=>$new,
            'confirm_password'=>$new,
        ], $csrf));
        $m=new \App\Models\AdminModel();
        $fresh=$m->find($admin['id']);
        $this->assertNotEquals($new, $fresh['password_hash']);
        $this->assertNotEquals($old, $fresh['password_hash']);
        $this->assertTrue(password_verify($new, $fresh['password_hash']));
        $this->assertArrayNotHasKey('new_password', $_SESSION);
    }

    public function testPasswordChangeRequiresAuth(): void
    {
        $csrf=$this->csrf();
        $res=$this->call('post','/admin/profile/password', array_merge([
            'current_password'=>'anything',
            'new_password'=>'NewPass12345!',
            'confirm_password'=>'NewPass12345!',
        ], $csrf));
        $res->assertRedirect();
        $this->assertStringContainsString('admin/login', $res->getRedirectUrl());
    }
}

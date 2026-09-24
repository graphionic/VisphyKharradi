<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdminAuthService;
use App\Services\AdminActivityService;

/**
 * AuthController — Phase 3
 *
 * Handles admin login/logout with throttling, CSRF, generic errors, activity logging.
 */
class AuthController extends BaseController
{
    private AdminAuthService $authService;
    private AdminActivityService $activity;

    public function __construct()
    {
        $this->authService = new AdminAuthService();
        $this->activity    = new AdminActivityService();
    }

    /**
     * GET /admin/login
     * Guest only (AdminGuest filter redirects authenticated)
     */
    public function login()
    {
        // Provide minimal view data
        return view('admin/auth/login', [
            'title' => 'Admin Sign In — Ftpreneur',
        ]);
    }

    /**
     * POST /admin/login
     */
    public function attemptLogin()
    {
        // Normalize email before validation — trim + lowercase (spec §6)
        $rawEmail = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');
        $normalizedEmail = AdminAuthService::normalizeEmail($rawEmail);

        // Temporarily inject normalized email for validation (so " ADMIN@example.com " passes valid_email)
        $validationData = [
            'email'    => $normalizedEmail,
            'password' => $password,
        ];
        $validationRules = [
            'email'    => 'required|valid_email|max_length[190]',
            'password' => 'required|max_length[255]',
        ];

        $validation = service('validation');
        $validation->setRules($validationRules);
        if (! $validation->run($validationData)) {
            return view('admin/auth/login', [
                'title'      => 'Admin Sign In — Ftpreneur',
                'validation' => $validation,
            ]);
        }

        $email = $normalizedEmail;
        $ip = $this->request->getIPAddress() ?? '0.0.0.0';

        // Throttling — shared-hosting compatible via CI4 Throttler (file cache)
        // Limit: 5 attempts per 15 minutes per IP and per normalized email
        // NOTE: Raw IP/email must NEVER be used directly in cache keys — IPv6 contains ":" which is a
        // CI4 reserved character ({}()/\@:) and would throw InvalidArgumentException.
        // We use a deterministic SHA-256 hex digest (0-9a-f) for both — safe, fixed-length, private.
        $throttler = service('throttler');
        $ipKey    = $this->throttleKey('ip', $ip);
        $emailKey = $this->throttleKey('email', $normalizedEmail);

        // Check both buckets; each check consumes 1 token if passed
        $ipAllowed    = $throttler->check($ipKey, 5, 900);
        $emailAllowed = $throttler->check($emailKey, 5, 900);

        if (! $ipAllowed || ! $emailAllowed) {
            $retry = $throttler->getTokenTime();
            // Generic throttled response — do not reveal whether email exists
            $this->response->setStatusCode(429);
            $this->response->setHeader('Retry-After', (string) $retry);

            // Optionally log throttled attempt without sensitive data
            $this->activity->log(
                'admin.login_throttled',
                null,
                null,
                null,
                'Login throttled for IP ' . substr($ip, 0, 45),
                ['retry_after' => $retry]
            );

            return view('admin/auth/login', [
                'title' => 'Admin Sign In — Ftpreneur',
                'error' => 'Too many attempts. Please try again in ' . $retry . ' seconds.',
            ]);
        }

        // Authenticate — generic error for unknown, wrong, inactive
        $result = $this->authService->authenticate($email, $password);

        if (! ($result['success'] ?? false)) {
            // Log failed attempt — admin_id null, do not log password
            $this->activity->log(
                'admin.login_failed',
                null,
                null,
                null,
                'Failed login attempt',
                ['attempted_email' => substr($normalizedEmail, 0, 190)]
            );

            return view('admin/auth/login', [
                'title' => 'Admin Sign In — Ftpreneur',
                'error' => $result['error'] ?? 'Invalid email or password.',
            ]);
        }

        /** @var array $admin */
        $admin = $result['admin'];

        // Success — establish session
        $this->authService->login($admin);

        // Clear email throttle on success to avoid locking legitimate user after prior fails
        $throttler->remove($emailKey);
        // Note: IP bucket continues to refill over window (not cleared) to retain brute-force protection per IP

        $this->activity->log(
            'admin.login_success',
            (int) $admin['id'],
            null,
            null,
            'Admin logged in',
            null
        );

        // Relative redirect preserves the active preview host (E2B or Cloudflare)
        // without forcing an absolute baseURL hostname. See App.php preview handling.
        return service('response')->redirect('/admin', 'auto', 302);
    }

    /**
     * POST /admin/logout
     * Requires adminAuth + CSRF
     */
    public function logout()
    {
        $session = session();
        $adminId = $session->get('admin_id');

        // Log before clearing (if adminId exists)
        if (! empty($adminId)) {
            $this->activity->log(
                'admin.logout',
                (int) $adminId,
                null,
                null,
                'Admin logged out',
                null
            );
        }

        $this->authService->logout();

        session()->setFlashdata('message', 'Signed out.');
        return service('response')->redirect('/admin/login', 'auto', 302);
    }

    /**
     * Centralized throttle key — safe for CI4 FileHandler.
     * CI4 cache keys reject {}()/\@: — IPv6 ":" would throw InvalidArgumentException.
     * We use deterministic SHA-256 hex (0-9a-f), fixed length, no raw IP/email exposure.
     */
    private function throttleKey(string $type, string $identity): string
    {
        if (! in_array($type, ['ip', 'email'], true)) {
            throw new \InvalidArgumentException('Invalid throttle type');
        }

        return 'admin_login_' . $type . '_' . hash('sha256', $identity);
    }
}

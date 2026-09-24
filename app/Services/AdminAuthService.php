<?php

namespace App\Services;

use App\Models\AdminModel;
use Config\Services;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * AdminAuthService — Phase 3
 *
 * Handles admin authentication, session, password verification, rehash, and password change.
 * Keep controllers thin — all business logic here.
 */
class AdminAuthService
{
    private AdminModel $adminModel;
    private AdminActivityService $activity;

    /** Dummy hash for timing-safe verification when email not found */
    private const DUMMY_HASH = '$2y$10$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi';

    public function __construct(?AdminModel $adminModel = null, ?AdminActivityService $activity = null)
    {
        $this->adminModel = $adminModel ?? new AdminModel();
        $this->activity   = $activity ?? new AdminActivityService();
    }

    /**
     * Normalize email: trim + lowercase
     */
    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Attempt authentication.
     *
     * Returns array with keys:
     *  - success bool
     *  - admin ?array on success
     *  - error ?string generic error message on failure
     *
     * Uses same generic error for unknown email, wrong password, inactive.
     * Mitigates enumeration via dummy verify and constant-time.
     */
    public function authenticate(string $rawEmail, string $password): array
    {
        $email = self::normalizeEmail($rawEmail);

        // Basic validation — but still perform dummy verify to keep timing similar
        if ($email === '' || $password === '') {
            // Dummy timing
            password_verify($password ?: 'dummy', self::DUMMY_HASH);
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        // Fetch admin by normalized email (DB collation may be ci, but we normalize)
        // Use case-insensitive lookup to respect UNIQUE email constraint behavior
        $admin = $this->adminModel->where('email', $email)->first();

        // If not found, try case-insensitive via LOWER? But where email = normalized already handles if DB is case-insensitive.
        // To be safe, also try lower lookup if not found and email contains uppercase originally (already normalized)
        if ($admin === null) {
            // Dummy verify to mitigate timing difference
            password_verify($password, self::DUMMY_HASH);
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        // Check active — but still verify password to keep timing similar
        $isActive = (int) ($admin['is_active'] ?? 0) === 1;

        $hash = $admin['password_hash'] ?? '';
        $verified = false;
        try {
            $verified = password_verify($password, $hash);
        } catch (\Throwable $e) {
            $verified = false;
        }

        if (! $verified || ! $isActive) {
            // Same generic error for wrong password or inactive — prevents enumeration
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        // Check rehash
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            if ($newHash !== false) {
                try {
                    // Bypass validation for partial update
                    $this->adminModel->skipValidation(true)->update($admin['id'], ['password_hash' => $newHash]);
                    $admin['password_hash'] = $newHash;
                } catch (\Throwable $e) {
                    log_message('error', 'Password rehash failed for admin ' . $admin['id'] . ': ' . $e->getMessage());
                } finally {
                    $this->adminModel->skipValidation(false);
                }
            }
        }

        return ['success' => true, 'admin' => $admin];
    }

    /**
     * Establish authenticated session.
     *
     * Stores minimal state: admin_id, admin_authenticated, auth_time, admin_name
     * Regenerates session ID to prevent fixation.
     * Updates last_login_at.
     */
    public function login(array $admin): void
    {
        $session = session();

        // Regenerate before setting data to prevent fixation
        $session->regenerate(true);

        $session->set([
            'admin_id'            => (int) $admin['id'],
            'admin_authenticated' => true,
            'auth_time'           => time(),
            'admin_name'          => $admin['name'] ?? '',
            'admin_email'         => $admin['email'] ?? '',
        ]);

        // Update last_login_at (skip validation for partial)
        try {
            $this->adminModel->skipValidation(true)->update($admin['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to update last_login_at: ' . $e->getMessage());
        } finally {
            $this->adminModel->skipValidation(false);
        }
    }

    /**
     * Check if current session is authenticated and admin still active.
     * Returns admin array or null.
     */
    public function getCurrentAdmin(): ?array
    {
        $session = session();
        $adminId = $session->get('admin_id');
        $auth    = $session->get('admin_authenticated');

        if (empty($adminId) || $auth !== true) {
            return null;
        }

        $admin = $this->adminModel->find($adminId);
        if ($admin === null) {
            return null;
        }

        if ((int) ($admin['is_active'] ?? 0) !== 1) {
            return null;
        }

        return $admin;
    }

    /**
     * Quick check is authenticated (session + active)
     */
    public function isAuthenticated(): bool
    {
        return $this->getCurrentAdmin() !== null;
    }

    /**
     * Clear authentication state and regenerate/destroy session.
     */
    public function logout(): void
    {
        $session = session();

        // Remove auth keys
        $session->remove(['admin_id', 'admin_authenticated', 'auth_time', 'admin_name', 'admin_email']);

        // Regenerate to prevent session fixation after logout
        // Also destroy old session data
        try {
            $session->regenerate(true);
        } catch (\Throwable $e) {
            // If regenerate fails, destroy
            try {
                $session->destroy();
            } catch (\Throwable $e2) {
                log_message('error', 'Session destroy after logout failed: ' . $e2->getMessage());
            }
        }

        // Ensure no stale data remains
        // Optionally destroy entirely, but keep regeneration as primary
    }

    /**
     * Change password for authenticated admin.
     *
     * @return array{success: bool, error?: string}
     */
    public function changePassword(int $adminId, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        $admin = $this->adminModel->find($adminId);
        if ($admin === null) {
            return ['success' => false, 'error' => 'Admin not found.'];
        }

        if ((int) ($admin['is_active'] ?? 0) !== 1) {
            return ['success' => false, 'error' => 'Account is inactive.'];
        }

        // Verify current
        if (! password_verify($currentPassword, $admin['password_hash'] ?? '')) {
            return ['success' => false, 'error' => 'Current password is incorrect.'];
        }

        // Validate new password
        if (strlen($newPassword) < 10) {
            return ['success' => false, 'error' => 'New password must be at least 10 characters.'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'error' => 'New password and confirmation do not match.'];
        }

        if ($newPassword === $currentPassword) {
            return ['success' => false, 'error' => 'New password must be different from current password.'];
        }

        // Reject empty obviously invalid
        if (trim($newPassword) === '') {
            return ['success' => false, 'error' => 'New password is required.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($newHash === false) {
            return ['success' => false, 'error' => 'Failed to hash password.'];
        }

        try {
            $result = $this->adminModel->skipValidation(true)->update($adminId, ['password_hash' => $newHash]);
            if ($result === false) {
                throw new \RuntimeException('Update returned false: ' . json_encode($this->adminModel->errors()));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Password change failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update password.'];
        } finally {
            $this->adminModel->skipValidation(false);
        }

        // Regenerate session ID to keep session valid but prevent fixation after privilege change
        $session = session();
        try {
            $session->regenerate(true);
            // Update auth_time
            $session->set('auth_time', time());
        } catch (\Throwable $e) {
            log_message('error', 'Session regenerate after password change failed: ' . $e->getMessage());
        }

        return ['success' => true];
    }
}

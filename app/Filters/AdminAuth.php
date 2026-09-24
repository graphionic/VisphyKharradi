<?php

namespace App\Filters;

use App\Models\AdminModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AdminAuth Filter — Phase 3
 *
 * Protects admin routes. Validates:
 * - admin_id exists in session
 * - admin_authenticated true
 * - admin exists in DB
 * - is_active = 1
 *
 * If fails: clears stale auth state and redirects to /admin/login.
 * Sets Cache-Control: no-store for authenticated admin pages.
 */
class AdminAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        $adminId = $session->get('admin_id');
        $auth    = $session->get('admin_authenticated');

        if (empty($adminId) || $auth !== true) {
            // Clear stale state
            $session->remove(['admin_id', 'admin_authenticated', 'auth_time', 'admin_name', 'admin_email']);
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        // Validate admin still exists and active (revalidation on each protected request)
        try {
            $adminModel = new AdminModel();
            $admin = $adminModel->find($adminId);
        } catch (\Throwable $e) {
            $session->remove(['admin_id', 'admin_authenticated', 'auth_time', 'admin_name', 'admin_email']);
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        if ($admin === null || (int) ($admin['is_active'] ?? 0) !== 1) {
            // Admin deleted or deactivated — force logout
            $session->remove(['admin_id', 'admin_authenticated', 'auth_time', 'admin_name', 'admin_email']);
            try {
                $session->regenerate(true);
            } catch (\Throwable $e) {
                // ignore
            }
            session()->setFlashdata('error', 'Session invalid. Please sign in again.');
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        // Optionally update auth_time? Not needed every request, but could enforce idle timeout later
        // For now, leave as is

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Prevent caching of sensitive admin pages
        if (! $response->hasHeader('Cache-Control')) {
            $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }
        if (! $response->hasHeader('Pragma')) {
            $response->setHeader('Pragma', 'no-cache');
        }
        if (! $response->hasHeader('Expires')) {
            $response->setHeader('Expires', '0');
        }

        return $response;
    }
}

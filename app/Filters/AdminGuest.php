<?php

namespace App\Filters;

use App\Models\AdminModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AdminGuest Filter — Phase 3
 *
 * Prevents authenticated admins from seeing /admin/login again.
 * Redirects to /admin if already authenticated and active.
 */
class AdminGuest implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $adminId = $session->get('admin_id');
        $auth    = $session->get('admin_authenticated');

        if (! empty($adminId) && $auth === true) {
            try {
                $adminModel = new AdminModel();
                $admin = $adminModel->find($adminId);
                if ($admin !== null && (int) ($admin['is_active'] ?? 0) === 1) {
                    return service('response')->redirect('/admin', 'auto', 302);
                }
                // If stale, clear and allow login page
                $session->remove(['admin_id', 'admin_authenticated', 'auth_time', 'admin_name', 'admin_email']);
            } catch (\Throwable $e) {
                // Allow login page on error
                return null;
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}

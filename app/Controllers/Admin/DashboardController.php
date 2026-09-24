<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdminAuthService;

/**
 * DashboardController — Phase 3 placeholder
 *
 * GET /admin — protected by AdminAuth
 * Shows minimal authenticated page (not Phase 4 premium design)
 */
class DashboardController extends BaseController
{
    public function index()
    {
        $authService = new AdminAuthService();
        $admin = $authService->getCurrentAdmin();

        // getCurrentAdmin already validated is_active, but keep fallback
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        // Cache-Control is set by AdminAuth filter after() as no-store
        return view('admin/dashboard/index', [
            'title' => 'Admin — Ftpreneur',
            'admin' => $admin,
        ]);
    }
}

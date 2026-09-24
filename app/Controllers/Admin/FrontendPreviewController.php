<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdminAuthService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * FrontendPreviewController — Dev-only preview for production frontend design system
 *
 * Route: /admin/frontend-preview (authenticated, non-production environment only)
 * Purpose: Visual verification of production frontend CSS/components before building landing page sections.
 *
 * Security:
 * - Requires adminAuth filter + AdminAuthService check
 * - Rejects in production environment (throws 404)
 * - Development-only internal admin tool
 */
class FrontendPreviewController extends BaseController
{
    public function index()
    {
        // Must NOT be exposed in production environment
        if (ENVIRONMENT === 'production') {
            throw PageNotFoundException::forPageNotFound();
        }

        $auth  = new AdminAuthService();
        $admin = $auth->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        return view('admin/frontend_preview/index', [
            'title' => 'Frontend Design System Preview — Ftpreneur Admin',
            'admin' => $admin,
        ]);
    }
}

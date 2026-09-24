<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdminAuthService;

/**
 * UiPreviewController — Phase 4 design system preview
 * Development only, authenticated. Not accessible in production.
 */
class UiPreviewController extends BaseController
{
    public function index()
    {
        if (ENVIRONMENT === 'production') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Not found');
        }

        $auth = new AdminAuthService();
        $admin = $auth->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        // No DB queries for demo data — static only
        return view('admin/ui/preview', [
            'title' => 'UI Preview — Ftpreneur Admin',
            'admin' => $admin,
        ]);
    }
}

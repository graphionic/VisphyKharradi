<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdminAuthService;
use App\Services\AdminActivityService;

/**
 * ProfileController — Phase 3
 *
 * GET /admin/profile — show name/email
 * POST /admin/profile/password — change password
 */
class ProfileController extends BaseController
{
    private AdminAuthService $authService;
    private AdminActivityService $activity;

    public function __construct()
    {
        $this->authService = new AdminAuthService();
        $this->activity    = new AdminActivityService();
    }

    public function index()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        return view('admin/profile/index', [
            'title' => 'Profile — Ftpreneur',
            'admin' => $admin,
        ]);
    }

    public function updatePassword()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $rules = [
            'current_password'      => 'required|max_length[255]',
            'new_password'          => 'required|min_length[10]|max_length[255]',
            'confirm_password'      => 'required|matches[new_password]|max_length[255]',
        ];

        // Provide custom messages? Use defaults but ensure escaping
        if (! $this->validate($rules)) {
            return view('admin/profile/index', [
                'title'      => 'Profile — Ftpreneur',
                'admin'      => $admin,
                'validation' => $this->validator,
            ]);
        }

        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');
        $confirm = (string) $this->request->getPost('confirm_password');

        $result = $this->authService->changePassword((int) $admin['id'], $current, $new, $confirm);

        if (! ($result['success'] ?? false)) {
            return view('admin/profile/index', [
                'title' => 'Profile — Ftpreneur',
                'admin' => $admin,
                'error' => $result['error'] ?? 'Failed to change password.',
            ]);
        }

        $this->activity->log(
            'admin.password_changed',
            (int) $admin['id'],
            null,
            null,
            'Admin changed password',
            null
        );

        // Keep session valid but regenerated (handled in service), show success
        return view('admin/profile/index', [
            'title'   => 'Profile — Ftpreneur',
            'admin'   => $this->authService->getCurrentAdmin() ?? $admin,
            'message' => 'Password changed successfully.',
        ]);
    }
}

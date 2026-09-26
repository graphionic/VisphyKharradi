<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Services\AdminActivityService;
use App\Services\AdminAuthService;

/**
 * PackageDisplayController — Package Display Template System Admin Selector
 *
 * Route: /admin/package-display
 * Purpose: Manage active homepage Package Display Design selection (concept_01, concept_02, concept_04)
 */
class PackageDisplayController extends BaseController
{
    private AdminAuthService $authService;
    private AdminActivityService $activity;
    private SettingModel $settingModel;

    public function __construct()
    {
        $this->authService  = new AdminAuthService();
        $this->activity     = new AdminActivityService();
        $this->settingModel = new SettingModel();
    }

    public function index()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $currentDesign = $this->settingModel->getPackageDisplayDesign();

        return view('admin/package_display/index', [
            'title'         => 'Package Display',
            'description'   => 'Choose how programs and packages are presented on the public website.',
            'admin'         => $admin,
            'currentDesign' => $currentDesign,
        ]);
    }

    public function update()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $selectedDesign = trim((string) $this->request->getPost('package_design'));

        if (! in_array($selectedDesign, SettingModel::ALLOWED_PACKAGE_DESIGNS, true)) {
            return redirect()->to('/admin/package-display')->with('error', 'Invalid package display design selected.');
        }

        $this->settingModel->setSetting(
            SettingModel::PACKAGE_DESIGN_KEY,
            $selectedDesign,
            'string',
            true
        );

        $this->activity->log(
            'admin.package_display_updated',
            (int) $admin['id'],
            null,
            null,
            'Updated package display design to ' . $selectedDesign,
            [
                'setting_key' => SettingModel::PACKAGE_DESIGN_KEY,
                'design'      => $selectedDesign,
            ]
        );

        return redirect()->to('/admin/package-display')->with('message', 'Package display design updated.');
    }
}

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
        $currentLayouts = $this->settingModel->getPackageDisplayLayouts();
        $currentCounts = $this->settingModel->getPackageDisplayCounts();

        // Keep valid choices available after validation or storage errors.
        $previousDesign = old('package_design', null, false);
        if (in_array($previousDesign, SettingModel::ALLOWED_PACKAGE_DESIGNS, true)) {
            $currentDesign = $previousDesign;
        }
        foreach (SettingModel::PACKAGE_DEVICE_LIMITS as $device => $max) {
            $previousLayout = old('package_layout_' . $device, null, false);
            if (in_array($previousLayout, SettingModel::ALLOWED_PACKAGE_LAYOUTS, true)) {
                $currentLayouts[$device] = $previousLayout;
            }
            foreach (SettingModel::PACKAGE_COUNT_PREFIXES as $mode => $prefix) {
                $previousCount = old('package_count_' . $mode . '_' . $device, null, false);
                $count = is_string($previousCount) ? filter_var($previousCount, FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1, 'max_range' => $max]]) : false;
                if ($count !== false) {
                    $currentCounts[$mode][$device] = $count;
                }
            }
        }

        return view('admin/package_display/index', [
            'title'         => 'Package Display',
            'description'   => 'Choose how programs and packages are presented on the public website.',
            'admin'         => $admin,
            'currentDesign' => $currentDesign,
            'currentLayouts' => $currentLayouts,
            'currentCounts' => $currentCounts,
        ]);
    }

    public function update()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $postedDesign = $this->request->getPost('package_design');
        $selectedDesign = is_string($postedDesign) ? trim($postedDesign) : '';

        if (! in_array($selectedDesign, SettingModel::ALLOWED_PACKAGE_DESIGNS, true)) {
            return redirect()->to('/admin/package-display')->withInput()->with('error', 'Invalid package display design selected.');
        }

        $layouts = [];
        foreach (SettingModel::PACKAGE_LAYOUT_KEYS as $device => $key) {
            $value = $this->request->getPost('package_layout_' . $device);
            if (!is_string($value) || !in_array($value, SettingModel::ALLOWED_PACKAGE_LAYOUTS, true)) {
                return redirect()->to('/admin/package-display')->withInput()->with('error', 'Select Grid or Carousel for each device.');
            }
            $layouts[$device] = $value;
        }

        $counts = [];
        foreach (SettingModel::PACKAGE_COUNT_PREFIXES as $mode => $prefix) {
            foreach (SettingModel::PACKAGE_DEVICE_LIMITS as $device => $max) {
                $posted = $this->request->getPost('package_count_' . $mode . '_' . $device);
                $value = is_string($posted) ? filter_var($posted, FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1, 'max_range' => $max]]) : false;
                if ($value === false) {
                    return redirect()->to('/admin/package-display')->withInput()->with('error', ucfirst($device) . ' card counts must be whole numbers between 1 and ' . $max . '.');
                }
                $counts[$mode][$device] = $value;
            }
        }

        if (!$this->settingModel->setPackageDisplay($selectedDesign, $layouts, $counts)) {
            return redirect()->to('/admin/package-display')->withInput()->with('error', 'Package display settings could not be saved. Please try again.');
        }

        $this->activity->log(
            'admin.package_display_updated',
            (int) $admin['id'],
            null,
            null,
            'Updated package display design, device layouts and card counts for ' . $selectedDesign,
            [
                'setting_key' => SettingModel::PACKAGE_DESIGN_KEY,
                'design'      => $selectedDesign,
                'layouts'     => $layouts,
                'counts'      => $counts,
            ]
        );

        return redirect()->to('/admin/package-display')->with('message', 'Package display settings updated.');
    }
}

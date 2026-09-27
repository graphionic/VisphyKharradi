<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Services\AdminActivityService;
use App\Services\AdminAuthService;
use App\Services\HtmlSanitizerService;

/**
 * LegalSettingsController — Admin Dynamic Content Management for Legal Pages
 *
 * Route: /admin/settings/legal
 * Purpose: Manage titles and rich HTML content for Privacy Policy, Terms & Conditions, and Refund Policy.
 */
class LegalSettingsController extends BaseController
{
    private AdminAuthService $authService;
    private AdminActivityService $activity;
    private SettingModel $settingModel;

    public const PAGES = [
        'privacy' => [
            'key_title'     => 'legal.privacy.title',
            'key_content'   => 'legal.privacy.content',
            'default_title' => 'Privacy Policy',
            'label'         => 'Privacy Policy',
        ],
        'terms' => [
            'key_title'     => 'legal.terms.title',
            'key_content'   => 'legal.terms.content',
            'default_title' => 'Terms & Conditions',
            'label'         => 'Terms & Conditions',
        ],
        'refund' => [
            'key_title'     => 'legal.refund.title',
            'key_content'   => 'legal.refund.content',
            'default_title' => 'Refund Policy',
            'label'         => 'Refund Policy',
        ],
    ];

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

        $tab = $this->request->getGet('tab');
        $activeTab = (is_string($tab) && array_key_exists($tab, self::PAGES)) ? $tab : 'privacy';

        $titles   = [];
        $contents = [];

        foreach (self::PAGES as $type => $config) {
            $rowTitle = $this->settingModel->where('setting_key', $config['key_title'])->first();
            $titles[$type] = trim((string) ($rowTitle['setting_value'] ?? $config['default_title']));

            $rowContent = $this->settingModel->where('setting_key', $config['key_content'])->first();
            $contents[$type] = (string) ($rowContent['setting_value'] ?? '');

            // Redisplay old input if returning from validation error
            $oldTab = old('page_type');
            if ($oldTab === $type) {
                $oldTitle = old('title');
                if (is_string($oldTitle)) {
                    $titles[$type] = $oldTitle;
                }
                $oldContent = old('content');
                if (is_string($oldContent)) {
                    $contents[$type] = $oldContent;
                }
            }
        }

        return view('admin/settings/legal', [
            'title'       => 'Legal Pages',
            'description' => 'Manage dynamic title and rich content for Privacy Policy, Terms & Conditions, and Refund Policy.',
            'admin'       => $admin,
            'pages'       => self::PAGES,
            'titles'      => $titles,
            'contents'    => $contents,
            'activeTab'   => $activeTab,
        ]);
    }

    public function update()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $pageType = trim((string) $this->request->getPost('page_type'));
        if (! array_key_exists($pageType, self::PAGES)) {
            return redirect()->to('/admin/settings/legal')->with('error', 'Invalid legal page tab selected.');
        }

        $pageConfig = self::PAGES[$pageType];
        $rawTitle   = trim((string) $this->request->getPost('title'));
        $rawContent = (string) $this->request->getPost('content');

        if ($rawTitle === '') {
            return redirect()->to('/admin/settings/legal?tab=' . $pageType)
                             ->withInput()
                             ->with('error', 'Page title is required for ' . $pageConfig['label'] . '.');
        }

        if (mb_strlen($rawTitle) > 255) {
            return redirect()->to('/admin/settings/legal?tab=' . $pageType)
                             ->withInput()
                             ->with('error', 'Page title must not exceed 255 characters.');
        }

        $cleanTitle = strip_tags($rawTitle);

        // Sanitize rich HTML using project's HtmlSanitizerService (HTMLPurifier fail-closed)
        $sanitizedContent = '';
        if (trim($rawContent) !== '') {
            try {
                $sanitizedContent = HtmlSanitizerService::sanitize($rawContent) ?? '';
            } catch (\Throwable $e) {
                log_message('error', 'LegalSettingsController HTML purification failed: ' . $e->getMessage());
                return redirect()->to('/admin/settings/legal?tab=' . $pageType)
                                 ->withInput()
                                 ->with('error', 'Content purification failed. Please check HTML tags or formatting.');
            }
        }

        // Save settings key-value pairs
        $savedTitle   = $this->settingModel->setSetting($pageConfig['key_title'], $cleanTitle, 'string', true);
        $savedContent = $this->settingModel->setSetting($pageConfig['key_content'], $sanitizedContent, 'text', true);

        if (! $savedTitle || ! $savedContent) {
            return redirect()->to('/admin/settings/legal?tab=' . $pageType)
                             ->withInput()
                             ->with('error', 'Failed to save settings for ' . $pageConfig['label'] . '. Please try again.');
        }

        // Log admin audit activity
        $this->activity->log(
            'admin.legal_settings_updated',
            (int) $admin['id'],
            'setting',
            null,
            'Updated ' . $pageConfig['label'] . ' legal page settings',
            [
                'page_type'   => $pageType,
                'key_title'   => $pageConfig['key_title'],
                'key_content' => $pageConfig['key_content'],
                'title'       => $cleanTitle,
            ]
        );

        return redirect()->to('/admin/settings/legal?tab=' . $pageType)
                         ->with('message', $pageConfig['label'] . ' updated successfully.');
    }
}

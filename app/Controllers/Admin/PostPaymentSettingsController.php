<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Services\AdminActivityService;
use App\Services\AdminAuthService;

/**
 * PostPaymentSettingsController — Admin Settings for Post-Payment Onboarding Destinations
 *
 * Route: /admin/settings/post-payment
 * Purpose: Manage Google Form URL, WhatsApp number, and default WhatsApp message template for checkout drawer success state.
 */
class PostPaymentSettingsController extends BaseController
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

        $googleFormUrl   = trim((string) ($this->settingModel->where('setting_key', 'post_payment.google_form_url')->first()['setting_value'] ?? ''));
        $whatsappNumber  = trim((string) ($this->settingModel->where('setting_key', 'post_payment.whatsapp_number')->first()['setting_value'] ?? ''));
        $whatsappMessage = (string) ($this->settingModel->where('setting_key', 'post_payment.whatsapp_message')->first()['setting_value'] ?? "Hi Visphy, I have completed payment for my order {order_number} ({program_name}). My name is {customer_name}.");

        return view('admin/settings/post_payment', [
            'title'           => 'Post-Payment Settings',
            'description'     => 'Configure onboarding destinations, Google Form link, and WhatsApp message for confirmed purchases.',
            'admin'           => $admin,
            'googleFormUrl'   => old('google_form_url', $googleFormUrl),
            'whatsappNumber'  => old('whatsapp_number', $whatsappNumber),
            'whatsappMessage' => old('whatsapp_message', $whatsappMessage),
        ]);
    }

    public function update()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $rawFormUrl  = trim((string) $this->request->getPost('google_form_url'));
        $rawWaNum    = trim((string) $this->request->getPost('whatsapp_number'));
        $rawWaMsg    = trim((string) $this->request->getPost('whatsapp_message'));

        if ($rawFormUrl !== '' && filter_var($rawFormUrl, FILTER_VALIDATE_URL) === false) {
            return redirect()->to('/admin/settings/post-payment')
                             ->withInput()
                             ->with('error', 'Please enter a valid HTTP or HTTPS URL for the Google Form.');
        }

        if ($rawWaNum !== '' && !preg_match('/^\+?[0-9\s\-()]{7,20}$/', $rawWaNum)) {
            return redirect()->to('/admin/settings/post-payment')
                             ->withInput()
                             ->with('error', 'Please enter a valid WhatsApp phone number.');
        }

        $savedForm = $this->settingModel->setSetting('post_payment.google_form_url', $rawFormUrl, 'url', true);
        $savedNum  = $this->settingModel->setSetting('post_payment.whatsapp_number', $rawWaNum, 'string', true);
        $savedMsg  = $this->settingModel->setSetting('post_payment.whatsapp_message', $rawWaMsg, 'text', true);

        if (!$savedForm || !$savedNum || !$savedMsg) {
            return redirect()->to('/admin/settings/post-payment')
                             ->withInput()
                             ->with('error', 'Failed to save post-payment settings. Please try again.');
        }

        $this->activity->log(
            'admin.post_payment_settings_updated',
            (int) $admin['id'],
            'setting',
            null,
            'Updated post-payment onboarding and WhatsApp settings',
            [
                'google_form_url' => $rawFormUrl,
                'whatsapp_number' => $rawWaNum,
            ]
        );

        return redirect()->to('/admin/settings/post-payment')
                         ->with('message', 'Post-payment settings updated successfully.');
    }
}

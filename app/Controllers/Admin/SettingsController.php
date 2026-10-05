<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Services\AdminActivityService;
use App\Services\AdminAuthService;
use App\Services\EncryptionService;

/**
 * SettingsController — Central Admin Settings Management
 *
 * Route: /admin/settings
 * Purpose: Manage General, Payment & Integrations, WhatsApp, SEO, and Legal settings in a unified hub.
 */
class SettingsController extends BaseController
{
    private AdminAuthService $authService;
    private AdminActivityService $activity;
    private SettingModel $settingModel;

    public const TABS = [
        'general'  => 'General',
        'payment'  => 'Payment & Integrations',
        'whatsapp' => 'WhatsApp',
        'seo'      => 'SEO Settings',
        'legal'    => 'Legal Pages',
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
        $activeTab = (is_string($tab) && array_key_exists($tab, self::TABS)) ? $tab : 'general';

        if ($activeTab === 'legal') {
            return redirect()->to('/admin/settings/legal');
        }

        // 1. General Settings
        $siteNameRow = $this->settingModel->where('setting_key', 'general.site_name')->first();
        if (empty($siteNameRow)) {
            $siteNameRow = $this->settingModel->where('setting_key', 'general.site_title')->first();
        }
        $siteName        = trim((string) ($siteNameRow['setting_value'] ?? 'Ftpreneur — Visphy Kharradi'));
        $logo            = trim((string) ($this->settingModel->where('setting_key', 'general.logo')->first()['setting_value'] ?? ''));
        $favicon         = trim((string) ($this->settingModel->where('setting_key', 'general.favicon')->first()['setting_value'] ?? ''));
        $contactPhone    = trim((string) ($this->settingModel->where('setting_key', 'general.contact_phone')->first()['setting_value'] ?? '+91 95742 93300'));
        $contactEmail    = trim((string) ($this->settingModel->where('setting_key', 'general.contact_email')->first()['setting_value'] ?? 'support@ftpreneur.com'));
        $generalWaNumber = trim((string) ($this->settingModel->where('setting_key', 'general.whatsapp_number')->first()['setting_value'] ?? ''));
        $address         = (string) ($this->settingModel->where('setting_key', 'general.address')->first()['setting_value'] ?? '');
        $businessHours   = trim((string) ($this->settingModel->where('setting_key', 'general.business_hours')->first()['setting_value'] ?? 'MON – SAT // 9:00 AM – 7:00 PM IST'));

        $websiteMode           = trim((string) ($this->settingModel->where('setting_key', 'general.website_mode')->first()['setting_value'] ?? 'live'));
        $websiteMode           = in_array($websiteMode, ['live', 'coming_soon'], true) ? $websiteMode : 'live';
        $comingSoonHeading     = trim((string) ($this->settingModel->where('setting_key', 'general.coming_soon_heading')->first()['setting_value'] ?? 'Something powerful is coming.'));
        $comingSoonMessage     = trim((string) ($this->settingModel->where('setting_key', 'general.coming_soon_message')->first()['setting_value'] ?? "We're preparing a better way to take control of your health, performance and well-being. Ftpreneur is launching soon."));
        $comingSoonShowContact = trim((string) ($this->settingModel->where('setting_key', 'general.coming_soon_show_contact')->first()['setting_value'] ?? 'enabled'));
        $comingSoonShowContact = in_array($comingSoonShowContact, ['enabled', 'disabled'], true) ? $comingSoonShowContact : 'enabled';

        // 2. Razorpay Credentials & Mode
        $razorpayMode         = trim((string) ($this->settingModel->where('setting_key', 'payment.razorpay.mode')->first()['setting_value'] ?? 'test'));
        $razorpayMode         = in_array($razorpayMode, ['test', 'live'], true) ? $razorpayMode : 'test';

        $razorpayTestKeyId    = trim((string) ($this->settingModel->where('setting_key', 'payment.razorpay.test.key_id')->first()['setting_value'] ?? ''));
        $hasRazorpayTestSecret  = !empty($this->settingModel->where('setting_key', 'payment.razorpay.test.key_secret_encrypted')->first()['setting_value'] ?? '');
        $hasRazorpayTestWebhook = !empty($this->settingModel->where('setting_key', 'payment.razorpay.test.webhook_secret_encrypted')->first()['setting_value'] ?? '');

        $razorpayLiveKeyId    = trim((string) ($this->settingModel->where('setting_key', 'payment.razorpay.live.key_id')->first()['setting_value'] ?? ''));
        $hasRazorpayLiveSecret  = !empty($this->settingModel->where('setting_key', 'payment.razorpay.live.key_secret_encrypted')->first()['setting_value'] ?? '');
        $hasRazorpayLiveWebhook = !empty($this->settingModel->where('setting_key', 'payment.razorpay.live.webhook_secret_encrypted')->first()['setting_value'] ?? '');

        // 3. Post-Payment Settings
        $googleFormUrl   = trim((string) ($this->settingModel->where('setting_key', 'post_payment.google_form_url')->first()['setting_value'] ?? ''));
        $whatsappNumber  = trim((string) ($this->settingModel->where('setting_key', 'post_payment.whatsapp_number')->first()['setting_value'] ?? ''));
        $whatsappMessage = (string) ($this->settingModel->where('setting_key', 'post_payment.whatsapp_message')->first()['setting_value'] ?? "Hi Visphy, I have completed payment for my order {order_number} ({program_name}). My name is {customer_name}.");

        // 4. WhatsApp API / Integration Settings
        $whatsappEnabled           = trim((string) ($this->settingModel->where('setting_key', 'whatsapp.enabled')->first()['setting_value'] ?? '0')) === '1';
        $whatsappProvider          = trim((string) ($this->settingModel->where('setting_key', 'whatsapp.provider')->first()['setting_value'] ?? 'Meta Cloud API'));
        $whatsappSenderNumber      = trim((string) ($this->settingModel->where('setting_key', 'whatsapp.sender_number')->first()['setting_value'] ?? ''));
        $whatsappPhoneNumberId     = trim((string) ($this->settingModel->where('setting_key', 'whatsapp.phone_number_id')->first()['setting_value'] ?? ''));
        $whatsappBusinessAccountId = trim((string) ($this->settingModel->where('setting_key', 'whatsapp.business_account_id')->first()['setting_value'] ?? ''));
        $hasWhatsappAccessToken    = !empty($this->settingModel->where('setting_key', 'whatsapp.api_access_token_encrypted')->first()['setting_value'] ?? '');
        $whatsappApiVersion        = trim((string) ($this->settingModel->where('setting_key', 'whatsapp.api_version')->first()['setting_value'] ?? 'v18.0'));

        // 5. SEO Settings (Landing Page)
        $seoTitleRow = $this->settingModel->where('setting_key', 'seo.home.title')->first();
        if (empty($seoTitleRow)) {
            $seoTitleRow = $this->settingModel->where('setting_key', 'seo.default_title')->first();
        }
        $seoTitle = trim((string) ($seoTitleRow['setting_value'] ?? "Ftpreneur — Visphy Kharradi's Nutrition, Strength & Disease Management Plan"));

        $seoDescRow = $this->settingModel->where('setting_key', 'seo.home.description')->first();
        if (empty($seoDescRow)) {
            $seoDescRow = $this->settingModel->where('setting_key', 'seo.meta_description')->first();
        }
        $seoMetaDesc = trim((string) ($seoDescRow['setting_value'] ?? 'Transform your health through scientific nutrition, strength training, and lifestyle disease management with Visphy Kharradi.'));

        $seoKeywords = trim((string) ($this->settingModel->where('setting_key', 'seo.home.keywords')->first()['setting_value'] ?? 'visphy kharradi, ftpreneur, nutrition, strength training, disease management, pune'));

        $seoCanonicalRow = $this->settingModel->where('setting_key', 'seo.home.canonical')->first();
        if (empty($seoCanonicalRow)) {
            $seoCanonicalRow = $this->settingModel->where('setting_key', 'seo.canonical_url')->first();
        }
        $seoCanonical = trim((string) ($seoCanonicalRow['setting_value'] ?? base_url()));

        $seoRobots = trim((string) ($this->settingModel->where('setting_key', 'seo.home.robots')->first()['setting_value'] ?? 'index, follow'));

        $seoOgTitle = trim((string) ($this->settingModel->where('setting_key', 'seo.home.og_title')->first()['setting_value'] ?? $seoTitle));
        $seoOgDesc  = trim((string) ($this->settingModel->where('setting_key', 'seo.home.og_description')->first()['setting_value'] ?? $seoMetaDesc));
        
        $seoOgImgRow = $this->settingModel->where('setting_key', 'seo.home.og_image')->first();
        if (empty($seoOgImgRow)) {
            $seoOgImgRow = $this->settingModel->where('setting_key', 'seo.og_image')->first();
        }
        $seoOgImage = trim((string) ($seoOgImgRow['setting_value'] ?? ''));

        $seoTwitterTitle = trim((string) ($this->settingModel->where('setting_key', 'seo.home.twitter_title')->first()['setting_value'] ?? $seoTitle));
        $seoTwitterDesc  = trim((string) ($this->settingModel->where('setting_key', 'seo.home.twitter_description')->first()['setting_value'] ?? $seoMetaDesc));
        $seoTwitterImage = trim((string) ($this->settingModel->where('setting_key', 'seo.home.twitter_image')->first()['setting_value'] ?? ''));

        return view('admin/settings/index', [
            'title'                     => 'Central Settings',
            'description'               => 'Manage general business details, brand assets, Razorpay gateway credentials, post-payment onboarding, WhatsApp API integration, SEO defaults, and legal page policies.',
            'admin'                     => $admin,
            'activeTab'                 => $activeTab,
            'tabs'                      => self::TABS,
            // General
            'siteName'                  => old('site_name', $siteName),
            'logo'                      => $logo,
            'favicon'                   => $favicon,
            'contactPhone'              => old('contact_phone', $contactPhone),
            'contactEmail'              => old('contact_email', $contactEmail),
            'generalWaNumber'           => old('general_whatsapp_number', $generalWaNumber),
            'address'                   => old('address', $address),
            'businessHours'             => old('business_hours', $businessHours),
            'websiteMode'               => old('website_mode', $websiteMode),
            'comingSoonHeading'         => old('coming_soon_heading', $comingSoonHeading),
            'comingSoonMessage'         => old('coming_soon_message', $comingSoonMessage),
            'comingSoonShowContact'     => old('coming_soon_show_contact', $comingSoonShowContact),
            // Razorpay
            'razorpayMode'              => old('razorpay_mode', $razorpayMode),
            'razorpayTestKeyId'         => old('razorpay_test_key_id', $razorpayTestKeyId),
            'hasRazorpayTestSecret'     => $hasRazorpayTestSecret,
            'hasRazorpayTestWebhook'    => $hasRazorpayTestWebhook,
            'razorpayLiveKeyId'         => old('razorpay_live_key_id', $razorpayLiveKeyId),
            'hasRazorpayLiveSecret'     => $hasRazorpayLiveSecret,
            'hasRazorpayLiveWebhook'    => $hasRazorpayLiveWebhook,
            'webhookUrl'                => base_url('payment/webhook'),
            // Post-Payment
            'googleFormUrl'             => old('google_form_url', $googleFormUrl),
            'whatsappNumber'            => old('whatsapp_number', $whatsappNumber),
            'whatsappMessage'           => old('whatsapp_message', $whatsappMessage),
            // WhatsApp API Integration
            'whatsappEnabled'           => old('whatsapp_enabled', $whatsappEnabled ? '1' : '0') === '1',
            'whatsappProvider'          => old('whatsapp_provider', $whatsappProvider),
            'whatsappSenderNumber'      => old('whatsapp_sender_number', $whatsappSenderNumber),
            'whatsappPhoneNumberId'     => old('whatsapp_phone_number_id', $whatsappPhoneNumberId),
            'whatsappBusinessAccountId' => old('whatsapp_business_account_id', $whatsappBusinessAccountId),
            'hasWhatsappAccessToken'    => $hasWhatsappAccessToken,
            'whatsappApiVersion'        => old('whatsapp_api_version', $whatsappApiVersion),
            // SEO
            'seoTitle'                  => old('seo_title', $seoTitle),
            'seoMetaDesc'               => old('seo_meta_description', $seoMetaDesc),
            'seoKeywords'               => old('seo_keywords', $seoKeywords),
            'seoCanonical'              => old('seo_canonical_url', $seoCanonical),
            'seoRobots'                 => old('seo_robots', $seoRobots),
            'seoOgTitle'                => old('seo_og_title', $seoOgTitle),
            'seoOgDesc'                 => old('seo_og_description', $seoOgDesc),
            'seoOgImage'                => $seoOgImage,
            'seoTwitterTitle'           => old('seo_twitter_title', $seoTwitterTitle),
            'seoTwitterDesc'            => old('seo_twitter_description', $seoTwitterDesc),
            'seoTwitterImage'           => $seoTwitterImage,
        ]);
    }

    public function update()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $tab = trim((string) $this->request->getPost('tab'));
        if (!array_key_exists($tab, self::TABS)) {
            return redirect()->to('/admin/settings')->with('error', 'Invalid settings tab.');
        }

        if ($tab === 'general') {
            $siteName        = trim((string) $this->request->getPost('site_name'));
            $contactPhone    = trim((string) $this->request->getPost('contact_phone'));
            $contactEmail    = trim((string) $this->request->getPost('contact_email'));
            $generalWaNumber = trim((string) $this->request->getPost('general_whatsapp_number'));
            $address               = trim((string) $this->request->getPost('address'));
            $businessHours         = trim((string) $this->request->getPost('business_hours'));

            $websiteMode           = trim((string) $this->request->getPost('website_mode'));
            $websiteMode           = in_array($websiteMode, ['live', 'coming_soon'], true) ? $websiteMode : 'live';

            $comingSoonHeading     = trim((string) $this->request->getPost('coming_soon_heading'));
            if ($comingSoonHeading === '') {
                $comingSoonHeading = 'Something powerful is coming.';
            }

            $comingSoonMessage     = trim((string) $this->request->getPost('coming_soon_message'));
            if ($comingSoonMessage === '') {
                $comingSoonMessage = "We're preparing a better way to take control of your health, performance and well-being. Ftpreneur is launching soon.";
            }

            $comingSoonShowContact = $this->request->getPost('coming_soon_show_contact') === 'disabled' ? 'disabled' : 'enabled';

            if ($siteName === '') {
                return redirect()->to('/admin/settings?tab=general')->withInput()->with('error', 'Business / Site name is required.');
            }

            if ($contactEmail !== '' && filter_var($contactEmail, FILTER_VALIDATE_EMAIL) === false) {
                return redirect()->to('/admin/settings?tab=general')->withInput()->with('error', 'Please enter a valid email address.');
            }

            // Save site_name and consolidate site_title into site_name
            $this->settingModel->setSetting('general.site_name', $siteName, 'string', true);
            $this->settingModel->setSetting('general.site_title', $siteName, 'string', true);
            $this->settingModel->setSetting('general.contact_phone', $contactPhone, 'string', true);
            $this->settingModel->setSetting('general.contact_email', $contactEmail, 'string', true);
            $this->settingModel->setSetting('general.whatsapp_number', $generalWaNumber, 'string', true);
            $this->settingModel->setSetting('general.address', $address, 'text', true);
            $this->settingModel->setSetting('general.business_hours', $businessHours, 'string', true);

            // Save Website Status & Coming Soon settings
            $this->settingModel->setSetting('general.website_mode', $websiteMode, 'string', true);
            $this->settingModel->setSetting('general.coming_soon_heading', $comingSoonHeading, 'string', true);
            $this->settingModel->setSetting('general.coming_soon_message', $comingSoonMessage, 'text', true);
            $this->settingModel->setSetting('general.coming_soon_show_contact', $comingSoonShowContact, 'string', true);

            // Handle Logo File Upload / Removal
            if ($this->request->getPost('remove_logo') === '1') {
                $oldLogo = $this->settingModel->where('setting_key', 'general.logo')->first()['setting_value'] ?? '';
                if (!empty($oldLogo) && file_exists(FCPATH . $oldLogo)) {
                    @unlink(FCPATH . $oldLogo);
                }
                $this->settingModel->setSetting('general.logo', '', 'string', true);
            } else {
                $logoFile = $this->request->getFile('logo_file');
                if ($logoFile && $logoFile->isValid() && !$logoFile->hasMoved()) {
                    $allowedTypes = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/webp', 'image/svg+xml'];
                    $allowedExts  = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
                    $mime = $logoFile->getMimeType();
                    $ext  = strtolower($logoFile->getClientExtension());

                    if ($logoFile->getSize() > 2 * 1024 * 1024) {
                        return redirect()->to('/admin/settings?tab=general')->withInput()->with('error', 'Logo file size must not exceed 2MB.');
                    }
                    if (!in_array($mime, $allowedTypes, true) || !in_array($ext, $allowedExts, true)) {
                        return redirect()->to('/admin/settings?tab=general')->withInput()->with('error', 'Invalid logo image format. Allowed: PNG, JPG, WEBP, SVG.');
                    }

                    $targetDir = FCPATH . 'uploads/settings/';
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0755, true);
                    }

                    $newName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $logoFile->move($targetDir, $newName);

                    $oldLogo = $this->settingModel->where('setting_key', 'general.logo')->first()['setting_value'] ?? '';
                    if (!empty($oldLogo) && file_exists(FCPATH . $oldLogo)) {
                        @unlink(FCPATH . $oldLogo);
                    }
                    $this->settingModel->setSetting('general.logo', 'uploads/settings/' . $newName, 'string', true);
                }
            }

            // Handle Favicon File Upload / Removal
            if ($this->request->getPost('remove_favicon') === '1') {
                $oldFavicon = $this->settingModel->where('setting_key', 'general.favicon')->first()['setting_value'] ?? '';
                if (!empty($oldFavicon) && file_exists(FCPATH . $oldFavicon)) {
                    @unlink(FCPATH . $oldFavicon);
                }
                $this->settingModel->setSetting('general.favicon', '', 'string', true);
            } else {
                $faviconFile = $this->request->getFile('favicon_file');
                if ($faviconFile && $faviconFile->isValid() && !$faviconFile->hasMoved()) {
                    $allowedTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml', 'image/webp'];
                    $allowedExts  = ['ico', 'png', 'svg', 'webp'];
                    $mime = $faviconFile->getMimeType();
                    $ext  = strtolower($faviconFile->getClientExtension());

                    if ($faviconFile->getSize() > 1024 * 1024) {
                        return redirect()->to('/admin/settings?tab=general')->withInput()->with('error', 'Favicon file size must not exceed 1MB.');
                    }
                    if (!in_array($mime, $allowedTypes, true) || !in_array($ext, $allowedExts, true)) {
                        return redirect()->to('/admin/settings?tab=general')->withInput()->with('error', 'Invalid favicon image format. Allowed: ICO, PNG, SVG, WEBP.');
                    }

                    $targetDir = FCPATH . 'uploads/settings/';
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0755, true);
                    }

                    $newName = 'favicon_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $faviconFile->move($targetDir, $newName);

                    $oldFavicon = $this->settingModel->where('setting_key', 'general.favicon')->first()['setting_value'] ?? '';
                    if (!empty($oldFavicon) && file_exists(FCPATH . $oldFavicon)) {
                        @unlink(FCPATH . $oldFavicon);
                    }
                    $this->settingModel->setSetting('general.favicon', 'uploads/settings/' . $newName, 'string', true);
                }
            }

            $this->activity->log('admin.settings_general_updated', (int) $admin['id'], 'setting', null, 'Updated General Settings');
            return redirect()->to('/admin/settings?tab=general')->with('message', 'General settings updated successfully.');

        } elseif ($tab === 'payment') {
            // Active Mode
            $mode = trim((string) $this->request->getPost('razorpay_mode'));
            $mode = in_array($mode, ['test', 'live'], true) ? $mode : 'test';
            $this->settingModel->setSetting('payment.razorpay.mode', $mode, 'string', true);

            // Test Credentials
            $testKeyId = trim((string) $this->request->getPost('razorpay_test_key_id'));
            $this->settingModel->setSetting('payment.razorpay.test.key_id', $testKeyId, 'string', true);

            $testSecretRaw = trim((string) $this->request->getPost('razorpay_test_key_secret'));
            if ($testSecretRaw !== '') {
                $encryptedTestSecret = EncryptionService::encrypt($testSecretRaw);
                $this->settingModel->setSetting('payment.razorpay.test.key_secret_encrypted', $encryptedTestSecret, 'string', true);
            }

            $testWebhookRaw = trim((string) $this->request->getPost('razorpay_test_webhook_secret'));
            if ($testWebhookRaw !== '') {
                $encryptedTestWebhook = EncryptionService::encrypt($testWebhookRaw);
                $this->settingModel->setSetting('payment.razorpay.test.webhook_secret_encrypted', $encryptedTestWebhook, 'string', true);
            }

            // Live Credentials
            $liveKeyId = trim((string) $this->request->getPost('razorpay_live_key_id'));
            $this->settingModel->setSetting('payment.razorpay.live.key_id', $liveKeyId, 'string', true);

            $liveSecretRaw = trim((string) $this->request->getPost('razorpay_live_key_secret'));
            if ($liveSecretRaw !== '') {
                $encryptedLiveSecret = EncryptionService::encrypt($liveSecretRaw);
                $this->settingModel->setSetting('payment.razorpay.live.key_secret_encrypted', $encryptedLiveSecret, 'string', true);
            }

            $liveWebhookRaw = trim((string) $this->request->getPost('razorpay_live_webhook_secret'));
            if ($liveWebhookRaw !== '') {
                $encryptedLiveWebhook = EncryptionService::encrypt($liveWebhookRaw);
                $this->settingModel->setSetting('payment.razorpay.live.webhook_secret_encrypted', $encryptedLiveWebhook, 'string', true);
            }

            // Post-Payment Settings
            $rawFormUrl  = trim((string) $this->request->getPost('google_form_url'));
            $rawWaNum    = trim((string) $this->request->getPost('whatsapp_number'));
            $rawWaMsg    = trim((string) $this->request->getPost('whatsapp_message'));

            if ($rawFormUrl !== '' && filter_var($rawFormUrl, FILTER_VALIDATE_URL) === false) {
                return redirect()->to('/admin/settings?tab=payment')->withInput()->with('error', 'Please enter a valid HTTP or HTTPS URL for the Google Form.');
            }

            $this->settingModel->setSetting('post_payment.google_form_url', $rawFormUrl, 'url', true);
            $this->settingModel->setSetting('post_payment.whatsapp_number', $rawWaNum, 'string', true);
            $this->settingModel->setSetting('post_payment.whatsapp_message', $rawWaMsg, 'text', true);

            $this->activity->log('admin.settings_payment_updated', (int) $admin['id'], 'setting', null, 'Updated Payment & Integration Settings');
            return redirect()->to('/admin/settings?tab=payment')->with('message', 'Payment & integration settings updated successfully.');

        } elseif ($tab === 'whatsapp') {
            $waEnabled           = $this->request->getPost('whatsapp_enabled') === '1' ? '1' : '0';
            $waProvider          = trim((string) $this->request->getPost('whatsapp_provider'));
            $waSenderNumber      = trim((string) $this->request->getPost('whatsapp_sender_number'));
            $waPhoneNumberId     = trim((string) $this->request->getPost('whatsapp_phone_number_id'));
            $waBusinessAccountId = trim((string) $this->request->getPost('whatsapp_business_account_id'));
            $waApiVersion        = trim((string) $this->request->getPost('whatsapp_api_version'));
            $waAccessTokenRaw    = trim((string) $this->request->getPost('whatsapp_api_access_token'));

            $this->settingModel->setSetting('whatsapp.enabled', $waEnabled, 'boolean', true);
            $this->settingModel->setSetting('whatsapp.provider', $waProvider !== '' ? $waProvider : 'Meta Cloud API', 'string', true);
            $this->settingModel->setSetting('whatsapp.sender_number', $waSenderNumber, 'string', true);
            $this->settingModel->setSetting('whatsapp.phone_number_id', $waPhoneNumberId, 'string', true);
            $this->settingModel->setSetting('whatsapp.business_account_id', $waBusinessAccountId, 'string', true);
            $this->settingModel->setSetting('whatsapp.api_version', $waApiVersion !== '' ? $waApiVersion : 'v18.0', 'string', true);

            if ($waAccessTokenRaw !== '') {
                $encryptedToken = EncryptionService::encrypt($waAccessTokenRaw);
                $this->settingModel->setSetting('whatsapp.api_access_token_encrypted', $encryptedToken, 'string', true);
            }

            $this->activity->log('admin.settings_whatsapp_updated', (int) $admin['id'], 'setting', null, 'Updated WhatsApp API Integration Settings');
            return redirect()->to('/admin/settings?tab=whatsapp')->with('message', 'WhatsApp API integration settings updated successfully.');

        } elseif ($tab === 'seo') {
            $seoTitle        = trim((string) $this->request->getPost('seo_title'));
            $seoMetaDesc     = trim((string) $this->request->getPost('seo_meta_description'));
            $seoKeywords     = trim((string) $this->request->getPost('seo_keywords'));
            $seoCanonical    = trim((string) $this->request->getPost('seo_canonical_url'));
            $seoRobots       = trim((string) $this->request->getPost('seo_robots'));

            $seoOgTitle      = trim((string) $this->request->getPost('seo_og_title'));
            $seoOgDesc       = trim((string) $this->request->getPost('seo_og_description'));

            $seoTwitterTitle = trim((string) $this->request->getPost('seo_twitter_title'));
            $seoTwitterDesc  = trim((string) $this->request->getPost('seo_twitter_description'));

            if ($seoTitle === '') {
                return redirect()->to('/admin/settings?tab=seo')->withInput()->with('error', 'Meta title is required.');
            }

            // Save SEO basics and consolidate legacy keys
            $this->settingModel->setSetting('seo.home.title', $seoTitle, 'string', true);
            $this->settingModel->setSetting('seo.default_title', $seoTitle, 'string', true);

            $this->settingModel->setSetting('seo.home.description', $seoMetaDesc, 'text', true);
            $this->settingModel->setSetting('seo.meta_description', $seoMetaDesc, 'text', true);

            $this->settingModel->setSetting('seo.home.keywords', $seoKeywords, 'string', true);

            $this->settingModel->setSetting('seo.home.canonical', $seoCanonical, 'url', true);
            $this->settingModel->setSetting('seo.canonical_url', $seoCanonical, 'url', true);

            $validRobots = ['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'];
            $robotsVal = in_array($seoRobots, $validRobots, true) ? $seoRobots : 'index, follow';
            $this->settingModel->setSetting('seo.home.robots', $robotsVal, 'string', true);

            // Open Graph
            $this->settingModel->setSetting('seo.home.og_title', $seoOgTitle, 'string', true);
            $this->settingModel->setSetting('seo.home.og_description', $seoOgDesc, 'text', true);

            // Handle OG Image upload / removal
            if ($this->request->getPost('remove_og_image') === '1') {
                $oldOg = $this->settingModel->where('setting_key', 'seo.home.og_image')->first()['setting_value'] ?? '';
                if (!empty($oldOg) && file_exists(FCPATH . $oldOg)) {
                    @unlink(FCPATH . $oldOg);
                }
                $this->settingModel->setSetting('seo.home.og_image', '', 'string', true);
                $this->settingModel->setSetting('seo.og_image', '', 'string', true);
            } else {
                $ogFile = $this->request->getFile('og_image_file');
                if ($ogFile && $ogFile->isValid() && !$ogFile->hasMoved()) {
                    $allowedTypes = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/webp'];
                    $allowedExts  = ['png', 'jpg', 'jpeg', 'webp'];
                    $mime = $ogFile->getMimeType();
                    $ext  = strtolower($ogFile->getClientExtension());

                    if ($ogFile->getSize() > 2 * 1024 * 1024) {
                        return redirect()->to('/admin/settings?tab=seo')->withInput()->with('error', 'Open Graph image file size must not exceed 2MB.');
                    }
                    if (!in_array($mime, $allowedTypes, true) || !in_array($ext, $allowedExts, true)) {
                        return redirect()->to('/admin/settings?tab=seo')->withInput()->with('error', 'Invalid Open Graph image format. Allowed: PNG, JPG, WEBP.');
                    }

                    $targetDir = FCPATH . 'uploads/settings/';
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0755, true);
                    }

                    $newName = 'og_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $ogFile->move($targetDir, $newName);

                    $oldOg = $this->settingModel->where('setting_key', 'seo.home.og_image')->first()['setting_value'] ?? '';
                    if (!empty($oldOg) && file_exists(FCPATH . $oldOg)) {
                        @unlink(FCPATH . $oldOg);
                    }
                    $relPath = 'uploads/settings/' . $newName;
                    $this->settingModel->setSetting('seo.home.og_image', $relPath, 'string', true);
                    $this->settingModel->setSetting('seo.og_image', $relPath, 'string', true);
                }
            }

            // Twitter / X
            $this->settingModel->setSetting('seo.home.twitter_title', $seoTwitterTitle, 'string', true);
            $this->settingModel->setSetting('seo.home.twitter_description', $seoTwitterDesc, 'text', true);

            // Handle Twitter Image upload / removal
            if ($this->request->getPost('remove_twitter_image') === '1') {
                $oldTw = $this->settingModel->where('setting_key', 'seo.home.twitter_image')->first()['setting_value'] ?? '';
                if (!empty($oldTw) && file_exists(FCPATH . $oldTw)) {
                    @unlink(FCPATH . $oldTw);
                }
                $this->settingModel->setSetting('seo.home.twitter_image', '', 'string', true);
            } else {
                $twFile = $this->request->getFile('twitter_image_file');
                if ($twFile && $twFile->isValid() && !$twFile->hasMoved()) {
                    $allowedTypes = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/webp'];
                    $allowedExts  = ['png', 'jpg', 'jpeg', 'webp'];
                    $mime = $twFile->getMimeType();
                    $ext  = strtolower($twFile->getClientExtension());

                    if ($twFile->getSize() > 2 * 1024 * 1024) {
                        return redirect()->to('/admin/settings?tab=seo')->withInput()->with('error', 'Twitter/X image file size must not exceed 2MB.');
                    }
                    if (!in_array($mime, $allowedTypes, true) || !in_array($ext, $allowedExts, true)) {
                        return redirect()->to('/admin/settings?tab=seo')->withInput()->with('error', 'Invalid Twitter/X image format. Allowed: PNG, JPG, WEBP.');
                    }

                    $targetDir = FCPATH . 'uploads/settings/';
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0755, true);
                    }

                    $newName = 'twitter_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $twFile->move($targetDir, $newName);

                    $oldTw = $this->settingModel->where('setting_key', 'seo.home.twitter_image')->first()['setting_value'] ?? '';
                    if (!empty($oldTw) && file_exists(FCPATH . $oldTw)) {
                        @unlink(FCPATH . $oldTw);
                    }
                    $this->settingModel->setSetting('seo.home.twitter_image', 'uploads/settings/' . $newName, 'string', true);
                }
            }

            $this->activity->log('admin.settings_seo_updated', (int) $admin['id'], 'setting', null, 'Updated SEO Settings');
            return redirect()->to('/admin/settings?tab=seo')->with('message', 'SEO settings updated successfully.');
        }

        return redirect()->to('/admin/settings');
    }
}

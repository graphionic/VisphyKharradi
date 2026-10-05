<?php

namespace App\Filters;

use App\Models\SettingModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Intercepts public traffic when Website Mode is set to 'coming_soon'.
 * Preserves access to /admin, /payment/webhook, and static assets.
 */
class WebsiteModeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $params = null)
    {
        try {
            $settingModel = new SettingModel();
            $modeRow = $settingModel->where('setting_key', 'general.website_mode')->first();
            $mode = trim((string) ($modeRow['setting_value'] ?? 'live'));
        } catch (\Throwable $e) {
            $mode = 'live'; // Safe fallback
        }

        if ($mode !== 'coming_soon') {
            return; // Live mode — proceed normally
        }

        // Check URI path for exempt routes
        $uri = $request->getUri();
        $path = trim($uri->getPath(), '/');

        if ($path === 'admin' 
            || str_starts_with($path, 'admin/') 
            || $path === 'payment/webhook' 
            || $path === 'webhook'
            || str_starts_with($path, 'assets/') 
            || str_starts_with($path, 'uploads/') 
            || $path === 'favicon.ico' 
            || $path === 'robots.txt'
        ) {
            return; // Exempt route — proceed
        }

        // Handle AJAX / JSON requests during Coming Soon mode
        if ($request->isAJAX() || (method_exists($request, 'hasHeader') && $request->hasHeader('Accept') && str_contains($request->getHeaderLine('Accept'), 'application/json'))) {
            $response = Services::response();
            return $response->setStatusCode(503)->setJSON([
                'status'  => 'coming_soon',
                'message' => 'The website is currently in Coming Soon mode. Public operations are disabled.'
            ]);
        }

        // Retrieve Coming Soon configuration
        $comingSoonHeadingRow = $settingModel->where('setting_key', 'general.coming_soon_heading')->first();
        $comingSoonHeading    = trim((string) ($comingSoonHeadingRow['setting_value'] ?? 'Something powerful is coming.'));
        if ($comingSoonHeading === '') {
            $comingSoonHeading = 'Something powerful is coming.';
        }

        $comingSoonMessageRow = $settingModel->where('setting_key', 'general.coming_soon_message')->first();
        $comingSoonMessage    = trim((string) ($comingSoonMessageRow['setting_value'] ?? "We're preparing a better way to take control of your health, performance and well-being. Ftpreneur is launching soon."));
        if ($comingSoonMessage === '') {
            $comingSoonMessage = "We're preparing a better way to take control of your health, performance and well-being. Ftpreneur is launching soon.";
        }

        $comingSoonShowContactRow = $settingModel->where('setting_key', 'general.coming_soon_show_contact')->first();
        $comingSoonShowContact    = trim((string) ($comingSoonShowContactRow['setting_value'] ?? 'enabled'));

        $siteNameRow = $settingModel->where('setting_key', 'general.site_name')->first();
        $siteName    = trim((string) ($siteNameRow['setting_value'] ?? 'Ftpreneur — Visphy Kharradi'));

        $logoRow = $settingModel->where('setting_key', 'general.logo')->first();
        $logo    = trim((string) ($logoRow['setting_value'] ?? 'assets/frontend/images/ftpreneur-logo.png'));
        if ($logo === '' || !file_exists(FCPATH . $logo)) {
            $logo = 'assets/frontend/images/ftpreneur-logo.png';
        }

        $whatsappNumber = trim((string) ($settingModel->where('setting_key', 'general.whatsapp_number')->first()['setting_value'] ?? ''));
        $contactPhone   = trim((string) ($settingModel->where('setting_key', 'general.contact_phone')->first()['setting_value'] ?? ''));
        $contactEmail   = trim((string) ($settingModel->where('setting_key', 'general.contact_email')->first()['setting_value'] ?? ''));

        $html = view('frontend/pages/coming_soon', [
            'siteName'              => $siteName,
            'logo'                  => $logo,
            'comingSoonHeading'     => $comingSoonHeading,
            'comingSoonMessage'     => $comingSoonMessage,
            'comingSoonShowContact' => $comingSoonShowContact,
            'whatsappNumber'        => $whatsappNumber,
            'contactPhone'          => $contactPhone,
            'contactEmail'          => $contactEmail,
        ]);

        $response = Services::response();
        return $response->setStatusCode(200)
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setBody($html);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $params = null)
    {
    }
}

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdminAuthService;

/**
 * BrandGuidelinesController — Admin preview for approved Brand Guidelines
 *
 * Route: /admin/brand-guidelines (authenticated)
 * Purpose: isolated preview of docs/brand-guidelines/ (reference only, not prod theme)
 *
 * Security:
 * - adminAuth filter required (routes)
 * - in-method re-check via AdminAuthService::getCurrentAdmin()
 * - no public route, no arbitrary file/path param, no traversal
 * - only fixed guideline files are rendered (whitelist + ROOTPATH)
 *
 * Isolation: guideline CSS/JS served via dedicated whitelisted endpoints
 * and rendered inside iframe so Admin Design System cannot leak.
 */
class BrandGuidelinesController extends BaseController
{
    private const GUIDELINE_HTML = ROOTPATH . 'docs/brand-guidelines/index.html';
    private const GUIDELINE_CSS  = ROOTPATH . 'docs/brand-guidelines/css/styles.css';
    private const GUIDELINE_JS   = ROOTPATH . 'docs/brand-guidelines/js/main.js';

    public function index()
    {
        $auth = new AdminAuthService();
        $admin = $auth->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        return view('admin/brand_guidelines/index', [
            'title' => 'Brand Guidelines — Ftpreneur Admin',
            'admin' => $admin,
        ]);
    }

    /**
     * Isolated frame: returns the raw guideline HTML with rewritten asset paths
     * pointing to whitelisted admin routes. Admin CSS is NOT included here.
     */
    public function frame()
    {
        $auth = new AdminAuthService();
        $admin = $auth->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $path = self::GUIDELINE_HTML;
        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Brand guideline not found');
        }

        $html = file_get_contents($path);
        if ($html === false) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Brand guideline unavailable');
        }

        // Rewrite relative asset refs to authenticated admin endpoints
        // Original: href="css/styles.css"  src="js/main.js"
        // Rewritten: href="/admin/brand-guidelines/css/styles.css" etc via site_url()
        $cssUrl = site_url('admin/brand-guidelines/css/styles.css');
        $jsUrl  = site_url('admin/brand-guidelines/js/main.js');

        // Replace only the guideline's own refs, tolerate optional leading ./ 
        $html = str_replace('href="css/styles.css"', 'href="' . $cssUrl . '"', $html);
        $html = str_replace("href='css/styles.css'", "href='" . $cssUrl . "'", $html);
        $html = str_replace('href="./css/styles.css"', 'href="' . $cssUrl . '"', $html);

        $html = str_replace('src="js/main.js"', 'src="' . $jsUrl . '"', $html);
        $html = str_replace("src='js/main.js'", "src='" . $jsUrl . "'", $html);
        $html = str_replace('src="./js/main.js"', 'src="' . $jsUrl . '"', $html);

        // Security: prevent guideline HTML from framing admin (we ARE framing it intentionally)
        // Frame itself should not be indexable
        $response = service('response');
        $response->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        // Allow external fonts/images required by guideline (already uses Google Fonts + Pexels)
        $response->setBody($html);
        return $response;
    }

    /**
     * Serve guideline CSS — whitelisted, no path parameter, no traversal.
     */
    public function css()
    {
        $auth = new AdminAuthService();
        $admin = $auth->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $path = self::GUIDELINE_CSS;
        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Brand guideline CSS not found');
        }

        $content = file_get_contents($path);
        $response = service('response');
        $response->setHeader('Content-Type', 'text/css; charset=UTF-8');
        $response->setHeader('Cache-Control', 'public, max-age=3600');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setBody($content !== false ? $content : '');
        return $response;
    }

    /**
     * Serve guideline JS — whitelisted, no path parameter, no traversal.
     */
    public function js()
    {
        $auth = new AdminAuthService();
        $admin = $auth->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $path = self::GUIDELINE_JS;
        if (!is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Brand guideline JS not found');
        }

        $content = file_get_contents($path);
        $response = service('response');
        $response->setHeader('Content-Type', 'application/javascript; charset=UTF-8');
        $response->setHeader('Cache-Control', 'public, max-age=3600');
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setBody($content !== false ? $content : '');
        return $response;
    }
}

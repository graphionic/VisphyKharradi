<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SecurityHeaders Filter — Phase 1 Baseline
 *
 * Sends minimal, safe headers that do NOT interfere with future Razorpay
 * integration. Full CSP with frame-src/script-src for checkout.razorpay.com
 * will be finalized in Phase 10 (payment integration).
 *
 * Ownership: CodeIgniter filter is primary. public/.htaccess provides
 * fallback for nosniff / frame-options if filter is bypassed, but filter
 * wins (no duplicate conflicting values).
 *
 * Phase 1 headers:
 *  - X-Content-Type-Options: nosniff
 *  - X-Frame-Options: SAMEORIGIN (clickjacking protection)
 *  - Referrer-Policy: strict-origin-when-cross-origin
 *  - Permissions-Policy: camera=(), microphone=(), geolocation=()
 *  - X-XSS-Protection: 0 (disable legacy XSS auditor — modern CSP is preferred)
 *
 * CSP and HSTS are NOT sent in Phase 1 to avoid breaking localhost HTTP or
 * future Razorpay frames. They will be added per SECURITY_ARCHITECTURE.md
 * during hardening (Phase 11) and Razorpay phase (Phase 10).
 */
class SecurityHeaders implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // No-op before — headers are set after controller compiles response.
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do not override if already set by controller.
        if (! $response->hasHeader('X-Content-Type-Options')) {
            $response->setHeader('X-Content-Type-Options', 'nosniff');
        }
        if (! $response->hasHeader('X-Frame-Options')) {
            $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        }
        if (! $response->hasHeader('Referrer-Policy')) {
            $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        if (! $response->hasHeader('Permissions-Policy')) {
            $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        }
        // Explicitly disable legacy XSS filter (modern browsers ignore it, but we are explicit)
        if (! $response->hasHeader('X-XSS-Protection')) {
            $response->setHeader('X-XSS-Protection', '0');
        }

        return $response;
    }
}

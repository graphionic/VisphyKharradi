<?php

namespace App\Services;

/**
 * HtmlSanitizerService — Phase 5D + 5E.2C
 * Server-side sanitization for rich text full_description using HTMLPurifier.
 * Allowlist: p, br, strong, b, em, i, u, h2, h3, ul, ol, li, blockquote, a (http/https)
 * 5E.2C: Fail-closed handling — never return raw on purifier failure; log without body.
 */
class HtmlSanitizerService
{
    private static ?\HTMLPurifier $purifier = null;

    /**
     * Test-only deterministic failure simulation.
     * Must NOT be triggerable via HTTP — only direct static assignment in testing env.
     */
    public static bool $simulateFailure = false;

    public static function getPurifier(): \HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }
        $config = \HTMLPurifier_Config::createDefault();

        // Core must be set before HTML definitions
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

        // Cache path writable — set early
        $cache = WRITEPATH . 'htmlpurifier';
        if (!is_dir($cache)) {
            @mkdir($cache, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cache);

        // Allow only safe elements and attributes
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,h2,h3,ul,ol,li,blockquote,a[href|target|rel|title]');
        // Allow only http/https for href
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        // Do not allow javascript/data etc. — handled via AllowedSchemes
        $config->set('HTML.TargetBlank', false);
        $config->set('URI.MakeAbsolute', false);
        $config->set('AutoFormat.RemoveEmpty', false);
        // Forbid dangerous elements (script etc. not in Allowed anyway, but extra)
        $config->set('HTML.ForbiddenElements', ['script','style','iframe','object','embed','form','input','button','link','meta','base','applet','frame','frameset']);
        // Disable external resources? Keep false to allow http links but not resources
        $config->set('URI.DisableExternalResources', false);
        $config->set('URI.DisableResources', false);
        // Remove style attributes — not allowed via Allowed, so no need ForbiddenAttributes
        // Ensure no extra CSS
        $config->set('CSS.AllowTricky', false);
        $config->set('CSS.AllowedProperties', []);

        self::$purifier = new \HTMLPurifier($config);
        return self::$purifier;
    }

    /**
     * Sanitize HTML, returns safe HTML string or null.
     * Fail-closed: on any HTMLPurifier exception, logs without body and throws.
     * Never returns raw input on failure.
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }
        $html = trim($html);
        if (mb_strlen($html) > 20000) {
            $html = mb_substr($html, 0, 20000);
        }

        // Deterministic test-only failure simulation — not remotely triggerable.
        // Guard: only honored when ENVIRONMENT === 'testing'.
        if (self::$simulateFailure) {
            $env = defined('ENVIRONMENT') ? ENVIRONMENT : (getenv('CI_ENVIRONMENT') ?: 'production');
            $isTesting = $env === 'testing' || getenv('CI_ENVIRONMENT') === 'testing';
            if ($isTesting) {
                $sim = new \RuntimeException('Simulated HTMLPurifier failure for testing');
                // Log without body — enough to diagnose, no raw content
                log_message('error', 'HtmlSanitizerService purification failed (simulated): ' . get_class($sim) . ' code ' . $sim->getCode());
                // Throw safe generic message — caller must not expose details or body
                throw new \RuntimeException('HTML purification temporarily unavailable. Please try again.', 0, $sim);
            }
            // Outside testing, ignore flag but warn (do not throw)
            log_message('warning', 'HtmlSanitizerService simulateFailure flag ignored outside testing environment');
        }

        try {
            $purifier = self::getPurifier();
            $clean = $purifier->purify($html);
            $clean = trim((string)$clean);
            if ($clean === '') {
                return '';
            }
            // Defense in depth: strip any remaining javascript:/data: and on* that somehow survived
            if (stripos($clean, 'javascript:') !== false || stripos($clean, 'data:') !== false) {
                $clean = preg_replace('/\s*(href|src)\s*=\s*[\"\']?\s*(javascript|data):[\"\']?[^\"\']*[\"\']?/i', '', $clean) ?? $clean;
            }
            $clean = preg_replace('/\s+on\w+\s*=\s*[\"\'][^\"\']*[\"\']|\s+on\w+\s*=\s*[^\s>]+/i', '', $clean) ?? $clean;

            return $clean;
        } catch (\Throwable $e) {
            // Already a simulated failure with our generic message? Re-throw as-is to preserve fail-closed
            // If it's our own generic RuntimeException from simulation, it already has safe message
            if ($e->getMessage() === 'HTML purification temporarily unavailable. Please try again.' && $e->getPrevious() !== null) {
                throw $e;
            }
            // Log without body, without file paths or body, with class/basename only
            $file = basename($e->getFile());
            $msgSnippet = substr($e->getMessage(), 0, 180);
            // Ensure snippet doesn't contain raw html body by stripping tags? But we limit length and don't include $html anyway
            // Do not log $html
            log_message('error', 'HtmlSanitizerService purification failed: ' . get_class($e) . ' code ' . $e->getCode() . ' at ' . $file . ':' . $e->getLine() . ' msg=' . $msgSnippet);
            // Throw safe generic — never return raw
            throw new \RuntimeException('HTML purification temporarily unavailable. Please try again.', 0, $e);
        }
    }

    /**
     * Determine if sanitized HTML is logically empty (no meaningful user content).
     * Checks sanitized result, not raw input. Treats Quill empty output (<p><br></p>),
     * whitespace-only, empty paragraphs, br-only, and equivalent empty formatting as empty.
     * Meaningful content (text, headings with text, list items with text, blockquotes, links with visible text) is NOT empty.
     */
    public static function isLogicallyEmpty(?string $sanitizedHtml): bool
    {
        if ($sanitizedHtml === null) {
            return true;
        }
        $html = trim($sanitizedHtml);
        if ($html === '') {
            return true;
        }
        // Decode entities so &nbsp; and numeric entities become visible whitespace
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Replace UTF-8 NBSP with regular space for trimming
        $decoded = str_replace("\xC2\xA0", ' ', $decoded);
        // Strip all allowed tags (p,br,strong etc.) to get visible text
        $text = strip_tags($decoded);
        // Trim typical whitespace
        $text = trim($text);
        if ($text === '') {
            return true;
        }
        // Unicode whitespace-only check
        if (preg_match('/^\s*$/u', $text) === 1) {
            return true;
        }
        return false;
    }

    public static function reset(): void
    {
        self::$purifier = null;
    }
}

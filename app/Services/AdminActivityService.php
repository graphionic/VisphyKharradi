<?php

namespace App\Services;

use App\Models\AdminActivityLogModel;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * AdminActivityService — Phase 3
 *
 * Centralized audit logging for admin security actions.
 * Ensures no secrets are logged and respects Zero-FK (no FK).
 */
class AdminActivityService
{
    private AdminActivityLogModel $logModel;

    public function __construct(?AdminActivityLogModel $logModel = null)
    {
        $this->logModel = $logModel ?? new AdminActivityLogModel();
    }

    /**
     * Log an admin activity.
     *
     * @param string      $action     e.g. admin.login_success, admin.login_failed, admin.logout, admin.password_changed
     * @param int|null    $adminId    logical reference, may be null for failed login where admin_id unknown
     * @param string|null $entityType
     * @param int|null    $entityId
     * @param string|null $description Human readable, no secrets
     * @param array|null  $metadata   Will be JSON encoded, sanitized, truncated
     */
    public function log(
        string $action,
        ?int $adminId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $metadata = null
    ): void {
        // Sanitize metadata: never allow secrets
        if ($metadata !== null) {
            $metadata = $this->sanitizeMetadata($metadata);
            $metadataJson = json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            // Truncate to avoid oversized TEXT (though TEXT is large)
            if ($metadataJson !== false && strlen($metadataJson) > 4000) {
                $metadataJson = substr($metadataJson, 0, 4000);
            }
        } else {
            $metadataJson = null;
        }

        $request = service('request');
        $ip = null;
        $userAgent = null;

        // getIPAddress respects Config\App::$proxyIPs — safe by default (empty = trust REMOTE_ADDR only)
        if ($request instanceof IncomingRequest) {
            try {
                $ip = $request->getIPAddress();
                // Validate IP format, truncate 45 chars for IPv6
                if ($ip !== null && strlen($ip) > 45) {
                    $ip = substr($ip, 0, 45);
                }
                // Limit IP to reasonable length, fallback null on invalid
                if ($ip !== null && filter_var($ip, FILTER_VALIDATE_IP) === false) {
                    $ip = null;
                }
            } catch (\Throwable $e) {
                $ip = null;
            }
            try {
                $ua = $request->getUserAgent();
                if ($ua !== null) {
                    $userAgent = (string) $request->getServer('HTTP_USER_AGENT');
                    if ($userAgent !== '') {
                        $userAgent = substr($userAgent, 0, 500);
                    } else {
                        $userAgent = null;
                    }
                }
            } catch (\Throwable $e) {
                $userAgent = null;
            }
        }

        // Truncate description
        if ($description !== null && strlen($description) > 500) {
            $description = substr($description, 0, 500);
        }

        // Truncate action
        if (strlen($action) > 50) {
            $action = substr($action, 0, 50);
        }

        $data = [
            'admin_id'    => $adminId,
            'action'      => $action,
            'entity_type' => $entityType !== null ? substr($entityType, 0, 50) : null,
            'entity_id'   => $entityId,
            'description' => $description,
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
            'metadata'    => $metadataJson,
        ];

        // Use skipValidation? Model has validation but we trust internal
        try {
            $this->logModel->insert($data);
        } catch (\Throwable $e) {
            // Never fail main flow due to logging failure; log to error log
            log_message('error', 'AdminActivityService log failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove sensitive keys from metadata, ensure no secrets leak.
     */
    private function sanitizeMetadata(array $metadata): array
    {
        $forbiddenKeys = [
            'password',
            'password_hash',
            'current_password',
            'new_password',
            'confirm_password',
            'csrf_ftpreneur_token',
            'csrf_token_name',
            'ftpreneur_csrf',
            'session_id',
            'cookie',
            'secret',
            'keySecret',
            'webhookSecret',
            'razorpay_signature',
        ];

        $clean = [];
        foreach ($metadata as $k => $v) {
            $lower = strtolower((string) $k);
            $isForbidden = false;
            foreach ($forbiddenKeys as $fk) {
                if (str_contains($lower, strtolower($fk))) {
                    $isForbidden = true;
                    break;
                }
            }
            if ($isForbidden) {
                continue;
            }
            // Truncate string values
            if (is_string($v) && strlen($v) > 500) {
                $v = substr($v, 0, 500);
            }
            // Only allow scalar/array, no objects
            if (is_scalar($v) || is_array($v) || $v === null) {
                $clean[$k] = $v;
            }
        }

        return $clean;
    }
}

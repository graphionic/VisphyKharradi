<?php

namespace App\Services;

use App\Models\PackageFeatureModel;
use App\Models\PackageGalleryImageModel;
use App\Models\PackageModel;

/**
 * PackageService — Phase 5A listing foundation
 *
 * Handles admin package list orchestration: filters, sorting, pagination,
 * and efficient feature counts (no N+1, no FK).
 */
class PackageService
{
    private PackageModel $packageModel;
    private PackageFeatureModel $featureModel;
    private PackageGalleryImageModel $galleryModel;

    public const PER_PAGE = 15;
    public const MAX_SEARCH_LENGTH = 190;

    public const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
    public const MAX_GALLERY_COUNT = 10;
    public const ALLOWED_IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public const MAX_IMAGE_WIDTH = 6000;
    public const MAX_IMAGE_HEIGHT = 6000;
    public const MAX_IMAGE_PIXELS = 25000000; // 25 MP
    public const ALLOWED_IMAGE_EXTS = ['jpg','jpeg','png','webp'];

    /** Whitelisted sort keys → [column, direction] */
    private const SORT_MAP = [
        'display_order' => ['display_order', 'ASC'],
        'newest'        => ['created_at', 'DESC'],
        'oldest'        => ['created_at', 'ASC'],
        'name_asc'      => ['name', 'ASC'],
        'name_desc'     => ['name', 'DESC'],
        'price_low'     => ['selling_price', 'ASC'],
        'price_high'    => ['selling_price', 'DESC'],
    ];

    public function __construct(?PackageModel $packageModel = null, ?PackageFeatureModel $featureModel = null, ?PackageGalleryImageModel $galleryModel = null)
    {
        $this->packageModel = $packageModel ?? new PackageModel();
        $this->featureModel = $featureModel ?? new PackageFeatureModel();
        $this->galleryModel = $galleryModel ?? new PackageGalleryImageModel();
    }

    /**
     * Get paginated admin package list.
     *
     * @param array $params ['q'=>?string,'status'=>?string,'sort'=>?string,'page'=>?int,'perPage'=>?int]
     * @return array{packages: array, pager: \CodeIgniter\Pager\Pager|null, total: int, perPage: int, currentPage: int, featureCounts: array<int,int>}
     */
    public function getAdminPackageList(array $params = []): array
    {
        $q = $this->normalizeSearch($params['q'] ?? null);
        $status = $this->normalizeStatus($params['status'] ?? null);
        [$sortCol, $sortDir] = $this->normalizeSort($params['sort'] ?? null);
        $perPage = $this->normalizePerPage($params['perPage'] ?? null);
        $page = max(1, (int) ($params['page'] ?? 1));

        $builder = $this->packageModel->builder();

        // Soft delete is handled by Model, but builder needs explicit where deleted_at IS NULL
        // PackageModel::builder does NOT auto-apply soft delete; we use Model query methods instead.
        // So we will use Model's where/like and paginate via Model.

        $model = $this->packageModel;
        // Reset model state
        $model->where('packages.deleted_at IS NULL', null, false);

        if ($status !== 'all') {
            $model->where('is_active', $status === 'active' ? 1 : 0);
        }

        if ($q !== '') {
            $model->groupStart()
                ->like('name', $q, 'both', null, true)
                ->orLike('slug', $q, 'both', null, true)
                ->orLike('short_description', $q, 'both', null, true)
                ->groupEnd();
        }

        // Apply sorting with deterministic secondary
        $model->orderBy($sortCol, $sortDir);
        // Ensure deterministic order when primary column not unique
        if ($sortCol !== 'display_order') {
            $model->orderBy('display_order', 'ASC');
        }
        $model->orderBy('id', 'ASC');

        // Pagination — CI4 Model::paginate expects Pager in service('pager')
        // We use manual limit/offset to keep control and preserve query params in controller.
        $total = $model->countAllResults(false); // false = don't reset builder

        $offset = ($page - 1) * $perPage;
        $packages = $model->findAll($perPage, $offset);

        // Feature counts — single aggregated query (no N+1), logical reference only
        $featureCounts = $this->getFeatureCounts(array_column($packages, 'id'));
        $optionCounts = $this->getOptionCounts(array_column($packages, 'id'));

        // Build pager manually for view — preserve query params
        $pager = service('pager');
        $pager->store('packages', $page, $perPage, $total, 1);

        return [
            'packages'       => $packages,
            'pager'          => $pager,
            'total'          => $total,
            'perPage'        => $perPage,
            'currentPage'    => $page,
            'featureCounts'  => $featureCounts,
            'optionCounts'   => $optionCounts,
            'filters'        => [
                'q'      => $q,
                'status' => $status,
                'sort'   => array_search([$sortCol, $sortDir], self::SORT_MAP, true) ?: 'display_order',
            ],
        ];
    }

    /**
     * Efficient feature counts for given package ids.
     * @param int[] $packageIds
     * @return array<int,int> package_id => count
     */
    public function getFeatureCounts(array $packageIds): array
    {
        if ($packageIds === []) {
            return [];
        }
        $packageIds = array_map('intval', $packageIds);
        $rows = $this->featureModel
            ->select('package_id, COUNT(*) as cnt')
            ->whereIn('package_id', $packageIds)
            ->where('is_active', 1)
            ->groupBy('package_id')
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['package_id']] = (int) $row['cnt'];
        }
        // Ensure every requested id has entry (0 if none)
        foreach ($packageIds as $id) {
            $map[$id] = $map[$id] ?? 0;
        }
        return $map;
    }

    public function normalizeSearch(?string $q): string
    {
        if ($q === null) return '';
        $q = trim($q);
        if (mb_strlen($q) > self::MAX_SEARCH_LENGTH) {
            $q = mb_substr($q, 0, self::MAX_SEARCH_LENGTH);
        }
        return $q;
    }

    public function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        if (in_array($status, ['active', 'inactive'], true)) {
            return $status;
        }
        return 'all';
    }

    /**
     * @return array{0:string,1:string}
     */
    public function normalizeSort(?string $sort): array
    {
        $sort = strtolower(trim((string) $sort));
        return self::SORT_MAP[$sort] ?? self::SORT_MAP['display_order'];
    }

    public function normalizePerPage($perPage): int
    {
        $perPage = (int) $perPage;
        if ($perPage < 1 || $perPage > 100) {
            return self::PER_PAGE;
        }
        return $perPage;
    }

    public function getSortMap(): array
    {
        return self::SORT_MAP;
    }

    public function getSortOptions(): array
    {
        return [
            'display_order' => 'Display Order',
            'newest'        => 'Newest',
            'oldest'        => 'Oldest',
            'name_asc'      => 'Name A–Z',
            'name_desc'     => 'Name Z–A',
            'price_low'     => 'Price Low–High',
            'price_high'    => 'Price High–Low',
        ];
    }

    /**
     * Badge presentation mapping — prevents arbitrary DB content as CSS class.
     */
    public static function badgeLabel(?string $badge): ?string
    {
        if ($badge === null || trim($badge) === '' || strtolower(trim($badge)) === 'none') {
            return null;
        }
        $map = [
            'popular'     => 'Popular',
            'recommended' => 'Recommended',
            'best_value'  => 'Best Value',
            'best-value'  => 'Best Value',
        ];
        $key = strtolower(trim($badge));
        if (isset($map[$key])) {
            return $map[$key];
        }
        // For custom badge, escape and title-case safely (no CSS class injection)
        // Limit length
        $clean = trim($badge);
        if (mb_strlen($clean) > 50) $clean = mb_substr($clean, 0, 50);
        // Allow only safe characters, but return label escaped in view
        return $clean;
    }

    public static function formatPrice($value): string
    {
        // $value is DECIMAL string, e.g. "12450.00" or "9999.5"
        // Remove trailing .00 if not meaningful, then Indian formatting with ₹
        $num = (float) $value;
        // Format without decimals if .00, else 2 decimals
        if (fmod($num, 1) === 0.0) {
            return '₹' . number_format($num, 0, '.', ',');
        }
        // Keep 2 decimals but trim trailing zero? For stored DECIMAL, keep 2 if needed
        $formatted = number_format($num, 2, '.', ',');
        // Remove .00 already handled, so keep as is
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        // If we trimmed to integer, reformat without decimals
        if (strpos($formatted, '.') === false) {
            return '₹' . $formatted;
        }
        return '₹' . $formatted;
    }

    public static function formatDuration(?int $value, ?string $unit): string
    {
        if (empty($value) || empty($unit)) {
            return '—';
        }
        $unit = strtolower(trim($unit));
        $value = (int) $value;
        // Handle pluralization
        $map = [
            'day'    => $value === 1 ? 'Day' : 'Days',
            'days'   => $value === 1 ? 'Day' : 'Days',
            'week'   => $value === 1 ? 'Week' : 'Weeks',
            'weeks'  => $value === 1 ? 'Week' : 'Weeks',
            'month'  => $value === 1 ? 'Month' : 'Months',
            'months' => $value === 1 ? 'Month' : 'Months',
            'year'   => $value === 1 ? 'Year' : 'Years',
            'years'  => $value === 1 ? 'Year' : 'Years',
        ];
        $label = $map[$unit] ?? ucfirst($unit);
        return $value . ' ' . $label;
    }

    /**
     * Vanilla slugify — used for JS suggestion and server normalization.
     */
    public static function slugify(string $text): string
    {
        $text = trim($text);
        // Transliterate basic
        $text = strtolower($text);
        // Replace non-alphanumeric with hyphen
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');
        // Collapse multiple hyphens
        $text = preg_replace('/-+/', '-', $text) ?? '';
        if (mb_strlen($text) < 2) {
            // Ensure minimal length; fallback to uniq
            $text = $text . '-package';
            $text = trim($text, '-');
        }
        if (mb_strlen($text) > 190) {
            $text = mb_substr($text, 0, 190);
            $text = rtrim($text, '-');
        }
        return $text;
    }

    /**
     * Validate Google Form URL — only https + exact hosts docs.google.com / forms.gle
     */
    public static function isValidGoogleFormUrl(?string $url): bool
    {
        if ($url === null || trim($url) === '') {
            return true; // optional
        }
        $url = trim($url);
        if (mb_strlen($url) > 2048) return false;
        // Must be valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
        $parts = parse_url($url);
        if ($parts === false) return false;
        // Scheme must be https
        if (!isset($parts['scheme']) || strtolower($parts['scheme']) !== 'https') return false;
        // Host must be exactly allowed
        if (!isset($parts['host'])) return false;
        $host = strtolower($parts['host']);
        // Reject userinfo
        if (isset($parts['user']) || isset($parts['pass'])) return false;
        $allowed = ['docs.google.com', 'forms.gle'];
        if (!in_array($host, $allowed, true)) return false;
        // Reject deceptive subdomains already handled by exact match, but also ensure no port tricks? Allow default port but host must be exact
        // Additional check: host must not contain extra dots beyond allowed
        // Already exact, so fine.
        return true;
    }

    /**
     * Validate WhatsApp template — plain text, allowed placeholders only
     */
    public static function isValidWhatsappTemplate(?string $template): bool
    {
        if ($template === null || trim($template) === '') return true;
        $template = trim($template);
        if (mb_strlen($template) > 1000) return false; // generous
        // Disallow wa.me URL? spec says never wa.me URL — but template is plain text, so if contains wa.me, reject?
        // We will reject if contains "wa.me" substring case-insensitive
        if (stripos($template, 'wa.me') !== false) return false;
        // Find all placeholders {xxx}
        if (preg_match_all('/\{([^}]+)\}/', $template, $matches)) {
            $allowed = ['customer_name', 'package_name', 'order_number'];
            foreach ($matches[1] as $placeholder) {
                $ph = trim($placeholder);
                if (!in_array($ph, $allowed, true)) {
                    return false;
                }
            }
        }
        // Also reject unknown placeholder patterns like {{ or unbalanced?
        // Ensure no stray { or } without proper placeholder? For simplicity, if contains { and not matched above, it's ok if it's not placeholder? But we already check all {...} must be allowed, so stray { without } will fail later? We consider template with {unknown} as invalid (already handled).
        // Also reject if contains <script> or html tags? Plain text should not contain < >? We allow but will escape on output; but we should reject if contains < > to prevent XSS? However, we will escape on output, so allow but validation will pass. We rely on escaping.
        return true;
    }

    /**
     * Validate badge — optional, max 50, safe chars
     */
    public static function isValidBadge(?string $badge): bool
    {
        if ($badge === null || trim($badge) === '') return true;
        $badge = trim($badge);
        if (mb_strlen($badge) > 50) return false;
        // Allow exact values popular/recommended/best_value/best-value/none plus custom alphanumeric + space/hyphen/underscore
        // For custom, we allow letters numbers space hyphen underscore only, to prevent XSS injection via badge
        if (!preg_match('/^[A-Za-z0-9 _\-]+$/', $badge)) return false;
        // Also allow whitelist mapping? We already allow those via regex, so any of them will pass.
        // If badge is one of allowed, it's valid; if custom but matches regex, also valid.
        // Invalid example: badge with <script> would fail regex.
        return true;
    }

    /**
     * Validate create payload — returns [errors array, normalized data]
     * Errors is associative field => message
     */
    public function validateCreate(array $input, ?int $excludeId = null): array
    {
        $errors = [];
        $data = [];

        // Normalize inputs — hardening: malformed arrays must not generate warnings
        $safeString = static function($v): string {
            if (is_array($v) || is_object($v)) return '';
            if ($v === null) return '';
            return trim((string)$v);
        };
        $name = $safeString($input['name'] ?? '');
        $slug = $safeString($input['slug'] ?? '');
        $shortDesc = $safeString($input['short_description'] ?? '');
        $fullDesc = $safeString($input['full_description'] ?? '');
        // Prices as string, trim — reject arrays, scientific notation etc. via regex later
        $regularPriceRaw = $safeString($input['regular_price'] ?? '');
        $sellingPriceRaw = $safeString($input['selling_price'] ?? '');
        $durationValueRaw = $safeString($input['duration_value'] ?? '');
        $durationUnit = strtolower($safeString($input['duration_unit'] ?? ''));
        $ctaLabel = $safeString($input['cta_label'] ?? '');
        $googleUrl = $safeString($input['google_form_url'] ?? '');
        $whatsapp = $safeString($input['whatsapp_template'] ?? '');
        $badgeRaw = $safeString($input['badge'] ?? '');
        $displayOrderRaw = $safeString($input['display_order'] ?? '');
        // Checkboxes: is_active, is_featured — deterministically normalize, arrays → 0, no warnings
        $normalizeBool = static function($v): int {
            if (is_array($v) || is_object($v)) return 0;
            if ($v === '1' || $v === 'on' || $v === 'ON' || $v === 'On' || $v === 1 || $v === true) return 1;
            // Strict: string "true", "yes", "-1" etc. are 0
            return 0;
        };
        $isActive = isset($input['is_active']) ? $normalizeBool($input['is_active']) : 0;
        $isFeatured = isset($input['is_featured']) ? $normalizeBool($input['is_featured']) : 0;

        // Features: expect features[] array — hardening: nested-array tampering must not warn
        $featuresRaw = $input['features'] ?? $input['features_text'] ?? $input['feature_text'] ?? [];
        if (!is_array($featuresRaw)) {
            // If scalar, wrap; if object, treat as empty
            if (is_object($featuresRaw)) $featuresRaw = [];
            else $featuresRaw = [$featuresRaw];
        }
        // Normalize features: each element may be string or array with feature_text
        $features = [];
        $safeStr = static function($v): string {
            if (is_array($v) || is_object($v)) return '';
            if ($v === null) return '';
            return trim((string)$v);
        };
        foreach ($featuresRaw as $f) {
            if (is_array($f)) {
                // Only allow known keys, nested arrays become empty string
                $maybe = $f['feature_text'] ?? $f['text'] ?? '';
                $text = $safeStr($maybe);
            } else {
                $text = $safeStr($f);
            }
            $features[] = $text;
        }
        // If features came as single string with commas? No, we treat as array.

        // ---- Validate name ----
        if ($name === '') {
            $errors['name'] = 'Package name is required.';
        } elseif (mb_strlen($name) < 3) {
            $errors['name'] = 'Package name must be at least 3 characters.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Package name must not exceed 150 characters.';
        }
        $data['name'] = $name;

        // ---- Slug ----
        // Auto-generate if empty? But spec says Slug * required, so must be provided. However, we support auto-generation via JS, but server must require.
        // Normalize slug: lowercase, trim, replace spaces? But we validate.
        $slugNorm = strtolower($slug);
        // If slug empty but name provided, we could auto-generate, but spec says server validation authoritative and slug must be unique URL-safe, so we should generate if empty? To be safe, if slug empty and name not empty, generate.
        if ($slugNorm === '' && $name !== '') {
            $slugNorm = self::slugify($name);
        }
        if ($slugNorm === '') {
            $errors['slug'] = 'Slug is required.';
        } elseif (mb_strlen($slugNorm) < 2) {
            $errors['slug'] = 'Slug must be at least 2 characters.';
        } elseif (mb_strlen($slugNorm) > 190) {
            $errors['slug'] = 'Slug must not exceed 190 characters.';
        } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugNorm)) {
            $errors['slug'] = 'Slug may contain only lowercase letters, numbers and hyphens, and must not start or end with hyphen.';
        } else {
            // Uniqueness check excluding current package for edit (safe, id compare, no SQL concat)
            $builder = $this->packageModel->where('slug', $slugNorm)->withDeleted();
            if ($excludeId !== null) {
                $builder->where('id !=', $excludeId);
            }
            $existing = $builder->first();
            if ($existing !== null) {
                $errors['slug'] = 'Slug is already in use.';
            }
        }
        $data['slug'] = $slugNorm;

        // Short description
        if (mb_strlen($shortDesc) > 300) {
            $errors['short_description'] = 'Short description must not exceed 300 characters.';
        }
        $data['short_description'] = $shortDesc === '' ? null : $shortDesc;

        // Full description — rich text sanitized server-side (5E.2C fail-closed + empty normalization)
        $sanitizationFailed = false;
        if ($fullDesc !== '') {
            try {
                $sanitized = \App\Services\HtmlSanitizerService::sanitize($fullDesc);
                // After sanitization, check length (strip tags for text length? but we check HTML length)
                if ($sanitized !== null && mb_strlen($sanitized) > 20000) {
                    $errors['full_description'] = 'Full description is too long after sanitization.';
                }
                // Normalize logically empty sanitized HTML to NULL (Quill empty, whitespace, br-only, etc.)
                // Determine emptiness from sanitized result, not unsafe raw
                if ($sanitized !== null && \App\Services\HtmlSanitizerService::isLogicallyEmpty($sanitized)) {
                    $sanitized = null;
                } elseif ($sanitized === '' && trim($fullDesc) !== '') {
                    // All content was stripped (e.g., script only) — treat as empty but not error
                    $sanitized = null;
                }
                $data['full_description'] = $sanitized;
                // Also store sanitized back for validation redisplay safety
                $fullDesc = $sanitized ?? '';
            } catch (\Throwable $e) {
                // Fail closed — never store raw, never expose details, log without body (service already logged)
                log_message('error', 'PackageService full_description sanitization failed: ' . get_class($e) . ' code ' . $e->getCode());
                $errors['full_description'] = 'Full description could not be processed. Please try again or use plain text.';
                $data['full_description'] = null;
                $fullDesc = '';
                $sanitizationFailed = true;
            }
        } else {
            $data['full_description'] = null;
        }
        // Plain length check before sanitization already handled via sanitized length; also ensure original not too long
        // Do not overwrite sanitization failure error
        if (!$sanitizationFailed && mb_strlen((string)($input['full_description'] ?? '')) > 20000) {
            $errors['full_description'] = 'Full description is too long.';
        }

        // Regular price
        if ($regularPriceRaw === '') {
            $errors['regular_price'] = 'Regular price is required.';
        } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $regularPriceRaw)) {
            $errors['regular_price'] = 'Regular price must be a valid amount with up to 2 decimals.';
        } elseif (\bccomp($regularPriceRaw, '0', 2) < 0) {
            $errors['regular_price'] = 'Regular price must be 0 or greater.';
        } elseif (\bccomp($regularPriceRaw, '99999999.99', 2) > 0) {
            $errors['regular_price'] = 'Regular price is too large.';
        }
        // Selling price
        if ($sellingPriceRaw === '') {
            $errors['selling_price'] = 'Selling price is required.';
        } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $sellingPriceRaw)) {
            $errors['selling_price'] = 'Selling price must be a valid amount with up to 2 decimals.';
        } elseif (\bccomp($sellingPriceRaw, '0', 2) < 0) {
            $errors['selling_price'] = 'Selling price must be 0 or greater.';
        } elseif (\bccomp($sellingPriceRaw, '99999999.99', 2) > 0) {
            $errors['selling_price'] = 'Selling price is too large.';
        }

        // If both prices valid, check selling <= regular — DECIMAL-safe, no float
        if (!isset($errors['regular_price']) && !isset($errors['selling_price'])) {
            // Normalize to 2 decimals without float: pad/truncate
            $toTwoDecimals = static function(string $val): string {
                $val = trim($val);
                if ($val === '') return '0.00';
                // Reject scientific notation already via regex, so safe
                if (strpos($val, '.') === false) {
                    return $val . '.00';
                }
                [$intPart, $decPart] = explode('.', $val, 2);
                $decPart = substr(str_pad($decPart, 2, '0'), 0, 2);
                // Remove leading zeros from int part but keep at least 1
                $intPart = ltrim($intPart, '0');
                if ($intPart === '') $intPart = '0';
                return $intPart . '.' . $decPart;
            };
            $reg = $toTwoDecimals($regularPriceRaw);
            $sell = $toTwoDecimals($sellingPriceRaw);
            if (\bccomp($sell, $reg, 2) > 0) {
                $errors['selling_price'] = 'Selling price must not exceed regular price.';
            }
            $data['regular_price'] = $reg;
            $data['selling_price'] = $sell;
        } else {
            $data['regular_price'] = $regularPriceRaw;
            $data['selling_price'] = $sellingPriceRaw;
        }

        // Duration value
        if ($durationValueRaw === '') {
            $errors['duration_value'] = 'Duration value is required.';
        } elseif (!ctype_digit($durationValueRaw) || (int)$durationValueRaw <= 0) {
            $errors['duration_value'] = 'Duration value must be a positive integer.';
        } elseif ((int)$durationValueRaw > 1000) {
            $errors['duration_value'] = 'Duration value is too large.';
        }
        $data['duration_value'] = $durationValueRaw === '' ? null : (int)$durationValueRaw;

        // Duration unit
        $allowedUnits = ['days', 'weeks', 'months'];
        if ($durationUnit === '') {
            $errors['duration_unit'] = 'Duration unit is required.';
        } elseif (!in_array($durationUnit, $allowedUnits, true)) {
            $errors['duration_unit'] = 'Duration unit must be Days, Weeks or Months.';
        }
        $data['duration_unit'] = $durationUnit;

        // CTA label
        if ($ctaLabel === '') {
            $ctaLabel = 'Get Started';
        }
        if (mb_strlen($ctaLabel) > 50) {
            $errors['cta_label'] = 'CTA label must not exceed 50 characters.';
        }
        $data['cta_label'] = $ctaLabel;

        // Google Form URL
        if ($googleUrl !== '' && !self::isValidGoogleFormUrl($googleUrl)) {
            $errors['google_form_url'] = 'Google Form URL must be a valid HTTPS URL on docs.google.com or forms.gle.';
        }
        $data['google_form_url'] = $googleUrl === '' ? null : $googleUrl;

        // WhatsApp template
        if ($whatsapp !== '' && !self::isValidWhatsappTemplate($whatsapp)) {
            $errors['whatsapp_template'] = 'WhatsApp template contains an invalid placeholder. Allowed: {customer_name}, {package_name}, {order_number}.';
        }
        if (mb_strlen($whatsapp) > 1000) {
            $errors['whatsapp_template'] = 'WhatsApp template is too long.';
        }
        $data['whatsapp_template'] = $whatsapp === '' ? null : $whatsapp;

        // Badge
        if ($badgeRaw !== '' && !self::isValidBadge($badgeRaw)) {
            $errors['badge'] = 'Badge is invalid.';
        }
        // Normalize badge: empty or 'none' => null
        $badgeNorm = strtolower(trim($badgeRaw));
        if ($badgeNorm === '' || $badgeNorm === 'none') {
            $data['badge'] = null;
        } else {
            $data['badge'] = trim($badgeRaw);
            if (mb_strlen($data['badge']) > 50) $data['badge'] = mb_substr($data['badge'], 0, 50);
        }

        // Display order — hardening: arrays must not default silently
        $rawDisplayOrder = $input['display_order'] ?? null;
        if (is_array($rawDisplayOrder) || is_object($rawDisplayOrder)) {
            $errors['display_order'] = 'Display order must be a non-negative integer.';
            $data['display_order'] = 100;
        } else {
            if ($displayOrderRaw === '') {
                $displayOrderRaw = '100';
            }
            if (!ctype_digit((string)$displayOrderRaw)) {
                $errors['display_order'] = 'Display order must be a non-negative integer.';
            } elseif ((int)$displayOrderRaw < 0 || (int)$displayOrderRaw > 100000) {
                $errors['display_order'] = 'Display order is out of range.';
            }
            $data['display_order'] = (int)$displayOrderRaw;
        }

        // Booleans
        $data['is_active'] = $isActive;
        $data['is_featured'] = $isFeatured;

        // Features
        // Filter out completely empty strings? But we want to validate and preserve.
        // For validation, we consider features array after trimming, keep order, but reject empty.
        // Count check
        if (count($features) > 50) {
            $errors['features'] = 'Too many features. Maximum 50 allowed.';
        } else {
            // Validate each feature
            $featureErrors = [];
            $normalizedFeatures = [];
            foreach ($features as $idx => $text) {
                $trimmed = trim($text);
                if ($trimmed === '') {
                    $featureErrors[$idx] = 'Feature text cannot be empty.';
                } elseif (mb_strlen($trimmed) > 300) {
                    $featureErrors[$idx] = 'Feature must not exceed 300 characters.';
                }
                $normalizedFeatures[] = $trimmed;
            }
            if (!empty($featureErrors)) {
                $errors['features'] = 'One or more features are invalid.';
                $errors['features_details'] = $featureErrors;
            }
            $data['features'] = $normalizedFeatures;
        }

        // ---- Options Validation & Top-Level Price Derivation ----
        $optionsRaw = $input['options'] ?? [];
        $normalizedOptions = [];
        if (!is_array($optionsRaw)) $optionsRaw = [];

        if (!empty($optionsRaw)) {
            [$optionErrors, $normalizedOptions] = $this->validateOptions($optionsRaw);
            if (!empty($optionErrors)) {
                $errors['options'] = $optionErrors;
            }
        }
        $data['options'] = $normalizedOptions;

        // If options provided and valid, auto-fill top-level prices/duration if empty
        if (!empty($normalizedOptions) && empty($errors['options'])) {
            $minOpt = null;
            foreach ($normalizedOptions as $opt) {
                if ($opt['is_active'] === 1) {
                    if ($minOpt === null || \bccomp((string)$opt['price'], (string)$minOpt['price'], 2) < 0) {
                        $minOpt = $opt;
                    }
                }
            }
            if ($minOpt === null && !empty($normalizedOptions)) {
                $minOpt = $normalizedOptions[0];
            }

            if ($minOpt !== null) {
                if ($sellingPriceRaw === '' || isset($errors['selling_price'])) {
                    unset($errors['selling_price']);
                    $sellingPriceRaw = number_format((float)$minOpt['price'], 2, '.', '');
                    $data['selling_price'] = $sellingPriceRaw;
                }
                if ($regularPriceRaw === '' || isset($errors['regular_price'])) {
                    unset($errors['regular_price']);
                    $regularPriceRaw = $sellingPriceRaw;
                    $data['regular_price'] = $regularPriceRaw;
                }
                if ($durationValueRaw === '' || isset($errors['duration_value'])) {
                    unset($errors['duration_value']);
                    $durationValueRaw = (string)$minOpt['duration_value'];
                    $data['duration_value'] = $minOpt['duration_value'];
                }
                if ($durationUnit === '' || isset($errors['duration_unit'])) {
                    unset($errors['duration_unit']);
                    $unitMap = ['day' => 'days', 'week' => 'weeks', 'month' => 'months', 'year' => 'months'];
                    $durationUnit = $unitMap[$minOpt['duration_unit']] ?? 'months';
                    $data['duration_unit'] = $durationUnit;
                }
            }
        }

        return [$errors, $data];
    }

    /**
     * Create package + features atomically.
     * @param array $input Raw POST input
     * @param int $adminId
     * @return array{success:bool, id?:int, errors?:array, data?:array}
     */
    public function createPackage(array $input, int $adminId): array
    {
        if (!function_exists('bccomp')) {
            log_message('error', 'bcmath extension missing for PackageService price comparison');
            return ['success' => false, 'errors' => ['exception' => 'Server misconfigured: bcmath required.']];
        }
        [$errors, $normalized] = $this->validateCreate($input);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'data' => $normalized];
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Prepare package data for insert
            $packageData = [
                'name'              => $normalized['name'],
                'slug'              => $normalized['slug'],
                'short_description' => $normalized['short_description'],
                'full_description'  => $normalized['full_description'],
                'regular_price'     => $normalized['regular_price'],
                'selling_price'     => $normalized['selling_price'],
                'duration_value'    => $normalized['duration_value'],
                'duration_unit'     => $normalized['duration_unit'],
                'badge'             => $normalized['badge'],
                'cta_label'         => $normalized['cta_label'],
                'google_form_url'   => $normalized['google_form_url'],
                'whatsapp_template' => $normalized['whatsapp_template'],
                'display_order'     => $normalized['display_order'],
                'is_featured'       => $normalized['is_featured'],
                'is_active'         => $normalized['is_active'],
            ];

            // Use Model insert with skipValidation (we already validated)
            $this->packageModel->skipValidation(true);
            $packageId = $this->packageModel->insert($packageData, true);
            if ($packageId === false) {
                $modelErrors = $this->packageModel->errors();
                throw new \RuntimeException('Package insert failed: ' . json_encode($modelErrors));
            }
            $packageId = (int)$packageId;

            // Insert features sequentially
            $features = $normalized['features'] ?? [];
            // Filter out empty after validation? But we already flagged empty as error, so if we are here, all are non-empty
            // However, if features is empty array, no inserts.
            $seq = 1;
            foreach ($features as $text) {
                $trimmed = trim($text);
                if ($trimmed === '') continue; // skip empty (should not happen after validation)
                $featData = [
                    'package_id'    => $packageId,
                    'feature_text'  => $trimmed,
                    'display_order' => $seq++,
                    'is_active'     => 1,
                ];
                $this->featureModel->skipValidation(true);
                $fid = $this->featureModel->insert($featData, true);
                if ($fid === false) {
                    throw new \RuntimeException('Feature insert failed: ' . json_encode($this->featureModel->errors()));
                }
            }

            // Insert options sequentially
            if (!empty($normalized['options'])) {
                $this->savePackageOptions($packageId, $normalized['options']);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed');
            }

            // Audit log — package.created
            try {
                $activity = new \App\Services\AdminActivityService();
                $activity->log(
                    'package.created',
                    $adminId,
                    'package',
                    $packageId,
                    'Package created: ' . $normalized['name'],
                    ['package_id' => $packageId, 'slug' => $normalized['slug'], 'name' => $normalized['name']]
                );
            } catch (\Throwable $e) {
                log_message('error', 'package.created audit failed: ' . $e->getMessage());
            }

            return ['success' => true, 'id' => $packageId, 'data' => $normalized];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'PackageService::createPackage failed: ' . $e->getMessage());
            // Detect duplicate slug race (unique constraint)
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'duplicate') || str_contains($e->getMessage(), '1062')) {
                return ['success' => false, 'errors' => ['slug' => 'Slug is already in use.'], 'data' => $normalized ?? $input];
            }
            return ['success' => false, 'errors' => ['exception' => 'Failed to create package. Please try again.'], 'data' => $normalized ?? $input];
        }
    }

    /**
     * Simulate failure inside feature replacement for rollback test (test-only).
     * When true, updatePackage will throw after deleting old features.
     */
    public static bool $simulateFeatureFailure = false;
    /**
     * Test-only: when true, forces production trust-boundary (disallows fallback copy).
     * Used to verify that arbitrary local file copy is blocked in production.
     */
    public static bool $forceProductionModeForTest = false;

    /**
     * Fetch package plus ordered features for edit prefill.
     * Returns null if not found or soft-deleted.
     * @return array|null{package:array, features: array<int,string>}
     */
    public function getPackageForEdit(int $packageId): ?array
    {
        $package = $this->packageModel->find($packageId);
        if ($package === null) {
            return null;
        }
        // find() already respects soft delete; if deleted, null
        $features = $this->featureModel
            ->where('package_id', $packageId)
            ->where('is_active', 1)
            ->orderBy('display_order', 'ASC')
            ->findAll();
        $texts = array_map(fn($r) => $r['feature_text'], $features);
        return ['package' => $package, 'features' => $texts, 'rawFeatures' => $features];
    }

    /**
     * Update package + features transactionally (zero-FK).
     * Shares validation with create (money, duration, slug excl current id, google, whatsapp, etc.)
     *
     * Flexible signature to satisfy spec + test call styles:
     *  - updatePackage(int $id, array $input, int $adminId)
     *  - updatePackage(int $id, array $packageData, array $features, int $adminId)
     *
     * @param int $packageId
     * @param array $packageData Either full input array containing features, or package fields
     * @param array|int $featuresOrAdminId features array or adminId when overload
     * @param int|null $adminId
     * @return array{success:bool, id?:int, errors?:array, data?:array, notFound?:bool}
     */
    public function updatePackage(int $packageId, array $packageData, $featuresOrAdminId = [], ?int $adminId = null): array
    {
        // Normalize overload
        $input = $packageData;
        $resolvedAdminId = 0;
        if (is_array($featuresOrAdminId) && $adminId !== null) {
            // Called as updatePackage(id, packageData, features, adminId)
            $features = $featuresOrAdminId;
            // Merge features into input if not already there
            if (!isset($input['features'])) {
                $input['features'] = $features;
            }
            $resolvedAdminId = $adminId;
        } elseif (is_int($featuresOrAdminId) && $adminId === null) {
            // Called as updatePackage(id, input, adminId)
            $resolvedAdminId = $featuresOrAdminId;
        } elseif (is_array($featuresOrAdminId) && $adminId === null && empty($featuresOrAdminId)) {
            // ambiguous, treat as no admin
            $resolvedAdminId = 0;
        } else {
            // Fallback
            $resolvedAdminId = is_int($adminId) ? $adminId : (is_int($featuresOrAdminId) ? $featuresOrAdminId : 0);
        }
        // If input doesn't contain features but second arg was features array with content, ensure it
        if (is_array($featuresOrAdminId) && !empty($featuresOrAdminId) && !isset($input['features'])) {
            $input['features'] = $featuresOrAdminId;
        }

        // Check package exists and not soft-deleted BEFORE validation (so we can return 404 fast)
        $existing = $this->packageModel->find($packageId);
        if ($existing === null) {
            return ['success' => false, 'notFound' => true, 'errors' => ['package' => 'Package not found.']];
        }

        // Ensure bcmath available — runtime check
        if (!function_exists('bccomp')) {
            log_message('error', 'bcmath extension missing for PackageService price comparison');
            return ['success' => false, 'errors' => ['exception' => 'Server misconfigured: bcmath required.']];
        }

        // Validate (reuse, excluding current id for slug)
        [$errors, $normalized] = $this->validateCreate($input, $packageId);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'data' => $normalized];
        }

        // Compute changed_fields before transaction for audit (compare existing + original features vs normalized)
        $changed = [];
        $fieldsToCheck = ['name','slug','short_description','full_description','regular_price','selling_price','duration_value','duration_unit','badge','cta_label','google_form_url','whatsapp_template','display_order','is_active','is_featured'];
        foreach ($fieldsToCheck as $field) {
            $oldVal = $existing[$field] ?? null;
            $newVal = $normalized[$field] ?? null;
            // Normalize for comparison: null vs '' etc, prices compare via bccomp
            if (in_array($field, ['regular_price','selling_price'], true)) {
                $oldNorm = $oldVal !== null ? number_format((float)$oldVal, 2, '.', '') : null;
                $newNorm = $newVal !== null ? number_format((float)$newVal, 2, '.', '') : null;
                $cmp = ($oldNorm === null && $newNorm === null) ? 0 : \bccomp((string)($oldNorm ?? '0'), (string)($newNorm ?? '0'), 2);
                if ($cmp !== 0) $changed[] = $field;
            } elseif ($field === 'duration_value' || $field === 'display_order' || $field === 'is_active' || $field === 'is_featured') {
                if ((int)($oldVal ?? 0) !== (int)($newVal ?? 0)) $changed[] = $field;
            } else {
                // string null normalization
                $o = $oldVal === null ? null : trim((string)$oldVal);
                $n = $newVal === null ? null : trim((string)$newVal);
                if ($o !== $n) $changed[] = $field;
            }
        }
        // Features changed? Need original features
        try {
            $origFeatures = $this->featureModel->where('package_id', $packageId)->where('is_active', 1)->orderBy('display_order','ASC')->findAll();
            $origTexts = array_map(fn($r) => trim((string)$r['feature_text']), $origFeatures);
            $newTexts = array_map(fn($t) => trim((string)$t), $normalized['features'] ?? []);
            // Compare count and order
            if ($origTexts !== $newTexts) {
                $changed[] = 'features';
            }
        } catch (\Throwable $e) {
            $changed[] = 'features';
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $packageDataForUpdate = [
                'name'              => $normalized['name'],
                'slug'              => $normalized['slug'],
                'short_description' => $normalized['short_description'],
                'full_description'  => $normalized['full_description'],
                'regular_price'     => $normalized['regular_price'],
                'selling_price'     => $normalized['selling_price'],
                'duration_value'    => $normalized['duration_value'],
                'duration_unit'     => $normalized['duration_unit'],
                'badge'             => $normalized['badge'],
                'cta_label'         => $normalized['cta_label'],
                'google_form_url'   => $normalized['google_form_url'],
                'whatsapp_template' => $normalized['whatsapp_template'],
                'display_order'     => $normalized['display_order'],
                'is_featured'       => $normalized['is_featured'],
                'is_active'         => $normalized['is_active'],
            ];

            // Use Model update with skipValidation
            $this->packageModel->skipValidation(true);
            // Need to ensure we update only existing not deleted — find already checked, but use update with id
            $result = $this->packageModel->update($packageId, $packageDataForUpdate);
            if ($result === false) {
                $errs = $this->packageModel->errors();
                throw new \RuntimeException('Package update failed: ' . json_encode($errs));
            }
            // Detect soft-deleted race: if update affected 0 rows and package disappeared
            // Check existence again
            $stillExists = $this->packageModel->find($packageId);
            if ($stillExists === null) {
                throw new \RuntimeException('Package disappeared during update');
            }

            // Delete existing features (logical reference only)
            $this->featureModel->where('package_id', $packageId)->delete();

            // Simulate failure for rollback test if flag set
            if (self::$simulateFeatureFailure) {
                throw new \RuntimeException('Simulated feature failure for rollback test');
            }

            // Insert new features sequentially
            $features = $normalized['features'] ?? [];
            $seq = 1;
            foreach ($features as $text) {
                $trimmed = trim((string)$text);
                if ($trimmed === '') continue; // should not happen after validation
                $featData = [
                    'package_id'    => $packageId,
                    'feature_text'  => $trimmed,
                    'display_order' => $seq++,
                    'is_active'     => 1,
                ];
                $this->featureModel->skipValidation(true);
                $fid = $this->featureModel->insert($featData, true);
                if ($fid === false) {
                    throw new \RuntimeException('Feature insert failed: ' . json_encode($this->featureModel->errors()));
                }
            }

            // Options replacement
            if (array_key_exists('options', $normalized)) {
                $this->savePackageOptions($packageId, $normalized['options']);
            }

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed');
            }

            // Audit — package.updated (even if no changes, per spec allow empty changed_fields)
            try {
                $activity = new \App\Services\AdminActivityService();
                $activity->log(
                    'package.updated',
                    $resolvedAdminId,
                    'package',
                    $packageId,
                    'Package updated: ' . $normalized['name'],
                    ['package_id' => $packageId, 'slug' => $normalized['slug'], 'changed_fields' => $changed]
                );
            } catch (\Throwable $e) {
                log_message('error', 'package.updated audit failed: ' . $e->getMessage());
            }

            return ['success' => true, 'id' => $packageId, 'data' => $normalized, 'changed_fields' => $changed];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'PackageService::updatePackage failed: ' . $e->getMessage());
            // Handle race duplicate slug safely
            $msg = $e->getMessage();
            if (str_contains($msg, 'Duplicate') || str_contains($msg, 'duplicate') || str_contains($msg, '1062') || str_contains(strtolower($msg), 'unique')) {
                return ['success' => false, 'errors' => ['slug' => 'Slug is already in use.'], 'data' => $normalized ?? $input];
            }
            // If package disappeared, signal notFound
            if (str_contains($msg, 'disappeared')) {
                return ['success' => false, 'notFound' => true, 'errors' => ['package' => 'Package not found.']];
            }
            return ['success' => false, 'errors' => ['exception' => 'Failed to update package. Please try again.'], 'data' => $normalized ?? $input];
        }
    }

    // ==================== MEDIA HELPERS ====================

    /**
     * Validate single uploaded image file (featured or gallery).
     * Accepts UploadedFile object or $_FILES array.
     * Returns null if valid, error string if invalid.
     */
    public function validateImageFile($file): ?string
    {
        if ($file === null) return null;
        // Handle UploadedFile object
        if (is_object($file) && method_exists($file, 'getError')) {
            $error = $file->getError();
            if ($error === UPLOAD_ERR_NO_FILE) return null;
            if ($error !== UPLOAD_ERR_OK) return 'File upload error.';
            $size = $file->getSize();
            $tmp = $file->getTempName();
            $clientName = $file->getClientName();
        } else if (is_array($file)) {
            $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) return null;
            if ($error !== UPLOAD_ERR_OK) return 'File upload error.';
            $size = $file['size'] ?? 0;
            $tmp = $file['tmp_name'] ?? '';
            $clientName = $file['name'] ?? '';
        } else {
            return 'Invalid file.';
        }

        if ($size > self::MAX_IMAGE_SIZE) {
            return 'Image must not exceed 5 MB.';
        }
        if ($size === 0) return 'Image is empty.';
        if (!is_file($tmp) || !is_readable($tmp)) return 'Invalid upload.';

        // MIME via finfo, not client type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if (!isset(self::ALLOWED_IMAGE_MIMES[$mime])) {
            return 'Invalid image type. Allowed: JPEG, PNG, WEBP.';
        }
        // Reject double-extension and traversal style names
        if (preg_match('/\.(php|phtml|phar|html|htm|js|svg|exe|sh|bat|cgi|pl|py)$/i', $clientName)) {
            return 'Invalid image type.';
        }
        // Validate file is actually image via getimagesize (checks magic bytes, not just extension)
        $info = @getimagesize($tmp);
        if ($info === false) return 'Invalid or corrupt image.';
        if (!in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return 'Invalid image type.';
        }
        // Dimension / decompression bomb protection
        $width = $info[0] ?? 0;
        $height = $info[1] ?? 0;
        if ($width <= 0 || $height <= 0) return 'Invalid image dimensions.';
        if ($width > self::MAX_IMAGE_WIDTH || $height > self::MAX_IMAGE_HEIGHT) {
            return 'Image dimensions too large. Max '.self::MAX_IMAGE_WIDTH.'x'.self::MAX_IMAGE_HEIGHT.' px.';
        }
        if (($width * $height) > self::MAX_IMAGE_PIXELS) {
            return 'Image has too many pixels. Max '.number_format(self::MAX_IMAGE_PIXELS).' pixels.';
        }
        return null;
    }

    /**
     * Generate safe unique filename with validated extension.
     */
    public function generateSafeFilename(string $mime): string
    {
        $ext = self::ALLOWED_IMAGE_MIMES[$mime] ?? 'jpg';
        // Use random_bytes for uniqueness
        $random = bin2hex(random_bytes(16));
        return $random . '.' . $ext;
    }

    /**
     * Store uploaded file to package directory, returns relative path.
     * PRODUCTION: only UploadedFile via move() is allowed. Fallback copy is TEST-ONLY.
     * is_array path is also test-only and must be via is_uploaded_file in prod.
     */
    public function storeUploadedFile($file, int $packageId, string $prefix = 'img'): string
    {
        $isTesting = !self::$forceProductionModeForTest && ((defined('ENVIRONMENT') && ENVIRONMENT === 'testing') || (getenv('CI_ENVIRONMENT') === 'testing'));
        if (is_object($file) && method_exists($file, 'getTempName')) {
            $tmp = $file->getTempName();
            $isValid = true;
            if (method_exists($file, 'isValid')) {
                try { $isValid = $file->isValid(); } catch (\Throwable $e) { $isValid = false; }
            }
            if (method_exists($file, 'hasMoved')) {
                try { if ($file->hasMoved()) $isValid = false; } catch (\Throwable $e) {}
            }
            // In production, the file must be a real uploaded file
            $isUploaded = @is_uploaded_file($tmp);
            if (!$isTesting && !$isUploaded) {
                // In prod, tmp must be an uploaded file; otherwise reject (prevents arbitrary local copy)
                throw new \RuntimeException('Invalid upload: not an uploaded file.');
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = @ $finfo->file($tmp);
            if ($mime === false || $mime === '') $mime = 'image/jpeg';
            if (!isset(self::ALLOWED_IMAGE_MIMES[$mime])) {
                throw new \RuntimeException('Invalid MIME for storage.');
            }
            $filename = $prefix . '_' . $this->generateSafeFilename($mime);
            $relative = 'uploads/packages/' . $packageId . '/' . $filename;
            $dest = FCPATH . $relative;
            $dir = dirname($dest);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            // Enforce storage root
            $realUploads = realpath(FCPATH . 'uploads/packages');
            if ($realUploads === false) @mkdir(FCPATH.'uploads/packages', 0755, true);
            if ($isValid && method_exists($file, 'move')) {
                try {
                    $file->move($dir, $filename, true);
                    return $relative;
                } catch (\Throwable $e) {
                    if (!$isTesting) throw new \RuntimeException('Failed to store uploaded file.');
                    // In testing, fallback to copy is allowed
                }
            }
            // Fallback copy is TEST-ONLY
            if (!$isTesting) {
                throw new \RuntimeException('Failed to store image: move failed and fallback not allowed in production.');
            }
            if (!@copy($tmp, $dest)) {
                if (!@move_uploaded_file($tmp, $dest) && !@copy($tmp, $dest)) {
                    throw new \RuntimeException('Failed to store image: '.$tmp.' -> '.$dest);
                }
            }
            return $relative;
        } else if (is_array($file)) {
            // is_array path is TEST-ONLY; in production HTTP, files are UploadedFile objects, not arrays.
            if (!$isTesting) {
                throw new \RuntimeException('Invalid file input in production.');
            }
            $tmp = $file['tmp_name'] ?? '';
            if ($tmp === '' || !is_file($tmp)) throw new \RuntimeException('Invalid array upload.');
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp);
            if (!isset(self::ALLOWED_IMAGE_MIMES[$mime])) throw new \RuntimeException('Invalid MIME.');
            $filename = $prefix . '_' . $this->generateSafeFilename($mime);
            $relative = 'uploads/packages/' . $packageId . '/' . $filename;
            $dest = FCPATH . $relative;
            $dir = dirname($dest);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            if (!@copy($tmp, $dest) && !@move_uploaded_file($tmp, $dest)) {
                if (!copy($tmp, $dest)) throw new \RuntimeException('Failed to store image.');
            }
            return $relative;
        }
        throw new \RuntimeException('Invalid file type for store.');
    }

    /**
     * Delete file given relative path (e.g., uploads/packages/1/xxx.jpg)
     * Hardened: verifies canonical path inside managed root, does not follow symlink outside.
     */
    public function deleteStoredFile(?string $relativePath): void
    {
        if ($relativePath === null || trim($relativePath) === '') return;
        // DB must store relative managed path only
        $expectedPrefix = 'uploads/packages/';
        if (strpos($relativePath, $expectedPrefix) !== 0) return;
        // Reject traversal in DB value itself
        if (strpos($relativePath, '..') !== false) return;
        if (strpos($relativePath, "\\") !== false) return;

        $full = FCPATH . $relativePath;
        $realUploads = realpath(FCPATH . 'uploads/packages');
        if ($realUploads === false) {
            // Try to create root if missing, otherwise abort
            @mkdir(FCPATH.'uploads/packages', 0755, true);
            $realUploads = realpath(FCPATH . 'uploads/packages');
            if ($realUploads === false) return;
        }

        // If path is a symlink, handle without following outside
        if (is_link($full)) {
            // Ensure the link itself is inside managed root (by comparing its directory)
            $linkDir = dirname($full);
            $realLinkDir = realpath($linkDir);
            if ($realLinkDir === false || strpos($realLinkDir, $realUploads) !== 0) return;
            // Unlink the symlink itself, not its target
            @unlink($full);
            return;
        }

        $realFile = realpath($full);
        if ($realFile === false) {
            // File may not exist or is broken symlink; if path is inside root, try to unlink the file/link itself
            // Double-check that the full path's directory is inside root
            $dir = dirname($full);
            $realDir = realpath($dir);
            if ($realDir !== false && strpos($realDir, $realUploads) === 0) {
                if (is_file($full) || is_link($full)) @unlink($full);
            }
            return;
        }
        // Canonical file must be inside managed root
        if (strpos($realFile, $realUploads) !== 0) return;
        // Ensure we are deleting a file, not a directory
        if (is_file($realFile) || is_link($full)) {
            // Use $full for unlink to avoid TOCTOU with realpath, but we already verified realFile inside root
            @unlink($realFile);
            // If $full was symlink, realFile is target outside, we already returned; so this is safe file inside
            if ($realFile !== $full && is_file($full)) @unlink($full);
        }
    }

    /**
     * Validate gallery count and each file.
     */
    public function validateGalleryFiles(array $files): ?string
    {
        if (count($files) > self::MAX_GALLERY_COUNT) {
            return 'Too many gallery images. Maximum ' . self::MAX_GALLERY_COUNT . ' allowed.';
        }
        foreach ($files as $f) {
            $err = $this->validateImageFile($f);
            if ($err !== null) return $err;
        }
        return null;
    }

    /**
     * Sanitize alt_text to plain text, max 300, no HTML execution, no warnings on tampering.
     * Returns null if empty after trim or if tampered array/object.
     */
    private function sanitizeAltText(mixed $alt): ?string
    {
        if (is_array($alt) || is_object($alt)) return null;
        if ($alt === null) return null;
        $s = trim((string)$alt);
        if ($s === '') return null;
        if (mb_strlen($s) > 300) $s = mb_substr($s, 0, 300);
        return $s;
    }

    /**
     * Sanitize original_name: untrusted metadata, never used for path/MIME/delete; escapes handled on view.
     */
    private function sanitizeOriginalName(mixed $name): ?string
    {
        if (is_array($name) || is_object($name)) return null;
        if ($name === null) return null;
        $s = trim((string)$name);
        if ($s === '') return null;
        if (mb_strlen($s) > 255) $s = mb_substr($s, 0, 255);
        return $s;
    }

    /**
     * Validate gallery IDs for update: ownership, uniqueness, malformed, cross-package, deleted.
     * Returns null on success or error string on failure (no warnings, no SQL error).
     */
    private function validateGalleryIds(array $ids, array $existingMap): ?string
    {
        // Check duplicates before ownership: duplicate IDs must be rejected
        $intIds = [];
        foreach ($ids as $eid) {
            if (is_array($eid) || is_object($eid)) return 'Invalid gallery selection.';
            // Must be scalar numeric positive integer
            $str = trim((string)$eid);
            if ($str === '' || !ctype_digit($str)) return 'Invalid gallery selection.';
            $int = (int)$str;
            if ($int <= 0) return 'Invalid gallery selection.';
            $intIds[] = $int;
        }
        if (count($intIds) !== count(array_unique($intIds))) {
            return 'Invalid gallery selection. Duplicate IDs.';
        }
        foreach ($intIds as $int) {
            if (!isset($existingMap[$int])) return 'Invalid gallery selection.';
        }
        return null;
    }

    /**
     * Validate gallery_order: must be subset of retained, unique, owned, no warnings.
     */
    private function validateGalleryOrder(array $order, array $retainedIds): ?string
    {
        if (empty($order)) return null;
        $intOrder = [];
        foreach ($order as $oid) {
            if (is_array($oid) || is_object($oid)) return 'Invalid gallery order.';
            $str = trim((string)$oid);
            if ($str === '' || !ctype_digit($str)) return 'Invalid gallery order.';
            $int = (int)$str;
            if ($int <= 0) return 'Invalid gallery order.';
            $intOrder[] = $int;
        }
        if (count($intOrder) !== count(array_unique($intOrder))) {
            return 'Invalid gallery order. Duplicate IDs.';
        }
        // Every order id must be in retainedIds
        $retainedSet = array_map('intval', $retainedIds);
        foreach ($intOrder as $int) {
            if (!in_array($int, $retainedSet, true)) return 'Invalid gallery order.';
        }
        return null;
    }

    /**
     * Find true orphan gallery rows: package_id points to no package row at all (even soft-deleted).
     * Deleted package media is NOT orphan (intentionally preserved).
     * @return array[] orphan rows
     */
    public function findOrphanGalleryRows(): array
    {
        try {
            $db = \Config\Database::connect();
            // LEFT JOIN packages (withDeleted) — we need to include soft-deleted rows, so join without deleted_at filter
            $builder = $db->table('package_gallery_images g');
            $builder->select('g.*');
            $builder->join('packages p', 'p.id = g.package_id', 'left');
            $builder->where('p.id IS NULL');
            return $builder->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Audit helper to check if any true orphan exists (for tests).
     */
    public function hasOrphans(): bool
    {
        return count($this->findOrphanGalleryRows()) > 0;
    }

    /**
     * Get gallery images for package ordered.
     */
    public function getGalleryImages(int $packageId): array
    {
        return $this->galleryModel->where('package_id', $packageId)->orderBy('display_order','ASC')->findAll();
    }

    /**
     * Get package with gallery for edit.
     */
    public function getPackageWithGallery(int $packageId): ?array
    {
        $pkg = $this->packageModel->find($packageId);
        if ($pkg === null) return null;
        $gallery = $this->getGalleryImages($packageId);
        $features = $this->featureModel->where('package_id',$packageId)->where('is_active',1)->orderBy('display_order','ASC')->findAll();
        $featTexts = array_map(fn($r)=>$r['feature_text'], $features);
        return ['package'=>$pkg, 'features'=>$featTexts, 'gallery'=>$gallery];
    }

    // ==================== LIFECYCLE OPERATIONS ====================

    public function toggleActive(int $packageId, int $adminId): array
    {
        $pkg = $this->packageModel->find($packageId);
        if ($pkg === null) return ['success'=>false,'notFound'=>true];
        $new = ((int)$pkg['is_active'] === 1) ? 0 : 1;
        $this->packageModel->skipValidation(true);
        $ok = $this->packageModel->update($packageId, ['is_active'=>$new]);
        if ($ok === false) return ['success'=>false,'errors'=>['exception'=>'Failed']];
        $action = $new === 1 ? 'package.activated' : 'package.deactivated';
        try {
            (new \App\Services\AdminActivityService())->log($action, $adminId, 'package', $packageId, $action.': '.$pkg['name'], ['package_id'=>$packageId]);
        } catch (\Throwable $e) {}
        return ['success'=>true,'is_active'=>$new];
    }

    public function toggleFeatured(int $packageId, int $adminId): array
    {
        $pkg = $this->packageModel->find($packageId);
        if ($pkg === null) return ['success'=>false,'notFound'=>true];
        $new = ((int)$pkg['is_featured'] === 1) ? 0 : 1;
        $this->packageModel->skipValidation(true);
        $ok = $this->packageModel->update($packageId, ['is_featured'=>$new]);
        if ($ok === false) return ['success'=>false,'errors'=>['exception'=>'Failed']];
        $action = $new === 1 ? 'package.featured' : 'package.unfeatured';
        try {
            (new \App\Services\AdminActivityService())->log($action, $adminId, 'package', $packageId, $action.': '.$pkg['name'], ['package_id'=>$packageId]);
        } catch (\Throwable $e) {}
        return ['success'=>true,'is_featured'=>$new];
    }

    public function reorderPackages(array $orderedIds, int $adminId): array
    {
        if (empty($orderedIds)) return ['success'=>false,'errors'=>['order'=>'No order provided']];
        // Validate all are integers and exist and not deleted
        $orderedIds = array_map('intval', $orderedIds);
        // Check for duplicates
        if (count($orderedIds) !== count(array_unique($orderedIds))) return ['success'=>false,'errors'=>['order'=>'Duplicate IDs']];
        // Fetch all non-deleted packages
        $all = $this->packageModel->findAll();
        $allIds = array_map(fn($r)=>(int)$r['id'], $all);
        sort($allIds);
        $sortedOrdered = $orderedIds;
        sort($sortedOrdered);
        if ($allIds !== $sortedOrdered) {
            return ['success'=>false,'errors'=>['order'=>'Order must include all packages']];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $order = 1;
            foreach ($orderedIds as $pid) {
                $this->packageModel->skipValidation(true);
                $this->packageModel->update($pid, ['display_order'=>$order++]);
            }
            $db->transComplete();
            if ($db->transStatus() === false) throw new \RuntimeException('Reorder failed');
            try {
                (new \App\Services\AdminActivityService())->log('packages.reordered', $adminId, 'package', null, 'Packages reordered', ['ordered_ids'=>$orderedIds]);
            } catch (\Throwable $e) {}
            return ['success'=>true];
        } catch (\Throwable $e) {
            $db->transRollback();
            return ['success'=>false,'errors'=>['exception'=>$e->getMessage()]];
        }
    }

    public function deletePackage(int $packageId, int $adminId): array
    {
        $pkg = $this->packageModel->find($packageId);
        if ($pkg === null) return ['success'=>false,'notFound'=>true];
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            // Set is_active 0 then soft delete
            $this->packageModel->skipValidation(true);
            $this->packageModel->update($packageId, ['is_active'=>0]);
            $this->packageModel->delete($packageId, false); // soft
            $db->transComplete();
            if ($db->transStatus()===false) throw new \RuntimeException('Delete failed');
            try {
                (new \App\Services\AdminActivityService())->log('package.deleted', $adminId, 'package', $packageId, 'Package deleted: '.$pkg['name'], ['package_id'=>$packageId]);
            } catch (\Throwable $e) {}
            return ['success'=>true];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error','deletePackage failed: '.$e->getMessage());
            return ['success'=>false,'errors'=>['exception'=>'Failed']];
        }
    }

    public function getDeletedPackages(): array
    {
        return $this->packageModel->onlyDeleted()->orderBy('deleted_at','DESC')->findAll();
    }

    public function restorePackage(int $packageId, int $adminId): array
    {
        $pkg = $this->packageModel->onlyDeleted()->find($packageId);
        if ($pkg === null) {
            // Check if not deleted but exists
            $exists = $this->packageModel->find($packageId);
            if ($exists !== null) return ['success'=>false,'errors'=>['package'=>'Package is not deleted']];
            return ['success'=>false,'notFound'=>true];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        try {
            // Find max display_order among active packages to place at end
            $maxRow = $this->packageModel->selectMax('display_order','max_order')->first();
            // Actually need to query all non-deleted max
            $builder = $this->packageModel->builder();
            $builder->selectMax('display_order','max_order');
            $builder->where('deleted_at IS NULL');
            $row = $builder->get()->getRowArray();
            $maxOrder = $row['max_order'] ?? 0;
            $newOrder = ((int)$maxOrder) + 1;
            // Restore: update deleted_at null via model, and set is_active 0, display_order new
            // Use builder to set deleted_at null
            $builder2 = $this->packageModel->builder();
            $builder2->where('id', $packageId);
            $builder2->update(['deleted_at'=>null, 'is_active'=>0, 'display_order'=>$newOrder]);
            // Also ensure model cache? Use withDeleted to verify
            $db->transComplete();
            if ($db->transStatus()===false) throw new \RuntimeException('Restore failed');
            try {
                (new \App\Services\AdminActivityService())->log('package.restored', $adminId, 'package', $packageId, 'Package restored: '.$pkg['name'], ['package_id'=>$packageId]);
            } catch (\Throwable $e) {}
            return ['success'=>true];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error','restorePackage failed: '.$e->getMessage());
            return ['success'=>false,'errors'=>['exception'=>'Failed']];
        }
    }

    /**
     * Create package with media (featured + gallery) atomically with compensating file cleanup.
     * Extended signature: createPackage(input, adminId, featuredFile, galleryFiles)
     * For backward compat, featuredFile and galleryFiles are optional.
     */
    public function createPackageWithMedia(array $input, int $adminId, $featuredFile = null, array $galleryFiles = [], array $galleryAlt = []): array
    {
        // Validate images first (before DB)
        if ($featuredFile !== null) {
            $err = $this->validateImageFile($featuredFile);
            if ($err !== null) return ['success'=>false,'errors'=>['featured_image'=>$err]];
        }
        if (!empty($galleryFiles)) {
            $err = $this->validateGalleryFiles($galleryFiles);
            if ($err !== null) return ['success'=>false,'errors'=>['gallery_images'=>$err]];
            if (count($galleryFiles) > self::MAX_GALLERY_COUNT) return ['success'=>false,'errors'=>['gallery_images'=>'Too many gallery images']];
        }

        // Validate package data (includes sanitization)
        [$errors, $normalized] = $this->validateCreate($input);
        if (!empty($errors)) return ['success'=>false,'errors'=>$errors,'data'=>$normalized];

        $db = \Config\Database::connect();
        $newFiles = []; // track stored files for cleanup on failure
        $packageId = null;

        $db->transStart();
        try {
            $packageData = [
                'name'              => $normalized['name'],
                'slug'              => $normalized['slug'],
                'short_description' => $normalized['short_description'],
                'full_description'  => $normalized['full_description'],
                'regular_price'     => $normalized['regular_price'],
                'selling_price'     => $normalized['selling_price'],
                'duration_value'    => $normalized['duration_value'],
                'duration_unit'     => $normalized['duration_unit'],
                'badge'             => $normalized['badge'],
                'cta_label'         => $normalized['cta_label'],
                'google_form_url'   => $normalized['google_form_url'],
                'whatsapp_template' => $normalized['whatsapp_template'],
                'display_order'     => $normalized['display_order'],
                'is_featured'       => $normalized['is_featured'],
                'is_active'         => $normalized['is_active'],
                'featured_image'    => null, // placeholder, will update after file stored
            ];
            $this->packageModel->skipValidation(true);
            $pid = $this->packageModel->insert($packageData, true);
            if ($pid === false) throw new \RuntimeException('Package insert failed: '.json_encode($this->packageModel->errors()));
            $packageId = (int)$pid;

            // Store featured image if provided
            $featuredPath = null;
            if ($featuredFile !== null && $this->getUploadError($featuredFile) !== UPLOAD_ERR_NO_FILE) {
                $featuredPath = $this->storeUploadedFile($featuredFile, $packageId, 'featured');
                $newFiles[] = $featuredPath;
                $this->packageModel->skipValidation(true);
                $this->packageModel->update($packageId, ['featured_image'=>$featuredPath]);
            }

            // Features
            $seq=1;
            foreach (($normalized['features'] ?? []) as $t) {
                $trim = trim((string)$t);
                if ($trim==='') continue;
                $this->featureModel->skipValidation(true);
                $fid = $this->featureModel->insert(['package_id'=>$packageId,'feature_text'=>$trim,'display_order'=>$seq++,'is_active'=>1], true);
                if ($fid===false) throw new \RuntimeException('Feature insert failed');
            }

            // Options
            if (!empty($normalized['options'])) {
                $this->savePackageOptions($packageId, $normalized['options']);
            }

            // Gallery
            $gSeq=1;
            foreach ($galleryFiles as $idx=>$gfile) {
                if ($this->getUploadError($gfile) === UPLOAD_ERR_NO_FILE) continue;
                $path = $this->storeUploadedFile($gfile, $packageId, 'gallery');
                $newFiles[] = $path;
                $alt = $galleryAlt[$idx] ?? null;
                if ($alt !== null) $alt = trim((string)$alt);
                if ($alt === '') $alt = null;
                if ($alt !== null && mb_strlen($alt) > 300) $alt = mb_substr($alt,0,300);
                $this->galleryModel->skipValidation(true);
                $gid = $this->galleryModel->insert(['package_id'=>$packageId,'image_path'=>$path,'original_name'=>$this->getClientName($gfile),'alt_text'=>$alt,'display_order'=>$gSeq++], true);
                if ($gid===false) throw new \RuntimeException('Gallery insert failed');
            }

            if (self::$simulateFeatureFailure) throw new \RuntimeException('Simulated feature failure');

            $db->transComplete();
            if ($db->transStatus()===false) throw new \RuntimeException('Transaction failed');

            // Audit
            try {
                (new \App\Services\AdminActivityService())->log('package.created', $adminId, 'package', $packageId, 'Package created: '.$normalized['name'], ['package_id'=>$packageId,'slug'=>$normalized['slug']]);
            } catch (\Throwable $e) {}

            return ['success'=>true,'id'=>$packageId,'data'=>$normalized];
        } catch (\Throwable $e) {
            $db->transRollback();
            // Cleanup newly stored files
            foreach ($newFiles as $f) $this->deleteStoredFile($f);
            // Also clean up directory if empty
            if ($packageId !== null) {
                $dir = FCPATH.'uploads/packages/'.$packageId;
                if (is_dir($dir)) @rmdir($dir);
            }
            log_message('error','PackageService::createPackageWithMedia failed: '.$e->getMessage());
            if (str_contains($e->getMessage(),'Duplicate') || str_contains($e->getMessage(),'1062')) {
                return ['success'=>false,'errors'=>['slug'=>'Slug is already in use.'],'data'=>$normalized ?? $input];
            }
            return ['success'=>false,'errors'=>['exception'=>'Failed to create package.'],'data'=>$normalized ?? $input];
        }
    }

    // Helper to get upload error
    private function getUploadError($file): int
    {
        if (is_object($file) && method_exists($file,'getError')) return $file->getError();
        if (is_array($file)) return $file['error'] ?? UPLOAD_ERR_NO_FILE;
        return UPLOAD_ERR_NO_FILE;
    }
    private function getClientName($file): ?string
    {
        if (is_object($file) && method_exists($file,'getClientName')) return $file->getClientName();
        if (is_array($file)) return $file['name'] ?? null;
        return null;
    }

    /**
     * Update package with media — handles featured replace/remove and gallery add/remove/reorder
     * Hardened 5E.2B: gallery ownership, client path distrust, limit final state, duplicate/malformed, order, alt, featured ownership, transaction.
     */
    public function updatePackageWithMedia(int $packageId, array $input, int $adminId, $featuredFile = null, bool $removeFeatured = false, array $galleryFiles = [], array $existingGalleryIds = [], array $existingGalleryAlt = [], array $galleryOrder = []): array
    {
        // === CLIENT PATH DISTRUST: ignore any submitted filesystem path keys ===
        unset($input['remove_path'], $input['image_path'], $input['featured_image'], $input['featured_image_path'], $input['gallery_path'], $input['gallery_image_path'], $input['remove_featured_path']);
        // Also ignore if client tries to inject image_path via existingGalleryAlt arrays? alt is handled separately

        // Validate images first (MIME/size/dimensions before DB)
        if ($featuredFile !== null && $this->getUploadError($featuredFile) !== UPLOAD_ERR_NO_FILE) {
            $err = $this->validateImageFile($featuredFile);
            if ($err !== null) return ['success'=>false,'errors'=>['featured_image'=>$err]];
        }
        // Normalize galleryFiles non-empty count for limit later (after ownership validation)
        $nonEmptyGalleryFiles = array_values(array_filter($galleryFiles, fn($f)=> $this->getUploadError($f) !== UPLOAD_ERR_NO_FILE));
        if (!empty($nonEmptyGalleryFiles)) {
            $err = $this->validateGalleryFiles($nonEmptyGalleryFiles);
            if ($err !== null) return ['success'=>false,'errors'=>['gallery_images'=>$err]];
        }

        // Normalize ID arrays to ensure they are arrays (controller does, but harden here without warnings)
        if (!is_array($existingGalleryIds)) $existingGalleryIds = (is_scalar($existingGalleryIds) && $existingGalleryIds !== '' && $existingGalleryIds !== null) ? [$existingGalleryIds] : [];
        if (!is_array($existingGalleryAlt)) $existingGalleryAlt = [];
        if (!is_array($galleryOrder)) $galleryOrder = [];
        // Also guard galleryFiles is array
        if (!is_array($galleryFiles)) $galleryFiles = [];

        // Basic check: existingGalleryIds must not contain nested arrays — will be caught in validateGalleryIds, but early sanitize to avoid warnings in count()
        // Do not simply count uploaded files: final state validation will handle limit after ownership check

        // Check package exists not deleted
        $existing = $this->packageModel->find($packageId);
        if ($existing===null) return ['success'=>false,'notFound'=>true,'errors'=>['package'=>'Package not found']];
        if (!function_exists('bccomp')) return ['success'=>false,'errors'=>['exception'=>'Server misconfigured: bcmath required.']];
        [$errors,$normalized] = $this->validateCreate($input, $packageId);
        if (!empty($errors)) return ['success'=>false,'errors'=>$errors,'data'=>$normalized];

        // Compute changed_fields etc. (simplified)
        $changed = [];
        $fieldsToCheck = ['name','slug','short_description','full_description','regular_price','selling_price','duration_value','duration_unit','badge','cta_label','google_form_url','whatsapp_template','display_order','is_active','is_featured'];
        foreach ($fieldsToCheck as $field) {
            $old = $existing[$field] ?? null;
            $new = $normalized[$field] ?? null;
            if (in_array($field, ['regular_price','selling_price'], true)) {
                $oldNorm = $old!==null ? number_format((float)$old,2,'.','') : null;
                $newNorm = $new!==null ? number_format((float)$new,2,'.','') : null;
                $cmp = ($oldNorm===null && $newNorm===null) ? 0 : \bccomp((string)($oldNorm??'0'), (string)($newNorm??'0'),2);
                if ($cmp!==0) $changed[]=$field;
            } elseif (in_array($field, ['duration_value','display_order','is_active','is_featured'], true)) {
                if ((int)($old??0)!==(int)($new??0)) $changed[]=$field;
            } else {
                $o = $old===null?null:trim((string)$old);
                $n = $new===null?null:trim((string)$new);
                if ($o!==$n) $changed[]=$field;
            }
        }
        try {
            $origFeats = $this->featureModel->where('package_id',$packageId)->where('is_active',1)->orderBy('display_order','ASC')->findAll();
            $origTexts = array_map(fn($r)=>trim((string)$r['feature_text']), $origFeats);
            $newTexts = array_map(fn($t)=>trim((string)$t), $normalized['features'] ?? []);
            if ($origTexts !== $newTexts) $changed[]='features';
        } catch (\Throwable $e) {$changed[]='features';}
        // Media changed detection
        $oldFeatured = $existing['featured_image'] ?? null;
        $willChangeFeatured = $removeFeatured || ($featuredFile!==null && $this->getUploadError($featuredFile)!==UPLOAD_ERR_NO_FILE);
        if ($willChangeFeatured) $changed[]='featured_image';
        $existingGallery = $this->galleryModel->where('package_id',$packageId)->orderBy('display_order','ASC')->findAll();
        $existingCount = count($existingGallery);
        // --- GALLERY OWNERSHIP & VALIDATION (must happen before limit/ordering) ---
        $existingMap = [];
        foreach ($existingGallery as $g) $existingMap[(int)$g['id']] = $g;

        // Validate existingGalleryIds ownership / malformed / duplicate / cross-package / deleted / unknown
        if (!empty($existingGalleryIds)) {
            // Filter out empty strings that controller left via array_filter, but harden: ensure no empty string sneaks
            $filteredIds = array_values(array_filter($existingGalleryIds, fn($v)=> $v !== '' && $v !== null));
            // Update variable to filtered for further logic (empty strings ignored safely)
            $existingGalleryIds = $filteredIds;
            $galleryIdErr = $this->validateGalleryIds($existingGalleryIds, $existingMap);
            if ($galleryIdErr !== null) {
                return ['success'=>false,'errors'=>['gallery_images'=>$galleryIdErr],'data'=>$normalized ?? $input];
            }
        } else {
            // Ensure empty stays empty array
            $existingGalleryIds = [];
        }

        // Gallery limit FINAL state: retained unique count + new files count must <=10
        $retainedCount = count(array_unique(array_map('intval', $existingGalleryIds)));
        $nonEmptyCount = count($nonEmptyGalleryFiles);
        $finalCount = $retainedCount + $nonEmptyCount;
        if ($finalCount > self::MAX_GALLERY_COUNT) {
            return ['success'=>false,'errors'=>['gallery_images'=>'Too many gallery images. Maximum '.self::MAX_GALLERY_COUNT.' allowed.'],'data'=>$normalized];
        }
        // Also handle case where no new files but retained alone >10 (should not happen via ownership but check)
        if ($retainedCount > self::MAX_GALLERY_COUNT) {
            return ['success'=>false,'errors'=>['gallery_images'=>'Too many gallery images.']];
        }

        // Validate galleryOrder ownership / duplicates / malformed if provided
        if (!empty($galleryOrder)) {
            // galleryOrder may be presented as JSON or array; controller already decoded, but harden
            // Filter empty strings
            $filteredOrder = array_values(array_filter($galleryOrder, fn($v)=> $v !== '' && $v !== null));
            $galleryOrder = $filteredOrder;
            $orderErr = $this->validateGalleryOrder($galleryOrder, $existingGalleryIds);
            if ($orderErr !== null) {
                return ['success'=>false,'errors'=>['gallery_images'=>$orderErr],'data'=>$normalized];
            }
        }

        $newCount = $retainedCount + $nonEmptyCount;
        if ($existingCount !== $newCount) $changed[]='gallery';
        // Also detect alt changes
        // Will be handled in transaction but mark gallery changed if any alt diff?
        // Simple: if any existing alt differs from submitted alt, mark
        // (do after alt map constructed inside transaction? For now mark if alt arrays non-empty)
        if (!empty($existingGalleryAlt)) $changed[]='gallery';

        $db = \Config\Database::connect();
        $newFiles = [];
        $oldFilesToDelete = []; // after commit
        $oldFeaturedToDelete = null;

        $db->transStart();
        try {
            $packageDataForUpdate = [
                'name'              => $normalized['name'],
                'slug'              => $normalized['slug'],
                'short_description' => $normalized['short_description'],
                'full_description'  => $normalized['full_description'],
                'regular_price'     => $normalized['regular_price'],
                'selling_price'     => $normalized['selling_price'],
                'duration_value'    => $normalized['duration_value'],
                'duration_unit'     => $normalized['duration_unit'],
                'badge'             => $normalized['badge'],
                'cta_label'         => $normalized['cta_label'],
                'google_form_url'   => $normalized['google_form_url'],
                'whatsapp_template' => $normalized['whatsapp_template'],
                'display_order'     => $normalized['display_order'],
                'is_featured'       => $normalized['is_featured'],
                'is_active'         => $normalized['is_active'],
            ];
            // === FEATURED OWNERSHIP: old path from DB only, never from client ===
            $newFeaturedPath = null;
            if ($featuredFile!==null && $this->getUploadError($featuredFile)!==UPLOAD_ERR_NO_FILE) {
                $newFeaturedPath = $this->storeUploadedFile($featuredFile, $packageId, 'featured');
                $newFiles[] = $newFeaturedPath;
                $packageDataForUpdate['featured_image'] = $newFeaturedPath;
                if ($oldFeatured) $oldFeaturedToDelete = $oldFeatured;
                $changed[]='featured_image';
            } elseif ($removeFeatured) {
                $packageDataForUpdate['featured_image'] = null;
                if ($oldFeatured) $oldFeaturedToDelete = $oldFeatured;
                $changed[]='featured_image';
            } else {
                // Keep existing — do not read any path from input
            }

            $this->packageModel->skipValidation(true);
            $result = $this->packageModel->update($packageId, $packageDataForUpdate);
            if ($result===false) throw new \RuntimeException('Package update failed: '.json_encode($this->packageModel->errors()));
            $stillExists = $this->packageModel->find($packageId);
            if ($stillExists===null) throw new \RuntimeException('Package disappeared');

            // Features: delete and reinsert
            $this->featureModel->where('package_id',$packageId)->delete();
            if (self::$simulateFeatureFailure) throw new \RuntimeException('Simulated feature failure');
            $seq=1;
            foreach (($normalized['features'] ?? []) as $t) {
                $trim=trim((string)$t);
                if ($trim==='') continue;
                $this->featureModel->skipValidation(true);
                $fid=$this->featureModel->insert(['package_id'=>$packageId,'feature_text'=>$trim,'display_order'=>$seq++,'is_active'=>1], true);
                if ($fid===false) throw new \RuntimeException('Feature insert failed');
            }

            // Options
            if (array_key_exists('options', $normalized)) {
                $this->savePackageOptions($packageId, $normalized['options']);
            }

            // Gallery: update retained, delete removed, insert new (preserve IDs, server-controlled order)
            // Re-validate map (already validated) but ensure no TOCTOU: re-fetch inside transaction? Use existingMap as of start, but check still
            $retainedIds = array_map('intval', $existingGalleryIds);
            // Delete removed (ownership already ensured)
            foreach ($existingGallery as $g) {
                if (!in_array((int)$g['id'], $retainedIds, true)) {
                    $oldFilesToDelete[] = $g['image_path'];
                    $this->galleryModel->delete((int)$g['id']);
                }
            }
            // Build altMap safely without warnings
            $altMap = [];
            foreach ($existingGalleryIds as $idx=>$eid) {
                $rawAlt = $existingGalleryAlt[$idx] ?? null;
                // Sanitize alt as plain text, no HTML, no array warnings
                $altMap[(int)$eid] = $this->sanitizeAltText($rawAlt);
                // Note: if sanitize returns null, we will keep existing alt? Actually spec says alt update: if submitted alt is null empty, keep existing? Original code used altMap eid => rawAlt, else existing. We'll honor sanitize result: if raw submitted and sanitize null, we treat as null (clear)? But to preserve, we fallback to existing later.
                // To be explicit, if alt was submitted as array/object, sanitize returns null, we will treat as provided but null (so clear alt). That's safe.
            }
            $orderIds = !empty($galleryOrder) ? $galleryOrder : $existingGalleryIds;
            // Filter orderIds to retained unique set, server-controlled sequential order
            $orderedRetained = array_values(array_filter(array_map('intval', $orderIds), fn($id)=>in_array((int)$id, $retainedIds, true)));
            // Ensure uniqueness for order (already validated) but deduplicate just in case
            $orderedRetained = array_values(array_unique($orderedRetained));
            // Ensure all retained are included if orderIds missed some
            if (count($orderedRetained) !== count($retainedIds)) {
                foreach ($retainedIds as $rid) {
                    if (!in_array($rid, $orderedRetained, true)) $orderedRetained[] = $rid;
                }
            }
            $gSeq = 1;
            foreach ($orderedRetained as $eid) {
                // Determine alt: if altMap has entry for this eid (even if null due to sanitize), use it; else fallback to existingMap's alt_text
                $hasAltSubmission = array_key_exists($eid, $altMap);
                $alt = $hasAltSubmission ? $altMap[$eid] : ($existingMap[$eid]['alt_text'] ?? null);
                // If altMap had sanitized null because empty, we respect null (clears alt). If no submission, fallback already done.
                // Ensure no warnings: alt is already sanitized or null
                $this->galleryModel->skipValidation(true);
                // alt_text is plain text, never HTML, truncated 300 already
                $this->galleryModel->update((int)$eid, ['display_order'=>$gSeq++, 'alt_text'=>$alt]);
            }
            // Insert new files with server-derived display_order sequential
            foreach ($galleryFiles as $idx=>$gfile) {
                if ($this->getUploadError($gfile)===UPLOAD_ERR_NO_FILE) continue;
                $path = $this->storeUploadedFile($gfile, $packageId, 'gallery');
                $newFiles[] = $path;
                $original = $this->sanitizeOriginalName($this->getClientName($gfile));
                $this->galleryModel->skipValidation(true);
                $gid = $this->galleryModel->insert(['package_id'=>$packageId,'image_path'=>$path,'original_name'=>$original,'alt_text'=>null,'display_order'=>$gSeq++], true);
                if ($gid===false) throw new \RuntimeException('Gallery insert new failed');
            }

            $db->transComplete();
            if ($db->transStatus()===false) throw new \RuntimeException('Transaction failed');

            // After commit, delete old files (filesystem compensating)
            if ($oldFeaturedToDelete) $this->deleteStoredFile($oldFeaturedToDelete);
            foreach ($oldFilesToDelete as $of) $this->deleteStoredFile($of);

            try {
                (new \App\Services\AdminActivityService())->log('package.updated', $adminId, 'package', $packageId, 'Package updated: '.$normalized['name'], ['package_id'=>$packageId,'slug'=>$normalized['slug'],'changed_fields'=>$changed]);
            } catch (\Throwable $e) {}

            return ['success'=>true,'id'=>$packageId,'data'=>$normalized,'changed_fields'=>$changed];
        } catch (\Throwable $e) {
            $db->transRollback();
            foreach ($newFiles as $f) $this->deleteStoredFile($f);
            log_message('error','PackageService::updatePackageWithMedia failed: '.$e->getMessage());
            $msg=$e->getMessage();
            if (str_contains($msg,'Duplicate') || str_contains($msg,'1062') || str_contains(strtolower($msg),'unique')) return ['success'=>false,'errors'=>['slug'=>'Slug is already in use.'],'data'=>$normalized ?? $input];
            if (str_contains($msg,'disappeared')) return ['success'=>false,'notFound'=>true,'errors'=>['package'=>'Package not found.']];
            if (str_contains($msg,'Invalid gallery')) return ['success'=>false,'errors'=>['gallery_images'=>$msg],'data'=>$normalized ?? $input];
            return ['success'=>false,'errors'=>['exception'=>'Failed to update package.'],'data'=>$normalized ?? $input];
        }
    }

    // ==================== DURATION & PRICING OPTIONS HELPERS ====================

    public function validateOptions(array $optionsRaw): array
    {
        $errors = [];
        $normalized = [];
        $allowedUnits = ['day', 'week', 'month', 'year', 'days', 'weeks', 'months', 'years'];

        $safeStr = static function($v): string {
            if (is_array($v) || is_object($v)) return '';
            if ($v === null) return '';
            return trim((string)$v);
        };

        foreach ($optionsRaw as $idx => $opt) {
            if (!is_array($opt)) continue;
            $optErrors = [];
            $name = $safeStr($opt['name'] ?? '');
            $durValRaw = $safeStr($opt['duration_value'] ?? '');
            $durUnit = strtolower($safeStr($opt['duration_unit'] ?? 'month'));
            $priceRaw = $safeStr($opt['price'] ?? '');
            $shortDesc = $safeStr($opt['short_description'] ?? '');
            $isActive = isset($opt['is_active']) ? ($opt['is_active'] === '1' || $opt['is_active'] === 1 || $opt['is_active'] === 'on' ? 1 : 0) : 1;

            if ($name === '') {
                $optErrors['name'] = 'Option name is required.';
            } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
                $optErrors['name'] = 'Option name must be between 2 and 150 characters.';
            }

            if ($durValRaw === '' || !ctype_digit($durValRaw) || (int)$durValRaw <= 0) {
                $optErrors['duration_value'] = 'Duration value must be a positive integer.';
            }

            if (!in_array($durUnit, $allowedUnits, true)) {
                $optErrors['duration_unit'] = 'Invalid duration unit.';
            }

            if ($priceRaw === '' || !preg_match('/^\d+(\.\d{1,2})?$/', $priceRaw) || \bccomp($priceRaw, '0', 2) <= 0) {
                $optErrors['price'] = 'Price must be a valid positive amount.';
            }

            if (mb_strlen($shortDesc) > 300) {
                $optErrors['short_description'] = 'Short description must not exceed 300 characters.';
            }

            // Features for this option
            $featuresRaw = $opt['features'] ?? $opt['inclusions'] ?? [];
            if (!is_array($featuresRaw)) $featuresRaw = [];
            $features = [];
            foreach ($featuresRaw as $f) {
                $fText = $safeStr($f);
                if ($fText !== '') {
                    if (mb_strlen($fText) > 300) {
                        $optErrors['features'] = 'Inclusion text must not exceed 300 characters.';
                    } else {
                        $features[] = $fText;
                    }
                }
            }

            if (!empty($optErrors)) {
                $errors[$idx] = $optErrors;
            }

            $unitMap = [
                'days' => 'day', 'day' => 'day',
                'weeks' => 'week', 'week' => 'week',
                'months' => 'month', 'month' => 'month',
                'years' => 'year', 'year' => 'year',
            ];
            $normUnit = $unitMap[$durUnit] ?? 'month';

            $normalized[] = [
                'id'                => isset($opt['id']) && ctype_digit((string)$opt['id']) ? (int)$opt['id'] : null,
                'name'              => $name,
                'duration_value'    => (int)$durValRaw,
                'duration_unit'     => $normUnit,
                'price'             => $priceRaw,
                'short_description' => $shortDesc !== '' ? $shortDesc : null,
                'is_active'         => $isActive,
                'sort_order'        => (int)($opt['sort_order'] ?? $idx),
                'features'          => $features,
            ];
        }

        return [$errors, $normalized];
    }

    public function savePackageOptions(int $packageId, array $options): void
    {
        $optionModel = new \App\Models\PackageOptionModel();
        $optionFeatureModel = new \App\Models\PackageOptionFeatureModel();

        // Fetch existing non-deleted options for this package
        $existingOptions = $optionModel->where('package_id', $packageId)->findAll();
        $existingIds = array_column($existingOptions, 'id');

        if (!empty($existingIds)) {
            // Delete option features for existing options
            $optionFeatureModel->whereIn('package_option_id', $existingIds)->delete();
            // Soft delete existing options
            $optionModel->whereIn('id', $existingIds)->delete();
        }

        $sortOrder = 1;
        foreach ($options as $opt) {
            if (empty($opt['name'])) continue;

            $durationUnit = strtolower(trim((string)($opt['duration_unit'] ?? 'month')));
            $unitMap = [
                'days' => 'day', 'day' => 'day',
                'weeks' => 'week', 'week' => 'week',
                'months' => 'month', 'month' => 'month',
                'years' => 'year', 'year' => 'year',
            ];
            $normUnit = $unitMap[$durationUnit] ?? 'month';

            $optData = [
                'package_id'        => $packageId,
                'name'              => trim((string)$opt['name']),
                'duration_value'    => (int)($opt['duration_value'] ?? 1),
                'duration_unit'     => $normUnit,
                'price'             => number_format((float)($opt['price'] ?? 0), 2, '.', ''),
                'short_description' => !empty($opt['short_description']) ? trim((string)$opt['short_description']) : null,
                'sort_order'        => $sortOrder++,
                'is_active'         => isset($opt['is_active']) ? (int)$opt['is_active'] : 1,
            ];

            $optionModel->skipValidation(true);
            $optId = $optionModel->insert($optData, true);
            if ($optId === false) {
                throw new \RuntimeException('Failed to insert package option: ' . json_encode($optionModel->errors()));
            }
            $optId = (int)$optId;

            // Insert option features
            $featSeq = 1;
            $features = $opt['features'] ?? [];
            foreach ($features as $fText) {
                $trimmed = trim((string)$fText);
                if ($trimmed === '') continue;
                $featData = [
                    'package_option_id' => $optId,
                    'feature_text'      => $trimmed,
                    'sort_order'        => $featSeq++,
                ];
                $optionFeatureModel->skipValidation(true);
                $optionFeatureModel->insert($featData);
            }
        }
    }

    public function getPackageOptions(int $packageId, bool $activeOnly = false): array
    {
        $optionModel = new \App\Models\PackageOptionModel();
        $optionFeatureModel = new \App\Models\PackageOptionFeatureModel();

        $builder = $optionModel->where('package_id', $packageId);
        if ($activeOnly) {
            $builder->where('is_active', 1);
        }
        $options = $builder->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();

        if (empty($options)) {
            return [];
        }

        $optionIds = array_column($options, 'id');
        $allFeatures = $optionFeatureModel
            ->whereIn('package_option_id', $optionIds)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $featuresByOption = [];
        foreach ($allFeatures as $feat) {
            $optId = (int)$feat['package_option_id'];
            $featuresByOption[$optId][] = $feat['feature_text'];
        }

        foreach ($options as &$opt) {
            $optId = (int)$opt['id'];
            $opt['features'] = $featuresByOption[$optId] ?? [];
        }

        return $options;
    }

    public function getOptionCounts(array $packageIds): array
    {
        if (empty($packageIds)) return [];
        $optionModel = new \App\Models\PackageOptionModel();
        $rows = $optionModel
            ->select('package_id, COUNT(*) as cnt, MIN(price) as min_price')
            ->whereIn('package_id', array_map('intval', $packageIds))
            ->where('is_active', 1)
            ->groupBy('package_id')
            ->findAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['package_id']] = [
                'count'     => (int)$r['cnt'],
                'min_price' => $r['min_price'] !== null ? (float)$r['min_price'] : null,
            ];
        }
        foreach ($packageIds as $id) {
            if (!isset($map[(int)$id])) {
                $map[(int)$id] = ['count' => 0, 'min_price' => null];
            }
        }
        return $map;
    }

    public static function formatOptionDuration(int $value, string $unit): string
    {
        $unit = strtolower(trim($unit));
        $map = [
            'day'   => $value === 1 ? 'Day' : 'Days',
            'days'  => $value === 1 ? 'Day' : 'Days',
            'week'  => $value === 1 ? 'Week' : 'Weeks',
            'weeks' => $value === 1 ? 'Week' : 'Weeks',
            'month' => $value === 1 ? 'Month' : 'Months',
            'months'=> $value === 1 ? 'Month' : 'Months',
            'year'  => $value === 1 ? 'Year' : 'Years',
            'years' => $value === 1 ? 'Year' : 'Years',
        ];
        $label = $map[$unit] ?? ucfirst($unit);
        return $value . ' ' . $label;
    }
}

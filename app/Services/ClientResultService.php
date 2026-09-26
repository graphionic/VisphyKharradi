<?php

namespace App\Services;

use App\Models\ClientResultModel;
use App\Models\ClientResultMetricModel;
use App\Models\ClientResultMediaModel;
use App\Models\ClientResultReportModel;
use App\Models\ClientResultFocusAreaModel;
use App\Models\PackageModel;
use App\Services\AdminActivityService;
use App\Services\HtmlSanitizerService;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * ClientResultService — Phase 07B & 07B.1: Core Client Results Admin Service
 *
 * Orchestrates Client Results CRUD, focus areas, metrics, media proof,
 * defensive child record cleanup, image uploads, and audit logging.
 */
class ClientResultService
{
    private ClientResultModel $resultModel;
    private ClientResultMetricModel $metricModel;
    private ClientResultMediaModel $mediaModel;
    private ClientResultReportModel $reportModel;
    private ClientResultFocusAreaModel $focusAreaModel;
    private PackageModel $packageModel;
    private AdminActivityService $activityService;
    private HtmlSanitizerService $sanitizer;

    public const PER_PAGE = 15;
    public const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
    public const MAX_REPORT_SIZE = 10 * 1024 * 1024; // 10MB
    public const ALLOWED_IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp'];
    public const ALLOWED_REPORT_EXTS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    public const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    public const ALLOWED_REPORT_MIMES = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/jpg'       => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];
    public const REPORT_TYPES = [
        'lab_report'          => 'Lab Report',
        'assessment_report'   => 'Assessment Report',
        'progress_report'     => 'Progress Report',
        'body_composition'   => 'Body Composition',
        'fitness_assessment' => 'Fitness Assessment',
        'other'               => 'Other',
    ];

    public function __construct(
        ?ClientResultModel $resultModel = null,
        ?ClientResultMetricModel $metricModel = null,
        ?ClientResultMediaModel $mediaModel = null,
        ?ClientResultReportModel $reportModel = null,
        ?ClientResultFocusAreaModel $focusAreaModel = null,
        ?PackageModel $packageModel = null,
        ?AdminActivityService $activityService = null,
        ?HtmlSanitizerService $sanitizer = null
    ) {
        $this->resultModel = $resultModel ?? new ClientResultModel();
        $this->metricModel = $metricModel ?? new ClientResultMetricModel();
        $this->mediaModel = $mediaModel ?? new ClientResultMediaModel();
        $this->reportModel = $reportModel ?? new ClientResultReportModel();
        $this->focusAreaModel = $focusAreaModel ?? new ClientResultFocusAreaModel();
        $this->packageModel = $packageModel ?? new PackageModel();
        $this->activityService = $activityService ?? new AdminActivityService();
        $this->sanitizer = $sanitizer ?? new HtmlSanitizerService();
    }

    /**
     * Get paginated admin Client Results list.
     */
    public function getAdminList(array $params = []): array
    {
        $q = trim((string) ($params['q'] ?? ''));
        $status = (string) ($params['status'] ?? 'all');
        $featured = (string) ($params['featured'] ?? 'all');
        $sort = (string) ($params['sort'] ?? 'display_order');
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['perPage'] ?? self::PER_PAGE);

        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = self::PER_PAGE;

        $builder = $this->resultModel->where('deleted_at IS NULL', null, false);

        if ($q !== '') {
            $builder->groupStart()
                ->like('client_display_name', $q)
                ->orLike('client_subtitle', $q)
                ->orLike('short_testimonial', $q)
                ->orLike('program_name_snapshot', $q)
                ->groupEnd();
        }

        if ($status === 'active') {
            $builder->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $builder->where('is_active', 0);
        }

        if ($featured === 'featured') {
            $builder->where('is_featured', 1);
        } elseif ($featured === 'not_featured') {
            $builder->where('is_featured', 0);
        }

        switch ($sort) {
            case 'newest':
                $builder->orderBy('created_at', 'DESC');
                break;
            case 'oldest':
                $builder->orderBy('created_at', 'ASC');
                break;
            case 'name_asc':
                $builder->orderBy('client_display_name', 'ASC');
                break;
            default:
                $builder->orderBy('display_order', 'ASC')->orderBy('id', 'ASC');
                break;
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        $results = $builder->paginate($perPage, 'default', $page);
        $pager = $this->resultModel->pager;

        return [
            'results'     => $results,
            'total'       => $total,
            'currentPage' => $page,
            'perPage'     => $perPage,
            'pager'       => $pager,
            'filters'     => [
                'q'        => $q,
                'status'   => $status,
                'featured' => $featured,
                'sort'     => $sort,
            ],
        ];
    }

    /**
     * Get single Client Result by ID.
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) return null;
        $res = $this->resultModel
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->first();

        return $res ?: null;
    }

    /**
     * Create a new Client Result record.
     */
    public function createResult(array $data, ?UploadedFile $coverFile = null, ?int $adminId = null): array
    {
        $displayName = trim((string) ($data['client_display_name'] ?? ''));
        $subtitle = trim((string) ($data['client_subtitle'] ?? '')) ?: null;
        $testimonial = trim((string) ($data['short_testimonial'] ?? ''));
        $fullStory = trim((string) ($data['full_story'] ?? ''));
        $duration = trim((string) ($data['journey_duration'] ?? '')) ?: null;
        $packageId = !empty($data['package_id']) ? (int) $data['package_id'] : null;
        $customProgram = trim((string) ($data['program_name_snapshot'] ?? '')) ?: null;
        $displayOrder = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : 100;
        $isFeatured = !empty($data['is_featured']) ? 1 : 0;
        $isActive = !empty($data['is_active']) ? 1 : 0;

        // Package relationship & snapshot resolution
        $programSnapshot = $customProgram;
        if ($packageId !== null) {
            $pkg = $this->packageModel->find($packageId);
            if (!$pkg) {
                return [
                    'success' => false,
                    'errors'  => ['package_id' => 'Selected program/package does not exist.'],
                ];
            }
            $programSnapshot = $pkg['name'];
        }

        // Cover image processing
        $coverImagePath = null;
        if ($coverFile && $coverFile->isValid() && !$coverFile->hasMoved()) {
            $imgRes = $this->uploadCoverImage($coverFile);
            if (!$imgRes['success']) {
                return [
                    'success' => false,
                    'errors'  => ['cover_image' => $imgRes['error']],
                ];
            }
            $coverImagePath = $imgRes['path'];
        }

        $cleanStory = $this->sanitizer->sanitizeHtml($fullStory);

        $saveData = [
            'package_id'            => $packageId,
            'program_name_snapshot' => $programSnapshot,
            'client_display_name'   => $displayName,
            'client_subtitle'       => $subtitle,
            'short_testimonial'     => $testimonial,
            'full_story'            => $cleanStory,
            'journey_duration'      => $duration,
            'cover_image'           => $coverImagePath,
            'display_order'         => $displayOrder,
            'is_featured'           => $isFeatured,
            'is_active'             => $isActive,
        ];

        if (!$this->resultModel->validate($saveData)) {
            // Clean up uploaded image if model validation fails
            if ($coverImagePath) {
                $this->unlinkFile($coverImagePath);
            }
            return [
                'success' => false,
                'errors'  => $this->resultModel->errors(),
            ];
        }

        $id = $this->resultModel->insert($saveData);
        if (!$id) {
            if ($coverImagePath) {
                $this->unlinkFile($coverImagePath);
            }
            return [
                'success' => false,
                'errors'  => ['db' => 'Failed to insert Client Result into database.'],
            ];
        }

        // Sync Focus Areas if provided
        if (isset($data['focus_areas']) && is_array($data['focus_areas'])) {
            $this->syncFocusAreas((int) $id, $data['focus_areas'], $adminId);
        }

        $this->activityService->log(
            'client_result.created',
            $adminId,
            'client_result',
            (int) $id,
            "Created Client Result ID {$id}: '{$displayName}'",
            ['client_display_name' => $displayName, 'package_id' => $packageId, 'is_active' => $isActive]
        );

        return [
            'success' => true,
            'id'      => (int) $id,
        ];
    }

    /**
     * Update existing Client Result record.
     */
    public function updateResult(int $id, array $data, ?UploadedFile $coverFile = null, ?int $adminId = null): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'Client Result not found.'],
            ];
        }

        $displayName = trim((string) ($data['client_display_name'] ?? ''));
        $subtitle = trim((string) ($data['client_subtitle'] ?? '')) ?: null;
        $testimonial = trim((string) ($data['short_testimonial'] ?? ''));
        $fullStory = trim((string) ($data['full_story'] ?? ''));
        $duration = trim((string) ($data['journey_duration'] ?? '')) ?: null;
        $packageId = !empty($data['package_id']) ? (int) $data['package_id'] : null;
        $customProgram = trim((string) ($data['program_name_snapshot'] ?? '')) ?: null;
        $displayOrder = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : 100;
        $isFeatured = isset($data['is_featured']) ? (!empty($data['is_featured']) ? 1 : 0) : (int) $existing['is_featured'];
        $isActive = isset($data['is_active']) ? (!empty($data['is_active']) ? 1 : 0) : (int) $existing['is_active'];

        // Package relationship & snapshot resolution
        $programSnapshot = $existing['program_name_snapshot'];
        if ($packageId !== null) {
            $pkg = $this->packageModel->find($packageId);
            if (!$pkg) {
                return [
                    'success' => false,
                    'errors'  => ['package_id' => 'Selected program/package does not exist.'],
                ];
            }
            $programSnapshot = $pkg['name'];
        } elseif ($customProgram !== null) {
            $programSnapshot = $customProgram;
        }

        // Handle cover image replacement or removal
        $coverImagePath = $existing['cover_image'];
        $newUploadedPath = null;

        if (!empty($data['remove_cover_image']) && $coverImagePath) {
            $this->unlinkFile($coverImagePath);
            $coverImagePath = null;
        }

        if ($coverFile && $coverFile->isValid() && !$coverFile->hasMoved()) {
            $imgRes = $this->uploadCoverImage($coverFile);
            if (!$imgRes['success']) {
                return [
                    'success' => false,
                    'errors'  => ['cover_image' => $imgRes['error']],
                ];
            }
            $newUploadedPath = $imgRes['path'];
            if ($coverImagePath) {
                $this->unlinkFile($coverImagePath);
            }
            $coverImagePath = $newUploadedPath;
        }

        $cleanStory = $this->sanitizer->sanitizeHtml($fullStory);

        $saveData = [
            'package_id'            => $packageId,
            'program_name_snapshot' => $programSnapshot,
            'client_display_name'   => $displayName,
            'client_subtitle'       => $subtitle,
            'short_testimonial'     => $testimonial,
            'full_story'            => $cleanStory,
            'journey_duration'      => $duration,
            'cover_image'           => $coverImagePath,
            'display_order'         => $displayOrder,
            'is_featured'           => $isFeatured,
            'is_active'             => $isActive,
        ];

        if (!$this->resultModel->validate($saveData)) {
            if ($newUploadedPath) {
                $this->unlinkFile($newUploadedPath);
            }
            return [
                'success' => false,
                'errors'  => $this->resultModel->errors(),
            ];
        }

        $updated = $this->resultModel->update($id, $saveData);
        if (!$updated) {
            if ($newUploadedPath) {
                $this->unlinkFile($newUploadedPath);
            }
            return [
                'success' => false,
                'errors'  => ['db' => 'Failed to update Client Result in database.'],
            ];
        }

        // Sync Focus Areas if provided
        if (isset($data['focus_areas']) && is_array($data['focus_areas'])) {
            $this->syncFocusAreas($id, $data['focus_areas'], $adminId);
        }

        $this->activityService->log(
            'client_result.updated',
            $adminId,
            'client_result',
            $id,
            "Updated Client Result ID {$id}: '{$displayName}'",
            ['client_display_name' => $displayName, 'package_id' => $packageId, 'is_active' => $isActive]
        );

        return [
            'success' => true,
            'id'      => $id,
        ];
    }

    /**
     * Soft delete Client Result and defensively clean child records (NO DB FK CASCADE).
     */
    public function deleteResult(int $id, ?int $adminId = null): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'Client Result not found.'],
            ];
        }

        $db = \Config\Database::connect();

        // Collect physical file references before deleting DB records
        $reportFiles = $this->reportModel->where('client_result_id', $id)->findColumn('file_path') ?: [];
        $mediaFiles = $this->mediaModel->where('client_result_id', $id)->findColumn('file_path') ?: [];

        $db->transStart();

        // 1. Clean child records in client_result_focus_areas, client_result_metrics, client_result_media, client_result_reports
        $this->focusAreaModel->where('client_result_id', $id)->delete();
        $this->metricModel->where('client_result_id', $id)->delete();
        $this->mediaModel->where('client_result_id', $id)->delete();
        $this->reportModel->where('client_result_id', $id)->delete();

        // 2. Soft-delete master record
        $this->resultModel->delete($id);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return [
                'success' => false,
                'errors'  => ['db' => 'Transaction failed while deleting Client Result.'],
            ];
        }

        // 3. Post-commit physical file cleanup
        if (!empty($existing['cover_image'])) {
            $this->unlinkFile($existing['cover_image']);
        }
        foreach ($mediaFiles as $mPath) {
            if (!empty($mPath)) {
                $this->unlinkFile($mPath);
            }
        }
        foreach ($reportFiles as $rPath) {
            if (!empty($rPath)) {
                $this->unlinkReportFile($rPath);
            }
        }

        $this->activityService->log(
            'client_result.deleted',
            $adminId,
            'client_result',
            $id,
            "Deleted Client Result ID {$id}: '{$existing['client_display_name']}'",
            ['client_display_name' => $existing['client_display_name']]
        );

        return [
            'success' => true,
        ];
    }

    /**
     * Get complete aggregated Client Result proof payload for Admin Preview.
     * Supports mode = 'public' (default, visitor-facing view) or 'all' (includes private items with badges).
     */
    public function getCompleteResultForAdminPreview(int $clientResultId, string $mode = 'public'): ?array
    {
        $mode = strtolower(trim($mode)) === 'all' ? 'all' : 'public';

        $result = $this->getById($clientResultId);
        if (!$result) {
            return null;
        }

        // 1. Focus Areas
        $focusAreas = $this->getFocusAreasByResultId($clientResultId);

        // 2. Metrics
        $allMetrics = $this->metricModel
            ->where('client_result_id', $clientResultId)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $metrics = [];
        $publicMetricsCount = 0;
        foreach ($allMetrics as $m) {
            if ($m['is_public']) {
                $publicMetricsCount++;
            }
            if ($mode === 'all' || $m['is_public']) {
                $metrics[] = $m;
            }
        }

        // 3. Media Proof (Before, After, Progress, Gallery)
        $allMedia = $this->mediaModel
            ->where('client_result_id', $clientResultId)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $beforeInDb = null;
        $afterInDb = null;
        $progressInDb = [];
        $galleryInDb = [];

        foreach ($allMedia as $med) {
            if ($med['media_type'] === 'before' && $beforeInDb === null) {
                $beforeInDb = $med;
            } elseif ($med['media_type'] === 'after' && $afterInDb === null) {
                $afterInDb = $med;
            } elseif ($med['media_type'] === 'progress') {
                $progressInDb[] = $med;
            } elseif ($med['media_type'] === 'gallery') {
                $galleryInDb[] = $med;
            }
        }

        // Apply mode filtering to media
        $before = ($beforeInDb && ($mode === 'all' || $beforeInDb['is_public'])) ? $beforeInDb : null;
        $after = ($afterInDb && ($mode === 'all' || $afterInDb['is_public'])) ? $afterInDb : null;

        $progress = array_values(array_filter($progressInDb, fn($med) => $mode === 'all' || $med['is_public']));
        $gallery = array_values(array_filter($galleryInDb, fn($med) => $mode === 'all' || $med['is_public']));

        $publicProgressCount = count(array_filter($progressInDb, fn($med) => !empty($med['is_public'])));
        $publicGalleryCount = count(array_filter($galleryInDb, fn($med) => !empty($med['is_public'])));

        // 4. Reports / Evidence
        $allReports = $this->reportModel
            ->where('client_result_id', $clientResultId)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $reports = [];
        $publicReportsCount = 0;
        foreach ($allReports as $r) {
            if ($r['is_public']) {
                $publicReportsCount++;
            }
            if ($mode === 'all' || $r['is_public']) {
                $reports[] = $r;
            }
        }

        // 5. Factual Proof Readiness Summary (Content Presence Indicators)
        $readiness = [
            'has_cover_image'             => !empty($result['cover_image']),
            'focus_areas_count'           => count($focusAreas),
            'all_metrics_count'           => count($allMetrics),
            'public_metrics_count'        => $publicMetricsCount,
            'has_before_image'            => $beforeInDb !== null,
            'has_public_before_image'     => $beforeInDb !== null && !empty($beforeInDb['is_public']),
            'has_after_image'             => $afterInDb !== null,
            'has_public_after_image'      => $afterInDb !== null && !empty($afterInDb['is_public']),
            'progress_media_count'        => count($progressInDb),
            'public_progress_media_count' => $publicProgressCount,
            'gallery_media_count'         => count($galleryInDb),
            'public_gallery_media_count'  => $publicGalleryCount,
            'all_reports_count'           => count($allReports),
            'public_reports_count'        => $publicReportsCount,
            'has_full_story'              => !empty($result['full_story']),
            'has_short_testimonial'       => !empty($result['short_testimonial']),
        ];

        return [
            'mode'       => $mode,
            'result'     => $result,
            'focusAreas' => $focusAreas,
            'metrics'    => $metrics,
            'media'      => [
                'before'   => $before,
                'after'    => $after,
                'progress' => $progress,
                'gallery'  => $gallery,
            ],
            'reports'    => $reports,
            'readiness'  => $readiness,
        ];
    }

    /**
     * Get focus areas for a Client Result ordered by display_order.
     */
    public function getFocusAreasByResultId(int $clientResultId): array
    {
        if ($clientResultId <= 0) return [];
        return $this->focusAreaModel
            ->where('client_result_id', $clientResultId)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Synchronize transformation focus areas for a Client Result.
     */
    public function syncFocusAreas(int $clientResultId, array $rawLabels, ?int $adminId = null): array
    {
        if ($clientResultId <= 0) {
            return ['success' => false, 'errors' => ['id' => 'Invalid Client Result ID.']];
        }

        $cleanLabels = [];
        $seenKeys = [];

        foreach ($rawLabels as $label) {
            $trimmed = trim(strip_tags((string) $label));
            if ($trimmed === '') continue;

            $trimmed = preg_replace('/\s+/', ' ', $trimmed);
            if (mb_strlen($trimmed) > 100) {
                $trimmed = mb_substr($trimmed, 0, 100);
            }

            $key = mb_strtolower($trimmed);
            if (isset($seenKeys[$key])) continue;
            $seenKeys[$key] = true;

            $cleanLabels[] = [
                'client_result_id' => $clientResultId,
                'label'            => $trimmed,
                'display_order'    => count($cleanLabels),
            ];

            if (count($cleanLabels) >= 12) {
                break;
            }
        }

        $this->focusAreaModel->where('client_result_id', $clientResultId)->delete();

        if (!empty($cleanLabels)) {
            $this->focusAreaModel->insertBatch($cleanLabels);
        }

        return ['success' => true, 'count' => count($cleanLabels)];
    }

    /**
     * Toggle active publication status.
     */
    public function toggleActive(int $id, ?int $adminId = null): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'Client Result not found.'],
            ];
        }

        $newStatus = $existing['is_active'] ? 0 : 1;
        $this->resultModel->update($id, ['is_active' => $newStatus]);

        $this->activityService->log(
            'client_result.status_changed',
            $adminId,
            'client_result',
            $id,
            "Toggled active status for Client Result ID {$id} to " . ($newStatus ? 'active' : 'inactive'),
            ['is_active' => $newStatus]
        );

        return [
            'success'   => true,
            'is_active' => $newStatus,
        ];
    }

    /**
     * Toggle featured highlight status.
     */
    public function toggleFeatured(int $id, ?int $adminId = null): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'Client Result not found.'],
            ];
        }

        $newFeatured = $existing['is_featured'] ? 0 : 1;
        $this->resultModel->update($id, ['is_featured' => $newFeatured]);

        $this->activityService->log(
            'client_result.featured_changed',
            $adminId,
            'client_result',
            $id,
            "Toggled featured status for Client Result ID {$id} to " . ($newFeatured ? 'featured' : 'standard'),
            ['is_featured' => $newFeatured]
        );

        return [
            'success'     => true,
            'is_featured' => $newFeatured,
        ];
    }

    /**
     * Get all outcome metrics for a specific Client Result.
     */
    public function getMetricsByResultId(int $clientResultId): array
    {
        if ($clientResultId <= 0) return [];
        return $this->metricModel
            ->where('client_result_id', $clientResultId)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Get single metric by ID and verify parent Client Result ownership.
     */
    public function getMetricByIdAndResultId(int $metricId, int $clientResultId): ?array
    {
        if ($metricId <= 0 || $clientResultId <= 0) return null;
        $metric = $this->metricModel
            ->where('id', $metricId)
            ->where('client_result_id', $clientResultId)
            ->first();

        return $metric ?: null;
    }

    /**
     * Create a new outcome metric for a Client Result.
     */
    public function createMetric(int $clientResultId, array $data, ?int $adminId = null): array
    {
        $result = $this->getById($clientResultId);
        if (!$result) {
            return [
                'success' => false,
                'errors'  => ['client_result_id' => 'Client Result not found.'],
            ];
        }

        // Enforce maximum 30 metrics limit per Client Result
        if ($this->metricModel->where('client_result_id', $clientResultId)->countAllResults() >= 30) {
            return [
                'success' => false,
                'errors'  => ['metric_name' => 'Maximum limit of 30 outcome metrics per Client Result reached.'],
            ];
        }

        $metricName = trim((string) ($data['metric_name'] ?? ''));
        $beforeVal  = trim((string) ($data['before_value'] ?? ''));
        $afterVal   = trim((string) ($data['after_value'] ?? ''));
        $unit       = trim((string) ($data['unit'] ?? '')) ?: null;
        $context    = trim((string) ($data['context'] ?? '')) ?: null;
        $startDate  = trim((string) ($data['measurement_start_date'] ?? '')) ?: null;
        $endDate    = trim((string) ($data['measurement_end_date'] ?? '')) ?: null;
        $order      = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : 0;
        $isPublic   = !empty($data['is_public']) ? 1 : 0;

        // Date chronology validation
        if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
            return [
                'success' => false,
                'errors'  => ['measurement_end_date' => 'Measurement end date cannot be earlier than start date.'],
            ];
        }

        $saveData = [
            'client_result_id'       => $clientResultId,
            'metric_name'            => $metricName,
            'before_value'           => $beforeVal,
            'after_value'            => $afterVal,
            'unit'                   => $unit,
            'context'                => $context,
            'measurement_start_date' => $startDate,
            'measurement_end_date'   => $endDate,
            'display_order'          => $order,
            'is_public'              => $isPublic,
        ];

        if (!$this->metricModel->validate($saveData)) {
            return [
                'success' => false,
                'errors'  => $this->metricModel->errors(),
            ];
        }

        $id = $this->metricModel->insert($saveData);
        if (!$id) {
            return [
                'success' => false,
                'errors'  => ['db' => 'Failed to insert outcome metric into database.'],
            ];
        }

        $this->activityService->log(
            'client_result_metric.created',
            $adminId,
            'client_result_metric',
            (int) $id,
            "Added outcome metric '{$metricName}' to Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'metric_name' => $metricName, 'is_public' => $isPublic]
        );

        return [
            'success' => true,
            'id'      => (int) $id,
        ];
    }

    /**
     * Update an existing outcome metric with strict ownership verification.
     */
    public function updateMetric(int $metricId, int $clientResultId, array $data, ?int $adminId = null): array
    {
        $existing = $this->getMetricByIdAndResultId($metricId, $clientResultId);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'Metric not found or does not belong to this Client Result.'],
            ];
        }

        $metricName = trim((string) ($data['metric_name'] ?? ''));
        $beforeVal  = trim((string) ($data['before_value'] ?? ''));
        $afterVal   = trim((string) ($data['after_value'] ?? ''));
        $unit       = trim((string) ($data['unit'] ?? '')) ?: null;
        $context    = trim((string) ($data['context'] ?? '')) ?: null;
        $startDate  = trim((string) ($data['measurement_start_date'] ?? '')) ?: null;
        $endDate    = trim((string) ($data['measurement_end_date'] ?? '')) ?: null;
        $order      = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : 0;
        $isPublic   = isset($data['is_public']) ? (!empty($data['is_public']) ? 1 : 0) : (int) $existing['is_public'];

        // Date chronology validation
        if ($startDate && $endDate && strtotime($endDate) < strtotime($startDate)) {
            return [
                'success' => false,
                'errors'  => ['measurement_end_date' => 'Measurement end date cannot be earlier than start date.'],
            ];
        }

        $saveData = [
            'client_result_id'       => $clientResultId,
            'metric_name'            => $metricName,
            'before_value'           => $beforeVal,
            'after_value'            => $afterVal,
            'unit'                   => $unit,
            'context'                => $context,
            'measurement_start_date' => $startDate,
            'measurement_end_date'   => $endDate,
            'display_order'          => $order,
            'is_public'              => $isPublic,
        ];

        if (!$this->metricModel->validate($saveData)) {
            return [
                'success' => false,
                'errors'  => $this->metricModel->errors(),
            ];
        }

        $updated = $this->metricModel->update($metricId, $saveData);
        if (!$updated) {
            return [
                'success' => false,
                'errors'  => ['db' => 'Failed to update outcome metric in database.'],
            ];
        }

        $this->activityService->log(
            'client_result_metric.updated',
            $adminId,
            'client_result_metric',
            $metricId,
            "Updated outcome metric ID {$metricId} ('{$metricName}') for Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'metric_name' => $metricName, 'is_public' => $isPublic]
        );

        return [
            'success' => true,
            'id'      => $metricId,
        ];
    }

    /**
     * Delete an outcome metric with strict ownership verification.
     */
    public function deleteMetric(int $metricId, int $clientResultId, ?int $adminId = null): array
    {
        $existing = $this->getMetricByIdAndResultId($metricId, $clientResultId);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'Metric not found or does not belong to this Client Result.'],
            ];
        }

        $this->metricModel->delete($metricId);

        $this->activityService->log(
            'client_result_metric.deleted',
            $adminId,
            'client_result_metric',
            $metricId,
            "Deleted outcome metric ID {$metricId} ('{$existing['metric_name']}') from Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'metric_name' => $existing['metric_name']]
        );

        return [
            'success' => true,
        ];
    }

    /**
     * Toggle metric public visibility with strict ownership verification.
     */
    public function toggleMetricPublic(int $metricId, int $clientResultId, ?int $adminId = null): array
    {
        $existing = $this->getMetricByIdAndResultId($metricId, $clientResultId);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'Metric not found or does not belong to this Client Result.'],
            ];
        }

        $newPublic = $existing['is_public'] ? 0 : 1;
        $this->metricModel->update($metricId, ['is_public' => $newPublic]);

        $this->activityService->log(
            'client_result_metric.visibility_changed',
            $adminId,
            'client_result_metric',
            $metricId,
            "Toggled visibility for metric ID {$metricId} to " . ($newPublic ? 'public' : 'private'),
            ['client_result_id' => $clientResultId, 'is_public' => $newPublic]
        );

        return [
            'success'   => true,
            'is_public' => $newPublic,
        ];
    }

    /**
     * Get all visual proof media for a specific Client Result.
     */
    public function getMediaByResultId(int $clientResultId): array
    {
        if ($clientResultId <= 0) return ['before' => null, 'after' => null, 'gallery' => [], 'all' => []];

        $allMedia = $this->mediaModel
            ->where('client_result_id', $clientResultId)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $before = null;
        $after = null;
        $gallery = [];

        foreach ($allMedia as $m) {
            if ($m['media_type'] === 'before' && $before === null) {
                $before = $m;
            } elseif ($m['media_type'] === 'after' && $after === null) {
                $after = $m;
            } else {
                $gallery[] = $m;
            }
        }

        return [
            'before'  => $before,
            'after'   => $after,
            'gallery' => $gallery,
            'all'     => $allMedia,
        ];
    }

    /**
     * Get single media item by ID and verify parent ownership.
     */
    public function getMediaByIdAndResultId(int $mediaId, int $clientResultId): ?array
    {
        if ($mediaId <= 0 || $clientResultId <= 0) return null;
        $media = $this->mediaModel
            ->where('id', $mediaId)
            ->where('client_result_id', $clientResultId)
            ->first();

        return $media ?: null;
    }

    /**
     * Upload or Replace primary BEFORE or AFTER image for a Client Result (Singleton per type).
     */
    public function savePrimaryMedia(int $clientResultId, string $type, UploadedFile $file, array $data = [], ?int $adminId = null): array
    {
        if (!in_array($type, ['before', 'after'], true)) {
            return ['success' => false, 'errors' => ['media_type' => 'Invalid primary media type.']];
        }

        $result = $this->getById($clientResultId);
        if (!$result) {
            return ['success' => false, 'errors' => ['client_result_id' => 'Client Result not found.']];
        }

        if (!$file->isValid() || $file->hasMoved()) {
            return ['success' => false, 'errors' => ['file' => 'Uploaded file is invalid or missing.']];
        }

        $uploadRes = $this->uploadCoverImage($file);
        if (!$uploadRes['success']) {
            return ['success' => false, 'errors' => ['file' => $uploadRes['error']]];
        }

        $newPath   = $uploadRes['path'];
        $caption   = trim((string) ($data['caption'] ?? '')) ?: null;
        $mediaDate = trim((string) ($data['media_date'] ?? '')) ?: null;
        $isPublic  = !empty($data['is_public']) ? 1 : 0;

        $existing = $this->mediaModel
            ->where('client_result_id', $clientResultId)
            ->where('media_type', $type)
            ->first();

        if ($existing) {
            $oldPath = $existing['file_path'];
            $updateData = [
                'file_path'  => $newPath,
                'caption'    => $caption !== null ? $caption : $existing['caption'],
                'media_date' => $mediaDate !== null ? $mediaDate : $existing['media_date'],
                'is_public'  => isset($data['is_public']) ? $isPublic : $existing['is_public'],
            ];

            if (!$this->mediaModel->validate($updateData)) {
                $this->unlinkFile($newPath);
                return ['success' => false, 'errors' => $this->mediaModel->errors()];
            }

            $updated = $this->mediaModel->update($existing['id'], $updateData);
            if (!$updated) {
                $this->unlinkFile($newPath);
                return ['success' => false, 'errors' => ['db' => 'Failed to update primary media in database.']];
            }

            if ($oldPath && $oldPath !== $newPath) {
                $this->unlinkFile($oldPath);
            }

            $this->activityService->log(
                'client_result_media.replaced',
                $adminId,
                'client_result_media',
                (int) $existing['id'],
                "Replaced primary '{$type}' image for Client Result ID {$clientResultId}",
                ['client_result_id' => $clientResultId, 'media_type' => $type, 'is_public' => $isPublic]
            );

            return ['success' => true, 'id' => (int) $existing['id']];
        }

        $saveData = [
            'client_result_id' => $clientResultId,
            'media_type'       => $type,
            'file_path'        => $newPath,
            'caption'          => $caption,
            'media_date'       => $mediaDate,
            'display_order'    => 0,
            'is_public'        => $isPublic,
        ];

        if (!$this->mediaModel->validate($saveData)) {
            $this->unlinkFile($newPath);
            return ['success' => false, 'errors' => $this->mediaModel->errors()];
        }

        $id = $this->mediaModel->insert($saveData);
        if (!$id) {
            $this->unlinkFile($newPath);
            return ['success' => false, 'errors' => ['db' => 'Failed to insert primary media into database.']];
        }

        $this->activityService->log(
            'client_result_media.created',
            $adminId,
            'client_result_media',
            (int) $id,
            "Added primary '{$type}' image for Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'media_type' => $type, 'is_public' => $isPublic]
        );

        return ['success' => true, 'id' => (int) $id];
    }

    /**
     * Upload one or multiple progress/gallery images for a Client Result.
     */
    public function uploadGalleryMedia(int $clientResultId, array $files, array $data = [], ?int $adminId = null): array
    {
        $result = $this->getById($clientResultId);
        if (!$result) {
            return ['success' => false, 'errors' => ['client_result_id' => 'Client Result not found.']];
        }

        $type = (string) ($data['media_type'] ?? 'progress');
        if (!in_array($type, ['progress', 'gallery'], true)) {
            $type = 'progress';
        }

        // Enforce maximum limit of 30 gallery images per type per Client Result
        $existingCount = $this->mediaModel
            ->where('client_result_id', $clientResultId)
            ->where('media_type', $type)
            ->countAllResults();

        if ($existingCount + count($files) > 30) {
            return [
                'success' => false,
                'errors'  => ['gallery_files' => "Maximum limit of 30 {$type} images per Client Result reached (currently {$existingCount})."],
            ];
        }

        $caption   = trim((string) ($data['caption'] ?? '')) ?: null;
        $mediaDate = trim((string) ($data['media_date'] ?? '')) ?: null;
        $order     = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : 0;
        $isPublic  = !empty($data['is_public']) ? 1 : 0;

        if (empty($files)) {
            return ['success' => false, 'errors' => ['files' => 'No files selected for upload.']];
        }

        $uploadedIds = [];
        $errors = [];

        foreach ($files as $idx => $file) {
            if (!($file instanceof UploadedFile) || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $uploadRes = $this->uploadCoverImage($file);
            if (!$uploadRes['success']) {
                $errors[] = "File #" . ($idx + 1) . ": " . $uploadRes['error'];
                continue;
            }

            $saveData = [
                'client_result_id' => $clientResultId,
                'media_type'       => $type,
                'file_path'        => $uploadRes['path'],
                'caption'          => $caption,
                'media_date'       => $mediaDate,
                'display_order'    => $order + $idx,
                'is_public'        => $isPublic,
            ];

            if (!$this->mediaModel->validate($saveData)) {
                $this->unlinkFile($uploadRes['path']);
                $errors[] = "File #" . ($idx + 1) . " validation failed.";
                continue;
            }

            $id = $this->mediaModel->insert($saveData);
            if ($id) {
                $uploadedIds[] = (int) $id;
                $this->activityService->log(
                    'client_result_media.created',
                    $adminId,
                    'client_result_media',
                    (int) $id,
                    "Uploaded {$type} photo for Client Result ID {$clientResultId}",
                    ['client_result_id' => $clientResultId, 'media_type' => $type, 'is_public' => $isPublic]
                );
            } else {
                $this->unlinkFile($uploadRes['path']);
            }
        }

        if (empty($uploadedIds) && !empty($errors)) {
            return ['success' => false, 'errors' => ['upload' => implode(' ', $errors)]];
        }

        return ['success' => true, 'uploaded_ids' => $uploadedIds, 'errors' => $errors];
    }

    /**
     * Update media item metadata.
     */
    public function updateMedia(int $mediaId, int $clientResultId, array $data, ?int $adminId = null): array
    {
        $existing = $this->getMediaByIdAndResultId($mediaId, $clientResultId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['id' => 'Media not found or does not belong to this Client Result.']];
        }

        $caption   = trim((string) ($data['caption'] ?? '')) ?: null;
        $mediaDate = trim((string) ($data['media_date'] ?? '')) ?: null;
        $order     = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : (int) $existing['display_order'];
        $isPublic  = isset($data['is_public']) ? (!empty($data['is_public']) ? 1 : 0) : (int) $existing['is_public'];
        $type      = (string) ($data['media_type'] ?? $existing['media_type']);

        if (!in_array($type, ['before', 'after', 'progress', 'gallery'], true)) {
            $type = $existing['media_type'];
        }

        $saveData = [
            'client_result_id' => $clientResultId,
            'media_type'       => $type,
            'file_path'        => $existing['file_path'],
            'caption'          => $caption,
            'media_date'       => $mediaDate,
            'display_order'    => $order,
            'is_public'        => $isPublic,
        ];

        if (!$this->mediaModel->validate($saveData)) {
            return ['success' => false, 'errors' => $this->mediaModel->errors()];
        }

        $updated = $this->mediaModel->update($mediaId, $saveData);
        if (!$updated) {
            return ['success' => false, 'errors' => ['db' => 'Failed to update media item in database.']];
        }

        $this->activityService->log(
            'client_result_media.updated',
            $adminId,
            'client_result_media',
            $mediaId,
            "Updated media ID {$mediaId} metadata for Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'media_type' => $type, 'is_public' => $isPublic]
        );

        return ['success' => true, 'id' => $mediaId];
    }

    /**
     * Delete media item and clean up physical file.
     */
    public function deleteMedia(int $mediaId, int $clientResultId, ?int $adminId = null): array
    {
        $existing = $this->getMediaByIdAndResultId($mediaId, $clientResultId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['id' => 'Media not found or does not belong to this Client Result.']];
        }

        $filePath = $existing['file_path'];

        $this->mediaModel->delete($mediaId);

        if (!empty($filePath)) {
            $this->unlinkFile($filePath);
        }

        $this->activityService->log(
            'client_result_media.deleted',
            $adminId,
            'client_result_media',
            $mediaId,
            "Deleted media ID {$mediaId} ({$existing['media_type']}) from Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'media_type' => $existing['media_type']]
        );

        return ['success' => true];
    }

    /**
     * Toggle media public visibility.
     */
    public function toggleMediaPublic(int $mediaId, int $clientResultId, ?int $adminId = null): array
    {
        $existing = $this->getMediaByIdAndResultId($mediaId, $clientResultId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['id' => 'Media not found or does not belong to this Client Result.']];
        }

        $newPublic = $existing['is_public'] ? 0 : 1;
        $this->mediaModel->update($mediaId, ['is_public' => $newPublic]);

        $this->activityService->log(
            'client_result_media.visibility_changed',
            $adminId,
            'client_result_media',
            $mediaId,
            "Toggled visibility for media ID {$mediaId} to " . ($newPublic ? 'public' : 'private'),
            ['client_result_id' => $clientResultId, 'is_public' => $newPublic]
        );

        return ['success' => true, 'is_public' => $newPublic];
    }

    /**
     * Upload cover image securely to public/uploads/client_results/.
     */
    private function uploadCoverImage(UploadedFile $file): array
    {
        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            return ['success' => false, 'error' => 'Cover image size must not exceed 5MB.'];
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, self::ALLOWED_IMAGE_EXTS, true)) {
            return ['success' => false, 'error' => 'Cover image must be a JPG, PNG, or WebP file.'];
        }

        $mime = $file->getMimeType();
        if (!array_key_exists($mime, self::ALLOWED_IMAGE_MIMES)) {
            return ['success' => false, 'error' => 'Invalid image MIME type: ' . $mime];
        }

        $targetDir = FCPATH . 'uploads/client_results/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $newName = $file->getRandomName();
        try {
            $file->move($targetDir, $newName);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Failed to save uploaded image: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'path'    => 'uploads/client_results/' . $newName,
        ];
    }

    /**
     * Get all reports for a specific Client Result ordered by display_order.
     */
    public function getReportsByResultId(int $clientResultId): array
    {
        if ($clientResultId <= 0) return [];
        return $this->reportModel
            ->where('client_result_id', $clientResultId)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Get single report by ID and verify parent Client Result ownership.
     */
    public function getReportByIdAndResultId(int $reportId, int $clientResultId): ?array
    {
        if ($reportId <= 0 || $clientResultId <= 0) return null;
        $report = $this->reportModel
            ->where('id', $reportId)
            ->where('client_result_id', $clientResultId)
            ->first();

        return $report ?: null;
    }

    /**
     * Create a new supporting report/evidence record for a Client Result.
     */
    public function createReport(int $clientResultId, array $data, UploadedFile $file, ?int $adminId = null): array
    {
        $result = $this->getById($clientResultId);
        if (!$result) {
            return ['success' => false, 'errors' => ['client_result_id' => 'Client Result not found.']];
        }

        if (!$file->isValid() || $file->hasMoved()) {
            return ['success' => false, 'errors' => ['file' => 'Uploaded report file is invalid or missing.']];
        }

        $uploadRes = $this->uploadReportFile($file, $clientResultId);
        if (!$uploadRes['success']) {
            return ['success' => false, 'errors' => ['file' => $uploadRes['error']]];
        }

        $title = trim((string) ($data['report_title'] ?? ''));
        $type = (string) ($data['report_type'] ?? 'other');
        if (!array_key_exists($type, self::REPORT_TYPES)) {
            $type = 'other';
        }

        $reportDate = trim((string) ($data['report_date'] ?? '')) ?: null;
        $description = trim((string) ($data['description'] ?? '')) ?: null;
        $displayOrder = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : 0;
        $isPublic = !empty($data['is_public']) ? 1 : 0;

        $saveData = [
            'client_result_id' => $clientResultId,
            'report_title'     => $title,
            'report_type'      => $type,
            'report_date'      => $reportDate,
            'file_path'        => $uploadRes['path'],
            'file_mime'        => $uploadRes['mime'],
            'file_size'        => $uploadRes['size'],
            'description'      => $description,
            'display_order'    => $displayOrder,
            'is_public'        => $isPublic,
        ];

        if (!$this->reportModel->validate($saveData)) {
            $this->unlinkReportFile($uploadRes['path']);
            return ['success' => false, 'errors' => $this->reportModel->errors()];
        }

        $id = $this->reportModel->insert($saveData);
        if (!$id) {
            $this->unlinkReportFile($uploadRes['path']);
            return ['success' => false, 'errors' => ['db' => 'Failed to insert report into database.']];
        }

        $this->activityService->log(
            'client_result_report.created',
            $adminId,
            'client_result_report',
            (int) $id,
            "Added report '{$title}' ({$type}) to Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'report_type' => $type, 'report_title' => $title, 'is_public' => $isPublic]
        );

        return ['success' => true, 'id' => (int) $id];
    }

    /**
     * Update an existing report record with strict ownership verification.
     */
    public function updateReport(int $reportId, int $clientResultId, array $data, ?UploadedFile $file = null, ?int $adminId = null): array
    {
        $existing = $this->getReportByIdAndResultId($reportId, $clientResultId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['id' => 'Report not found or does not belong to this Client Result.']];
        }

        $title = trim((string) ($data['report_title'] ?? ''));
        $type = (string) ($data['report_type'] ?? $existing['report_type']);
        if (!array_key_exists($type, self::REPORT_TYPES)) {
            $type = $existing['report_type'];
        }

        $reportDate = trim((string) ($data['report_date'] ?? '')) ?: null;
        $description = trim((string) ($data['description'] ?? '')) ?: null;
        $displayOrder = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : (int) $existing['display_order'];
        $isPublic = isset($data['is_public']) ? (!empty($data['is_public']) ? 1 : 0) : (int) $existing['is_public'];

        $filePath = $existing['file_path'];
        $fileMime = $existing['file_mime'];
        $fileSize = $existing['file_size'];
        $newUploadedPath = null;

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $uploadRes = $this->uploadReportFile($file, $clientResultId);
            if (!$uploadRes['success']) {
                return ['success' => false, 'errors' => ['file' => $uploadRes['error']]];
            }
            $newUploadedPath = $uploadRes['path'];
            $filePath = $newUploadedPath;
            $fileMime = $uploadRes['mime'];
            $fileSize = $uploadRes['size'];
        }

        $saveData = [
            'client_result_id' => $clientResultId,
            'report_title'     => $title,
            'report_type'      => $type,
            'report_date'      => $reportDate,
            'file_path'        => $filePath,
            'file_mime'        => $fileMime,
            'file_size'        => $fileSize,
            'description'      => $description,
            'display_order'    => $displayOrder,
            'is_public'        => $isPublic,
        ];

        if (!$this->reportModel->validate($saveData)) {
            if ($newUploadedPath) {
                $this->unlinkReportFile($newUploadedPath);
            }
            return ['success' => false, 'errors' => $this->reportModel->errors()];
        }

        $updated = $this->reportModel->update($reportId, $saveData);
        if (!$updated) {
            if ($newUploadedPath) {
                $this->unlinkReportFile($newUploadedPath);
            }
            return ['success' => false, 'errors' => ['db' => 'Failed to update report in database.']];
        }

        if ($newUploadedPath && $existing['file_path'] !== $newUploadedPath) {
            $this->unlinkReportFile($existing['file_path']);
        }

        $action = $newUploadedPath ? 'client_result_report.replaced' : 'client_result_report.updated';
        $this->activityService->log(
            $action,
            $adminId,
            'client_result_report',
            $reportId,
            "Updated report ID {$reportId} ('{$title}') for Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'report_type' => $type, 'report_title' => $title, 'is_public' => $isPublic]
        );

        return ['success' => true, 'id' => $reportId];
    }

    /**
     * Delete report and safely clean up physical file.
     */
    public function deleteReport(int $reportId, int $clientResultId, ?int $adminId = null): array
    {
        $existing = $this->getReportByIdAndResultId($reportId, $clientResultId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['id' => 'Report not found or does not belong to this Client Result.']];
        }

        $filePath = $existing['file_path'];

        $this->reportModel->delete($reportId);

        if (!empty($filePath)) {
            $this->unlinkReportFile($filePath);
        }

        $this->activityService->log(
            'client_result_report.deleted',
            $adminId,
            'client_result_report',
            $reportId,
            "Deleted report ID {$reportId} ('{$existing['report_title']}') from Client Result ID {$clientResultId}",
            ['client_result_id' => $clientResultId, 'report_title' => $existing['report_title']]
        );

        return ['success' => true];
    }

    /**
     * Toggle report public visibility.
     */
    public function toggleReportPublic(int $reportId, int $clientResultId, ?int $adminId = null): array
    {
        $existing = $this->getReportByIdAndResultId($reportId, $clientResultId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['id' => 'Report not found or does not belong to this Client Result.']];
        }

        $newPublic = $existing['is_public'] ? 0 : 1;
        $this->reportModel->update($reportId, ['is_public' => $newPublic]);

        $this->activityService->log(
            'client_result_report.visibility_changed',
            $adminId,
            'client_result_report',
            $reportId,
            "Toggled visibility for report ID {$reportId} to " . ($newPublic ? 'public' : 'private'),
            ['client_result_id' => $clientResultId, 'is_public' => $newPublic]
        );

        return ['success' => true, 'is_public' => $newPublic];
    }

    /**
     * Resolve protected private file location for secure Admin serving.
     */
    public function serveReportFile(int $reportId, int $clientResultId): array
    {
        $report = $this->getReportByIdAndResultId($reportId, $clientResultId);
        if (!$report) {
            return ['success' => false, 'error' => 'Report not found or unauthorized.', 'code' => 404];
        }

        $storedPath = $report['file_path'];
        $fullPath = WRITEPATH . ltrim($storedPath, '/\\');

        $baseDir = realpath(WRITEPATH . 'uploads/client_results/reports');
        if ($baseDir === false) {
            return ['success' => false, 'error' => 'Storage directory error.', 'code' => 500];
        }

        $canonicalBase = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
        $realPath = realpath($fullPath);

        if ($realPath === false || strpos($realPath, $canonicalBase) !== 0) {
            return ['success' => false, 'error' => 'Invalid or unauthorized report file path.', 'code' => 403];
        }

        if (!file_exists($realPath) || !is_file($realPath)) {
            return ['success' => false, 'error' => 'Report file not found on storage.', 'code' => 404];
        }

        return [
            'success'  => true,
            'path'     => $realPath,
            'mime'     => $report['file_mime'],
            'filename' => $report['report_title'],
        ];
    }

    /**
     * Upload report securely to protected non-public location WRITEPATH uploads/client_results/reports/{clientResultId}/.
     */
    private function uploadReportFile(UploadedFile $file, int $clientResultId): array
    {
        if ($file->getSize() > self::MAX_REPORT_SIZE) {
            return ['success' => false, 'error' => 'Report file size must not exceed 10MB.'];
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, self::ALLOWED_REPORT_EXTS, true)) {
            return ['success' => false, 'error' => 'Report must be a PDF, JPG, PNG, or WebP file.'];
        }

        $mime = $file->getMimeType();
        if (!array_key_exists($mime, self::ALLOWED_REPORT_MIMES)) {
            return ['success' => false, 'error' => 'Invalid report MIME type: ' . $mime];
        }

        $targetDir = WRITEPATH . 'uploads/client_results/reports/' . $clientResultId . '/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $newName = $file->getRandomName();
        try {
            $file->move($targetDir, $newName);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Failed to save uploaded report file: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'path'    => 'uploads/client_results/reports/' . $clientResultId . '/' . $newName,
            'mime'    => $mime,
            'size'    => filesize($targetDir . $newName),
        ];
    }

    /**
     * Get active public Client Results formatted for the frontend landing page.
     * Efficient batch retrieval (NO N+1). Excludes private metrics, private media, and reports.
     */
    public function getPublicClientResultsForLanding(): array
    {
        $results = $this->resultModel
            ->where('is_active', 1)
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('is_featured', 'DESC')
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if (empty($results)) {
            return [];
        }

        $resultIds = array_column($results, 'id');

        // 1. Batch fetch focus areas
        $focusAreas = $this->focusAreaModel
            ->whereIn('client_result_id', $resultIds)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $focusByResult = [];
        foreach ($focusAreas as $fa) {
            $rId = (int) $fa['client_result_id'];
            $focusByResult[$rId][] = $fa['label'];
        }

        // 2. Batch fetch PUBLIC metrics ONLY
        $metrics = $this->metricModel
            ->whereIn('client_result_id', $resultIds)
            ->where('is_public', 1)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $metricsByResult = [];
        foreach ($metrics as $m) {
            $rId = (int) $m['client_result_id'];
            $metricsByResult[$rId][] = [
                'id'           => (int) $m['id'],
                'metric_name'  => $m['metric_name'],
                'before_value' => $m['before_value'],
                'after_value'  => $m['after_value'],
                'unit'         => $m['unit'],
                'context'      => $m['context'],
            ];
        }

        // 3. Batch fetch PUBLIC media ONLY
        $media = $this->mediaModel
            ->whereIn('client_result_id', $resultIds)
            ->where('is_public', 1)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $mediaByResult = [];
        foreach ($media as $med) {
            $rId = (int) $med['client_result_id'];
            $mediaByResult[$rId][] = $med;
        }

        // Hydrate results safely
        foreach ($results as &$res) {
            $rId = (int) $res['id'];
            $res['focus_areas'] = $focusByResult[$rId] ?? [];
            $res['metrics']     = $metricsByResult[$rId] ?? [];
            $res['media']       = $mediaByResult[$rId] ?? [];
            // Remove full_story to keep landing view payload minimal
            unset($res['full_story']);
        }
        unset($res);

        return $results;
    }

    /**
     * Safely unlink report file if exists under private WRITEPATH directory.
     */
    private function unlinkReportFile(string $relativePath): void
    {
        if (empty($relativePath)) return;
        $fullPath = WRITEPATH . ltrim($relativePath, '/\\');
        $baseDir = realpath(WRITEPATH . 'uploads/client_results/reports');
        if ($baseDir === false) return;

        $canonicalBase = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
        $realFile = realpath($fullPath);

        if ($realFile !== false && strpos($realFile, $canonicalBase) === 0) {
            if (file_exists($realFile) && is_file($realFile)) {
                @unlink($realFile);
            }
        }
    }

    /**
     * Safely unlink file if exists under public web root FCPATH.
     */
    private function unlinkFile(string $relativePath): void
    {
        if (empty($relativePath)) return;
        $fullPath = FCPATH . ltrim($relativePath, '/\\');
        $baseDir = realpath(FCPATH . 'uploads/client_results');
        if ($baseDir === false) {
            if (file_exists($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
            return;
        }

        $canonicalBase = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;
        $realFile = realpath($fullPath);

        if ($realFile !== false && strpos($realFile, $canonicalBase) === 0) {
            if (file_exists($realFile) && is_file($realFile)) {
                @unlink($realFile);
            }
        }
    }
}



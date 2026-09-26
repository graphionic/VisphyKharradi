<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PackageModel;
use App\Services\AdminAuthService;
use App\Services\ClientResultService;

/**
 * ClientResultController — Phase 07B: Core Client Results Admin Management
 */
class ClientResultController extends BaseController
{
    private ClientResultService $resultService;
    private AdminAuthService $authService;
    private PackageModel $packageModel;

    public function __construct(
        ?ClientResultService $resultService = null,
        ?AdminAuthService $authService = null,
        ?PackageModel $packageModel = null
    ) {
        $this->resultService = $resultService ?? new ClientResultService();
        $this->authService = $authService ?? new AdminAuthService();
        $this->packageModel = $packageModel ?? new PackageModel();
    }

    /**
     * Client Results Listing.
     */
    public function index()
    {
        $q = (string) ($this->request->getGet('q') ?? '');
        $status = (string) ($this->request->getGet('status') ?? 'all');
        $featured = (string) ($this->request->getGet('featured') ?? 'all');
        $sort = (string) ($this->request->getGet('sort') ?? 'display_order');
        $page = (int) ($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;

        $result = $this->resultService->getAdminList([
            'q'        => $q,
            'status'   => $status,
            'featured' => $featured,
            'sort'     => $sort,
            'page'     => $page,
            'perPage'  => ClientResultService::PER_PAGE,
        ]);

        $queryParams = [];
        if ($result['filters']['q'] !== '') $queryParams['q'] = $result['filters']['q'];
        if ($result['filters']['status'] !== 'all') $queryParams['status'] = $result['filters']['status'];
        if ($result['filters']['featured'] !== 'all') $queryParams['featured'] = $result['filters']['featured'];

        return view('admin/client_results/index', [
            'title'       => 'Client Results — Ftpreneur Admin',
            'results'     => $result['results'],
            'pager'       => $result['pager'],
            'total'       => $result['total'],
            'perPage'     => $result['perPage'],
            'currentPage' => $result['currentPage'],
            'filters'     => $result['filters'],
            'queryParams' => $queryParams,
            'rangeStart'  => $result['total'] === 0 ? 0 : ($result['currentPage'] - 1) * $result['perPage'] + 1,
            'rangeEnd'    => min($result['total'], $result['currentPage'] * $result['perPage']),
        ]);
    }

    /**
     * Render Client Result Create View.
     */
    public function create()
    {
        $errors = session()->getFlashdata('errors') ?? [];
        if (!is_array($errors)) $errors = [];

        $packages = $this->packageModel
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('display_order', 'ASC')
            ->findAll();

        return view('admin/client_results/create', [
            'title'    => 'Create Client Result — Ftpreneur Admin',
            'packages' => $packages,
            'errors'   => $errors,
        ]);
    }

    /**
     * Store New Client Result.
     */
    public function store()
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $input = $this->request->getPost();
        $coverFile = $this->request->getFile('cover_image');

        if ($coverFile && $coverFile->getError() === UPLOAD_ERR_NO_FILE) {
            $coverFile = null;
        }

        $res = $this->resultService->createResult($input, $coverFile, $adminId);

        if (!$res['success']) {
            return redirect()->back()->withInput()->with('errors', $res['errors']);
        }

        return redirect()->to(site_url('admin/client-results'))->with('success', 'Client Result created successfully.');
    }

    /**
     * Render Client Result Edit View.
     */
    public function edit(int $id)
    {
        $resultRecord = $this->resultService->getById($id);
        if (!$resultRecord) {
            return redirect()->to(site_url('admin/client-results'))->with('errors', ['id' => 'Client Result not found.']);
        }

        $errors = session()->getFlashdata('errors') ?? [];
        if (!is_array($errors)) $errors = [];

        $packages = $this->packageModel
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('display_order', 'ASC')
            ->findAll();

        $metrics = $this->resultService->getMetricsByResultId($id);
        $mediaData = $this->resultService->getMediaByResultId($id);
        $focusAreas = $this->resultService->getFocusAreasByResultId($id);
        $reports = $this->resultService->getReportsByResultId($id);

        return view('admin/client_results/edit', [
            'title'      => 'Edit Client Result — Ftpreneur Admin',
            'result'     => $resultRecord,
            'packages'   => $packages,
            'metrics'    => $metrics,
            'media'      => $mediaData,
            'focusAreas' => $focusAreas,
            'reports'    => $reports,
            'errors'     => $errors,
        ]);
    }

    /**
     * Store or Replace Primary BEFORE Image.
     */
    public function storeBeforeMedia(int $clientResultId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $file = $this->request->getFile('before_image');
        if (!$file || !$file->isValid()) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->with('errors', ['before_image' => 'Please select a valid image file to upload.']);
        }

        $input = $this->request->getPost();
        $res = $this->resultService->savePrimaryMedia($clientResultId, 'before', $file, $input, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->withInput()
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
            ->with('success', 'Primary Before photo saved successfully.');
    }

    /**
     * Store or Replace Primary AFTER Image.
     */
    public function storeAfterMedia(int $clientResultId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $file = $this->request->getFile('after_image');
        if (!$file || !$file->isValid()) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->with('errors', ['after_image' => 'Please select a valid image file to upload.']);
        }

        $input = $this->request->getPost();
        $res = $this->resultService->savePrimaryMedia($clientResultId, 'after', $file, $input, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->withInput()
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
            ->with('success', 'Primary After photo saved successfully.');
    }

    /**
     * Store Progress or Gallery Media (Single or Multi-upload).
     */
    public function storeGalleryMedia(int $clientResultId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $files = $this->request->getFileMultiple('gallery_files');
        if (empty($files)) {
            $singleFile = $this->request->getFile('gallery_file');
            if ($singleFile && $singleFile->isValid()) {
                $files = [$singleFile];
            }
        }

        if (empty($files)) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->with('errors', ['gallery_files' => 'Please select at least one photo to upload.']);
        }

        $input = $this->request->getPost();
        $res = $this->resultService->uploadGalleryMedia($clientResultId, $files, $input, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->withInput()
                ->with('errors', $res['errors']);
        }

        $count = count($res['uploaded_ids'] ?? []);
        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
            ->with('success', "{$count} photo(s) added to progress gallery successfully.");
    }

    /**
     * Update Existing Media Item Metadata.
     */
    public function updateMedia(int $clientResultId, int $mediaId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $input = $this->request->getPost();
        $res = $this->resultService->updateMedia($mediaId, $clientResultId, $input, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->withInput()
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
            ->with('success', 'Media item updated successfully.');
    }

    /**
     * Delete Media Item (POST request only).
     */
    public function deleteMedia(int $clientResultId, int $mediaId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->deleteMedia($mediaId, $clientResultId, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
            ->with('success', 'Media item deleted successfully.');
    }

    /**
     * Toggle Media Public Visibility.
     */
    public function toggleMediaPublic(int $clientResultId, int $mediaId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->toggleMediaPublic($mediaId, $clientResultId, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
                ->with('errors', $res['errors']);
        }

        $label = $res['is_public'] ? 'Public' : 'Private';
        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#visual-proof"))
            ->with('success', "Media visibility changed to {$label}.");
    }

    /**
     * Store New Outcome Metric for Client Result.
     */
    public function storeMetric(int $clientResultId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $input = $this->request->getPost();
        $res = $this->resultService->createMetric($clientResultId, $input, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#outcomes"))
                ->withInput()
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#outcomes"))
            ->with('success', 'Outcome metric added successfully.');
    }

    /**
     * Update Existing Outcome Metric.
     */
    public function updateMetric(int $clientResultId, int $metricId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $input = $this->request->getPost();
        $res = $this->resultService->updateMetric($metricId, $clientResultId, $input, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#outcomes"))
                ->withInput()
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#outcomes"))
            ->with('success', 'Outcome metric updated successfully.');
    }

    /**
     * Delete Outcome Metric (POST request only).
     */
    public function deleteMetric(int $clientResultId, int $metricId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->deleteMetric($metricId, $clientResultId, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#outcomes"))
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#outcomes"))
            ->with('success', 'Outcome metric deleted successfully.');
    }

    /**
     * Toggle Metric Public Visibility.
     */
    public function toggleMetricPublic(int $clientResultId, int $metricId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->toggleMetricPublic($metricId, $clientResultId, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#outcomes"))
                ->with('errors', $res['errors']);
        }

        $label = $res['is_public'] ? 'Public' : 'Private';
        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#outcomes"))
            ->with('success', "Outcome metric visibility changed to {$label}.");
    }

    /**
     * Update Existing Client Result.
     */
    public function update(int $id)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $input = $this->request->getPost();
        $coverFile = $this->request->getFile('cover_image');

        if ($coverFile && $coverFile->getError() === UPLOAD_ERR_NO_FILE) {
            $coverFile = null;
        }

        $res = $this->resultService->updateResult($id, $input, $coverFile, $adminId);

        if (!$res['success']) {
            return redirect()->back()->withInput()->with('errors', $res['errors']);
        }

        return redirect()->to(site_url('admin/client-results'))->with('success', 'Client Result updated successfully.');
    }

    /**
     * Soft Delete Client Result (POST request only).
     */
    public function delete(int $id)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->deleteResult($id, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url('admin/client-results'))->with('errors', $res['errors']);
        }

        return redirect()->to(site_url('admin/client-results'))->with('success', 'Client Result deleted successfully.');
    }

    /**
     * Toggle Active Publication Status.
     */
    public function toggleActive(int $id)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->toggleActive($id, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url('admin/client-results'))->with('errors', $res['errors']);
        }

        $statusLabel = $res['is_active'] ? 'activated' : 'deactivated';
        return redirect()->to(site_url('admin/client-results'))->with('success', "Client Result status successfully {$statusLabel}.");
    }

    /**
     * Toggle Featured Status.
     */
    public function toggleFeatured(int $id)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->toggleFeatured($id, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url('admin/client-results'))->with('errors', $res['errors']);
        }

        $featuredLabel = $res['is_featured'] ? 'featured' : 'standard';
        return redirect()->to(site_url('admin/client-results'))->with('success', "Client Result marked as {$featuredLabel}.");
    }

    /**
     * Store New Supporting Report for Client Result.
     */
    public function storeReport(int $clientResultId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $file = $this->request->getFile('report_file');
        if (!$file || !$file->isValid()) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
                ->with('errors', ['report_file' => 'Please select a valid report file (PDF, JPG, PNG, WebP up to 10MB).']);
        }

        $input = $this->request->getPost();
        $res = $this->resultService->createReport($clientResultId, $input, $file, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
                ->withInput()
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
            ->with('success', 'Supporting report added successfully.');
    }

    /**
     * Update Existing Report Metadata or Replace File.
     */
    public function updateReport(int $clientResultId, int $reportId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $file = $this->request->getFile('report_file');
        if ($file && $file->getError() === UPLOAD_ERR_NO_FILE) {
            $file = null;
        }

        $input = $this->request->getPost();
        $res = $this->resultService->updateReport($reportId, $clientResultId, $input, $file, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
                ->withInput()
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
            ->with('success', 'Report details updated successfully.');
    }

    /**
     * Delete Report and remove physical file (POST request only).
     */
    public function deleteReport(int $clientResultId, int $reportId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->deleteReport($reportId, $clientResultId, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
                ->with('errors', $res['errors']);
        }

        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
            ->with('success', 'Report deleted successfully.');
    }

    /**
     * Toggle Report Public Visibility.
     */
    public function toggleReportPublic(int $clientResultId, int $reportId)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->resultService->toggleReportPublic($reportId, $clientResultId, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
                ->with('errors', $res['errors']);
        }

        $label = $res['is_public'] ? 'Public' : 'Private';
        return redirect()->to(site_url("admin/client-results/{$clientResultId}/edit#reports-evidence"))
            ->with('success', "Report visibility changed to {$label}.");
    }

    /**
     * Serve Protected Private Report File to Authenticated Admin.
     */
    public function serveReportFile(int $clientResultId, int $reportId)
    {
        $res = $this->resultService->serveReportFile($reportId, $clientResultId);
        if (!$res['success']) {
            return $this->response->setStatusCode($res['code'] ?? 404)->setBody($res['error']);
        }

        $filePath = $res['path'];
        $mimeType = $res['mime'] ?? 'application/octet-stream';
        $rawFilename = $res['filename'] ?? 'report';

        // Sanitize filename for Content-Disposition header (prevent header injection/CRLF/quotes)
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $rawFilename);
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext !== '' && strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION)) !== $ext) {
            $safeFilename .= '.' . $ext;
        }

        $disposition = (strpos($mimeType, 'pdf') !== false || strtolower($ext) === 'pdf' || strpos($mimeType, 'image/') === 0) ? 'inline' : 'attachment';

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', $disposition . '; filename="' . $safeFilename . '"')
            ->setHeader('Content-Length', (string) filesize($filePath))
            ->setBody(file_get_contents($filePath));
    }

    /**
     * Render Admin Complete Client Proof Preview View.
     */
    public function preview(int $id)
    {
        $mode = strtolower(trim((string) ($this->request->getGet('mode') ?? 'public')));
        if ($mode !== 'all') {
            $mode = 'public';
        }

        $previewData = $this->resultService->getCompleteResultForAdminPreview($id, $mode);
        if (!$previewData) {
            return redirect()->to(site_url('admin/client-results'))->with('errors', ['id' => 'Client Result not found.']);
        }

        return view('admin/client_results/preview', [
            'title'       => 'Preview Client Proof — ' . esc($previewData['result']['client_display_name']) . ' — Ftpreneur Admin',
            'previewData' => $previewData,
            'result'      => $previewData['result'],
            'mode'        => $mode,
        ]);
    }
}

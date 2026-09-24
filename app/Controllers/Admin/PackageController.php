<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PackageFeatureModel;
use App\Models\PackageGalleryImageModel;
use App\Models\PackageModel;
use App\Services\AdminAuthService;
use App\Services\PackageService;

/**
 * PackageController — Phase 5D: media, rich text, lifecycle
 */
class PackageController extends BaseController
{
    private PackageService $packageService;
    private AdminAuthService $authService;
    private PackageModel $packageModel;
    private PackageFeatureModel $featureModel;
    private PackageGalleryImageModel $galleryModel;

    public function __construct(?PackageService $packageService = null, ?AdminAuthService $authService = null, ?PackageModel $packageModel = null, ?PackageFeatureModel $featureModel = null, ?PackageGalleryImageModel $galleryModel = null)
    {
        $this->packageService = $packageService ?? new PackageService();
        $this->authService = $authService ?? new AdminAuthService();
        $this->packageModel = $packageModel ?? new PackageModel();
        $this->featureModel = $featureModel ?? new PackageFeatureModel();
        $this->galleryModel = $galleryModel ?? new PackageGalleryImageModel();
    }

    public function index()
    {
        $q = (string) ($this->request->getGet('q') ?? '');
        $status = (string) ($this->request->getGet('status') ?? 'all');
        $sort = (string) ($this->request->getGet('sort') ?? 'display_order');
        $page = (int) ($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;

        $result = $this->packageService->getAdminPackageList([
            'q'       => $q,
            'status'  => $status,
            'sort'    => $sort,
            'page'    => $page,
            'perPage' => PackageService::PER_PAGE,
        ]);

        $queryParams = [];
        if ($result['filters']['q'] !== '') $queryParams['q'] = $result['filters']['q'];
        if ($result['filters']['status'] !== 'all') $queryParams['status'] = $result['filters']['status'];
        if ($result['filters']['sort'] !== 'display_order') $queryParams['sort'] = $result['filters']['sort'];

        return view('admin/packages/index', [
            'title'         => 'Packages — Ftpreneur',
            'packages'      => $result['packages'],
            'pager'         => $result['pager'],
            'total'         => $result['total'],
            'perPage'       => $result['perPage'],
            'currentPage'   => $result['currentPage'],
            'featureCounts' => $result['featureCounts'],
            'filters'       => $result['filters'],
            'queryParams'   => $queryParams,
            'sortOptions'   => $this->packageService->getSortOptions(),
            'rangeStart'    => $result['total'] === 0 ? 0 : ($result['currentPage'] - 1) * $result['perPage'] + 1,
            'rangeEnd'      => min($result['total'], $result['currentPage'] * $result['perPage']),
        ]);
    }

    public function create()
    {
        $errors = session()->getFlashdata('errors') ?? [];
        if (isset($errors) && !is_array($errors)) $errors = [];
        return view('admin/packages/create', [
            'title'  => 'Create Package — Ftpreneur',
            'errors' => $errors,
        ]);
    }

    public function store()
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int)$admin['id'] : (int) (session()->get('admin_id') ?? 0);
        $input = $this->request->getPost();
        // 5E.2B: ignore client-controlled path keys on create as well
        unset($input['remove_path'], $input['image_path'], $input['featured_image'], $input['featured_image_path'], $input['gallery_path']);

        // Handle featured image and gallery files
        $featuredFile = $this->request->getFile('featured_image');
        // Normalize: if no file, set to null for service
        if ($featuredFile && $featuredFile->getError() === UPLOAD_ERR_NO_FILE) $featuredFile = null;

        $galleryFiles = [];
        $files = $this->request->getFiles();
        if (isset($files['gallery_images'])) {
            $gf = $files['gallery_images'];
            // $gf may be array of UploadedFile
            if (is_array($gf)) {
                foreach ($gf as $f) {
                    if ($f instanceof \CodeIgniter\HTTP\Files\UploadedFile) $galleryFiles[] = $f;
                }
            }
        }
        // Also handle gallery_images[] as array via getFileMultiple? fallback to getFile
        // For tests, also check $_FILES superglobal directly
        if (empty($galleryFiles) && isset($_FILES['gallery_images'])) {
            // $_FILES gallery_images may be array structure
            $g = $_FILES['gallery_images'];
            if (is_array($g['name'])) {
                $count = count($g['name']);
                for ($i=0;$i<$count;$i++) {
                    $galleryFiles[] = [
                        'name' => $g['name'][$i],
                        'type' => $g['type'][$i],
                        'tmp_name' => $g['tmp_name'][$i],
                        'error' => $g['error'][$i],
                        'size' => $g['size'][$i],
                    ];
                }
            } elseif (isset($g['tmp_name']) && $g['tmp_name'] !== '') {
                $galleryFiles[] = $g;
            }
        }
        // Gallery alt texts if provided
        $galleryAlt = $this->request->getPost('gallery_alt') ?? [];

        // Use media-aware create if files present, else fallback to simple create for backward compat
        if ($featuredFile !== null || !empty($galleryFiles)) {
            $result = $this->packageService->createPackageWithMedia($input, $adminId, $featuredFile, $galleryFiles, $galleryAlt);
        } else {
            // Check if $_FILES has featured_image as array (test simulation)
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = $this->packageService->createPackageWithMedia($input, $adminId, $_FILES['featured_image'], $galleryFiles, $galleryAlt);
            } else {
                $result = $this->packageService->createPackage($input, $adminId);
                // Also try media-aware with nulls to handle gallery via $_FILES already captured? For now simple
                // If gallery files were via $_FILES, we already captured above, but if we went to else, we missed? Check again
                if (!empty($galleryFiles)) {
                    // This shouldn't happen, but handle
                    $result = $this->packageService->createPackageWithMedia($input, $adminId, null, $galleryFiles, $galleryAlt);
                }
            }
        }

        if ($result['success'] === true) {
            return redirect()->to(site_url('admin/packages'))
                ->with('message', 'Package created successfully.');
        }
        return redirect()->to(site_url('admin/packages/create'))
            ->withInput()
            ->with('errors', $result['errors'])
            ->with('error', 'Please correct the highlighted fields.');
    }

    public function edit($id = null)
    {
        if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        $packageId = (int)$id;
        $data = $this->packageService->getPackageWithGallery($packageId);
        // Fallback to old method if not found (handles soft-delete)
        if ($data === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        $package = $data['package'];
        $features = $data['features'];
        $gallery = $data['gallery'];

        $errors = session()->getFlashdata('errors') ?? [];
        if (!is_array($errors)) $errors = [];

        return view('admin/packages/edit', [
            'title'    => 'Edit Package — Ftpreneur',
            'package'  => $package,
            'features' => $features,
            'gallery'  => $gallery,
            'errors'   => $errors,
        ]);
    }

    public function update($id = null)
    {
        if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        $packageId = (int)$id;
        $existing = $this->packageModel->find($packageId);
        if ($existing === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int)$admin['id'] : (int) (session()->get('admin_id') ?? 0);
        $input = $this->request->getPost();
        // 5E.2B: CLIENT MUST NOT CONTROL FILE PATH — ignore any filesystem path submission (never trust remove_path/image_path/featured_image as path)
        unset($input['remove_path'], $input['image_path'], $input['featured_image'], $input['featured_image_path'], $input['gallery_path'], $input['gallery_image_path'], $input['remove_featured_path']);
        if (isset($_POST['remove_path'])) unset($_POST['remove_path']);
        if (isset($_POST['image_path'])) unset($_POST['image_path']);
        if (isset($_POST['featured_image']) && is_string($_POST['featured_image'])) {
            // POST featured_image as string path is not allowed; only file upload via $_FILES is authoritative. Keep $input clean.
            unset($input['featured_image']);
        }

        // Featured image handling
        $featuredFile = $this->request->getFile('featured_image');
        if ($featuredFile && $featuredFile->getError() === UPLOAD_ERR_NO_FILE) $featuredFile = null;
        // Also check $_FILES for test
        if ($featuredFile === null && isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $featuredFile = $_FILES['featured_image'];
        }
        $removeFeatured = (bool)($this->request->getPost('remove_featured_image') ?? false);
        if (isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] === '1') $removeFeatured = true;

        // Gallery handling
        $galleryFiles = [];
        $files = $this->request->getFiles();
        if (isset($files['gallery_images'])) {
            $gf = $files['gallery_images'];
            if (is_array($gf)) {
                foreach ($gf as $f) {
                    if ($f instanceof \CodeIgniter\HTTP\Files\UploadedFile) $galleryFiles[] = $f;
                }
            }
        }
        if (empty($galleryFiles) && isset($_FILES['gallery_images'])) {
            $g = $_FILES['gallery_images'];
            if (is_array($g['name'] ?? null)) {
                $count = count($g['name']);
                for ($i=0;$i<$count;$i++) {
                    $galleryFiles[] = [
                        'name' => $g['name'][$i],
                        'type' => $g['type'][$i],
                        'tmp_name' => $g['tmp_name'][$i],
                        'error' => $g['error'][$i],
                        'size' => $g['size'][$i],
                    ];
                }
            } elseif (isset($g['tmp_name']) && is_string($g['tmp_name']) && $g['tmp_name'] !== '') {
                $galleryFiles[] = $g;
            }
        }
        $existingGalleryIds = $this->request->getPost('existing_gallery_ids') ?? $this->request->getPost('gallery_existing_ids') ?? [];
        if (!is_array($existingGalleryIds)) $existingGalleryIds = [$existingGalleryIds];
        $existingGalleryIds = array_filter($existingGalleryIds, fn($v)=> $v !== '' && $v !== null);
        // Also handle gallery_order if provided
        $galleryOrder = $this->request->getPost('gallery_order') ?? [];
        if (is_string($galleryOrder)) {
            $decoded = json_decode($galleryOrder, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $galleryOrder = $decoded;
            else $galleryOrder = [$galleryOrder];
        }
        if (!is_array($galleryOrder)) $galleryOrder = [];
        $existingGalleryAlt = $this->request->getPost('existing_gallery_alt') ?? $this->request->getPost('gallery_alt_existing') ?? [];
        if (!is_array($existingGalleryAlt)) $existingGalleryAlt = [$existingGalleryAlt];

        // Decide which service method to use: if any media fields present, use media-aware
        $hasMedia = $featuredFile !== null || $removeFeatured || !empty($galleryFiles) || !empty($existingGalleryIds) || !empty($galleryOrder);
        // For edit, even if no media change, we still need to preserve existing gallery via ids, so we should always use media-aware to handle gallery preservation?
        // If hasMedia is false but package has existing gallery, we still need to preserve it? However our updatePackage without media would delete and reinsert features but not gallery, leaving gallery untouched.
        // But our updatePackageWithMedia deletes and reinserts gallery based on existingGalleryIds. If hasMedia false, we would delete gallery if we use media-aware with empty existingGalleryIds.
        // So we need to detect: if hasMedia is false, use simple updatePackage (which leaves gallery unchanged). If hasMedia true, use media-aware.
        // For edit form, we always send existing_gallery_ids (hidden), so hasMedia will be true if gallery exists, ensuring preservation.
        // If gallery exists but form didn't send ids (old tests), hasMedia false -> gallery would remain, but we need to preserve anyway. That's okay.

        if ($hasMedia) {
            $result = $this->packageService->updatePackageWithMedia($packageId, $input, $adminId, $featuredFile, $removeFeatured, $galleryFiles, $existingGalleryIds, $existingGalleryAlt, $galleryOrder);
        } else {
            $result = $this->packageService->updatePackage($packageId, $input, $adminId);
        }

        if (isset($result['notFound']) && $result['notFound'] === true) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        if ($result['success'] === true) {
            return redirect()->to(site_url('admin/packages'))
                ->with('message', 'Package updated successfully.');
        }
        return redirect()->to(site_url('admin/packages/' . $packageId . '/edit'))
            ->withInput()
            ->with('errors', $result['errors'])
            ->with('error', 'Please correct the highlighted fields.');
    }

    // Toggle active
    public function toggleActive($id = null)
    {
        if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        $packageId = (int)$id;
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int)$admin['id'] : (int) (session()->get('admin_id') ?? 0);
        $result = $this->packageService->toggleActive($packageId, $adminId);
        if (isset($result['notFound'])) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        if ($result['success']) {
            $msg = $result['is_active'] ? 'Package activated.' : 'Package deactivated.';
            return redirect()->to(site_url('admin/packages'))->with('message', $msg);
        }
        return redirect()->to(site_url('admin/packages'))->with('error', 'Failed to update package.');
    }

    public function toggleFeatured($id = null)
    {
        if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        $packageId = (int)$id;
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int)$admin['id'] : (int) (session()->get('admin_id') ?? 0);
        $result = $this->packageService->toggleFeatured($packageId, $adminId);
        if (isset($result['notFound'])) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        if ($result['success']) {
            $msg = $result['is_featured'] ? 'Package marked as featured.' : 'Package removed from featured.';
            return redirect()->to(site_url('admin/packages'))->with('message', $msg);
        }
        return redirect()->to(site_url('admin/packages'))->with('error', 'Failed to update package.');
    }

    public function reorder()
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int)$admin['id'] : (int) (session()->get('admin_id') ?? 0);
        $ordered = $this->request->getPost('ordered_ids') ?? $this->request->getPost('package_order') ?? $this->request->getPost('order') ?? [];
        if (is_string($ordered)) {
            $decoded = json_decode($ordered, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $ordered = $decoded;
            else $ordered = explode(',', $ordered);
        }
        if (!is_array($ordered)) $ordered = [$ordered];
        $ordered = array_values(array_filter($ordered, fn($v)=> $v !== '' && $v !== null));
        $result = $this->packageService->reorderPackages($ordered, $adminId);
        if ($result['success']) {
            return redirect()->to(site_url('admin/packages'))->with('message', 'Packages reordered.');
        }
        return redirect()->to(site_url('admin/packages'))->with('error', $result['errors']['order'] ?? 'Failed to reorder.')->withInput();
    }

    public function delete($id = null)
    {
        if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        $packageId = (int)$id;
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int)$admin['id'] : (int) (session()->get('admin_id') ?? 0);
        $result = $this->packageService->deletePackage($packageId, $adminId);
        if (isset($result['notFound'])) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        if ($result['success']) {
            return redirect()->to(site_url('admin/packages'))->with('message', 'Package deleted.');
        }
        return redirect()->to(site_url('admin/packages'))->with('error', 'Failed to delete package.');
    }

    public function deleted()
    {
        $packages = $this->packageService->getDeletedPackages();
        // Need feature counts for deleted? Not needed, but we can compute
        $featureCounts = [];
        if (!empty($packages)) {
            $featureCounts = $this->packageService->getFeatureCounts(array_column($packages, 'id'));
        }
        return view('admin/packages/deleted', [
            'title' => 'Deleted Packages — Ftpreneur',
            'packages' => $packages,
            'featureCounts' => $featureCounts,
        ]);
    }

    public function restore($id = null)
    {
        if ($id === null || !ctype_digit((string)$id) || (int)$id <= 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        }
        $packageId = (int)$id;
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int)$admin['id'] : (int) (session()->get('admin_id') ?? 0);
        $result = $this->packageService->restorePackage($packageId, $adminId);
        if (isset($result['notFound'])) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Package not found');
        if ($result['success']) {
            return redirect()->to(site_url('admin/packages/deleted'))->with('message', 'Package restored.');
        }
        return redirect()->to(site_url('admin/packages/deleted'))->with('error', $result['errors']['package'] ?? 'Failed to restore.');
    }
}

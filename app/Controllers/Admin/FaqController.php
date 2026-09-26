<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdminAuthService;
use App\Services\FaqService;

/**
 * FaqController — Phase 06: Dynamic FAQ Admin Management
 */
class FaqController extends BaseController
{
    private FaqService $faqService;
    private AdminAuthService $authService;

    public function __construct(?FaqService $faqService = null, ?AdminAuthService $authService = null)
    {
        $this->faqService = $faqService ?? new FaqService();
        $this->authService = $authService ?? new AdminAuthService();
    }

    /**
     * FAQ Listing.
     */
    public function index()
    {
        $q = (string) ($this->request->getGet('q') ?? '');
        $status = (string) ($this->request->getGet('status') ?? 'all');
        $page = (int) ($this->request->getGet('page') ?? 1);
        if ($page < 1) $page = 1;

        $result = $this->faqService->getAdminFaqList([
            'q'       => $q,
            'status'  => $status,
            'page'    => $page,
            'perPage' => FaqService::PER_PAGE,
        ]);

        $queryParams = [];
        if ($result['filters']['q'] !== '') $queryParams['q'] = $result['filters']['q'];
        if ($result['filters']['status'] !== 'all') $queryParams['status'] = $result['filters']['status'];

        return view('admin/faqs/index', [
            'title'       => 'FAQs — Ftpreneur Admin',
            'faqs'        => $result['faqs'],
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
     * Render FAQ Create View.
     */
    public function create()
    {
        $errors = session()->getFlashdata('errors') ?? [];
        if (!is_array($errors)) $errors = [];

        return view('admin/faqs/create', [
            'title'  => 'Create FAQ — Ftpreneur Admin',
            'errors' => $errors,
        ]);
    }

    /**
     * Store New FAQ.
     */
    public function store()
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $input = $this->request->getPost();
        $res = $this->faqService->createFaq($input, $adminId);

        if (!$res['success']) {
            return redirect()->back()->withInput()->with('errors', $res['errors']);
        }

        return redirect()->to(site_url('admin/faqs'))->with('success', 'FAQ created successfully.');
    }

    /**
     * Render FAQ Edit View.
     */
    public function edit(int $id)
    {
        $faq = $this->faqService->getFaqById($id);
        if (!$faq) {
            return redirect()->to(site_url('admin/faqs'))->with('errors', ['id' => 'FAQ not found.']);
        }

        $errors = session()->getFlashdata('errors') ?? [];
        if (!is_array($errors)) $errors = [];

        return view('admin/faqs/edit', [
            'title'  => 'Edit FAQ — Ftpreneur Admin',
            'faq'    => $faq,
            'errors' => $errors,
        ]);
    }

    /**
     * Update Existing FAQ.
     */
    public function update(int $id)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $input = $this->request->getPost();
        $res = $this->faqService->updateFaq($id, $input, $adminId);

        if (!$res['success']) {
            return redirect()->back()->withInput()->with('errors', $res['errors']);
        }

        return redirect()->to(site_url('admin/faqs'))->with('success', 'FAQ updated successfully.');
    }

    /**
     * Soft Delete FAQ (POST request only).
     */
    public function delete(int $id)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->faqService->deleteFaq($id, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url('admin/faqs'))->with('errors', $res['errors']);
        }

        return redirect()->to(site_url('admin/faqs'))->with('success', 'FAQ deleted successfully.');
    }

    /**
     * Toggle FAQ Active Status.
     */
    public function toggleActive(int $id)
    {
        $admin = $this->authService->getCurrentAdmin();
        $adminId = $admin ? (int) $admin['id'] : (int) (session()->get('admin_id') ?? 0);

        $res = $this->faqService->toggleActive($id, $adminId);

        if (!$res['success']) {
            return redirect()->to(site_url('admin/faqs'))->with('errors', $res['errors']);
        }

        $statusLabel = $res['is_active'] ? 'activated' : 'deactivated';
        return redirect()->to(site_url('admin/faqs'))->with('success', "FAQ status successfully {$statusLabel}.");
    }
}

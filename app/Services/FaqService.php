<?php

namespace App\Services;

use App\Models\FaqModel;
use App\Services\AdminActivityService;
use App\Services\HtmlSanitizerService;

/**
 * FaqService — Phase 06: Dynamic FAQ System Service Layer
 *
 * Encapsulates business logic, validation, HTML sanitization, and audit logging for FAQs.
 */
class FaqService
{
    private FaqModel $faqModel;
    private AdminActivityService $activityService;
    private HtmlSanitizerService $sanitizer;

    public const PER_PAGE = 15;

    public function __construct(
        ?FaqModel $faqModel = null,
        ?AdminActivityService $activityService = null,
        ?HtmlSanitizerService $sanitizer = null
    ) {
        $this->faqModel = $faqModel ?? new FaqModel();
        $this->activityService = $activityService ?? new AdminActivityService();
        $this->sanitizer = $sanitizer ?? new HtmlSanitizerService();
    }

    /**
     * Get active FAQs for public landing page rendering.
     * Only returns is_active = 1 and deleted_at IS NULL.
     */
    public function getActiveFaqs(): array
    {
        return $this->faqModel
            ->where('is_active', 1)
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Get paginated FAQ list for admin dashboard.
     */
    public function getAdminFaqList(array $params = []): array
    {
        $q = trim((string) ($params['q'] ?? ''));
        $status = (string) ($params['status'] ?? 'all');
        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['perPage'] ?? self::PER_PAGE);
        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = self::PER_PAGE;

        $builder = $this->faqModel->where('deleted_at IS NULL', null, false);

        if ($q !== '') {
            $builder->groupStart()
                ->like('question', $q)
                ->orLike('answer', $q)
                ->orLike('category', $q)
                ->groupEnd();
        }

        if ($status === 'active') {
            $builder->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $builder->where('is_active', 0);
        }

        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        $faqs = $builder
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->paginate($perPage, 'default', $page);

        $pager = $this->faqModel->pager;

        return [
            'faqs'        => $faqs,
            'total'       => $total,
            'currentPage' => $page,
            'perPage'     => $perPage,
            'pager'       => $pager,
            'filters'     => [
                'q'      => $q,
                'status' => $status,
            ],
        ];
    }

    /**
     * Get single FAQ by ID.
     */
    public function getFaqById(int $id): ?array
    {
        if ($id <= 0) return null;
        $faq = $this->faqModel
            ->where('id', $id)
            ->where('deleted_at IS NULL', null, false)
            ->first();

        return $faq ?: null;
    }

    /**
     * Create a new FAQ.
     */
    public function createFaq(array $data, ?int $adminId = null): array
    {
        $question = trim((string) ($data['question'] ?? ''));
        $answer = trim((string) ($data['answer'] ?? ''));
        $category = trim((string) ($data['category'] ?? '')) ?: null;
        $displayOrder = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : 100;
        $isActive = !empty($data['is_active']) ? 1 : 0;

        // Sanitize answer to prevent XSS while allowing basic formatting if needed
        $cleanAnswer = $this->sanitizer->sanitizeHtml($answer);

        $saveData = [
            'question'      => $question,
            'answer'        => $cleanAnswer,
            'category'      => $category,
            'display_order' => $displayOrder,
            'is_active'     => $isActive,
        ];

        if (!$this->faqModel->validate($saveData)) {
            return [
                'success' => false,
                'errors'  => $this->faqModel->errors(),
            ];
        }

        $id = $this->faqModel->insert($saveData);
        if (!$id) {
            return [
                'success' => false,
                'errors'  => ['db' => 'Failed to create FAQ in database.'],
            ];
        }

        $this->activityService->log(
            'faq.created',
            $adminId,
            'faq',
            (int) $id,
            "Created FAQ ID {$id}: '{$question}'",
            ['question' => $question, 'is_active' => $isActive]
        );

        return [
            'success' => true,
            'id'      => (int) $id,
        ];
    }

    /**
     * Update an existing FAQ.
     */
    public function updateFaq(int $id, array $data, ?int $adminId = null): array
    {
        $existing = $this->getFaqById($id);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'FAQ not found.'],
            ];
        }

        $question = trim((string) ($data['question'] ?? ''));
        $answer = trim((string) ($data['answer'] ?? ''));
        $category = trim((string) ($data['category'] ?? '')) ?: null;
        $displayOrder = isset($data['display_order']) && $data['display_order'] !== '' ? (int) $data['display_order'] : 100;
        $isActive = isset($data['is_active']) ? (!empty($data['is_active']) ? 1 : 0) : (int) $existing['is_active'];

        $cleanAnswer = $this->sanitizer->sanitizeHtml($answer);

        $saveData = [
            'question'      => $question,
            'answer'        => $cleanAnswer,
            'category'      => $category,
            'display_order' => $displayOrder,
            'is_active'     => $isActive,
        ];

        if (!$this->faqModel->validate($saveData)) {
            return [
                'success' => false,
                'errors'  => $this->faqModel->errors(),
            ];
        }

        $updated = $this->faqModel->update($id, $saveData);
        if (!$updated) {
            return [
                'success' => false,
                'errors'  => ['db' => 'Failed to update FAQ in database.'],
            ];
        }

        $this->activityService->log(
            'faq.updated',
            $adminId,
            'faq',
            $id,
            "Updated FAQ ID {$id}: '{$question}'",
            ['question' => $question, 'is_active' => $isActive]
        );

        return [
            'success' => true,
            'id'      => $id,
        ];
    }

    /**
     * Soft delete an FAQ.
     */
    public function deleteFaq(int $id, ?int $adminId = null): array
    {
        $existing = $this->getFaqById($id);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'FAQ not found.'],
            ];
        }

        $deleted = $this->faqModel->delete($id);
        if (!$deleted) {
            return [
                'success' => false,
                'errors'  => ['db' => 'Failed to delete FAQ.'],
            ];
        }

        $this->activityService->log(
            'faq.deleted',
            $adminId,
            'faq',
            $id,
            "Deleted FAQ ID {$id}: '{$existing['question']}'",
            ['question' => $existing['question']]
        );

        return [
            'success' => true,
        ];
    }

    /**
     * Toggle active state for an FAQ.
     */
    public function toggleActive(int $id, ?int $adminId = null): array
    {
        $existing = $this->getFaqById($id);
        if (!$existing) {
            return [
                'success' => false,
                'errors'  => ['id' => 'FAQ not found.'],
            ];
        }

        $newStatus = $existing['is_active'] ? 0 : 1;
        $this->faqModel->update($id, ['is_active' => $newStatus]);

        $this->activityService->log(
            'faq.status_changed',
            $adminId,
            'faq',
            $id,
            "Toggled active status for FAQ ID {$id} to " . ($newStatus ? 'active' : 'inactive'),
            ['is_active' => $newStatus]
        );

        return [
            'success'   => true,
            'is_active' => $newStatus,
        ];
    }
}

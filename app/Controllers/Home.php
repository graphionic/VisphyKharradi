<?php

namespace App\Controllers;

/**
 * Home Controller — Public Root
 *
 * Renders public frontend layout and active page views.
 * Phase 6A: Frontend Foundation
 */
class Home extends BaseController
{
    public function index(): string
    {
        $settingModel = new \App\Models\SettingModel();
        $packageModel = new \App\Models\PackageModel();
        $featureModel = new \App\Models\PackageFeatureModel();

        // Resolve active package display design setting (fallback to concept_02)
        $activeDesign = $settingModel->getPackageDisplayDesign();

        // Query active packages from database ordered by display_order
        $packages = $packageModel
            ->where('is_active', 1)
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('display_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        // Hydrate active features for each package
        if (!empty($packages)) {
            $packageIds = array_column($packages, 'id');
            $allFeatures = $featureModel
                ->whereIn('package_id', $packageIds)
                ->where('is_active', 1)
                ->orderBy('display_order', 'ASC')
                ->findAll();

            $featuresByPackage = [];
            foreach ($allFeatures as $feat) {
                $pid = (int) $feat['package_id'];
                $featuresByPackage[$pid][] = $feat['feature_text'];
            }

            foreach ($packages as &$pkg) {
                $pid = (int) $pkg['id'];
                $pkg['features'] = $featuresByPackage[$pid] ?? [];
            }
            unset($pkg);
        }

        // Fetch active FAQs for Section 07
        $faqService = new \App\Services\FaqService();
        $faqs = $faqService->getActiveFaqs();

        // Fetch active public Client Results for Section 06
        $resultService = new \App\Services\ClientResultService();
        $clientResults = $resultService->getPublicClientResultsForLanding();

        return view('frontend/pages/home', [
            'title'            => "Ftpreneur — Visphy Kharradi's Nutrition, Strength & Disease Management",
            'meta_description' => "Transform your health through scientific nutrition, strength training, and lifestyle disease management with Visphy Kharradi.",
            'canonical_url'    => base_url('/'),
            'activeDesign'     => $activeDesign,
            'packages'         => $packages,
            'faqs'             => $faqs,
            'clientResults'    => $clientResults,
        ]);
    }

    /**
     * Public AJAX endpoint returning safe Client Result detail for bottom drawer.
     */
    public function resultDetail(int $id)
    {
        $service = new \App\Services\ClientResultService();
        $detail = $service->getFullClientResultForPublic($id);

        if (!$detail) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'error'   => 'Client Result not found or inactive.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => $detail,
        ]);
    }

    /**
     * Public secure streaming route for public reports/evidence.
     */
    public function streamPublicReport(int $reportId)
    {
        $service = new \App\Services\ClientResultService();
        $res = $service->servePublicReportFile($reportId);

        if (!$res['success']) {
            return $this->response
                ->setStatusCode($res['code'] ?? 404)
                ->setJSON(['success' => false, 'error' => $res['error']]);
        }

        return $this->response
            ->setHeader('Content-Type', $res['mime'])
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($res['filename']) . '"')
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody(file_get_contents($res['path']));
    }
}

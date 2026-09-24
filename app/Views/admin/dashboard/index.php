<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php
$pageContext = 'Dashboard';
$eyebrow = 'Overview';
$title = 'Dashboard';
$desc = 'Welcome back, ' . ($admin['name'] ?? 'Admin') . ' — the premium shell is ready. Future modules will appear here as they ship.';
?>
<?= $this->include('admin/partials/page_header', ['title' => $title, 'description' => $desc, 'eyebrow' => $eyebrow]) ?>

<div class="u-stack-lg">

    <!-- Welcome / context -->
    <section class="card">
        <div class="card__body">
            <div class="u-flex-between" style="flex-wrap:wrap; gap:16px;">
                <div class="u-stack-sm" style="min-width:260px;">
                    <div class="caption">Authenticated</div>
                    <div class="u-flex" style="gap:10px;">
                        <span style="width:8px;height:8px;border-radius:9999px;background:var(--color-success);display:inline-block;flex-shrink:0; box-shadow:0 0 0 4px rgba(14,122,91,0.12);"></span>
                        <h2 class="h3">Authenticated successfully.</h2>
                    </div>
                    <p class="small u-muted" style="margin:0; font-size:0.80rem; color:var(--color-text-faint);">Welcome back</p>
                    <p class="small u-muted" style="margin:0;">Signed in as <strong style="color:var(--color-text); font-weight:600;"><?= esc($admin['name'] ?? '') ?></strong> <span class="u-muted">(<?= esc($admin['email'] ?? '') ?>)</span></p>
                    <div class="u-flex" style="gap:8px; flex-wrap:wrap; margin-top:4px;">
                        <span class="badge badge--primary">ID <?= esc((string)($admin['id'] ?? '')) ?></span>
                        <span class="badge badge--neutral">Session <?= esc(substr((string) session()->get('auth_time'), 0, 10)) ?></span>
                        <span class="badge badge--info">no-store</span>
                    </div>
                </div>
                <div class="u-flex" style="gap:12px;">
                    <a href="<?= site_url('admin/profile') ?>" class="btn btn--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="7.5" r="3.2" stroke="currentColor" stroke-width="1.35"/><path d="M4.5 15.5a5.5 5.5 0 0 1 11 0" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg>
                        Profile &amp; security
                    </a>
                    <?php if (ENVIRONMENT !== 'production'): ?>
                    <a href="<?= site_url('admin/ui-preview') ?>" class="btn btn--secondary">View design system</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Structure preview — no fake metrics -->
    <section>
        <div class="demo-section__title">Upcoming structure — Phase 5</div>
        <div class="u-stack" style="margin-top:12px;">
            <div style="display:grid; gap:16px; grid-template-columns: repeat(auto-fit, minmax(240px,1fr));">
                <div class="card">
                    <div class="card__body">
                        <div class="u-flex-between" style="margin-bottom:10px;">
                            <div class="card__title">Packages</div>
                            <span class="badge badge--neutral">Soon</span>
                        </div>
                        <p class="small u-muted">Create and curate wellness programs for the public site.</p>
                        <div class="skeleton skeleton--text" style="width:88%; margin-top:16px;"></div>
                        <div class="skeleton skeleton--text" style="width:64%; margin-top:8px;"></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card__body">
                        <div class="u-flex-between" style="margin-bottom:10px;">
                            <div class="card__title">Orders</div>
                            <span class="badge badge--neutral">Soon</span>
                        </div>
                        <p class="small u-muted">Review customer orders, payment status and fulfillment.</p>
                        <div class="skeleton skeleton--text" style="width:88%; margin-top:16px;"></div>
                        <div class="skeleton skeleton--text" style="width:64%; margin-top:8px;"></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card__body">
                        <div class="u-flex-between" style="margin-bottom:10px;">
                            <div class="card__title">Payments</div>
                            <span class="badge badge--neutral">Soon</span>
                        </div>
                        <p class="small u-muted">Reconcile payments, refunds and payout readiness.</p>
                        <div class="skeleton skeleton--text" style="width:88%; margin-top:16px;"></div>
                        <div class="skeleton skeleton--text" style="width:64%; margin-top:8px;"></div>
                    </div>
                </div>
            </div>

            <div class="card card--subtle">
                <div class="card__body" style="display:grid; gap:10px;">
                    <div class="caption">What this shell demonstrates</div>
                    <ul class="u-stack-sm" style="margin:0; padding-left:18px; color:var(--color-text-muted); font-size:0.875rem; line-height:1.5;">
                        <li>Surface hierarchy: canvas → card → subtle grouping</li>
                        <li>Typography &amp; spacing rhythm validated at 1440 / 768 / 390</li>
                        <li>Navigation, topbar and page header composition ready for business modules</li>
                        <li>No placeholder revenue — design reviewed on structure, not fake data</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

</div>
<?= $this->endSection() ?>

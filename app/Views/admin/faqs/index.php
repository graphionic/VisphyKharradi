<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php
$hasFilters = ($filters['q'] !== '' || $filters['status'] !== 'all');
$hasFaqs = !empty($faqs);
?>
<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Content</div>
        <h1 class="page-header__title">FAQs</h1>
        <p class="page-header__desc">Manage dynamic questions and answers rendered on the landing page.</p>
    </div>
    <div class="page-header__actions" style="display:flex; gap:8px;">
        <a href="<?= esc(site_url('admin/faqs/create'), 'attr') ?>" class="btn btn--primary">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            Add FAQ
        </a>
    </div>
</div>

<div class="u-stack-lg">

    <!-- Flash Notifications -->
    <?= $this->include('admin/partials/flash') ?>

    <!-- Filter toolbar -->
    <section class="card">
        <div class="card__body">
            <form method="get" action="<?= esc(site_url('admin/faqs'), 'attr') ?>" style="display:grid; grid-template-columns: 1fr 180px auto; gap:12px; align-items:end;">
                <div class="field" style="margin:0;">
                    <label class="field__label" for="q">Search</label>
                    <input class="field__input" id="q" name="q" type="search" placeholder="Search questions or answers..." value="<?= esc($filters['q']) ?>" maxlength="190" autocomplete="off" aria-label="Search FAQs">
                </div>
                <div class="field" style="margin:0;">
                    <label class="field__label" for="status">Status</label>
                    <select class="field__input" id="status" name="status" aria-label="Filter by status">
                        <option value="all" <?= $filters['status']==='all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= $filters['status']==='active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filters['status']==='inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn--secondary">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= esc(site_url('admin/faqs'), 'attr') ?>" class="btn btn--ghost">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <!-- FAQ Table Card -->
    <section class="card">
        <div class="card__header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 class="card__title">FAQ Registry</h2>
                <p class="card__desc">Showing <?= esc($rangeStart) ?>–<?= esc($rangeEnd) ?> of <?= esc($total) ?> items</p>
            </div>
        </div>

        <div class="card__body u-padding-0">
            <?php if (!$hasFaqs): ?>
                <div style="padding:48px 24px; text-align:center; color:var(--admin-text-muted);">
                    <p style="font-size:15px; margin-bottom:12px;">No FAQs found.</p>
                    <?php if ($hasFilters): ?>
                        <a href="<?= esc(site_url('admin/faqs'), 'attr') ?>" class="btn btn--ghost">Clear search filters</a>
                    <?php else: ?>
                        <a href="<?= esc(site_url('admin/faqs/create'), 'attr') ?>" class="btn btn--primary">Create your first FAQ</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:70px;">Order</th>
                                <th>Question</th>
                                <th style="width:140px;">Category</th>
                                <th style="width:110px;">Status</th>
                                <th style="width:160px;">Updated</th>
                                <th style="width:200px; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faqs as $faq): ?>
                                <tr>
                                    <td>
                                        <span style="font-family:var(--font-family-mono); font-weight:700; color:var(--admin-text-subtle);">#<?= esc($faq['display_order']) ?></span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:var(--admin-text-main); line-height:1.4; margin-bottom:4px; max-width:520px;">
                                            <?= esc($faq['question']) ?>
                                        </div>
                                        <div style="font-size:12px; color:var(--admin-text-muted); max-width:520px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <?= esc(strip_tags($faq['answer'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge--neutral" style="font-size:11px;">
                                            <?= esc($faq['category'] ?? 'General') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($faq['is_active']): ?>
                                            <span class="badge badge--success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge--muted">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:12px; color:var(--admin-text-muted);">
                                        <?= esc(date('M j, Y H:i', strtotime($faq['updated_at'] ?? $faq['created_at']))) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex; gap:6px; align-items:center;">
                                            <!-- Toggle Active -->
                                            <form method="post" action="<?= esc(site_url("admin/faqs/{$faq['id']}/toggle-active"), 'attr') ?>" style="margin:0; display:inline;">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn--ghost" style="padding:4px 8px; font-size:12px;" title="<?= $faq['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                    <?= $faq['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>

                                            <!-- Edit -->
                                            <a href="<?= esc(site_url("admin/faqs/{$faq['id']}/edit"), 'attr') ?>" class="btn btn--secondary" style="padding:4px 10px; font-size:12px;">
                                                Edit
                                            </a>

                                            <!-- Delete -->
                                            <form method="post" action="<?= esc(site_url("admin/faqs/{$faq['id']}/delete"), 'attr') ?>" style="margin:0; display:inline;" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn--ghost" style="padding:4px 8px; font-size:12px; color:var(--admin-danger);" title="Delete FAQ">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pager && $pager->getPageCount() > 1): ?>
                    <div style="padding:16px 24px; border-top:1px solid var(--admin-border-subtle);">
                        <?= $pager->links('default', 'default_full') ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </section>

</div>
<?= $this->endSection() ?>

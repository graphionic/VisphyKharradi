<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php
$hasFilters = ($filters['q'] !== '' || $filters['status'] !== 'all' || $filters['featured'] !== 'all');
$hasResults = !empty($results);
?>
<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Content</div>
        <h1 class="page-header__title">Client Results</h1>
        <p class="page-header__desc">Manage transformation stories, testimonials, and client proof records.</p>
    </div>
    <div class="page-header__actions" style="display:flex; gap:8px;">
        <a href="<?= esc(site_url('admin/client-results/create'), 'attr') ?>" class="btn btn--primary">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            Add Client Result
        </a>
    </div>
</div>

<div class="u-stack-lg">

    <!-- Flash Notifications -->
    <?= $this->include('admin/partials/flash') ?>

    <!-- Filter toolbar -->
    <section class="card">
        <div class="card__body">
            <form method="get" action="<?= esc(site_url('admin/client-results'), 'attr') ?>" style="display:grid; grid-template-columns: 1fr 160px 160px auto; gap:12px; align-items:end;">
                <div class="field" style="margin:0;">
                    <label class="field__label" for="q">Search</label>
                    <input class="field__input" id="q" name="q" type="search" placeholder="Search client name, program, or story..." value="<?= esc($filters['q']) ?>" maxlength="190" autocomplete="off" aria-label="Search Client Results">
                </div>
                <div class="field" style="margin:0;">
                    <label class="field__label" for="status">Status</label>
                    <select class="field__input" id="status" name="status" aria-label="Filter by status">
                        <option value="all" <?= $filters['status']==='all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= $filters['status']==='active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filters['status']==='inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="field" style="margin:0;">
                    <label class="field__label" for="featured">Featured</label>
                    <select class="field__input" id="featured" name="featured" aria-label="Filter by featured status">
                        <option value="all" <?= $filters['featured']==='all' ? 'selected' : '' ?>>All Items</option>
                        <option value="featured" <?= $filters['featured']==='featured' ? 'selected' : '' ?>>Featured Only</option>
                        <option value="not_featured" <?= $filters['featured']==='not_featured' ? 'selected' : '' ?>>Standard Only</option>
                    </select>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn--secondary">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= esc(site_url('admin/client-results'), 'attr') ?>" class="btn btn--ghost">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <!-- Table Listing Card -->
    <section class="card">
        <div class="card__header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 class="card__title">Client Results Registry</h2>
                <p class="card__desc">Showing <?= esc($rangeStart) ?>–<?= esc($rangeEnd) ?> of <?= esc($total) ?> records</p>
            </div>
        </div>

        <div class="card__body u-padding-0">
            <?php if (!$hasResults): ?>
                <div style="padding:48px 24px; text-align:center; color:var(--admin-text-muted);">
                    <p style="font-size:15px; margin-bottom:12px;">No Client Results found.</p>
                    <?php if ($hasFilters): ?>
                        <a href="<?= esc(site_url('admin/client-results'), 'attr') ?>" class="btn btn--ghost">Clear search filters</a>
                    <?php else: ?>
                        <a href="<?= esc(site_url('admin/client-results/create'), 'attr') ?>" class="btn btn--primary">Create your first Client Result</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:70px;">Order</th>
                                <th>Client Identity</th>
                                <th>Program / Service Snapshot</th>
                                <th style="width:130px;">Duration</th>
                                <th style="width:100px;">Featured</th>
                                <th style="width:100px;">Status</th>
                                <th style="width:140px;">Updated</th>
                                <th style="width:220px; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $res): ?>
                                <tr>
                                    <td>
                                        <span style="font-family:var(--font-family-mono); font-weight:700; color:var(--admin-text-subtle);">#<?= esc($res['display_order']) ?></span>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:12px;">
                                            <?php if (!empty($res['cover_image'])): ?>
                                                <img src="<?= base_url(esc($res['cover_image'])) ?>" alt="" style="width:40px; height:40px; border-radius:8px; object-fit:cover; background:#16213A; border:1px solid var(--admin-border-subtle);" />
                                            <?php else: ?>
                                                <div style="width:40px; height:40px; border-radius:8px; background:var(--admin-bg-subdued); display:grid; place-items:center; color:var(--admin-text-muted); font-weight:700; font-size:14px; border:1px solid var(--admin-border-subtle);">
                                                    <?= esc(strtoupper(substr($res['client_display_name'], 0, 1))) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight:700; color:var(--admin-text-main); line-height:1.3;">
                                                    <?= esc($res['client_display_name']) ?>
                                                </div>
                                                <?php if (!empty($res['client_subtitle'])): ?>
                                                    <div style="font-size:12px; color:var(--admin-text-muted);">
                                                        <?= esc($res['client_subtitle']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge--neutral" style="font-size:11.5px;">
                                            <?= esc($res['program_name_snapshot'] ?? 'Custom Service') ?>
                                        </span>
                                    </td>
                                    <td style="font-size:13px; color:var(--admin-text-main);">
                                        <?= esc($res['journey_duration'] ?? 'N/A') ?>
                                    </td>
                                    <td>
                                        <!-- Featured Toggle Form -->
                                        <form method="post" action="<?= esc(site_url("admin/client-results/{$res['id']}/toggle-featured"), 'attr') ?>" style="margin:0; display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" style="background:none; border:0; padding:0; cursor:pointer;" title="Toggle Featured State">
                                                <?php if ($res['is_featured']): ?>
                                                    <span class="badge badge--accent" style="background:var(--color-accent-light, #FFF6EB); color:#E88B1A; border:1px solid #FF9E2C;">★ Featured</span>
                                                <?php else: ?>
                                                    <span class="badge badge--muted" style="opacity:0.7;">Standard</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <!-- Active Toggle Form -->
                                        <form method="post" action="<?= esc(site_url("admin/client-results/{$res['id']}/toggle-active"), 'attr') ?>" style="margin:0; display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" style="background:none; border:0; padding:0; cursor:pointer;" title="Toggle Active Publication">
                                                <?php if ($res['is_active']): ?>
                                                    <span class="badge badge--success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge--muted">Inactive</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="font-size:12px; color:var(--admin-text-muted);">
                                        <?= esc(date('M j, Y H:i', strtotime($res['updated_at'] ?? $res['created_at']))) ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex; gap:6px; align-items:center;">
                                            <a href="<?= esc(site_url("admin/client-results/{$res['id']}/edit"), 'attr') ?>" class="btn btn--secondary" style="padding:4px 10px; font-size:12px;">
                                                Edit
                                            </a>
                                            <a href="<?= esc(site_url("admin/client-results/{$res['id']}/preview"), 'attr') ?>" class="btn btn--ghost" style="padding:4px 8px; font-size:12px;" title="Preview Complete Proof">
                                                Preview
                                            </a>
                                            <form method="post" action="<?= esc(site_url("admin/client-results/{$res['id']}/delete"), 'attr') ?>" style="margin:0; display:inline;" onsubmit="return confirm('Are you sure you want to delete this Client Result? Logical child metrics, media, and reports will also be cleaned up.');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn--ghost" style="padding:4px 8px; font-size:12px; color:var(--admin-danger);" title="Delete Client Result">
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

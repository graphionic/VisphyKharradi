<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php
use App\Services\PackageService;
?>
<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Management</div>
        <h1 class="page-header__title">Deleted Packages</h1>
        <p class="page-header__desc">Soft-deleted packages. You can restore them; they will be inactive and at the end of the order.</p>
    </div>
    <div class="page-header__actions">
        <a href="<?= esc(site_url('admin/packages'), 'attr') ?>" class="btn btn--ghost">Back to Packages</a>
    </div>
</div>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert--success" role="status" style="margin-bottom:16px;"><div class="alert__content"><?= esc(session()->getFlashdata('message')) ?></div></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert--danger" role="alert" style="margin-bottom:16px;"><div class="alert__content"><?= esc(session()->getFlashdata('error')) ?></div></div>
<?php endif; ?>

<?php if (empty($packages)): ?>
    <div class="empty" role="status" style="margin-top:16px;">
        <div class="empty__icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 20 20" fill="none"><rect x="3" y="4" width="14" height="12" rx="1.4" stroke="currentColor" stroke-width="1.35"/><path d="M7 8h6M7 12h4" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg></div>
        <h3 class="empty__title">No deleted packages</h3>
        <p class="empty__desc">Deleted packages will appear here for restore.</p>
    </div>
<?php else: ?>
    <div class="table-wrap" style="margin-top:16px;" role="region" aria-label="Deleted packages" tabindex="0">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Package</th>
                    <th scope="col">Price</th>
                    <th scope="col">Duration</th>
                    <th scope="col">Deleted Date</th>
                    <th scope="col" style="text-align:right;">Restore</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packages as $pkg): ?>
                    <?php
                        $fid = (int)$pkg['id'];
                        $priceSelling = PackageService::formatPrice($pkg['selling_price']);
                        $duration = PackageService::formatDuration($pkg['duration_value'] ?? null, $pkg['duration_unit'] ?? null);
                        $thumb = $pkg['featured_image'] ?? null;
                        $deletedAt = $pkg['deleted_at'] ?? '';
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <div style="width:48px; height:36px; border-radius:6px; overflow:hidden; border:1px solid var(--color-border); background:var(--color-surface-muted); flex-shrink:0; display:grid; place-items:center;">
                                    <?php if (!empty($thumb)): ?>
                                        <img src="<?= esc('/' . ltrim($thumb, '/'), 'attr') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="color:var(--color-text-muted)"><rect x="3" y="4" width="14" height="12" rx="1.2" stroke="currentColor" stroke-width="1.2"/><path d="M3 12l4-4 3 3 4-5 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1" fill="currentColor"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="table__primary"><?= esc($pkg['name']) ?></div>
                                    <div class="table__muted"><?= esc($pkg['slug']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="table__mono"><?= esc($priceSelling) ?></td>
                        <td><?= esc($duration) ?></td>
                        <td><?= esc($deletedAt) ?></td>
                        <td style="text-align:right;">
                            <form method="post" action="<?= esc(site_url('admin/packages/' . $fid . '/restore'), 'attr') ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn--secondary btn--sm">Restore</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>

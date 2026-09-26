<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('head') ?>
<style>
.preview-mode-switch { display:inline-flex; background:var(--admin-bg-subdued); border:1px solid var(--admin-border-subtle); border-radius:20px; padding:3px; }
.preview-mode-pill { display:inline-flex; align-items:center; padding:5px 14px; border-radius:16px; font-size:12px; font-weight:600; text-decoration:none; color:var(--admin-text-muted); transition:all 0.2s ease; }
.preview-mode-pill--active { background:var(--admin-primary); color:#ffffff; font-weight:700; shadow:0 2px 4px rgba(0,0,0,0.1); }

.readiness-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap:12px; }
.readiness-item { padding:10px 12px; background:var(--admin-bg-surface); border:1px solid var(--admin-border-subtle); border-radius:8px; display:flex; align-items:center; justify-content:space-between; }
.readiness-item__label { font-size:12px; font-weight:600; color:var(--admin-text-muted); }
.readiness-item__val { font-size:12px; font-weight:700; color:var(--admin-text-main); }
.readiness-item__val--ok { color:#15803d; }
.readiness-item__val--none { color:var(--admin-text-muted); }

.proof-hero-card { background:linear-gradient(135deg, var(--admin-bg-surface) 0%, var(--admin-bg-subdued) 100%); border:1px solid var(--admin-border-subtle); border-radius:12px; padding:28px; }
.proof-focus-pill { display:inline-flex; align-items:center; background:var(--admin-bg-surface); color:var(--admin-primary); border:1px solid var(--admin-border-subtle); padding:4px 12px; border-radius:16px; font-size:12px; font-weight:700; letter-spacing:0.03em; }

.metric-proof-card { background:var(--admin-bg-surface); border:1px solid var(--admin-border-subtle); border-radius:10px; padding:18px; }
.metric-proof-val { font-size:18px; font-weight:800; color:var(--admin-text-main); display:flex; align-items:center; gap:8px; margin:8px 0; }
.metric-proof-val__arrow { color:var(--admin-primary); }

.story-rendered-content { font-size:14.5px; line-height:1.7; color:var(--admin-text-main); }
.story-rendered-content p { margin-bottom:12px; }
.story-rendered-content h2, .story-rendered-content h3 { font-weight:700; color:var(--admin-text-main); margin-top:18px; margin-bottom:8px; }
.story-rendered-content ul, .story-rendered-content ol { padding-left:20px; margin-bottom:12px; }

.private-badge { font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; text-transform:uppercase; }
.public-badge { font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; text-transform:uppercase; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$res       = $previewData['result'];
$readiness = $previewData['readiness'];
$media     = $previewData['media'];
$focus     = $previewData['focusAreas'];
$metrics   = $previewData['metrics'];
$reports   = $previewData['reports'];
?>

<!-- Page Header & Utility Bar -->
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Content / Client Results / Admin Proof Preview</div>
        <h1 class="page-header__title" style="display:flex; align-items:center; gap:12px;">
            <span>Client Proof Preview</span>
            <?php if ($res['is_active']): ?>
                <span class="badge badge--success" style="font-size:11px;">Active</span>
            <?php else: ?>
                <span class="badge badge--muted" style="font-size:11px;">Inactive</span>
            <?php endif; ?>
            <?php if ($res['is_featured']): ?>
                <span class="badge badge--accent" style="font-size:11px; background:var(--color-accent-light, #FFF6EB); color:#E88B1A; border:1px solid #FF9E2C;">★ Featured</span>
            <?php endif; ?>
        </h1>
        <p class="page-header__desc">Inspect approved client proof content as configured for visitor presentation.</p>
    </div>
    <div class="page-header__actions" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <!-- Mode Switcher -->
        <div class="preview-mode-switch">
            <a href="<?= esc(site_url("admin/client-results/{$res['id']}/preview?mode=public"), 'attr') ?>" class="preview-mode-pill <?= $mode === 'public' ? 'preview-mode-pill--active' : '' ?>">
                Public Preview
            </a>
            <a href="<?= esc(site_url("admin/client-results/{$res['id']}/preview?mode=all"), 'attr') ?>" class="preview-mode-pill <?= $mode === 'all' ? 'preview-mode-pill--active' : '' ?>">
                All Evidence (Admin)
            </a>
        </div>
        <a href="<?= esc(site_url("admin/client-results/{$res['id']}/edit"), 'attr') ?>" class="btn btn--secondary">
            Back to Edit
        </a>
        <a href="<?= esc(site_url('admin/client-results'), 'attr') ?>" class="btn btn--ghost">
            Client Registry
        </a>
    </div>
</div>

<!-- Inactive Status Warning Notice -->
<?php if (!$res['is_active']): ?>
    <div style="padding:12px 16px; background:#fffbeb; border:1px solid #fef3c7; border-radius:8px; color:#92400e; font-size:13px; font-weight:600; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>This Client Result is currently <strong>Inactive</strong> and will not be eligible for visitor-facing display until activated.</span>
    </div>
<?php endif; ?>

<div class="u-stack-lg" style="max-width:960px;">

    <!-- PROOF READINESS SUMMARY -->
    <section class="card">
        <div class="card__header">
            <h2 class="card__title">PROOF READINESS SUMMARY</h2>
            <p class="card__desc">Factual status of content presence across Client Result components.</p>
        </div>
        <div class="card__body">
            <div class="readiness-grid">
                
                <div class="readiness-item">
                    <span class="readiness-item__label">Featured Image</span>
                    <span class="readiness-item__val <?= $readiness['has_cover_image'] ? 'readiness-item__val--ok' : 'readiness-item__val--none' ?>">
                        <?= $readiness['has_cover_image'] ? '✓ Added' : '— Missing' ?>
                    </span>
                </div>

                <div class="readiness-item">
                    <span class="readiness-item__label">Focus Areas</span>
                    <span class="readiness-item__val <?= $readiness['focus_areas_count'] > 0 ? 'readiness-item__val--ok' : 'readiness-item__val--none' ?>">
                        <?= $readiness['focus_areas_count'] > 0 ? '✓ ' . $readiness['focus_areas_count'] . ' added' : '— None' ?>
                    </span>
                </div>

                <div class="readiness-item">
                    <span class="readiness-item__label">Public Metrics</span>
                    <span class="readiness-item__val <?= $readiness['public_metrics_count'] > 0 ? 'readiness-item__val--ok' : 'readiness-item__val--none' ?>">
                        <?= $readiness['public_metrics_count'] > 0 ? '✓ ' . $readiness['public_metrics_count'] . ' public' : '— None' ?>
                    </span>
                </div>

                <div class="readiness-item">
                    <span class="readiness-item__label">Before / After</span>
                    <span class="readiness-item__val <?= ($readiness['has_before_image'] && $readiness['has_after_image']) ? 'readiness-item__val--ok' : 'readiness-item__val--none' ?>">
                        Before <?= $readiness['has_before_image'] ? '✓' : '—' ?> / After <?= $readiness['has_after_image'] ? '✓' : '—' ?>
                    </span>
                </div>

                <div class="readiness-item">
                    <span class="readiness-item__label">Progress Gallery</span>
                    <span class="readiness-item__val <?= $readiness['public_progress_media_count'] > 0 ? 'readiness-item__val--ok' : 'readiness-item__val--none' ?>">
                        <?= $readiness['public_progress_media_count'] > 0 ? '✓ ' . $readiness['public_progress_media_count'] . ' public' : '— None' ?>
                    </span>
                </div>

                <div class="readiness-item">
                    <span class="readiness-item__label">Story Gallery</span>
                    <span class="readiness-item__val <?= $readiness['public_gallery_media_count'] > 0 ? 'readiness-item__val--ok' : 'readiness-item__val--none' ?>">
                        <?= $readiness['public_gallery_media_count'] > 0 ? '✓ ' . $readiness['public_gallery_media_count'] . ' public' : '— None' ?>
                    </span>
                </div>

                <div class="readiness-item">
                    <span class="readiness-item__label">Reports / Evidence</span>
                    <span class="readiness-item__val <?= $readiness['public_reports_count'] > 0 ? 'readiness-item__val--ok' : 'readiness-item__val--none' ?>">
                        <?= $readiness['public_reports_count'] > 0 ? '✓ ' . $readiness['public_reports_count'] . ' public' : '— None' ?>
                    </span>
                </div>

                <div class="readiness-item">
                    <span class="readiness-item__label">Full Story</span>
                    <span class="readiness-item__val <?= $readiness['has_full_story'] ? 'readiness-item__val--ok' : 'readiness-item__val--none' ?>">
                        <?= $readiness['has_full_story'] ? '✓ Added' : '— Missing' ?>
                    </span>
                </div>

            </div>
        </div>
    </section>

    <!-- 01 — CLIENT OVERVIEW & HERO -->
    <div class="proof-hero-card">
        <div style="display:flex; gap:24px; align-items:flex-start; flex-wrap:wrap;">
            
            <?php if (!empty($res['cover_image'])): ?>
                <img src="<?= base_url(esc($res['cover_image'])) ?>" alt="<?= esc($res['client_display_name']) ?>" style="width:140px; height:140px; object-fit:cover; border-radius:12px; border:1px solid var(--admin-border-subtle); background:#000;" />
            <?php else: ?>
                <div style="width:140px; height:140px; border-radius:12px; background:var(--admin-bg-subdued); border:1px dashed var(--admin-border-subtle); display:grid; place-items:center; color:var(--admin-text-muted); font-weight:800; font-size:36px;">
                    <?= esc(strtoupper(substr($res['client_display_name'], 0, 1))) ?>
                </div>
            <?php endif; ?>

            <div style="flex:1; min-width:260px;">
                <div style="font-size:11px; font-weight:700; color:var(--admin-primary); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px;">
                    <?= esc($res['program_name_snapshot'] ?? 'Custom Program') ?> <?= !empty($res['journey_duration']) ? '• ' . esc($res['journey_duration']) : '' ?>
                </div>

                <h2 style="font-size:26px; font-weight:800; color:var(--admin-text-main); margin:0 0 4px 0; line-height:1.2;">
                    <?= esc($res['client_display_name']) ?>
                </h2>

                <?php if (!empty($res['client_subtitle'])): ?>
                    <p style="font-size:14px; color:var(--admin-text-muted); margin:0 0 16px 0;">
                        <?= esc($res['client_subtitle']) ?>
                    </p>
                <?php endif; ?>

                <!-- Focus Area Pills -->
                <?php if (!empty($focus)): ?>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">
                        <?php foreach ($focus as $f): ?>
                            <span class="proof-focus-pill"><?= esc(mb_strtoupper($f['label'])) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- 02 — SHORT CARD TESTIMONIAL -->
    <section class="card">
        <div class="card__header">
            <h2 class="card__title">CARD TESTIMONIAL</h2>
            <p class="card__desc">Concise snippet intended for landing page cards.</p>
        </div>
        <div class="card__body">
            <div style="padding:16px 20px; background:var(--admin-bg-subdued); border-left:4px solid var(--admin-primary); border-radius:0 8px 8px 0; font-size:15px; font-style:italic; color:var(--admin-text-main); line-height:1.6;">
                "<?= esc($res['short_testimonial']) ?>"
            </div>
        </div>
    </section>

    <!-- 03 — FULL TRANSFORMATION STORY -->
    <section class="card">
        <div class="card__header">
            <h2 class="card__title">FULL TRANSFORMATION STORY</h2>
            <p class="card__desc">Detailed long-form story formatted for the proof bottom drawer.</p>
        </div>
        <div class="card__body">
            <?php if (!empty($res['full_story'])): ?>
                <div class="story-rendered-content">
                    <?= $res['full_story'] ?>
                </div>
            <?php else: ?>
                <p style="font-size:13px; color:var(--admin-text-muted); font-style:italic;">No full story content added yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- 04 — MEASURABLE RESULTS -->
    <section class="card">
        <div class="card__header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 class="card__title">MEASURABLE RESULTS</h2>
                <p class="card__desc">Documented before-and-after outcome metrics.</p>
            </div>
            <?php if ($mode === 'public'): ?>
                <span style="font-size:11px; color:var(--admin-text-muted);">Showing Public Metrics Only</span>
            <?php else: ?>
                <span style="font-size:11px; color:var(--admin-text-muted);">Showing All Metrics (Includes Private)</span>
            <?php endif; ?>
        </div>
        <div class="card__body">
            <?php if (empty($metrics)): ?>
                <div style="text-align:center; padding:24px; color:var(--admin-text-muted); font-size:13px; background:var(--admin-bg-subdued); border-radius:8px;">
                    No public measurable results selected for display.
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:16px;">
                    <?php foreach ($metrics as $m): ?>
                        <div class="metric-proof-card">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:12px; font-weight:700; color:var(--admin-text-muted); text-transform:uppercase; letter-spacing:0.04em;">
                                    <?= esc($m['metric_name']) ?>
                                </span>
                                <?php if ($mode === 'all'): ?>
                                    <span class="<?= $m['is_public'] ? 'public-badge' : 'private-badge' ?>">
                                        <?= $m['is_public'] ? 'Public' : 'Private' ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="metric-proof-val">
                                <span><?= esc($m['before_value']) ?> <?= esc($m['unit'] ?? '') ?></span>
                                <span class="metric-proof-val__arrow">→</span>
                                <span style="color:var(--admin-primary);"><?= esc($m['after_value']) ?> <?= esc($m['unit'] ?? '') ?></span>
                            </div>

                            <?php if (!empty($m['context']) || !empty($m['measurement_start_date']) || !empty($m['measurement_end_date'])): ?>
                                <div style="font-size:11px; color:var(--admin-text-muted); margin-top:6px; border-top:1px solid var(--admin-border-subtle); padding-top:6px;">
                                    <?php if (!empty($m['context'])): ?>
                                        <div><?= esc($m['context']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($m['measurement_start_date']) || !empty($m['measurement_end_date'])): ?>
                                        <div>Timeline: <?= esc($m['measurement_start_date'] ?: 'N/A') ?> to <?= esc($m['measurement_end_date'] ?: 'N/A') ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 05 — BEFORE & AFTER -->
    <section class="card">
        <div class="card__header">
            <h2 class="card__title">BEFORE &amp; AFTER TRANSFORMATION</h2>
            <p class="card__desc">Primary visual comparison proof.</p>
        </div>
        <div class="card__body">
            <?php 
            $beforeImg = $media['before'];
            $afterImg  = $media['after'];
            ?>
            <?php if (!$beforeImg && !$afterImg): ?>
                <div style="text-align:center; padding:24px; color:var(--admin-text-muted); font-size:13px; background:var(--admin-bg-subdued); border-radius:8px;">
                    No public Before / After proof selected.
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    
                    <!-- BEFORE -->
                    <div style="border:1px solid var(--admin-border-subtle); border-radius:8px; overflow:hidden; background:var(--admin-bg-surface);">
                        <div style="padding:10px 14px; background:var(--admin-bg-subdued); display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--admin-border-subtle);">
                            <span style="font-size:12px; font-weight:700; color:var(--admin-primary); text-transform:uppercase;">BEFORE</span>
                            <?php if ($mode === 'all' && $beforeImg): ?>
                                <span class="<?= $beforeImg['is_public'] ? 'public-badge' : 'private-badge' ?>"><?= $beforeImg['is_public'] ? 'Public' : 'Private' ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($beforeImg): ?>
                            <div style="height:260px; background:#000;">
                                <img src="<?= base_url(esc($beforeImg['file_path'])) ?>" alt="Before" style="width:100%; height:100%; object-fit:cover;" />
                            </div>
                            <?php if (!empty($beforeImg['caption']) || !empty($beforeImg['media_date'])): ?>
                                <div style="padding:10px; font-size:12px; color:var(--admin-text-muted);">
                                    <?= esc($beforeImg['caption'] ?? '') ?> <?= !empty($beforeImg['media_date']) ? ' (' . esc($beforeImg['media_date']) . ')' : '' ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="height:200px; display:grid; place-items:center; color:var(--admin-text-muted); font-size:12px;">
                                Before photo not selected
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- AFTER -->
                    <div style="border:1px solid var(--admin-border-subtle); border-radius:8px; overflow:hidden; background:var(--admin-bg-surface);">
                        <div style="padding:10px 14px; background:var(--admin-bg-subdued); display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--admin-border-subtle);">
                            <span style="font-size:12px; font-weight:700; color:var(--admin-primary); text-transform:uppercase;">AFTER</span>
                            <?php if ($mode === 'all' && $afterImg): ?>
                                <span class="<?= $afterImg['is_public'] ? 'public-badge' : 'private-badge' ?>"><?= $afterImg['is_public'] ? 'Public' : 'Private' ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($afterImg): ?>
                            <div style="height:260px; background:#000;">
                                <img src="<?= base_url(esc($afterImg['file_path'])) ?>" alt="After" style="width:100%; height:100%; object-fit:cover;" />
                            </div>
                            <?php if (!empty($afterImg['caption']) || !empty($afterImg['media_date'])): ?>
                                <div style="padding:10px; font-size:12px; color:var(--admin-text-muted);">
                                    <?= esc($afterImg['caption'] ?? '') ?> <?= !empty($afterImg['media_date']) ? ' (' . esc($afterImg['media_date']) . ')' : '' ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="height:200px; display:grid; place-items:center; color:var(--admin-text-muted); font-size:12px;">
                                After photo not selected
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 06 — TRANSFORMATION GALLERY -->
    <section class="card">
        <div class="card__header">
            <h2 class="card__title">TRANSFORMATION GALLERY</h2>
            <p class="card__desc">Documented milestone progress photos across the timeline.</p>
        </div>
        <div class="card__body">
            <?php if (empty($media['progress'])): ?>
                <div style="text-align:center; padding:24px; color:var(--admin-text-muted); font-size:13px; background:var(--admin-bg-subdued); border-radius:8px;">
                    No public progress photos selected.
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:16px;">
                    <?php foreach ($media['progress'] as $p): ?>
                        <div style="border:1px solid var(--admin-border-subtle); border-radius:8px; overflow:hidden; background:var(--admin-bg-surface);">
                            <div style="position:relative; height:140px; background:#000;">
                                <img src="<?= base_url(esc($p['file_path'])) ?>" alt="Progress" style="width:100%; height:100%; object-fit:cover;" />
                                <?php if ($mode === 'all'): ?>
                                    <div style="position:absolute; top:6px; right:6px;">
                                        <span class="<?= $p['is_public'] ? 'public-badge' : 'private-badge' ?>"><?= $p['is_public'] ? 'Public' : 'Private' ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($p['caption']) || !empty($p['media_date'])): ?>
                                <div style="padding:10px; font-size:11.5px; color:var(--admin-text-main);">
                                    <?php if (!empty($p['caption'])): ?><div style="font-weight:600;"><?= esc($p['caption']) ?></div><?php endif; ?>
                                    <?php if (!empty($p['media_date'])): ?><div style="color:var(--admin-text-muted);"><?= esc($p['media_date']) ?></div><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 07 — CLIENT STORY GALLERY -->
    <section class="card">
        <div class="card__header">
            <h2 class="card__title">CLIENT STORY GALLERY</h2>
            <p class="card__desc">Supporting lifestyle, achievement, and story photos.</p>
        </div>
        <div class="card__body">
            <?php if (empty($media['gallery'])): ?>
                <div style="text-align:center; padding:24px; color:var(--admin-text-muted); font-size:13px; background:var(--admin-bg-subdued); border-radius:8px;">
                    No public client story photos selected.
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:16px;">
                    <?php foreach ($media['gallery'] as $g): ?>
                        <div style="border:1px solid var(--admin-border-subtle); border-radius:8px; overflow:hidden; background:var(--admin-bg-surface);">
                            <div style="position:relative; height:140px; background:#000;">
                                <img src="<?= base_url(esc($g['file_path'])) ?>" alt="Story Gallery" style="width:100%; height:100%; object-fit:cover;" />
                                <?php if ($mode === 'all'): ?>
                                    <div style="position:absolute; top:6px; right:6px;">
                                        <span class="<?= $g['is_public'] ? 'public-badge' : 'private-badge' ?>"><?= $g['is_public'] ? 'Public' : 'Private' ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($g['caption']) || !empty($g['media_date'])): ?>
                                <div style="padding:10px; font-size:11.5px; color:var(--admin-text-main);">
                                    <?php if (!empty($g['caption'])): ?><div style="font-weight:600;"><?= esc($g['caption']) ?></div><?php endif; ?>
                                    <?php if (!empty($g['media_date'])): ?><div style="color:var(--admin-text-muted);"><?= esc($g['media_date']) ?></div><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 08 — REPORTS / EVIDENCE -->
    <section class="card">
        <div class="card__header">
            <h2 class="card__title">REPORTS / EVIDENCE</h2>
            <p class="card__desc">Approved supporting documentary evidence and lab reports.</p>
        </div>
        <div class="card__body">
            <?php if (empty($reports)): ?>
                <div style="text-align:center; padding:24px; color:var(--admin-text-muted); font-size:13px; background:var(--admin-bg-subdued); border-radius:8px;">
                    No public reports or supporting evidence selected.
                </div>
            <?php else: ?>
                <div class="u-stack-sm">
                    <?php foreach ($reports as $r): 
                        $isPdf = strpos($r['file_mime'], 'pdf') !== false || strtolower(pathinfo($r['file_path'], PATHINFO_EXTENSION)) === 'pdf';
                        $formattedSize = $r['file_size'] >= 1048576 ? number_format($r['file_size'] / 1048576, 1) . ' MB' : number_format($r['file_size'] / 1024, 1) . ' KB';
                        $typeLabels = [
                            'lab_report'          => 'Lab Report',
                            'assessment_report'   => 'Assessment Report',
                            'progress_report'     => 'Progress Report',
                            'body_composition'   => 'Body Composition',
                            'fitness_assessment' => 'Fitness Assessment',
                            'other'               => 'Other',
                        ];
                        $typeName = $typeLabels[$r['report_type']] ?? 'Document';
                    ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:var(--admin-bg-surface); border:1px solid var(--admin-border-subtle); border-radius:8px; gap:16px; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:36px; height:36px; border-radius:6px; background:var(--admin-bg-subdued); border:1px solid var(--admin-border-subtle); display:grid; place-items:center; color:<?= $isPdf ? '#ef4444' : 'var(--admin-primary)' ?>;">
                                    <?php if ($isPdf): ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    <?php else: ?>
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span style="font-weight:700; font-size:13.5px; color:var(--admin-text-main);"><?= esc($r['report_title']) ?></span>
                                        <span style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:10px; background:var(--admin-bg-subdued); color:var(--admin-text-muted); border:1px solid var(--admin-border-subtle); text-transform:uppercase;"><?= esc($typeName) ?></span>
                                        <?php if ($mode === 'all'): ?>
                                            <span class="<?= $r['is_public'] ? 'public-badge' : 'private-badge' ?>"><?= $r['is_public'] ? 'Public' : 'Private' ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:11.5px; color:var(--admin-text-muted); margin-top:2px;">
                                        <?= esc(strtoupper(pathinfo($r['file_path'], PATHINFO_EXTENSION))) ?> • <?= esc($formattedSize) ?> <?= !empty($r['report_date']) ? '• Date: ' . esc($r['report_date']) : '' ?>
                                    </div>
                                    <?php if (!empty($r['description'])): ?>
                                        <div style="font-size:11.5px; color:var(--admin-text-muted); font-style:italic; margin-top:2px;"><?= esc($r['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <a href="<?= esc(site_url("admin/client-results/{$res['id']}/reports/{$r['id']}/file"), 'attr') ?>" target="_blank" class="btn btn--secondary btn--sm">
                                    Preview Document
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</div>
<?= $this->endSection() ?>

<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('head') ?>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
.focus-pill-container { display:flex; flex-wrap:wrap; gap:8px; align-items:center; min-height:42px; padding:8px 12px; background:var(--admin-bg-surface); border:1px solid var(--admin-border-subtle); border-radius:8px; }
.focus-pill { display:inline-flex; align-items:center; gap:6px; background:var(--admin-bg-subdued); color:var(--admin-text-main); border:1px solid var(--admin-border-subtle); padding:4px 10px; border-radius:16px; font-size:12px; font-weight:600; letter-spacing:0.02em; }
.focus-pill__remove { background:none; border:none; color:var(--admin-text-muted); cursor:pointer; font-size:14px; line-height:1; padding:0 2px; font-weight:700; }
.focus-pill__remove:hover { color:var(--admin-danger); }
.preset-chip { display:inline-block; background:var(--admin-bg-subdued); border:1px dashed var(--admin-border-subtle); color:var(--admin-text-muted); padding:3px 8px; border-radius:12px; font-size:11px; font-weight:500; cursor:pointer; user-select:none; }
.preset-chip:hover { border-color:var(--admin-primary); color:var(--admin-primary); background:var(--admin-bg-surface); }
.ql-container.ql-snow { border-bottom-left-radius:8px; border-bottom-right-radius:8px; background:var(--admin-bg-surface); }
.ql-toolbar.ql-snow { border-top-left-radius:8px; border-top-right-radius:8px; background:var(--admin-bg-subdued); border-color:var(--admin-border-subtle); }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$progressPhotos = array_filter($media['gallery'] ?? [], fn($m) => $m['media_type'] === 'progress');
$storyPhotos    = array_filter($media['gallery'] ?? [], fn($m) => $m['media_type'] === 'gallery');
?>

<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Content / Client Results</div>
        <h1 class="page-header__title">Edit Client Result</h1>
        <p class="page-header__desc">Update Client Result record #<?= esc($result['id']) ?> (<?= esc($result['client_display_name']) ?>)</p>
    </div>
    <div class="page-header__actions" style="display:flex; gap:8px;">
        <a href="<?= esc(site_url("admin/client-results/{$result['id']}/preview"), 'attr') ?>" class="btn btn--secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Preview Proof
        </a>
        <a href="<?= esc(site_url('admin/client-results'), 'attr') ?>" class="btn btn--ghost">Back to Results</a>
    </div>
</div>

<div class="u-stack-lg" style="max-width:880px;">

    <!-- Flash Notifications -->
    <?= $this->include('admin/partials/flash') ?>

    <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}"), 'attr') ?>" enctype="multipart/form-data" class="u-stack-lg" id="client-result-form">
        <?= csrf_field() ?>

        <!-- 01 — CLIENT & PROGRAM -->
        <section class="card">
            <div class="card__header">
                <h2 class="card__title">01 — CLIENT &amp; PROGRAM</h2>
                <p class="card__desc">Public display identity and program association.</p>
            </div>
            <div class="card__body u-stack-md">
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="field">
                        <label class="field__label" for="client_display_name">Client Display Name <span style="color:var(--admin-danger);">*</span></label>
                        <input class="field__input" id="client_display_name" name="client_display_name" type="text" value="<?= esc(old('client_display_name', $result['client_display_name'])) ?>" required maxlength="100" autocomplete="off">
                        <span class="field__hint">Public display name or initial format for privacy.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="client_subtitle">Client Subtitle / Role <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="client_subtitle" name="client_subtitle" type="text" value="<?= esc(old('client_subtitle', $result['client_subtitle'])) ?>" maxlength="150">
                        <span class="field__hint">Brief context such as profession or age.</span>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:16px;">
                    <div class="field">
                        <label class="field__label" for="package_id">Program / Package Reference <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <select class="field__input" id="package_id" name="package_id">
                            <option value="">-- Custom / No Specific Package --</option>
                            <?php if (!empty($packages)): ?>
                                <?php foreach ($packages as $pkg): ?>
                                    <option value="<?= esc($pkg['id'], 'attr') ?>" <?= (string)old('package_id', (string)$result['package_id']) === (string)$pkg['id'] ? 'selected' : '' ?>>
                                        <?= esc($pkg['name']) ?> (₹<?= number_format($pkg['selling_price']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <span class="field__hint">Select an active Ftpreneur program to capture the program name snapshot.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="journey_duration">Journey Duration <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="journey_duration" name="journey_duration" type="text" value="<?= esc(old('journey_duration', $result['journey_duration'])) ?>" maxlength="50">
                        <span class="field__hint">Transformation duration.</span>
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="program_name_snapshot">Program Name Snapshot <span style="color:var(--admin-text-muted);">(Current: <?= esc($result['program_name_snapshot'] ?? 'None') ?>)</span></label>
                    <input class="field__input" id="program_name_snapshot" name="program_name_snapshot" type="text" value="<?= esc(old('program_name_snapshot', $result['program_name_snapshot'])) ?>" maxlength="150">
                    <span class="field__hint">Historical program display name. Automatically updated when selecting a package above.</span>
                </div>

            </div>
        </section>

        <!-- 02 — TRANSFORMATION FOCUS -->
        <section class="card">
            <div class="card__header">
                <h2 class="card__title">02 — TRANSFORMATION FOCUS</h2>
                <p class="card__desc">Tag pills describing key focus areas (e.g. WEIGHT LOSS, DIABETIC CONTROL, CHOLESTEROL).</p>
            </div>
            <div class="card__body u-stack-md">
                
                <div class="field">
                    <label class="field__label">Focus Areas / Outcome Labels</label>
                    <div class="focus-pill-container" id="focus-pills-container">
                        <?php 
                        $loadedFocus = !empty($focusAreas) ? array_column($focusAreas, 'label') : ['WEIGHT LOSS', 'METABOLIC HEALTH'];
                        $currentFocus = old('focus_areas', $loadedFocus);
                        if (is_array($currentFocus)): 
                            foreach ($currentFocus as $fLabel): 
                                $fLabel = trim((string)$fLabel);
                                if ($fLabel === '') continue;
                        ?>
                            <span class="focus-pill">
                                <span><?= esc(mb_strtoupper($fLabel)) ?></span>
                                <button type="button" class="focus-pill__remove" data-remove-pill>&times;</button>
                                <input type="hidden" name="focus_areas[]" value="<?= esc($fLabel, 'attr') ?>">
                            </span>
                        <?php 
                            endforeach; 
                        endif; 
                        ?>
                    </div>
                    <div style="display:flex; gap:8px; margin-top:8px;">
                        <input class="field__input" id="focus-tag-input" type="text" placeholder="Type a focus area (e.g. DIABETIC CONTROL)..." maxlength="100" style="max-width:340px;">
                        <button type="button" class="btn btn--secondary btn--sm" id="btn-add-focus-tag">+ Add Focus</button>
                    </div>
                    
                    <div style="margin-top:12px;">
                        <div style="font-size:11px; font-weight:600; color:var(--admin-text-muted); margin-bottom:6px;">QUICK ADD PRESETS:</div>
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            <span class="preset-chip" data-preset="WEIGHT LOSS">+ Weight Loss</span>
                            <span class="preset-chip" data-preset="DIABETIC CONTROL">+ Diabetic Control</span>
                            <span class="preset-chip" data-preset="CHOLESTEROL">+ Cholesterol</span>
                            <span class="preset-chip" data-preset="BODY COMPOSITION">+ Body Composition</span>
                            <span class="preset-chip" data-preset="MUSCLE MASS">+ Muscle Mass</span>
                            <span class="preset-chip" data-preset="METABOLIC HEALTH">+ Metabolic Health</span>
                            <span class="preset-chip" data-preset="HORMONAL HEALTH">+ Hormonal Health</span>
                            <span class="preset-chip" data-preset="DIGESTIVE HEALTH">+ Digestive Health</span>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- 03 — TESTIMONIAL & STORY -->
        <section class="card">
            <div class="card__header">
                <h2 class="card__title">03 — TESTIMONIAL &amp; STORY</h2>
                <p class="card__desc">Card snippet and complete story for the future bottom drawer.</p>
            </div>
            <div class="card__body u-stack-md">
                
                <div class="field">
                    <label class="field__label" for="short_testimonial">Short Card Testimonial <span style="color:var(--admin-danger);">*</span></label>
                    <textarea class="field__input" id="short_testimonial" name="short_testimonial" rows="3" required maxlength="500"><?= esc(old('short_testimonial', $result['short_testimonial'])) ?></textarea>
                    <span class="field__hint">Displayed on landing page cards. Maximum 500 characters. Plain text only.</span>
                </div>

                <div class="field">
                    <label class="field__label" for="full_story">Full Transformation Story <span style="color:var(--admin-danger);">*</span></label>
                    
                    <!-- Quill Toolbar & Container -->
                    <div id="quill-wrapper" style="margin-bottom:6px;">
                        <div id="quill-toolbar-story">
                            <span class="ql-formats">
                                <select class="ql-header">
                                    <option selected></option>
                                    <option value="2">Heading 2</option>
                                    <option value="3">Heading 3</option>
                                </select>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-bold"></button>
                                <button class="ql-italic"></button>
                                <button class="ql-underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered"></button>
                                <button class="ql-list" value="bullet"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-clean"></button>
                            </span>
                        </div>
                        <div id="quill-editor-story" style="min-height:220px; font-size:14px; line-height:1.6;">
                            <?= old('full_story', $result['full_story']) ?>
                        </div>
                    </div>

                    <!-- Hidden/Fallback Textarea for HTML payload -->
                    <textarea id="full_story" name="full_story" style="display:none;" required><?= esc(old('full_story', $result['full_story'])) ?></textarea>
                    <span class="field__hint">Detailed long-form story formatted for the proof bottom drawer. Paragraphs, headings, bold, and lists supported.</span>
                </div>

            </div>
        </section>

        <!-- 04 — FEATURED CLIENT IMAGE -->
        <section class="card">
            <div class="card__header">
                <h2 class="card__title">04 — FEATURED CLIENT IMAGE</h2>
                <p class="card__desc">Primary image used for the Client Result card and proof experience.</p>
            </div>
            <div class="card__body u-stack-md">
                
                <div class="field">
                    <label class="field__label">Featured Client Image <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    
                    <?php if (!empty($result['cover_image'])): ?>
                        <div style="display:flex; align-items:center; gap:16px; margin-bottom:12px; padding:12px; background:var(--admin-bg-subdued); border-radius:8px; border:1px solid var(--admin-border-subtle);">
                            <img src="<?= base_url(esc($result['cover_image'])) ?>" alt="" style="width:70px; height:70px; object-fit:cover; border-radius:6px; border:1px solid var(--admin-border-subtle);" />
                            <div>
                                <div style="font-weight:600; font-size:13px; color:var(--admin-text-main);">Current Featured Image</div>
                                <div style="font-size:11px; color:var(--admin-text-muted); margin-bottom:6px;"><?= esc($result['cover_image']) ?></div>
                                <label style="font-size:12px; color:var(--admin-danger); cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                                    <input type="checkbox" name="remove_cover_image" value="1" style="accent-color:var(--admin-danger);"> Remove current image
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div id="cover-upload-area" style="border:2px dashed var(--admin-border-subtle); border-radius:10px; padding:24px; text-align:center; background:var(--admin-bg-surface); cursor:pointer; transition:border-color 0.2s;">
                        <input type="file" name="cover_image" id="cover_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Featured client image">
                        <div style="display:grid; gap:8px; place-items:center;">
                            <div style="width:48px; height:48px; border-radius:12px; background:var(--admin-bg-subdued); display:grid; place-items:center; color:var(--admin-text-muted);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                            <div style="font-size:14px; font-weight:600; color:var(--admin-text-main);">Click to replace or drag &amp; drop new featured image</div>
                            <div style="font-size:12px; color:var(--admin-text-muted);">JPG, PNG, WebP up to 5 MB — secure randomized filename</div>
                            <button type="button" class="btn btn--secondary btn--sm" id="btn-select-cover">Choose Image</button>
                        </div>
                        <div id="cover-new-preview" style="margin-top:14px; display:none; text-align:center;"></div>
                    </div>

                    <div style="display:flex; gap:8px; margin-top:8px;">
                        <button type="button" class="btn btn--ghost btn--sm" id="btn-remove-cover-new" style="display:none; color:var(--admin-danger);">Clear selected image</button>
                    </div>
                </div>

            </div>
        </section>

        <!-- 05 — DISPLAY SETTINGS -->
        <section class="card">
            <div class="card__header">
                <h2 class="card__title">05 — DISPLAY SETTINGS</h2>
                <p class="card__desc">Primary feature image and publication flags.</p>
            </div>
            <div class="card__body">
                
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; align-items:end;">
                    <div class="field" style="margin:0;">
                        <label class="field__label" for="display_order">Display Order <span style="color:var(--admin-danger);">*</span></label>
                        <input class="field__input" id="display_order" name="display_order" type="number" min="0" step="1" value="<?= esc(old('display_order', $result['display_order'])) ?>" required>
                    </div>

                    <div class="field" style="margin:0;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                            <input type="checkbox" name="is_featured" value="1" <?= old('is_featured', (string)$result['is_featured']) === '1' ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                            <span style="font-weight:600; color:var(--admin-text-main);">Featured Highlight</span>
                        </label>
                    </div>

                    <div class="field" style="margin:0;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                            <input type="checkbox" name="is_active" value="1" <?= old('is_active', (string)$result['is_active']) === '1' ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                            <span style="font-weight:600; color:var(--admin-text-main);">Active (Published)</span>
                        </label>
                    </div>
                </div>

            </div>
        </section>

        <!-- Actions Footer -->
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:12px; color:var(--admin-text-muted);">
                Created: <?= esc(date('M j, Y H:i', strtotime($result['created_at']))) ?>
            </div>
            <div style="display:flex; gap:12px;">
                <a href="<?= esc(site_url('admin/client-results'), 'attr') ?>" class="btn btn--ghost">Cancel</a>
                <button type="submit" class="btn btn--primary">Update Client Result</button>
            </div>
        </div>

    </form>

    <!-- RESULTS / OUTCOMES (Phase 07C) -->
    <section class="card" id="outcomes" style="margin-top:32px;">
        <div class="card__header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 class="card__title">RESULTS / OUTCOMES</h2>
                <p class="card__desc">Add documented before-and-after measurements for this client (e.g. Weight, HbA1c, Blood Pressure).</p>
            </div>
            <button type="button" class="btn btn--primary btn--sm" data-modal-open="add-metric-modal">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="margin-right:4px;"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                + ADD RESULT
            </button>
        </div>
        <div class="card__body">
            <?php if (empty($metrics)): ?>
                <div style="text-align:center; padding: 36px 20px; background:var(--admin-bg-subdued); border-radius:8px; border:1px dashed var(--admin-border-subtle);">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--admin-text-muted)" stroke-width="1.5" style="margin-bottom:10px;">
                        <path d="M3 3v18h18M18 9l-5 5-4-4-5 5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div style="font-weight:600; font-size:14px; color:var(--admin-text-main); margin-bottom:4px;">No measurable outcomes have been added yet</div>
                    <p style="font-size:13px; color:var(--admin-text-muted); margin-bottom:16px; max-width:440px; margin-left:auto; margin-right:auto;">Document concrete before-and-after metrics like Weight (92 → 78 kg), HbA1c (8.2 → 6.4 %), or Blood Pressure.</p>
                    <button type="button" class="btn btn--primary btn--sm" data-modal-open="add-metric-modal">+ ADD RESULT</button>
                </div>
            <?php else: ?>
                <div class="u-stack-sm">
                    <?php foreach ($metrics as $m): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 16px; background:var(--admin-bg-surface); border:1px solid var(--admin-border-subtle); border-radius:8px; gap:16px; flex-wrap:wrap;">
                            <div>
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                    <span style="font-weight:700; font-size:13px; letter-spacing:0.02em; color:var(--admin-text-main); text-transform:uppercase;"><?= esc($m['metric_name']) ?></span>
                                    <?php if ($m['is_public']): ?>
                                        <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">PUBLIC</span>
                                    <?php else: ?>
                                        <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">PRIVATE</span>
                                    <?php endif; ?>
                                    <span style="font-size:11px; color:var(--admin-text-muted);">Order: <?= esc($m['display_order']) ?></span>
                                </div>
                                <div style="font-size:15px; font-weight:600; color:var(--admin-text-main); display:flex; align-items:center; gap:8px;">
                                    <span><?= esc($m['before_value']) ?> <?= esc($m['unit'] ?? '') ?></span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--admin-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                    <span style="color:var(--admin-primary);"><?= esc($m['after_value']) ?> <?= esc($m['unit'] ?? '') ?></span>
                                </div>
                                <?php if (!empty($m['context']) || !empty($m['measurement_start_date']) || !empty($m['measurement_end_date'])): ?>
                                    <div style="font-size:12px; color:var(--admin-text-muted); margin-top:4px; display:flex; gap:12px; flex-wrap:wrap;">
                                        <?php if (!empty($m['context'])): ?>
                                            <span>Context: <?= esc($m['context']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($m['measurement_start_date']) || !empty($m['measurement_end_date'])): ?>
                                            <span>Dates: <?= esc($m['measurement_start_date'] ?: 'N/A') ?> to <?= esc($m['measurement_end_date'] ?: 'N/A') ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/metrics/{$m['id']}/toggle-public"), 'attr') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm" title="Toggle Public visibility">
                                        <?= $m['is_public'] ? 'Make Private' : 'Make Public' ?>
                                    </button>
                                </form>
                                <button type="button" class="btn btn--secondary btn--sm" data-modal-open="edit-metric-modal-<?= esc($m['id'], 'attr') ?>">Edit</button>
                                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/metrics/{$m['id']}/delete"), 'attr') ?>" style="display:inline;" onsubmit="return confirm('Delete this outcome metric?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--admin-danger);">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- VISUAL PROOF (Phase 07D & 07B.1 Architecture Separation) -->
    <section class="card" id="visual-proof" style="margin-top:32px;">
        <div class="card__header">
            <h2 class="card__title">VISUAL PROOF</h2>
            <p class="card__desc">Primary Before &amp; After photos, Transformation Progress Gallery, and Client Story Gallery.</p>
        </div>
        <div class="card__body u-stack-lg">
            
            <!-- A. BEFORE & AFTER -->
            <div>
                <h3 style="font-size:14px; font-weight:700; color:var(--admin-text-main); text-transform:uppercase; letter-spacing:0.04em; margin-bottom:14px;">A. Before &amp; After Transformation</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    
                    <!-- BEFORE Slot -->
                    <div style="border:1px solid var(--admin-border-subtle); border-radius:8px; padding:16px; background:var(--admin-bg-surface);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-weight:700; font-size:13px; color:var(--admin-primary); text-transform:uppercase;">Primary Before Image</span>
                            <?php if (!empty($media['before'])): ?>
                                <?php if ($media['before']['is_public']): ?>
                                    <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">PUBLIC</span>
                                <?php else: ?>
                                    <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">PRIVATE</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($media['before'])): ?>
                            <div style="position:relative; width:100%; height:220px; border-radius:6px; overflow:hidden; border:1px solid var(--admin-border-subtle); margin-bottom:12px; background:#000;">
                                <img src="<?= base_url(esc($media['before']['file_path'])) ?>" alt="Before Transformation" style="width:100%; height:100%; object-fit:cover;" />
                            </div>
                            <?php if (!empty($media['before']['caption']) || !empty($media['before']['media_date'])): ?>
                                <div style="font-size:12px; color:var(--admin-text-muted); margin-bottom:12px;">
                                    <?php if (!empty($media['before']['caption'])): ?><div><strong>Caption:</strong> <?= esc($media['before']['caption']) ?></div><?php endif; ?>
                                    <?php if (!empty($media['before']['media_date'])): ?><div><strong>Date:</strong> <?= esc($media['before']['media_date']) ?></div><?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$media['before']['id']}/toggle-public"), 'attr') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm">
                                        <?= $media['before']['is_public'] ? 'Make Private' : 'Make Public' ?>
                                    </button>
                                </form>
                                <button type="button" class="btn btn--secondary btn--sm" data-modal-open="replace-before-modal">Replace</button>
                                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$media['before']['id']}/delete"), 'attr') ?>" style="display:inline;" onsubmit="return confirm('Remove primary Before photo?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--admin-danger);">Remove</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/before"), 'attr') ?>" enctype="multipart/form-data" class="u-stack-sm">
                                <?= csrf_field() ?>
                                <div style="border:2px dashed var(--admin-border-subtle); border-radius:6px; padding:24px 16px; text-align:center; background:var(--admin-bg-subdued);">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--admin-text-muted)" stroke-width="1.5" style="margin-bottom:8px;"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <div style="font-size:13px; font-weight:600; color:var(--admin-text-main); margin-bottom:4px;">No Before Photo Uploaded</div>
                                    <input class="field__input" type="file" name="before_image" accept="image/jpeg,image/png,image/webp" required style="font-size:12px; margin-bottom:8px;">
                                    <button type="submit" class="btn btn--primary btn--sm" style="width:100%;">Upload Before Photo</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- AFTER Slot -->
                    <div style="border:1px solid var(--admin-border-subtle); border-radius:8px; padding:16px; background:var(--admin-bg-surface);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                            <span style="font-weight:700; font-size:13px; color:var(--admin-primary); text-transform:uppercase;">Primary After Image</span>
                            <?php if (!empty($media['after'])): ?>
                                <?php if ($media['after']['is_public']): ?>
                                    <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">PUBLIC</span>
                                <?php else: ?>
                                    <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">PRIVATE</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($media['after'])): ?>
                            <div style="position:relative; width:100%; height:220px; border-radius:6px; overflow:hidden; border:1px solid var(--admin-border-subtle); margin-bottom:12px; background:#000;">
                                <img src="<?= base_url(esc($media['after']['file_path'])) ?>" alt="After Transformation" style="width:100%; height:100%; object-fit:cover;" />
                            </div>
                            <?php if (!empty($media['after']['caption']) || !empty($media['after']['media_date'])): ?>
                                <div style="font-size:12px; color:var(--admin-text-muted); margin-bottom:12px;">
                                    <?php if (!empty($media['after']['caption'])): ?><div><strong>Caption:</strong> <?= esc($media['after']['caption']) ?></div><?php endif; ?>
                                    <?php if (!empty($media['after']['media_date'])): ?><div><strong>Date:</strong> <?= esc($media['after']['media_date']) ?></div><?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$media['after']['id']}/toggle-public"), 'attr') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm">
                                        <?= $media['after']['is_public'] ? 'Make Private' : 'Make Public' ?>
                                    </button>
                                </form>
                                <button type="button" class="btn btn--secondary btn--sm" data-modal-open="replace-after-modal">Replace</button>
                                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$media['after']['id']}/delete"), 'attr') ?>" style="display:inline;" onsubmit="return confirm('Remove primary After photo?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--admin-danger);">Remove</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/after"), 'attr') ?>" enctype="multipart/form-data" class="u-stack-sm">
                                <?= csrf_field() ?>
                                <div style="border:2px dashed var(--admin-border-subtle); border-radius:6px; padding:24px 16px; text-align:center; background:var(--admin-bg-subdued);">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--admin-text-muted)" stroke-width="1.5" style="margin-bottom:8px;"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <div style="font-size:13px; font-weight:600; color:var(--admin-text-main); margin-bottom:4px;">No After Photo Uploaded</div>
                                    <input class="field__input" type="file" name="after_image" accept="image/jpeg,image/png,image/webp" required style="font-size:12px; margin-bottom:8px;">
                                    <button type="submit" class="btn btn--primary btn--sm" style="width:100%;">Upload After Photo</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- B. TRANSFORMATION / PROGRESS GALLERY -->
            <div style="border-top:1px solid var(--admin-border-subtle); padding-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                    <div>
                        <h3 style="font-size:14px; font-weight:700; color:var(--admin-text-main); text-transform:uppercase; letter-spacing:0.04em; margin:0;">B. Transformation / Progress Gallery</h3>
                        <p style="font-size:12px; color:var(--admin-text-muted); margin:2px 0 0;">Document visual progress milestones throughout the client's journey.</p>
                    </div>
                    <button type="button" class="btn btn--primary btn--sm" data-modal-open="add-progress-gallery-modal">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="margin-right:4px;"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        + Add Progress Photos
                    </button>
                </div>

                <?php if (empty($progressPhotos)): ?>
                    <div style="text-align:center; padding:28px 20px; background:var(--admin-bg-subdued); border-radius:8px; border:1px dashed var(--admin-border-subtle);">
                        <div style="font-weight:600; font-size:13px; color:var(--admin-text-main); margin-bottom:4px;">No progress photos uploaded yet</div>
                        <p style="font-size:12px; color:var(--admin-text-muted); margin-bottom:12px;">Upload intermediate milestone photos taken during the transformation.</p>
                        <button type="button" class="btn btn--primary btn--sm" data-modal-open="add-progress-gallery-modal">+ Add Progress Photos</button>
                    </div>
                <?php else: ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap:16px;">
                        <?php foreach ($progressPhotos as $gm): ?>
                            <div style="border:1px solid var(--admin-border-subtle); border-radius:8px; overflow:hidden; background:var(--admin-bg-surface); display:flex; flex-direction:column;">
                                <div style="position:relative; width:100%; height:130px; background:#000;">
                                    <img src="<?= base_url(esc($gm['file_path'])) ?>" alt="Progress Photo" style="width:100%; height:100%; object-fit:cover;" />
                                    <div style="position:absolute; top:6px; right:6px;">
                                        <?php if ($gm['is_public']): ?>
                                            <span style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">PUBLIC</span>
                                        <?php else: ?>
                                            <span style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">PRIVATE</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="padding:10px; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                                    <div style="font-size:12px; color:var(--admin-text-main); margin-bottom:8px;">
                                        <?php if (!empty($gm['caption'])): ?>
                                            <div style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= esc($gm['caption']) ?>"><?= esc($gm['caption']) ?></div>
                                        <?php endif; ?>
                                        <div style="font-size:11px; color:var(--admin-text-muted);">
                                            Order: <?= esc($gm['display_order']) ?> <?= !empty($gm['media_date']) ? '| ' . esc($gm['media_date']) : '' ?>
                                        </div>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:4px; border-top:1px solid var(--admin-border-subtle); padding-top:6px; margin-top:auto;">
                                        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$gm['id']}/toggle-public"), 'attr') ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn--ghost btn--sm" style="font-size:11px; padding:2px 6px;">
                                                <?= $gm['is_public'] ? 'Hide' : 'Show' ?>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn--secondary btn--sm" style="font-size:11px; padding:2px 6px;" data-modal-open="edit-media-modal-<?= esc($gm['id'], 'attr') ?>">Edit</button>
                                        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$gm['id']}/delete"), 'attr') ?>" style="display:inline;" onsubmit="return confirm('Delete this progress photo?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn--ghost btn--sm" style="font-size:11px; padding:2px 6px; color:var(--admin-danger);">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- C. CLIENT STORY GALLERY -->
            <div style="border-top:1px solid var(--admin-border-subtle); padding-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                    <div>
                        <h3 style="font-size:14px; font-weight:700; color:var(--admin-text-main); text-transform:uppercase; letter-spacing:0.04em; margin:0;">C. Client Story / Testimonial Gallery</h3>
                        <p style="font-size:12px; color:var(--admin-text-muted); margin:2px 0 0;">Supporting lifestyle, achievement, and testimonial story images.</p>
                    </div>
                    <button type="button" class="btn btn--primary btn--sm" data-modal-open="add-story-gallery-modal">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="margin-right:4px;"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        + Add Story Photos
                    </button>
                </div>

                <?php if (empty($storyPhotos)): ?>
                    <div style="text-align:center; padding:28px 20px; background:var(--admin-bg-subdued); border-radius:8px; border:1px dashed var(--admin-border-subtle);">
                        <div style="font-weight:600; font-size:13px; color:var(--admin-text-main); margin-bottom:4px;">No client story photos uploaded yet</div>
                        <p style="font-size:12px; color:var(--admin-text-muted); margin-bottom:12px;">Upload lifestyle or coaching session photos that enrich the client story.</p>
                        <button type="button" class="btn btn--primary btn--sm" data-modal-open="add-story-gallery-modal">+ Add Story Photos</button>
                    </div>
                <?php else: ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap:16px;">
                        <?php foreach ($storyPhotos as $gm): ?>
                            <div style="border:1px solid var(--admin-border-subtle); border-radius:8px; overflow:hidden; background:var(--admin-bg-surface); display:flex; flex-direction:column;">
                                <div style="position:relative; width:100%; height:130px; background:#000;">
                                    <img src="<?= base_url(esc($gm['file_path'])) ?>" alt="Client Story Photo" style="width:100%; height:100%; object-fit:cover;" />
                                    <div style="position:absolute; top:6px; right:6px;">
                                        <?php if ($gm['is_public']): ?>
                                            <span style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">PUBLIC</span>
                                        <?php else: ?>
                                            <span style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">PRIVATE</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="padding:10px; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
                                    <div style="font-size:12px; color:var(--admin-text-main); margin-bottom:8px;">
                                        <?php if (!empty($gm['caption'])): ?>
                                            <div style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= esc($gm['caption']) ?>"><?= esc($gm['caption']) ?></div>
                                        <?php endif; ?>
                                        <div style="font-size:11px; color:var(--admin-text-muted);">
                                            Order: <?= esc($gm['display_order']) ?> <?= !empty($gm['media_date']) ? '| ' . esc($gm['media_date']) : '' ?>
                                        </div>
                                    </div>
                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:4px; border-top:1px solid var(--admin-border-subtle); padding-top:6px; margin-top:auto;">
                                        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$gm['id']}/toggle-public"), 'attr') ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn--ghost btn--sm" style="font-size:11px; padding:2px 6px;">
                                                <?= $gm['is_public'] ? 'Hide' : 'Show' ?>
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn--secondary btn--sm" style="font-size:11px; padding:2px 6px;" data-modal-open="edit-media-modal-<?= esc($gm['id'], 'attr') ?>">Edit</button>
                                        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$gm['id']}/delete"), 'attr') ?>" style="display:inline;" onsubmit="return confirm('Delete this story photo?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn--ghost btn--sm" style="font-size:11px; padding:2px 6px; color:var(--admin-danger);">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- REPORTS / EVIDENCE (Phase 07E) -->
    <section class="card" id="reports-evidence" style="margin-top:32px;">
        <div class="card__header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 class="card__title">REPORTS / EVIDENCE</h2>
                <p class="card__desc">Attach approved supporting reports or documents for this client.</p>
            </div>
            <button type="button" class="btn btn--primary btn--sm" data-modal-open="add-report-modal">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="margin-right:4px;"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                + ADD REPORT
            </button>
        </div>
        <div class="card__body">
            <?php if (empty($reports)): ?>
                <div style="text-align:center; padding: 36px 20px; background:var(--admin-bg-subdued); border-radius:8px; border:1px dashed var(--admin-border-subtle);">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--admin-text-muted)" stroke-width="1.5" style="margin-bottom:10px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="14 2 14 8 20 8" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="16" y1="13" x2="8" y2="13" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="16" y1="17" x2="8" y2="17" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="10 9 9 9 8 9" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div style="font-weight:600; font-size:14px; color:var(--admin-text-main); margin-bottom:4px;">No reports or supporting evidence have been added yet</div>
                    <p style="font-size:13px; color:var(--admin-text-muted); margin-bottom:16px; max-width:440px; margin-left:auto; margin-right:auto;">Attach lab reports, body composition scans, or progress assessments (PDF, JPG, PNG, WebP up to 10 MB).</p>
                    <button type="button" class="btn btn--primary btn--sm" data-modal-open="add-report-modal">+ ADD REPORT</button>
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
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 16px; background:var(--admin-bg-surface); border:1px solid var(--admin-border-subtle); border-radius:8px; gap:16px; flex-wrap:wrap;">
                            <div style="display:flex; align-items:center; gap:14px;">
                                <div style="width:40px; height:40px; border-radius:8px; background:var(--admin-bg-subdued); border:1px solid var(--admin-border-subtle); display:grid; place-items:center; color:<?= $isPdf ? '#ef4444' : 'var(--admin-primary)' ?>;">
                                    <?php if ($isPdf): ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                    <?php else: ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:2px;">
                                        <span style="font-weight:700; font-size:14px; color:var(--admin-text-main);"><?= esc($r['report_title']) ?></span>
                                        <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:12px; background:var(--admin-bg-subdued); color:var(--admin-text-muted); border:1px solid var(--admin-border-subtle); text-transform:uppercase;"><?= esc($typeName) ?></span>
                                        <?php if ($r['is_public']): ?>
                                            <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">PUBLIC</span>
                                        <?php else: ?>
                                            <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">PRIVATE</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:12px; color:var(--admin-text-muted); display:flex; gap:12px; flex-wrap:wrap;">
                                        <span>Format: <strong><?= esc(strtoupper(pathinfo($r['file_path'], PATHINFO_EXTENSION))) ?></strong> (<?= esc($formattedSize) ?>)</span>
                                        <?php if (!empty($r['report_date'])): ?>
                                            <span>Date: <?= esc($r['report_date']) ?></span>
                                        <?php endif; ?>
                                        <span>Order: <?= esc($r['display_order']) ?></span>
                                    </div>
                                    <?php if (!empty($r['description'])): ?>
                                        <div style="font-size:12px; color:var(--admin-text-muted); margin-top:2px; font-style:italic;">
                                            <?= esc($r['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <a href="<?= esc(site_url("admin/client-results/{$result['id']}/reports/{$r['id']}/file"), 'attr') ?>" target="_blank" class="btn btn--secondary btn--sm">
                                    Preview
                                </a>
                                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/reports/{$r['id']}/toggle-public"), 'attr') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm">
                                        <?= $r['is_public'] ? 'Make Private' : 'Make Public' ?>
                                    </button>
                                </form>
                                <button type="button" class="btn btn--secondary btn--sm" data-modal-open="edit-report-modal-<?= esc($r['id'], 'attr') ?>">Edit</button>
                                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/reports/{$r['id']}/delete"), 'attr') ?>" style="display:inline;" onsubmit="return confirm('Delete this report and remove its file?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--ghost btn--sm" style="color:var(--admin-danger);">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</div>

<!-- Modal: Add Progress Gallery Photos -->
<div class="modal-overlay" data-modal="add-progress-gallery-modal" aria-hidden="true">
    <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="add-prog-title">
        <div class="modal__header">
            <div>
                <h3 class="modal__title" id="add-prog-title">Add Transformation / Progress Photos</h3>
                <p class="modal__desc">Select one or multiple progress photos taken across the transformation timeline.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
        </div>
        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media"), 'attr') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="media_type" value="progress">
            <div class="modal__body u-stack-md">
                <div class="field">
                    <label class="field__label">Select Photos <span style="color:var(--admin-danger);">*</span></label>
                    <input class="field__input" name="gallery_files[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required data-modal-focus>
                    <span class="field__hint">Select multiple images (JPG, PNG, WebP up to 5MB each).</span>
                </div>
                <div class="field">
                    <label class="field__label">Media Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" name="media_date" type="date">
                </div>
                <div class="field">
                    <label class="field__label">Caption / Note <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" name="caption" type="text" placeholder="e.g. Week 8 progress check-in" maxlength="300">
                </div>
                <div class="field">
                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_public" value="1" style="width:18px; height:18px; accent-color:var(--admin-primary);">
                        <span style="font-weight:600; color:var(--admin-text-main);">Public (Visible on proof drawer)</span>
                    </label>
                </div>
            </div>
            <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn--primary">Upload Progress Photos</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Client Story Gallery Photos -->
<div class="modal-overlay" data-modal="add-story-gallery-modal" aria-hidden="true">
    <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="add-story-title">
        <div class="modal__header">
            <div>
                <h3 class="modal__title" id="add-story-title">Add Client Story Photos</h3>
                <p class="modal__desc">Select one or multiple supporting lifestyle or testimonial photos.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
        </div>
        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media"), 'attr') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="media_type" value="gallery">
            <div class="modal__body u-stack-md">
                <div class="field">
                    <label class="field__label">Select Photos <span style="color:var(--admin-danger);">*</span></label>
                    <input class="field__input" name="gallery_files[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required data-modal-focus>
                    <span class="field__hint">Select multiple images (JPG, PNG, WebP up to 5MB each).</span>
                </div>
                <div class="field">
                    <label class="field__label">Media Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" name="media_date" type="date">
                </div>
                <div class="field">
                    <label class="field__label">Caption / Note <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" name="caption" type="text" placeholder="e.g. Marathon finish line celebration" maxlength="300">
                </div>
                <div class="field">
                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_public" value="1" style="width:18px; height:18px; accent-color:var(--admin-primary);">
                        <span style="font-weight:600; color:var(--admin-text-main);">Public (Visible on proof drawer)</span>
                    </label>
                </div>
            </div>
            <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn--primary">Upload Story Photos</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Replace Before Photo -->
<?php if (!empty($media['before'])): ?>
<div class="modal-overlay" data-modal="replace-before-modal" aria-hidden="true">
    <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="replace-before-title">
        <div class="modal__header">
            <div>
                <h3 class="modal__title" id="replace-before-title">Replace Primary Before Photo</h3>
                <p class="modal__desc">Upload a new image to replace the current Before photo.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
        </div>
        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/before"), 'attr') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal__body u-stack-md">
                <div class="field">
                    <label class="field__label" for="rep_before_image">New Before Image <span style="color:var(--admin-danger);">*</span></label>
                    <input class="field__input" id="rep_before_image" name="before_image" type="file" accept="image/jpeg,image/png,image/webp" required data-modal-focus>
                </div>
                <div class="field">
                    <label class="field__label" for="rep_before_caption">Caption <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" id="rep_before_caption" name="caption" type="text" value="<?= esc($media['before']['caption']) ?>" maxlength="300">
                </div>
                <div class="field">
                    <label class="field__label" for="rep_before_date">Media Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" id="rep_before_date" name="media_date" type="date" value="<?= esc($media['before']['media_date']) ?>">
                </div>
                <div class="field">
                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_public" value="1" <?= $media['before']['is_public'] ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                        <span style="font-weight:600; color:var(--admin-text-main);">Public (Visible on proof drawer)</span>
                    </label>
                </div>
            </div>
            <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn--primary">Replace Before Photo</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Replace After Photo -->
<?php if (!empty($media['after'])): ?>
<div class="modal-overlay" data-modal="replace-after-modal" aria-hidden="true">
    <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="replace-after-title">
        <div class="modal__header">
            <div>
                <h3 class="modal__title" id="replace-after-title">Replace Primary After Photo</h3>
                <p class="modal__desc">Upload a new image to replace the current After photo.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
        </div>
        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/after"), 'attr') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal__body u-stack-md">
                <div class="field">
                    <label class="field__label" for="rep_after_image">New After Image <span style="color:var(--admin-danger);">*</span></label>
                    <input class="field__input" id="rep_after_image" name="after_image" type="file" accept="image/jpeg,image/png,image/webp" required data-modal-focus>
                </div>
                <div class="field">
                    <label class="field__label" for="rep_after_caption">Caption <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" id="rep_after_caption" name="caption" type="text" value="<?= esc($media['after']['caption']) ?>" maxlength="300">
                </div>
                <div class="field">
                    <label class="field__label" for="rep_after_date">Media Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" id="rep_after_date" name="media_date" type="date" value="<?= esc($media['after']['media_date']) ?>">
                </div>
                <div class="field">
                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_public" value="1" <?= $media['after']['is_public'] ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                        <span style="font-weight:600; color:var(--admin-text-main);">Public (Visible on proof drawer)</span>
                    </label>
                </div>
            </div>
            <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn--primary">Replace After Photo</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modals: Edit Gallery Photo Metadata -->
<?php if (!empty($media['gallery'])): ?>
    <?php foreach ($media['gallery'] as $gm): ?>
        <div class="modal-overlay" data-modal="edit-media-modal-<?= esc($gm['id'], 'attr') ?>" aria-hidden="true">
            <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="edit-media-title-<?= esc($gm['id'], 'attr') ?>">
                <div class="modal__header">
                    <div>
                        <h3 class="modal__title" id="edit-media-title-<?= esc($gm['id'], 'attr') ?>">Edit Photo Details</h3>
                        <p class="modal__desc">Update metadata for photo #<?= esc($gm['id']) ?>.</p>
                    </div>
                    <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
                </div>
                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/media/{$gm['id']}"), 'attr') ?>">
                    <?= csrf_field() ?>
                    <div class="modal__body u-stack-md">
                        <div style="display:flex; gap:16px; align-items:center; margin-bottom:12px;">
                            <img src="<?= base_url(esc($gm['file_path'])) ?>" alt="" style="width:70px; height:70px; object-fit:cover; border-radius:6px; border:1px solid var(--admin-border-subtle);" />
                            <div style="font-size:12px; color:var(--admin-text-muted);">
                                <div><strong>ID:</strong> #<?= esc($gm['id']) ?></div>
                                <div><strong>Path:</strong> <?= esc($gm['file_path']) ?></div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div class="field">
                                <label class="field__label" for="edit_med_type_<?= esc($gm['id'], 'attr') ?>">Media Category</label>
                                <select class="field__input" id="edit_med_type_<?= esc($gm['id'], 'attr') ?>" name="media_type">
                                    <option value="progress" <?= $gm['media_type'] === 'progress' ? 'selected' : '' ?>>Progress Photo</option>
                                    <option value="gallery" <?= $gm['media_type'] === 'gallery' ? 'selected' : '' ?>>Story Gallery Photo</option>
                                </select>
                            </div>

                            <div class="field">
                                <label class="field__label" for="edit_med_date_<?= esc($gm['id'], 'attr') ?>">Media Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                                <input class="field__input" id="edit_med_date_<?= esc($gm['id'], 'attr') ?>" name="media_date" type="date" value="<?= esc($gm['media_date']) ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label class="field__label" for="edit_med_caption_<?= esc($gm['id'], 'attr') ?>">Caption <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                            <input class="field__input" id="edit_med_caption_<?= esc($gm['id'], 'attr') ?>" name="caption" type="text" value="<?= esc($gm['caption']) ?>" maxlength="300">
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:end;">
                            <div class="field" style="margin:0;">
                                <label class="field__label" for="edit_med_order_<?= esc($gm['id'], 'attr') ?>">Display Order</label>
                                <input class="field__input" id="edit_med_order_<?= esc($gm['id'], 'attr') ?>" name="display_order" type="number" min="0" value="<?= esc($gm['display_order']) ?>">
                            </div>

                            <div class="field" style="margin:0; padding-bottom:8px;">
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="checkbox" name="is_public" value="1" <?= $gm['is_public'] ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                                    <span style="font-weight:600; color:var(--admin-text-main);">Public (Show on proof drawer)</span>
                                </label>
                            </div>
                        </div>

                    </div>
                    <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                        <button type="submit" class="btn btn--primary">Update Photo Details</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal: Add Outcome Metric -->
<div class="modal-overlay" data-modal="add-metric-modal" aria-hidden="true">
    <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="add-metric-modal-title">
        <div class="modal__header">
            <div>
                <h3 class="modal__title" id="add-metric-modal-title">Add Measurable Outcome Metric</h3>
                <p class="modal__desc">Record a documented before-and-after metric for this client result.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
        </div>
        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/metrics"), 'attr') ?>">
            <?= csrf_field() ?>
            <div class="modal__body u-stack-md">
                
                <div class="field">
                    <label class="field__label" for="add_metric_name">Metric Name <span style="color:var(--admin-danger);">*</span></label>
                    <input class="field__input" id="add_metric_name" name="metric_name" type="text" placeholder="e.g. Weight, HbA1c, Blood Pressure, Waist" required maxlength="100" autocomplete="off" data-modal-focus>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
                    <div class="field">
                        <label class="field__label" for="add_before_value">Before Value <span style="color:var(--admin-danger);">*</span></label>
                        <input class="field__input" id="add_before_value" name="before_value" type="text" placeholder="e.g. 92, 8.2, 160/100" required maxlength="100" autocomplete="off">
                    </div>

                    <div class="field">
                        <label class="field__label" for="add_after_value">After Value <span style="color:var(--admin-danger);">*</span></label>
                        <input class="field__input" id="add_after_value" name="after_value" type="text" placeholder="e.g. 78, 6.4, 125/82" required maxlength="100" autocomplete="off">
                    </div>

                    <div class="field">
                        <label class="field__label" for="add_unit">Unit <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="add_unit" name="unit" type="text" placeholder="e.g. kg, %, mmHg, in" maxlength="50" autocomplete="off">
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="add_context">Context / Notes <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" id="add_context" name="context" type="text" placeholder="e.g. Measured after 16 weeks, DEXA scan, Morning fasting test" maxlength="255">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="field">
                        <label class="field__label" for="add_start_date">Measurement Start Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="add_start_date" name="measurement_start_date" type="date">
                    </div>

                    <div class="field">
                        <label class="field__label" for="add_end_date">Measurement End Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="add_end_date" name="measurement_end_date" type="date">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:end;">
                    <div class="field" style="margin:0;">
                        <label class="field__label" for="add_display_order">Display Order</label>
                        <input class="field__input" id="add_display_order" name="display_order" type="number" min="0" value="0">
                    </div>

                    <div class="field" style="margin:0; padding-bottom:8px;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                            <input type="checkbox" name="is_public" value="1" style="width:18px; height:18px; accent-color:var(--admin-primary);">
                            <span style="font-weight:600; color:var(--admin-text-main);">Public (Show on proof drawer)</span>
                        </label>
                    </div>
                </div>

            </div>
            <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn--primary">Save Outcome Metric</button>
            </div>
        </form>
    </div>
</div>

<!-- Modals: Edit Outcome Metrics -->
<?php if (!empty($metrics)): ?>
    <?php foreach ($metrics as $m): ?>
        <div class="modal-overlay" data-modal="edit-metric-modal-<?= esc($m['id'], 'attr') ?>" aria-hidden="true">
            <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="edit-metric-modal-title-<?= esc($m['id'], 'attr') ?>">
                <div class="modal__header">
                    <div>
                        <h3 class="modal__title" id="edit-metric-modal-title-<?= esc($m['id'], 'attr') ?>">Edit Outcome Metric</h3>
                        <p class="modal__desc">Update outcome metric #<?= esc($m['id']) ?> for this client result.</p>
                    </div>
                    <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
                </div>
                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/metrics/{$m['id']}"), 'attr') ?>">
                    <?= csrf_field() ?>
                    <div class="modal__body u-stack-md">
                        
                        <div class="field">
                            <label class="field__label" for="edit_metric_name_<?= esc($m['id'], 'attr') ?>">Metric Name <span style="color:var(--admin-danger);">*</span></label>
                            <input class="field__input" id="edit_metric_name_<?= esc($m['id'], 'attr') ?>" name="metric_name" type="text" value="<?= esc($m['metric_name']) ?>" required maxlength="100" autocomplete="off" data-modal-focus>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
                            <div class="field">
                                <label class="field__label" for="edit_before_value_<?= esc($m['id'], 'attr') ?>">Before Value <span style="color:var(--admin-danger);">*</span></label>
                                <input class="field__input" id="edit_before_value_<?= esc($m['id'], 'attr') ?>" name="before_value" type="text" value="<?= esc($m['before_value']) ?>" required maxlength="100" autocomplete="off">
                            </div>

                            <div class="field">
                                <label class="field__label" for="edit_after_value_<?= esc($m['id'], 'attr') ?>">After Value <span style="color:var(--admin-danger);">*</span></label>
                                <input class="field__input" id="edit_after_value_<?= esc($m['id'], 'attr') ?>" name="after_value" type="text" value="<?= esc($m['after_value']) ?>" required maxlength="100" autocomplete="off">
                            </div>

                            <div class="field">
                                <label class="field__label" for="edit_unit_<?= esc($m['id'], 'attr') ?>">Unit <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                                <input class="field__input" id="edit_unit_<?= esc($m['id'], 'attr') ?>" name="unit" type="text" value="<?= esc($m['unit']) ?>" maxlength="50" autocomplete="off">
                            </div>
                        </div>

                        <div class="field">
                            <label class="field__label" for="edit_context_<?= esc($m['id'], 'attr') ?>">Context / Notes <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                            <input class="field__input" id="edit_context_<?= esc($m['id'], 'attr') ?>" name="context" type="text" value="<?= esc($m['context']) ?>" maxlength="255">
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div class="field">
                                <label class="field__label" for="edit_start_date_<?= esc($m['id'], 'attr') ?>">Measurement Start Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                                <input class="field__input" id="edit_start_date_<?= esc($m['id'], 'attr') ?>" name="measurement_start_date" type="date" value="<?= esc($m['measurement_start_date']) ?>">
                            </div>

                            <div class="field">
                                <label class="field__label" for="edit_end_date_<?= esc($m['id'], 'attr') ?>">Measurement End Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                                <input class="field__input" id="edit_end_date_<?= esc($m['id'], 'attr') ?>" name="measurement_end_date" type="date" value="<?= esc($m['measurement_end_date']) ?>">
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:end;">
                            <div class="field" style="margin:0;">
                                <label class="field__label" for="edit_display_order_<?= esc($m['id'], 'attr') ?>">Display Order</label>
                                <input class="field__input" id="edit_display_order_<?= esc($m['id'], 'attr') ?>" name="display_order" type="number" min="0" value="<?= esc($m['display_order']) ?>">
                            </div>

                            <div class="field" style="margin:0; padding-bottom:8px;">
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                                    <input type="checkbox" name="is_public" value="1" <?= $m['is_public'] ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                                    <span style="font-weight:600; color:var(--admin-text-main);">Public (Show on proof drawer)</span>
                                </label>
                            </div>
                        </div>

                    </div>
                    <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                        <button type="submit" class="btn btn--primary">Update Outcome Metric</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal: Add Supporting Report -->
<div class="modal-overlay" data-modal="add-report-modal" aria-hidden="true">
    <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="add-rep-title">
        <div class="modal__header">
            <div>
                <h3 class="modal__title" id="add-rep-title">Add Supporting Report / Evidence</h3>
                <p class="modal__desc">Attach an approved lab report, assessment, or progress document.</p>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
        </div>
        <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/reports"), 'attr') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal__body u-stack-md">
                
                <div class="field">
                    <label class="field__label" for="add_rep_title_input">Report Title <span style="color:var(--admin-danger);">*</span></label>
                    <input class="field__input" id="add_rep_title_input" name="report_title" type="text" placeholder="e.g. Lipid Profile &amp; HbA1c Lab Test" required maxlength="150" autocomplete="off" data-modal-focus>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="field">
                        <label class="field__label" for="add_rep_type_input">Report Type <span style="color:var(--admin-danger);">*</span></label>
                        <select class="field__input" id="add_rep_type_input" name="report_type" required>
                            <option value="lab_report">Lab Report</option>
                            <option value="assessment_report">Assessment Report</option>
                            <option value="progress_report">Progress Report</option>
                            <option value="body_composition">Body Composition</option>
                            <option value="fitness_assessment">Fitness Assessment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="field">
                        <label class="field__label" for="add_rep_date_input">Report Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="add_rep_date_input" name="report_date" type="date">
                    </div>
                </div>

                <div class="field">
                    <label class="field__label">Report File <span style="color:var(--admin-danger);">*</span></label>
                    <div id="report-dropzone-add" style="border:2px dashed var(--admin-border-subtle); border-radius:10px; padding:20px; text-align:center; background:var(--admin-bg-subdued); cursor:pointer;">
                        <input type="file" name="report_file" id="add_report_file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Report File">
                        <div style="display:grid; gap:6px; place-items:center;">
                            <div style="width:40px; height:40px; border-radius:10px; background:var(--admin-bg-surface); display:grid; place-items:center; color:var(--admin-text-muted); border:1px solid var(--admin-border-subtle);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/></svg>
                            </div>
                            <div style="font-size:13px; font-weight:600; color:var(--admin-text-main);">Drop report here or click to choose file</div>
                            <div style="font-size:11px; color:var(--admin-text-muted);">PDF, JPG, PNG or WebP • Maximum 10 MB</div>
                            <button type="button" class="btn btn--secondary btn--sm" id="btn-choose-report-add">Choose File</button>
                        </div>
                        <div id="report-add-preview" style="margin-top:10px; display:none;"></div>
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="add_rep_desc_input">Description / Notes <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <textarea class="field__input" id="add_rep_desc_input" name="description" rows="2" maxlength="500" placeholder="e.g. Pre-program comprehensive blood work from Metropolis Labs."></textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:end;">
                    <div class="field" style="margin:0;">
                        <label class="field__label" for="add_rep_order_input">Display Order</label>
                        <input class="field__input" id="add_rep_order_input" name="display_order" type="number" min="0" value="0">
                    </div>

                    <div class="field" style="margin:0; padding-bottom:8px;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                            <input type="checkbox" name="is_public" value="1" style="width:18px; height:18px; accent-color:var(--admin-primary);">
                            <span style="font-weight:600; color:var(--admin-text-main);">Public (Approved for proof drawer)</span>
                        </label>
                    </div>
                </div>

            </div>
            <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn--primary">Save Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Modals: Edit Reports -->
<?php if (!empty($reports)): ?>
    <?php foreach ($reports as $r): ?>
        <div class="modal-overlay" data-modal="edit-report-modal-<?= esc($r['id'], 'attr') ?>" aria-hidden="true">
            <div class="modal modal--lg" role="dialog" aria-modal="true" aria-labelledby="edit-rep-title-<?= esc($r['id'], 'attr') ?>">
                <div class="modal__header">
                    <div>
                        <h3 class="modal__title" id="edit-rep-title-<?= esc($r['id'], 'attr') ?>">Edit Report Details</h3>
                        <p class="modal__desc">Update metadata or replace report file for report #<?= esc($r['id']) ?>.</p>
                    </div>
                    <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Close modal">&times;</button>
                </div>
                <form method="post" action="<?= esc(site_url("admin/client-results/{$result['id']}/reports/{$r['id']}"), 'attr') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="modal__body u-stack-md">
                        
                        <div class="field">
                            <label class="field__label" for="edit_rep_title_<?= esc($r['id'], 'attr') ?>">Report Title <span style="color:var(--admin-danger);">*</span></label>
                            <input class="field__input" id="edit_rep_title_<?= esc($r['id'], 'attr') ?>" name="report_title" type="text" value="<?= esc($r['report_title']) ?>" required maxlength="150" autocomplete="off" data-modal-focus>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div class="field">
                                <label class="field__label" for="edit_rep_type_<?= esc($r['id'], 'attr') ?>">Report Type <span style="color:var(--admin-danger);">*</span></label>
                                <select class="field__input" id="edit_rep_type_<?= esc($r['id'], 'attr') ?>" name="report_type" required>
                                    <option value="lab_report" <?= $r['report_type'] === 'lab_report' ? 'selected' : '' ?>>Lab Report</option>
                                    <option value="assessment_report" <?= $r['report_type'] === 'assessment_report' ? 'selected' : '' ?>>Assessment Report</option>
                                    <option value="progress_report" <?= $r['report_type'] === 'progress_report' ? 'selected' : '' ?>>Progress Report</option>
                                    <option value="body_composition" <?= $r['report_type'] === 'body_composition' ? 'selected' : '' ?>>Body Composition</option>
                                    <option value="fitness_assessment" <?= $r['report_type'] === 'fitness_assessment' ? 'selected' : '' ?>>Fitness Assessment</option>
                                    <option value="other" <?= $r['report_type'] === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="field">
                                <label class="field__label" for="edit_rep_date_<?= esc($r['id'], 'attr') ?>">Report Date <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                                <input class="field__input" id="edit_rep_date_<?= esc($r['id'], 'attr') ?>" name="report_date" type="date" value="<?= esc($r['report_date']) ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label class="field__label">Replace Report File <span style="color:var(--admin-text-muted);">(Optional — Leave empty to keep current file)</span></label>
                            <input class="field__input" name="report_file" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                            <span class="field__hint">Current file: <?= esc(basename($r['file_path'])) ?> (<?= esc($r['file_mime']) ?>)</span>
                        </div>

                        <div class="field">
                            <label class="field__label" for="edit_rep_desc_<?= esc($r['id'], 'attr') ?>">Description / Notes <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                            <textarea class="field__input" id="edit_rep_desc_<?= esc($r['id'], 'attr') ?>" name="description" rows="2" maxlength="500"><?= esc($r['description']) ?></textarea>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:end;">
                            <div class="field" style="margin:0;">
                                <label class="field__label" for="edit_rep_order_<?= esc($r['id'], 'attr') ?>">Display Order</label>
                                <input class="field__input" id="edit_rep_order_<?= esc($r['id'], 'attr') ?>" name="display_order" type="number" min="0" value="<?= esc($r['display_order']) ?>">
                            </div>

                            <div class="field" style="margin:0; padding-bottom:8px;">
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                                    <input type="checkbox" name="is_public" value="1" <?= $r['is_public'] ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                                    <span style="font-weight:600; color:var(--admin-text-main);">Public (Approved for proof drawer)</span>
                                </label>
                            </div>
                        </div>

                    </div>
                    <div class="modal__footer" style="padding:16px 20px; border-top:1px solid var(--admin-border-subtle); display:flex; justify-content:flex-end; gap:12px;">
                        <button type="button" class="btn btn--ghost" data-modal-close>Cancel</button>
                        <button type="submit" class="btn btn--primary">Update Report</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
(function() {
    // 1. Focus Area Tag/Pill Builder
    const container = document.getElementById('focus-pills-container');
    const input = document.getElementById('focus-tag-input');
    const addBtn = document.getElementById('btn-add-focus-tag');

    function addFocusTag(text) {
        text = text.trim();
        if (!text) return;
        text = text.toUpperCase();

        const existing = Array.from(container.querySelectorAll('input[name="focus_areas[]"]')).map(i => i.value.toUpperCase());
        if (existing.includes(text)) {
            if (input) input.value = '';
            return;
        }

        const pill = document.createElement('span');
        pill.className = 'focus-pill';
        pill.innerHTML = `<span>${text}</span><button type="button" class="focus-pill__remove" data-remove-pill>&times;</button><input type="hidden" name="focus_areas[]" value="${text}">`;
        container.appendChild(pill);
        if (input) input.value = '';
    }

    if (addBtn && input) {
        addBtn.addEventListener('click', () => addFocusTag(input.value));
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addFocusTag(input.value);
            }
        });
    }

    if (container) {
        container.addEventListener('click', (e) => {
            if (e.target.closest('[data-remove-pill]')) {
                e.target.closest('.focus-pill').remove();
            }
        });
    }

    document.querySelectorAll('[data-preset]').forEach(chip => {
        chip.addEventListener('click', () => addFocusTag(chip.getAttribute('data-preset')));
    });

    // 2. Quill Rich Text Editor
    const textarea = document.getElementById('full_story');
    const editorEl = document.getElementById('quill-editor-story');
    if (editorEl && textarea && window.Quill) {
        try {
            const quill = new Quill('#quill-editor-story', {
                theme: 'snow',
                modules: { toolbar: '#quill-toolbar-story' }
            });
            if (textarea.value) {
                quill.root.innerHTML = textarea.value;
            }
            quill.on('text-change', () => {
                textarea.value = quill.root.innerHTML;
            });
            const form = document.getElementById('client-result-form');
            if (form) {
                form.addEventListener('submit', () => {
                    textarea.value = quill.root.innerHTML;
                });
            }
        } catch(e) {
            textarea.style.display = 'block';
        }
    }

    // 3. Featured Image Custom Dropzone Uploader
    const coverInput = document.getElementById('cover_image');
    const coverArea = document.getElementById('cover-upload-area');
    const coverNewPreview = document.getElementById('cover-new-preview');
    const btnSelectCover = document.getElementById('btn-select-cover');
    const btnRemoveCoverNew = document.getElementById('btn-remove-cover-new');

    if (coverArea && coverInput) {
        const openPicker = () => coverInput.click();
        coverArea.addEventListener('click', (e) => {
            if (e.target.closest('button') || e.target.closest('input')) return;
            openPicker();
        });
        if (btnSelectCover) btnSelectCover.addEventListener('click', (e) => { e.stopPropagation(); openPicker(); });

        coverArea.addEventListener('dragover', (e) => { e.preventDefault(); coverArea.style.borderColor = 'var(--admin-primary)'; });
        coverArea.addEventListener('dragleave', () => { coverArea.style.borderColor = ''; });
        coverArea.addEventListener('drop', (e) => {
            e.preventDefault();
            coverArea.style.borderColor = '';
            if (e.dataTransfer.files.length) {
                coverInput.files = e.dataTransfer.files;
                updateCoverPreview();
            }
        });

        coverInput.addEventListener('change', updateCoverPreview);

        function updateCoverPreview() {
            if (!coverInput.files.length) {
                if (coverNewPreview) coverNewPreview.style.display = 'none';
                if (btnRemoveCoverNew) btnRemoveCoverNew.style.display = 'none';
                return;
            }
            const file = coverInput.files[0];
            if (coverNewPreview) {
                const url = URL.createObjectURL(file);
                coverNewPreview.innerHTML = `
                    <div style="display:inline-flex; align-items:center; gap:12px; padding:10px 14px; background:var(--admin-bg-subdued); border:1px solid var(--admin-border-subtle); border-radius:8px;">
                        <img src="${url}" alt="Preview" style="width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid var(--admin-border-subtle);" />
                        <div style="text-align:left;">
                            <div style="font-weight:600; font-size:13px; color:var(--admin-text-main);">${file.name}</div>
                            <div style="font-size:11px; color:var(--admin-text-muted);">${(file.size/1024).toFixed(1)} KB</div>
                        </div>
                    </div>
                `;
                coverNewPreview.style.display = 'block';
            }
            if (btnRemoveCoverNew) btnRemoveCoverNew.style.display = 'inline-flex';
        }

        if (btnRemoveCoverNew) {
            btnRemoveCoverNew.addEventListener('click', () => {
                coverInput.value = '';
                if (coverNewPreview) coverNewPreview.style.display = 'none';
                btnRemoveCoverNew.style.display = 'none';
            });
        }
    }

    // 4. Report Custom Dropzone Uploader
    const repDropzone = document.getElementById('report-dropzone-add');
    const repInput = document.getElementById('add_report_file');
    const repPreview = document.getElementById('report-add-preview');
    const btnChooseRep = document.getElementById('btn-choose-report-add');

    if (repDropzone && repInput) {
        const openRepPicker = () => repInput.click();
        repDropzone.addEventListener('click', (e) => {
            if (e.target.closest('button') || e.target.closest('input')) return;
            openRepPicker();
        });
        if (btnChooseRep) btnChooseRep.addEventListener('click', (e) => { e.stopPropagation(); openRepPicker(); });

        repDropzone.addEventListener('dragover', (e) => { e.preventDefault(); repDropzone.style.borderColor = 'var(--admin-primary)'; });
        repDropzone.addEventListener('dragleave', () => { repDropzone.style.borderColor = ''; });
        repDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            repDropzone.style.borderColor = '';
            if (e.dataTransfer.files.length) {
                repInput.files = e.dataTransfer.files;
                updateRepPreview();
            }
        });

        repInput.addEventListener('change', updateRepPreview);

        function updateRepPreview() {
            if (!repInput.files.length) {
                if (repPreview) repPreview.style.display = 'none';
                return;
            }
            const file = repInput.files[0];
            const isPdf = file.type === 'application/pdf' || file.name.endsWith('.pdf');
            const sizeStr = file.size >= 1048576 ? (file.size/1048576).toFixed(1) + ' MB' : (file.size/1024).toFixed(1) + ' KB';
            if (repPreview) {
                repPreview.innerHTML = `
                    <div style="display:inline-flex; align-items:center; gap:12px; padding:10px 14px; background:var(--admin-bg-surface); border:1px solid var(--admin-border-subtle); border-radius:8px; text-align:left;">
                        <div style="width:36px; height:36px; border-radius:6px; background:var(--admin-bg-subdued); display:grid; place-items:center; color:${isPdf ? '#ef4444' : 'var(--admin-primary)'}; font-weight:700; font-size:11px;">
                            ${isPdf ? 'PDF' : 'IMG'}
                        </div>
                        <div>
                            <div style="font-weight:600; font-size:13px; color:var(--admin-text-main);">${file.name}</div>
                            <div style="font-size:11px; color:var(--admin-text-muted);">${file.type || 'Document'} • ${sizeStr}</div>
                        </div>
                    </div>
                `;
                repPreview.style.display = 'block';
            }
        }
    }
})();
</script>

<?= $this->endSection() ?>

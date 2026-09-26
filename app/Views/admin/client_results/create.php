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
<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Content / Client Results</div>
        <h1 class="page-header__title">Create Client Result</h1>
        <p class="page-header__desc">Add a new client transformation story, focus areas, and testimonial record.</p>
    </div>
    <div class="page-header__actions">
        <a href="<?= esc(site_url('admin/client-results'), 'attr') ?>" class="btn btn--ghost">Back to Results</a>
    </div>
</div>

<div class="u-stack-lg" style="max-width:880px;">

    <!-- Flash Notifications -->
    <?= $this->include('admin/partials/flash') ?>

    <form method="post" action="<?= esc(site_url('admin/client-results'), 'attr') ?>" enctype="multipart/form-data" class="u-stack-lg" id="client-result-form">
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
                        <input class="field__input" id="client_display_name" name="client_display_name" type="text" placeholder="e.g. Rahul S." value="<?= esc(old('client_display_name')) ?>" required maxlength="100" autocomplete="off">
                        <span class="field__hint">Public display name or initial format for privacy.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="client_subtitle">Client Subtitle / Role <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="client_subtitle" name="client_subtitle" type="text" placeholder="e.g. Business Owner, 42" value="<?= esc(old('client_subtitle')) ?>" maxlength="150">
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
                                    <option value="<?= esc($pkg['id'], 'attr') ?>" <?= (string)old('package_id') === (string)$pkg['id'] ? 'selected' : '' ?>>
                                        <?= esc($pkg['name']) ?> (₹<?= number_format($pkg['selling_price']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <span class="field__hint">Select an active Ftpreneur program to capture the program name snapshot.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="journey_duration">Journey Duration <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="journey_duration" name="journey_duration" type="text" placeholder="e.g. 16 Weeks" value="<?= esc(old('journey_duration', '16 Weeks')) ?>" maxlength="50">
                        <span class="field__hint">Transformation duration.</span>
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="program_name_snapshot">Custom Program Name Override <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                    <input class="field__input" id="program_name_snapshot" name="program_name_snapshot" type="text" placeholder="e.g. Custom Executive Coaching" value="<?= esc(old('program_name_snapshot')) ?>" maxlength="150">
                    <span class="field__hint">Used only if no standard package is selected above.</span>
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
                        $oldFocus = old('focus_areas', ['WEIGHT LOSS', 'METABOLIC HEALTH']);
                        if (is_array($oldFocus)): 
                            foreach ($oldFocus as $fLabel): 
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
                    <textarea class="field__input" id="short_testimonial" name="short_testimonial" rows="3" placeholder="Concise high-impact testimonial quote for landing page card..." required maxlength="500"><?= esc(old('short_testimonial')) ?></textarea>
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
                            <?= old('full_story') ?>
                        </div>
                    </div>

                    <!-- Hidden/Fallback Textarea for HTML payload -->
                    <textarea id="full_story" name="full_story" style="display:none;" required><?= esc(old('full_story')) ?></textarea>
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
                    
                    <div id="cover-upload-area" style="border:2px dashed var(--admin-border-subtle); border-radius:10px; padding:24px; text-align:center; background:var(--admin-bg-surface); cursor:pointer; transition:border-color 0.2s;">
                        <input type="file" name="cover_image" id="cover_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Featured client image">
                        <div style="display:grid; gap:8px; place-items:center;">
                            <div style="width:48px; height:48px; border-radius:12px; background:var(--admin-bg-subdued); display:grid; place-items:center; color:var(--admin-text-muted);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                            <div style="font-size:14px; font-weight:600; color:var(--admin-text-main);">Click to upload or drag &amp; drop featured image</div>
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
                <p class="card__desc">Publication flags and ordering position.</p>
            </div>
            <div class="card__body">
                
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; align-items:end;">
                    <div class="field" style="margin:0;">
                        <label class="field__label" for="display_order">Display Order <span style="color:var(--admin-danger);">*</span></label>
                        <input class="field__input" id="display_order" name="display_order" type="number" min="0" step="1" value="<?= esc(old('display_order', 100)) ?>" required>
                    </div>

                    <div class="field" style="margin:0;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                            <input type="checkbox" name="is_featured" value="1" <?= old('is_featured') === '1' ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                            <span style="font-weight:600; color:var(--admin-text-main);">Featured Highlight</span>
                        </label>
                    </div>

                    <div class="field" style="margin:0;">
                        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none;">
                            <input type="checkbox" name="is_active" value="1" <?= old('is_active', '1') === '1' ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                            <span style="font-weight:600; color:var(--admin-text-main);">Active (Published)</span>
                        </label>
                    </div>
                </div>

            </div>
        </section>

        <!-- Actions Footer -->
        <div style="display:flex; justify-content:flex-end; gap:12px;">
            <a href="<?= esc(site_url('admin/client-results'), 'attr') ?>" class="btn btn--ghost">Cancel</a>
            <button type="submit" class="btn btn--primary">Create Client Result</button>
        </div>

    </form>

</div>

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

        // Prevent duplicates
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
})();
</script>
<?= $this->endSection() ?>

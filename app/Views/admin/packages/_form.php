<?= csrf_field() ?><?php
// Shared package form partial — Phase 5D (extends 5C)
// Expected vars: $mode, $package, $featuresList, $gallery (array), $formAction, $submitLabel, $errors
$errors = $errors ?? [];
if (isset($validation) && $validation instanceof \CodeIgniter\Validation\Validation) {
    $errors = array_merge($errors, $validation->getErrors());
}
$sessionErrors = session()->getFlashdata('errors');
if (is_array($sessionErrors) && !empty($sessionErrors)) {
    $errors = array_merge($errors, $sessionErrors);
}
$hasError = function($field) use ($errors) {
    return isset($errors[$field]) && $errors[$field] !== '';
};
$pkg = $package ?? [];
$gallery = $gallery ?? [];
$getValue = function($field, $default = '') use ($pkg) {
    $old = old($field);
    if ($old !== null) {
        // 5E.2C: rich-text validation redisplay — sanitize unsanitized old() before Quill receives it (fail-closed)
        if ($field === 'full_description') {
            $raw = old($field, null, false);
            try {
                $san = \App\Services\HtmlSanitizerService::sanitize((string)$raw);
                return $san ?? '';
            } catch (\Throwable $e) {
                // Fail closed on redisplay — never return raw, log without body (service already logged)
                log_message('error', 'Package form redisplay sanitization failed: ' . get_class($e) . ' code ' . $e->getCode());
                return '';
            }
        }
        return $old;
    }
    if (isset($pkg[$field])) return (string)$pkg[$field];
    return $default;
};
$oldFeatures = old('features');
if ($oldFeatures === null) {
    $oldFeatures = $features ?? $featuresList ?? [];
}
if (!is_array($oldFeatures)) $oldFeatures = [];

$oldOptions = old('options');
if ($oldOptions === null) {
    $oldOptions = $options ?? [];
}
if (!is_array($oldOptions)) $oldOptions = [];

$featuredImage = $pkg['featured_image'] ?? null;
$hasFeaturedError = $hasError('featured_image');
$hasGalleryError = $hasError('gallery_images');
?>

<div class="pkg-create__layout">
    <!-- MAIN COLUMN -->
    <div class="pkg-create__main">

        <!-- Basic Information -->
        <section class="card">
            <div class="card__header">
                <div>
                    <div class="card__title">Basic Information</div>
                    <div class="card__subtitle">Core identity for the package</div>
                </div>
            </div>
            <div class="card__body" style="display:grid; gap:16px;">
                <div class="field">
                    <label class="field__label" for="name">Package Name <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                    <input class="field__input <?= $hasError('name') ? 'field__input--error' : '' ?>" id="name" name="name" type="text" value="<?= esc($getValue('name', '')) ?>" maxlength="150" required aria-required="true" aria-invalid="<?= $hasError('name') ? 'true' : 'false' ?>" aria-describedby="<?= $hasError('name') ? 'err-name' : '' ?>">
                    <?php if ($hasError('name')): ?><div id="err-name" class="field__error"><?= esc($errors['name']) ?></div><?php endif; ?>
                </div>

                <div class="field">
                    <label class="field__label" for="slug">Slug <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                    <input class="field__input <?= $hasError('slug') ? 'field__input--error' : '' ?>" id="slug" name="slug" type="text" value="<?= esc($getValue('slug', '')) ?>" maxlength="190" required aria-required="true" aria-invalid="<?= $hasError('slug') ? 'true' : 'false' ?>" aria-describedby="hint-slug <?= $hasError('slug') ? 'err-slug' : '' ?>">
                    <div id="hint-slug" class="field__hint">URL-safe, lowercase letters, numbers and hyphens. Auto-suggested from name.</div>
                    <?php if ($hasError('slug')): ?><div id="err-slug" class="field__error"><?= esc($errors['slug']) ?></div><?php endif; ?>
                </div>

                <div class="field">
                    <label class="field__label" for="short_description">Short Description</label>
                    <textarea class="field__input <?= $hasError('short_description') ? 'field__input--error' : '' ?>" id="short_description" name="short_description" rows="2" maxlength="300" aria-describedby="<?= $hasError('short_description') ? 'err-short_description' : '' ?>"><?= esc($getValue('short_description', '')) ?></textarea>
                    <div class="field__hint">Max 300 characters. Shown in package card and listing.</div>
                    <?php if ($hasError('short_description')): ?><div id="err-short_description" class="field__error"><?= esc($errors['short_description']) ?></div><?php endif; ?>
                </div>

                <div class="field">
                    <label class="field__label" for="full_description">Full Description</label>
                    <div id="quill-wrapper" style="display:none; border:1px solid var(--color-border-input); border-radius:var(--radius-control); overflow:hidden;" class="<?= $hasError('full_description') ? 'field__input--error' : '' ?>">
                        <div id="quill-toolbar" style="border-bottom:1px solid var(--color-divider);">
                            <span class="ql-formats">
                                <button class="ql-bold" title="Bold"></button>
                                <button class="ql-italic" title="Italic"></button>
                                <button class="ql-underline" title="Underline"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-header" value="2" title="Heading 2"></button>
                                <button class="ql-header" value="3" title="Heading 3"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-list" value="ordered" title="Numbered list"></button>
                                <button class="ql-list" value="bullet" title="Bulleted list"></button>
                                <button class="ql-blockquote" title="Blockquote"></button>
                            </span>
                            <span class="ql-formats">
                                <button class="ql-link" title="Link"></button>
                                <button class="ql-clean" title="Clear formatting"></button>
                            </span>
                        </div>
                        <div id="quill-editor" style="min-height:160px; background:#fff;"></div>
                    </div>
                    <textarea id="full_description" name="full_description" class="field__input <?= $hasError('full_description') ? 'field__input--error' : '' ?>" rows="8" style="min-height:160px; resize:vertical;"><?= esc($getValue('full_description', '')) ?></textarea>
                    <noscript><div class="field__hint" style="margin-top:6px;">JavaScript is disabled — plain textarea is active. Content sanitized server-side.</div></noscript>
                    <div class="field__hint">Rich text: bold, italic, headings, lists, blockquote, links. Sanitized server-side.</div>
                    <?php if ($hasError('full_description')): ?><div id="err-full_description" class="field__error"><?= esc($errors['full_description']) ?></div><?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Pricing & Duration (Base/Default) -->
        <section class="card">
            <div class="card__header">
                <div>
                    <div class="card__title">Base Pricing &amp; Duration</div>
                    <div class="card__subtitle">Default/starting amount and program length for backward compatibility</div>
                </div>
            </div>
            <div class="card__body" style="display:grid; gap:16px;">
                <div class="form__row form__row--2">
                    <div class="field">
                        <label class="field__label" for="regular_price">Regular Price (₹)</label>
                        <input class="field__input <?= $hasError('regular_price') ? 'field__input--error' : '' ?>" id="regular_price" name="regular_price" type="number" step="0.01" min="0" inputmode="decimal" value="<?= esc($getValue('regular_price', '')) ?>" aria-invalid="<?= $hasError('regular_price') ? 'true' : 'false' ?>">
                        <?php if ($hasError('regular_price')): ?><div class="field__error"><?= esc($errors['regular_price']) ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="field__label" for="selling_price">Selling Price (₹)</label>
                        <input class="field__input <?= $hasError('selling_price') ? 'field__input--error' : '' ?>" id="selling_price" name="selling_price" type="number" step="0.01" min="0" inputmode="decimal" value="<?= esc($getValue('selling_price', '')) ?>" aria-invalid="<?= $hasError('selling_price') ? 'true' : 'false' ?>">
                        <div class="field__hint">Must be ≤ regular price. Auto-derives from options if left blank.</div>
                        <?php if ($hasError('selling_price')): ?><div class="field__error"><?= esc($errors['selling_price']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form__row form__row--2">
                    <div class="field">
                        <label class="field__label" for="duration_value">Duration Value</label>
                        <input class="field__input <?= $hasError('duration_value') ? 'field__input--error' : '' ?>" id="duration_value" name="duration_value" type="number" min="1" step="1" value="<?= esc($getValue('duration_value', '')) ?>">
                        <?php if ($hasError('duration_value')): ?><div class="field__error"><?= esc($errors['duration_value']) ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="field__label" for="duration_unit">Duration Unit</label>
                        <select class="field__input <?= $hasError('duration_unit') ? 'field__input--error' : '' ?>" id="duration_unit" name="duration_unit">
                            <option value="">Select unit</option>
                            <option value="days" <?= $getValue('duration_unit', '')==='days' ? 'selected' : '' ?>>Days</option>
                            <option value="weeks" <?= $getValue('duration_unit', '')==='weeks' ? 'selected' : '' ?>>Weeks</option>
                            <option value="months" <?= $getValue('duration_unit', '')==='months' ? 'selected' : '' ?>>Months</option>
                        </select>
                        <?php if ($hasError('duration_unit')): ?><div class="field__error"><?= esc($errors['duration_unit']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- DURATION & PRICING OPTIONS -->
        <section class="card" id="pkg-options-section">
            <div class="card__header">
                <div>
                    <div class="card__title">Duration &amp; Pricing Options</div>
                    <div class="card__subtitle">Dynamic duration tiers, pricing, and option-specific inclusions</div>
                </div>
                <button type="button" class="btn btn--secondary btn--sm" id="btn-add-option">+ Add Option</button>
            </div>
            <div class="card__body">
                <?php if ($hasError('options')): ?>
                    <div class="field__error" style="margin-bottom:12px;">Please fix highlighted errors in options below.</div>
                <?php endif; ?>

                <div id="options-container" style="display:grid; gap:16px;">
                    <div id="options-empty-state" class="u-muted" style="text-align:center; padding:24px 12px; border:1.5px dashed var(--color-border); border-radius:8px; font-size:0.875rem; display:<?= empty($oldOptions) ? 'block' : 'none' ?>;">
                        No duration options configured yet. Click <strong>+ Add Option</strong> to create custom duration tiers (e.g. 3 Months, 6 Months).
                    </div>

                    <?php foreach ($oldOptions as $optIdx => $opt): ?>
                        <?php
                        $optName = $opt['name'] ?? '';
                        $optVal = $opt['duration_value'] ?? '3';
                        $optUnit = strtolower($opt['duration_unit'] ?? 'month');
                        $optPrice = $opt['price'] ?? '';
                        $optDesc = $opt['short_description'] ?? '';
                        $optActive = isset($opt['is_active']) ? ($opt['is_active'] == 1 || $opt['is_active'] === '1' || $opt['is_active'] === 'on') : true;
                        $optFeatures = $opt['features'] ?? $opt['inclusions'] ?? [];
                        if (!is_array($optFeatures)) $optFeatures = [];
                        $optErr = $errors['options'][$optIdx] ?? [];
                        ?>
                        <div class="card option-card" data-option-item style="border:1px solid var(--color-border); border-radius:8px; overflow:hidden;">
                            <div class="option-card__header" data-toggle-option style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:var(--color-surface-subtle); cursor:pointer; user-select:none;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <span class="option-chevron" style="display:inline-flex; align-items:center; transition:transform 0.2s;">
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                    <div>
                                        <strong class="option-title-display"><?= esc($optName !== '' ? $optName : 'New Duration Option') ?></strong>
                                        <span class="badge option-active-badge <?= $optActive ? 'badge--success' : 'badge--neutral' ?>" style="margin-left:8px;"><?= $optActive ? 'Active' : 'Inactive' ?></span>
                                        <div class="field__hint option-subtitle-display" style="margin:2px 0 0 0;"><?= esc($optVal) ?> <?= esc(ucfirst($optUnit)) ?><?= $optPrice !== '' ? ' — ₹' . esc(number_format((float)$optPrice, 0)) : '' ?></div>
                                    </div>
                                </div>
                                <div style="display:flex; gap:6px; align-items:center;" onclick="event.stopPropagation();">
                                    <button type="button" class="icon-btn icon-btn--sm" data-move-option-up aria-label="Move option up" title="Move up">
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 14L6 10l1.4-1.4L10 11.2l2.6-2.6L14 10l-4 4Z" fill="currentColor"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn icon-btn--sm" data-move-option-down aria-label="Move option down" title="Move down">
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6l4 4-1.4 1.4L10 8.8 7.4 11.4 6 10l4-4Z" fill="currentColor"/></svg>
                                    </button>
                                    <button type="button" class="btn btn--ghost btn--sm" data-remove-option style="color:var(--color-danger);">Remove</button>
                                </div>
                            </div>
                            <div class="option-card__body" style="padding:16px; display:grid; gap:16px; border-top:1px solid var(--color-border-subtle);">
                                <input type="hidden" name="options[<?= $optIdx ?>][id]" value="<?= esc((string)($opt['id'] ?? '')) ?>">
                                <div class="form__row form__row--2">
                                    <div class="field">
                                        <label class="field__label">Option Name <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                                        <input class="field__input option-name-input <?= isset($optErr['name']) ? 'field__input--error' : '' ?>" name="options[<?= $optIdx ?>][name]" type="text" value="<?= esc($optName) ?>" placeholder="e.g. 3 Month Program" required>
                                        <?php if (isset($optErr['name'])): ?><div class="field__error"><?= esc($optErr['name']) ?></div><?php endif; ?>
                                    </div>
                                    <div class="field">
                                        <label class="field__label">Price (₹) <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                                        <input class="field__input option-price-input <?= isset($optErr['price']) ? 'field__input--error' : '' ?>" name="options[<?= $optIdx ?>][price]" type="number" step="0.01" min="0" value="<?= esc($optPrice) ?>" placeholder="50000" required>
                                        <?php if (isset($optErr['price'])): ?><div class="field__error"><?= esc($optErr['price']) ?></div><?php endif; ?>
                                    </div>
                                </div>

                                <div class="form__row form__row--2">
                                    <div class="field">
                                        <label class="field__label">Duration Value <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                                        <input class="field__input option-dur-val-input <?= isset($optErr['duration_value']) ? 'field__input--error' : '' ?>" name="options[<?= $optIdx ?>][duration_value]" type="number" min="1" step="1" value="<?= esc((string)$optVal) ?>" placeholder="3" required>
                                        <?php if (isset($optErr['duration_value'])): ?><div class="field__error"><?= esc($optErr['duration_value']) ?></div><?php endif; ?>
                                    </div>
                                    <div class="field">
                                        <label class="field__label">Duration Unit <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                                        <select class="field__input option-dur-unit-input <?= isset($optErr['duration_unit']) ? 'field__input--error' : '' ?>" name="options[<?= $optIdx ?>][duration_unit]" required>
                                            <option value="day" <?= in_array($optUnit, ['day','days']) ? 'selected' : '' ?>>Days</option>
                                            <option value="week" <?= in_array($optUnit, ['week','weeks']) ? 'selected' : '' ?>>Weeks</option>
                                            <option value="month" <?= in_array($optUnit, ['month','months']) ? 'selected' : '' ?>>Months</option>
                                            <option value="year" <?= in_array($optUnit, ['year','years']) ? 'selected' : '' ?>>Years</option>
                                        </select>
                                        <?php if (isset($optErr['duration_unit'])): ?><div class="field__error"><?= esc($optErr['duration_unit']) ?></div><?php endif; ?>
                                    </div>
                                </div>

                                <div class="field">
                                    <label class="field__label">Short Description</label>
                                    <textarea class="field__input" name="options[<?= $optIdx ?>][short_description]" rows="2" placeholder="Brief overview for this option..."><?= esc($optDesc) ?></textarea>
                                </div>

                                <div class="field">
                                    <label style="display:flex; gap:8px; align-items:center; cursor:pointer;">
                                        <input type="checkbox" class="option-active-input" name="options[<?= $optIdx ?>][is_active]" value="1" <?= $optActive ? 'checked' : '' ?> style="width:16px;height:16px;">
                                        <span class="field__label" style="margin:0;">Active (Enabled for selection)</span>
                                    </label>
                                </div>

                                <!-- Inclusions -->
                                <div style="border:1px solid var(--color-border-subtle); border-radius:6px; padding:12px; background:var(--color-surface-subtle);">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                        <label class="field__label" style="margin:0; font-weight:600;">What's Included <span class="u-muted" style="font-weight:normal;">(Option features)</span></label>
                                        <button type="button" class="btn btn--secondary btn--sm" data-add-option-inclusion>+ Add Inclusion</button>
                                    </div>
                                    <div class="option-inclusions-list" style="display:grid; gap:8px;">
                                        <?php foreach ($optFeatures as $fIdx => $fText): ?>
                                            <div class="option-inclusion-row" style="display:flex; gap:8px; align-items:center;">
                                                <input class="field__input" name="options[<?= $optIdx ?>][features][]" type="text" value="<?= esc($fText) ?>" maxlength="300" placeholder="e.g. 2 video calls with Visphy Kharradi">
                                                <button type="button" class="btn btn--ghost btn--sm" data-remove-option-inclusion style="color:var(--color-danger);">Remove</button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Package Features -->
        <section class="card">
            <div class="card__header">
                <div>
                    <div class="card__title">Package Features</div>
                    <div class="card__subtitle">Ordered list — stored as package_features rows, max 50</div>
                </div>
                <button type="button" class="btn btn--secondary btn--sm" id="btn-add-feature">Add Feature</button>
            </div>
            <div class="card__body">
                <?php if ($hasError('features')): ?><div class="field__error" style="margin-bottom:12px;"><?= esc($errors['features']) ?></div><?php endif; ?>
                <div id="feature-list" class="feature-list" aria-label="Package features">
                    <?php foreach ($oldFeatures as $idx => $feat): ?>
                        <?php $featError = $errors['features_details'][$idx] ?? null; ?>
                        <div class="feature-row" data-feature-row draggable="true">
                            <div class="feature-row__input">
                                <span class="u-muted" aria-hidden="true" style="cursor:grab; user-select:none; padding:0 4px;" title="Drag to reorder">&#x283F;</span>
                                <input class="field__input <?= $featError ? 'field__input--error' : '' ?>" name="features[]" type="text" value="<?= esc($feat) ?>" maxlength="300" placeholder="Feature text" aria-label="Feature <?= esc((string)($idx+1)) ?>">
                            </div>
                            <div class="feature-row__actions">
                                <button type="button" class="icon-btn icon-btn--sm" data-move-up aria-label="Move up" title="Move up">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 14L6 10l1.4-1.4L10 11.2l2.6-2.6L14 10l-4 4Z" fill="currentColor"/><path d="M10 5l-4 4 1.4 1.4L10 7.8l2.6 2.6L14 9l-4-4Z" fill="currentColor" opacity="0.5"/></svg>
                                </button>
                                <button type="button" class="icon-btn icon-btn--sm" data-move-down aria-label="Move down" title="Move down">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6l4 4-1.4 1.4L10 8.8 7.4 11.4 6 10l4-4Z" fill="currentColor" opacity="0.5"/><path d="M10 15l-4-4 1.4-1.4L10 12.2l2.6-2.6L14 11l-4 4Z" fill="currentColor"/></svg>
                                </button>
                                <button type="button" class="btn btn--ghost btn--sm" data-remove-feature aria-label="Remove feature">Remove</button>
                            </div>
                            <?php if ($featError): ?><div class="field__error" style="grid-column:1/-1;"><?= esc($featError) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="field__hint" style="margin-top:10px;">Plain text, trimmed, max 300 chars. Display order is derived from sequence.</div>
                <template id="feature-row-template">
                    <div class="feature-row" data-feature-row draggable="true">
                        <div class="feature-row__input">
                            <span class="u-muted" aria-hidden="true" style="cursor:grab; user-select:none; padding:0 4px;" title="Drag to reorder">&#x283F;</span>
                            <input class="field__input" name="features[]" type="text" value="" maxlength="300" placeholder="Feature text" aria-label="Feature">
                        </div>
                        <div class="feature-row__actions">
                            <button type="button" class="icon-btn icon-btn--sm" data-move-up aria-label="Move up" title="Move up">
                                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 14L6 10l1.4-1.4L10 11.2l2.6-2.6L14 10l-4 4Z" fill="currentColor"/><path d="M10 5l-4 4 1.4 1.4L10 7.8l2.6 2.6L14 9l-4-4Z" fill="currentColor" opacity="0.5"/></svg>
                            </button>
                            <button type="button" class="icon-btn icon-btn--sm" data-move-down aria-label="Move down" title="Move down">
                                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6l4 4-1.4 1.4L10 8.8 7.4 11.4 6 10l4-4Z" fill="currentColor" opacity="0.5"/><path d="M10 15l-4-4 1.4-1.4L10 12.2l2.6-2.6L14 11l-4 4Z" fill="currentColor"/></svg>
                            </button>
                            <button type="button" class="btn btn--ghost btn--sm" data-remove-feature aria-label="Remove feature">Remove</button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Media — Featured + Gallery -->
        <section class="card">
            <div class="card__header">
                <div>
                    <div class="card__title">Media</div>
                    <div class="card__subtitle">Featured image (1) and gallery (up to 10) — JPEG/PNG/WEBP, 5 MB max, sanitized filenames</div>
                </div>
            </div>
            <div class="card__body" style="display:grid; gap:20px;">
                <!-- Featured Image -->
                <div class="field">
                    <label class="field__label">Featured Image</label>
                    <div class="field__hint" style="margin-bottom:8px;">Recommended 1200×800, JPEG/PNG/WEBP, max 5 MB. SVG not allowed.</div>
                    <?php if ($hasFeaturedError): ?><div class="field__error" style="margin-bottom:8px;"><?= esc($errors['featured_image']) ?></div><?php endif; ?>
                    <?php if (!empty($featuredImage)): ?>
                        <div id="featured-preview" style="display:grid; gap:10px; margin-bottom:12px; border:1px solid var(--color-border-subtle); border-radius:8px; padding:12px; background:var(--color-surface-subtle);">
                            <div style="display:flex; gap:12px; align-items:center;">
                                <img src="<?= esc('/' . ltrim($featuredImage, '/'), 'attr') ?>" alt="Featured" style="width:120px; height:80px; object-fit:cover; border-radius:8px; border:1px solid var(--color-border);">
                                <div style="display:grid; gap:4px;">
                                    <span class="small" style="word-break:break-all;"><?= esc($featuredImage) ?></span>
                                    <label style="display:flex; gap:8px; align-items:center; cursor:pointer; font-size:0.8125rem;"><input type="checkbox" name="remove_featured_image" value="1" id="remove_featured_image" style="width:16px;height:16px;"> Remove image</label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div id="featured-upload-area" style="border:1.5px dashed var(--color-border); border-radius:10px; padding:18px; text-align:center; background:#fff; cursor:pointer; transition:border-color 0.2s;">
                        <input type="file" name="featured_image" id="featured_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Featured image">
                        <div style="display:grid; gap:8px; place-items:center;">
                            <div style="width:44px; height:44px; border-radius:10px; background:var(--color-surface-muted); display:grid; place-items:center; color:var(--color-text-muted);">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M3 12l4-4 3 3 4-5 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg>
                            </div>
                            <div style="font-size:0.8125rem; font-weight:600;">Click to upload or drag &amp; drop</div>
                            <div class="field__hint">JPEG, PNG, WEBP up to 5 MB — safe random filename stored</div>
                            <button type="button" class="btn btn--secondary btn--sm" id="btn-select-featured">Select Image</button>
                        </div>
                        <div id="featured-new-preview" style="margin-top:12px; display:none; text-align:center;"></div>
                        <div class="field__hint" style="margin-top:8px;">Upload area — accessible file input remains</div>
                    </div>
                    <div style="display:flex; gap:8px; margin-top:8px;">
                        <button type="button" class="btn btn--ghost btn--sm" id="btn-remove-featured-new" style="display:none;">Clear selection</button>
                    </div>
                </div>

                <!-- Gallery -->
                <div class="field">
                    <label class="field__label">Gallery Images <span class="u-muted" style="font-weight:450;">(max 10)</span></label>
                    <div class="field__hint" style="margin-bottom:8px;">Multiple JPEG/PNG/WEBP, 5 MB each. Drag or use Move buttons. Alt text optional.</div>
                    <?php if ($hasGalleryError): ?><div class="field__error" style="margin-bottom:8px;"><?= esc($errors['gallery_images']) ?></div><?php endif; ?>
                    <div id="gallery-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap:12px;">
                        <?php foreach ($gallery as $g): ?>
                            <div class="card" data-gallery-item data-gallery-id="<?= esc((string)$g['id'], 'attr') ?>" draggable="true" style="padding:8px; display:grid; gap:8px;">
                                <img src="<?= esc('/' . ltrim($g['image_path'], '/'), 'attr') ?>" alt="<?= esc($g['alt_text'] ?? '', 'attr') ?>" style="width:100%; height:110px; object-fit:cover; border-radius:8px; border:1px solid var(--color-border);">
                                <input type="hidden" name="existing_gallery_ids[]" value="<?= esc((string)$g['id']) ?>">
                                <input type="text" name="existing_gallery_alt[]" value="<?= esc($g['alt_text'] ?? '') ?>" maxlength="300" placeholder="Alt text (optional)" class="field__input" style="height:32px; font-size:0.75rem;">
                                <div style="display:flex; gap:6px;">
                                    <button type="button" class="icon-btn icon-btn--sm" data-gallery-left aria-label="Move left"><svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 5l-5 5 5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                    <button type="button" class="icon-btn icon-btn--sm" data-gallery-right aria-label="Move right"><svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                    <button type="button" class="btn btn--ghost btn--sm" data-gallery-remove style="margin-left:auto;">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="gallery-new-preview" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap:12px; margin-top:12px;"></div>
                    <div style="margin-top:12px; border:1.5px dashed var(--color-border); border-radius:10px; padding:16px; text-align:center; background:#fff; cursor:pointer;" id="gallery-upload-area">
                        <input type="file" name="gallery_images[]" id="gallery_images" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Gallery images">
                        <div style="display:grid; gap:6px; place-items:center;">
                            <div style="font-size:0.8125rem; font-weight:600;">Add gallery images</div>
                            <div class="field__hint">Select multiple — up to 10 total, 5 MB each</div>
                            <button type="button" class="btn btn--secondary btn--sm" id="btn-select-gallery">Select Images</button>
                        </div>
                    </div>
                    <div class="field__hint" style="margin-top:6px;">Order is saved as displayed. Use drag or Move buttons.</div>
                </div>
            </div>
        </section>

        <!-- Onboarding -->
        <section class="card">
            <div class="card__header">
                <div>
                    <div class="card__title">Onboarding</div>
                    <div class="card__subtitle">Post-purchase flow helpers</div>
                </div>
            </div>
            <div class="card__body" style="display:grid; gap:16px;">
                <div class="field">
                    <label class="field__label" for="cta_label">CTA Label</label>
                    <input class="field__input <?= $hasError('cta_label') ? 'field__input--error' : '' ?>" id="cta_label" name="cta_label" type="text" value="<?= esc($getValue('cta_label', 'Get Started')) ?>" maxlength="50">
                    <?php if ($hasError('cta_label')): ?><div class="field__error"><?= esc($errors['cta_label']) ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="google_form_url">Google Form URL</label>
                    <input class="field__input <?= $hasError('google_form_url') ? 'field__input--error' : '' ?>" id="google_form_url" name="google_form_url" type="url" value="<?= esc($getValue('google_form_url', '')) ?>" maxlength="2048" placeholder="https://docs.google.com/forms/d/... or https://forms.gle/..." aria-describedby="hint-google <?= $hasError('google_form_url') ? 'err-google' : '' ?>">
                    <div id="hint-google" class="field__hint">Only HTTPS on docs.google.com or forms.gle.</div>
                    <?php if ($hasError('google_form_url')): ?><div id="err-google" class="field__error"><?= esc($errors['google_form_url']) ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="whatsapp_template">WhatsApp Message Template</label>
                    <textarea class="field__input <?= $hasError('whatsapp_template') ? 'field__input--error' : '' ?>" id="whatsapp_template" name="whatsapp_template" rows="3" maxlength="1000" placeholder="Hi {customer_name}, your package {package_name}..." aria-describedby="hint-wa <?= $hasError('whatsapp_template') ? 'err-wa' : '' ?>"><?= esc($getValue('whatsapp_template', '')) ?></textarea>
                    <div id="hint-wa" class="field__hint">Plain text only. Allowed placeholders: <code>{customer_name}</code> <code>{package_name}</code> <code>{order_number}</code></div>
                    <?php if ($hasError('whatsapp_template')): ?><div id="err-wa" class="field__error"><?= esc($errors['whatsapp_template']) ?></div><?php endif; ?>
                </div>
            </div>
        </section>

    </div>

    <!-- SETTINGS COLUMN -->
    <div class="pkg-create__side">

        <!-- Publishing -->
        <section class="card">
            <div class="card__header">
                <div class="card__title">Publishing</div>
            </div>
            <div class="card__body" style="display:grid; gap:14px;">
                <?php
                $isActiveOld = old('is_active');
                if ($isActiveOld !== null) {
                    $isActiveChecked = $isActiveOld === '1' || $isActiveOld === 'on' || $isActiveOld == 1;
                } else {
                    $isActiveChecked = isset($pkg['is_active']) ? (int)$pkg['is_active'] === 1 : true;
                }
                $isFeaturedOld = old('is_featured');
                if ($isFeaturedOld !== null) {
                    $isFeaturedChecked = $isFeaturedOld === '1' || $isFeaturedOld === 'on' || $isFeaturedOld == 1;
                } else {
                    $isFeaturedChecked = isset($pkg['is_featured']) ? (int)$pkg['is_featured'] === 1 : false;
                }
                ?>
                <label class="field" style="display:flex; gap:10px; align-items:center; margin:0; cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?= $isActiveChecked ? 'checked' : '' ?> style="width:18px;height:18px;">
                    <span>
                        <span class="field__label" style="margin:0;">Active</span>
                        <span class="field__hint" style="margin:0;">Visible on site when active</span>
                    </span>
                </label>
                <label class="field" style="display:flex; gap:10px; align-items:center; margin:0; cursor:pointer;">
                    <input type="checkbox" name="is_featured" value="1" <?= $isFeaturedChecked ? 'checked' : '' ?> style="width:18px;height:18px;">
                    <span>
                        <span class="field__label" style="margin:0;">Featured</span>
                        <span class="field__hint" style="margin:0;">Highlight on listing</span>
                    </span>
                </label>
            </div>
        </section>

        <!-- Badge -->
        <section class="card">
            <div class="card__header">
                <div class="card__title">Badge</div>
            </div>
            <div class="card__body" style="display:grid; gap:14px;">
                <div class="field">
                    <label class="field__label" for="badge">Badge</label>
                    <select class="field__input <?= $hasError('badge') ? 'field__input--error' : '' ?>" id="badge" name="badge">
                        <option value="">— No badge</option>
                        <option value="none" <?= $getValue('badge','')==='none' ? 'selected' : '' ?>>None</option>
                        <option value="popular" <?= $getValue('badge','')==='popular' ? 'selected' : '' ?>>Popular</option>
                        <option value="recommended" <?= $getValue('badge','')==='recommended' ? 'selected' : '' ?>>Recommended</option>
                        <option value="best_value" <?= $getValue('badge','')==='best_value' ? 'selected' : '' ?>>Best Value</option>
                    </select>
                    <?php if ($hasError('badge')): ?><div class="field__error"><?= esc($errors['badge']) ?></div><?php endif; ?>
                    <div class="field__hint">Max 50 chars. Custom badges are escaped.</div>
                </div>
            </div>
        </section>

        <!-- Display Order -->
        <section class="card">
            <div class="card__header">
                <div class="card__title">Display Order</div>
            </div>
            <div class="card__body" style="display:grid; gap:14px;">
                <div class="field">
                    <label class="field__label" for="display_order">Order</label>
                    <input class="field__input <?= $hasError('display_order') ? 'field__input--error' : '' ?>" id="display_order" name="display_order" type="number" min="0" max="100000" step="1" value="<?= esc($getValue('display_order', '100')) ?>">
                    <?php if ($hasError('display_order')): ?><div class="field__error"><?= esc($errors['display_order']) ?></div><?php endif; ?>
                    <div class="field__hint">Lower numbers appear first.</div>
                </div>
            </div>
        </section>

    </div>
</div>

<!-- Bottom actions -->
<div class="u-flex" style="justify-content:flex-end; gap:12px; margin-top:24px;">
    <a href="<?= esc(site_url('admin/packages'), 'attr') ?>" class="btn btn--ghost">Cancel</a>
    <button type="submit" class="btn btn--primary"><?= esc($submitLabel ?? 'Save Changes') ?></button>
</div>

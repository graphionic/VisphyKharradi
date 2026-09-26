<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Content / FAQs</div>
        <h1 class="page-header__title">Create FAQ</h1>
        <p class="page-header__desc">Add a new dynamic question and answer to the landing page FAQ section.</p>
    </div>
    <div class="page-header__actions">
        <a href="<?= esc(site_url('admin/faqs'), 'attr') ?>" class="btn btn--ghost">Back to FAQs</a>
    </div>
</div>

<div class="u-stack-lg" style="max-width:820px;">

    <!-- Flash Notifications -->
    <?= $this->include('admin/partials/flash') ?>

    <section class="card">
        <div class="card__header">
            <h2 class="card__title">FAQ Details</h2>
            <p class="card__desc">Fields marked with an asterisk (*) are required.</p>
        </div>
        <div class="card__body">
            <form method="post" action="<?= esc(site_url('admin/faqs'), 'attr') ?>" class="u-stack-md">
                <?= csrf_field() ?>

                <div class="field">
                    <label class="field__label" for="question">Question <span style="color:var(--admin-danger);">*</span></label>
                    <input class="field__input" id="question" name="question" type="text" placeholder="e.g. Who is Ftpreneur for?" value="<?= esc(old('question')) ?>" required maxlength="500" autocomplete="off">
                    <span class="field__hint">Keep questions concise, clear, and direct.</span>
                </div>

                <div class="field">
                    <label class="field__label" for="answer">Answer <span style="color:var(--admin-danger);">*</span></label>
                    <textarea class="field__input" id="answer" name="answer" rows="5" placeholder="Provide a detailed, responsible answer..." required><?= esc(old('answer')) ?></textarea>
                    <span class="field__hint">Responsible explanation. Avoid absolute medical claims or guaranteed weight loss promises.</span>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div class="field">
                        <label class="field__label" for="category">Category <span style="color:var(--admin-text-muted);">(Optional)</span></label>
                        <input class="field__input" id="category" name="category" type="text" placeholder="e.g. General, Nutrition, Training" value="<?= esc(old('category', 'General')) ?>" maxlength="100">
                        <span class="field__hint">Used for admin organization.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="display_order">Display Order <span style="color:var(--admin-danger);">*</span></label>
                        <input class="field__input" id="display_order" name="display_order" type="number" min="0" step="1" value="<?= esc(old('display_order', 100)) ?>" required>
                        <span class="field__hint">Lower numbers appear first on the landing page.</span>
                    </div>
                </div>

                <div class="field" style="margin-top:8px;">
                    <label style="display:inline-flex; align-items:center; gap:10px; cursor:pointer; user-select:none;">
                        <input type="checkbox" name="is_active" value="1" <?= old('is_active', '1') === '1' ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--admin-primary);">
                        <span style="font-weight:600; color:var(--admin-text-main);">Active (Render on Landing Page)</span>
                    </label>
                    <span class="field__hint" style="display:block; margin-top:4px;">When active, this FAQ will be visible to public site visitors.</span>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px; padding-top:16px; border-top:1px solid var(--admin-border-subtle);">
                    <a href="<?= esc(site_url('admin/faqs'), 'attr') ?>" class="btn btn--ghost">Cancel</a>
                    <button type="submit" class="btn btn--primary">Create FAQ</button>
                </div>
            </form>
        </div>
    </section>

</div>
<?= $this->endSection() ?>

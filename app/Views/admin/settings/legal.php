<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<div class="legal-settings-page">
    <?= $this->include('admin/partials/page_header', [
        'title'       => $title,
        'description' => $description,
        'eyebrow'     => 'SYSTEM / SETTINGS',
        'breadcrumbs' => [
            ['label' => 'Admin', 'url' => site_url('admin')],
            ['label' => 'Settings', 'url' => '#'],
            ['label' => 'Legal Pages'],
        ],
    ]) ?>

    <?= $this->include('admin/partials/flash') ?>

    <!-- Navigation Tabs for Privacy, Terms, Refund -->
    <div class="legal-tabs" role="tablist" aria-label="Legal Page Selection">
        <?php foreach ($pages as $type => $config): ?>
            <?php $isActive = ($activeTab === $type); ?>
            <a href="<?= site_url('admin/settings/legal?tab=' . $type) ?>"
               class="legal-tab <?= $isActive ? 'legal-tab--active' : '' ?>"
               role="tab"
               aria-selected="<?= $isActive ? 'true' : 'false' ?>">
                <span class="legal-tab__badge"><?= esc(strtoupper($type)) ?></span>
                <span class="legal-tab__label"><?= esc($config['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Active Tab Form Container -->
    <?php $currentConfig = $pages[$activeTab]; ?>
    <div class="card legal-card">
        <div class="card__header">
            <div>
                <h3 class="card__title"><?= esc($currentConfig['label']) ?></h3>
                <p class="card__subtitle">Edit page title and legal content for public display.</p>
            </div>
        </div>

        <form method="post" action="<?= site_url('admin/settings/legal') ?>" class="card__body" id="legalForm">
            <?= csrf_field() ?>
            <input type="hidden" name="page_type" value="<?= esc($activeTab) ?>">

            <!-- Page Title Field -->
            <div class="field">
                <label for="page_title" class="field__label">Page Title <span class="field__required">*</span></label>
                <input type="text"
                       id="page_title"
                       name="title"
                       class="input"
                       value="<?= esc($titles[$activeTab]) ?>"
                       maxlength="255"
                       required
                       placeholder="e.g. Privacy Policy" />
                <div class="field__hint">Displayed in public header banner and browser title tag.</div>
            </div>

            <!-- Rich Text Content Editor -->
            <div class="field" style="margin-top: 24px;">
                <label for="legal_content" class="field__label">Rich Content <span class="field__required">*</span></label>
                
                <!-- Quill Rich Text Editor Container -->
                <div id="quill-toolbar-legal" class="quill-toolbar">
                    <span class="ql-formats">
                        <select class="ql-header">
                            <option value="2">Heading 2</option>
                            <option value="3">Heading 3</option>
                            <option selected>Normal</option>
                        </select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-bold" type="button" aria-label="Bold"></button>
                        <button class="ql-italic" type="button" aria-label="Italic"></button>
                        <button class="ql-underline" type="button" aria-label="Underline"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="ordered" type="button" aria-label="Ordered List"></button>
                        <button class="ql-list" value="bullet" type="button" aria-label="Bullet List"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-link" type="button" aria-label="Insert Link"></button>
                        <button class="ql-blockquote" type="button" aria-label="Blockquote"></button>
                        <button class="ql-clean" type="button" aria-label="Clear Formatting"></button>
                    </span>
                </div>

                <div id="quill-editor-legal" class="quill-editor" style="min-height: 360px;">
                    <?= $contents[$activeTab] ?>
                </div>

                <textarea id="legal_content" name="content" class="input" style="display:none;"><?= esc($contents[$activeTab]) ?></textarea>
                <noscript>
                    <div class="field__hint" style="margin-top:6px;">JavaScript disabled — plain textarea active. Content sanitized server-side via HTMLPurifier.</div>
                </noscript>
                <div class="field__hint" style="margin-top:8px;">Supports headings, bold, italic, lists, and links. Automatically sanitized with fail-closed security.</div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions" style="margin-top: 32px; display: flex; align-items: center; justify-content: flex-end; gap: 16px;">
                <button type="submit" class="btn btn--primary btn--md">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M4 4h10l2 2v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M6 4v4h7V4M6 16v-5h7v5" stroke="currentColor" stroke-width="1.4"/>
                    </svg>
                    Save <?= esc($currentConfig['label']) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.legal-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--border-color, #E2E8F0);
    padding-bottom: 1px;
}
.legal-tab {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    border-radius: 10px 10px 0 0;
    font-family: var(--f-display, sans-serif);
    font-size: 14px;
    font-weight: 600;
    color: var(--text-muted, #64748B);
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 0.25s ease;
}
.legal-tab:hover {
    color: var(--text-color, #0F172A);
    background-color: rgba(241, 245, 249, 0.6);
}
.legal-tab--active {
    color: var(--primary-color, #2E47FF);
    background-color: #FFFFFF;
    border-color: var(--border-color, #E2E8F0);
    border-bottom-color: #FFFFFF;
    margin-bottom: -1px;
}
.legal-tab__badge {
    font-family: var(--f-mono, monospace);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    padding: 2px 6px;
    border-radius: 4px;
    background: rgba(226, 232, 240, 0.8);
    color: var(--text-muted, #475569);
}
.legal-tab--active .legal-tab__badge {
    background: rgba(46, 71, 255, 0.1);
    color: #2E47FF;
}
.quill-toolbar {
    border-radius: 8px 8px 0 0;
    border-color: var(--border-color, #CBD5E1) !important;
}
.quill-editor {
    border-radius: 0 0 8px 8px;
    border-color: var(--border-color, #CBD5E1) !important;
    background: #FFFFFF;
}
</style>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editorEl = document.getElementById('quill-editor-legal');
    const textarea = document.getElementById('legal_content');
    const form     = document.getElementById('legalForm');

    if (editorEl && textarea && window.Quill) {
        const quill = new Quill('#quill-editor-legal', {
            theme: 'snow',
            modules: { toolbar: '#quill-toolbar-legal' }
        });

        // Sync Quill HTML content into hidden textarea on submit
        if (form) {
            form.addEventListener('submit', function () {
                textarea.value = quill.root.innerHTML;
            });
        }
    }
});
</script>
<?= $this->endSection() ?>

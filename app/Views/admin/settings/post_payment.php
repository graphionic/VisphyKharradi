<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<div class="post-payment-settings-page">
    <?= $this->include('admin/partials/page_header', [
        'title'       => $title,
        'description' => $description,
        'eyebrow'     => 'SYSTEM / SETTINGS',
        'breadcrumbs' => [
            ['label' => 'Admin', 'url' => site_url('admin')],
            ['label' => 'Settings', 'url' => '#'],
            ['label' => 'Post-Payment'],
        ],
    ]) ?>

    <?= $this->include('admin/partials/flash') ?>

    <div class="card">
        <div class="card__header">
            <div>
                <h3 class="card__title">Onboarding &amp; WhatsApp Destinations</h3>
                <p class="card__subtitle">Configure dynamic destinations shown inside the checkout drawer after payment is confirmed.</p>
            </div>
        </div>

        <form method="post" action="<?= site_url('admin/settings/post-payment') ?>" class="card__body">
            <?= csrf_field() ?>

            <!-- Google Form URL -->
            <div class="field">
                <label for="google_form_url" class="field__label">Post-Payment Google Form URL</label>
                <input type="url"
                       id="google_form_url"
                       name="google_form_url"
                       class="input"
                       value="<?= esc($googleFormUrl) ?>"
                       placeholder="https://docs.google.com/forms/d/e/.../viewform" />
                <div class="field__hint">The onboarding questionnaire link opened by clients when clicking "Complete Your Details →".</div>
            </div>

            <!-- WhatsApp Number -->
            <div class="field" style="margin-top: 24px;">
                <label for="whatsapp_number" class="field__label">WhatsApp Support Number</label>
                <input type="text"
                       id="whatsapp_number"
                       name="whatsapp_number"
                       class="input"
                       value="<?= esc($whatsappNumber) ?>"
                       placeholder="e.g. 919876543210 or 447817946465" />
                <div class="field__hint">Enter full WhatsApp number including country code (e.g., 919876543210 or 447817946465). Any +, spaces or hyphens will be stripped automatically.</div>
            </div>

            <!-- Default WhatsApp Message -->
            <div class="field" style="margin-top: 24px;">
                <label for="whatsapp_message" class="field__label">Default WhatsApp Message Template</label>
                <textarea id="whatsapp_message"
                          name="whatsapp_message"
                          class="input"
                          rows="4"
                          style="min-height: 110px; resize: vertical;"><?= esc($whatsappMessage) ?></textarea>
                <div class="field__hint">
                    Supported dynamic placeholders: 
                    <code>{order_number}</code>, <code>{program_name}</code>, <code>{customer_name}</code>. 
                    Sensitive payment keys are never included.
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions" style="margin-top: 32px; display: flex; align-items: center; justify-content: flex-end; gap: 16px;">
                <button type="submit" class="btn btn--primary btn--md">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M4 4h10l2 2v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M6 4v4h7V4M6 16v-5h7v5" stroke="currentColor" stroke-width="1.4"/>
                    </svg>
                    Save Post-Payment Settings
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

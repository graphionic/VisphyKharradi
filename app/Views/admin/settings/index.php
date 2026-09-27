<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<div class="settings-page">
    <?= $this->include('admin/partials/page_header', [
        'title'       => $title,
        'description' => $description,
        'eyebrow'     => 'SYSTEM / SETTINGS',
        'breadcrumbs' => [
            ['label' => 'Admin', 'url' => site_url('admin')],
            ['label' => 'Settings', 'url' => site_url('admin/settings')],
            ['label' => esc($tabs[$activeTab] ?? 'Settings')],
        ],
    ]) ?>

    <?= $this->include('admin/partials/flash') ?>

    <!-- Central Settings Navigation Tabs -->
    <div class="settings-tabs" role="tablist" style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color, #E2E8F0); padding-bottom: 1px; flex-wrap: wrap;">
        <a href="<?= site_url('admin/settings?tab=general') ?>"
           class="settings-tab <?= $activeTab === 'general' ? 'settings-tab--active' : '' ?>">
            <span class="settings-tab__label">General</span>
        </a>
        <a href="<?= site_url('admin/settings?tab=payment') ?>"
           class="settings-tab <?= $activeTab === 'payment' ? 'settings-tab--active' : '' ?>">
            <span class="settings-tab__label">Payment &amp; Integrations</span>
        </a>
        <a href="<?= site_url('admin/settings?tab=whatsapp') ?>"
           class="settings-tab <?= $activeTab === 'whatsapp' ? 'settings-tab--active' : '' ?>">
            <span class="settings-tab__label">WhatsApp</span>
        </a>
        <a href="<?= site_url('admin/settings?tab=seo') ?>"
           class="settings-tab <?= $activeTab === 'seo' ? 'settings-tab--active' : '' ?>">
            <span class="settings-tab__label">SEO Settings</span>
        </a>
        <a href="<?= site_url('admin/settings/legal') ?>"
           class="settings-tab">
            <span class="settings-tab__label">Legal Pages</span>
            <span class="badge badge--neutral" style="font-size: 9px; margin-left: 4px;">Policy Editor</span>
        </a>
    </div>

    <!-- Active Tab Settings Card -->
    <div class="card">
        <div class="card__header">
            <div>
                <h3 class="card__title"><?= esc($tabs[$activeTab] ?? 'Settings') ?></h3>
                <p class="card__subtitle">
                    <?php if ($activeTab === 'general'): ?>
                        Manage business branding, company logo, favicon, public contact info, address, and operating hours.
                    <?php elseif ($activeTab === 'payment'): ?>
                        Configure Razorpay payment gateway credentials (Test/Live) and post-payment onboarding destinations.
                    <?php elseif ($activeTab === 'whatsapp'): ?>
                        Configure WhatsApp API / Meta Cloud API credentials for future automated messaging.
                    <?php elseif ($activeTab === 'seo'): ?>
                        Configure search engine meta tags, Open Graph cards, and Twitter/X social sharing previews.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <form method="post" action="<?= site_url('admin/settings') ?>" enctype="multipart/form-data" class="card__body">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="<?= esc($activeTab) ?>">

            <!-- TAB 1: GENERAL SETTINGS -->
            <?php if ($activeTab === 'general'): ?>

                <!-- SECTION 1: BRAND & ASSETS -->
                <div style="border: 1px solid var(--color-border, #E2E8F0); border-radius: 12px; padding: 24px; margin-bottom: 32px; background: #FAFAFA;">
                    <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">Brand Identity &amp; Assets</h4>

                    <div class="field">
                        <label for="site_name" class="field__label">Site / Business Name <span aria-hidden="true" style="color:var(--color-danger, #EF4444)">*</span></label>
                        <input type="text" id="site_name" name="site_name" class="field__input" value="<?= esc($siteName) ?>" required maxlength="150" placeholder="e.g. Ftpreneur — Visphy Kharradi">
                        <div class="field__hint">Appears in site header, default title tags, and system communications.</div>
                    </div>

                    <!-- Logo & Favicon Media Cards Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-top: 20px;">
                        
                        <!-- Logo Media Control Dropzone -->
                        <div class="field">
                            <label class="field__label">Website Logo</label>
                            <div class="field__hint" style="margin-bottom:8px;">Transparent PNG/SVG or WEBP recommended, max 2 MB.</div>
                            
                            <?php if (!empty($logo) && file_exists(FCPATH . $logo)): ?>
                                <div id="logo-preview" style="display:flex; gap:12px; align-items:center; margin-bottom:12px; border:1px solid var(--color-border-subtle, #E2E8F0); border-radius:8px; padding:12px; background:var(--color-surface-subtle, #F8FAFC);">
                                    <img src="<?= base_url($logo) ?>" alt="Website Logo" style="max-height:48px; max-width:180px; object-fit:contain; border-radius:6px; border:1px solid var(--color-border, #E2E8F0);">
                                    <div style="display:grid; gap:4px;">
                                        <span class="small" style="word-break:break-all; font-weight:600; font-size:0.75rem; color:#64748B;"><?= esc($logo) ?></span>
                                        <label style="display:flex; gap:6px; align-items:center; cursor:pointer; font-size:0.8125rem; color:#EF4444; font-weight:600;">
                                            <input type="checkbox" name="remove_logo" value="1" id="remove_logo" style="width:16px;height:16px;"> Remove logo
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div id="logo-upload-area" style="border:1.5px dashed var(--color-border, #CBD5E1); border-radius:10px; padding:18px; text-align:center; background:#fff; cursor:pointer; transition:border-color 0.2s;">
                                <input type="file" name="logo_file" id="logo_file" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Website Logo">
                                <div style="display:grid; gap:8px; place-items:center;">
                                    <div style="width:40px; height:40px; border-radius:10px; background:var(--color-surface-muted, #F1F5F9); display:grid; place-items:center; color:var(--color-text-muted, #64748B);">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M3 12l4-4 3 3 4-5 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg>
                                    </div>
                                    <div style="font-size:0.8125rem; font-weight:600;">Click to upload or drag &amp; drop</div>
                                    <div class="field__hint">PNG, JPG, WEBP, SVG up to 2 MB — safe random filename stored</div>
                                    <button type="button" class="btn btn--secondary btn--sm" id="btn-select-logo">Select Image</button>
                                </div>
                                <div id="logo-new-preview" style="margin-top:12px; display:none; text-align:center;"></div>
                            </div>
                            <div style="display:flex; gap:8px; margin-top:8px;">
                                <button type="button" class="btn btn--ghost btn--sm" id="btn-remove-logo-new" style="display:none;">Clear selection</button>
                            </div>
                        </div>

                        <!-- Favicon Media Control Dropzone -->
                        <div class="field">
                            <label class="field__label">Website Favicon</label>
                            <div class="field__hint" style="margin-bottom:8px;">ICO, PNG, SVG or WEBP recommended, max 1 MB.</div>
                            
                            <?php if (!empty($favicon) && file_exists(FCPATH . $favicon)): ?>
                                <div id="favicon-preview" style="display:flex; gap:12px; align-items:center; margin-bottom:12px; border:1px solid var(--color-border-subtle, #E2E8F0); border-radius:8px; padding:12px; background:var(--color-surface-subtle, #F8FAFC);">
                                    <img src="<?= base_url($favicon) ?>" alt="Favicon" style="width:32px; height:32px; object-fit:contain; border-radius:4px; border:1px solid var(--color-border, #E2E8F0);">
                                    <div style="display:grid; gap:4px;">
                                        <span class="small" style="word-break:break-all; font-weight:600; font-size:0.75rem; color:#64748B;"><?= esc($favicon) ?></span>
                                        <label style="display:flex; gap:6px; align-items:center; cursor:pointer; font-size:0.8125rem; color:#EF4444; font-weight:600;">
                                            <input type="checkbox" name="remove_favicon" value="1" id="remove_favicon" style="width:16px;height:16px;"> Remove favicon
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div id="favicon-upload-area" style="border:1.5px dashed var(--color-border, #CBD5E1); border-radius:10px; padding:18px; text-align:center; background:#fff; cursor:pointer; transition:border-color 0.2s;">
                                <input type="file" name="favicon_file" id="favicon_file" accept=".ico,.png,.svg,.webp,image/x-icon,image/vnd.microsoft.icon,image/png,image/svg+xml,image/webp" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Website Favicon">
                                <div style="display:grid; gap:8px; place-items:center;">
                                    <div style="width:40px; height:40px; border-radius:10px; background:var(--color-surface-muted, #F1F5F9); display:grid; place-items:center; color:var(--color-text-muted, #64748B);">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M3 12l4-4 3 3 4-5 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg>
                                    </div>
                                    <div style="font-size:0.8125rem; font-weight:600;">Click to upload or drag &amp; drop</div>
                                    <div class="field__hint">ICO, PNG, SVG, WEBP up to 1 MB — safe random filename stored</div>
                                    <button type="button" class="btn btn--secondary btn--sm" id="btn-select-favicon">Select Image</button>
                                </div>
                                <div id="favicon-new-preview" style="margin-top:12px; display:none; text-align:center;"></div>
                            </div>
                            <div style="display:flex; gap:8px; margin-top:8px;">
                                <button type="button" class="btn btn--ghost btn--sm" id="btn-remove-favicon-new" style="display:none;">Clear selection</button>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- SECTION 2: PUBLIC CONTACT & BUSINESS INFO -->
                <div style="border: 1px solid var(--color-border, #E2E8F0); border-radius: 12px; padding: 24px; background: #FFFFFF;">
                    <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">Public Contact &amp; Operations</h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                        <div class="field">
                            <label for="contact_email" class="field__label">Contact Email Address</label>
                            <input type="email" id="contact_email" name="contact_email" class="field__input" value="<?= esc($contactEmail) ?>" maxlength="190" placeholder="support@ftpreneur.com">
                            <div class="field__hint">Public support email displayed across website header/footer.</div>
                        </div>

                        <div class="field">
                            <label for="contact_phone" class="field__label">Contact Phone Number</label>
                            <input type="text" id="contact_phone" name="contact_phone" class="field__input" value="<?= esc($contactPhone) ?>" maxlength="30" placeholder="+91 95742 93300">
                            <div class="field__hint">Public phone number for customer inquiries.</div>
                        </div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="general_whatsapp_number" class="field__label">General WhatsApp Contact Number</label>
                        <input type="text" id="general_whatsapp_number" name="general_whatsapp_number" class="field__input" value="<?= esc($generalWaNumber) ?>" placeholder="e.g. 919574293300 or +91 95742 93300">
                        <div class="field__hint">Public WhatsApp inquiry contact number (independent of post-payment onboarding WhatsApp destination).</div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="address" class="field__label">Business Physical Address</label>
                        <textarea id="address" name="address" class="field__input" rows="3" style="min-height: 80px; resize: vertical;" placeholder="e.g. Suite 402, Business Bay, Kharadi, Pune, Maharashtra 411014"><?= esc($address) ?></textarea>
                        <div class="field__hint">Official business address displayed on contact page and legal footers.</div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="business_hours" class="field__label">Business Operational Hours</label>
                        <input type="text" id="business_hours" name="business_hours" class="field__input" value="<?= esc($businessHours) ?>" maxlength="100" placeholder="MON – SAT // 9:00 AM – 7:00 PM IST">
                        <div class="field__hint">Operating hours displayed in support cards and footer.</div>
                    </div>
                </div>

            <!-- TAB 2: PAYMENT & INTEGRATIONS -->
            <?php elseif ($activeTab === 'payment'): ?>

                <!-- SECTION A: RAZORPAY CONFIGURATION -->
                <div style="border: 1px solid var(--color-border, #E2E8F0); border-radius: 12px; padding: 24px; margin-bottom: 32px; background: #FAFAFA;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap;">
                        <div>
                            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">Razorpay Gateway Configuration</h4>
                            <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748B;">Manage active mode, test credentials, and live production credentials.</p>
                        </div>
                        <div>
                            <?php if ($razorpayMode === 'live'): ?>
                                <span class="badge badge--success" style="padding: 6px 14px; font-weight: 700; font-size: 12px; letter-spacing: 0.5px;">LIVE MODE</span>
                            <?php else: ?>
                                <span class="badge badge--warning" style="padding: 6px 14px; font-weight: 700; font-size: 12px; letter-spacing: 0.5px;">TEST MODE</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Active Mode Switcher -->
                    <div class="field" style="margin-bottom: 24px; padding: 16px; background: #FFFFFF; border: 1px solid var(--color-border, #E2E8F0); border-radius: 8px;">
                        <label class="field__label" style="margin-bottom: 8px; font-weight: 700;">Active Gateway Mode <span aria-hidden="true" style="color:var(--color-danger, #EF4444)">*</span></label>
                        <div style="display: flex; gap: 24px; align-items: center; margin-top: 6px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 600;">
                                <input type="radio" name="razorpay_mode" value="test" <?= $razorpayMode === 'test' ? 'checked' : '' ?> style="width:16px;height:16px;">
                                <span>Test Mode (Sandbox)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 600;">
                                <input type="radio" name="razorpay_mode" value="live" <?= $razorpayMode === 'live' ? 'checked' : '' ?> style="width:16px;height:16px;">
                                <span>Live Mode (Production)</span>
                            </label>
                        </div>
                        <div class="field__hint" style="margin-top: 6px;">
                            Runtime automatically selects active credentials based on this setting. If admin settings are blank, system falls back to <code>.env</code> file.
                        </div>
                    </div>

                    <!-- Grid for Test & Live Credentials -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
                        
                        <!-- Test Credentials Box -->
                        <div style="background: #FFFFFF; border: 1px solid <?= $razorpayMode === 'test' ? '#CBD5E1' : '#E2E8F0' ?>; border-radius: 8px; padding: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                <h5 style="margin: 0; font-size: 14px; font-weight: 700; color: #0F172A; text-transform: uppercase;">Test Mode Credentials</h5>
                                <span class="badge badge--warning" style="font-size: 10px;">Sandbox</span>
                            </div>

                            <div class="field">
                                <label for="razorpay_test_key_id" class="field__label">Test Key ID</label>
                                <input type="text" id="razorpay_test_key_id" name="razorpay_test_key_id" class="field__input" value="<?= esc($razorpayTestKeyId) ?>" placeholder="rzp_test_...">
                            </div>

                            <div class="field" style="margin-top: 14px;">
                                <label for="razorpay_test_key_secret" class="field__label">Test Key Secret</label>
                                <input type="password" id="razorpay_test_key_secret" name="razorpay_test_key_secret" class="field__input" placeholder="<?= $hasRazorpayTestSecret ? 'Configured — leave blank to keep' : 'Enter Test Key Secret' ?>" autocomplete="new-password">
                                <?php if ($hasRazorpayTestSecret): ?>
                                    <div class="field__hint" style="color: #059669; font-weight: 600;">Secret stored encrypted. Leave blank to keep existing.</div>
                                <?php endif; ?>
                            </div>

                            <div class="field" style="margin-top: 14px;">
                                <label for="razorpay_test_webhook_secret" class="field__label">Test Webhook Secret</label>
                                <input type="password" id="razorpay_test_webhook_secret" name="razorpay_test_webhook_secret" class="field__input" placeholder="<?= $hasRazorpayTestWebhook ? 'Configured — leave blank to keep' : 'Enter Test Webhook Secret' ?>" autocomplete="new-password">
                                <?php if ($hasRazorpayTestWebhook): ?>
                                    <div class="field__hint" style="color: #059669; font-weight: 600;">Secret stored encrypted. Leave blank to keep existing.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Live Credentials Box -->
                        <div style="background: #FFFFFF; border: 1px solid <?= $razorpayMode === 'live' ? '#CBD5E1' : '#E2E8F0' ?>; border-radius: 8px; padding: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                                <h5 style="margin: 0; font-size: 14px; font-weight: 700; color: #0F172A; text-transform: uppercase;">Live Mode Credentials</h5>
                                <span class="badge badge--success" style="font-size: 10px;">Production</span>
                            </div>

                            <div class="field">
                                <label for="razorpay_live_key_id" class="field__label">Live Key ID</label>
                                <input type="text" id="razorpay_live_key_id" name="razorpay_live_key_id" class="field__input" value="<?= esc($razorpayLiveKeyId) ?>" placeholder="rzp_live_...">
                            </div>

                            <div class="field" style="margin-top: 14px;">
                                <label for="razorpay_live_key_secret" class="field__label">Live Key Secret</label>
                                <input type="password" id="razorpay_live_key_secret" name="razorpay_live_key_secret" class="field__input" placeholder="<?= $hasRazorpayLiveSecret ? 'Configured — leave blank to keep' : 'Enter Live Key Secret' ?>" autocomplete="new-password">
                                <?php if ($hasRazorpayLiveSecret): ?>
                                    <div class="field__hint" style="color: #059669; font-weight: 600;">Secret stored encrypted. Leave blank to keep existing.</div>
                                <?php endif; ?>
                            </div>

                            <div class="field" style="margin-top: 14px;">
                                <label for="razorpay_live_webhook_secret" class="field__label">Live Webhook Secret</label>
                                <input type="password" id="razorpay_live_webhook_secret" name="razorpay_live_webhook_secret" class="field__input" placeholder="<?= $hasRazorpayLiveWebhook ? 'Configured — leave blank to keep' : 'Enter Live Webhook Secret' ?>" autocomplete="new-password">
                                <?php if ($hasRazorpayLiveWebhook): ?>
                                    <div class="field__hint" style="color: #059669; font-weight: 600;">Secret stored encrypted. Leave blank to keep existing.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- Webhook Endpoint Info Box -->
                    <div style="background: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 8px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); color: #475569; text-transform: uppercase;">
                            RAZORPAY WEBHOOK ENDPOINT
                        </div>
                        <div style="margin-top: 6px; font-family: var(--f-mono, monospace); font-size: 13px; color: #0F172A; font-weight: 600; word-break: break-all;">
                            <?= esc($webhookUrl) ?>
                        </div>
                        <div style="margin-top: 8px; font-size: 12px; color: #64748B;">
                            Configure this URL in your Razorpay Dashboard webhooks section. Handled events: <code>payment.captured</code>, <code>order.paid</code>.
                        </div>
                    </div>
                </div>

                <!-- SECTION B: POST-PAYMENT DESTINATIONS -->
                <div style="border: 1px solid var(--color-border, #E2E8F0); border-radius: 12px; padding: 24px; background: #FFFFFF;">
                    <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">Post-Payment Destinations</h4>

                    <div class="field">
                        <label for="google_form_url" class="field__label">Post-Payment Google Form URL</label>
                        <input type="url" id="google_form_url" name="google_form_url" class="field__input" value="<?= esc($googleFormUrl) ?>" placeholder="https://docs.google.com/forms/d/e/.../viewform">
                        <div class="field__hint">The onboarding questionnaire link opened by clients when clicking "Complete Your Details →".</div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="whatsapp_number" class="field__label">Post-Payment WhatsApp Support Number</label>
                        <input type="text" id="whatsapp_number" name="whatsapp_number" class="field__input" value="<?= esc($whatsappNumber) ?>" placeholder="e.g. 919876543210 or 447817946465">
                        <div class="field__hint">Enter full WhatsApp number including country code (e.g., 919876543210 or 447817946465). Non-digit characters will be stripped automatically.</div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="whatsapp_message" class="field__label">Default WhatsApp Message Template</label>
                        <textarea id="whatsapp_message" name="whatsapp_message" class="field__input" rows="3" style="min-height: 80px; resize: vertical;"><?= esc($whatsappMessage) ?></textarea>
                        <div class="field__hint">
                            Supported dynamic placeholders: 
                            <code>{order_number}</code>, <code>{program_name}</code>, <code>{customer_name}</code>.
                        </div>
                    </div>
                </div>

            <!-- TAB 3: WHATSAPP INTEGRATION -->
            <?php elseif ($activeTab === 'whatsapp'): ?>

                <div style="padding: 16px 20px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" style="margin-top: 2px; flex-shrink: 0;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4m0-4h.01"/>
                    </svg>
                    <div style="font-size: 13px; color: #1E40AF; line-height: 1.5;">
                        <strong>WhatsApp API Integration (Configuration Only).</strong><br>
                        Configure your WhatsApp Business API / Meta Cloud API credentials below. Automatic API message sending will be enabled in a future release. Current customer post-payment chat (wa.me) will continue operating smoothly using the Post-Payment WhatsApp Number.
                    </div>
                </div>

                <div class="field">
                    <label class="field" style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="whatsapp_enabled" value="1" <?= $whatsappEnabled ? 'checked' : '' ?> style="width:18px;height:18px;">
                        <span class="field__label" style="margin:0; font-size:14px; font-weight:700;">Enable WhatsApp API Integration</span>
                    </label>
                    <div class="field__hint" style="margin-top:4px;">Master toggle for automated WhatsApp API messaging service.</div>
                </div>

                <div class="field" style="margin-top: 20px;">
                    <label for="whatsapp_provider" class="field__label">Provider / Integration Type</label>
                    <input type="text" id="whatsapp_provider" name="whatsapp_provider" class="field__input" value="<?= esc($whatsappProvider) ?>" placeholder="e.g. Meta Cloud API, Twilio, Gupshup">
                    <div class="field__hint">API service provider name. Defaults to <code>Meta Cloud API</code>.</div>
                </div>

                <div class="field" style="margin-top: 20px;">
                    <label for="whatsapp_sender_number" class="field__label">Sender / WhatsApp Business Number</label>
                    <input type="text" id="whatsapp_sender_number" name="whatsapp_sender_number" class="field__input" value="<?= esc($whatsappSenderNumber) ?>" placeholder="e.g. 919574293300">
                    <div class="field__hint">Registered WhatsApp Business phone number including country code.</div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div class="field">
                        <label for="whatsapp_phone_number_id" class="field__label">Phone Number ID</label>
                        <input type="text" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" class="field__input" value="<?= esc($whatsappPhoneNumberId) ?>" placeholder="Meta Cloud API Phone Number ID">
                    </div>

                    <div class="field">
                        <label for="whatsapp_business_account_id" class="field__label">Business Account ID</label>
                        <input type="text" id="whatsapp_business_account_id" name="whatsapp_business_account_id" class="field__input" value="<?= esc($whatsappBusinessAccountId) ?>" placeholder="Meta WhatsApp Business Account ID">
                    </div>
                </div>

                <div class="field" style="margin-top: 20px;">
                    <label for="whatsapp_api_access_token" class="field__label">Permanent API Access Token</label>
                    <input type="password" id="whatsapp_api_access_token" name="whatsapp_api_access_token" class="field__input" placeholder="<?= $hasWhatsappAccessToken ? 'Configured — leave blank to keep' : 'Enter Permanent System User Access Token' ?>" autocomplete="new-password">
                    <?php if ($hasWhatsappAccessToken): ?>
                        <div class="field__hint" style="color: #059669; font-weight: 600;">Access Token stored encrypted. Leave blank to keep existing.</div>
                    <?php else: ?>
                        <div class="field__hint">Encrypted at rest. Never displayed back on UI.</div>
                    <?php endif; ?>
                </div>

                <div class="field" style="margin-top: 20px;">
                    <label for="whatsapp_api_version" class="field__label">API Version</label>
                    <input type="text" id="whatsapp_api_version" name="whatsapp_api_version" class="field__input" value="<?= esc($whatsappApiVersion) ?>" placeholder="e.g. v18.0">
                    <div class="field__hint">Graph API version tag. Defaults to <code>v18.0</code>.</div>
                </div>

            <!-- TAB 4: SEO SETTINGS -->
            <?php elseif ($activeTab === 'seo'): ?>

                <!-- SECTION 1: SEARCH ENGINE BASICS -->
                <div style="border: 1px solid var(--color-border, #E2E8F0); border-radius: 12px; padding: 24px; margin-bottom: 32px; background: #FAFAFA;">
                    <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">Search Engine Meta Tags</h4>

                    <div class="field">
                        <label for="seo_title" class="field__label">Meta Title <span aria-hidden="true" style="color:var(--color-danger, #EF4444)">*</span></label>
                        <input type="text" id="seo_title" name="seo_title" class="field__input" value="<?= esc($seoTitle) ?>" required maxlength="190">
                        <div class="field__hint">Primary landing page <code>&lt;title&gt;</code> tag for Google search results.</div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="seo_meta_description" class="field__label">Meta Description</label>
                        <textarea id="seo_meta_description" name="seo_meta_description" class="field__input" rows="3" style="min-height: 80px; resize: vertical;"><?= esc($seoMetaDesc) ?></textarea>
                        <div class="field__hint">Search engine snippet text displayed under the page title.</div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="seo_keywords" class="field__label">Meta Keywords</label>
                        <input type="text" id="seo_keywords" name="seo_keywords" class="field__input" value="<?= esc($seoKeywords) ?>" placeholder="comma, separated, keywords">
                        <div class="field__hint">Comma-separated target topic keywords (e.g. visphy kharradi, ftpreneur, nutrition).</div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
                        <div class="field">
                            <label for="seo_canonical_url" class="field__label">Canonical URL</label>
                            <input type="url" id="seo_canonical_url" name="seo_canonical_url" class="field__input" value="<?= esc($seoCanonical) ?>" placeholder="https://ftpreneur.com">
                            <div class="field__hint">Preferred canonical URL for indexing avoiding duplicate content.</div>
                        </div>

                        <div class="field">
                            <label for="seo_robots" class="field__label">Robots Tag</label>
                            <select id="seo_robots" name="seo_robots" class="field__input">
                                <option value="index, follow" <?= $seoRobots === 'index, follow' ? 'selected' : '' ?>>index, follow (Default / Recommended)</option>
                                <option value="noindex, follow" <?= $seoRobots === 'noindex, follow' ? 'selected' : '' ?>>noindex, follow</option>
                                <option value="index, nofollow" <?= $seoRobots === 'index, nofollow' ? 'selected' : '' ?>>index, nofollow</option>
                                <option value="noindex, nofollow" <?= $seoRobots === 'noindex, nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                            </select>
                            <div class="field__hint">Search engine crawler indexing &amp; link-following directive.</div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: OPEN GRAPH (FACEBOOK, LINKEDIN, WHATSAPP PREVIEWS) -->
                <div style="border: 1px solid var(--color-border, #E2E8F0); border-radius: 12px; padding: 24px; margin-bottom: 32px; background: #FFFFFF;">
                    <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">Open Graph (Facebook / LinkedIn / WhatsApp)</h4>

                    <div class="field">
                        <label for="seo_og_title" class="field__label">Open Graph Title</label>
                        <input type="text" id="seo_og_title" name="seo_og_title" class="field__input" value="<?= esc($seoOgTitle) ?>" placeholder="Title for social media shares">
                        <div class="field__hint">Headline displayed when page link is shared on Facebook, LinkedIn, or WhatsApp.</div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="seo_og_description" class="field__label">Open Graph Description</label>
                        <textarea id="seo_og_description" name="seo_og_description" class="field__input" rows="3" style="min-height: 80px; resize: vertical;"><?= esc($seoOgDesc) ?></textarea>
                        <div class="field__hint">Summary description for social sharing cards.</div>
                    </div>

                    <!-- OG Image Media Control Dropzone -->
                    <div class="field" style="margin-top: 20px;">
                        <label class="field__label">Open Graph Social Image</label>
                        <div class="field__hint" style="margin-bottom:8px;">Recommended 1200×630 px, PNG/JPG/WEBP, max 2 MB.</div>
                        
                        <?php if (!empty($seoOgImage) && file_exists(FCPATH . $seoOgImage)): ?>
                            <div id="og-preview" style="display:flex; gap:12px; align-items:center; margin-bottom:12px; border:1px solid var(--color-border-subtle, #E2E8F0); border-radius:8px; padding:12px; background:var(--color-surface-subtle, #F8FAFC);">
                                <img src="<?= base_url($seoOgImage) ?>" alt="Open Graph Social Preview" style="max-height:80px; max-width:180px; object-fit:cover; border-radius:6px; border:1px solid var(--color-border, #E2E8F0);">
                                <div style="display:grid; gap:4px;">
                                    <span class="small" style="word-break:break-all; font-weight:600; font-size:0.75rem; color:#64748B;"><?= esc($seoOgImage) ?></span>
                                    <label style="display:flex; gap:6px; align-items:center; cursor:pointer; font-size:0.8125rem; color:#EF4444; font-weight:600;">
                                        <input type="checkbox" name="remove_og_image" value="1" id="remove_og_image" style="width:16px;height:16px;"> Remove OG image
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="og-upload-area" style="border:1.5px dashed var(--color-border, #CBD5E1); border-radius:10px; padding:18px; text-align:center; background:#fff; cursor:pointer; transition:border-color 0.2s;">
                            <input type="file" name="og_image_file" id="og_image_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Open Graph Image">
                            <div style="display:grid; gap:8px; place-items:center;">
                                <div style="width:40px; height:40px; border-radius:10px; background:var(--color-surface-muted, #F1F5F9); display:grid; place-items:center; color:var(--color-text-muted, #64748B);">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M3 12l4-4 3 3 4-5 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg>
                                </div>
                                <div style="font-size:0.8125rem; font-weight:600;">Click to upload or drag &amp; drop</div>
                                <div class="field__hint">PNG, JPG, WEBP up to 2 MB — safe random filename stored</div>
                                <button type="button" class="btn btn--secondary btn--sm" id="btn-select-og">Select Image</button>
                            </div>
                            <div id="og-new-preview" style="margin-top:12px; display:none; text-align:center;"></div>
                        </div>
                        <div style="display:flex; gap:8px; margin-top:8px;">
                            <button type="button" class="btn btn--ghost btn--sm" id="btn-remove-og-new" style="display:none;">Clear selection</button>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: TWITTER / X CARDS -->
                <div style="border: 1px solid var(--color-border, #E2E8F0); border-radius: 12px; padding: 24px; background: #FFFFFF;">
                    <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0F172A; text-transform: uppercase; letter-spacing: 0.5px;">Twitter / X Card Parameters</h4>

                    <div class="field">
                        <label for="seo_twitter_title" class="field__label">Twitter / X Title</label>
                        <input type="text" id="seo_twitter_title" name="seo_twitter_title" class="field__input" value="<?= esc($seoTwitterTitle) ?>" placeholder="Title for Twitter/X cards">
                        <div class="field__hint">Headline for Twitter summary_large_image cards.</div>
                    </div>

                    <div class="field" style="margin-top: 20px;">
                        <label for="seo_twitter_description" class="field__label">Twitter / X Description</label>
                        <textarea id="seo_twitter_description" name="seo_twitter_description" class="field__input" rows="3" style="min-height: 80px; resize: vertical;"><?= esc($seoTwitterDesc) ?></textarea>
                        <div class="field__hint">Card summary description for Twitter posts.</div>
                    </div>

                    <!-- Twitter Image Media Control Dropzone -->
                    <div class="field" style="margin-top: 20px;">
                        <label class="field__label">Twitter / X Card Image</label>
                        <div class="field__hint" style="margin-bottom:8px;">Recommended summary_large_image preview, PNG/JPG/WEBP, max 2 MB.</div>
                        
                        <?php if (!empty($seoTwitterImage) && file_exists(FCPATH . $seoTwitterImage)): ?>
                            <div id="twitter-preview" style="display:flex; gap:12px; align-items:center; margin-bottom:12px; border:1px solid var(--color-border-subtle, #E2E8F0); border-radius:8px; padding:12px; background:var(--color-surface-subtle, #F8FAFC);">
                                <img src="<?= base_url($seoTwitterImage) ?>" alt="Twitter Card Preview" style="max-height:80px; max-width:180px; object-fit:cover; border-radius:6px; border:1px solid var(--color-border, #E2E8F0);">
                                <div style="display:grid; gap:4px;">
                                    <span class="small" style="word-break:break-all; font-weight:600; font-size:0.75rem; color:#64748B;"><?= esc($seoTwitterImage) ?></span>
                                    <label style="display:flex; gap:6px; align-items:center; cursor:pointer; font-size:0.8125rem; color:#EF4444; font-weight:600;">
                                        <input type="checkbox" name="remove_twitter_image" value="1" id="remove_twitter_image" style="width:16px;height:16px;"> Remove Twitter image
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="twitter-upload-area" style="border:1.5px dashed var(--color-border, #CBD5E1); border-radius:10px; padding:18px; text-align:center; background:#fff; cursor:pointer; transition:border-color 0.2s;">
                            <input type="file" name="twitter_image_file" id="twitter_image_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" style="position:absolute; left:-9999px; width:1px; height:1px; opacity:0;" aria-label="Twitter Card Image">
                            <div style="display:grid; gap:8px; place-items:center;">
                                <div style="width:40px; height:40px; border-radius:10px; background:var(--color-surface-muted, #F1F5F9); display:grid; place-items:center; color:var(--color-text-muted, #64748B);">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M3 12l4-4 3 3 4-5 3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg>
                                </div>
                                <div style="font-size:0.8125rem; font-weight:600;">Click to upload or drag &amp; drop</div>
                                <div class="field__hint">PNG, JPG, WEBP up to 2 MB — safe random filename stored</div>
                                <button type="button" class="btn btn--secondary btn--sm" id="btn-select-twitter">Select Image</button>
                            </div>
                            <div id="twitter-new-preview" style="margin-top:12px; display:none; text-align:center;"></div>
                        </div>
                        <div style="display:flex; gap:8px; margin-top:8px;">
                            <button type="button" class="btn btn--ghost btn--sm" id="btn-remove-twitter-new" style="display:none;">Clear selection</button>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <!-- Form Actions -->
            <div class="form-actions" style="margin-top: 32px; display: flex; align-items: center; justify-content: flex-end; gap: 16px;">
                <button type="submit" class="btn btn--primary btn--md">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M4 4h10l2 2v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M6 4v4h7V4M6 16v-5h7v5" stroke="currentColor" stroke-width="1.4"/>
                    </svg>
                    Save <?= esc($tabs[$activeTab] ?? 'Settings') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.settings-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px 10px 0 0;
    font-family: var(--f-display, sans-serif);
    font-size: 14px;
    font-weight: 600;
    color: var(--text-muted, #64748B);
    text-decoration: none;
    border: 1px solid transparent;
    transition: all 0.2s ease;
}
.settings-tab:hover {
    color: var(--text-color, #0F172A);
    background-color: rgba(241, 245, 249, 0.7);
}
.settings-tab--active {
    color: var(--primary-color, #2E47FF);
    background-color: #FFFFFF;
    border-color: var(--border-color, #E2E8F0);
    border-bottom-color: #FFFFFF;
    margin-bottom: -1px;
}
</style>

<script>
function setupDropzone(areaId, inputId, btnId, previewId, clearBtnId) {
    const area = document.getElementById(areaId);
    const input = document.getElementById(inputId);
    const btnSelect = document.getElementById(btnId);
    const preview = document.getElementById(previewId);
    const btnClear = document.getElementById(clearBtnId);

    if (!area || !input) return;

    const openPicker = () => input.click();
    area.addEventListener('click', (e) => {
        if (e.target.closest('button') || e.target.closest('input') || e.target.closest('label')) return;
        openPicker();
    });
    if (btnSelect) btnSelect.addEventListener('click', (e) => { e.stopPropagation(); openPicker(); });

    area.addEventListener('dragover', (e) => {
        e.preventDefault();
        area.style.borderColor = 'var(--color-primary, #2E47FF)';
        area.style.backgroundColor = 'rgba(46, 71, 255, 0.02)';
    });
    area.addEventListener('dragleave', () => {
        area.style.borderColor = 'var(--color-border, #CBD5E1)';
        area.style.backgroundColor = '#FFFFFF';
    });
    area.addEventListener('drop', (e) => {
        e.preventDefault();
        area.style.borderColor = 'var(--color-border, #CBD5E1)';
        area.style.backgroundColor = '#FFFFFF';
        if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            updatePreview();
        }
    });

    input.addEventListener('change', updatePreview);

    function updatePreview() {
        if (!input.files.length) {
            if (preview) preview.style.display = 'none';
            if (btnClear) btnClear.style.display = 'none';
            return;
        }
        const file = input.files[0];
        if (preview) {
            preview.style.display = 'block';
            preview.innerHTML = `
                <div style="border: 1px solid var(--color-border, #E2E8F0); border-radius: 8px; padding: 10px; display: inline-flex; align-items: center; gap: 12px; background: #F8FAFC; margin-top: 8px;">
                    <img style="max-width: 120px; max-height: 80px; object-fit: contain; border-radius: 6px; border: 1px solid #CBD5E1;" alt="Selected file preview">
                    <div style="text-align: left;">
                        <div style="font-size: 0.8125rem; font-weight: 700; color: #0F172A;">New selection</div>
                        <div style="font-size: 0.75rem; color: #64748B;">${file.name} (${Math.round(file.size / 1024)} KB)</div>
                    </div>
                </div>`;
            const img = preview.querySelector('img');
            const reader = new FileReader();
            reader.onload = (e) => { img.src = e.target.result; };
            reader.readAsDataURL(file);
        }
        if (btnClear) btnClear.style.display = 'inline-flex';
    }

    if (btnClear) {
        btnClear.addEventListener('click', (e) => {
            e.stopPropagation();
            input.value = '';
            if (preview) preview.style.display = 'none';
            btnClear.style.display = 'none';
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    setupDropzone('logo-upload-area', 'logo_file', 'btn-select-logo', 'logo-new-preview', 'btn-remove-logo-new');
    setupDropzone('favicon-upload-area', 'favicon_file', 'btn-select-favicon', 'favicon-new-preview', 'btn-remove-favicon-new');
    setupDropzone('og-upload-area', 'og_image_file', 'btn-select-og', 'og-new-preview', 'btn-remove-og-new');
    setupDropzone('twitter-upload-area', 'twitter_image_file', 'btn-select-twitter', 'twitter-new-preview', 'btn-remove-twitter-new');
});
</script>
<?= $this->endSection() ?>

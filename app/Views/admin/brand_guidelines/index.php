<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('head') ?>
<style>
/* Brand Guidelines preview chrome — isolated to this admin page only */
.brand-preview-toolbar{
  display:flex; align-items:center; justify-content:space-between;
  gap:12px; flex-wrap:wrap;
  padding:14px 16px;
  border-bottom:1px solid var(--color-border, #E2E8F0);
  background: var(--color-surface, #fff);
}
.brand-preview-toolbar__left{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.brand-preview-toolbar__right{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.brand-preview-frame-wrap{
  background:#FAFAF6;
  padding:0;
  overflow:hidden;
  border-radius:0 0 12px 12px;
}
.brand-preview-frame{
  width:100%;
  height:75vh;
  min-height:640px;
  max-height:1200px;
  border:0;
  display:block;
  background:#fff;
}
@media (max-width: 768px){
  .brand-preview-frame{ height:72vh; min-height:560px; }
}
.brand-preview-meta{
  display:flex; align-items:center; gap:8px; flex-wrap:wrap;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$eyebrow = 'System · Documentation';
$title = 'Brand Guidelines';
$desc = 'Approved Visphy Kharradi visual reference — preserved in docs/brand-guidelines/. Preview is isolated via iframe so admin CSS cannot corrupt guideline styles and guideline CSS cannot leak into admin.';
?>
<?= $this->include('admin/partials/page_header', ['title' => $title, 'description' => $desc, 'eyebrow' => $eyebrow]) ?>

<div class="u-stack-lg">

    <section class="card">
        <div class="card__body">
            <div class="u-flex-between" style="flex-wrap:wrap; gap:16px;">
                <div class="u-stack-sm" style="min-width:260px; max-width:640px;">
                    <div class="caption">SOURCE OF TRUTH</div>
                    <h2 class="h3" style="margin:0;">Visphy Kharradi — Brand Guidelines + Digital Visual System</h2>
                    <p class="small u-muted" style="margin:0; line-height:1.6;">
                        43 stops · Light-first · Proposed master palette (Ultramarine #2E47FF / Mint #00B79B / Marigold #FF9E2C / Ink #141B31).
                        This is <strong style="color:var(--color-text);">documentation/reference only</strong> — not the production frontend theme.
                        Future public site must derive from this system; do not copy this HTML into <code>app/Views</code>.
                    </p>
                    <div class="u-flex" style="gap:8px; flex-wrap:wrap; margin-top:6px;">
                        <span class="badge badge--neutral">docs/brand-guidelines/index.html</span>
                        <span class="badge badge--neutral">css/styles.css</span>
                        <span class="badge badge--neutral">js/main.js</span>
                        <span class="badge badge--info">Isolated iframe</span>
                    </div>
                </div>
                <div class="u-stack-sm" style="min-width:200px;">
                    <div class="caption">OPEN OPTIONS</div>
                    <div class="u-flex" style="gap:8px; flex-wrap:wrap;">
                        <a href="<?= site_url('admin/brand-guidelines/frame') ?>" target="_blank" rel="noopener" class="btn btn--secondary">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7 12l5-5M8 7h5v5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="3" width="14" height="14" rx="1.5" stroke="currentColor" stroke-width="1.25"/></svg>
                            Open frame only
                        </a>
                        <button type="button" class="btn btn--primary" onclick="document.getElementById('brand-guidelines-frame')?.requestFullscreen?.()">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 7V4h3M16 7V4h-3M4 13v3h3M16 13v3h-3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Fullscreen
                        </button>
                    </div>
                    <p class="small u-muted" style="margin:0; font-size:0.78rem; color:var(--color-text-faint);">Requires admin authentication. No public route exists.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="card" style="overflow:hidden; padding:0;">
        <div class="brand-preview-toolbar">
            <div class="brand-preview-toolbar__left">
                <div class="brand-preview-meta">
                    <span class="badge badge--primary">Authenticated preview</span>
                    <span class="small u-muted" style="font-size:0.82rem;">Admin CSS isolated — guideline renders with its own Tailwind CSS + JS</span>
                </div>
            </div>
            <div class="brand-preview-toolbar__right">
                <span class="small u-muted" style="font-size:0.78rem; color:var(--color-text-faint);">43 STOPS · RESPONSIVE · SCROLL INSIDE FRAME</span>
            </div>
        </div>
        <div class="brand-preview-frame-wrap">
            <iframe
                id="brand-guidelines-frame"
                src="<?= site_url('admin/brand-guidelines/frame') ?>"
                title="Brand Guidelines — Visphy Kharradi (isolated preview)"
                class="brand-preview-frame"
                loading="lazy"
                allow="fullscreen"
                sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
                referrerpolicy="same-origin"
            ></iframe>
        </div>
        <div class="card__body" style="border-top:1px solid var(--color-border, #E2E8F0); background: var(--color-surface-subtle, #F8FAFC);">
            <p class="small u-muted" style="margin:0; font-size:0.82rem; line-height:1.6;">
                <strong style="color:var(--color-text);">Isolation note:</strong> Guideline HTML is served via <code>admin/brand-guidelines/frame</code> and its CSS/JS via
                <code>admin/brand-guidelines/css/styles.css</code> + <code>js/main.js</code> — all behind <code>adminAuth</code>.
                The iframe's document includes only guideline CSS; the parent admin shell CSS never enters the frame. Guideline CSS cannot leak out.
                Resize the browser or use fullscreen for mobile/desktop review. External Google Fonts + Pexels images load from CDN inside the frame.
            </p>
        </div>
    </section>

    <section class="card card--subtle">
        <div class="card__body" style="display:grid; gap:10px;">
            <div class="caption">GUARDRAILS</div>
            <ul class="u-stack-sm" style="margin:0; padding-left:18px; color:var(--color-text-muted); font-size:0.875rem; line-height:1.6;">
                <li><strong>Do not</strong> copy this guideline's HTML structure verbatim into <code>app/Views/home/</code>.</li>
                <li><strong>Do not</strong> treat <code>docs/brand-guidelines/css/styles.css</code> as production <code>public/assets/</code> CSS.</li>
                <li>Future frontend must <strong>reference</strong> colors, type, spacing, components, imagery, motion — may adapt markup for a11y/responsive/SEO/CI4.</li>
                <li>This preview is conveniency only; the canonical source remains <code>docs/brand-guidelines/</code> + <code>docs/BRAND_GUIDELINES.md</code>.</li>
            </ul>
        </div>
    </section>

</div>
<?= $this->endSection() ?>

<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php $pageContext = 'UI Preview'; ?>

<?= $this->include('admin/partials/page_header', [
    'eyebrow' => 'Design System',
    'title' => 'UI Preview',
    'description' => 'Premium CRM audit — all components rendered with static demo data (UI DEMO, never production). Authenticated & non-production only.',
]) ?>

<div class="u-stack-lg">

    <!-- Internal section nav — lightweight, per spec 35 -->
    <nav class="card card--subtle" aria-label="Preview sections" style="padding:10px 12px;">
        <div class="u-flex" style="gap:8px; flex-wrap:wrap; font-size:0.8125rem;">
            <span class="caption" style="margin-right:4px;">Jump to</span>
            <a href="#foundations" class="btn btn--ghost btn--sm">Foundations</a>
            <a href="#navigation" class="btn btn--ghost btn--sm">Navigation</a>
            <a href="#buttons" class="btn btn--ghost btn--sm">Buttons</a>
            <a href="#forms" class="btn btn--ghost btn--sm">Forms</a>
            <a href="#data" class="btn btn--ghost btn--sm">Data</a>
            <a href="#feedback" class="btn btn--ghost btn--sm">Feedback</a>
            <a href="#overlays" class="btn btn--ghost btn--sm">Overlays</a>
            <a href="#states" class="btn btn--ghost btn--sm">States</a>
        </div>
    </nav>

    <!-- FOUNDATIONS -->
    <section id="foundations" class="u-stack">
        <div class="demo-section__title">Foundations</div>

        <div class="card">
            <div class="card__header">
                <div>
                    <div class="card__title">Typography — operational hierarchy</div>
                    <div class="card__subtitle">Inter / system, tight tracking, confined uppercase</div>
                </div>
            </div>
            <div class="card__body">
                <div class="u-stack">
                    <div>
                        <div class="caption">Page title — 22px / 600 / −0.02em</div>
                        <div class="h1" style="margin-top:6px;">Packages — Manage programs</div>
                    </div>
                    <div>
                        <div class="caption">Section — 20px</div>
                        <div class="h2" style="margin-top:6px;">Section title</div>
                    </div>
                    <div>
                        <div class="caption">Card title — 14px / 600</div>
                        <div class="card__title" style="margin-top:6px;">Card title with precise weight</div>
                    </div>
                    <div>
                        <div class="caption">Body — 14px / 1.5 / muted secondary</div>
                        <p style="margin-top:6px; max-width:60ch; color:var(--color-text-secondary);">Ftpreneur provides personalised wellness guidance. Body copy prioritises readability and calm hierarchy over decorative treatment.</p>
                    </div>
                    <div>
                        <div class="caption">Small &amp; hint — 12–12.5px</div>
                        <p class="small u-muted" style="margin-top:6px;">Helper text, secondary description and table metadata use muted 12–12.5px.</p>
                    </div>
                    <div>
                        <div class="caption">Label — 12px / 600 / −0.01em</div>
                        <div class="field__label" style="margin-top:6px;">Field label</div>
                    </div>
                    <div>
                        <div class="caption">Numeric — tabular, 24px</div>
                        <div style="margin-top:6px; font-variant-numeric: tabular-nums; font-weight:600; font-size:1.375rem; letter-spacing:-0.02em; color:var(--color-text);">₹12,450 <span class="small u-muted" style="font-weight:400;">INR</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header">
                <div class="card__title">Surface &amp; color — layered, restrained</div>
                <span class="badge badge--gold">Canvas → Surface → Raised</span>
            </div>
            <div class="card__body">
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(132px,1fr)); gap:12px;">
                    <?php $swatches = [
                        ['--color-canvas','Canvas','#F1F4F8'],
                        ['--color-surface','Surface','#FFFFFF'],
                        ['--color-surface-subtle','Subtle','#F8FAFC'],
                        ['--color-surface-muted','Muted','#F1F5F9'],
                        ['--color-sidebar','Sidebar','#0E2336'],
                        ['--color-primary','Primary','#0F2A44'],
                        ['--color-accent','Accent','#1E4FA3'],
                        ['--color-gold','Gold','#B9975A'],
                        ['--color-success-soft','Success','#ECFDF5'],
                        ['--color-warning-soft','Warning','#FFFBEB'],
                        ['--color-danger-soft','Danger','#FEF2F2'],
                        ['--color-info-soft','Info','#EFF6FF'],
                    ]; foreach ($swatches as [$var,$label,$hex]): ?>
                    <div style="display:grid; gap:7px;">
                        <div style="height:44px; border-radius:10px; border:1px solid var(--color-border-subtle); background: var(<?= esc($var) ?>); box-shadow: var(--shadow-xs);"></div>
                        <div style="font-size:0.75rem; font-weight:600; letter-spacing:-0.01em;"><?= esc($label) ?> <span style="color:var(--color-text-muted); font-weight:400;"><?= esc($hex) ?></span></div>
                        <div class="caption" style="font-size:0.60rem; text-transform:none; letter-spacing:0.02em;"><?= esc($var) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert--info" style="margin-top:16px;">
                    <svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.35"/><path d="M10 9.5V13M10 7.5h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <div class="alert__content"><span class="alert__title">Strategy</span> Admin is 95% neutral. Navy for sidebar and primary, sapphire sparingly for links/focus. Gold only as tiny accent (border, dot, indicator). Status communicates state, not brand.</div>
                </div>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:12px; margin-top:16px;">
                    <div class="card card--subtle"><div class="card__body"><div class="caption">Level 0 — Flat canvas</div><div class="small u-muted" style="margin-top:6px;">App background #F1F4F8, no shadow</div></div></div>
                    <div class="card"><div class="card__body"><div class="caption">Level 1 — Card</div><div class="small u-muted" style="margin-top:6px;">card shadow: 0 1px 3px + 0 4px 12px, border subtle</div></div></div>
                    <div class="card card--elevated"><div class="card__body"><div class="caption">Level 2 — Floating</div><div class="small u-muted" style="margin-top:6px;">Dropdown elevation, soft floating shadow</div></div></div>
                </div>
            </div>
        </div>
    </section>

    <!-- NAVIGATION -->
    <section id="navigation" class="u-stack">
        <div class="demo-section__title">Navigation</div>

        <div class="demo-cols demo-cols--2">
            <div class="card">
                <div class="card__header"><div class="card__title">Tabs</div><span class="caption">Keyboard, overflow scroll</span></div>
                <div class="card__body" style="display:grid; gap:14px;">
                    <div class="tabs" role="tablist">
                        <button class="tabs__tab tabs__tab--active" role="tab" aria-selected="true">Overview</button>
                        <button class="tabs__tab" role="tab" aria-selected="false">Activity</button>
                        <button class="tabs__tab" role="tab" aria-selected="false">Settings</button>
                    </div>
                    <div style="padding:14px; background:var(--color-surface-subtle); border:1px solid var(--color-divider); border-radius:10px; font-size:0.8125rem; color:var(--color-text-muted);">Tab panel — consistent with page header rhythm.</div>
                </div>
            </div>
            <div class="card">
                <div class="card__header"><div class="card__title">Breadcrumbs</div><span class="caption">Page header companion</span></div>
                <div class="card__body" style="display:grid; gap:14px;">
                    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="#">Admin</a><span class="breadcrumbs__sep">/</span><a href="#">Packages</a><span class="breadcrumbs__sep">/</span><span aria-current="page">UI Demo</span></nav>
                    <div class="page-header" style="margin:0; padding:0; border:0;">
                        <div class="page-header__meta">
                            <div class="page-header__eyebrow">Programme</div>
                            <div class="page-header__title" style="font-size:1.125rem;">Page header</div>
                            <div class="page-header__desc">Eyebrow + Title + Supporting description — actions align naturally on the right.</div>
                        </div>
                        <div class="page-header__actions"><button class="btn btn--primary btn--sm">Action</button></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header"><div class="card__title">Pagination — compact, modern</div><span class="caption">Previous / numbers / next</span></div>
            <div class="card__body">
                <nav class="pagination" aria-label="Pagination" style="border:0; padding:0;">
                    <div class="pagination__info">Page 2 of 12</div>
                    <ul class="pagination__list">
                        <li><a class="pagination__link" href="#">Previous</a></li>
                        <li><a class="pagination__link" href="#">1</a></li>
                        <li><a class="pagination__link pagination__link--active" href="#" aria-current="page">2</a></li>
                        <li><a class="pagination__link" href="#">3</a></li>
                        <li><span class="pagination__link pagination__link--disabled">…</span></li>
                        <li><a class="pagination__link" href="#">12</a></li>
                        <li><a class="pagination__link" href="#">Next</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </section>

    <!-- BUTTONS -->
    <section id="buttons" class="card">
        <div class="card__header">
            <div>
                <div class="card__title">Buttons — refined proportions</div>
                <div class="card__subtitle">38px / 32px, 500–600 weight, 9px radius, restrained motion</div>
            </div>
        </div>
        <div class="card__body">
            <div class="u-stack">
                <div>
                    <div class="caption" style="margin-bottom:8px;">Variants</div>
                    <div class="demo-row">
                        <button class="btn btn--primary">Primary</button>
                        <button class="btn btn--secondary">Secondary</button>
                        <button class="btn btn--ghost">Ghost</button>
                        <button class="btn btn--danger">Danger</button>
                        <button class="btn btn--danger-soft">Danger soft</button>
                        <button class="btn btn--accent">Accent</button>
                    </div>
                </div>
                <div>
                    <div class="caption" style="margin-bottom:8px;">Sizes &amp; states</div>
                    <div class="demo-row">
                        <button class="btn btn--primary btn--sm">Small primary</button>
                        <button class="btn btn--secondary btn--sm">Small secondary</button>
                        <button class="btn btn--primary btn--lg">Large</button>
                        <button class="btn btn--primary" disabled>Disabled</button>
                        <button class="btn btn--primary btn--loading">Loading</button>
                    </div>
                </div>
                <div>
                    <div class="caption" style="margin-bottom:8px;">With icons &amp; icon buttons (36px, aria-label)</div>
                    <div class="demo-row">
                        <button class="btn btn--primary">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            Add package
                        </button>
                        <button class="btn btn--secondary">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.35"/><path d="M10 8v4M8 10h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                            Secondary
                        </button>
                        <button class="icon-btn" aria-label="Edit"><svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M11 4l5 5-7 7H4v-5l7-7Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/></svg></button>
                        <button class="icon-btn" aria-label="More actions"><svg width="16" height="16" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="5" r="1.5" fill="currentColor"/><circle cx="10" cy="10" r="1.5" fill="currentColor"/><circle cx="10" cy="15" r="1.5" fill="currentColor"/></svg></button>
                        <button class="icon-btn icon-btn--ghost" aria-label="Close" data-tooltip="Close"><svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>
                        <button class="icon-btn icon-btn--sm" aria-label="Delete"><svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M4 6h12M6 6V16a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V6M9 9v6M13 9v6M8 6l1-2h2l1 2" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                    </div>
                </div>
                <div class="small u-muted">Focus: tab to see premium ring. Hover shows subtle elevation. Active depresses 0.5px.</div>
            </div>
        </div>
    </section>

    <!-- FORMS -->
    <section id="forms" class="card">
        <div class="card__header"><div class="card__title">Form Controls — tinted, excellent focus</div><span class="caption">Label 12px / hint 12px / error 12px — not heavy</span></div>
        <div class="card__body">
            <div class="form" style="max-width:720px;">
                <div class="form__row form__row--2">
                    <div class="field">
                        <label class="field__label" for="demo-text">Text input</label>
                        <input class="field__input" id="demo-text" placeholder="e.g. Starter Wellness" value="UI DEMO — Demo Package">
                        <div class="field__hint">Helper explains purpose, not placeholder.</div>
                    </div>
                    <div class="field field--error">
                        <label class="field__label field__label--required" for="demo-error">With error</label>
                        <input class="field__input" id="demo-error" value="wrong">
                        <div class="field__error">This field is required.</div>
                    </div>
                </div>

                <div class="form__row form__row--2">
                    <div class="field">
                        <label class="field__label" for="demo-email">Email</label>
                        <input class="field__input" id="demo-email" type="email" value="demo@ftpreneur.local">
                    </div>
                    <div class="field">
                        <label class="field__label" for="demo-select">Select</label>
                        <select class="field__select" id="demo-select"><option>Days</option><option>Weeks</option><option>Months</option></select>
                    </div>
                </div>

                <div class="field">
                    <label class="field__label" for="demo-textarea">Textarea</label>
                    <textarea class="field__textarea" id="demo-textarea" placeholder="Full description (UI DEMO)">Textarea supports longer content with proper hover and focus halo. Background subtly tints before focus.</textarea>
                </div>

                <div class="form__row form__row--2">
                    <div class="field">
                        <label class="field__label" for="demo-pw">Password with toggle</label>
                        <div class="password-field">
                            <input class="field__input" id="demo-pw" type="password" value="DemoPass123!">
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password"><svg data-eye width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M2 10s3-5.5 8-5.5S18 10 18 10s-3 5.5-8 5.5S2 10 2 10Z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.3" stroke="currentColor" stroke-width="1.4"/></svg><svg data-eye-off width="18" height="18" viewBox="0 0 20 20" fill="none" style="display:none"><path d="M3 3l14 14M9.8 5.2a5.5 5.5 0 0 1 5.7 3.8M14.5 10a5.5 5.5 0 0 1-7.1 4.3M2.5 10a9.5 9.5 0 0 1 5.2-4.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></button>
                        </div>
                    </div>
                    <div class="field">
                        <label class="field__label" for="demo-disabled">Disabled</label>
                        <input class="field__input" id="demo-disabled" value="Cannot edit" disabled>
                    </div>
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:18px; padding-top:4px;">
                    <label class="check"><input type="checkbox" class="check__input check__input--box" checked><span class="check__label">Checkbox</span></label>
                    <label class="check"><input type="checkbox" class="check__input check__input--box"><span class="check__label">Unchecked</span></label>
                    <label class="check"><input type="radio" name="demo-radio" class="check__input check__input--radio" checked><span class="check__label">Radio</span></label>
                    <label class="check"><input type="radio" name="demo-radio" class="check__input check__input--radio"><span class="check__label">Radio</span></label>
                    <label class="toggle"><input type="checkbox" class="toggle__input" checked><span class="toggle__label">Toggle</span></label>
                    <label class="toggle"><input type="checkbox" class="toggle__input"><span class="toggle__label">Off</span></label>
                </div>

                <div class="u-flex" style="justify-content:flex-end; gap:12px;">
                    <button type="button" class="btn btn--ghost">Cancel</button>
                    <button type="button" class="btn btn--primary">Save changes</button>
                </div>
            </div>
        </div>
    </section>

    <!-- DATA DISPLAY -->
    <section id="data" class="u-stack">
        <div class="demo-section__title">Data display</div>

        <div style="display:grid; gap:16px; grid-template-columns: repeat(auto-fill, minmax(280px,1fr));">
            <div class="card">
                <div class="card__header">
                    <div>
                        <div class="card__title">Standard</div>
                        <div class="card__subtitle">Header + body + footer</div>
                    </div>
                    <span class="badge badge--neutral">Default</span>
                </div>
                <div class="card__body"><p class="small u-muted">Forms and settings use this composition. Border subtle, shadow card.</p></div>
                <div class="card__footer"><button class="btn btn--ghost btn--sm">Cancel</button><button class="btn btn--primary btn--sm">Confirm</button></div>
            </div>
            <div class="card card--subtle">
                <div class="card__body">
                    <div class="card__title" style="margin-bottom:6px;">Subtle</div>
                    <p class="small u-muted">Secondary grouping without heavy borders — uses muted surface.</p>
                </div>
            </div>
            <div class="card card--interactive">
                <div class="card__body">
                    <div class="card__title" style="margin-bottom:6px;">Interactive</div>
                    <p class="small u-muted">Hover lifts 1px with elevated shadow — for clickable previews.</p>
                </div>
            </div>
        </div>

        <!-- Badges -->
        <div class="demo-cols demo-cols--2">
            <div class="card">
                <div class="card__header"><div class="card__title">Badges — Title Case, dot, soft</div></div>
                <div class="card__body">
                    <div class="demo-row">
                        <span class="badge badge--neutral"><span class="badge__dot"></span> Neutral</span>
                        <span class="badge badge--primary">Primary</span>
                        <span class="badge badge--success"><span class="badge__dot"></span> Active</span>
                        <span class="badge badge--warning"><span class="badge__dot"></span> Pending</span>
                        <span class="badge badge--danger">Failed</span>
                        <span class="badge badge--info">Info</span>
                        <span class="badge badge--gold"><span class="badge__dot"></span> Premium</span>
                    </div>
                    <div class="small u-muted" style="margin-top:10px;">Not ALL CAPS. 11px / 600 / 0.01em, soft backgrounds, optional dot.</div>
                </div>
            </div>
            <div class="card">
                <div class="card__header"><div class="card__title">Stat (future, no fake revenue)</div></div>
                <div class="card__body" style="display:grid; gap:12px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                        <div class="stat"><div class="stat__label">Packages</div><div class="stat__value">—</div><div class="stat__hint">No fake data until Phase 5</div></div>
                        <div class="stat"><div class="stat__label">Orders</div><div class="stat__value" style="color:var(--color-text-muted);">—</div><div class="stat__hint">Pending orders module</div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table — CRM quality -->
        <div class="card" style="overflow:hidden;">
            <div class="card__header">
                <div>
                    <div class="card__title">Table — CRM quality (UI DEMO)</div>
                    <div class="card__subtitle">Subtle header tint, 14px row padding, divider-only, tabular numbers</div>
                </div>
                <button class="btn btn--primary btn--sm">Add package</button>
            </div>
            <div class="table-wrap" style="border:0; border-radius:0; box-shadow:none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Slug</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div class="table__primary">Starter Wellness</div><div class="table__muted">FTP-2026-000001 · 30 days</div></td>
                            <td class="table__muted">starter-wellness</td>
                            <td class="table__mono"><span class="table__primary">₹2,999</span> <span class="table__muted" style="text-decoration:line-through;">₹4,999</span></td>
                            <td><span class="badge badge--success"><span class="badge__dot"></span> Active</span></td>
                            <td>
                                <div class="table__actions">
                                    <div class="dropdown" data-dropdown>
                                        <button class="icon-btn icon-btn--sm" data-dropdown-trigger aria-haspopup="menu" aria-expanded="false" aria-label="Row actions"><svg width="14" height="14" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="5" r="1.4" fill="currentColor"/><circle cx="10" cy="10" r="1.4" fill="currentColor"/><circle cx="10" cy="15" r="1.4" fill="currentColor"/></svg></button>
                                        <div class="dropdown__menu" data-dropdown-menu role="menu">
                                            <button class="dropdown__item" data-dropdown-item role="menuitem">View</button>
                                            <button class="dropdown__item" data-dropdown-item role="menuitem">Edit</button>
                                            <div class="dropdown__divider"></div>
                                            <button class="dropdown__item dropdown__item--danger" data-dropdown-item role="menuitem">Archive</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><div class="table__primary">Premium Guidance</div><div class="table__muted">FTP-2026-000002 · 60 days</div></td>
                            <td class="table__muted">premium-guidance</td>
                            <td class="table__mono table__primary">₹5,499</td>
                            <td><span class="badge badge--neutral">Draft</span></td>
                            <td><div class="table__actions"><button class="icon-btn icon-btn--sm" aria-label="Edit" data-tooltip="Edit"><svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M11 4l5 5-7 7H4v-5l7-7Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/></svg></button></div></td>
                        </tr>
                        <tr>
                            <td><div class="table__primary">Family Plan</div><div class="table__muted">FTP-2026-000003 · 90 days</div></td>
                            <td class="table__muted">family-plan</td>
                            <td class="table__mono table__primary">₹8,999</td>
                            <td><span class="badge badge--warning"><span class="badge__dot"></span> Pending</span></td>
                            <td><div class="table__actions"><span class="badge badge--danger">Failed</span></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination" style="margin:0;">
                <div class="pagination__info">Showing 1–3 of 3 (UI DEMO)</div>
                <ul class="pagination__list">
                    <li><a class="pagination__link pagination__link--disabled" href="#" aria-disabled="true">Previous</a></li>
                    <li><a class="pagination__link pagination__link--active" href="#" aria-current="page">1</a></li>
                    <li><a class="pagination__link" href="#">2</a></li>
                    <li><a class="pagination__link" href="#">Next</a></li>
                </ul>
            </div>
        </div>
    </section>

    <!-- FEEDBACK -->
    <section id="feedback" class="u-stack">
        <div class="demo-section__title">Feedback</div>

        <div class="demo-cols demo-cols--2">
            <div class="card">
                <div class="card__header"><div class="card__title">Alerts — integrated</div></div>
                <div class="card__body" style="display:grid; gap:10px;">
                    <div class="alert alert--success" role="status">
                        <svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 10l3 3 5-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.35"/></svg>
                        <div class="alert__content"><div class="alert__title">Success</div>Package saved — demo only.</div><button class="alert__dismiss" data-alert-dismiss aria-label="Dismiss">×</button>
                    </div>
                    <div class="alert alert--danger" role="alert"><svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6.5V11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="10" cy="13.5" r="1" fill="currentColor"/><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.35"/></svg><div class="alert__content"><div class="alert__title">Error</div>Please correct the fields below.</div><button class="alert__dismiss" data-alert-dismiss aria-label="Dismiss">×</button></div>
                    <div class="alert alert--warning" role="alert"><svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6l5.5 9H4.5L10 6Z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/><path d="M10 9v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg><div class="alert__content"><div class="alert__title">Warning</div>This package has active orders.</div></div>
                    <div class="alert alert--info" role="status"><svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.35"/><path d="M10 9.5V13M10 7.5h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg><div class="alert__content"> Demo data only — UI DEMO.</div></div>
                </div>
            </div>
            <div class="card">
                <div class="card__header"><div class="card__title">Empty State</div></div>
                <div class="empty">
                    <div class="empty__icon"><svg width="22" height="22" viewBox="0 0 20 20" fill="none"><rect x="3" y="4" width="14" height="12" rx="1.4" stroke="currentColor" stroke-width="1.35"/><path d="M7 8h6M7 12h4" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg></div>
                    <h3 class="empty__title">No packages yet</h3>
                    <p class="empty__desc">Add your first package to make it available on the Ftpreneur website.</p>
                    <button class="btn btn--primary btn--sm">Add package</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header"><div class="card__title">Loading &amp; skeleton</div><span class="caption">Respects prefers-reduced-motion</span></div>
            <div class="card__body">
                <div style="display:grid; gap:14px; max-width:520px;">
                    <button class="btn btn--primary btn--loading" style="width:142px;">Saving…</button>
                    <div style="display:grid; gap:8px;">
                        <div class="skeleton skeleton--title"></div>
                        <div class="skeleton skeleton--text" style="width:90%"></div>
                        <div class="skeleton skeleton--text" style="width:74%"></div>
                        <div class="u-flex" style="gap:10px; margin-top:4px;">
                            <div class="skeleton skeleton--avatar"></div>
                            <div style="flex:1; display:grid; gap:6px;"><div class="skeleton skeleton--text"></div><div class="skeleton skeleton--text" style="width:58%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- OVERLAYS -->
    <section id="overlays" class="u-stack">
        <div class="demo-section__title">Overlays</div>

        <div class="demo-cols demo-cols--2">
            <div class="card">
                <div class="card__header"><div class="card__title">Dropdown Menu</div><span class="caption">Esc / click-outside / Arrow keys</span></div>
                <div class="card__body" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                    <div class="dropdown" data-dropdown>
                        <button class="btn btn--secondary" data-dropdown-trigger aria-haspopup="menu" aria-expanded="false">Actions <svg width="12" height="12" viewBox="0 0 20 20" fill="none"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
                        <div class="dropdown__menu" data-dropdown-menu role="menu">
                            <div class="dropdown__label">Demo actions</div>
                            <button class="dropdown__item" data-dropdown-item role="menuitem">View details</button>
                            <button class="dropdown__item" data-dropdown-item role="menuitem">Edit package</button>
                            <div class="dropdown__divider"></div>
                            <button class="dropdown__item dropdown__item--danger" data-dropdown-item role="menuitem">Archive</button>
                        </div>
                    </div>
                    <span class="small u-muted">Also used for topbar account and table row actions.</span>
                </div>
            </div>
            <div class="card">
                <div class="card__header"><div class="card__title">Modal &amp; tooltip</div></div>
                <div class="card__body" style="display:grid; gap:12px;">
                    <p class="small u-muted">Escape, overlay click and close button work. Focus trapped, scroll locked.</p>
                    <div class="demo-row">
                        <button class="btn btn--primary btn--sm" data-modal-open="demo-modal">Open modal</button>
                        <button class="btn btn--danger-soft btn--sm" data-modal-open="confirm-modal">Confirm dialog</button>
                        <button class="btn btn--ghost btn--sm" data-tooltip="Premium tooltip — 11px, 6px radius">Hover for tooltip</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATES -->
    <section id="states" class="card">
        <div class="card__header"><div class="card__title">States — focus, disabled, hover</div><span class="caption">Visible focus, WCAG 2.2 AA</span></div>
        <div class="card__body">
            <div class="u-stack" style="gap:10px; font-size:0.8125rem; color:var(--color-text-muted);">
                <div>Focus <span class="u-code">:focus-visible</span> uses premium ring <span class="u-code">box-shadow: 0 0 0 3px rgba(15,42,68,0.16)</span>. Tab through this page to verify.</div>
                <div>Disabled inputs use muted surface #F1F5F9, 0.92 opacity, not-allowed cursor.</div>
                <div>Loading uses spin border, respects <span class="u-code">prefers-reduced-motion</span> — animation disabled if preferred.</div>
                <div>Rows use hover #F8FAFC, not heavy grid. Sidebar active uses inset gold accent + subtle tint, not giant white pill.</div>
            </div>
        </div>
    </section>

</div>

<!-- Modals -->
<div class="modal-overlay" data-modal="demo-modal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="demo-modal-title">
        <div class="modal__header">
            <div>
                <h2 class="modal__title" id="demo-modal-title">Demo modal</h2>
                <p class="modal__desc">Reusable dialog: overlay, focus trap, body scroll-lock, 18px radius.</p>
            </div>
            <button class="icon-btn icon-btn--ghost" data-modal-close aria-label="Close"><svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>
        </div>
        <div class="modal__body">
            <p class="small u-muted">Body scrolls if content overflows. Footer has strong hierarchy.</p>
            <div class="card card--subtle" style="margin-top:14px;"><div class="card__body" style="padding:12px;"><span class="u-code">UI DEMO</span> — no production data.</div></div>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" data-modal-close>Cancel</button>
            <button class="btn btn--primary" data-modal-close>Confirm</button>
        </div>
    </div>
</div>

<div class="modal-overlay" data-modal="confirm-modal" aria-hidden="true">
    <div class="modal modal--sm" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div class="modal__header">
            <div>
                <h2 class="modal__title" id="confirm-title">Archive package?</h2>
                <p class="modal__desc">Demo confirmation — no data will change. Danger is clear but not screaming red.</p>
            </div>
            <button class="icon-btn icon-btn--ghost" data-modal-close aria-label="Close"><svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>
        </div>
        <div class="modal__body">
            <div class="alert alert--warning"><div class="alert__content">UI DEMO — package “Starter Wellness” will be archived (demo only).</div></div>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" data-modal-close>Cancel</button>
            <button class="btn btn--danger" data-modal-close>Archive</button>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

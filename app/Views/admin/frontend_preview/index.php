<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title ?? 'Frontend Design System Preview') ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Actual Production Frontend Design System CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/frontend/css/tokens.css?v=6.0') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/frontend/css/base.css?v=6.0') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/frontend/css/layout.css?v=6.0') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/frontend/css/components.css?v=6.0') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/frontend/css/utilities.css?v=6.0') ?>">

    <style>
        .dev-banner {
            background-color: #1E293B;
            color: #F8FAFC;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
        }
        .dev-badge {
            background-color: #00B79B;
            color: #FFFFFF;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .preview-section-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .color-swatch-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
        }
        .color-swatch {
            border-radius: 8px;
            padding: 1rem;
            color: #FFFFFF;
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="dev-banner">
        <div>
            <span class="dev-badge">DEV ONLY</span>
            <strong style="margin-left: 0.5rem;">Production Frontend Design System Preview</strong>
            <span style="color: #94A3B8; margin-left: 0.5rem;">(Phase 6A Foundation)</span>
        </div>
        <div>
            <a href="<?= site_url('admin') ?>" style="color: #60A5FA; font-weight: 500;">&larr; Back to Admin Dashboard</a>
        </div>
    </div>

    <main class="container section">
        <!-- Header -->
        <div class="mb-8">
            <span class="kicker">Design Tokens &amp; Component Primitives</span>
            <h1 class="display-title">Production Frontend System</h1>
            <p class="body-lead">
                Derived directly from the approved Brand Guidelines. Used to build all Phase 6 public landing page sections.
            </p>
        </div>

        <!-- 1. Color Palette Tokens -->
        <div class="preview-section-box">
            <h2 class="section-title mb-4">1. Brand Color Palette</h2>
            <div class="color-swatch-grid">
                <div class="color-swatch" style="background-color: var(--color-primary);">
                    Primary<br><small style="opacity: 0.8;">#2E47FF</small>
                </div>
                <div class="color-swatch" style="background-color: var(--color-secondary);">
                    Secondary<br><small style="opacity: 0.8;">#00B79B</small>
                </div>
                <div class="color-swatch" style="background-color: var(--color-accent); color: #141B31;">
                    Accent<br><small style="opacity: 0.8;">#FF9E2C</small>
                </div>
                <div class="color-swatch" style="background-color: var(--color-ink);">
                    Ink<br><small style="opacity: 0.8;">#141B31</small>
                </div>
                <div class="color-swatch" style="background-color: var(--color-bg-canvas); color: #0F172A; border: 1px solid #E2E8F0;">
                    Canvas<br><small style="opacity: 0.8;">#F8FAFC</small>
                </div>
                <div class="color-swatch" style="background-color: var(--color-bg-subdued); color: #0F172A; border: 1px solid #E2E8F0;">
                    Subdued<br><small style="opacity: 0.8;">#F1F5F9</small>
                </div>
            </div>
        </div>

        <!-- 2. Typography Hierarchy -->
        <div class="preview-section-box">
            <h2 class="section-title mb-4">2. Typography Hierarchy</h2>
            <div class="flex flex-col gap-4">
                <div>
                    <span class="kicker">Kicker / Eyebrow Text</span>
                    <h1>Display Heading (h1)</h1>
                </div>
                <div>
                    <h2>Section Heading (h2)</h2>
                </div>
                <div>
                    <h3>Card &amp; Subsection Heading (h3)</h3>
                </div>
                <div>
                    <p class="body-lead">Body Lead Paragraph: Scientific nutrition protocols and customized strength training designed for long-term health.</p>
                </div>
                <div>
                    <p>Standard Body Copy: Every program is tailored around bio-individual data, metabolic health, and progressive adaptation.</p>
                </div>
                <div>
                    <p class="meta-text">Meta Text / Secondary Caption (Small)</p>
                </div>
            </div>
        </div>

        <!-- 3. Button Primitives -->
        <div class="preview-section-box">
            <h2 class="section-title mb-4">3. Button Primitives</h2>
            <div class="flex items-center gap-4" style="flex-wrap: wrap;">
                <button type="button" class="btn btn--primary">Primary Action</button>
                <button type="button" class="btn btn--secondary">Secondary Action</button>
                <button type="button" class="btn btn--accent">Accent Action</button>
                <button type="button" class="btn btn--outline">Outline Action</button>
                <button type="button" class="btn btn--subtle">Subtle Action</button>
                <button type="button" class="btn btn--text">Text Link Action</button>
                <button type="button" class="btn btn--primary" disabled>Disabled Action</button>
            </div>
        </div>

        <!-- 4. Badges & Pills -->
        <div class="preview-section-box">
            <h2 class="section-title mb-4">4. Badges &amp; Pills</h2>
            <div class="flex items-center gap-4" style="flex-wrap: wrap;">
                <span class="badge badge--primary">Primary Badge</span>
                <span class="badge badge--secondary">Secondary Badge</span>
                <span class="badge badge--accent">Accent Badge</span>
                <span class="badge badge--subdued">Subdued Badge</span>
                <span class="badge badge--dark">Dark Badge</span>
            </div>
        </div>

        <!-- 5. Cards & Surfaces -->
        <div class="preview-section-box">
            <h2 class="section-title mb-4">5. Cards &amp; Surfaces</h2>
            <div class="grid grid--3">
                <div class="card card--interactive">
                    <span class="badge badge--primary mb-2" style="align-self: flex-start;">Standard Card</span>
                    <h3>Interactive Surface</h3>
                    <p class="mt-2">Hover effect with smooth elevation transform and subtle border highlight.</p>
                </div>
                <div class="card card--featured">
                    <span class="badge badge--accent mb-2" style="align-self: flex-start;">Featured</span>
                    <h3>Featured Surface</h3>
                    <p class="mt-2">Border accent with primary brand highlight for high-priority items.</p>
                </div>
                <div class="card card--dark">
                    <span class="badge badge--dark mb-2" style="align-self: flex-start;">Dark Surface</span>
                    <h3>Dark Surface</h3>
                    <p class="mt-2">High contrast dark container for contrast blocks and callouts.</p>
                </div>
            </div>
        </div>
    </main>

    <script src="<?= base_url('assets/frontend/js/main.js?v=6.0') ?>" defer></script>
</body>
</html>

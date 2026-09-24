<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title ?? 'Admin — Ftpreneur') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="/assets/admin/css/tokens.css?v=5.0">
    <link rel="stylesheet" href="/assets/admin/css/base.css?v=5.0">
    <link rel="stylesheet" href="/assets/admin/css/layout.css?v=5.0">
    <link rel="stylesheet" href="/assets/admin/css/components.css?v=5.0">
    <link rel="stylesheet" href="/assets/admin/css/utilities.css?v=5.0">
    <?= $this->renderSection('head') ?>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to content</a>

    <div class="admin-shell" data-admin-shell>
        <?= $this->include('admin/partials/sidebar') ?>
        <div class="admin-overlay" data-sidebar-overlay aria-hidden="true"></div>

        <?= $this->include('admin/partials/topbar') ?>

        <main id="main-content" class="admin-main" tabindex="-1">
            <div class="admin-main__inner">
                <div class="admin-flash" aria-live="polite" aria-atomic="true">
                    <?= $this->include('admin/partials/flash') ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert--danger" role="alert">
                            <svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6.5V10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="10" cy="13.5" r="1" fill="currentColor"/><path d="M10 16A6 6 0 1 0 10 4a6 6 0 0 0 0 12Z" stroke="currentColor" stroke-width="1.4"/></svg>
                            <div class="alert__content"><?= esc($error) ?></div>
                            <button class="alert__dismiss" data-alert-dismiss aria-label="Dismiss"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($message)): ?>
                        <div class="alert alert--success" role="status" data-auto-dismiss>
                            <svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 10l3 3 5-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.4"/></svg>
                            <div class="alert__content"><?= esc($message) ?></div>
                            <button class="alert__dismiss" data-alert-dismiss aria-label="Dismiss"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
                        </div>
                    <?php endif; ?>
                </div>

                <?= $this->renderSection('content') ?>
            </div>
        </main>
    </div>

    <script src="/assets/admin/js/navigation.js?v=5.0" defer></script>
    <script src="/assets/admin/js/dropdown.js?v=5.0" defer></script>
    <script src="/assets/admin/js/modal.js?v=5.0" defer></script>
    <script src="/assets/admin/js/admin.js?v=5.0" defer></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>

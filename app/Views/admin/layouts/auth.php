<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($title ?? 'Admin Sign In — Ftpreneur') ?></title>
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
    <?= $this->renderSection('content') ?>
    <script src="/assets/admin/js/admin.js?v=5.0" defer></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>

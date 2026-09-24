<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <?= $this->include('frontend/partials/head') ?>
</head>
<body class="<?= esc($body_class ?? '') ?>">
    <!-- Accessibility Skip Link -->
    <a href="#main-content" class="skip-to-content">Skip to main content</a>

    <!-- Main Content Landmark -->
    <main id="main-content" role="main">
        <?= $this->renderSection('content') ?>
    </main>

    <!-- Global Scripts -->
    <?= $this->include('frontend/partials/scripts') ?>
</body>
</html>

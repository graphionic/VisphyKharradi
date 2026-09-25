<?php
/**
 * Ftpreneur Public Frontend — Head Partial
 *
 * Slots & Dynamic Variables:
 * - $title (string)
 * - $meta_description (string)
 * - $canonical_url (string)
 * - $og_title (string)
 * - $og_description (string)
 * - $og_image (string)
 * - $robots (string)
 */

$pageTitle = esc($title ?? "Ftpreneur — Visphy Kharradi's Nutrition, Strength & Disease Management Plan");
$metaDesc  = esc($meta_description ?? "Transform your health through scientific nutrition, strength training, and lifestyle disease management with Visphy Kharradi.");
$canonical = esc($canonical_url ?? current_url());
$ogTitle   = esc($og_title ?? $pageTitle);
$ogDesc    = esc($og_description ?? $metaDesc);
$ogImage   = esc($og_image ?? base_url('assets/frontend/images/og-default.jpg'));
$robotsVal = esc($robots ?? 'index, follow');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="<?= $robotsVal ?>">
<title><?= $pageTitle ?></title>
<meta name="description" content="<?= $metaDesc ?>">
<link rel="canonical" href="<?= $canonical ?>">

<!-- Open Graph / Social -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $canonical ?>">
<meta property="og:title" content="<?= $ogTitle ?>">
<meta property="og:description" content="<?= $ogDesc ?>">
<meta property="og:image" content="<?= $ogImage ?>">
<meta property="og:site_name" content="Ftpreneur">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $ogTitle ?>">
<meta name="twitter:description" content="<?= $ogDesc ?>">
<meta name="twitter:image" content="<?= $ogImage ?>">

<!-- CSRF Protection -->
<?= csrf_meta() ?>

<!-- Preloads -->
<link rel="preload" href="<?= base_url('assets/frontend/fonts/bricolage-grotesque.woff2') ?>" as="font" type="font/woff2" crossorigin />
<link rel="preload" href="<?= base_url('assets/frontend/images/visphy-cutout.webp') ?>" as="image" />

<!-- Arena Hero Fonts & Styles -->
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/fonts.css') ?>" />
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/hero.css') ?>" />
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/credibility.css?v=6.0') ?>" />
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/reality.css?v=1.0') ?>" />

<!-- Production Frontend CSS -->
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/tokens.css?v=6.0') ?>">
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/base.css?v=6.0') ?>">
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/layout.css?v=6.0') ?>">
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/components.css?v=6.0') ?>">
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/utilities.css?v=6.0') ?>">

<script>document.documentElement.classList.replace("no-js", "js");</script>

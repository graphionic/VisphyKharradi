<?php
use App\Services\PackageService;

/**
 * Concept 04 — "Premium Visual Program Grid" Production Renderer
 * Full-bleed poster cards with diagonal panels, checkerboard rhythm, initial 6 visible,
 * Show More expansion, and bottom detail dossier.
 */
$totalPackages = count($packages);
$edPrograms = [];

foreach ($packages as $i => $pkg) {
    $numInt = $i + 1;
    $isFeatured = (int)($pkg['is_featured'] ?? 0) === 1;
    $badgeText = PackageService::badgeLabel($pkg['badge'] ?? null);

    $sellingPrice = (float)($pkg['selling_price'] ?? 0);
    $regularPrice = !empty($pkg['regular_price']) ? (float)$pkg['regular_price'] : null;

    $durVal = (int)($pkg['duration_value'] ?? 12);
    $durUnit = strtolower((string)($pkg['duration_unit'] ?? 'weeks'));
    $weeks = ($durUnit === 'months' || $durUnit === 'month') ? ($durVal * 4) : $durVal;

    $imgUrl = !empty($pkg['featured_image'])
        ? base_url($pkg['featured_image'])
        : base_url('assets/frontend/images/vispy-hercules-pillars-record.jpg');

    $edPrograms[] = [
        'number'            => $numInt,
        'id'                => (int)$pkg['id'],
        'name'              => $pkg['name'],
        'slug'              => $pkg['slug'],
        'category'          => !empty($badgeText) ? $badgeText : 'Personalised',
        'eyebrow'           => 'Personalised Program',
        'purpose'           => $pkg['short_description'],
        'short_description' => $pkg['short_description'],
        'full_description'  => $pkg['full_description'],
        'price'             => $sellingPrice,
        'discountPrice'     => $sellingPrice,
        'regular_price'     => $regularPrice,
        'durationWeeks'     => $weeks,
        'format'            => '1:1 Personalised',
        'reviewFrequency'   => 'Every 2 weeks',
        'featured'          => $isFeatured,
        'featuredLabel'     => 'Signature Program',
        'google_form_url'   => $pkg['google_form_url'],
        'cta_label'         => $pkg['cta_label'],
        'image'             => $imgUrl,
        'features'          => $pkg['features'] ?? [],
    ];
}

$edProgramsJson = json_encode($edPrograms, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<!-- Concept 04 Stylesheet -->
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/packages-concept-04.css?v=1.0') ?>" />

<!-- Concept 04 Data Payload -->
<script>
  window.ED_PROGRAMS = <?= $edProgramsJson ?>;
</script>

<section class="pv-sec-c4" id="programs" aria-labelledby="pv-heading">
  <div class="pv__inner">

    <!-- Intro Header -->
    <header class="pv__intro">
      <div>
        <p class="pv__eyebrow"><i aria-hidden="true"></i>04 / PROGRAM EDITIONS</p>
        <h2 class="pv__heading" id="pv-heading">PROGRAMS, BUILT <em>AROUND YOU.</em></h2>
      </div>
      <div class="pv__intro-r">
        <p class="pv__lede">Not generic plans. Structured support around your health, your routine and your goals — every program is personalised after a 1:1 assessment.</p>
        <p class="pv__qual">Programs are personalised. Individual outcomes vary.</p>
      </div>
    </header>

    <!-- Category Filter Bar -->
    <div class="pv__bar">
      <div class="pv__filters" role="group" aria-label="Filter programs by category">
        <span class="pv__filters-line" aria-hidden="true"></span>
      </div>
      <p class="pv__status" aria-live="polite">ACTIVE EDITIONS: <b><?= sprintf('%02d', $totalPackages) ?></b></p>
    </div>

    <!-- Cards Grid -->
    <div class="pv__grid" role="list"></div>

    <!-- Show More Controls -->
    <div class="pv__more">
      <div class="pv__more-meta">
        <span class="pv__more-count"><b>06</b> / <?= sprintf('%02d', $totalPackages) ?></span>
        <span class="pv__more-track" aria-hidden="true"><i></i></span>
      </div>
      <button class="pv__more-btn" type="button">
        <span class="pv__more-label">Show more programs</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6" /></svg>
      </button>
    </div>

  </div>
</section>

<!-- Poster Card Template -->
<template id="pv-card-tpl-c4">
  <article class="pv-card" role="listitem">
    <div class="pv-card__media">
      <img class="pv-card__img" alt="" loading="lazy" decoding="async" />
      <span class="pv-card__rail" aria-hidden="true"></span>
      <p class="pv-card__tag"><b data-f="number">01</b><i aria-hidden="true"></i><span data-f="category">Personalised</span></p>
      <p class="pv-card__sig"><i aria-hidden="true"></i><span data-f="featuredLabel">Signature Program</span></p>
      <span class="pv-card__wk" aria-hidden="true"><b data-f="durationWeeks">12</b> wk</span>
      <span class="pv-card__num" data-f="number" aria-hidden="true">01</span>
    </div>
    <div class="pv-card__panel">
      <h3 class="pv-card__name" data-f="name">Program Name</h3>
      <p class="pv-card__line" data-f="line">Short description</p>
      <dl class="pv-card__facts">
        <div><dt>Duration</dt><dd><span data-f="durationWeeks">12</span> weeks</dd></div>
        <div><dt>Investment</dt><dd><s data-f="priceWas"></s><span data-f="price">₹14,999</span></dd></div>
      </dl>
    </div>
    <button class="pv-card__cta" type="button">
      <span class="pv-card__cta-t">Explore program</span>
      <span class="pv-card__cta-a" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h13M13 6l6 6-6 6" /></svg></span>
    </button>
  </article>
</template>

<!-- ================================================================
     BOTTOM DRAWER — PREMIUM PROGRAM PROFILE
     ================================================================ -->
<div class="pvd" id="pv-drawer" data-accent="ultra" hidden>
  <div class="pvd__scrim" data-close></div>
  <div class="pvd__sheet" role="dialog" aria-modal="true" aria-labelledby="pvd-title" tabindex="-1">

    <!-- Header Bar -->
    <header class="pvd__head">
      <div class="pvd__grab" aria-hidden="true"><span></span></div>
      <div class="pvd__id">
        <p class="pvd__kicker"><i aria-hidden="true"></i>Program <b data-f="number">01</b> <span>/</span> <span data-f="category">Personalised</span></p>
        <div class="pvd__title-row">
          <h2 class="pvd__title" id="pvd-title" data-f="name">Program Title</h2>
          <span class="pvd__wk"><b data-f="durationWeeks">12</b> weeks</span>
        </div>
      </div>
      <div class="pvd__ctrl">
        <button class="pvd__step" type="button" data-prev><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H6M11 6l-6 6 6 6" /></svg><span>Previous</span></button>
        <button class="pvd__step" type="button" data-next><span>Next</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6" /></svg></button>
        <span class="pvd__div" aria-hidden="true"></span>
        <button class="pvd__close" type="button" data-close><span>Close</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg></button>
      </div>
    </header>

    <!-- Drawer Body -->
    <div class="pvd__body">
      <aside class="pvd__visual">
        <div class="pvd__photo">
          <img class="pvd__img" alt="" />
          <span class="pvd__rail" aria-hidden="true"></span>
          <span class="pvd__num" data-f="number" aria-hidden="true">01</span>
          <p class="pvd__sig"><i aria-hidden="true"></i><span data-f="featuredLabel">Signature Program</span></p>
          <div class="pvd__cap">
            <span data-f="eyebrowCase">Personalised Program</span>
            <span><span data-f="durationWeeks">12</span> wk · <span data-f="format">1:1 Personalised</span></span>
          </div>
        </div>
      </aside>

      <div class="pvd__main">
        <nav class="pvd__nav" aria-label="Program sections">
          <a href="#pvd-s1" data-sec="1" class="is-active">Overview</a>
          <a href="#pvd-s2" data-sec="2">Focus</a>
          <a href="#pvd-s3" data-sec="3">Included</a>
          <a href="#pvd-s4" data-sec="4">Journey</a>
          <a href="#pvd-s5" data-sec="5">Investment</a>
          <span class="pvd__nav-line" aria-hidden="true"><i></i></span>
        </nav>

        <div class="pvd__content">
          <section class="pvd__sec" id="pvd-s1" data-sec="1">
            <p class="pvd__label">Overview</p>
            <p class="pvd__lead" data-f="summary"></p>
            <p class="pvd__note" data-f="healthNote"></p>
            <div class="pvd__who">
              <p class="pvd__label">Who it's for</p>
              <p data-f="suitableFor"></p>
            </div>
            <dl class="pvd__stats">
              <div><dt>Duration</dt><dd><b data-f="durationWeeks">12</b><span>weeks</span></dd></div>
              <div><dt>Format</dt><dd><b>1:1</b><span>personalised</span></dd></div>
              <div><dt>Reviews</dt><dd><b data-f="reviewEvery">2</b><span data-f="reviewUnit">weekly reviews</span></dd></div>
            </dl>
          </section>

          <section class="pvd__sec" id="pvd-s2" data-sec="2">
            <p class="pvd__label">Focus</p>
            <h3 class="pvd__h">Program emphasis</h3>
            <div class="pvd__mix" role="img" aria-label="Program emphasis by discipline"></div>
            <ul class="pvd__mix-key"></ul>
            <p class="pvd__label pvd__label--sp">Focus areas</p>
            <ul class="pvd__chips"></ul>
          </section>

          <section class="pvd__sec" id="pvd-s3" data-sec="3">
            <p class="pvd__label">What's included</p>
            <h3 class="pvd__h">Everything in the program</h3>
            <ul class="pvd__inc"></ul>
          </section>

          <section class="pvd__sec" id="pvd-s4" data-sec="4">
            <p class="pvd__label">Program journey</p>
            <h3 class="pvd__h">How the weeks unfold</h3>
            <div class="pvd__track">
              <ol class="pvd__stages"></ol>
              <div class="pvd__rail-wrap" aria-hidden="true"><span class="pvd__rail-fill"></span><div class="pvd__weeks"></div></div>
            </div>
            <p class="pvd__stage-note" aria-live="polite"></p>
            <p class="pvd__small">Indicative structure. Your weeks are personalised after the assessment.</p>
          </section>

          <section class="pvd__sec pvd__sec--last" id="pvd-s5" data-sec="5">
            <p class="pvd__label">Investment</p>
            <div class="pvd__inv">
              <div>
                <p class="pvd__inv-price"><s data-f="priceWas"></s><b data-f="price">₹14,999</b></p>
                <p class="pvd__inv-sub"><span data-f="durationWeeks">12</span> weeks · <span data-f="format">1:1 Personalised</span> · reviews <span data-f="reviewLc">every 2 weeks</span></p>
              </div>
              <ul class="pvd__inv-list"></ul>
            </div>
            <button class="pvd__nextp" type="button" data-next>
              <span class="pvd__nextp-img"><img alt="" /></span>
              <span class="pvd__nextp-t"><small>Next program · <span class="pvd__nextp-n">02</span></small><b class="pvd__nextp-name">Program 2</b></span>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6" /></svg>
            </button>
          </section>
        </div>
      </div>
    </div>

    <!-- Persistent Footer Bar -->
    <footer class="pvd__foot">
      <div class="pvd__foot-l">
        <p class="pvd__foot-meta"><b><span data-f="durationWeeks">12</span> weeks</b><span data-f="format">1:1 Personalised</span></p>
        <p class="pvd__foot-qual">Programs are personalised. Individual outcomes vary.</p>
      </div>
      <div class="pvd__foot-r">
        <p class="pvd__foot-price"><s data-f="priceWas"></s><b data-f="price">₹14,999</b></p>
        <button class="pvd__talk" type="button" data-talk>Talk to us first</button>
        <button class="pvd__cta" type="button" data-start>
          <span class="pvd__cta-l">Start this program</span>
          <span class="pvd__cta-ok" aria-hidden="true">Program selected</span>
          <span class="pvd__cta-a" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h13M13 6l6 6-6 6" /></svg></span>
        </button>
      </div>
    </footer>

    <div class="pvd__toast" role="status" aria-live="polite"></div>
  </div>
</div>

<!-- Concept 04 Script -->
<script src="<?= base_url('assets/frontend/js/packages-concept-04.js?v=1.0') ?>" defer></script>

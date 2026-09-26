<?php
use App\Services\PackageService;

/**
 * Concept 01 — "The Training Log" Production Renderer
 * High-impact editorial training-log cards, 3-column desktop grid, initial 6 visible,
 * Show More expansion, and bottom detail dossier.
 */
$totalPackages = count($packages);
$pkPrograms = [];

foreach ($packages as $i => $pkg) {
    $numInt = $i + 1;
    $isFeatured = (int)($pkg['is_featured'] ?? 0) === 1;
    $badgeText = PackageService::badgeLabel($pkg['badge'] ?? null);

    $sellingPrice = (float)($pkg['selling_price'] ?? 0);
    $regularPrice = !empty($pkg['regular_price']) ? (float)$pkg['regular_price'] : null;

    $durVal = (int)($pkg['duration_value'] ?? 12);
    $durUnit = strtolower((string)($pkg['duration_unit'] ?? 'weeks'));
    $weeks = ($durUnit === 'months' || $durUnit === 'month') ? ($durVal * 4) : $durVal;

    $accent = $isFeatured ? 'ultra' : ($i % 3 === 0 ? 'ultra' : ($i % 3 === 1 ? 'mint' : 'marigold'));

    $imgUrl = !empty($pkg['featured_image'])
        ? base_url($pkg['featured_image'])
        : base_url('assets/frontend/images/vispy-hercules-pillars-record.jpg');

    $pkPrograms[] = [
        'n'                 => $numInt,
        'id'                => (int)$pkg['id'],
        'name'              => $pkg['name'],
        'slug'              => $pkg['slug'],
        'category'          => !empty($badgeText) ? $badgeText : 'Personalised',
        'purpose'           => $pkg['short_description'],
        'short_description' => $pkg['short_description'],
        'full_description'  => $pkg['full_description'],
        'price'             => $sellingPrice,
        'regular_price'     => $regularPrice,
        'weeks'             => $weeks,
        'featured'          => $isFeatured,
        'accent'            => $accent,
        'google_form_url'   => $pkg['google_form_url'],
        'cta_label'         => $pkg['cta_label'],
        'featured_image'    => $imgUrl,
        'features'          => $pkg['features'] ?? [],
    ];
}

$pkProgramsJson = json_encode($pkPrograms, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<!-- Concept 01 Stylesheet -->
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/packages-concept-01.css?v=1.0') ?>" />

<!-- Concept 01 Payload -->
<script>
  window.PK_PROGRAMS = <?= $pkProgramsJson ?>;
</script>

<section class="pk-sec-c1" id="programs" aria-labelledby="pk-title">
  <div class="pk__inner">

    <!-- Intro Header -->
    <header class="pk__intro">
      <div class="pk__intro-main">
        <p class="pk__eyebrow"><span class="pk__eyebrow-n">04</span> / PROGRAM EDITIONS</p>
        <h2 class="pk__title" id="pk-title"><span>PROGRAMS, BUILT</span> <span class="pk__title-accent">AROUND YOU.</span></h2>
      </div>
      <div class="pk__intro-side">
        <p class="pk__lede">Not generic plans. Structured support around your health, your routine and your goals — every program is personalised after a 1:1 assessment.</p>
        <p class="pk__qual">Programs are personalised. Individual outcomes vary.</p>
      </div>
    </header>

    <!-- Category Filter & Legend Bar -->
    <div class="pk__bar">
      <div class="pk__filters" role="group" aria-label="Filter programs by category"></div>
      <p class="pk__legend" aria-hidden="true"><i></i>1 tick = 1 week</p>
    </div>

    <!-- Cards Grid -->
    <ol class="pk__grid" aria-label="Programs"></ol>

    <!-- Show More Controls -->
    <div class="pk__more">
      <div class="pk__progress" aria-hidden="true"></div>
      <p class="pk__count" aria-live="polite"><b class="pk__count-n">06</b> / <span class="pk__count-t"><?= sprintf('%02d', $totalPackages) ?></span> programs</p>
      <button class="pk__more-btn" type="button">
        <span class="pk__more-t">Show more programs</span>
        <span class="pk__more-i" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 5v14M6 13l6 6 6-6" /></svg></span>
      </button>
    </div>

  </div>
</section>

<!-- =====================================================
     BOTTOM DRAWER — PROGRAM DOSSIER
     ===================================================== -->
<div class="pkd" id="pk-drawer" hidden>
  <div class="pkd__backdrop" data-close></div>

  <div class="pkd__sheet" role="dialog" aria-modal="true" aria-labelledby="pkd-title" tabindex="-1">
    <div class="pkd__grab" aria-hidden="true"><span></span></div>

    <!-- Header Bar -->
    <div class="pkd__bar">
      <p class="pkd__crumb"><span class="pkd__crumb-dot" aria-hidden="true"></span>Program // <b data-f="n">01</b> <span class="pkd__crumb-sep">·</span> <span data-f="category">Personalised</span></p>
      <div class="pkd__ctrl">
        <button class="pkd__nav" type="button" data-prev aria-label="Previous program"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" /></svg></button>
        <button class="pkd__nav" type="button" data-next aria-label="Next program"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg></button>
        <button class="pkd__close" type="button" data-close><span>Close</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg></button>
      </div>
    </div>

    <!-- Drawer Body -->
    <div class="pkd__body">

      <!-- Left Column: Identity -->
      <aside class="pkd__id" aria-label="Program identity">
        <span class="pkd__num" data-f="n" aria-hidden="true">01</span>
        <div class="pkd__id-main">
          <p class="pkd__cat pkd__r" data-f="category">Personalised</p>
          <h2 class="pkd__title pkd__r" id="pkd-title" data-f="name">Program Name</h2>
          <p class="pkd__tagline pkd__r">Personalised Nutrition + Strength + Lifestyle</p>
          <div class="pkd__track pkd__r" data-f="track" aria-hidden="true"></div>
          <p class="pkd__weeks pkd__r"><b data-f="weeks">12</b>-week program</p>
          <div class="pkd__suit pkd__r">
            <span>Suitable for</span>
            <p data-f="suitable">Anyone seeking scientific guidance.</p>
          </div>
        </div>
      </aside>

      <!-- Center Column: Dossier -->
      <div class="pkd__main">
        <nav class="pkd__index" aria-label="Program sections">
          <ol>
            <li><a href="#pkd-s1" data-i="0"><span>01</span> Overview</a></li>
            <li><a href="#pkd-s2" data-i="1"><span>02</span> Focus</a></li>
            <li><a href="#pkd-s3" data-i="2"><span>03</span> Included</a></li>
            <li><a href="#pkd-s4" data-i="3"><span>04</span> Journey</a></li>
            <li><a href="#pkd-s5" data-i="4"><span>05</span> Duration</a></li>
            <li><a href="#pkd-s6" data-i="5"><span>06</span> Investment</a></li>
          </ol>
          <span class="pkd__rail" aria-hidden="true"><i></i></span>
        </nav>

        <div class="pkd__scroll">
          <div class="pkd__content">

            <section class="pkd__sec pkd__r" id="pkd-s1" aria-labelledby="pkd-h1">
              <h3 class="pkd__h" id="pkd-h1"><span>01</span> / Overview</h3>
              <p class="pkd__lead" data-f="purpose"></p>
              <p class="pkd__text" data-f="overview"></p>
              <p class="pkd__medical" data-f="medical">Designed to work alongside your doctor's care. It does not replace medical advice, treatment or medication.</p>
            </section>

            <section class="pkd__sec pkd__r" id="pkd-s2" aria-labelledby="pkd-h2">
              <h3 class="pkd__h" id="pkd-h2"><span>02</span> / What we work on</h3>
              <ul class="pkd__work" data-f="work"></ul>
            </section>

            <section class="pkd__sec pkd__r" id="pkd-s3" aria-labelledby="pkd-h3">
              <h3 class="pkd__h" id="pkd-h3"><span>03</span> / What's included</h3>
              <ul class="pkd__inc" data-f="includes"></ul>
            </section>

            <section class="pkd__sec pkd__r" id="pkd-s4" aria-labelledby="pkd-h4">
              <h3 class="pkd__h" id="pkd-h4"><span>04</span> / Program journey</h3>
              <ol class="pkd__journey" data-f="journey"></ol>
            </section>

            <section class="pkd__sec pkd__r" id="pkd-s5" aria-labelledby="pkd-h5">
              <h3 class="pkd__h" id="pkd-h5"><span>05</span> / Duration</h3>
              <p class="pkd__big"><b data-f="weeks">12</b> weeks</p>
              <p class="pkd__text" data-f="durationNote"></p>
            </section>

            <section class="pkd__sec pkd__r" id="pkd-s6" aria-labelledby="pkd-h6">
              <h3 class="pkd__h" id="pkd-h6"><span>06</span> / Investment</h3>
              <p class="pkd__big" data-f="price">₹14,999</p>
              <p class="pkd__text">One program fee for the full <span data-f="weeks">12</span> weeks.</p>
              <button class="pkd__cta pkd__cta--inline" type="button" data-choose><span>Start this program</span><i aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" /></svg></i></button>
            </section>

          </div>
        </div>
      </div>

      <!-- Right Column: Decision Panel -->
      <aside class="pkd__side pkd__r" aria-label="Choose this program">
        <dl class="pkd__facts">
          <div><dt>Duration</dt><dd><span data-f="weeks">12</span> weeks</dd></div>
          <div><dt>Format</dt><dd>1:1 personalised</dd></div>
          <div><dt>Reviews</dt><dd>Every 2 weeks</dd></div>
        </dl>
        <p class="pkd__price"><small>Investment</small><span data-f="price">₹14,999</span></p>
        <button class="pkd__cta" type="button" data-choose><span>Choose this program</span><i aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" /></svg></i></button>
        <a class="pkd__talk" href="#talk">Talk to us first</a>
        <p class="pkd__qual">Programs are personalised. Individual outcomes vary.</p>
        <button class="pkd__next" type="button" data-next>
          <span class="pkd__next-l">Next program</span>
          <span class="pkd__next-n"><b data-f="nextN">02</b> <span data-f="nextName">Program 2</span></span>
        </button>
      </aside>

    </div>

    <!-- Mobile Dock -->
    <div class="pkd__dock">
      <p><small>From</small><b data-f="price">₹14,999</b></p>
      <button class="pkd__dock-cta" type="button" data-choose>Choose program <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></button>
    </div>

    <p class="pkd__toast" role="status" aria-live="polite"></p>
  </div>
</div>

<!-- Concept 01 Script -->
<script src="<?= base_url('assets/frontend/js/packages-concept-01.js?v=1.0') ?>" defer></script>

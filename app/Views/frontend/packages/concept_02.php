<?php
use App\Services\PackageService;

/**
 * Concept 02 — Photography Editorial Production Renderer
 * High-impact photography, 3-column desktop grid, initial 6 visible, Show More button,
 * and bottom detail drawer.
 */
$totalPackages = count($packages);
?>

<section class="pkg-sec" id="programs" aria-labelledby="pkg-heading">
  <!-- Section Intro -->
  <header class="pkg-intro">
    <div class="pkg-intro-l">
      <p class="pkg-eyebrow"><span>04</span> / PROGRAM EDITIONS</p>
      <h2 class="pkg-heading" id="pkg-heading">Programs, built <em>around you.</em></h2>
    </div>
    <div class="pkg-intro-r">
      <p class="pkg-lede">Each program is an edition of the same method: a 1:1 assessment, then nutrition, strength and lifestyle structured around your routine, reviewed as you go.</p>
      <p class="pkg-qual">Programs are personalised. Individual outcomes vary.</p>
    </div>
  </header>

  <!-- Filter / Status Bar -->
  <div class="pkg-bar">
    <div class="pkg-filters">
      <span class="pkg-filter-pill">
        <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true">
          <circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.8"/>
          <path d="M10 6v4l2.5 2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        All Programs
      </span>
    </div>
    <p class="pkg-status" aria-live="polite">Active Editions: <?= $totalPackages ?></p>
  </div>

  <!-- Cards Grid -->
  <div class="pkg-grid" role="list">
    <?php foreach ($packages as $i => $pkg): ?>
      <?php
        $numStr = str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
        $isFeatured = (int)($pkg['is_featured'] ?? 0) === 1;
        $badgeText = PackageService::badgeLabel($pkg['badge'] ?? null);

        $sellingPrice = PackageService::formatPrice($pkg['selling_price'] ?? 0);
        $regularPrice = !empty($pkg['regular_price']) && (float)$pkg['regular_price'] > (float)$pkg['selling_price']
          ? PackageService::formatPrice($pkg['regular_price'])
          : null;

        $duration = PackageService::formatDuration((int)($pkg['duration_value'] ?? 0), (string)($pkg['duration_unit'] ?? ''));

        $imgUrl = !empty($pkg['featured_image'])
          ? base_url($pkg['featured_image'])
          : base_url('assets/frontend/images/vispy-hercules-pillars-record.jpg');

        $jsonPayload = htmlspecialchars(json_encode([
          'id'                      => (int)$pkg['id'],
          'name'                    => $pkg['name'],
          'slug'                    => $pkg['slug'],
          'short_description'       => $pkg['short_description'],
          'full_description'        => $pkg['full_description'],
          'selling_price'           => $pkg['selling_price'],
          'regular_price'           => $pkg['regular_price'],
          'formatted_selling_price' => $sellingPrice,
          'formatted_regular_price' => $regularPrice,
          'duration_value'          => $pkg['duration_value'],
          'duration_unit'           => $pkg['duration_unit'],
          'badge'                   => $badgeText,
          'cta_label'               => $pkg['cta_label'],
          'google_form_url'         => $pkg['google_form_url'],
          'image_url'               => $imgUrl,
          'features'                => $pkg['features'] ?? [],
        ]), ENT_QUOTES, 'UTF-8');
      ?>

      <article class="pkg-card <?= $isFeatured ? 'is-featured' : '' ?>" 
               role="listitem" 
               tabindex="0"
               data-package-json="<?= $jsonPayload ?>"
      >
        <!-- Spine -->
        <div class="pkg-card__spine" aria-hidden="true">
          <span class="pkg-card__spine-fill"></span>
          <span class="pkg-card__spine-txt">
            <b><?= $numStr ?></b><i></i><span>EDITION</span>
          </span>
        </div>

        <!-- Main Card Content -->
        <div class="pkg-card__main">
          <figure class="pkg-card__cover">
            <div class="pkg-card__frame">
              <img class="pkg-card__img" 
                   src="<?= esc($imgUrl, 'attr') ?>" 
                   alt="<?= esc($pkg['name']) ?>" 
                   loading="lazy" 
              />
            </div>

            <span class="pkg-card__no" aria-hidden="true"><?= $numStr ?></span>
            <span class="pkg-card__wk" aria-hidden="true"><?= esc($duration) ?></span>

            <?php if ($isFeatured): ?>
              <span class="pkg-card__sig" aria-hidden="true">
                <i></i><?= esc($badgeText ?? 'SIGNATURE') ?>
              </span>
            <?php endif; ?>
          </figure>

          <div class="pkg-card__text">
            <p class="pkg-card__eyebrow"><?= esc($badgeText ?? '1:1 PERSONALISED') ?></p>
            <h3 class="pkg-card__title"><?= esc($pkg['name']) ?></h3>
            <p class="pkg-card__summary"><?= esc($pkg['short_description'] ?? '') ?></p>
          </div>

          <div class="pkg-card__foot">
            <div class="pkg-card__price">
              <small>Investment</small>
              <?php if ($regularPrice): ?>
                <s><?= esc($regularPrice) ?></s>
              <?php endif; ?>
              <b><?= esc($sellingPrice) ?></b>
            </div>

            <button class="pkg-card__explore" type="button" aria-label="Explore <?= esc($pkg['name']) ?>">
              <span>Explore</span>
              <span class="pkg-card__arrow" aria-hidden="true">
                <i></i>
                <svg viewBox="0 0 24 24"><path d="M13 6l6 6-6 6" /></svg>
              </span>
            </button>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Show More Controller -->
  <?php if ($totalPackages > 6): ?>
    <div class="pkg-more">
      <p class="pkg-more-count">Showing <b>6</b> of <b><?= $totalPackages ?></b> programs</p>
      <button class="pkg-more-btn" type="button">
        <span>Show more programs</span>
        <span class="pkg-more-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 5v14M6 13l6 6 6-6" /></svg>
        </span>
      </button>
    </div>
  <?php endif; ?>
</section>

<!-- Bottom Package Detail Drawer -->
<div class="pkg-drawer" id="pkg-drawer" aria-hidden="true">
  <div class="pkg-drawer__scrim" data-pkg-close></div>

  <div class="pkg-drawer__sheet" role="dialog" aria-modal="true" tabindex="-1">
    <!-- Drawer Bar -->
    <div class="pkg-drawer__bar">
      <p class="pkg-drawer__crumb">
        Edition <b data-drawer-num>01</b> / <span data-drawer-total><?= str_pad((string)$totalPackages, 2, '0', STR_PAD_LEFT) ?></span>
      </p>

      <div class="pkg-drawer__ctrl">
        <button class="pkg-drawer__nav-btn" type="button" data-pkg-prev aria-label="Previous program">
          <svg viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6" /></svg>
          Prev
        </button>
        <button class="pkg-drawer__nav-btn" type="button" data-pkg-next aria-label="Next program">
          Next
          <svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6" /></svg>
        </button>
        <button class="pkg-drawer__close-btn" type="button" data-pkg-close aria-label="Close drawer">
          <span>Close</span>
          <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
      </div>
    </div>

    <!-- Drawer Body -->
    <div class="pkg-drawer__body">
      <!-- Visual Image Column -->
      <div class="pkg-drawer__visual">
        <div class="pkg-drawer__photo-wrap">
          <img class="pkg-drawer__img" src="" alt="" data-drawer-img />
          <div class="pkg-drawer__photo-cap">
            <span>PROGRAM DETAILS</span>
            <span data-drawer-duration>12 WEEKS</span>
          </div>
        </div>
      </div>

      <!-- Story / Detail Column -->
      <div class="pkg-drawer__story">
        <div>
          <p class="pkg-drawer__kicker" data-drawer-kicker>SIGNATURE PROGRAM</p>
          <h2 class="pkg-drawer__title" data-drawer-title>Program Name</h2>
          <p class="pkg-drawer__lead" data-drawer-lead>Short summary description.</p>
        </div>

        <dl class="pkg-drawer__facts">
          <div>
            <dt>Duration</dt>
            <dd data-drawer-duration>12 Weeks</dd>
          </div>
          <div>
            <dt>Format</dt>
            <dd>1:1 Personalised</dd>
          </div>
          <div>
            <dt>Reviews</dt>
            <dd>Bi-weekly</dd>
          </div>
        </dl>

        <!-- Features Included -->
        <div>
          <h3 class="pkg-drawer__sec-title">What's Included</h3>
          <ul class="pkg-drawer__features" data-drawer-features>
            <!-- Dynamically populated -->
          </ul>
        </div>

        <!-- Full Description / Overview -->
        <div class="pkg-drawer__desc" data-drawer-desc>
          <!-- Rich HTML content -->
        </div>

        <!-- Investment & Action Box -->
        <div class="pkg-drawer__inv-box">
          <div>
            <span style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.12em; opacity:0.7;">Total Program Investment</span>
            <div class="pkg-drawer__inv-price-wrap">
              <s data-drawer-regular-price style="display:none;"></s>
              <b class="pkg-drawer__inv-price" data-drawer-price>₹14,999</b>
            </div>
          </div>

          <a href="#start" class="pkg-drawer__cta" data-drawer-cta>
            Start this program
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

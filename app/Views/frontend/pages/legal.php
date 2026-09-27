<?= $this->extend('frontend/layouts/main') ?>

<?= $this->section('content') ?>

<!-- NAVIGATION -->
<header class="nav">
  <a class="brand" href="<?= base_url('/') ?>" aria-label="FTPRENEUR by Visphy Kharradi — home">
    <img class="brand__logo" src="<?= base_url('assets/frontend/images/ftpreneur-logo.png') ?>" alt="FTPRENEUR by Visphy Kharradi" width="1324" height="1188" />
  </a>
  <nav class="nav__links" aria-label="Primary">
    <a href="<?= base_url('/#about') ?>">About</a>
    <a href="<?= base_url('/#approach') ?>">Approach</a>
    <a href="<?= base_url('/#programs') ?>">Programs</a>
    <a href="<?= base_url('/#faq') ?>">FAQ</a>
  </nav>
  <a class="nav__phone" href="tel:+919574293300" aria-label="Call +91 95742 93300">+91 95742 93300</a>
  <a class="nav__cta" href="<?= base_url('/#programs') ?>">
    <span>Explore Programs</span>
    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h9M8.5 4.5 12 8l-3.5 3.5" /></svg>
  </a>
  <button class="nav__menu" type="button" aria-label="Open menu" aria-expanded="false"><span></span><span></span></button>
</header>

<main class="legal-page">
  <!-- PAGE HEADER -->
  <header class="legal-header">
    <div class="legal-header__container">
      <nav class="legal-header__breadcrumb" aria-label="Breadcrumb">
        <a href="<?= base_url('/') ?>">Home</a>
        <span class="legal-header__breadcrumb-sep" aria-hidden="true">/</span>
        <span class="legal-header__breadcrumb-current"><?= esc($pageTitle) ?></span>
      </nav>
      
      <span class="legal-header__eyebrow">LEGAL DOCUMENTATION</span>
      <h1 class="legal-header__title"><?= esc($pageTitle) ?></h1>
      <p class="legal-header__subtitle">Official guidelines, policies and operational transparency for Ftpreneur by Visphy Kharradi.</p>
      
      <div class="legal-header__meta">
        <span class="legal-header__meta-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          Effective Date: September 2026
        </span>
        <span class="legal-header__meta-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          Verified Legal Policy
        </span>
      </div>
    </div>
  </header>

  <!-- CONTENT SECTION -->
  <section class="legal-body">
    <div class="legal-container">
      <article class="legal-article">
        <?php if (!empty(trim((string) $pageContent))): ?>
          <?= $pageContent ?>
        <?php else: ?>
          <div class="legal-empty">
            <div class="legal-empty__icon" aria-hidden="true">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <h2 class="legal-empty__title">Policy Details Updating</h2>
            <p class="legal-empty__desc">The content for this legal page is currently being updated by our administration team. Please check back shortly or reach out to support if you have immediate questions.</p>
            <a href="<?= base_url('/') ?>" class="legal-empty__btn">Back to Home</a>
          </div>
        <?php endif; ?>
      </article>
    </div>
  </section>
</main>

<!-- FOOTER -->
<?= $this->include('frontend/sections/footer') ?>

<?= $this->endSection() ?>

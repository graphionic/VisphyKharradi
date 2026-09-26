<?php
/**
 * FTPRENEUR Landing Page Footer — Premium Editorial Wellness/Fitness
 */
?>
<footer class="footer" id="footer" aria-labelledby="footer-cta-title">

  <!-- Background Oversized Watermark -->
  <div class="footer__watermark" aria-hidden="true">FTPRENEUR</div>

  <!-- Background Grid Lines -->
  <div class="footer__bg-grid" aria-hidden="true">
    <div class="footer__bg-line footer__bg-line--h"></div>
  </div>

  <div class="footer__container">

    <!-- 01 · TOP CTA AREA -->
    <div class="footer__cta-block">
      
      <div class="footer__cta-eyebrow">
        <span class="footer__eyebrow-dot" aria-hidden="true"></span>
        <span class="footer__eyebrow-text">READY WHEN YOU ARE.</span>
      </div>

      <h2 id="footer-cta-title" class="footer__cta-heading">
        <span class="footer__heading-line">YOUR HEALTH.</span>
        <span class="footer__heading-line footer__heading-accent">YOUR NEXT MOVE.</span>
      </h2>

      <p class="footer__cta-sub">
        Take the next step towards a more personalised approach to nutrition, strength and lifestyle.
      </p>

      <div class="footer__cta-actions">
        <a class="footer__btn-primary" href="#programs">
          <span>FIND MY PROGRAM</span>
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h9M8.5 4.5 12 8l-3.5 3.5" /></svg>
        </a>

        <a class="footer__btn-secondary" href="tel:+919574293300" aria-label="Call +91 95742 93300">
          <span class="footer__phone-tag">DIRECT CALL</span>
          <span class="footer__phone-num">+91 95742 93300</span>
        </a>
      </div>

    </div>

    <!-- SUBTLE DIVIDER -->
    <div class="footer__divider" aria-hidden="true"></div>

    <!-- 02 · MAIN FOOTER GRID -->
    <div class="footer__main">

      <!-- LEFT COLUMN: BRAND -->
      <div class="footer__col footer__col--brand">
        <a class="footer__brand" href="#" aria-label="FTPRENEUR by Visphy Kharradi — home">
          <img class="footer__brand-logo" src="<?= base_url('assets/frontend/images/ftpreneur-logo.png') ?>" alt="FTPRENEUR by Visphy Kharradi" width="1324" height="1188" />
        </a>

        <p class="footer__brand-copy">
          Personalised nutrition, strength training and lifestyle guidance built around your health, goals and everyday life.
        </p>

        <div class="footer__coach-badge">
          <span class="footer__coach-title">FOUNDER / COACH</span>
          <span class="footer__coach-name">VISPHY KHARRADI</span>
        </div>
      </div>

      <!-- CENTER COLUMN: EXPLORE -->
      <div class="footer__col footer__col--nav">
        <span class="footer__col-label">EXPLORE</span>
        <nav class="footer__nav" aria-label="Footer Navigation">
          <ul class="footer__nav-list">
            <li><a href="#about">About</a></li>
            <li><a href="#approach">Approach</a></li>
            <li><a href="#programs">Programs</a></li>
            <li><a href="#results">Client Results</a></li>
            <li><a href="#faq">FAQ</a></li>
          </ul>
        </nav>
      </div>

      <!-- RIGHT COLUMN: CONNECT -->
      <div class="footer__col footer__col--connect">
        <span class="footer__col-label">CONNECT</span>
        <div class="footer__contact-card">
          <p class="footer__contact-prompt">Have questions before starting?</p>
          <a class="footer__contact-link" href="tel:+919574293300" aria-label="Call +91 95742 93300">
            <svg viewBox="0 0 16 16" aria-hidden="true" class="footer__phone-icon">
              <path d="M3.5 2h3l1.5 3.5L6.2 7c.8 1.6 2.1 2.9 3.7 3.7l1.5-1.8 3.5 1.5v3c0 .8-.7 1.5-1.5 1.5C7.2 14.9 1.1 8.8 1.1 1.5 1.1.7 1.8 0 2.6 0h.9z" fill="currentColor"/>
            </svg>
            <span>+91 95742 93300</span>
          </a>
          <span class="footer__contact-hours">MON – SAT // 9:00 AM – 7:00 PM IST</span>
        </div>
      </div>

    </div>

    <!-- 03 · BOTTOM BAR -->
    <div class="footer__bottom">
      <p class="footer__copyright">
        &copy; <?= date('Y') ?> Ftpreneur. All rights reserved.
      </p>

      <div class="footer__legal">
        <a href="#privacy" class="footer__legal-link">Privacy Policy</a>
        <span class="footer__legal-sep" aria-hidden="true">•</span>
        <a href="#terms" class="footer__legal-link">Terms &amp; Conditions</a>
      </div>
    </div>

  </div>

</footer>

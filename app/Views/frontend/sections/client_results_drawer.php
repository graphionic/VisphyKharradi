<?php
/**
 * Frontend Section 06 — Client Results Full Story Bottom Drawer Partial
 * 
 * Phase 09A: Approved Full Story Bottom Drawer & Report Viewer
 * Rendered at the bottom of landing page; controlled via client-results.js.
 */
?>
<div class="crd" id="crd" hidden data-tone="ultra">
  <div class="crd__scrim" data-crd-close></div>
  <div class="crd__sheet" role="dialog" aria-modal="true" aria-labelledby="crd-title" tabindex="-1">

    <!-- Sticky Header -->
    <header class="crd__head">
      <span class="crd__grab" aria-hidden="true"></span>
      <div class="crd__head-id">
        <span class="crd__head-media" aria-hidden="true"></span>
        <div>
          <p class="crd__head-kicker">Client result <b class="crd__head-n">/ 01</b></p>
          <p class="crd__head-name" id="crd-title">Client Story</p>
        </div>
      </div>
      <p class="crd__head-prog"></p>
      <button class="crd__x" type="button" data-crd-close aria-label="Close client story">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </header>

    <!-- Scrollable Drawer Container -->
    <div class="crd__scroll">
      <div class="crd__content" id="crd-content">
        <!-- Dynamic content injected via JS -->
      </div>
    </div>

    <!-- Inner Evidence / Report Viewer (Slide-over layer inside drawer) -->
    <div class="crd__viewer" role="region" aria-label="Evidence report viewer" hidden>
      <header class="crd__vhead">
        <button class="crd__back" type="button" data-viewer-back>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H6M11 6l-6 6 6 6"/></svg>
          <span>Back to story</span>
        </button>
        <div class="crd__vtitle">
          <p class="crd__vname" id="crd-vname">Report</p>
          <p class="crd__vmeta" id="crd-vmeta"></p>
        </div>
        <button class="crd__x crd__x--soft" type="button" data-viewer-back aria-label="Close report viewer">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </header>
      <div class="crd__vbody" id="crd-vbody">
        <!-- PDF or image content injected dynamically -->
      </div>
    </div>

  </div>
</div>

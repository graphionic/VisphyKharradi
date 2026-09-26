<!-- ==========================================================================
     SECTION 06 — DYNAMIC FAQ SYSTEM (Editorial Accordion)
     ========================================================================== -->
<section class="faq-sec" id="faq" aria-labelledby="faq-title">
  
  <!-- Background Technical Grid & Ghost Numerals -->
  <div class="faq-bg-grid" aria-hidden="true">
    <span class="faq-bg-line faq-bg-line--h1"></span>
    <span class="faq-bg-line faq-bg-line--h2"></span>
    <span class="faq-bg-line faq-bg-line--v1"></span>
    <span class="faq-bg-line faq-bg-line--v2"></span>
    <div class="faq-bg-ghost">06</div>
  </div>

  <div class="faq-container">
    <div class="faq-grid">
      
      <!-- Left Column: Editorial Intro -->
      <div class="faq-left">
        
        <div class="faq-chapter-bar">
          <div class="faq-chapter-id">
            <span class="faq-chapter-num">06</span>
            <span class="faq-chapter-slash">/</span>
            <span class="faq-chapter-label">FAQ</span>
          </div>
          <div class="faq-chapter-divider" aria-hidden="true"></div>
        </div>

        <h2 class="faq-headline" id="faq-title">
          <span class="line">QUESTIONS,</span>
          <span class="line line--accent">ANSWERED.</span>
        </h2>

        <p class="faq-lead">
          Everything you need to know about Ftpreneur, our personalised 1:1 coaching methodology, onboarding baseline, and program structure.
        </p>

        <div class="faq-meta-pill" aria-hidden="true">
          <span class="dot"></span>
          <span>1:1 PERSONALISED DIRECT SUPPORT</span>
        </div>

      </div>

      <!-- Right Column: Editorial Accordion List -->
      <div class="faq-right">
        <div class="faq-accordion" role="tablist" aria-label="Frequently Asked Questions">
          
          <?php foreach ($faqs as $index => $faq): ?>
            <?php 
              $numStr = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
              $itemNum = $index + 1;
            ?>
            <div class="faq-item" data-faq-item>
              
              <button type="button"
                      class="faq-trigger"
                      id="faq-btn-<?= $itemNum ?>"
                      aria-expanded="false"
                      aria-controls="faq-ans-<?= $itemNum ?>"
                      data-faq-trigger>
                <span class="faq-num"><?= $numStr ?></span>
                <span class="faq-question"><?= esc($faq['question']) ?></span>
                <span class="faq-icon" aria-hidden="true">
                  <svg viewBox="0 0 20 20" fill="none" class="faq-icon-svg">
                    <line class="faq-icon-v" x1="10" y1="4" x2="10" y2="16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <line x1="4" y1="10" x2="16" y2="10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                  </svg>
                </span>
              </button>

              <div id="faq-ans-<?= $itemNum ?>"
                   class="faq-panel"
                   role="region"
                   aria-labelledby="faq-btn-<?= $itemNum ?>"
                   hidden>
                <div class="faq-panel-inner">
                  <p class="faq-answer"><?= nl2br(esc($faq['answer'])) ?></p>
                </div>
              </div>

            </div>
          <?php endforeach; ?>

        </div>
      </div>

    </div>
  </div>
</section>

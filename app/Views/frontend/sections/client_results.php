<?php
/**
 * Frontend Section 06 — Client Results / Real Progress Carousel
 * 
 * Phase 08A & Phase 09A: Approved Transformation Journal Carousel & Drawer
 * Displays active public Client Results in an editorial 3/2/1 grouped carousel.
 */
if (empty($clientResults)) {
    return;
}

$tones = ['ultra', 'mint', 'mari'];
?>
<section class="crj" aria-labelledby="crj-h">
  <div class="crj__wrap">
    <header class="crj__intro">
      <div>
        <p class="crj__eyebrow">Real progress</p>
        <h2 class="crj__h" id="crj-h">Progress, <span>made personal.<svg viewBox="0 0 300 16" preserveAspectRatio="none" aria-hidden="true"><path d="M3 11 C 60 4, 140 3, 297 8"/></svg></span></h2>
      </div>
      <p class="crj__lede">Every journey is different. Explore real experiences and measurable changes from personalised nutrition, strength and lifestyle programs.</p>
    </header>

    <div class="crj__carousel" aria-roledescription="carousel" aria-label="Client stories" data-total-count="<?= count($clientResults) ?>">
      <div class="crj__canvas">
        <div class="crj__viewport" tabindex="0" aria-label="Client stories. Use left and right arrow keys to browse.">
          <div class="crj__track">
            <?php foreach ($clientResults as $idx => $res): ?>
              <?php
                $tone = $tones[$idx % 3];
                $name = trim($res['client_display_name'] ?? '');
                
                // Compute initials
                $nameParts = preg_split('/\s+/', $name);
                if (count($nameParts) >= 2) {
                    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr(end($nameParts), 0, 1));
                } else {
                    $initials = mb_strtoupper(mb_substr($name, 0, 2));
                }
                
                $coverImage = !empty($res['cover_image']) ? $res['cover_image'] : null;
                $hasImage = $coverImage && file_exists(FCPATH . ltrim($coverImage, '/\\'));
                $focusAreas = $res['focus_areas'] ?? [];
                $metrics = array_slice($res['metrics'] ?? [], 0, 2);
              ?>
              <article class="crj-s" data-tone="<?= $tone ?>" data-i="<?= $idx ?>" data-id="<?= (int)$res['id'] ?>" aria-label="<?= esc($name) ?>">
                <div class="crj-s__top">
                  <div class="crj-s__media<?= $hasImage ? ' has-img' : '' ?>" aria-hidden="true">
                    <?php if ($hasImage): ?>
                      <img src="<?= base_url(esc($coverImage)) ?>" alt="<?= esc($name) ?>" width="104" height="88" loading="lazy" decoding="async" />
                    <?php endif; ?>
                    <span class="crj-s__ini"><?= esc($initials) ?></span>
                  </div>
                  <div class="crj-s__id">
                    <p class="crj-s__n"><?= sprintf('%02d', $idx + 1) ?></p>
                    <p class="crj-s__name"><?= esc($name) ?></p>
                    <?php if (!empty($res['client_subtitle'])): ?>
                      <p class="crj-s__ctx"><?= esc($res['client_subtitle']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>

                <p class="crj-s__prog">
                  <?= esc($res['program_name_snapshot'] ?? 'Health Program') ?>
                  <?php if (!empty($res['journey_duration'])): ?>
                    <i></i><b><?= esc($res['journey_duration']) ?></b>
                  <?php endif; ?>
                </p>

                <p class="crj-s__focus">
                  <?php if (!empty($focusAreas)): ?>
                    <?php 
                      $visibleFocus = array_slice($focusAreas, 0, 2);
                      $remainingCount = count($focusAreas) - 2;
                    ?>
                    <?= implode('<i></i>', array_map('esc', $visibleFocus)) ?>
                    <?php if ($remainingCount > 0): ?>
                      <i></i><span title="<?= esc(implode(', ', array_slice($focusAreas, 2))) ?>">+<?= $remainingCount ?></span>
                    <?php endif; ?>
                  <?php endif; ?>
                </p>

                <blockquote class="crj-s__quote">
                  <p>&ldquo;<?= esc($res['short_testimonial']) ?>&rdquo;</p>
                </blockquote>

                <ol class="crj-s__res">
                  <?php foreach ($metrics as $m): ?>
                    <li>
                      <p class="crj-s__lbl"><?= esc($m['metric_name']) ?></p>
                      <p class="crj-s__row" aria-label="<?= esc($m['metric_name'] . ': ' . $m['before_value'] . ' to ' . $m['after_value'] . ($m['unit'] ? ' ' . $m['unit'] : '')) ?>">
                        <span class="crj-s__b"><?= esc($m['before_value']) ?><?php if (!empty($m['unit'])): ?><small><?= esc($m['unit']) ?></small><?php endif; ?></span>
                        <span class="crj-s__line" aria-hidden="true"><i></i></span>
                        <span class="crj-s__a"><?= esc($m['after_value']) ?><?php if (!empty($m['unit'])): ?><small><?= esc($m['unit']) ?></small><?php endif; ?></span>
                      </p>
                    </li>
                  <?php endforeach; ?>
                </ol>

                <a class="crj-s__cta" href="#crd-<?= (int)$res['id'] ?>" aria-haspopup="dialog" data-result-id="<?= (int)$res['id'] ?>" onclick="if(window.CRD){window.CRD.open(<?= (int)$res['id'] ?>, this);} return false;">
                  View full story
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg>
                </a>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <footer class="crj__nav">
        <div class="crj__prog">
          <b class="crj__cur">01</b>
          <span class="crj__line" aria-hidden="true"><i></i></span>
          <span class="crj__tot">03</span>
          <span class="crj__range" aria-live="polite">Stories 1–3 of <?= count($clientResults) ?></span>
        </div>
        <p class="crj__fine">Individual results vary.</p>
        <div class="crj__btns">
          <button class="crj__btn" type="button" data-prev disabled>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H6M11 6l-6 6 6 6"/></svg>
            <span>Previous</span>
          </button>
          <button class="crj__btn crj__btn--next" type="button" data-next>
            <span>Next stories</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg>
          </button>
        </div>
      </footer>
    </div>
    </div>
  </div>
</section>

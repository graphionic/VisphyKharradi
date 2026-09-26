<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<div class="package-display-page">
    <?= $this->include('admin/partials/page_header', [
        'title'       => $title,
        'description' => $description,
        'eyebrow'     => 'SYSTEM',
        'breadcrumbs' => [
            ['label' => 'Admin', 'url' => site_url('admin')],
            ['label' => 'System', 'url' => '#'],
            ['label' => 'Package Display'],
        ],
    ]) ?>

    <form method="post" action="<?= site_url('admin/package-display') ?>" class="package-display-form" id="packageDisplayForm">
        <?= csrf_field() ?>

        <div class="package-display-grid">
            <?php
            $templates = [
                [
                    'id'          => 'concept_01',
                    'code'        => 'Concept 01',
                    'title'       => 'Technical Editorial',
                    'description' => 'Structured, editorial program cards with a technical performance-inspired presentation.',
                    'is_default'  => false,
                    'preview_type'=> 'technical',
                ],
                [
                    'id'          => 'concept_02',
                    'code'        => 'Concept 02',
                    'title'       => 'Photography Editorial',
                    'description' => 'Image-led editorial cards with strong visual storytelling and an immersive program experience.',
                    'is_default'  => true,
                    'preview_type'=> 'photography',
                ],
                [
                    'id'          => 'concept_04',
                    'code'        => 'Concept 04',
                    'title'       => 'Premium Visual Grid',
                    'description' => 'A bold visual grid focused on imagery, hierarchy and premium program discovery.',
                    'is_default'  => false,
                    'preview_type'=> 'grid',
                ],
            ];
            ?>

            <?php foreach ($templates as $tpl): ?>
                <?php $isActive = ($currentDesign === $tpl['id']); ?>
                <label class="package-card <?= $isActive ? 'package-card--active' : '' ?>" data-card-id="<?= esc($tpl['id']) ?>">
                    <input type="radio" 
                           name="package_design" 
                           value="<?= esc($tpl['id']) ?>" 
                           class="package-card__radio" 
                           <?= $isActive ? 'checked' : '' ?> 
                    />

                    <!-- Visual Header / Badges -->
                    <div class="package-card__top">
                        <div class="package-card__meta">
                            <span class="package-card__concept"><?= esc($tpl['code']) ?></span>
                            <?php if ($tpl['is_default']): ?>
                                <span class="badge badge--gold">DEFAULT</span>
                            <?php endif; ?>
                        </div>
                        <div class="package-card__status">
                            <span class="package-card__badge-active <?= $isActive ? '' : 'is-hidden' ?>">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M13.5 4.5L6.5 11.5L3 8" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                ACTIVE
                            </span>
                            <span class="package-card__indicator" aria-hidden="true"></span>
                        </div>
                    </div>

                    <!-- Layout Schematic Preview -->
                    <div class="package-card__preview package-card__preview--<?= $tpl['preview_type'] ?>">
                        <div class="schematic">
                            <div class="schematic__header">
                                <div class="schematic__bar"></div>
                                <div class="schematic__pills">
                                    <span></span><span></span><span></span>
                                </div>
                            </div>
                            <?php if ($tpl['preview_type'] === 'technical'): ?>
                                <div class="schematic__body schematic__body--technical">
                                    <div class="schematic__tech-card">
                                        <div class="schematic__tech-header">
                                            <div class="schematic__tech-tag">METABOLIC</div>
                                            <div class="schematic__tech-price">₹45k</div>
                                        </div>
                                        <div class="schematic__line schematic__line--title"></div>
                                        <div class="schematic__line schematic__line--sub"></div>
                                        <div class="schematic__tech-grid">
                                            <div class="schematic__tech-box"></div>
                                            <div class="schematic__tech-box"></div>
                                        </div>
                                    </div>
                                    <div class="schematic__tech-card">
                                        <div class="schematic__tech-header">
                                            <div class="schematic__tech-tag">LONGEVITY</div>
                                            <div class="schematic__tech-price">₹60k</div>
                                        </div>
                                        <div class="schematic__line schematic__line--title"></div>
                                        <div class="schematic__line schematic__line--sub"></div>
                                    </div>
                                </div>
                            <?php elseif ($tpl['preview_type'] === 'photography'): ?>
                                <div class="schematic__body schematic__body--photography">
                                    <div class="schematic__photo-card">
                                        <div class="schematic__photo-hero">
                                            <div class="schematic__photo-overlay">
                                                <div class="schematic__line schematic__line--light"></div>
                                            </div>
                                        </div>
                                        <div class="schematic__photo-footer">
                                            <div class="schematic__line"></div>
                                        </div>
                                    </div>
                                    <div class="schematic__photo-card">
                                        <div class="schematic__photo-hero"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="schematic__body schematic__body--grid">
                                    <div class="schematic__grid-card schematic__grid-card--tall">
                                        <div class="schematic__grid-badge"></div>
                                        <div class="schematic__line schematic__line--light"></div>
                                    </div>
                                    <div class="schematic__grid-card">
                                        <div class="schematic__line"></div>
                                    </div>
                                    <div class="schematic__grid-card">
                                        <div class="schematic__line"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="package-card__content">
                        <h3 class="package-card__title"><?= esc($tpl['title']) ?></h3>
                        <p class="package-card__desc"><?= esc($tpl['description']) ?></p>
                    </div>

                    <!-- Footer Action State -->
                    <div class="package-card__footer">
                        <span class="package-card__select-text">
                            <?= $isActive ? 'Selected' : 'Select Design' ?>
                        </span>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <section class="package-layout-settings" aria-labelledby="package-layout-title">
            <h2 id="package-layout-title">Layout by device</h2>
            <p>Choose how cards are arranged. The selected concept and package card design stay the same.</p>
            <div class="package-layout-settings__grid">
                <?php foreach (['desktop' => 'Desktop · above 1024px', 'tablet' => 'Tablet · 641–1024px', 'mobile' => 'Mobile · up to 640px'] as $device => $label): ?>
                    <?php $selectedLayout = $currentLayouts[$device] ?? \App\Models\SettingModel::DEFAULT_PACKAGE_LAYOUTS[$device]; ?>
                    <fieldset class="package-device" data-device="<?= esc($device) ?>">
                        <legend><?= esc($label) ?></legend>
                        <span class="package-device__label">Layout</span>
                        <?php foreach (['grid' => 'Grid', 'carousel' => 'Carousel'] as $value => $name): ?>
                            <label>
                                <input type="radio" name="package_layout_<?= esc($device) ?>" value="<?= esc($value) ?>"
                                    <?= $selectedLayout === $value ? 'checked' : '' ?> required>
                                <?= esc($name) ?>
                            </label>
                        <?php endforeach; ?>
                        <?php foreach (['grid' => 'Columns', 'carousel' => 'Slides per view'] as $mode => $countLabel): ?>
                            <?php
                            $count = $currentCounts[$mode][$device] ?? \App\Models\SettingModel::DEFAULT_PACKAGE_COUNTS[$device];
                            $max = \App\Models\SettingModel::PACKAGE_DEVICE_LIMITS[$device];
                            $inputId = 'package-count-' . $mode . '-' . $device;
                            ?>
                            <div class="package-device__count" data-count-mode="<?= esc($mode) ?>" <?= $mode !== $selectedLayout ? 'hidden' : '' ?>>
                                <label class="package-device__label" for="<?= esc($inputId) ?>"><?= esc($countLabel) ?></label>
                                <div class="package-stepper" data-stepper>
                                    <button type="button" data-step="-1" aria-label="<?= esc('Decrease ' . $device . ' ' . strtolower($countLabel), 'attr') ?>" aria-controls="<?= esc($inputId) ?>" <?= $count <= 1 ? 'disabled' : '' ?>>−</button>
                                    <input type="text" readonly inputmode="numeric" role="spinbutton"
                                        id="<?= esc($inputId) ?>" name="package_count_<?= esc($mode . '_' . $device) ?>"
                                        value="<?= (int) $count ?>" aria-valuenow="<?= (int) $count ?>"
                                        aria-valuemin="1" aria-valuemax="<?= (int) $max ?>"
                                        aria-label="<?= esc($device . ' ' . strtolower($countLabel), 'attr') ?>">
                                    <button type="button" data-step="1" aria-label="<?= esc('Increase ' . $device . ' ' . strtolower($countLabel), 'attr') ?>" aria-controls="<?= esc($inputId) ?>" <?= $count >= $max ? 'disabled' : '' ?>>+</button>
                                </div>
                                <span class="package-device__range">1–<?= (int) $max ?> cards</span>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endforeach; ?>
            </div>
            <p>Grid keeps Show More. Carousel supports swipe and navigation through all packages, with no autoplay.</p>
        </section>

        <!-- Sticky Action Bar -->
        <div class="package-display-actions">
            <div class="package-display-actions__inner">
                <div class="package-display-actions__hint">
                    Save to apply this design, device layouts and card counts to the website.
                </div>
                <button type="submit" class="btn btn--primary btn--lg">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M14.5 3H5.5C4.11929 3 3 4.11929 3 5.5V14.5C3 15.8807 4.11929 17 5.5 17H14.5C15.8807 17 17 15.8807 17 14.5V5.5C17 4.11929 15.8807 3 14.5 3Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M7 10L9 12L13 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Save Display Settings
                </button>
            </div>
        </div>
    </form>
</div>

<style>
/* Package Display Admin Component Styles */
.package-layout-settings { padding: var(--space-6); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-card); }
.package-layout-settings p { color: var(--color-text-muted); margin: 12px 0; }
.package-layout-settings__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
.package-layout-settings fieldset { margin: 0; padding: 16px; border: 1px solid var(--color-border); border-radius: var(--radius-md); min-width: 0; }
.package-layout-settings legend { font-weight: 700; }
.package-layout-settings label { display: inline-flex; align-items: center; gap: 6px; margin-right: 16px; cursor: pointer; }

.package-layout-settings .package-device__label { display: block; margin: 0 0 10px; font-size: var(--text-sm); font-weight: 600; color: var(--color-text); }
.package-device__count { margin-top: 22px; }
.package-device__count[hidden] { display: none; }
.package-device__range { display: block; margin-top: 8px; font-size: var(--text-xs); color: var(--color-text-muted); }
.package-stepper { display: inline-flex; align-items: center; border: 1px solid var(--color-border); border-radius: var(--radius-md); background: var(--color-canvas); overflow: hidden; }
.package-stepper button { display: grid; place-items: center; width: 42px; height: 42px; padding: 0; border: 0; background: transparent; color: var(--color-text); font-size: 22px; cursor: pointer; }
.package-stepper button:hover:not(:disabled) { background: var(--color-surface-selected); color: var(--color-primary); }
.package-stepper button:disabled { opacity: .3; cursor: default; }
.package-stepper input { width: 52px; height: 42px; min-width: 0; padding: 0; border: 0; border-inline: 1px solid var(--color-border); border-radius: 0; background: var(--color-surface); color: var(--color-text); text-align: center; font: inherit; font-weight: 700; }
.package-stepper :is(button, input):focus-visible { outline: 2px solid var(--color-primary); outline-offset: -3px; }

.package-display-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
    padding-bottom: var(--space-10);
}

.package-display-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.package-display-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: var(--space-6);
}

@media (min-width: 992px) {
    .package-display-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Card Container */
.package-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-card);
    padding: var(--space-5);
    cursor: pointer;
    transition: all var(--duration-fast) var(--ease-default);
    box-shadow: var(--shadow-xs);
    user-select: none;
}

.package-card:hover {
    border-color: var(--color-border-strong);
    box-shadow: var(--shadow-card);
    transform: translateY(-2px);
}

.package-card--active {
    border-color: var(--color-primary);
    background: var(--color-surface-selected);
    box-shadow: 0 0 0 1px var(--color-primary), var(--shadow-card);
}

.package-card__radio {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}

/* Top Meta */
.package-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-4);
}

.package-card__meta {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.package-card__concept {
    font-size: var(--text-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text-muted);
}

.badge--gold {
    background: var(--color-gold-soft);
    color: var(--color-gold-text);
    border: 1px solid var(--color-gold-border);
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: var(--radius-xs);
}

.package-card__status {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.package-card__badge-active {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--color-primary);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.04em;
    padding: 3px 8px;
    border-radius: var(--radius-full);
}

.package-card__badge-active.is-hidden {
    display: none;
}

.package-card__indicator {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid var(--color-border-strong);
    background: var(--color-surface);
    transition: all var(--duration-fast) var(--ease-default);
}

.package-card--active .package-card__indicator {
    border-color: var(--color-primary);
    background: var(--color-primary);
    box-shadow: inset 0 0 0 3px #fff;
}

/* Schematic Layout Previews */
.package-card__preview {
    height: 170px;
    background: var(--color-canvas);
    border: 1px solid var(--color-border-subtle);
    border-radius: var(--radius-md);
    padding: var(--space-3);
    margin-bottom: var(--space-4);
    overflow: hidden;
    position: relative;
}

.schematic {
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.schematic__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--color-border);
}

.schematic__bar {
    width: 32px;
    height: 6px;
    background: var(--color-primary);
    border-radius: 3px;
}

.schematic__pills {
    display: flex;
    gap: 4px;
}

.schematic__pills span {
    width: 14px;
    height: 5px;
    background: var(--color-border-strong);
    border-radius: 2px;
}

.schematic__body {
    flex: 1;
    display: grid;
    gap: var(--space-2);
}

.schematic__body--technical {
    grid-template-columns: 1fr 1fr;
}

.schematic__tech-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xs);
    padding: 6px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.schematic__tech-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.schematic__tech-tag {
    font-size: 8px;
    font-weight: 800;
    color: var(--color-accent);
}

.schematic__tech-price {
    font-size: 8px;
    font-weight: 700;
    color: var(--color-text-muted);
}

.schematic__line {
    height: 4px;
    background: var(--color-border-strong);
    border-radius: 2px;
    width: 85%;
}

.schematic__line--title {
    width: 90%;
    background: var(--color-text);
}

.schematic__line--sub {
    width: 65%;
    background: var(--color-text-muted);
}

.schematic__line--light {
    background: rgba(255, 255, 255, 0.8);
}

.schematic__tech-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3px;
    margin-top: auto;
}

.schematic__tech-box {
    height: 12px;
    background: var(--color-canvas-tint);
    border: 1px dashed var(--color-border-strong);
    border-radius: 2px;
}

.schematic__body--photography {
    grid-template-columns: 1fr 1fr;
}

.schematic__photo-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xs);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.schematic__photo-hero {
    height: 70%;
    background: linear-gradient(135deg, #16213A 0%, #0F2A44 100%);
    position: relative;
    padding: 6px;
    display: flex;
    align-items: flex-end;
}

.schematic__photo-footer {
    padding: 6px;
    background: var(--color-surface);
}

.schematic__body--grid {
    grid-template-columns: 1.2fr 1fr;
    grid-template-rows: 1fr 1fr;
}

.schematic__grid-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xs);
    padding: 6px;
}

.schematic__grid-card--tall {
    grid-row: 1 / span 2;
    background: linear-gradient(180deg, var(--color-primary) 0%, #16213A 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.schematic__grid-badge {
    width: 20px;
    height: 4px;
    background: var(--color-gold);
    border-radius: 2px;
}

/* Card Text */
.package-card__content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.package-card__title {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--color-text);
    margin: 0;
    letter-spacing: var(--tracking-title);
}

.package-card__desc {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin: 0;
    line-height: var(--leading-relaxed);
}

/* Card Footer */
.package-card__footer {
    margin-top: var(--space-4);
    padding-top: var(--space-3);
    border-top: 1px solid var(--color-border-subtle);
    display: flex;
    justify-content: flex-end;
}

.package-card__select-text {
    font-size: var(--text-xs);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text-muted);
    transition: color var(--duration-fast) var(--ease-default);
}

.package-card--active .package-card__select-text {
    color: var(--color-primary);
}

/* Actions Footer Bar */
.package-display-actions {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-panel);
    padding: var(--space-4) var(--space-6);
    box-shadow: var(--shadow-sm);
}

.package-display-actions__inner {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    align-items: center;
    justify-content: space-between;
}

@media (min-width: 640px) {
    .package-display-actions__inner {
        flex-direction: row;
    }
}

.package-display-actions__hint {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.package-device').forEach(device => {
        function syncLayout() {
            const selected = device.querySelector('input[type="radio"]:checked');
            device.querySelectorAll('[data-count-mode]').forEach(control => {
                control.hidden = selected && control.dataset.countMode !== selected.value;
            });
        }
        device.querySelectorAll('input[type="radio"]').forEach(radio => radio.addEventListener('change', syncLayout));
        syncLayout();
        device.querySelectorAll('[data-stepper]').forEach(stepper => {
            const input = stepper.querySelector('input');
            const minimum = Number(input.getAttribute('aria-valuemin'));
            const maximum = Number(input.getAttribute('aria-valuemax'));
            const decrease = stepper.querySelector('[data-step="-1"]');
            const increase = stepper.querySelector('[data-step="1"]');
            function setValue(value) {
                const next = Math.max(minimum, Math.min(maximum, value));
                input.value = next;
                input.setAttribute('aria-valuenow', next);
                decrease.disabled = next === minimum;
                increase.disabled = next === maximum;
            }
            stepper.querySelectorAll('button').forEach(button => {
                button.addEventListener('click', () => setValue(Number(input.value) + Number(button.dataset.step)));
            });
            input.addEventListener('keydown', event => {
                const changes = {ArrowUp: 1, ArrowRight: 1, ArrowDown: -1, ArrowLeft: -1};
                if (Object.prototype.hasOwnProperty.call(changes, event.key)) {
                    event.preventDefault();
                    setValue(Number(input.value) + changes[event.key]);
                } else if (event.key === 'Home' || event.key === 'End') {
                    event.preventDefault();
                    setValue(event.key === 'Home' ? minimum : maximum);
                }
            });
            setValue(Number(input.value));
        });
    });

    const cards = document.querySelectorAll('.package-card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            cards.forEach(c => {
                c.classList.remove('package-card--active');
                const badge = c.querySelector('.package-card__badge-active');
                if (badge) badge.classList.add('is-hidden');
                const text = c.querySelector('.package-card__select-text');
                if (text) text.textContent = 'Select Design';
            });

            this.classList.add('package-card--active');
            const radio = this.querySelector('.package-card__radio');
            if (radio) radio.checked = true;

            const badge = this.querySelector('.package-card__badge-active');
            if (badge) badge.classList.remove('is-hidden');
            const text = this.querySelector('.package-card__select-text');
            if (text) text.textContent = 'Selected';
        });
    });
});
</script>
<?= $this->endSection() ?>

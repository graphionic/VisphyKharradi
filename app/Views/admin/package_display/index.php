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

        <!-- Sticky Action Bar -->
        <div class="package-display-actions">
            <div class="package-display-actions__inner">
                <div class="package-display-actions__hint">
                    Changes apply immediately to public website routing once saved.
                </div>
                <button type="submit" class="btn btn--primary btn--lg">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M14.5 3H5.5C4.11929 3 3 4.11929 3 5.5V14.5C3 15.8807 4.11929 17 5.5 17H14.5C15.8807 17 17 15.8807 17 14.5V5.5C17 4.11929 15.8807 3 14.5 3Z" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M7 10L9 12L13 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Save Design
                </button>
            </div>
        </div>
    </form>
</div>

<style>
/* Package Display Admin Component Styles */
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

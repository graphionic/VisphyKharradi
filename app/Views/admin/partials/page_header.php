<?php
/**
 * Page Header — reusable v5 — eyebrow + title + desc + actions
 * Variables: $title (string), $description (string|null), $eyebrow (string|null), $actions (html string optional), $breadcrumbs (array optional)
 */
$title = $title ?? '';
$description = $description ?? null;
$eyebrow = $eyebrow ?? null;
?>
<?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <?php if ($i > 0): ?><span class="breadcrumbs__sep">/</span><?php endif; ?>
            <?php if (!empty($crumb['url'])): ?>
                <a href="<?= esc($crumb['url'], 'attr') ?>"><?= esc($crumb['label']) ?></a>
            <?php else: ?>
                <span aria-current="page"><?= esc($crumb['label']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<div class="page-header">
    <div class="page-header__meta">
        <?php if (!empty($eyebrow)): ?><div class="page-header__eyebrow"><?= esc($eyebrow) ?></div><?php endif; ?>
        <h1 class="page-header__title"><?= esc($title) ?></h1>
        <?php if ($description): ?>
            <p class="page-header__desc"><?= esc($description) ?></p>
        <?php endif; ?>
    </div>
    <?php if (!empty($actions)): ?>
        <div class="page-header__actions">
            <?= $actions /* caller ensures escaped/safe */ ?>
        </div>
    <?php endif; ?>
    <?php if (isset($actionSlot)): ?>
        <div class="page-header__actions">
            <?= $actionSlot ?>
        </div>
    <?php endif; ?>
</div>

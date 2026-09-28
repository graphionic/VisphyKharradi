<?php
/**
 * Frontend Package Display Section Router
 *
 * Active design resolved via $activeDesign (setting: frontend.package_design).
 * Available designs: concept_01, concept_02, concept_04.
 * Default & Step 2 Production Renderer: concept_02.
 * Layout settings apply to the existing cards in every concept.
 */
$design = $activeDesign ?? 'concept_02';

$packageLayouts = $packageLayouts ?? \App\Models\SettingModel::DEFAULT_PACKAGE_LAYOUTS;
$packageCounts = $packageCounts ?? array_fill_keys(['grid', 'carousel'], \App\Models\SettingModel::DEFAULT_PACKAGE_COUNTS);
$countStyles = [];
foreach (['grid', 'carousel'] as $mode) {
    foreach (\App\Models\SettingModel::PACKAGE_DEVICE_LIMITS as $device => $max) {
        $count = max(1, min($max, (int) ($packageCounts[$mode][$device] ?? \App\Models\SettingModel::DEFAULT_PACKAGE_COUNTS[$device])));
        $countStyles[] = '--package-' . $mode . '-' . $device . ':' . $count;
    }
}
?>
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/packages-layout.css?v=' . time()) ?>">
<script src="<?= base_url('assets/frontend/js/packages-layout.js?v=' . time()) ?>" defer></script>
<div class="package-display" style="<?= esc(implode(';', $countStyles), 'attr') ?>"
     data-layout-desktop="<?= esc($packageLayouts['desktop'], 'attr') ?>"
     data-layout-tablet="<?= esc($packageLayouts['tablet'], 'attr') ?>"
     data-layout-mobile="<?= esc($packageLayouts['mobile'], 'attr') ?>">
<?php
switch ($design) {
    case 'concept_01':
        echo $this->include('frontend/packages/concept_01');
        break;
    case 'concept_04':
        echo $this->include('frontend/packages/concept_04');
        break;
    case 'concept_02':
    default:
        echo $this->include('frontend/packages/concept_02');
        break;
}

?>
</div>

<?= $this->include('frontend/sections/checkout') ?>
<link rel="stylesheet" href="<?= base_url('assets/frontend/css/checkout.css?v=' . time()) ?>">
<script src="<?= base_url('assets/frontend/js/checkout.js?v=' . time()) ?>" defer></script>

<?php
/**
 * Ftpreneur Public Frontend — Scripts Partial
 */
?>
<script src="<?= base_url('assets/frontend/js/hero.js') ?>" defer></script>
<script src="<?= base_url('assets/frontend/js/credibility.js?v=1.0') ?>" defer></script>
<script src="<?= base_url('assets/frontend/js/reality.js?v=1.0') ?>" defer></script>
<?php if (file_exists(FCPATH . 'assets/frontend/js/main.js')): ?>
<script src="<?= base_url('assets/frontend/js/main.js?v=6.0') ?>" defer></script>
<?php endif; ?>

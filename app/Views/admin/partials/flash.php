<?php
// Central flash rendering — uses alert component, escaped, no secrets
$success = session()->getFlashdata('message');
$errorFlash = session()->getFlashdata('error');
?>
<?php if (!empty($success)): ?>
    <div class="alert alert--success" role="status" data-auto-dismiss>
        <svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 10l3 3 5-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.4"/></svg>
        <div class="alert__content"><?= esc($success) ?></div>
        <button class="alert__dismiss" data-alert-dismiss aria-label="Dismiss"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
    </div>
<?php endif; ?>
<?php if (!empty($errorFlash)): ?>
    <div class="alert alert--danger" role="alert">
        <svg class="alert__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6.5V10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="10" cy="13.5" r="1" fill="currentColor"/><path d="M10 16A6 6 0 1 0 10 4a6 6 0 0 0 0 12Z" stroke="currentColor" stroke-width="1.4"/></svg>
        <div class="alert__content"><?= esc($errorFlash) ?></div>
        <button class="alert__dismiss" data-alert-dismiss aria-label="Dismiss"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
    </div>
<?php endif; ?>

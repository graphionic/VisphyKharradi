<?php
$adminName = esc($admin['name'] ?? session()->get('admin_name') ?? 'Admin');
$adminEmail = esc($admin['email'] ?? session()->get('admin_email') ?? '');
$initial = strtoupper(substr(trim($admin['name'] ?? 'A'), 0, 1));
?>
<header class="admin-topbar" role="banner">
    <div class="admin-topbar__left">
        <button class="admin-icon-btn" data-sidebar-open aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 6h14M3 10h14M3 14h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </button>
        <button class="admin-icon-btn u-hide-mobile" data-sidebar-collapse aria-label="Collapse sidebar" aria-pressed="false" data-tooltip="Collapse">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 5l-5 5 5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="admin-topbar__breadcrumb" aria-label="Page context">
            <span class="admin-topbar__breadcrumb-dot" aria-hidden="true"></span>
            <span>Admin</span>
            <span class="admin-topbar__breadcrumb-sep" aria-hidden="true">/</span>
            <strong><?= esc($pageContext ?? 'Dashboard') ?></strong>
        </div>
    </div>

    <div class="admin-topbar__right">
        <div class="dropdown" data-dropdown>
            <button class="btn btn--ghost" data-dropdown-trigger aria-haspopup="menu" aria-expanded="false" aria-label="Account menu">
                <span class="admin-topbar__avatar" aria-hidden="true"><?= esc($initial) ?></span>
                <span class="admin-topbar__user-name"><?= $adminName ?></span>
                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true" class="admin-topbar__chevron"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="dropdown__menu" data-dropdown-menu role="menu" aria-label="Account">
                <div class="dropdown__account-head">
                    <div class="dropdown__account-name"><?= $adminName ?></div>
                    <div class="dropdown__account-email" title="<?= $adminEmail ?>"><?= $adminEmail ?></div>
                </div>
                <div class="dropdown__divider"></div>
                <a class="dropdown__item" href="<?= site_url('admin/profile') ?>" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="7.5" r="3.2" stroke="currentColor" stroke-width="1.35"/><path d="M4.5 15.5a5.5 5.5 0 0 1 11 0" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg>
                    Profile
                </a>
                <?php if (ENVIRONMENT !== 'production'): ?>
                <a class="dropdown__item" href="<?= site_url('admin/ui-preview') ?>" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="3" width="14" height="14" rx="1.6" stroke="currentColor" stroke-width="1.35"/><path d="M7 10h6M10 7v6" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg>
                    UI Preview <span class="dropdown__meta">Dev</span>
                </a>
                <?php endif; ?>
                <div class="dropdown__divider"></div>
                <form method="post" action="<?= site_url('admin/logout') ?>" role="none">
                    <?= csrf_field() ?>
                    <button type="submit" class="dropdown__item dropdown__item--danger" role="menuitem">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7 4H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3M12 14l4-4-4-4M16 10H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

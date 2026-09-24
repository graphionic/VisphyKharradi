<?php
/**
 * Sidebar — Ftpreneur Admin v5 — Premium CRM navigation
 * Expects: $admin (optional), current path via uri_string()
 */
$current = uri_string();
$current = trim($current, '/');
if ($current === '') $current = 'admin';

if (!function_exists('ftpreneur_isActive')) {
    function ftpreneur_isActive(string $current, array $paths): bool
    {
        foreach ($paths as $p) {
            if ($current === $p) return true;
            if (str_starts_with($current, $p . '/')) return true;
        }
        return false;
    }
}
$adminName = esc($admin['name'] ?? session()->get('admin_name') ?? 'Admin');
$adminEmail = esc($admin['email'] ?? session()->get('admin_email') ?? '');
$initial = strtoupper(substr(trim($admin['name'] ?? 'A'), 0, 1));
if ($initial === '') $initial = 'A';
?>
<aside class="admin-sidebar" data-sidebar aria-label="Primary">
    <div class="admin-sidebar__inner">
        <div class="admin-sidebar__brand">
            <div class="admin-sidebar__logo-mark" aria-hidden="true">F</div>
            <div class="admin-sidebar__brand-text">
                <span class="admin-sidebar__brand-title">FTP<span>RENEUR</span></span>
                <span class="admin-sidebar__brand-sub">Visphy Kharradi</span>
            </div>
            <button class="admin-sidebar__close" data-sidebar-close aria-label="Close navigation">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            </button>
        </div>

        <nav class="admin-nav" aria-label="Admin sections">
            <div class="admin-nav__group">
                <div class="admin-nav__label">Overview</div>
                <ul class="admin-nav__list">
                    <li>
                        <a class="admin-nav__link <?= ftpreneur_isActive($current, ['admin']) && !ftpreneur_isActive($current, ['admin/profile','admin/packages','admin/orders','admin/payments','admin/settings','admin/activity','admin/ui-preview','admin/brand-guidelines']) ? 'admin-nav__link--active' : '' ?>"
                           href="<?= site_url('admin') ?>" aria-current="<?= ftpreneur_isActive($current, ['admin']) && $current==='admin' ? 'page' : 'false' ?>">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 8.5L10 3l7 5.5V16a1 1 0 0 1-1 1h-3V11H7v6H4a1 1 0 0 1-1-1V8.5Z" stroke="currentColor" stroke-width="1.45" stroke-linejoin="round"/></svg>
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="admin-nav__group">
                <div class="admin-nav__label">Management</div>
                <ul class="admin-nav__list">
                    <li>
                        <a class="admin-nav__link <?= ftpreneur_isActive($current, ['admin/packages']) ? 'admin-nav__link--active' : '' ?>"
                           href="<?= site_url('admin/packages') ?>" aria-current="<?= ftpreneur_isActive($current, ['admin/packages']) ? 'page' : 'false' ?>">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="12" rx="1.5" stroke="currentColor" stroke-width="1.35"/><path d="M7 8h6M7 12h4" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg>
                            <span>Packages</span>
                        </a>
                    </li>
                    <li>
                        <a class="admin-nav__link admin-nav__link--disabled" href="#" aria-disabled="true" tabindex="-1">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 13l3-3 3 3 4-4 3 3" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="3" width="14" height="14" rx="1.5" stroke="currentColor" stroke-width="1.35"/></svg>
                            <span>Orders</span>
                            <span class="admin-nav__badge-soon">Soon</span>
                        </a>
                    </li>
                    <li>
                        <a class="admin-nav__link admin-nav__link--disabled" href="#" aria-disabled="true" tabindex="-1">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="5" width="14" height="10" rx="1.2" stroke="currentColor" stroke-width="1.35"/><path d="M3 8h14M7 12h4" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg>
                            <span>Payments</span>
                            <span class="admin-nav__badge-soon">Soon</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="admin-nav__group">
                <div class="admin-nav__label">System</div>
                <ul class="admin-nav__list">
                    <li>
                        <a class="admin-nav__link admin-nav__link--disabled" href="#" aria-disabled="true" tabindex="-1">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 7.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5Z" stroke="currentColor" stroke-width="1.35"/><path d="M10 3v1.2M10 15.8V17M3 10h1.2M15.8 10H17M4.6 4.6l.85.85M14.55 14.55l.85.85M4.6 15.4l.85-.85M14.55 5.45l.85-.85" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                            <span>Settings</span>
                            <span class="admin-nav__badge-soon">Soon</span>
                        </a>
                    </li>
                    <li>
                        <a class="admin-nav__link admin-nav__link--disabled" href="#" aria-disabled="true" tabindex="-1">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 6h12M4 10h12M4 14h8" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/><circle cx="14.5" cy="14.5" r="1" fill="currentColor"/></svg>
                            <span>Activity Logs</span>
                            <span class="admin-nav__badge-soon">Soon</span>
                        </a>
                    </li>
                    <li>
                        <a class="admin-nav__link <?= ftpreneur_isActive($current, ['admin/brand-guidelines']) ? 'admin-nav__link--active' : '' ?>"
                           href="<?= site_url('admin/brand-guidelines') ?>" aria-current="<?= ftpreneur_isActive($current, ['admin/brand-guidelines']) ? 'page' : 'false' ?>">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14Z" stroke="currentColor" stroke-width="1.35"/><circle cx="7.5" cy="8.5" r="1" fill="currentColor"/><circle cx="12.5" cy="8.5" r="1" fill="currentColor"/><circle cx="7.5" cy="12.5" r="1" fill="currentColor"/><circle cx="12.5" cy="12.5" r="1" fill="currentColor"/></svg>
                            <span>Brand Guidelines</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="admin-nav__group">
                <div class="admin-nav__label">Account</div>
                <ul class="admin-nav__list">
                    <li>
                        <a class="admin-nav__link <?= ftpreneur_isActive($current, ['admin/profile']) ? 'admin-nav__link--active' : '' ?>"
                           href="<?= site_url('admin/profile') ?>">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="7.5" r="3.2" stroke="currentColor" stroke-width="1.35"/><path d="M4.5 15.5a5.5 5.5 0 0 1 11 0" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg>
                            <span>Profile</span>
                        </a>
                    </li>
                    <?php if (ENVIRONMENT !== 'production' && ftpreneur_isActive($current, ['admin/ui-preview'])): ?>
                    <li>
                        <a class="admin-nav__link <?= $current==='admin/ui-preview' ? 'admin-nav__link--active' : '' ?>" href="<?= site_url('admin/ui-preview') ?>">
                            <svg class="admin-nav__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="3" width="14" height="14" rx="1.6" stroke="currentColor" stroke-width="1.35"/><path d="M7 10h6M10 7v6" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg>
                            <span>UI Preview</span>
                            <span class="admin-nav__meta">Dev</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <div class="admin-sidebar__footer">
            <div class="admin-account-mini" aria-label="Signed in account">
                <div class="admin-account-mini__avatar" aria-hidden="true"><?= esc($initial) ?></div>
                <div class="admin-account-mini__meta">
                    <div class="admin-account-mini__name"><?= $adminName ?></div>
                    <div class="admin-account-mini__email" title="<?= $adminEmail ?>"><?= $adminEmail ?></div>
                </div>
            </div>
            <form method="post" action="<?= site_url('admin/logout') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--sidebar">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7 4H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3M12 14l4-4-4-4M16 10H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Logout
                </button>
            </form>
        </div>
    </div>
</aside>

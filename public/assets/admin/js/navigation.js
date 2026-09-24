/* Ftpreneur Admin — navigation.js
 * Sidebar collapse (desktop) + mobile drawer
 * No dependencies, shared-hosting safe
 */
(function () {
  const STORAGE_KEY = 'ftpreneur_admin_sidebar_collapsed';
  const shell = document.querySelector('[data-admin-shell]');
  const sidebar = document.querySelector('[data-sidebar]');
  const overlay = document.querySelector('[data-sidebar-overlay]');
  const openBtn = document.querySelector('[data-sidebar-open]');
  const closeBtn = document.querySelector('[data-sidebar-close]');
  const collapseBtn = document.querySelector('[data-sidebar-collapse]');

  if (!shell || !sidebar) return;

  function isDesktop() { return window.matchMedia('(min-width: 1025px)').matches; }

  function setCollapsed(collapsed, persist = true) {
    shell.classList.toggle('admin-shell--collapsed', collapsed);
    if (collapseBtn) {
      collapseBtn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
      const icon = collapseBtn.querySelector('[data-collapse-icon]');
      if (icon) icon.textContent = collapsed ? '›' : '‹';
      collapseBtn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
      collapseBtn.setAttribute('data-tooltip', collapsed ? 'Expand' : 'Collapse');
    }
    if (persist) {
      try { localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0'); } catch (e) {}
    }
  }

  function setDrawerOpen(open) {
    sidebar.classList.toggle('admin-sidebar--open', open);
    if (overlay) overlay.classList.toggle('admin-overlay--visible', open);
    document.body.style.overflow = open && !isDesktop() ? 'hidden' : '';
    if (openBtn) openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    // Focus management
    if (open) {
      const firstLink = sidebar.querySelector('a, button');
      if (firstLink) setTimeout(() => firstLink.focus(), 0);
    } else {
      if (openBtn) openBtn.focus();
    }
  }

  // Restore collapsed from storage (desktop only)
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === '1' && isDesktop()) setCollapsed(true, false);
  } catch (e) {}

  if (collapseBtn) {
    collapseBtn.addEventListener('click', () => {
      const collapsed = !shell.classList.contains('admin-shell--collapsed');
      setCollapsed(collapsed);
    });
  }

  if (openBtn) openBtn.addEventListener('click', () => setDrawerOpen(true));
  if (closeBtn) closeBtn.addEventListener('click', () => setDrawerOpen(false));
  if (overlay) overlay.addEventListener('click', () => setDrawerOpen(false));

  // Escape closes drawer
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('admin-sidebar--open')) {
      setDrawerOpen(false);
    }
  });

  // Resize: if moving to desktop, ensure drawer closed
  window.addEventListener('resize', () => {
    if (isDesktop() && sidebar.classList.contains('admin-sidebar--open')) {
      setDrawerOpen(false);
    }
  });

  // Expose for other modules if needed
  window.FtpreneurNav = { setCollapsed, setDrawerOpen };
})();

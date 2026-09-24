/* Ftpreneur Admin — dropdown.js
 * Reusable dropdown: account menu + row actions
 * Keyboard: Escape, ArrowDown/Up, click outside
 */
(function () {
  const dropdowns = document.querySelectorAll('[data-dropdown]');

  function closeAll(except) {
    dropdowns.forEach((dd) => {
      if (dd === except) return;
      const btn = dd.querySelector('[data-dropdown-trigger]');
      const menu = dd.querySelector('[data-dropdown-menu]');
      if (btn) btn.setAttribute('aria-expanded', 'false');
      if (menu) menu.classList.remove('dropdown__menu--open');
    });
  }

  dropdowns.forEach((dd) => {
    const trigger = dd.querySelector('[data-dropdown-trigger]');
    const menu = dd.querySelector('[data-dropdown-menu]');
    if (!trigger || !menu) return;

    function open() {
      closeAll(dd);
      menu.classList.add('dropdown__menu--open');
      trigger.setAttribute('aria-expanded', 'true');
      // focus first item
      const first = menu.querySelector('[data-dropdown-item], button, a');
      if (first) setTimeout(() => first.focus(), 0);
    }
    function close() {
      menu.classList.remove('dropdown__menu--open');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.focus();
    }
    function isOpen() { return menu.classList.contains('dropdown__menu--open'); }

    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      if (isOpen()) close(); else open();
    });

    // Keyboard on trigger
    trigger.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        open();
      }
    });

    // Keyboard inside menu
    menu.addEventListener('keydown', (e) => {
      const items = Array.from(menu.querySelectorAll('[data-dropdown-item], a, button'));
      const idx = items.indexOf(document.activeElement);
      if (e.key === 'Escape') {
        e.stopPropagation();
        close();
      } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        const next = items[(idx + 1) % items.length];
        if (next) next.focus();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        const prev = items[(idx - 1 + items.length) % items.length];
        if (prev) prev.focus();
      } else if (e.key === 'Tab') {
        // allow tab to close if leaving
        setTimeout(() => {
          if (!menu.contains(document.activeElement) && !trigger.contains(document.activeElement)) {
            menu.classList.remove('dropdown__menu--open');
            trigger.setAttribute('aria-expanded', 'false');
          }
        }, 0);
      }
    });
  });

  // Click outside closes
  document.addEventListener('click', (e) => {
    dropdowns.forEach((dd) => {
      const menu = dd.querySelector('[data-dropdown-menu]');
      const trigger = dd.querySelector('[data-dropdown-trigger]');
      if (!menu || !trigger) return;
      if (!dd.contains(e.target)) {
        menu.classList.remove('dropdown__menu--open');
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  });

  // Global Escape closes all
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAll(null);
  });
})();

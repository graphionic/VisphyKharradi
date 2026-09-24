/* Ftpreneur Admin — modal.js
 * Accessible dialog: overlay, focus trap, Escape, scroll lock
 */
(function () {
  const modals = document.querySelectorAll('[data-modal]');
  let lastFocus = null;

  function trapFocus(modal) {
    const focusable = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    function onKey(e) {
      if (e.key !== 'Tab') return;
      if (e.shiftKey) {
        if (document.activeElement === first) {
          e.preventDefault();
          last.focus();
        }
      } else {
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    }
    modal.addEventListener('keydown', onKey);
    return () => modal.removeEventListener('keydown', onKey);
  }

  let cleanupTrap = null;

  function openModal(id) {
    const overlay = document.querySelector(`[data-modal="${id}"]`);
    if (!overlay) return;
    lastFocus = document.activeElement;
    overlay.classList.add('modal-overlay--open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    const dialog = overlay.querySelector('[role="dialog"], .modal');
    if (dialog) {
      dialog.setAttribute('aria-modal', 'true');
      const focusTarget = dialog.querySelector('[data-modal-focus], button, input');
      if (focusTarget) setTimeout(() => focusTarget.focus(), 0);
      cleanupTrap = trapFocus(dialog);
    }
  }

  function closeModal(overlay) {
    if (!overlay) return;
    overlay.classList.remove('modal-overlay--open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (cleanupTrap) { cleanupTrap(); cleanupTrap = null; }
    if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
  }

  // Triggers
  document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-modal-open]');
    if (openBtn) {
      const id = openBtn.getAttribute('data-modal-open');
      if (id) openModal(id);
      return;
    }
    const closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      const overlay = closeBtn.closest('[data-modal]');
      if (overlay) closeModal(overlay);
      return;
    }
    // Click on overlay background closes
    const overlay = e.target.closest('[data-modal]');
    if (overlay && e.target === overlay) {
      closeModal(overlay);
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const open = document.querySelector('.modal-overlay--open');
      if (open) closeModal(open);
    }
  });

  window.FtpreneurModal = { open: openModal, close: closeModal };
})();

/* Ftpreneur Admin — admin.js
 * Glue: flash dismiss, password toggle, helpers
 */
(function () {
  // Flash dismiss
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-alert-dismiss]');
    if (btn) {
      const alert = btn.closest('.alert');
      if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-4px)';
        setTimeout(() => alert.remove(), 160);
      }
    }
  });

  // Auto-dismiss success flashes after 6s (respect reduced motion? still dismiss but not annoying)
  const successAlerts = document.querySelectorAll('.alert--success[data-auto-dismiss]');
  successAlerts.forEach((el) => {
    setTimeout(() => {
      const btn = el.querySelector('[data-alert-dismiss]');
      if (btn) btn.click();
      else {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 160);
      }
    }, 6000);
  });

  // Password visibility toggle
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-password-toggle]');
    if (!btn) return;
    const field = btn.closest('.password-field');
    if (!field) return;
    const input = field.querySelector('input');
    if (!input) return;
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.setAttribute('aria-pressed', isText ? 'false' : 'true');
    btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
    // swap icon
    const eye = btn.querySelector('[data-eye]');
    const eyeOff = btn.querySelector('[data-eye-off]');
    if (eye && eyeOff) {
      eye.style.display = isText ? '' : 'none';
      eyeOff.style.display = isText ? 'none' : '';
    }
  });

  // Prevent double submit on buttons with data-loading
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    const btn = form.querySelector('button[type="submit"]');
    if (btn && btn.hasAttribute('data-prevent-double')) {
      btn.classList.add('btn--loading');
      btn.disabled = true;
      // allow actual submit, but prevent second click
      setTimeout(() => { btn.disabled = false; btn.classList.remove('btn--loading'); }, 4000);
    }
  });

  // Add subtle enhancement: table responsive hint
  document.querySelectorAll('.table-wrap').forEach((wrap) => {
    if (wrap.scrollWidth > wrap.clientWidth) {
      wrap.setAttribute('data-hint', 'Scroll to see more');
    }
  });
})();

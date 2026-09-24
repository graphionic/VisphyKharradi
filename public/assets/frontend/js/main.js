/**
 * Ftpreneur — Production Frontend JavaScript Foundation
 * Framework-free, lightweight, accessible.
 */

(function () {
  'use strict';

  // Mark document as JS-enabled for progressive enhancement
  document.documentElement.classList.add('js-enabled');

  // Motion preference detection
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /**
   * DOM Ready Handler
   */
  function onDOMReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  /**
   * Initialize Global Frontend Behaviors
   */
  onDOMReady(function () {
    initKeyboardFocus();
    initSmoothScroll();
  });

  /**
   * Keyboard focus indicator handler
   */
  function initKeyboardFocus() {
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Tab') {
        document.body.classList.add('user-is-tabbing');
      }
    });

    document.addEventListener('mousedown', function () {
      document.body.classList.remove('user-is-tabbing');
    });
  }

  /**
   * Safe smooth scrolling for internal hash links
   */
  function initSmoothScroll() {
    if (prefersReducedMotion) return;

    document.addEventListener('click', function (e) {
      const anchor = e.target.closest('a[href^="#"]:not([href="#"])');
      if (!anchor) return;

      const targetId = anchor.getAttribute('href').substring(1);
      const targetEl = document.getElementById(targetId);

      if (targetEl) {
        e.preventDefault();
        targetEl.scrollIntoView({ behavior: 'smooth' });
        targetEl.focus({ preventScroll: true });
      }
    });
  }

  // Export public namespace for safe expansion in future phases
  window.Ftpreneur = window.Ftpreneur || {};
  window.Ftpreneur.reducedMotion = prefersReducedMotion;
})();

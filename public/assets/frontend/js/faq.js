/* ==========================================================================
   FTPRENEUR — Section 06: Dynamic FAQ System (Vanilla JS Accordion)
   Accessible accordion with single-open toggle and keyboard navigation.
   ========================================================================== */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var faqItems = Array.prototype.slice.call(document.querySelectorAll('[data-faq-item]'));
    if (!faqItems.length) return;

    var triggers = faqItems.map(function (item) {
      return item.querySelector('[data-faq-trigger]');
    }).filter(Boolean);

    function toggleFaq(targetItem, open) {
      var trigger = targetItem.querySelector('[data-faq-trigger]');
      var panel = targetItem.querySelector('.faq-panel');
      if (!trigger || !panel) return;

      var isCurrentlyOpen = targetItem.classList.contains('is-open');
      var shouldOpen = typeof open === 'boolean' ? open : !isCurrentlyOpen;

      if (shouldOpen) {
        // Close all other items (one open at a time)
        faqItems.forEach(function (otherItem) {
          if (otherItem !== targetItem && otherItem.classList.contains('is-open')) {
            toggleFaq(otherItem, false);
          }
        });

        targetItem.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        panel.removeAttribute('hidden');
      } else {
        targetItem.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        panel.setAttribute('hidden', '');
      }
    }

    // Attach click and keyboard handlers
    triggers.forEach(function (btn, index) {
      var item = faqItems[index];

      btn.addEventListener('click', function () {
        toggleFaq(item);
      });

      btn.addEventListener('keydown', function (e) {
        var key = e.key;

        if (key === 'ArrowDown') {
          e.preventDefault();
          var nextIndex = (index + 1) % triggers.length;
          triggers[nextIndex].focus();
        } else if (key === 'ArrowUp') {
          e.preventDefault();
          var prevIndex = (index - 1 + triggers.length) % triggers.length;
          triggers[prevIndex].focus();
        } else if (key === 'Home') {
          e.preventDefault();
          triggers[0].focus();
        } else if (key === 'End') {
          e.preventDefault();
          triggers[triggers.length - 1].focus();
        }
      });
    });
  });
})();

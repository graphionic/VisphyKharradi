/** Landing-page entrances: progressive enhancement, no hidden-content dependency. */
(function () {
  'use strict';

  function init() {
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motion.matches || !window.IntersectionObserver || !Element.prototype.animate) return;

    // Animate content groups, never sections containing fixed drawers or moving tracks.
    // Hero and credibility retain their existing entrance choreography.
    const selectors = [
      '.reality__chapter-bar', '.reality__left', '.reality__row',
      '#programs .pk__intro', '#programs .pk__bar', '#programs .pk__grid',
      '#programs .pv__intro', '#programs .pv__bar', '#programs .pv__grid',
      '#programs .pkg-intro', '#programs .pkg-bar', '#programs .pkg-grid',
      '.crj__intro', '.crj__carousel',
      '.approach-conversion-left', '.approach-conversion-right',
      '.faq-chapter-bar', '.faq-headline', '.faq-lead', '.faq-meta-pill', '.faq-item'
    ];
    const pending = new Set(document.querySelectorAll(selectors.join(',')));
    const running = new Map();

    function finish(element) {
      observer.unobserve(element);
      pending.delete(element);
      const animation = running.get(element);
      if (animation) animation.cancel();
      running.delete(element);
    }

    const observer = new IntersectionObserver(entries => {
      let stagger = 0;
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const element = entry.target;
        observer.unobserve(element);
        pending.delete(element);
        // Content above a restored scroll position needs no entrance.
        if (motion.matches || entry.boundingClientRect.bottom <= 0 || element.contains(document.activeElement)) return;
        const animation = element.animate([
          { opacity: 0, translate: '0 22px' },
          { opacity: 1, translate: '0 0' }
        ], {
          duration: 620,
          delay: Math.min(stagger++ * 65, 195),
          easing: 'cubic-bezier(.16, .84, .24, 1)',
          fill: 'backwards'
        });
        running.set(element, animation);
        animation.onfinish = () => running.delete(element);
      });
    }, { threshold: 0, rootMargin: '0px 0px -24px 0px' });

    pending.forEach(element => observer.observe(element));

    // Keyboard focus must always reveal its target immediately.
    document.addEventListener('focusin', event => {
      [...pending, ...running.keys()].forEach(element => {
        if (element.contains(event.target)) finish(element);
      });
    });

    motion.addEventListener('change', () => {
      if (!motion.matches) return;
      observer.disconnect();
      pending.clear();
      running.forEach(animation => animation.cancel());
      running.clear();
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

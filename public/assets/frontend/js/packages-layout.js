/* Shared layout behavior; each concept keeps its existing card renderer. */
(function () {
  'use strict';
  window.PackageLayout = {
    create: function (root, track, onChange) {
      var settings = root.closest('.package-display');
      var mobile = window.matchMedia('(max-width: 640px)');
      var tablet = window.matchMedia('(max-width: 1024px)');
      var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
      var carousel = false;
      var columns = 0;
      var originalLabel = track.getAttribute('aria-label');
      track.id = track.id || 'package-card-track';
      track.classList.add('package-layout-track');

      var controls = document.createElement('div');
      controls.className = 'package-layout-controls';
      controls.hidden = true;
      controls.innerHTML = '<button type="button" data-slide="-1" aria-label="Previous packages">' +
        '<span aria-hidden="true">←</span> Previous</button>' +
        '<span class="package-layout-progress" role="status" aria-live="polite" aria-atomic="true"></span>' +
        '<button type="button" data-slide="1" aria-label="Next packages">Next <span aria-hidden="true">→</span></button>';
      track.after(controls);
      var buttons = controls.querySelectorAll('button');
      var progress = controls.querySelector('.package-layout-progress');
      buttons.forEach(function (button) { button.setAttribute('aria-controls', track.id); });

      function refresh() {
        var cards = Array.from(track.children);
        controls.hidden = !carousel || cards.length < 2;
        if (!carousel) return;
        var bounds = track.getBoundingClientRect();
        var visible = cards.map(function (card, i) {
          var r = card.getBoundingClientRect();
          return r.right > bounds.left + 2 && r.left < bounds.right - 2 ? i : -1;
        }).filter(function (i) { return i >= 0; });
        var first = visible.length ? visible[0] + 1 : 0;
        var last = visible.length ? visible[visible.length - 1] + 1 : 0;
        var text = (first === last ? first : first + '–' + last) + ' / ' + cards.length;
        if (progress.textContent !== text) progress.textContent = text;
        buttons[0].disabled = track.scrollLeft <= 2;
        buttons[1].disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 2;
      }

      function move(direction) {
        if (!carousel || !track.firstElementChild) return;
        var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        var step = track.firstElementChild.getBoundingClientRect().width + gap;
        track.scrollBy({ left: direction * step, behavior: reduced.matches ? 'auto' : 'smooth' });
      }
      buttons.forEach(function (button) {
        button.addEventListener('click', function () { move(Number(button.dataset.slide)); });
      });
      track.addEventListener('keydown', function (event) {
        if (!carousel || event.target !== track) return;
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
          event.preventDefault();
          move(event.key === 'ArrowRight' ? 1 : -1);
        }
      });
      var pending = false;
      track.addEventListener('scroll', function () {
        if (pending) return;
        pending = true;
        requestAnimationFrame(function () { pending = false; refresh(); });
      }, { passive: true });

      function gridColumns() {
        return Math.max(1, parseInt(getComputedStyle(track).getPropertyValue('--package-columns'), 10) || 1);
      }
      function fillRows(count) {
        var width = gridColumns();
        return Math.ceil(count / width) * width;
      }

      function apply(initial) {
        var device = mobile.matches ? 'mobile' : tablet.matches ? 'tablet' : 'desktop';
        var mode = settings ? settings.dataset['layout' + device[0].toUpperCase() + device.slice(1)] : null;
        var next = (mode || (device === 'desktop' ? 'grid' : 'carousel')) === 'carousel';
        var nextColumns = gridColumns();
        var changed = carousel !== next || (!next && columns !== nextColumns);
        columns = nextColumns;
        carousel = next;
        root.classList.toggle('packages--carousel', carousel);
        if (carousel) {
          track.tabIndex = 0;
          track.setAttribute('aria-label', 'Programs carousel — use arrow keys or swipe to browse');
        } else {
          track.removeAttribute('tabindex');
          if (originalLabel === null) track.removeAttribute('aria-label');
          else track.setAttribute('aria-label', originalLabel);
        }
        if (changed) track.scrollLeft = 0;
        if (changed && !initial) onChange();
        refresh();
      }
      mobile.addEventListener('change', function () { apply(false); });
      tablet.addEventListener('change', function () { apply(false); });
      new ResizeObserver(refresh).observe(track);
      apply(true);
      return {
        isCarousel: function () { return carousel; },
        pageSize: function () { return fillRows(6); },
        fillRows: fillRows,
        refresh: refresh,
        reset: function () { track.scrollLeft = 0; refresh(); }
      };
    }
  };
})();

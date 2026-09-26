/* ==========================================================================
   FTPRENEUR — Client Results · Transformation Journal · grouped carousel
   3 stories per page ≥1100px · 2 on tablet · 1 on mobile. Vanilla JS.
   Order = existing display order (featured records first). Cards show the
   first TWO metrics in display order; everything else belongs to Phase 09.
   ========================================================================== */
(function () {
  'use strict';

  var STORIES = window.CRJ_STORIES || [];  // shared with the drawer (stories.js)

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  var TONES = ['ultra', 'mint', 'mari'];

  // identity: real photo if one exists, otherwise editorial initials on a pale tone tint
  function identity(s) {
    var ini = '<span class="crj-s__ini">' + esc(s.initials) + '</span>';
    if (!s.image) return '<div class="crj-s__media" aria-hidden="true">' + ini + '</div>';
    return '<div class="crj-s__media has-img">' +
      '<img src="' + esc(s.image) + '" alt="' + esc(s.name) + '" width="104" height="88" loading="lazy" decoding="async">' + ini +
      (s.imageSample ? '<em class="rv-sample" aria-hidden="true">Sample image</em>' : '') + '</div>';
  }

  function focusLine(f) {
    if (!f.length) return '<p class="crj-s__focus" aria-hidden="true"></p>'; // keeps the subgrid rows aligned
    var shown = f.slice(0, 2).map(esc).join('<i></i>');
    return '<p class="crj-s__focus">' + shown + (f.length > 2 ? '<i></i><span title="' + esc(f.slice(2).join(', ')) + '">+' + (f.length - 2) + '</span>' : '') + '</p>';
  }

  function story(s, i) {
    var tone = TONES[i % 3];
    var metrics = s.results.slice(0, 2).map(function (m) {
      var u = m.unit ? '<small>' + esc(m.unit) + '</small>' : '';
      return '<li><p class="crj-s__lbl">' + esc(m.label) + '</p>' +
        '<p class="crj-s__row" aria-label="' + esc(m.label + ': ' + m.before + ' to ' + m.after + (m.unit ? ' ' + m.unit : '')) + '">' +
        '<span class="crj-s__b">' + esc(m.before) + u + '</span><span class="crj-s__line" aria-hidden="true"><i></i></span>' +
        '<span class="crj-s__a">' + esc(m.after) + u + '</span></p></li>';
    }).join('');
    return '<article class="crj-s" data-tone="' + tone + '" data-i="' + i + '" aria-label="' + esc(s.name) + '">' +
      '<div class="crj-s__top">' +
        identity(s) +
        '<div class="crj-s__id"><p class="crj-s__n">' + pad(i + 1) + '</p><p class="crj-s__name">' + esc(s.name) + '</p><p class="crj-s__ctx">' + esc(s.context) + '</p></div>' +
      '</div>' +
      '<p class="crj-s__prog">' + esc(s.program) + '<i></i><b>' + s.weeks + ' weeks</b></p>' +
      focusLine(s.focus) +
      '<blockquote class="crj-s__quote"><p>\u201C' + esc(s.quote) + '\u201D</p></blockquote>' +
      '<ol class="crj-s__res">' + metrics + '</ol>' +
      '<a class="crj-s__cta" href="#" aria-haspopup="dialog" data-story="' + i + '">View full story<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg></a>' +
    '</article>';
  }

  /* ------------------------------------------------------------ grouped carousel */
  var root = $('.crj__carousel'), track = $('.crj__track', root), viewport = $('.crj__viewport', root);
  var mq3 = window.matchMedia('(min-width: 1100px)'), mq2 = window.matchMedia('(min-width: 700px)');
  var per = 3, page = 0, pages = 1, pageEls = [];

  function perView() { return mq3.matches ? 3 : mq2.matches ? 2 : 1; }

  function build(keepStory) {
    per = perView();
    pages = Math.ceil(STORIES.length / per);
    var h = '';
    for (var p = 0; p < pages; p++) {
      h += '<div class="crj__page" data-per="' + per + '" role="group" aria-roledescription="slide" aria-label="Page ' + (p + 1) + ' of ' + pages + '">';
      STORIES.slice(p * per, p * per + per).forEach(function (s, k) { h += story(s, p * per + k); });
      h += '</div>';
    }
    track.innerHTML = h;
    pageEls = $$('.crj__page', track);
    page = Math.min(pages - 1, Math.floor((keepStory || 0) / per));
    root.classList.add('is-static');
    sync(true);
    requestAnimationFrame(function () { root.classList.remove('is-static'); });
  }

  function place(extra) {
    track.style.transform = 'translate3d(calc(' + (-page * 100) + '% + ' + (extra || 0) + 'px),0,0)';
  }

  function sync(silent) {
    place();
    $('.crj__cur', root).textContent = pad(page + 1);
    $('.crj__tot', root).textContent = pad(pages);
    $('.crj__line i', root).style.setProperty('--p', ((page + 1) / pages).toFixed(3));
    var a = page * per + 1, b = Math.min(STORIES.length, a + per - 1);
    $('.crj__range', root).textContent = per === 1 ? 'Story ' + a + ' of ' + STORIES.length : 'Stories ' + a + '\u2013' + b + ' of ' + STORIES.length;
    pageEls.forEach(function (el, k) {
      var on = k === page;
      el.classList.toggle('is-active', on);
      el.setAttribute('aria-hidden', on ? 'false' : 'true');
      if (on) el.removeAttribute('inert'); else el.setAttribute('inert', '');
    });
    $('[data-prev]', root).disabled = page === 0;
    $('[data-next]', root).disabled = page === pages - 1;
    $('[data-next] span', root).textContent = per === 1 ? 'Next story' : 'Next stories';
  }

  function go(p) {
    p = Math.max(0, Math.min(pages - 1, p));
    if (p === page) { place(); return; }
    page = p; sync();
  }

  $('[data-prev]', root).addEventListener('click', function () { go(page - 1); });
  $('[data-next]', root).addEventListener('click', function () { go(page + 1); });
  root.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { e.preventDefault(); go(page + 1); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); go(page - 1); }
  });

  // swipe / drag with axis lock so vertical scrolling still works
  var x0 = null, y0 = 0, dx = 0, lock = null, pid = null, dragged = false;
  viewport.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'mouse' && (e.button !== 0 || e.target.closest('a, button'))) return;
    x0 = e.clientX; y0 = e.clientY; dx = 0; lock = null; pid = e.pointerId; dragged = false;
  });
  viewport.addEventListener('pointermove', function (e) {
    if (x0 == null || e.pointerId !== pid) return;
    dx = e.clientX - x0;
    if (lock == null && (Math.abs(dx) > 8 || Math.abs(e.clientY - y0) > 8)) {
      lock = Math.abs(dx) > Math.abs(e.clientY - y0) ? 'x' : 'y';
      if (lock === 'x') { viewport.setPointerCapture(pid); root.classList.add('is-dragging'); }
    }
    if (lock !== 'x') return;
    dragged = true;
    var edge = (page === 0 && dx > 0) || (page === pages - 1 && dx < 0);
    place(dx * (edge ? .3 : 1));
  });
  function end() {
    if (x0 == null) return;
    root.classList.remove('is-dragging'); x0 = null;
    if (lock === 'x' && Math.abs(dx) > 60) go(page + (dx < 0 ? 1 : -1)); else place();
    setTimeout(function () { dragged = false; }, 0);
  }
  viewport.addEventListener('pointerup', end);
  viewport.addEventListener('pointercancel', end);
  viewport.addEventListener('dragstart', function (e) { e.preventDefault(); });

  // regroup when the breakpoint changes, keeping the first visible story in view
  function regroup() { var first = page * per; if (perView() !== per) build(first); }
  mq3.addEventListener('change', regroup);
  mq2.addEventListener('change', regroup);

  // View full story → opens the Full Story bottom drawer (drawer.js)
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-story]'); if (!a || !root.contains(a)) return;
    e.preventDefault();
    if (dragged) return;
    if (window.CRD) window.CRD.open(+a.dataset.story, a);
  });

  // lets the drawer keep the carousel in step when browsing stories inside it
  window.CRJ = {
    showStory: function (i) { go(Math.floor(i / per)); },
    ctaFor: function (i) { return track.querySelector('[data-story="' + i + '"]'); }
  };

  track.addEventListener('error', function (e) {
    if (e.target.tagName === 'IMG') e.target.closest('.crj-s__media').classList.add('is-fallback');
  }, true);

  build(0);
})();

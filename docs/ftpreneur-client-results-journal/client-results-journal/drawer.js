/* ==========================================================================
   FTPRENEUR — Client Results · Full Story bottom drawer (prototype of Phase 09)
   Opens from any "View full story" link in the carousel. Same page, no route.
   Sections render only when they have content, so records without media
   collapse to story + outcomes with no empty states.
   ========================================================================== */
(function () {
  'use strict';

  var STORIES = window.CRJ_STORIES || [];
  var TONES = ['ultra', 'mint', 'mari'];
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  var ARROW = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg>';
  var ARROW_L = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H6M11 6l-6 6 6 6"/></svg>';

  var dr = $('#crd'), sheet = $('.crd__sheet', dr), scroller = $('.crd__scroll', dr), content = $('.crd__content', dr);
  var viewer = $('.crd__viewer', dr), vbody = $('.crd__vbody', dr);
  var cur = -1, opener = null, reportBtn = null, savedY = 0, closeT = null;

  /* ------------------------------------------------------------ pieces */
  // identity: same rule as the carousel. Real image if present, otherwise clean initials.
  function media(s, cls) {
    var ini = '<span class="crd-media__ini">' + esc(s.initials) + '</span>';
    if (!s.image) return '<span class="crd-media ' + cls + '" aria-hidden="true">' + ini + '</span>';
    return '<span class="crd-media ' + cls + ' has-img"><img src="' + esc(s.image) + '" alt="' + esc(s.name) + '">' + ini +
      (s.imageSample ? '<em class="rv-ph rv-ph--mini">Sample image</em>' : '') + '</span>';
  }
  // neutral prototype image region; the label is review-only (not production UI)
  function ph(label, cls) {
    return '<div class="crd-ph ' + (cls || '') + '" role="img" aria-label="' + esc(label) + ' (prototype placeholder)"><span class="rv-ph">' + esc(label) + '</span></div>';
  }
  function unit(u) { return u ? '<small>' + esc(u) + '</small>' : ''; }

  function sections(s, i) {
    var ev = s.evidence || {}, out = [];

    out.push({ label: 'Client story', title: 'How it unfolded', cls: 'crd-sec--story', html:
      '<div class="crd-story__grid"><div class="crd-story__text">' + s.story.map(function (p) { return '<p>' + esc(p) + '</p>'; }).join('') + '</div>' +
      '<aside class="crd-story__meta" aria-label="Journey details"><dl>' +
        '<div><dt>Program</dt><dd>' + esc(s.program) + '</dd></div>' +
        '<div><dt>Duration</dt><dd>' + s.weeks + ' weeks</dd></div>' +
        '<div><dt>Format</dt><dd>1:1 personalised coaching</dd></div>' +
        '<div><dt>Recorded outcomes</dt><dd>' + s.results.length + ' measurements</dd></div>' +
      '</dl></aside></div>' });

    out.push({ label: 'Recorded outcomes', title: 'What was recorded', note: s.recorded, html:
      '<ol class="crd-out">' + s.results.map(function (m) {
        return '<li><p class="crd-out__lbl">' + esc(m.label) + '</p>' +
          '<div class="crd-out__row" aria-label="' + esc(m.label + ': ' + m.before + ' to ' + m.after + (m.unit ? ' ' + m.unit : '')) + '">' +
            '<p class="crd-out__b"><span>' + esc(m.before) + unit(m.unit) + '</span>' + (m.beforeDate ? '<time>' + esc(m.beforeDate) + '</time>' : '') + '</p>' +
            '<span class="crd-out__line" aria-hidden="true"><i></i></span>' +
            '<p class="crd-out__a"><span>' + esc(m.after) + unit(m.unit) + '</span>' + (m.afterDate ? '<time>' + esc(m.afterDate) + '</time>' : '') + '</p>' +
          '</div></li>';
      }).join('') + '</ol>' });

    if (ev.beforeAfter) {
      var b = ev.beforeAfter.before, a = ev.beforeAfter.after;
      out.push({ label: 'Before & after', title: 'Before and after', html:
        '<div class="crd-ba">' +
          '<figure>' + ph('Before image', 'crd-ph--ba') + '<figcaption><b>Before</b><span>' + esc(b.caption) + ' · ' + esc(b.date) + '</span></figcaption></figure>' +
          '<span class="crd-ba__mid" aria-hidden="true">' + ARROW + '</span>' +
          '<figure>' + ph('After image', 'crd-ph--ba') + '<figcaption><b>After</b><span>' + esc(a.caption) + ' · ' + esc(a.date) + '</span></figcaption></figure>' +
        '</div>' });
    }
    if (ev.progress && ev.progress.length) {
      out.push({ label: 'Transformation gallery', title: 'Progress along the way', html:
        '<div class="crd-prog crd-prog--' + Math.min(ev.progress.length, 3) + '">' + ev.progress.slice(0, 3).map(function (g, k) {
          return '<figure class="crd-prog__f' + (k === 0 ? ' is-lead' : '') + '">' + ph('Progress image', 'crd-ph--prog') + '<figcaption>' + esc(g.caption) + '</figcaption></figure>';
        }).join('') + '</div>' });
    }
    if (ev.lifestyle && ev.lifestyle.length) {
      out.push({ label: 'Client story gallery', title: 'Moments from the journey', html:
        '<div class="crd-life">' + ev.lifestyle.map(function (g, k) {
          return '<figure class="crd-life__f is-' + esc(g.shape || 'square') + '" data-k="' + (k % 3) + '">' + ph('Story image', 'crd-ph--life') + '<figcaption>' + esc(g.caption) + '</figcaption></figure>';
        }).join('') + '</div>' });
    }
    if (ev.reports && ev.reports.length) {
      out.push({ label: 'Reports & evidence', title: 'Reports and evidence', html:
        '<ul class="crd-rep">' + ev.reports.map(function (r, k) {
          return '<li><span class="crd-rep__type">' + (r.type === 'pdf' ? 'PDF' : 'IMG') + '</span>' +
            '<div class="crd-rep__t"><p>' + esc(r.title) + '</p><span>' + esc(r.kind) + ' · ' + esc(r.date) + '</span></div>' +
            '<button class="crd-rep__view" type="button" data-report="' + k + '">View report' + ARROW + '</button></li>';
        }).join('') + '</ul>' });
    }
    return out;
  }

  function render(i) {
    var s = STORIES[i], tone = TONES[i % 3];
    dr.dataset.tone = tone;
    var secs = sections(s, i);
    var prev = STORIES[i - 1], next = STORIES[i + 1];
    var pn = function (t, k, dir) {
      if (!t) return '<span></span>';
      return '<button class="crd-pn__b crd-pn__b--' + dir + '" type="button" data-goto-story="' + k + '" data-tone="' + TONES[k % 3] + '">' +
        (dir === 'prev' ? ARROW_L + media(t, 'crd-media--xs') : '') +
        '<span class="crd-pn__t"><small>' + (dir === 'prev' ? 'Previous story' : 'Next story') + '</small><b>' + esc(t.name) + '</b><span>' + esc(t.program) + '</span></span>' +
        (dir === 'next' ? media(t, 'crd-media--xs') + ARROW : '') + '</button>';
    };

    content.innerHTML =
      '<section class="crd-ov">' +
        '<div class="crd-ov__id">' + media(s, 'crd-media--lg') +
          '<div>' +
          '<h2 class="crd-ov__name" id="crd-title">' + esc(s.name) + '</h2>' +
          '<p class="crd-ov__ctx">' + esc(s.context) + '</p>' +
          '<p class="crd-ov__prog">' + esc(s.program) + '<i></i><b>' + s.weeks + ' weeks</b></p></div>' +
        '</div>' +
        (s.focus.length ? '<div class="crd-ov__focus"><p class="crd-lbl crd-lbl--plain">Transformation focus</p><ul>' + s.focus.map(function (f) { return '<li>' + esc(f) + '</li>'; }).join('') + '</ul></div>' : '') +
      '</section>' +
      '<section class="crd-quote" aria-label="Testimonial"><blockquote><p>\u201C' + esc(s.quote) + '\u201D</p></blockquote><p class="crd-quote__by">' + esc(s.name) + '<span>' + esc(s.context) + '</span></p></section>' +
      secs.map(function (x, k) {
        return '<section class="crd-sec ' + (x.cls || '') + '"><header class="crd-sec__h"><p class="crd-lbl"><b>' + pad(k + 1) + '</b>' + esc(x.label) + '</p>' +
          '<h3>' + esc(x.title) + '</h3>' + (x.note ? '<p class="crd-sec__note">' + esc(x.note) + '</p>' : '') + '</header>' + x.html + '</section>';
      }).join('') +
      '<section class="crd-cta">' +
        '<div><h3>Your journey<br>will be your own.</h3><p>Every plan is built around the individual, their goals, lifestyle and starting point.</p></div>' +
        '<div class="crd-cta__act"><button class="crd-cta__p" type="button" data-cta="find">Find my program' + ARROW + '</button>' +
        '<button class="crd-cta__s" type="button" data-cta="explore">Explore programs</button>' +
        '<p class="crd-cta__fine">Individual outcomes vary.</p></div>' +
      '</section>' +
      '<nav class="crd-pn" aria-label="More client stories">' + pn(prev, i - 1, 'prev') + pn(next, i + 1, 'next') + '</nav>';

    // header (compact identity appears once the overview scrolls away)
    $('.crd__head-media', dr).innerHTML = media(s, 'crd-media--sm');
    $('.crd__head-n', dr).textContent = '/ ' + pad(i + 1);
    $('.crd__head-name', dr).innerHTML = esc(s.name) + '<span>' + esc(s.context) + '</span>';
    $('.crd__head-prog', dr).innerHTML = esc(s.program) + '<i></i><b>' + s.weeks + ' weeks</b>';
    content.querySelectorAll('img').forEach(function (im) { im.addEventListener('error', function () { im.parentNode.classList.add('is-fallback'); }); });
    $$('.crd__head-media img', dr).forEach(function (im) { im.addEventListener('error', function () { im.parentNode.classList.add('is-fallback'); }); });
    cur = i;
    onScroll();
  }

  /* ------------------------------------------------------------ open / close */
  function lock() {
    savedY = window.scrollY;
    var sb = window.innerWidth - document.documentElement.clientWidth;
    document.documentElement.classList.add('crd-lock');
    if (sb > 0) document.documentElement.style.paddingRight = sb + 'px';
  }
  function unlock() {
    document.documentElement.classList.remove('crd-lock');
    document.documentElement.style.paddingRight = '';
    window.scrollTo(0, savedY);
  }

  function open(i, from) {
    clearTimeout(closeT);
    opener = from || document.activeElement;
    render(i);
    scroller.scrollTop = 0;
    closeViewer(true);
    if (dr.hidden) {
      lock();
      dr.hidden = false;
      void sheet.offsetWidth;
      requestAnimationFrame(function () { dr.classList.add('is-open'); });
    }
    dr.classList.remove('is-closing');
    sheet.focus({ preventScroll: true });
  }

  function close() {
    if (dr.hidden || dr.classList.contains('is-closing')) return;
    dr.classList.add('is-closing');
    dr.classList.remove('is-open');
    closeT = setTimeout(function () {
      dr.hidden = true; dr.classList.remove('is-closing');
      closeViewer(true);
      unlock();
      // focus returns to the "View full story" of the story last shown (the opener if unchanged)
      var back = (window.CRJ && window.CRJ.ctaFor(cur)) || opener;
      if (back && document.contains(back)) back.focus({ preventScroll: true });
    }, reduced.matches ? 0 : 320);
  }

  function switchTo(i) {
    if (i < 0 || i >= STORIES.length || i === cur) return;
    if (window.CRJ) window.CRJ.showStory(i);   // keep the carousel page in step
    var go = function () { render(i); scroller.scrollTop = 0; content.classList.remove('is-swap'); sheet.focus({ preventScroll: true }); };
    if (reduced.matches) { go(); return; }
    content.classList.add('is-swap');
    setTimeout(go, 180);
  }

  /* ------------------------------------------------------------ report viewer (inner layer) */
  function openViewer(k, btn) {
    var r = STORIES[cur].evidence.reports[k];
    reportBtn = btn;
    $('.crd__vname', dr).textContent = r.title;
    $('.crd__vmeta', dr).textContent = r.kind + ' · ' + r.date + ' · ' + (r.type === 'pdf' ? 'PDF' + (r.pages ? ', ' + r.pages + (r.pages > 1 ? ' pages' : ' page') : '') : 'Image');
    if (r.type === 'pdf') {
      var pages = '';
      for (var p = 1; p <= (r.pages || 1); p++) {
        pages += '<div class="crd-doc" role="img" aria-label="PDF page ' + p + ' (prototype placeholder)"><span class="rv-ph">PDF document · page ' + p + '</span>' +
          '<div class="crd-doc__sk"><i style="width:38%"></i><i style="width:22%"></i><b></b><i></i><i></i><i style="width:82%"></i><b></b><i></i><i style="width:64%"></i><i></i><i style="width:74%"></i></div></div>';
      }
      vbody.innerHTML = '<div class="crd-docs">' + pages + '</div>';
    } else {
      vbody.innerHTML = '<div class="crd-vimg">' + ph('Report image', 'crd-ph--report') + '</div>';
    }
    vbody.scrollTop = 0;
    viewer.hidden = false;
    scroller.setAttribute('inert', '');
    $('.crd__head', dr).setAttribute('inert', '');
    void viewer.offsetWidth;
    viewer.classList.add('is-open');
    $('.crd__back', viewer).focus({ preventScroll: true });
  }
  function closeViewer(instant) {
    if (viewer.hidden) return;
    viewer.classList.remove('is-open');
    scroller.removeAttribute('inert');
    $('.crd__head', dr).removeAttribute('inert');
    var done = function () {
      viewer.hidden = true; vbody.innerHTML = '';
      if (!instant && reportBtn && document.contains(reportBtn)) reportBtn.focus({ preventScroll: true });
    };
    if (instant || reduced.matches) done(); else setTimeout(done, 300);
  }

  /* ------------------------------------------------------------ events */
  dr.addEventListener('click', function (e) {
    var t;
    if (e.target.closest('[data-viewer-back]')) { closeViewer(); return; }
    if (e.target.closest('[data-crd-close]')) { close(); return; }
    if ((t = e.target.closest('[data-goto-story]'))) { switchTo(+t.dataset.gotoStory); return; }
    if ((t = e.target.closest('[data-report]'))) { openViewer(+t.dataset.report, t); return; }
    if ((t = e.target.closest('[data-cta]'))) { note(t.dataset.cta === 'find' ? 'Prototype: opens the program finder on the landing page.' : 'Prototype: scrolls to the Programs section on the landing page.'); }
  });

  document.addEventListener('keydown', function (e) {
    if (dr.hidden) return;
    if (e.key === 'Escape') { e.preventDefault(); if (!viewer.hidden) closeViewer(); else close(); return; }
    if (e.key !== 'Tab') return;
    var scope = viewer.hidden ? sheet : viewer;
    var f = $$('button:not([disabled]), a[href], [tabindex="0"]', scope).filter(function (el) { return el.offsetParent !== null && !el.closest('[inert]') && !el.closest('[hidden]'); });
    if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && (document.activeElement === first || document.activeElement === sheet)) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });

  // compact identity in the header once the overview has scrolled past
  function onScroll() { dr.classList.toggle('is-scrolled', scroller.scrollTop > 190); }
  scroller.addEventListener('scroll', onScroll, { passive: true });

  // drag the handle / header down to dismiss (touch)
  (function () {
    var head = $('.crd__head', dr), y0 = null, dy = 0;
    head.addEventListener('pointerdown', function (e) { if (e.target.closest('button') || e.pointerType === 'mouse') return; y0 = e.clientY; dy = 0; head.setPointerCapture(e.pointerId); dr.classList.add('is-dragging'); });
    head.addEventListener('pointermove', function (e) { if (y0 == null) return; dy = Math.max(0, e.clientY - y0); sheet.style.transform = 'translate(-50%,' + dy + 'px)'; });
    function end() { if (y0 == null) return; y0 = null; dr.classList.remove('is-dragging'); sheet.style.transform = ''; if (dy > 120) close(); }
    head.addEventListener('pointerup', end); head.addEventListener('pointercancel', end);
  })();

  var toastEl = document.createElement('div'); toastEl.className = 'crd-toast'; toastEl.setAttribute('role', 'status'); sheet.appendChild(toastEl);
  var tt;
  function note(msg) { toastEl.textContent = msg; toastEl.classList.add('is-on'); clearTimeout(tt); tt = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2400); }

  window.CRD = { open: open, close: close };
})();

/* ==========================================================================
   FTPRENEUR — Program Experience · Concept 04 · PREMIUM VISUAL PROGRAM GRID
   Vanilla JS, no dependencies. Reads window.ED_PROGRAMS (same contract as
   Concepts 01 / 02). One card template → any number of programs.
   ========================================================================== */
(function () {
  'use strict';

  var PROGRAMS = window.ED_PROGRAMS || [];
  var NOTES = window.ED_JOURNEY_NOTES || {};
  var PAGE = 6;
  var FILTERS = ['All', 'Weight', 'Metabolic', 'Strength', 'Nutrition', 'Lifestyle'];
  var ACCENT = { Weight: 'ultra', Lifestyle: 'ultra', Metabolic: 'mint', Nutrition: 'mint', Strength: 'marigold', Complete: 'ink' };

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var inr = function (n) { return '\u20B9' + Number(n).toLocaleString('en-IN'); };
  var fileOf = function (p) { return (p.image || '').split('/').pop(); };
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  var mqHover = window.matchMedia('(hover: hover)');

  var root = $('#programs');
  var grid = $('.pv__grid', root);
  var tpl = $('#pv-card-tpl');
  var filtersEl = $('.pv__filters', root);
  var line = $('.pv__filters-line', root);
  var status = $('.pv__status', root);
  var more = $('.pv__more', root);
  var moreBtn = $('.pv__more-btn', root);

  var state = { filter: 'All', shown: PAGE };
  var cards = {};  // slug → element

  /* ------------------------------------------------------------ cards */
  function build(p) {
    var el = tpl.content.firstElementChild.cloneNode(true);
    el.dataset.slug = p.slug;
    el.dataset.accent = ACCENT[p.category] || 'ultra';
    if (p.featured) el.classList.add('is-featured');
    var map = {
      number: p.number, category: p.category, name: p.name,
      line: p.eyebrow.charAt(0) + p.eyebrow.slice(1).toLowerCase(),
      durationWeeks: p.durationWeeks, price: inr(p.discountPrice || p.price),
      priceWas: p.discountPrice ? inr(p.price) : '', featuredLabel: p.featuredLabel || '', file: fileOf(p)
    };
    $$('[data-f]', el).forEach(function (n) { n.textContent = map[n.dataset.f] == null ? '' : map[n.dataset.f]; });
    var img = $('.pv-card__img', el);
    img.style.setProperty('--focus', p.imageFocus || '50% 40%');
    img.alt = p.imageAlt || '';
    img.addEventListener('error', function () { el.classList.add('is-missing'); });
    img.src = p.image;
    var cta = $('.pv-card__cta', el);
    cta.setAttribute('aria-label', 'Explore ' + p.name + ', ' + p.durationWeeks + ' weeks, ' + map.price);
    cta.addEventListener('click', function () { openDrawer(p.slug, el); });
    return el;
  }

  function list() {
    return PROGRAMS.filter(function (p) { return state.filter === 'All' || p.category === state.filter; });
  }

  function cols() { return getComputedStyle(grid).gridTemplateColumns.split(' ').length; }

  function rhythm() {
    // chequerboard mirroring: diagonal cut and number swap side card to card
    var c = cols();
    $$('.pv-card', grid).forEach(function (el, i) {
      var r = Math.floor(i / c), k = i % c;
      el.dataset.side = c === 1 ? (i % 2 ? 'r' : 'l') : ((r + k) % 2 ? 'r' : 'l');
    });
  }

  function render(animFrom) {
    var items = list(), vis = items.slice(0, state.shown);
    var eager = grid.children.length === 0;
    grid.innerHTML = '';
    vis.forEach(function (p, i) {
      var el = cards[p.slug] || (cards[p.slug] = build(p));
      el.classList.remove('is-in', 'is-out', 'is-pre');
      $('.pv-card__img', el).loading = (eager && i < 6) ? 'eager' : 'lazy';
      grid.appendChild(el);
      if (animFrom != null && i >= animFrom && !reduced.matches) {
        el.style.setProperty('--d', ((i - animFrom) * 0.07).toFixed(2) + 's');
        el.classList.add('is-pre');
      }
    });
    rhythm();
    if (animFrom != null && !reduced.matches) {
      void grid.offsetWidth;
      requestAnimationFrame(function () {
        $$('.pv-card.is-pre', grid).forEach(function (el) { el.classList.add('is-in'); el.classList.remove('is-pre'); });
      });
    }
    var n = vis.length, t = items.length;
    status.innerHTML = 'Showing <b>' + pad(n) + '</b> of ' + pad(t);
    more.hidden = t <= PAGE;
    $('.pv__more-count', root).innerHTML = '<b>' + pad(n) + '</b> / ' + pad(t);
    $('.pv__more-track', root).style.setProperty('--p', (n / t).toFixed(3));
    var done = n >= t;
    moreBtn.classList.toggle('is-less', done);
    $('.pv__more-label', root).textContent = done ? 'Show fewer programs' : 'Show more programs';
    moreBtn.setAttribute('aria-label', done ? 'Show fewer programs' : 'Show ' + Math.min(PAGE, t - n) + ' more programs, showing ' + n + ' of ' + t);
  }

  /* ------------------------------------------------------------ filter */
  FILTERS.forEach(function (f) {
    var b = document.createElement('button');
    var c = f === 'All' ? PROGRAMS.length : PROGRAMS.filter(function (p) { return p.category === f; }).length;
    b.className = 'pv__filter'; b.type = 'button'; b.dataset.f = f;
    b.innerHTML = f + '<sup>' + pad(c) + '</sup>';
    b.setAttribute('aria-pressed', f === state.filter ? 'true' : 'false');
    if (f === state.filter) b.classList.add('is-active');
    b.addEventListener('click', function () { setFilter(f); });
    filtersEl.insertBefore(b, line);
  });
  function moveLine() {
    var a = $('.pv__filter.is-active', filtersEl); if (!a) return;
    line.style.width = a.offsetWidth + 'px';
    line.style.transform = 'translateX(' + a.offsetLeft + 'px)';
  }
  function setFilter(f) {
    if (f === state.filter) return;
    state.filter = f; state.shown = PAGE;
    $$('.pv__filter', filtersEl).forEach(function (b) {
      var on = b.dataset.f === f; b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    moveLine();
    var cur = $$('.pv-card', grid);
    if (reduced.matches || !cur.length) { render(0); return; }
    cur.forEach(function (el) { el.classList.add('is-out'); });
    setTimeout(function () { render(0); }, 200);
  }

  /* ------------------------------------------------------------ show more */
  moreBtn.addEventListener('click', function () {
    var t = list().length;
    if (state.shown >= t) {
      state.shown = PAGE; render();
      root.scrollIntoView({ behavior: reduced.matches ? 'auto' : 'smooth', block: 'start' });
      return;
    }
    var from = state.shown;
    state.shown = Math.min(t, state.shown + PAGE);
    render(from);
    var first = grid.children[from];
    if (first) setTimeout(function () { $('.pv-card__cta', first).focus({ preventScroll: true }); }, 80);
  });

  var rz;
  window.addEventListener('resize', function () { clearTimeout(rz); rz = setTimeout(function () { rhythm(); moveLine(); }, 120); });

  /* ============================================================ DRAWER */
  var dr = $('#pv-drawer');
  var sheet = $('.pvd__sheet', dr);
  var main = $('.pvd__main', dr);
  var body = $('.pvd__body', dr);
  var cur = null, lastFocus = null, stageIdx = 0, spans = [];

  function scroller() { return getComputedStyle(body).display === 'block' ? body : main; }

  function fill(p) {
    cur = p;
    dr.dataset.accent = ACCENT[p.category] || 'ultra';
    dr.dataset.featured = p.featured ? 'true' : 'false';
    var rv = parseInt((p.reviewFrequency || '').replace(/\D+/g, ''), 10) || 0;
    var map = {
      number: p.number, category: p.category, name: p.name, durationWeeks: p.durationWeeks,
      summary: p.summary, suitableFor: p.suitableFor, healthNote: p.healthNote || '',
      format: p.format, featuredLabel: p.featuredLabel || '', file: fileOf(p),
      eyebrowCase: p.eyebrow,
      reviewEvery: rv ? rv : '\u2014', reviewUnit: rv ? 'weekly reviews' : 'reviews',
      reviewLc: (p.reviewFrequency || '').toLowerCase(),
      price: inr(p.discountPrice || p.price), priceWas: p.discountPrice ? inr(p.price) : ''
    };
    $$('[data-f]', dr).forEach(function (n) { n.textContent = map[n.dataset.f] == null ? '' : map[n.dataset.f]; });
    $('.pvd__stats div:last-child dd span', dr).textContent = rv ? (rv === 1 ? 'weekly' : 'weeks apart') : '';
    $('.pvd__stats div:last-child dd b', dr).textContent = rv ? rv : '\u2014';

    var img = $('.pvd__img', dr);
    dr.classList.remove('is-missing');
    img.onerror = function () { dr.classList.add('is-missing'); };
    img.style.setProperty('--focus', p.imageFocus || '50% 40%');
    img.alt = p.imageAlt || '';
    img.src = p.image;

    // focus: emphasis bar
    var mix = $('.pvd__mix', dr), key = $('.pvd__mix-key', dr), words = { 3: 'Lead', 2: 'Core', 1: 'Support' };
    mix.innerHTML = ''; key.innerHTML = '';
    p.disciplines.slice().sort(function (a, b) { return b.weight - a.weight; }).forEach(function (d, i) {
      var s = document.createElement('span');
      s.className = 'w' + d.weight; s.style.flexGrow = d.weight; s.style.setProperty('--dd', (i * 0.1) + 's');
      s.textContent = d.weight > 1 ? d.name : '';
      s.title = d.name + ' \u00B7 ' + words[d.weight];
      mix.appendChild(s);
      var li = document.createElement('li');
      li.innerHTML = '<b></b><small>' + words[d.weight] + '</small>';
      li.firstChild.textContent = d.name;
      key.appendChild(li);
    });
    mix.setAttribute('aria-label', 'Program emphasis: ' + p.disciplines.map(function (d) { return d.name + ' ' + words[d.weight].toLowerCase(); }).join(', '));
    $('.pvd__chips', dr).innerHTML = '';
    p.focusAreas.forEach(function (f) { var li = document.createElement('li'); li.textContent = f; $('.pvd__chips', dr).appendChild(li); });
    $('.pvd__inc', dr).innerHTML = '';
    p.included.forEach(function (f) { var li = document.createElement('li'); li.textContent = f; $('.pvd__inc', dr).appendChild(li); });

    var inv = $('.pvd__inv-list', dr); inv.innerHTML = '';
    [p.durationWeeks + ' weeks, 1:1', 'Reviews ' + (p.reviewFrequency || '').toLowerCase(), p.included.length + ' components included', 'Personalised after assessment'].forEach(function (t) {
      var li = document.createElement('li'); li.textContent = t; inv.appendChild(li);
    });

    journey(p);

    var nx = neighbour(1);
    $('.pvd__nextp-n', dr).textContent = nx.number;
    $('.pvd__nextp-name', dr).textContent = nx.name;
    var ni = $('.pvd__nextp-img img', dr), nw = $('.pvd__nextp-img', dr);
    nw.classList.remove('is-missing');
    ni.onerror = function () { nw.classList.add('is-missing'); };
    ni.src = nx.image;

    $('.pvd__cta', dr).classList.remove('is-done');
    $('.pvd__cta', dr).setAttribute('aria-label', 'Start this program: ' + p.name);
    $$('.pvd__r', dr).forEach(function (el, i) { el.style.setProperty('--i', i); });
    $$('.pvd__sec', dr).forEach(function (s) { s.classList.remove('is-seen'); });
  }

  /* journey: weighted week spans (shared algorithm with the Concept 03 roadmap) */
  function journey(p) {
    var N = p.durationWeeks, J = p.journey, L = J.length;
    var w = J.map(function (s, i) { return i === 0 ? 1 : i === 1 ? 1.4 : i === Math.floor(L / 2) ? 4 : 2; });
    var W = w.reduce(function (a, b) { return a + b; }, 0), cum = 0;
    spans = [];
    J.forEach(function (s, i) {
      var st = Math.max(1, Math.round(cum / W * N) + 1); cum += w[i];
      var en = i === L - 1 ? N : Math.max(st, Math.round(cum / W * N));
      spans.push([Math.min(st, N), Math.min(en, N)]);
    });
    var ol = $('.pvd__stages', dr); ol.innerHTML = '';
    J.forEach(function (s, i) {
      var li = document.createElement('li'), sp = spans[i];
      li.innerHTML = '<button class="pvd__stage" type="button" aria-pressed="false"><span class="pvd__stage-n">' + pad(i + 1) + '</span><span class="pvd__stage-t"></span><span class="pvd__stage-w">Week ' + sp[0] + (sp[1] > sp[0] ? '\u2013' + sp[1] : '') + '</span></button>';
      $('.pvd__stage-t', li).textContent = s;
      var b = $('button', li);
      b.addEventListener('click', function () { setStage(i); });
      b.addEventListener('mouseenter', function () { if (mqHover.matches) setStage(i); });
      ol.appendChild(li);
    });
    var rv = parseInt((p.reviewFrequency || '').replace(/\D+/g, ''), 10) || 0;
    var wk = $('.pvd__weeks', dr), h = '';
    for (var k = 1; k <= N; k++) {
      if (k === 1 || k === N || (rv && k % rv === 0)) h += '<span data-w="' + k + '" style="left:' + ((k - .5) / N * 100).toFixed(2) + '%">W' + k + '</span>';
    }
    wk.innerHTML = h;
    setStage(0, true);
  }
  function setStage(i, silent) {
    stageIdx = i;
    var sp = spans[i], N = cur.durationWeeks, name = cur.journey[i];
    $$('.pvd__stage', dr).forEach(function (b, k) { b.setAttribute('aria-pressed', k === i ? 'true' : 'false'); });
    $('.pvd__rail-fill', dr).style.setProperty('--p', (sp[1] / N).toFixed(3));
    $$('.pvd__weeks span', dr).forEach(function (s) { s.classList.toggle('is-on', +s.dataset.w <= sp[1]); });
    var note = $('.pvd__stage-note', dr);
    note.innerHTML = '<b></b> ';
    note.firstChild.textContent = pad(i + 1) + ' \u00B7 ' + name + '.';
    note.appendChild(document.createTextNode(NOTES[name] || ''));
    if (!silent) { note.classList.remove('is-tick'); void note.offsetWidth; note.classList.add('is-tick'); }
  }

  function order() { return list().slice(0, Math.max(state.shown, 1)); }
  function neighbour(dir) {
    var o = order(); if (!o.length) o = PROGRAMS;
    var i = o.indexOf(cur); if (i < 0) { o = PROGRAMS; i = o.indexOf(cur); }
    return o[(i + dir + o.length) % o.length];
  }

  /* scroll-spy + progress */
  function spy() {
    var sc = scroller(), top = sc.scrollTop, max = sc.scrollHeight - sc.clientHeight;
    $('.pvd__nav-line', dr).firstElementChild.style.setProperty('--p', max > 0 ? (top / max).toFixed(3) : 1);
    var navH = $('.pvd__nav', dr).offsetHeight, sRect = sc.getBoundingClientRect(), active = 1;
    $$('.pvd__sec', dr).forEach(function (s) {
      var r = s.getBoundingClientRect();
      if (r.top - sRect.top <= navH + 80) active = +s.dataset.sec;
      if (r.top - sRect.top < sc.clientHeight * .85) s.classList.add('is-seen');
    });
    if (max > 0 && top >= max - 4) active = 5;
    $$('.pvd__nav a', dr).forEach(function (a) { a.classList.toggle('is-active', +a.dataset.sec === active); });
  }
  main.addEventListener('scroll', spy, { passive: true });
  body.addEventListener('scroll', spy, { passive: true });
  $$('.pvd__nav a', dr).forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      var s = $(a.getAttribute('href'), dr), sc = scroller();
      var y = s.getBoundingClientRect().top - sc.getBoundingClientRect().top + sc.scrollTop - $('.pvd__nav', dr).offsetHeight - 14;
      if (sc === body) y += 0;
      sc.scrollTo({ top: Math.max(0, y), behavior: reduced.matches ? 'auto' : 'smooth' });
    });
  });

  function markCard() {
    $$('.pv-card.is-selected', grid).forEach(function (c) { c.classList.remove('is-selected'); });
    if (cur && cards[cur.slug]) cards[cur.slug].classList.add('is-selected');
  }

  function openDrawer(slug, from) {
    var p = PROGRAMS.filter(function (x) { return x.slug === slug; })[0]; if (!p) return;
    lastFocus = from ? $('.pv-card__cta', from) : document.activeElement;
    fill(p);
    dr.hidden = false;
    dr.classList.remove('is-closing');
    document.body.classList.add('is-locked');
    main.scrollTop = 0; body.scrollTop = 0;
    void sheet.offsetWidth;
    requestAnimationFrame(function () { dr.classList.add('is-open'); spy(); });
    sheet.focus({ preventScroll: true });
    markCard();
    history.replaceState(null, '', '#program=' + p.slug);
  }

  function closeDrawer() {
    if (dr.hidden || dr.classList.contains('is-closing')) return;
    dr.classList.add('is-closing');
    dr.classList.remove('is-open');
    sheet.style.transform = '';
    var done = function () {
      dr.hidden = true; dr.classList.remove('is-closing');
      document.body.classList.remove('is-locked');
      $$('.pv-card.is-selected', grid).forEach(function (c) { c.classList.remove('is-selected'); });
      history.replaceState(null, '', location.pathname + location.search);
      if (lastFocus && document.contains(lastFocus)) lastFocus.focus({ preventScroll: true });
    };
    setTimeout(done, reduced.matches ? 0 : 400);
  }

  function step(dir) {
    var nx = neighbour(dir); if (!nx || nx === cur) return;
    if (reduced.matches) { fill(nx); after(); return; }
    dr.classList.add('is-swapping');
    setTimeout(function () { fill(nx); after(); dr.classList.remove('is-swapping'); }, 200);
    function after() {
      main.scrollTop = 0; body.scrollTop = 0; spy(); markCard();
      history.replaceState(null, '', '#program=' + nx.slug);
      var c = cards[nx.slug];
      if (c) lastFocus = $('.pv-card__cta', c);
    }
  }

  $$('[data-close]', dr).forEach(function (b) { b.addEventListener('click', closeDrawer); });
  $$('[data-prev]', dr).forEach(function (b) { b.addEventListener('click', function () { step(-1); }); });
  $$('[data-next]', dr).forEach(function (b) { b.addEventListener('click', function () { step(1); }); });

  var toastT;
  function toast(msg) {
    var t = $('.pvd__toast', dr);
    t.innerHTML = '<i>\u25CF</i>'; t.appendChild(document.createTextNode(msg));
    t.classList.add('is-on'); clearTimeout(toastT);
    toastT = setTimeout(function () { t.classList.remove('is-on'); }, 2600);
  }
  $('[data-start]', dr).addEventListener('click', function () {
    this.classList.add('is-done');
    toast(cur.name + ' selected. Prototype only: no checkout.');
  });
  $('[data-talk]', dr).addEventListener('click', function () { toast('A consultation request would open here (prototype).'); });

  // keyboard: Esc, arrows, focus trap
  document.addEventListener('keydown', function (e) {
    if (dr.hidden) return;
    if (e.key === 'Escape') { e.preventDefault(); closeDrawer(); return; }
    var tag = (document.activeElement && document.activeElement.tagName) || '';
    if ((e.key === 'ArrowRight' || e.key === 'ArrowLeft') && !/INPUT|TEXTAREA|SELECT/.test(tag)) { e.preventDefault(); step(e.key === 'ArrowRight' ? 1 : -1); return; }
    if (e.key === 'Tab') {
      var f = $$('button, a[href], [tabindex]:not([tabindex="-1"])', sheet).filter(function (el) { return el.offsetParent !== null; });
      if (!f.length) return;
      var a = f[0], z = f[f.length - 1];
      if (e.shiftKey && (document.activeElement === a || document.activeElement === sheet)) { e.preventDefault(); z.focus(); }
      else if (!e.shiftKey && document.activeElement === z) { e.preventDefault(); a.focus(); }
    }
  });

  // drag the header down to dismiss (touch + pen + mouse)
  (function () {
    var head = $('.pvd__head', dr), y0 = null, dy = 0, id = null;
    head.addEventListener('pointerdown', function (e) {
      if (e.target.closest('button')) return;
      y0 = e.clientY; dy = 0; id = e.pointerId; head.setPointerCapture(id); dr.classList.add('is-dragging');
    });
    head.addEventListener('pointermove', function (e) {
      if (y0 == null) return;
      dy = Math.max(0, e.clientY - y0);
      sheet.style.transform = 'translate(-50%,' + dy + 'px)';
    });
    function end() {
      if (y0 == null) return;
      dr.classList.remove('is-dragging'); y0 = null;
      if (dy > Math.min(140, sheet.offsetHeight * .18)) closeDrawer(); else sheet.style.transform = '';
    }
    head.addEventListener('pointerup', end);
    head.addEventListener('pointercancel', end);
  })();

  /* ------------------------------------------------------------ boot */
  render();
  moveLine();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(moveLine);
  function fromHash() {
    var m = /program=([\w-]+)/.exec(location.hash);
    if (m) { var c = cards[m[1]]; openDrawer(m[1], c || null); }
  }
  fromHash();
  window.addEventListener('hashchange', function () { if (dr.hidden) fromHash(); });
})();

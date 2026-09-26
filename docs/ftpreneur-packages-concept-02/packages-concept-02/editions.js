/* ==========================================================================
   FTPRENEUR — Program Experience · Concept 02 · Visual Program Editions
   Vanilla JS. Cards are generated from window.ED_PROGRAMS via <template>.
   ========================================================================== */
(function () {
  'use strict';

  var PROGRAMS = window.ED_PROGRAMS || [];
  var NOTES = window.ED_JOURNEY_NOTES || {};
  var BATCH = 6;
  var CATS = ['All', 'Weight', 'Metabolic', 'Strength', 'Nutrition', 'Lifestyle'];
  var ACCENT = { Weight: 'ultra', Metabolic: 'mint', Strength: 'marigold', Nutrition: 'mint', Lifestyle: 'ultra', Complete: 'ink' };
  var LEVEL = { 1: 'Support', 2: 'Core', 3: 'Lead' };
  var ARROW = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg>';
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var inr = function (n) { return '\u20B9' + Number(n).toLocaleString('en-IN'); };
  var fileOf = function (p) { return (p.image || '').split('/').pop(); };
  var bySlug = function (s) { for (var i = 0; i < PROGRAMS.length; i++) if (PROGRAMS[i].slug === s) return PROGRAMS[i]; return null; };

  /* ------------------------------------------------------------ section */
  var section = $('.ed');
  var grid = $('.ed__grid', section);
  var filtersEl = $('.ed__filters', section);
  var ink = $('.ed__filters-ink', section);
  var statusEl = $('.ed__status', section);
  var moreWrap = $('.ed__more', section);
  var moreBtn = $('.ed__more-btn', section);
  var moreLabel = $('.ed__more-label', section);
  var moreCount = $('.ed__more-count', section);
  var tpl = $('#ed-card-tpl');

  var state = { cat: 'All', shown: BATCH, list: PROGRAMS.slice(), busy: false };

  function listFor(cat) { return cat === 'All' ? PROGRAMS.slice() : PROGRAMS.filter(function (p) { return p.category === cat; }); }

  /* ---- filters ---- */
  CATS.forEach(function (c) {
    var b = document.createElement('button');
    b.type = 'button'; b.className = 'ed__filter' + (c === 'All' ? ' is-active' : '');
    b.dataset.cat = c; b.setAttribute('aria-pressed', c === 'All' ? 'true' : 'false');
    b.innerHTML = c + '<sup>' + pad(listFor(c).length) + '</sup>';
    b.addEventListener('click', function () { setFilter(c); });
    filtersEl.appendChild(b);
  });
  function moveInk() {
    var a = $('.ed__filter.is-active', filtersEl); if (!a) return;
    ink.style.width = a.offsetWidth + 'px';
    ink.style.transform = 'translate(' + a.offsetLeft + 'px,' + a.offsetTop + 'px)';
  }

  /* ---- card component ---- */
  function fill(root, p, extra) {
    var map = {
      number: p.number, category: p.category, name: p.name, shortName: p.shortName, eyebrow: p.eyebrow,
      summary: p.summary, suitableFor: p.suitableFor, durationWeeks: p.durationWeeks, format: p.format,
      reviewFrequency: p.reviewFrequency, reviewFrequencyLc: (p.reviewFrequency || '').toLowerCase(),
      price: inr(p.discountPrice || p.price), priceWas: p.discountPrice ? inr(p.price) : '',
      featuredLabel: p.featuredLabel || '', healthNote: p.healthNote || '', file: fileOf(p)
    };
    if (extra) for (var k in extra) map[k] = extra[k];
    $$('[data-f]', root).forEach(function (el) { var v = map[el.dataset.f]; el.textContent = v == null ? '' : v; });
  }

  function makeCard(p) {
    var node = tpl.content.firstElementChild.cloneNode(true);
    node.dataset.slug = p.slug;
    node.dataset.accent = ACCENT[p.category] || 'ultra';
    node.style.setProperty('--focus', p.imageFocus || '50% 50%');
    if (p.featured) node.classList.add('is-featured');
    fill(node, p);
    if (p.featured) $('.ed-card__spine-txt span', node).textContent = 'Signature';

    var img = $('.ed-card__img', node);
    img.addEventListener('error', function () { node.classList.add('is-missing'); });
    img.alt = p.imageAlt || '';
    if (PROGRAMS.indexOf(p) < BATCH) img.loading = 'eager';
    img.src = p.image;

    var ul = $('.ed-card__focus', node);
    (p.focusAreas || []).slice(0, 3).forEach(function (f) { var li = document.createElement('li'); li.textContent = f; ul.appendChild(li); });

    var btn = $('.ed-card__explore', node);
    btn.setAttribute('aria-label', 'Explore ' + p.name + ', ' + p.durationWeeks + ' weeks, ' + inr(p.discountPrice || p.price));
    btn.addEventListener('click', function () { openDrawer(p.slug, node); });
    return node;
  }

  function reveal(cards) {
    if (!cards.length) return;
    if (reduced) { cards.forEach(function (c) { c.classList.remove('is-pre'); }); return; }
    void grid.offsetWidth;
    requestAnimationFrame(function () {
      cards.forEach(function (c, i) {
        c.style.setProperty('--d', (i * 0.075).toFixed(3) + 's');
        c.classList.add('is-in'); c.classList.remove('is-pre');
      });
      setTimeout(function () { cards.forEach(function (c) { c.classList.remove('is-in'); c.style.removeProperty('--d'); }); }, 1500 + cards.length * 75);
    });
  }

  function updateMeta() {
    var total = state.list.length, shown = Math.min(state.shown, total);
    statusEl.textContent = 'Showing ' + pad(shown) + ' of ' + pad(total) + (state.cat === 'All' ? ' programs' : ' \u00B7 ' + state.cat);
    moreCount.innerHTML = '<b>' + pad(shown) + '</b> / ' + pad(total) + ' programs';
    moreWrap.hidden = total <= BATCH;
    var all = shown >= total;
    moreBtn.classList.toggle('is-less', all);
    moreLabel.textContent = all ? 'Show less' : 'Show more programs';
  }

  function render(animate) {
    grid.innerHTML = '';
    var cards = state.list.slice(0, state.shown).map(function (p) {
      var c = makeCard(p); if (animate) c.classList.add('is-pre'); grid.appendChild(c); return c;
    });
    updateMeta();
    return cards;
  }

  function setFilter(cat) {
    if (cat === state.cat || state.busy) return;
    state.busy = true; state.cat = cat;
    $$('.ed__filter', filtersEl).forEach(function (b) {
      var on = b.dataset.cat === cat; b.classList.toggle('is-active', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    moveInk();
    var old = $$('.ed-card', grid);
    old.forEach(function (c, i) { c.style.transitionDelay = (i * 0.02) + 's'; c.classList.add('is-out'); });
    setTimeout(function () {
      state.list = listFor(cat); state.shown = BATCH;
      reveal(render(true)); state.busy = false;
    }, reduced ? 0 : 240);
  }

  moreBtn.addEventListener('click', function () {
    if (state.busy) return;
    var total = state.list.length;
    if (state.shown >= total) {                      // show less
      state.busy = true;
      var extra = $$('.ed-card', grid).slice(BATCH);
      extra.forEach(function (c) { c.classList.add('is-out'); });
      setTimeout(function () {
        extra.forEach(function (c) { c.remove(); });
        state.shown = BATCH; updateMeta(); state.busy = false;
        var top = section.getBoundingClientRect().top + window.scrollY + grid.offsetTop - 120;
        if (window.scrollY > top) window.scrollTo({ top: top, behavior: reduced ? 'auto' : 'smooth' });
      }, reduced ? 0 : 230);
      return;
    }
    var next = state.list.slice(state.shown, state.shown + BATCH);
    state.shown += next.length;
    var cards = next.map(function (p) { var c = makeCard(p); c.classList.add('is-pre'); grid.appendChild(c); return c; });
    updateMeta(); reveal(cards);
    var first = cards[0];
    if (first) setTimeout(function () {
      var r = first.getBoundingClientRect();
      if (r.top > window.innerHeight - 120) window.scrollBy({ top: r.top - window.innerHeight * 0.3, behavior: reduced ? 'auto' : 'smooth' });
    }, 60);
  });

  /* initial render: reveal when the grid enters view */
  var firstCards = render(true);
  moveInk();
  window.addEventListener('resize', moveInk);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(moveInk);
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      if (es.some(function (e) { return e.isIntersecting; })) { reveal(firstCards); io.disconnect(); }
    }, { threshold: 0.12 });
    io.observe(grid);
  } else reveal(firstCards);

  /* ------------------------------------------------------------ drawer */
  var dr = $('#ed-drawer');
  var sheet = $('.edd__sheet', dr);
  var body = $('.edd__body', dr);
  var story = $('.edd__story', dr);
  var photo = $('.edd__photo', dr);
  var dImg = $('.edd__img', dr);
  var secs = $$('.edd__sec', dr);
  var railLinks = $$('.edd__rail a', dr);
  var railFill = $('.edd__rail-line i', dr);
  var capSec = $('.edd__photo-sec', dr);
  var dock = $('.edd__dock', dr);
  var cta = $('.edd__cta', dr);
  var toast = $('.edd__toast', dr);
  var jnyNote = $('.edd__jny-note', dr);
  var mqMobile = window.matchMedia('(max-width: 899px)');
  var LABELS = ['Overview', 'Focus', 'Included', 'Journey', 'Investment'];

  var cur = null, lastFocus = null, activeSec = 1, toastT = 0, ctaT = 0, isOpen = false;

  $$('.edd__r', dr).forEach(function (el, i) { el.style.setProperty('--i', i); });

  function scroller() { return mqMobile.matches ? body : story; }
  function navList() { return state.list.length ? state.list : PROGRAMS; }

  function setStep(i, focus) {
    var steps = $$('.edd__jny button', dr);
    steps.forEach(function (b, k) { b.setAttribute('aria-pressed', k === i ? 'true' : 'false'); });
    var name = cur.journey[i];
    $('.edd__jny-note .edd__jny-n', dr).textContent = pad(i + 1);
    $('.edd__jny-t', dr).innerHTML = '<b></b>';
    $('.edd__jny-t b', dr).textContent = name;
    $('.edd__jny-t', dr).appendChild(document.createTextNode(NOTES[name] || ''));
    if (focus !== false && !reduced) { jnyNote.classList.remove('is-tick'); void jnyNote.offsetWidth; jnyNote.classList.add('is-tick'); }
  }

  function fillDrawer(p) {
    cur = p;
    var list = navList(), idx = list.indexOf(p); if (idx < 0) { list = PROGRAMS; idx = PROGRAMS.indexOf(p); }
    dr.dataset.accent = ACCENT[p.category] || 'ultra';
    dr.dataset.featured = p.featured ? 'true' : 'false';
    fill(dr, p, { total: pad(PROGRAMS.length) });

    dr.classList.remove('is-missing');
    dImg.style.setProperty('--focus', p.imageFocus || '50% 50%');
    dImg.onerror = function () { dr.classList.add('is-missing'); };
    dImg.alt = p.imageAlt || '';
    dImg.src = p.image;

    var disc = $('.edd__disc', dr); disc.innerHTML = '';
    p.disciplines.forEach(function (d, r) {
      var li = document.createElement('li');
      var dots = '';
      for (var k = 0; k < 3; k++) dots += '<i class="' + (k < d.weight ? 'on' : '') + '" style="--dd:' + (r * 0.12 + k * 0.06).toFixed(2) + 's"></i>';
      li.innerHTML = '<span class="edd__disc-name"></span><span class="edd__dots" role="img" aria-label="' + LEVEL[d.weight] + ' focus">' + dots + '</span><span class="edd__disc-lvl">' + LEVEL[d.weight] + '</span>';
      li.firstChild.textContent = d.name;
      disc.appendChild(li);
    });

    var areas = $('.edd__areas', dr); areas.innerHTML = '';
    p.focusAreas.forEach(function (a) { var li = document.createElement('li'), s = document.createElement('span'); s.textContent = a; li.appendChild(s); areas.appendChild(li); });

    var inc = $('.edd__inc', dr); inc.innerHTML = '';
    p.included.forEach(function (t) { var li = document.createElement('li'); li.textContent = t; inc.appendChild(li); });

    var jny = $('.edd__jny', dr); jny.innerHTML = '';
    p.journey.forEach(function (s, i) {
      var li = document.createElement('li');
      li.innerHTML = '<button type="button" aria-pressed="false" style="--dd:' + (i * 0.14).toFixed(2) + 's"><span class="dot" aria-hidden="true"></span><span class="edd__jny-n">' + pad(i + 1) + '</span><span class="edd__jny-l"><span></span>' + ARROW + '</span></button>';
      $('.edd__jny-l span', li).textContent = s;
      var b = $('button', li);
      b.addEventListener('click', function () { setStep(i); });
      b.addEventListener('mouseenter', function () { if (window.matchMedia('(hover: hover)').matches) setStep(i); });
      jny.appendChild(li);
    });
    setStep(0, false);

    var nxt = list[(idx + 1) % list.length];
    $('.edd__next-no', dr).textContent = nxt.number;
    $('.edd__next-name', dr).textContent = nxt.name;
    var nImgWrap = $('.edd__next-img', dr), nImg = $('img', nImgWrap);
    nImgWrap.classList.remove('is-missing');
    nImg.onerror = function () { nImgWrap.classList.add('is-missing'); };
    nImg.src = nxt.image; nImg.style.objectPosition = nxt.imageFocus || '50% 50%';

    cta.classList.remove('is-done'); clearTimeout(ctaT);
    secs.forEach(function (s) { s.classList.remove('is-seen'); });
    body.scrollTop = 0; story.scrollTop = 0;
    activeSec = 0; spy();

    $$('.ed-card.is-selected', grid).forEach(function (c) { c.classList.remove('is-selected'); });
    var card = $('.ed-card[data-slug="' + p.slug + '"]', grid);
    if (card) card.classList.add('is-selected');
    try { history.replaceState(null, '', '#program=' + p.slug); } catch (e) {}
  }

  function replayPhoto() {
    if (reduced) return;
    photo.style.transition = 'none'; photo.style.clipPath = 'inset(100% 0 0 0 round 22px)';
    dImg.style.transition = 'none'; dImg.style.transform = 'scale(1.08)';
    void photo.offsetWidth;
    photo.style.transition = ''; photo.style.clipPath = '';
    dImg.style.transition = ''; dImg.style.transform = '';
  }

  function openDrawer(slug, cardEl) {
    var p = bySlug(slug); if (!p) return;
    if (isOpen) { swapTo(p); return; }
    lastFocus = cardEl ? $('.ed-card__explore', cardEl) : document.activeElement;
    if (cardEl && !reduced) { cardEl.classList.add('is-selected', 'is-launch'); setTimeout(function () { cardEl.classList.remove('is-launch'); }, 220); }
    setTimeout(function () {
      fillDrawer(p);
      dr.hidden = false; dr.classList.remove('is-closing');
      void dr.offsetWidth;
      dr.classList.add('is-open'); isOpen = true;
      activeSec = 0; spy();
      document.body.classList.add('is-locked');
      sheet.focus({ preventScroll: true });
    }, cardEl && !reduced ? 170 : 0);
  }

  function swapTo(p) {
    if (!p || p === cur) return;
    dr.classList.add('is-swapping');
    setTimeout(function () {
      fillDrawer(p); replayPhoto(); activeSec = 0; spy();
      requestAnimationFrame(function () { dr.classList.remove('is-swapping'); });
    }, reduced ? 0 : 200);
  }

  function step(dir) {
    var list = navList(), idx = list.indexOf(cur);
    if (idx < 0) { list = PROGRAMS; idx = PROGRAMS.indexOf(cur); }
    swapTo(list[(idx + dir + list.length) % list.length]);
  }

  function closeDrawer() {
    if (!isOpen) return;
    isOpen = false;
    dr.classList.add('is-closing'); dr.classList.remove('is-open');
    sheet.style.transform = '';
    setTimeout(function () {
      dr.hidden = true; dr.classList.remove('is-closing');
      document.body.classList.remove('is-locked');
      var card = cur && $('.ed-card[data-slug="' + cur.slug + '"]', grid);
      $$('.ed-card.is-selected', grid).forEach(function (c) { c.classList.remove('is-selected'); });
      var target = card ? $('.ed-card__explore', card) : lastFocus;
      if (target && target.focus) target.focus({ preventScroll: true });
    }, reduced ? 0 : 400);
    try { history.replaceState(null, '', location.pathname + location.search); } catch (e) {}
  }

  /* ---- scroll-spy, rail progress, dock ---- */
  function spy() {
    var sc = scroller(), r = sc.getBoundingClientRect();
    var line = r.top + sc.clientHeight * 0.38, act = 1;
    secs.forEach(function (s, i) {
      var t = s.getBoundingClientRect().top;
      if (t <= line) act = i + 1;
      if (t < r.top + sc.clientHeight * 0.8) s.classList.add('is-seen');
    });
    var max = sc.scrollHeight - sc.clientHeight;
    if (max > 0 && sc.scrollTop >= max - 4) act = secs.length;
    railLinks.forEach(function (a, i) { a.classList.toggle('is-active', i + 1 === act); a.classList.toggle('is-past', i + 1 < act); });
    railFill.style.setProperty('--p', ((act - 1) / (secs.length - 1)).toFixed(3));
    if (act !== activeSec) {
      activeSec = act;
      $('.edd__sec-n', capSec).textContent = pad(act);
      $('.edd__sec-l', capSec).textContent = LABELS[act - 1];
      if (!reduced) { capSec.classList.remove('is-tick'); void capSec.offsetWidth; capSec.classList.add('is-tick'); }
    }
    var inv = secs[secs.length - 1].getBoundingClientRect();
    dock.classList.toggle('is-hidden', inv.top + 140 < r.bottom);
  }
  story.addEventListener('scroll', spy, { passive: true });
  body.addEventListener('scroll', spy, { passive: true });
  window.addEventListener('resize', function () { if (isOpen) spy(); });

  railLinks.forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault();
      var s = $(a.getAttribute('href'), dr), sc = scroller();
      var top = sc.scrollTop + s.getBoundingClientRect().top - sc.getBoundingClientRect().top - 28;
      sc.scrollTo({ top: top, behavior: reduced ? 'auto' : 'smooth' });
    });
  });

  /* ---- controls ---- */
  $$('[data-close]', dr).forEach(function (b) { b.addEventListener('click', closeDrawer); });
  $$('[data-prev]', dr).forEach(function (b) { b.addEventListener('click', function () { step(-1); }); });
  $$('[data-next]', dr).forEach(function (b) { b.addEventListener('click', function () { step(1); }); });

  function showToast(html) {
    toast.innerHTML = html; toast.classList.add('is-on');
    clearTimeout(toastT); toastT = setTimeout(function () { toast.classList.remove('is-on'); }, 3000);
  }
  $$('[data-start]', dr).forEach(function (b) {
    b.addEventListener('click', function () {
      cta.classList.remove('is-done'); void cta.offsetWidth; cta.classList.add('is-done');
      clearTimeout(ctaT); ctaT = setTimeout(function () { cta.classList.remove('is-done'); }, 2800);
      var t = document.createElement('span'); t.textContent = cur.shortName;
      showToast('<i>\u2713</i>' + t.innerHTML + ' selected \u2014 checkout would start here (prototype)');
    });
  });
  $('[data-talk]', dr).addEventListener('click', function () {
    var t = document.createElement('span'); t.textContent = cur.shortName;
    showToast('<i>\u2192</i>Enquiry about ' + t.innerHTML + ' \u2014 the contact form would open here (prototype)');
  });

  /* ---- keyboard ---- */
  document.addEventListener('keydown', function (e) {
    if (!isOpen) return;
    if (e.key === 'Escape') { e.preventDefault(); closeDrawer(); return; }
    if ((e.key === 'ArrowLeft' || e.key === 'ArrowRight') && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) {
      e.preventDefault(); step(e.key === 'ArrowRight' ? 1 : -1); return;
    }
    if (e.key === 'Tab') {
      var f = $$('button, a[href], [tabindex]:not([tabindex="-1"])', sheet).filter(function (el) { return el.offsetParent !== null && !el.closest('.is-hidden'); });
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && (document.activeElement === first || document.activeElement === sheet)) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      else if (!sheet.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
    }
  });

  /* ---- drag down to close (grab handle / bar) ---- */
  var drag = null;
  $('.edd__bar', dr).addEventListener('pointerdown', function (e) {
    if (e.target.closest('button') || (e.pointerType === 'mouse' && e.button !== 0)) return;
    drag = { y: e.clientY, t: Date.now(), dy: 0 };
    dr.classList.add('is-dragging');
    e.currentTarget.setPointerCapture(e.pointerId);
  });
  $('.edd__bar', dr).addEventListener('pointermove', function (e) {
    if (!drag) return;
    drag.dy = Math.max(0, e.clientY - drag.y);
    sheet.style.transform = 'translate(-50%,' + drag.dy + 'px)';
  });
  function endDrag() {
    if (!drag) return;
    var v = drag.dy / Math.max(1, Date.now() - drag.t), dy = drag.dy;
    drag = null; dr.classList.remove('is-dragging');
    if (dy > 140 || (dy > 40 && v > 0.6)) closeDrawer(); else sheet.style.transform = '';
  }
  $('.edd__bar', dr).addEventListener('pointerup', endDrag);
  $('.edd__bar', dr).addEventListener('pointercancel', endDrag);

  /* ---- deep link: #program=slug ---- */
  function fromHash() {
    var m = /program=([\w-]+)/.exec(location.hash);
    if (m && bySlug(m[1])) openDrawer(m[1], $('.ed-card[data-slug="' + m[1] + '"]', grid));
  }
  window.addEventListener('hashchange', fromHash);
  fromHash();
})();

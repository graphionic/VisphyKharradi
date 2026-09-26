/* ==========================================================================
   FTPRENEUR — Program Experience · Concept 04 · PREMIUM VISUAL PROGRAM GRID
   Production JS Engine — Scoped execution with real DB data.
   ========================================================================== */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var PROGRAMS = window.ED_PROGRAMS || [];
    var PAGE = 6;
    var ACCENT = { Weight: 'ultra', Lifestyle: 'ultra', Metabolic: 'mint', Nutrition: 'mint', Strength: 'marigold', Complete: 'ink' };

    var root = document.querySelector('.pv-sec-c4');
    if (!root || !PROGRAMS || !PROGRAMS.length) return;

    var grid = root.querySelector('.pv__grid');
    var tpl = document.getElementById('pv-card-tpl-c4');
    var filtersEl = root.querySelector('.pv__filters');
    var line = root.querySelector('.pv__filters-line');
    var status = root.querySelector('.pv__status');
    var more = root.querySelector('.pv__more');
    var moreBtn = root.querySelector('.pv__more-btn');

    if (!grid || !tpl) return;

    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    var inr = function (n) { return '₹' + Number(n).toLocaleString('en-IN'); };
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    var state = { filter: 'All', shown: PAGE };
    var cards = {};

    /* ---------- Category Filter Buttons ---------- */
    var rawCats = ['All'];
    PROGRAMS.forEach(function (p) {
      if (p.category && rawCats.indexOf(p.category) === -1) {
        rawCats.push(p.category);
      }
    });
    var FILTERS = rawCats;

    if (filtersEl) {
      FILTERS.forEach(function (f) {
        var b = document.createElement('button');
        var c = f === 'All' ? PROGRAMS.length : PROGRAMS.filter(function (p) { return p.category === f; }).length;
        b.className = 'pv__filter' + (f === state.filter ? ' is-active' : '');
        b.type = 'button';
        b.dataset.f = f;
        b.innerHTML = f + '<sup>' + pad(c) + '</sup>';
        b.setAttribute('aria-pressed', f === state.filter ? 'true' : 'false');
        b.addEventListener('click', function () { setFilter(f); });
        if (line) {
          filtersEl.insertBefore(b, line);
        } else {
          filtersEl.appendChild(b);
        }
      });
    }

    function moveLine() {
      if (!filtersEl || !line) return;
      var a = filtersEl.querySelector('.pv__filter.is-active');
      if (!a) return;
      line.style.width = a.offsetWidth + 'px';
      line.style.transform = 'translateX(' + a.offsetLeft + 'px)';
    }

    function setFilter(f) {
      if (f === state.filter) return;
      state.filter = f;
      state.shown = PAGE;
      if (filtersEl) {
        filtersEl.querySelectorAll('.pv__filter').forEach(function (b) {
          var on = b.dataset.f === f;
          b.classList.toggle('is-active', on);
          b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
      }
      moveLine();
      render(0);
    }

    /* ---------- Card Builder ---------- */
    function build(p) {
      var el = tpl.content.firstElementChild.cloneNode(true);
      el.dataset.slug = p.slug;
      el.dataset.accent = ACCENT[p.category] || 'ultra';
      if (p.featured) el.classList.add('is-featured');

      var map = {
        number: pad(p.number),
        category: p.category || 'Personalised',
        name: p.name,
        line: p.short_description || p.purpose || '',
        durationWeeks: p.durationWeeks || 12,
        price: inr(p.discountPrice || p.price),
        priceWas: p.discountPrice ? inr(p.price) : '',
        featuredLabel: p.featuredLabel || 'Signature Program'
      };

      Array.prototype.slice.call(el.querySelectorAll('[data-f]')).forEach(function (n) {
        var key = n.dataset.f;
        n.textContent = map[key] == null ? '' : map[key];
      });

      var img = el.querySelector('.pv-card__img');
      if (img) {
        img.alt = p.name || '';
        img.src = p.image || '';
      }

      var cta = el.querySelector('.pv-card__cta');
      if (cta) {
        cta.setAttribute('aria-label', 'Explore ' + p.name + ', ' + map.durationWeeks + ' weeks, ' + map.price);
        cta.addEventListener('click', function (e) {
          e.preventDefault();
          openDrawer(p.slug, el);
        });
      }

      var nameBtn = el.querySelector('.pv-card__name');
      if (nameBtn) {
        nameBtn.addEventListener('click', function (e) {
          e.preventDefault();
          openDrawer(p.slug, el);
        });
      }

      return el;
    }

    function list() {
      return PROGRAMS.filter(function (p) { return state.filter === 'All' || p.category === state.filter; });
    }

    function cols() {
      var comp = getComputedStyle(grid).gridTemplateColumns;
      return comp ? comp.split(' ').length : 3;
    }

    function rhythm() {
      var c = cols();
      Array.prototype.slice.call(grid.querySelectorAll('.pv-card')).forEach(function (el, i) {
        var r = Math.floor(i / c), k = i % c;
        el.dataset.side = c === 1 ? (i % 2 ? 'r' : 'l') : ((r + k) % 2 ? 'r' : 'l');
      });
    }

    function render(animFrom) {
      var items = list(), vis = items.slice(0, state.shown);
      grid.innerHTML = '';
      vis.forEach(function (p, i) {
        var el = cards[p.slug] || (cards[p.slug] = build(p));
        grid.appendChild(el);
      });
      rhythm();

      var n = vis.length, t = items.length;
      if (status) status.innerHTML = 'Showing <b>' + pad(n) + '</b> of ' + pad(t);
      if (more) more.hidden = t <= PAGE;

      var countEl = root.querySelector('.pv__more-count');
      if (countEl) countEl.innerHTML = '<b>' + pad(n) + '</b> / ' + pad(t);

      var trackEl = root.querySelector('.pv__more-track i');
      if (trackEl) trackEl.style.setProperty('--p', t > 0 ? (n / t).toFixed(3) : 1);

      var done = n >= t;
      if (moreBtn) {
        moreBtn.classList.toggle('is-less', done);
        var lbl = root.querySelector('.pv__more-label');
        if (lbl) lbl.textContent = done ? 'Show fewer programs' : 'Show more programs';
        moreBtn.setAttribute('aria-label', done ? 'Show fewer programs' : 'Show ' + Math.min(PAGE, t - n) + ' more programs');
      }
    }

    if (moreBtn) {
      moreBtn.addEventListener('click', function () {
        var t = list().length;
        if (state.shown >= t) {
          state.shown = PAGE;
          render();
          root.scrollIntoView({ behavior: reduced.matches ? 'auto' : 'smooth', block: 'start' });
          return;
        }
        var from = state.shown;
        state.shown = Math.min(t, state.shown + PAGE);
        render(from);
      });
    }

    /* ---------- Bottom Drawer ---------- */
    var dr = document.getElementById('pv-drawer');
    if (!dr) return;

    var sheet = dr.querySelector('.pvd__sheet');
    var main = dr.querySelector('.pvd__main');
    var body = dr.querySelector('.pvd__body');
    var cur = null, lastFocus = null;

    function scroller() {
      return (body && getComputedStyle(body).display === 'block') ? body : main;
    }

    function fill(p) {
      cur = p;
      dr.dataset.accent = ACCENT[p.category] || 'ultra';
      dr.dataset.featured = p.featured ? 'true' : 'false';

      var map = {
        number: pad(p.number),
        category: p.category || 'Personalised',
        name: p.name,
        durationWeeks: p.durationWeeks || 12,
        summary: p.short_description || p.purpose || '',
        healthNote: p.full_description || p.overview || 'Every program is structured 1:1 around your assessment, routine, and progress.',
        suitableFor: p.suitableFor || 'Anyone seeking scientific guidance for nutrition, strength, and lifestyle habits.',
        format: p.format || '1:1 Personalised',
        featuredLabel: p.featuredLabel || 'Signature Program',
        reviewEvery: '2',
        reviewUnit: 'weekly reviews',
        reviewLc: 'every 2 weeks',
        price: inr(p.discountPrice || p.price),
        priceWas: p.discountPrice ? inr(p.price) : ''
      };

      Array.prototype.slice.call(dr.querySelectorAll('[data-f]')).forEach(function (n) {
        var key = n.dataset.f;
        n.textContent = map[key] == null ? '' : map[key];
      });

      var img = dr.querySelector('.pvd__img');
      if (img) {
        img.alt = p.name || '';
        img.src = p.image || '';
      }

      // Emphasis Mix Bar
      var mix = dr.querySelector('.pvd__mix');
      var keyEl = dr.querySelector('.pvd__mix-key');
      if (mix && keyEl) {
        mix.innerHTML = '<span class="w3" style="flex-grow:3">Nutrition</span><span class="w2" style="flex-grow:3">Strength</span><span class="w1" style="flex-grow:2">Lifestyle</span>';
        keyEl.innerHTML = '<li><b>Nutrition</b><small>Lead</small></li><li><b>Strength</b><small>Lead</small></li><li><b>Lifestyle</b><small>Core</small></li>';
      }

      // Focus Areas
      var chipsEl = dr.querySelector('.pvd__chips');
      if (chipsEl) {
        chipsEl.innerHTML = '';
        var areas = p.focusAreas || ['Personalised Nutrition', 'Strength & Movement', 'Lifestyle Management', 'Biweekly Reviews'];
        areas.forEach(function (f) {
          var li = document.createElement('li');
          li.textContent = f;
          chipsEl.appendChild(li);
        });
      }

      // Includes List
      var incEl = dr.querySelector('.pvd__inc');
      if (incEl) {
        incEl.innerHTML = '';
        var incItems = (p.features && p.features.length) ? p.features : [
          '1:1 Health Assessment & Biomarker Evaluation',
          'Tailored Nutrition & Movement Protocols',
          'Lifestyle & Stress Recovery Strategy',
          'Biweekly Progress Tracking & Plan Adjustments'
        ];
        incItems.forEach(function (fText) {
          var li = document.createElement('li');
          li.textContent = typeof fText === 'string' ? fText : (fText.title || fText.feature_text || 'Feature');
          incEl.appendChild(li);
        });
      }

      // Journey Stages
      var stagesEl = dr.querySelector('.pvd__stages');
      if (stagesEl) {
        var w = p.durationWeeks || 12;
        var mid = Math.max(3, Math.round(w * .5));
        var stages = [
          { n: '01', title: 'Start', week: 'Week 0 · welcome' },
          { n: '02', title: 'Assess', week: 'Week 1 · 1:1 review' },
          { n: '03', title: 'Personalise', week: 'Week 1–2 · plan build' },
          { n: '04', title: 'Build', week: 'Weeks 2–' + mid + ' · habits' },
          { n: '05', title: 'Review', week: 'Every 2 weeks · refine' },
          { n: '06', title: 'Progress', week: 'Week ' + w + ' · next phase' }
        ];
        stagesEl.innerHTML = stages.map(function (st) {
          return '<li><div class="pvd__stage"><span class="pvd__stage-n">' + st.n + '</span><span class="pvd__stage-t">' + st.title + '</span><span class="pvd__stage-w">' + st.week + '</span></div></li>';
        }).join('');
      }

      // Next Program Teaser
      var nx = neighbour(1);
      var nextNumEl = dr.querySelector('.pvd__nextp-n');
      var nextNameEl = dr.querySelector('.pvd__nextp-name');
      var nextImgEl = dr.querySelector('.pvd__nextp-img img');
      if (nextNumEl) nextNumEl.textContent = pad(nx.number);
      if (nextNameEl) nextNameEl.textContent = nx.name;
      if (nextImgEl) nextImgEl.src = nx.image || '';

      // Form CTA Link
      var ctaBtn = dr.querySelector('[data-start]');
      var ctaFormUrl = p.google_form_url || null;
      if (ctaBtn) {
        ctaBtn.classList.remove('is-done');
        if (ctaFormUrl) {
          ctaBtn.onclick = function () {
            window.open(ctaFormUrl, '_blank', 'noopener,noreferrer');
          };
        } else {
          ctaBtn.onclick = function () {
            ctaBtn.classList.add('is-done');
            toast(p.name + ' selected — redirecting to consultation...');
          };
        }
      }
    }

    function order() {
      var o = list().slice(0, Math.max(state.shown, 1));
      return o.length ? o : PROGRAMS;
    }

    function neighbour(dir) {
      var o = order();
      var i = o.indexOf(cur);
      if (i < 0) { o = PROGRAMS; i = o.indexOf(cur); }
      return o[(i + dir + o.length) % o.length];
    }

    function markCard() {
      Array.prototype.slice.call(grid.querySelectorAll('.pv-card.is-selected')).forEach(function (c) {
        c.classList.remove('is-selected');
      });
      if (cur && cards[cur.slug]) cards[cur.slug].classList.add('is-selected');
    }

    function openDrawer(slug, from) {
      var p = PROGRAMS.filter(function (x) { return x.slug === slug; })[0];
      if (!p) return;
      lastFocus = from ? from.querySelector('.pv-card__cta') || from : document.activeElement;
      fill(p);
      dr.hidden = false;
      dr.classList.remove('is-closing');
      document.body.classList.add('is-locked-c4');
      if (main) main.scrollTop = 0;
      if (body) body.scrollTop = 0;
      requestAnimationFrame(function () { dr.classList.add('is-open'); });
      if (sheet) sheet.focus({ preventScroll: true });
      markCard();
      history.replaceState(null, '', '#program=' + p.slug);
    }

    function closeDrawer() {
      if (dr.hidden || dr.classList.contains('is-closing')) return;
      dr.classList.add('is-closing');
      dr.classList.remove('is-open');
      var done = function () {
        dr.hidden = true;
        dr.classList.remove('is-closing');
        document.body.classList.remove('is-locked-c4');
        Array.prototype.slice.call(grid.querySelectorAll('.pv-card.is-selected')).forEach(function (c) {
          c.classList.remove('is-selected');
        });
        history.replaceState(null, '', location.pathname + location.search);
        if (lastFocus && document.contains(lastFocus)) lastFocus.focus({ preventScroll: true });
      };
      setTimeout(done, reduced.matches ? 0 : 380);
    }

    function step(dir) {
      var nx = neighbour(dir);
      if (!nx || nx === cur) return;
      fill(nx);
      if (main) main.scrollTop = 0;
      if (body) body.scrollTop = 0;
      markCard();
      history.replaceState(null, '', '#program=' + nx.slug);
    }

    Array.prototype.slice.call(dr.querySelectorAll('[data-close]')).forEach(function (b) {
      b.addEventListener('click', closeDrawer);
    });
    Array.prototype.slice.call(dr.querySelectorAll('[data-prev]')).forEach(function (b) {
      b.addEventListener('click', function () { step(-1); });
    });
    Array.prototype.slice.call(dr.querySelectorAll('[data-next]')).forEach(function (b) {
      b.addEventListener('click', function () { step(1); });
    });

    var toastT;
    function toast(msg) {
      var t = dr.querySelector('.pvd__toast');
      if (!t) return;
      t.textContent = msg;
      t.classList.add('is-on');
      clearTimeout(toastT);
      toastT = setTimeout(function () { t.classList.remove('is-on'); }, 2600);
    }

    // Keyboard controls
    document.addEventListener('keydown', function (e) {
      if (dr.hidden) return;
      if (e.key === 'Escape') { e.preventDefault(); closeDrawer(); return; }
      if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
        e.preventDefault();
        step(e.key === 'ArrowRight' ? 1 : -1);
        return;
      }
    });

    /* Boot */
    render();
    moveLine();
    window.addEventListener('resize', function () {
      rhythm();
      moveLine();
    });

    /* Deep Link #program=slug */
    var m = /program=([\w-]+)/.exec(location.hash);
    if (m) {
      var c = cards[m[1]];
      openDrawer(m[1], c || null);
    }
  });
})();

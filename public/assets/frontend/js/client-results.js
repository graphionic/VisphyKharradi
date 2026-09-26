/**
 * FTPRENEUR — Client Results / Real Progress Carousel & Approved Full Story Drawer Logic
 * Phase 08A & Phase 09A
 * Vanilla JavaScript implementation matching Arena prototype (journal.js + drawer.js)
 */
(function () {
  'use strict';

  var TONES = ['ultra', 'mint', 'mari'];
  var ARROW = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg>';
  var ARROW_L = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H6M11 6l-6 6 6 6"/></svg>';

  function pad(n) {
    return (n < 10 ? '0' : '') + n;
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
    });
  }

  function toTitleCase(str) {
    return String(str || '').toLowerCase().replace(/(?:^|\s|-)\S/g, function (m) {
      return m.toUpperCase();
    });
  }

  function getBaseUrl() {
    var base = document.querySelector('meta[name="base-url"]');
    if (base && base.content) return base.content.replace(/\/+$/, '');
    return window.location.origin;
  }

  /* ==========================================================================
     CAROUSEL LOGIC (.crj)
     ========================================================================== */
  function initCarousel() {
    var root = document.querySelector('.crj__carousel');
    if (!root) return;

    var track = root.querySelector('.crj__track');
    var viewport = root.querySelector('.crj__viewport');
    if (!track || !viewport) return;

    var storyArticles = Array.prototype.slice.call(track.querySelectorAll('.crj-s'));
    var totalStories = storyArticles.length;
    if (totalStories === 0) return;

    var mq3 = window.matchMedia('(min-width: 1100px)');
    var mq2 = window.matchMedia('(min-width: 700px)');

    var per = 3;
    var page = 0;
    var pages = 1;
    var pageEls = [];

    var autoplayTimer = null;
    var AUTOPLAY_INTERVAL = 4500;
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function startAutoplay() {
      stopAutoplay();
      if (reducedMotion.matches || pages <= 1) return;
      autoplayTimer = setInterval(function () {
        var dr = document.getElementById('crd');
        if (document.hidden || (dr && (dr.classList.contains('is-open') || !dr.hidden))) return;
        var nextPage = (page + 1) % pages;
        gotoPage(nextPage);
      }, AUTOPLAY_INTERVAL);
    }

    function stopAutoplay() {
      if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
      }
    }

    function getPerView() {
      if (mq3.matches) return 3;
      if (mq2.matches) return 2;
      return 1;
    }

    function buildPages(keepStoryIdx) {
      per = getPerView();
      pages = Math.ceil(totalStories / per);

      var fragment = document.createDocumentFragment();
      pageEls = [];

      for (var p = 0; p < pages; p++) {
        var pageDiv = document.createElement('div');
        pageDiv.className = 'crj__page';
        pageDiv.setAttribute('data-per', per);
        pageDiv.setAttribute('role', 'group');
        pageDiv.setAttribute('aria-roledescription', 'slide');
        pageDiv.setAttribute('aria-label', 'Page ' + (p + 1) + ' of ' + pages);

        var slice = storyArticles.slice(p * per, p * per + per);
        slice.forEach(function (article) {
          pageDiv.appendChild(article);
        });

        fragment.appendChild(pageDiv);
        pageEls.push(pageDiv);
      }

      track.innerHTML = '';
      track.appendChild(fragment);

      page = Math.min(pages - 1, Math.floor((keepStoryIdx || 0) / per));
      root.classList.add('is-static');
      sync();

      requestAnimationFrame(function () {
        root.classList.remove('is-static');
      });

      startAutoplay();
    }

    function place(extraPx) {
      var offset = -page * 100;
      if (extraPx) {
        track.style.transform = 'translate3d(calc(' + offset + '% + ' + extraPx + 'px), 0, 0)';
      } else {
        track.style.transform = 'translate3d(' + offset + '%, 0, 0)';
      }
    }

    function sync() {
      place();

      var curEl = root.querySelector('.crj__cur');
      var totEl = root.querySelector('.crj__tot');
      var lineEl = root.querySelector('.crj__line i');
      var rangeEl = root.querySelector('.crj__range');
      var prevBtn = root.querySelector('[data-prev]');
      var nextBtn = root.querySelector('[data-next]');
      var nextSpan = root.querySelector('[data-next] span');

      if (curEl) curEl.textContent = pad(page + 1);
      if (totEl) totEl.textContent = pad(pages);
      if (lineEl) lineEl.style.setProperty('--p', ((page + 1) / pages).toFixed(3));

      if (rangeEl) {
        var startIdx = page * per + 1;
        var endIdx = Math.min(totalStories, startIdx + per - 1);
        if (per === 1) {
          rangeEl.textContent = 'Story ' + startIdx + ' of ' + totalStories;
        } else {
          rangeEl.textContent = 'Stories ' + startIdx + '–' + endIdx + ' of ' + totalStories;
        }
      }

      pageEls.forEach(function (el, k) {
        var isActive = (k === page);
        el.classList.toggle('is-active', isActive);
        el.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        if (isActive) {
          el.removeAttribute('inert');
        } else {
          el.setAttribute('inert', '');
        }
      });

      if (prevBtn) prevBtn.disabled = false;
      if (nextBtn) nextBtn.disabled = false;
      if (nextSpan) {
        nextSpan.textContent = (per === 1) ? 'Next story' : 'Next stories';
      }
    }

    function gotoPage(targetPage) {
      page = (targetPage + pages) % pages;
      sync();
    }

    var prevBtn = root.querySelector('[data-prev]');
    var nextBtn = root.querySelector('[data-next]');

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        stopAutoplay();
        gotoPage(page - 1);
        startAutoplay();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        stopAutoplay();
        gotoPage(page + 1);
        startAutoplay();
      });
    }

    root.addEventListener('mouseenter', stopAutoplay);
    root.addEventListener('mouseleave', startAutoplay);
    root.addEventListener('focusin', stopAutoplay);
    root.addEventListener('focusout', startAutoplay);

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stopAutoplay();
      else startAutoplay();
    });

    function onResize() {
      var currentFirstStoryIdx = page * per;
      buildPages(currentFirstStoryIdx);
    }

    if (typeof mq3.addEventListener === 'function') {
      mq3.addEventListener('change', onResize);
      mq2.addEventListener('change', onResize);
    } else {
      mq3.addListener(onResize);
      mq2.addListener(onResize);
    }

    viewport.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        stopAutoplay();
        gotoPage(page - 1);
        startAutoplay();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        stopAutoplay();
        gotoPage(page + 1);
        startAutoplay();
      }
    });

    // Horizontal Trackpad / Mouse Wheel Support
    var wheelDebounce = null;
    viewport.addEventListener('wheel', function (e) {
      if (Math.abs(e.deltaX) > Math.abs(e.deltaY) && Math.abs(e.deltaX) > 15) {
        if (e.cancelable) e.preventDefault();
        stopAutoplay();
        if (!wheelDebounce) {
          if (e.deltaX > 0) gotoPage(page + 1);
          else gotoPage(page - 1);
          wheelDebounce = setTimeout(function () {
            wheelDebounce = null;
          }, 350);
        }
        startAutoplay();
      }
    }, { passive: false });

    // Mouse & Touch Drag (Unified Mouse & Touch Handling + Axis Locking)
    var startX = 0;
    var startY = 0;
    var currentDx = 0;
    var isDragging = false;
    var isAxisLocked = false;
    var isHorizontal = false;
    var dragMoved = false;

    viewport.style.cursor = 'grab';

    function onPointerStart(clientX, clientY, isMouse) {
      stopAutoplay();
      startX = clientX;
      startY = clientY;
      currentDx = 0;
      isDragging = true;
      isAxisLocked = isMouse;
      isHorizontal = isMouse;
      dragMoved = false;
      if (isMouse) {
        root.classList.add('is-dragging');
        viewport.style.cursor = 'grabbing';
      }
    }

    function onPointerMove(clientX, clientY, isMouse, e) {
      if (!isDragging) return;
      var dx = clientX - startX;
      var dy = clientY - startY;

      if (!isAxisLocked) {
        if (Math.abs(dx) > 8 || Math.abs(dy) > 8) {
          isAxisLocked = true;
          isHorizontal = (Math.abs(dx) > Math.abs(dy));
        }
      }

      if (isHorizontal) {
        if (e && e.cancelable) e.preventDefault();
        if (Math.abs(dx) > 8) dragMoved = true;
        currentDx = dx;
        root.classList.add('is-dragging');
        place(currentDx);
      }
    }

    function onPointerEnd() {
      if (!isDragging) return;
      isDragging = false;
      root.classList.remove('is-dragging');
      viewport.style.cursor = 'grab';

      var threshold = viewport.clientWidth * 0.12;
      if (isHorizontal && Math.abs(currentDx) > threshold) {
        if (currentDx < 0) {
          gotoPage(page + 1);
        } else if (currentDx > 0) {
          gotoPage(page - 1);
        } else {
          sync();
        }
      } else {
        sync();
      }
      currentDx = 0;
      startAutoplay();
    }

    // Touch Events
    viewport.addEventListener('touchstart', function (e) {
      if (e.touches.length !== 1) return;
      onPointerStart(e.touches[0].clientX, e.touches[0].clientY, false);
    }, { passive: true });

    viewport.addEventListener('touchmove', function (e) {
      if (!isDragging || e.touches.length !== 1) return;
      onPointerMove(e.touches[0].clientX, e.touches[0].clientY, false, e);
    }, { passive: false });

    viewport.addEventListener('touchend', onPointerEnd, { passive: true });
    viewport.addEventListener('touchcancel', onPointerEnd, { passive: true });

    // Desktop Mouse Drag Events
    viewport.addEventListener('mousedown', function (e) {
      if (e.button !== 0) return;
      onPointerStart(e.clientX, e.clientY, true);
    });

    window.addEventListener('mousemove', function (e) {
      if (!isDragging) return;
      onPointerMove(e.clientX, e.clientY, true, e);
    });

    window.addEventListener('mouseup', function (e) {
      if (isDragging) onPointerEnd();
    });

    // Suppress click on links when user was dragging mouse
    viewport.addEventListener('click', function (e) {
      if (dragMoved) {
        e.preventDefault();
        e.stopPropagation();
        dragMoved = false;
      }
    }, true);

    buildPages(0);
  }

  /* ==========================================================================
     FULL STORY BOTTOM DRAWER LOGIC (.crd)
     Exact Arena drawer.js Port connected to CodeIgniter detail endpoint
     ========================================================================== */
  function initDrawer() {
    var dr = document.getElementById('crd');
    if (!dr) return;

    var sheet = dr.querySelector('.crd__sheet');
    var scroller = dr.querySelector('.crd__scroll');
    var content = dr.querySelector('#crd-content');
    var viewer = dr.querySelector('.crd__viewer');
    var vbody = dr.querySelector('#crd-vbody');
    var vname = dr.querySelector('#crd-vname');
    var vmeta = dr.querySelector('#crd-vmeta');
    var headMedia = dr.querySelector('.crd__head-media');
    var headNum = dr.querySelector('.crd__head-n');
    var headName = dr.querySelector('.crd__head-name');
    var headProg = dr.querySelector('.crd__head-prog');

    var currentId = null;
    var openerEl = null;
    var activeReportBtn = null;
    var savedY = 0;
    var closeTimer = null;
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function getCarouselStoryElements() {
      return Array.prototype.slice.call(document.querySelectorAll('.crj-s[data-id]'));
    }

    function getStoryDataFromDom(el) {
      if (!el) return null;
      var nameEl = el.querySelector('.crj-s__name');
      var progEl = el.querySelector('.crj-s__prog');
      var mediaEl = el.querySelector('.crj-s__media');
      var imgEl = mediaEl ? mediaEl.querySelector('img') : null;
      var iniEl = mediaEl ? mediaEl.querySelector('.crj-s__ini') : null;

      return {
        id: parseInt(el.getAttribute('data-id'), 10),
        tone: el.getAttribute('data-tone') || 'ultra',
        name: nameEl ? nameEl.textContent.trim() : 'Client Story',
        program: progEl ? progEl.childNodes[0].textContent.trim() : 'Personalised Program',
        coverImage: imgEl ? imgEl.getAttribute('src') : null,
        initials: iniEl ? iniEl.textContent.trim() : ''
      };
    }

    function mediaHtml(coverPath, name, initials, cls) {
      var ini = '<span class="crd-media__ini">' + esc(initials) + '</span>';
      if (!coverPath) {
        return '<span class="crd-media ' + cls + '" aria-hidden="true">' + ini + '</span>';
      }
      var fullUrl = (coverPath.indexOf('http') === 0) ? coverPath : (getBaseUrl() + '/' + coverPath.replace(/^\/+/, ''));
      return '<span class="crd-media ' + cls + ' has-img"><img src="' + esc(fullUrl) + '" alt="' + esc(name) + '" loading="lazy" />' + ini + '</span>';
    }

    function renderArenaDrawer(data, storyIdx, totalStories) {
      var tone = TONES[storyIdx % 3];
      dr.setAttribute('data-tone', tone);

      // Section composition array matching Arena sections(s, i)
      var secs = [];

      // 1. CLIENT STORY
      if (data.full_story) {
        var rawStory = data.full_story;
        var storyHtml = (rawStory.indexOf('<p>') !== -1) ? rawStory : '<p>' + esc(rawStory) + '</p>';
        secs.push({
          label: 'Client story',
          title: 'How it unfolded',
          cls: 'crd-sec--story',
          html: '<div class="crd-story__grid">' +
            '<div class="crd-story__text">' + storyHtml + '</div>' +
            '<aside class="crd-story__meta" aria-label="Journey details"><dl>' +
              '<div><dt>Program</dt><dd>' + esc(data.program_name_snapshot || 'Personalised Program') + '</dd></div>' +
              (data.journey_duration ? '<div><dt>Duration</dt><dd>' + esc(data.journey_duration) + '</dd></div>' : '') +
              '<div><dt>Format</dt><dd>1:1 personalised coaching</dd></div>' +
              (data.metrics && data.metrics.length ? '<div><dt>Recorded outcomes</dt><dd>' + data.metrics.length + ' measurements</dd></div>' : '') +
            '</dl></aside>' +
          '</div>'
        });
      }

      // 2. RECORDED OUTCOMES
      if (data.metrics && data.metrics.length) {
        secs.push({
          label: 'Recorded outcomes',
          title: 'What was recorded',
          note: data.journey_duration ? 'Recorded across the ' + esc(data.journey_duration) + ' journey' : null,
          html: '<ol class="crd-out">' + data.metrics.map(function (m) {
            var u = m.unit ? '<small>' + esc(m.unit) + '</small>' : '';
            return '<li>' +
              '<p class="crd-out__lbl">' + esc(m.metric_name) + '</p>' +
              '<div class="crd-out__row" aria-label="' + esc(m.metric_name + ': ' + m.before_value + ' to ' + m.after_value + (m.unit ? ' ' + m.unit : '')) + '">' +
                '<p class="crd-out__b"><span>' + esc(m.before_value) + u + '</span>' + (m.measurement_start_date ? '<time>' + esc(m.measurement_start_date) + '</time>' : '') + '</p>' +
                '<span class="crd-out__line" aria-hidden="true"><i></i></span>' +
                '<p class="crd-out__a"><span>' + esc(m.after_value) + u + '</span>' + (m.measurement_end_date ? '<time>' + esc(m.measurement_end_date) + '</time>' : '') + '</p>' +
              '</div>' +
            '</li>';
          }).join('') + '</ol>'
        });
      }

      // 3. BEFORE & AFTER
      if (data.before_after && data.before_after.length) {
        secs.push({
          label: 'Before & after',
          title: 'Before and after',
          html: '<div class="crd-ba">' +
            data.before_after.map(function (med) {
              var imgUrl = (med.file_path.indexOf('http') === 0) ? med.file_path : (getBaseUrl() + '/' + med.file_path.replace(/^\/+/, ''));
              return '<figure>' +
                '<div class="crd-ph crd-ph--ba"><img src="' + esc(imgUrl) + '" alt="' + esc(med.caption || 'Before/After image') + '" loading="lazy" /></div>' +
                '<figcaption><b>' + esc(med.label_type || 'Comparison') + '</b><span>' + esc(med.caption || '') + '</span></figcaption>' +
              '</figure>';
            }).join('<span class="crd-ba__mid" aria-hidden="true">' + ARROW + '</span>') +
          '</div>'
        });
      }

      // 4. TRANSFORMATION GALLERY
      if (data.transformation_gallery && data.transformation_gallery.length) {
        secs.push({
          label: 'Transformation gallery',
          title: 'Progress along the way',
          html: '<div class="crd-prog crd-prog--' + Math.min(data.transformation_gallery.length, 3) + '">' +
            data.transformation_gallery.slice(0, 3).map(function (g, k) {
              var imgUrl = (g.file_path.indexOf('http') === 0) ? g.file_path : (getBaseUrl() + '/' + g.file_path.replace(/^\/+/, ''));
              return '<figure class="crd-prog__f' + (k === 0 ? ' is-lead' : '') + '">' +
                '<div class="crd-ph crd-ph--prog"><img src="' + esc(imgUrl) + '" alt="' + esc(g.caption || 'Progress image') + '" loading="lazy" /></div>' +
                (g.caption ? '<figcaption>' + esc(g.caption) + '</figcaption>' : '') +
              '</figure>';
            }).join('') +
          '</div>'
        });
      }

      // 5. CLIENT STORY GALLERY
      if (data.client_story_gallery && data.client_story_gallery.length) {
        secs.push({
          label: 'Client story gallery',
          title: 'Moments from the journey',
          html: '<div class="crd-life">' +
            data.client_story_gallery.map(function (g, k) {
              var imgUrl = (g.file_path.indexOf('http') === 0) ? g.file_path : (getBaseUrl() + '/' + g.file_path.replace(/^\/+/, ''));
              return '<figure class="crd-life__f is-square" data-k="' + (k % 3) + '">' +
                '<div class="crd-ph crd-ph--life"><img src="' + esc(imgUrl) + '" alt="' + esc(g.caption || 'Story image') + '" loading="lazy" /></div>' +
                (g.caption ? '<figcaption>' + esc(g.caption) + '</figcaption>' : '') +
              '</figure>';
            }).join('') +
          '</div>'
        });
      }

      // 6. REPORTS & EVIDENCE
      if (data.reports && data.reports.length) {
        secs.push({
          label: 'Reports & evidence',
          title: 'Reports and evidence',
          html: '<ul class="crd-rep">' +
            data.reports.map(function (r) {
              var isPdf = (r.file_mime === 'application/pdf');
              return '<li>' +
                '<span class="crd-rep__type">' + (isPdf ? 'PDF' : 'IMG') + '</span>' +
                '<div class="crd-rep__t"><p>' + esc(r.report_title) + '</p><span>' + esc(r.report_type || 'Report') + (r.report_date ? ' &middot; ' + esc(r.report_date) : '') + '</span></div>' +
                '<button class="crd-rep__view" type="button" data-report-id="' + r.id + '" data-report-title="' + esc(r.report_title) + '" data-report-mime="' + esc(r.file_mime) + '">View report' + ARROW + '</button>' +
              '</li>';
            }).join('') +
          '</ul>'
        });
      }

      // Prev / Next story calculation from carousel DOM
      var storyEls = getCarouselStoryElements();
      var curIdx = -1;
      storyEls.forEach(function (el, k) {
        if (parseInt(el.getAttribute('data-id'), 10) === data.id) curIdx = k;
      });

      var prevData = (curIdx > 0) ? getStoryDataFromDom(storyEls[curIdx - 1]) : null;
      var nextData = (curIdx !== -1 && curIdx < storyEls.length - 1) ? getStoryDataFromDom(storyEls[curIdx + 1]) : null;

      var renderPnButton = function (t, dir) {
        if (!t) return '<span></span>';
        var tTone = t.tone;
        return '<button class="crd-pn__b crd-pn__b--' + dir + '" type="button" data-goto-story-id="' + t.id + '" data-tone="' + tTone + '">' +
          (dir === 'prev' ? ARROW_L + mediaHtml(t.coverImage, t.name, t.initials, 'crd-media--xs') : '') +
          '<span class="crd-pn__t"><small>' + (dir === 'prev' ? 'Previous story' : 'Next story') + '</small><b>' + esc(t.name) + '</b><span>' + esc(t.program) + '</span></span>' +
          (dir === 'next' ? mediaHtml(t.coverImage, t.name, t.initials, 'crd-media--xs') + ARROW : '') +
        '</button>';
      };

      // Compute initials for current story
      var nameParts = (data.client_display_name || '').trim().split(/\s+/);
      var initials = (nameParts.length >= 2)
        ? (nameParts[0].charAt(0) + nameParts[nameParts.length - 1].charAt(0)).toUpperCase()
        : (data.client_display_name || 'CR').substr(0, 2).toUpperCase();

      content.innerHTML =
        '<section class="crd-ov">' +
          '<div class="crd-ov__id">' +
            mediaHtml(data.cover_image, data.client_display_name, initials, 'crd-media--lg') +
            '<div>' +
              '<h2 class="crd-ov__name" id="crd-title">' + esc(data.client_display_name) + '</h2>' +
              (data.client_subtitle ? '<p class="crd-ov__ctx">' + esc(data.client_subtitle) + '</p>' : '') +
              '<p class="crd-ov__prog">' + esc(data.program_name_snapshot || 'Personalised Program') +
                (data.journey_duration ? '<i></i><b>' + esc(data.journey_duration) + '</b>' : '') +
              '</p>' +
            '</div>' +
          '</div>' +
          (data.focus_areas && data.focus_areas.length ? '<div class="crd-ov__focus"><p class="crd-lbl crd-lbl--plain">Transformation focus</p><ul>' + data.focus_areas.map(function (f) { return '<li>' + esc(toTitleCase(f)) + '</li>'; }).join('') + '</ul></div>' : '') +
        '</section>' +
        (data.short_testimonial ? '<section class="crd-quote" aria-label="Testimonial"><blockquote><p>&ldquo;' + esc(data.short_testimonial) + '&rdquo;</p></blockquote><p class="crd-quote__by">' + esc(data.client_display_name) + (data.client_subtitle ? '<span>' + esc(data.client_subtitle) + '</span>' : '') + '</p></section>' : '') +
        secs.map(function (x, k) {
          return '<section class="crd-sec ' + (x.cls || '') + '"><header class="crd-sec__h"><p class="crd-lbl"><b>' + pad(k + 1) + '</b>' + esc(x.label) + '</p>' +
            '<h3>' + esc(x.title) + '</h3>' + (x.note ? '<p class="crd-sec__note">' + esc(x.note) + '</p>' : '') + '</header>' + x.html + '</section>';
        }).join('') +
        '<section class="crd-cta">' +
          '<div><h3>Your journey<br>will be your own.</h3><p>Every plan is built around the individual, their goals, lifestyle and starting point.</p></div>' +
          '<div class="crd-cta__act">' +
            '<a class="crd-cta__p" href="#packages" data-cta="find">Find my program' + ARROW + '</a>' +
            '<a class="crd-cta__s" href="#packages" data-cta="explore">Explore programs</a>' +
            '<p class="crd-cta__fine">Individual outcomes vary.</p>' +
          '</div>' +
        '</section>' +
        '<nav class="crd-pn" aria-label="More client stories">' + renderPnButton(prevData, 'prev') + renderPnButton(nextData, 'next') + '</nav>';

      // Update sticky progressive header
      if (headMedia) headMedia.innerHTML = mediaHtml(data.cover_image, data.client_display_name, initials, 'crd-media--sm');
      if (headNum) headNum.textContent = '/ ' + pad(storyIdx + 1);
      if (headName) headName.innerHTML = esc(data.client_display_name) + (data.client_subtitle ? '<span>' + esc(data.client_subtitle) + '</span>' : '');
      if (headProg) headProg.innerHTML = esc(data.program_name_snapshot || '') + (data.journey_duration ? '<i></i><b>' + esc(data.journey_duration) + '</b>' : '');

      currentId = data.id;
      onScroll();
    }

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

    function open(id, from) {
      if (!id) return;
      clearTimeout(closeTimer);
      openerEl = from || document.activeElement;

      var storyEls = getCarouselStoryElements();
      var storyIdx = 0;
      storyEls.forEach(function (el, k) {
        if (parseInt(el.getAttribute('data-id'), 10) === id) storyIdx = k;
      });

      content.innerHTML = '<div style="padding: 4rem 2rem; text-align: center; color: var(--muted); font-size: 0.95rem;">Loading story baseline...</div>';
      scroller.scrollTop = 0;
      closeViewer(true);

      lock();
      dr.removeAttribute('hidden');
      dr.hidden = false;
      dr.setAttribute('aria-hidden', 'false');
      dr.classList.remove('is-closing');

      void sheet.offsetWidth;
      requestAnimationFrame(function () {
        dr.classList.add('is-open');
      });

      sheet.focus({ preventScroll: true });

      // Asynchronously fetch CodeIgniter public detail endpoint
      var url = getBaseUrl() + '/client-results/' + id + '/detail';
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (res) {
          if (!res.ok) throw new Error('Client Result detail fetch failed');
          return res.json();
        })
        .then(function (resData) {
          var payload = (resData && resData.data) ? resData.data : resData;
          renderArenaDrawer(payload, storyIdx, storyEls.length);
          scroller.scrollTop = 0;
        })
        .catch(function () {
          content.innerHTML = '<div style="padding: 4rem 2rem; text-align: center; color: #ef4444;">Unable to load story details. Please try again.</div>';
        });
    }

    function close() {
      if (dr.hidden || dr.classList.contains('is-closing')) return;
      dr.classList.add('is-closing');
      dr.classList.remove('is-open');

      var delay = reducedMotion.matches ? 0 : 320;
      closeTimer = setTimeout(function () {
        dr.hidden = true;
        dr.setAttribute('hidden', '');
        dr.setAttribute('aria-hidden', 'true');
        dr.classList.remove('is-closing');
        closeViewer(true);
        unlock();

        if (openerEl && document.contains(openerEl)) {
          openerEl.focus({ preventScroll: true });
        }
      }, delay);
    }

    function switchTo(id) {
      if (!id || id === currentId) return;
      if (reducedMotion.matches) {
        open(id);
        return;
      }
      content.classList.add('is-swap');
      setTimeout(function () {
        open(id);
        content.classList.remove('is-swap');
      }, 180);
    }

    /* ------------------------------------------------------------ inner report viewer */
    function openViewer(reportId, title, mime, btn) {
      activeReportBtn = btn;
      if (vname) vname.textContent = title || 'Report';
      if (vmeta) vmeta.textContent = (mime === 'application/pdf') ? 'PDF Document · Secure Stream' : 'Image Document · Secure Stream';

      var streamUrl = getBaseUrl() + '/client-results/report/stream/' + reportId;

      if (mime === 'application/pdf') {
        vbody.innerHTML = '<iframe src="' + esc(streamUrl) + '" style="width:100%; height:100%; border:0;" title="' + esc(title) + '"></iframe>';
      } else {
        vbody.innerHTML = '<div class="crd-vimg" style="display:flex; align-items:center; justify-content:center; padding:1.5rem; height:100%;"><img src="' + esc(streamUrl) + '" alt="' + esc(title) + '" style="max-width:100%; max-height:100%; object-fit:contain;" /></div>';
      }

      vbody.scrollTop = 0;
      viewer.hidden = false;
      scroller.setAttribute('inert', '');
      dr.querySelector('.crd__head').setAttribute('inert', '');
      void viewer.offsetWidth;
      viewer.classList.add('is-open');

      var backBtn = viewer.querySelector('[data-viewer-back]');
      if (backBtn) backBtn.focus({ preventScroll: true });
    }

    function closeViewer(instant) {
      if (viewer.hidden) return;
      viewer.classList.remove('is-open');
      scroller.removeAttribute('inert');
      dr.querySelector('.crd__head').removeAttribute('inert');

      var done = function () {
        viewer.hidden = true;
        vbody.innerHTML = '';
        if (!instant && activeReportBtn && document.contains(activeReportBtn)) {
          activeReportBtn.focus({ preventScroll: true });
        }
      };

      if (instant || reducedMotion.matches) done();
      else setTimeout(done, 300);
    }

    /* ------------------------------------------------------------ header scroll listener */
    function onScroll() {
      dr.classList.toggle('is-scrolled', scroller.scrollTop > 190);
    }
    scroller.addEventListener('scroll', onScroll, { passive: true });

    /* ------------------------------------------------------------ touch drag to dismiss */
    (function () {
      var head = dr.querySelector('.crd__head');
      if (!head) return;
      var y0 = null;
      var dy = 0;

      head.addEventListener('pointerdown', function (e) {
        if (e.target.closest('button') || e.pointerType === 'mouse') return;
        y0 = e.clientY;
        dy = 0;
        head.setPointerCapture(e.pointerId);
        dr.classList.add('is-dragging');
      });

      head.addEventListener('pointermove', function (e) {
        if (y0 == null) return;
        dy = Math.max(0, e.clientY - y0);
        sheet.style.transform = 'translate(-50%,' + dy + 'px)';
      });

      function end() {
        if (y0 == null) return;
        y0 = null;
        dr.classList.remove('is-dragging');
        sheet.style.transform = '';
        if (dy > 120) close();
      }

      head.addEventListener('pointerup', end);
      head.addEventListener('pointercancel', end);
    })();

    /* ------------------------------------------------------------ event delegation */
    dr.addEventListener('click', function (e) {
      var t;
      if (e.target.closest('[data-viewer-back]')) {
        closeViewer();
        return;
      }
      if (e.target.closest('[data-crd-close]')) {
        close();
        return;
      }
      if ((t = e.target.closest('[data-goto-story-id]'))) {
        var targetId = parseInt(t.getAttribute('data-goto-story-id'), 10);
        if (targetId) switchTo(targetId);
        return;
      }
      if ((t = e.target.closest('[data-report-id]'))) {
        var rId = parseInt(t.getAttribute('data-report-id'), 10);
        var rTitle = t.getAttribute('data-report-title');
        var rMime = t.getAttribute('data-report-mime');
        if (rId) openViewer(rId, rTitle, rMime, t);
        return;
      }
      if ((t = e.target.closest('[data-cta]'))) {
        close();
        var targetSection = document.querySelector('#packages') || document.querySelector('#programs');
        if (targetSection) targetSection.scrollIntoView({ behavior: 'smooth' });
        return;
      }
    });

    document.addEventListener('keydown', function (e) {
      if (dr.hidden) return;
      if (e.key === 'Escape') {
        e.preventDefault();
        if (!viewer.hidden) closeViewer();
        else close();
        return;
      }
      if (e.key !== 'Tab') return;
      var scope = viewer.hidden ? sheet : viewer;
      var focusables = Array.prototype.slice.call(scope.querySelectorAll('button:not([disabled]), a[href], [tabindex="0"], iframe')).filter(function (el) {
        return el.offsetParent !== null && !el.closest('[inert]') && !el.closest('[hidden]');
      });
      if (!focusables.length) return;
      var first = focusables[0];
      var last = focusables[focusables.length - 1];

      if (e.shiftKey && (document.activeElement === first || document.activeElement === sheet)) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });

    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('.crj-s__cta, [data-result-id]');
      if (trigger) {
        e.preventDefault();
        var resultId = parseInt(trigger.getAttribute('data-result-id'), 10);
        if (resultId) {
          open(resultId, trigger);
        }
      }
    });

    window.CRD = {
      open: open,
      close: close
    };
  }

  window.CRD = {
    open: function (id, from) {
      var dr = document.getElementById('crd');
      if (dr && typeof initDrawer === 'function') {
        initDrawer();
        if (window.CRD && window.CRD.open) {
          window.CRD.open(id, from);
        }
      }
    },
    close: function () {
      var dr = document.getElementById('crd');
      if (dr && window.CRD && window.CRD.close) {
        window.CRD.close();
      }
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initCarousel();
      initDrawer();
    });
  } else {
    initCarousel();
    initDrawer();
  }
})();

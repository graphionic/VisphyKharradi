/**
 * FTPRENEUR — Package Display Template System (Concept 02 Production Renderer)
 * Handles Show More pagination, drawer slide-up, keyboard ESC close, scroll lock, and prev/next package switching.
 */
document.addEventListener('DOMContentLoaded', function() {
  const sec = document.querySelector('.pkg-sec');
  if (!sec) return;

  const grid = sec.querySelector('.pkg-grid');
  const cards = Array.from(sec.querySelectorAll('.pkg-card'));
  const moreBtn = sec.querySelector('.pkg-more-btn');
  const moreWrap = sec.querySelector('.pkg-more');
  const countEl = sec.querySelector('.pkg-more-count');

  const drawer = document.getElementById('pkg-drawer');
  if (!drawer) return;

  const scrim = drawer.querySelector('.pkg-drawer__scrim');
  const sheet = drawer.querySelector('.pkg-drawer__sheet');
  const closeBtns = drawer.querySelectorAll('[data-pkg-close]');
  const prevBtn = drawer.querySelector('[data-pkg-prev]');
  const nextBtn = drawer.querySelector('[data-pkg-next]');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let closeTimer;
  let afterClose;
  let drawerOpener;

  // Data map for drawer switching and option handling
  const packageDataMap = [];
  cards.forEach((card, index) => {
    try {
      const json = card.getAttribute('data-package-json');
      if (json) {
        const data = JSON.parse(json);
        data.index = index;
        data.cardElement = card;
        
        let initialOpt = null;
        if (data.options && data.options.length > 0) {
          initialOpt = data.options[0];
          data.selectedOptionId = initialOpt ? initialOpt.id : null;
        } else {
          data.selectedOptionId = null;
        }

        packageDataMap.push(data);

        // Render options on card if > 1
        const cardOptsContainer = card.querySelector('[data-card-opts]');
        if (cardOptsContainer && data.options && data.options.length > 1) {
          cardOptsContainer.innerHTML = '';
          data.options.forEach(opt => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pkg-card__opt-btn' + (opt.id === data.selectedOptionId ? ' is-active' : '');
            btn.setAttribute('role', 'radio');
            btn.setAttribute('aria-checked', opt.id === data.selectedOptionId ? 'true' : 'false');
            btn.textContent = opt.formatted_duration || opt.name;
            
            btn.addEventListener('click', function(e) {
              e.stopPropagation(); // prevent opening drawer when picking option
              data.selectedOptionId = opt.id;
              
              // update card pills UI
              cardOptsContainer.querySelectorAll('.pkg-card__opt-btn').forEach(b => {
                b.classList.remove('is-active');
                b.setAttribute('aria-checked', 'false');
              });
              btn.classList.add('is-active');
              btn.setAttribute('aria-checked', 'true');

              // update card price & duration
              const priceEl = card.querySelector('.pkg-card__price b');
              if (priceEl && opt.formatted_price) priceEl.textContent = opt.formatted_price;
              
              const wkEl = card.querySelector('.pkg-card__wk');
              if (wkEl && opt.formatted_duration) wkEl.textContent = opt.formatted_duration;
            });
            cardOptsContainer.appendChild(btn);
          });

          // Set initial card price & duration if default option exists
          if (initialOpt) {
            const priceEl = card.querySelector('.pkg-card__price b');
            if (priceEl && initialOpt.formatted_price) priceEl.textContent = initialOpt.formatted_price;
            const wkEl = card.querySelector('.pkg-card__wk');
            if (wkEl && initialOpt.formatted_duration) wkEl.textContent = initialOpt.formatted_duration;
          }
        }
      }
    } catch (e) {
      console.error('Failed to parse package json', e);
    }
  });

  let activeIndex = 0;
  const initialVisible = 6;
  let currentlyVisible = Math.min(initialVisible, cards.length);

  const layout = window.PackageLayout.create(sec, grid, updateGridVisibility);

  // Initialize grid visibility
  function updateGridVisibility() {
    if (!layout.isCarousel()) {
      currentlyVisible = Math.min(cards.length, layout.fillRows(currentlyVisible));
    }
    cards.forEach((card, i) => {
      if (layout.isCarousel() || i < currentlyVisible) {
        card.style.display = 'grid';
      } else {
        card.style.display = 'none';
      }
    });

    if (countEl) {
      countEl.innerHTML = `Showing <b>${currentlyVisible}</b> of <b>${cards.length}</b> programs`;
    }

    if (moreWrap) {
      if (currentlyVisible >= cards.length) {
        moreWrap.classList.add('is-hidden');
      } else {
        moreWrap.classList.remove('is-hidden');
      }
    }
    layout.refresh();
  }

  updateGridVisibility();

  if (moreBtn) {
    moreBtn.addEventListener('click', function() {
      currentlyVisible = Math.min(currentlyVisible + layout.pageSize(), cards.length);
      updateGridVisibility();
    });
  }

  // Populate drawer
  function openDrawerForPackage(index) {
    if (index < 0) index = packageDataMap.length - 1;
    if (index >= packageDataMap.length) index = 0;

    const data = packageDataMap[index];
    if (!data) return;

    activeIndex = index;

    // Elements inside drawer
    const elCrumbNum = drawer.querySelector('[data-drawer-num]');
    const elCrumbTotal = drawer.querySelector('[data-drawer-total]');
    const elTitle = drawer.querySelector('[data-drawer-title]');
    const elKicker = drawer.querySelector('[data-drawer-kicker]');
    const elLead = drawer.querySelector('[data-drawer-lead]');
    const elDesc = drawer.querySelector('[data-drawer-desc]');
    const elDuration = drawer.querySelector('[data-drawer-duration]');
    const elPrice = drawer.querySelector('[data-drawer-price]');
    const elRegularPrice = drawer.querySelector('[data-drawer-regular-price]');
    const elImg = drawer.querySelector('[data-drawer-img]');
    const elFeatures = drawer.querySelector('[data-drawer-features]');
    const elCta = drawer.querySelector('[data-drawer-cta]');
    const optsWrap = drawer.querySelector('[data-drawer-opts-wrap]');
    const optsContainer = drawer.querySelector('[data-drawer-opts]');

    if (elCrumbNum) elCrumbNum.textContent = String(index + 1).padStart(2, '0');
    if (elCrumbTotal) elCrumbTotal.textContent = String(packageDataMap.length).padStart(2, '0');
    if (elTitle) elTitle.textContent = data.name || '';
    if (elKicker) elKicker.textContent = data.badge ? data.badge.toUpperCase() : 'PROGRAM EDITION';
    if (elLead) elLead.textContent = data.short_description || '';
    
    if (elDesc) {
      if (data.full_description && data.full_description.trim() !== '') {
        elDesc.innerHTML = data.full_description;
        elDesc.style.display = 'block';
      } else {
        elDesc.innerHTML = '';
        elDesc.style.display = 'none';
      }
    }

    // Drawer options & price synchronization
    let activeOpt = null;
    if (data.options && data.options.length > 0) {
      activeOpt = data.options.find(o => o.id === data.selectedOptionId) || data.options[0];
      data.selectedOptionId = activeOpt.id;
    }

    const durationText = activeOpt ? activeOpt.formatted_duration : (data.duration_value && data.duration_unit ? `${data.duration_value} ${data.duration_unit}` : '12 Weeks');
    if (elDuration) elDuration.textContent = durationText;

    const priceText = activeOpt ? activeOpt.formatted_price : (data.formatted_selling_price || ('₹' + (data.selling_price || '')));
    if (elPrice) elPrice.textContent = priceText;
    
    if (elRegularPrice) {
      if (!activeOpt && data.regular_price && parseFloat(data.regular_price) > parseFloat(data.selling_price || 0)) {
        elRegularPrice.textContent = data.formatted_regular_price || ('₹' + data.regular_price);
        elRegularPrice.style.display = 'inline';
      } else {
        elRegularPrice.style.display = 'none';
      }
    }

    // Render drawer options selector if options > 1
    if (optsWrap && optsContainer) {
      if (data.options && data.options.length > 1) {
        optsWrap.style.display = 'block';
        optsContainer.innerHTML = '';
        data.options.forEach(opt => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'pkg-drawer__opt-btn' + (opt.id === data.selectedOptionId ? ' is-active' : '');
          btn.setAttribute('role', 'radio');
          btn.setAttribute('aria-checked', opt.id === data.selectedOptionId ? 'true' : 'false');
          btn.textContent = opt.formatted_duration || opt.name;

          btn.addEventListener('click', function(e) {
            e.stopPropagation();
            data.selectedOptionId = opt.id;
            
            // update drawer buttons
            optsContainer.querySelectorAll('.pkg-drawer__opt-btn').forEach(b => {
              b.classList.remove('is-active');
              b.setAttribute('aria-checked', 'false');
            });
            btn.classList.add('is-active');
            btn.setAttribute('aria-checked', 'true');

            // update drawer price & duration
            if (elPrice && opt.formatted_price) elPrice.textContent = opt.formatted_price;
            if (elDuration && opt.formatted_duration) elDuration.textContent = opt.formatted_duration;

            // sync card UI
            if (data.cardElement) {
              const cardPriceEl = data.cardElement.querySelector('.pkg-card__price b');
              if (cardPriceEl && opt.formatted_price) cardPriceEl.textContent = opt.formatted_price;
              const cardWkEl = data.cardElement.querySelector('.pkg-card__wk');
              if (cardWkEl && opt.formatted_duration) cardWkEl.textContent = opt.formatted_duration;
              const cardOpts = data.cardElement.querySelectorAll('.pkg-card__opt-btn');
              cardOpts.forEach(cb => {
                const isActive = cb.textContent.trim() === (opt.formatted_duration || opt.name).trim();
                cb.classList.toggle('is-active', isActive);
                cb.setAttribute('aria-checked', isActive ? 'true' : 'false');
              });
            }
          });
          optsContainer.appendChild(btn);
        });
      } else {
        optsWrap.style.display = 'none';
      }
    }

    if (elImg) {
      elImg.src = data.image_url || '';
      elImg.alt = data.name || 'Program image';
    }

    if (elFeatures) {
      elFeatures.innerHTML = '';
      if (data.features && Array.isArray(data.features) && data.features.length > 0) {
        data.features.forEach(feat => {
          const li = document.createElement('li');
          li.textContent = feat;
          elFeatures.appendChild(li);
        });
      }
    }

    if (elCta) {
      elCta.textContent = data.cta_label || 'Start this program';
      elCta.removeAttribute('target');
      elCta.removeAttribute('rel');
      elCta.href = '#checkout';
      elCta.onclick = function (event) {
        event.preventDefault();
        if (!window.FtCheckout) return;
        const returnFocus = drawerOpener;
        const optId = data.selectedOptionId;
        closeDrawer(function () { window.FtCheckout.open(data.id, returnFocus, optId); });
      };
    }

    // Open animations & lock scroll
    clearTimeout(closeTimer);
    const wasOpen = drawer.classList.contains('is-active');
    if (!wasOpen) drawerOpener = document.activeElement;
    drawer.inert = false;
    drawer.setAttribute('aria-hidden', 'false');
    drawer.classList.add('is-active');
    document.body.classList.add('is-pkg-drawer-locked');
    if (!wasOpen && sheet) sheet.focus({ preventScroll: true });
  }

  function finishClose() {
    if (drawer.classList.contains('is-active')) return;
    clearTimeout(closeTimer);
    document.body.classList.remove('is-pkg-drawer-locked');
    if (afterClose) { const callback = afterClose; afterClose = null; callback(); }
  }

  function closeDrawer(onClosed) {
    if (!drawer.classList.contains('is-active')) return;
    afterClose = typeof onClosed === 'function' ? onClosed : null;
    drawer.classList.remove('is-active');
    if (!afterClose && drawerOpener && drawerOpener.isConnected) drawerOpener.focus({ preventScroll: true });
    drawer.inert = true;
    drawer.setAttribute('aria-hidden', 'true');
    closeTimer = setTimeout(finishClose, reducedMotion.matches ? 0 : 600);
  }

  if (sheet) sheet.addEventListener('transitionend', function(event) {
    if (event.target === sheet && event.propertyName === 'transform') finishClose();
  });

  // Card click triggers
  cards.forEach((card, index) => {
    card.addEventListener('click', function(e) {
      openDrawerForPackage(index);
    });
  });

  // Prev / Next inside drawer
  if (prevBtn) {
    prevBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      openDrawerForPackage(activeIndex - 1);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      openDrawerForPackage(activeIndex + 1);
    });
  }

  // Close triggers
  closeBtns.forEach(btn => {
    btn.addEventListener('click', closeDrawer);
  });

  if (scrim) {
    scrim.addEventListener('click', closeDrawer);
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && drawer.classList.contains('is-active')) {
      closeDrawer();
    }
  });
});

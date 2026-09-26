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

  // Data map for drawer switching
  const packageDataMap = [];
  cards.forEach((card, index) => {
    try {
      const json = card.getAttribute('data-package-json');
      if (json) {
        const data = JSON.parse(json);
        data.index = index;
        packageDataMap.push(data);
      }
    } catch (e) {
      console.error('Failed to parse package json', e);
    }
  });

  let activeIndex = 0;
  const initialVisible = 6;
  const step = 6;
  let currentlyVisible = Math.min(initialVisible, cards.length);

  // Initialize grid visibility
  function updateGridVisibility() {
    cards.forEach((card, i) => {
      if (i < currentlyVisible) {
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
  }

  updateGridVisibility();

  if (moreBtn) {
    moreBtn.addEventListener('click', function() {
      currentlyVisible = Math.min(currentlyVisible + step, cards.length);
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

    const durationText = data.duration_value && data.duration_unit ? `${data.duration_value} ${data.duration_unit}` : '12 Weeks';
    if (elDuration) elDuration.textContent = durationText;

    if (elPrice) elPrice.textContent = data.formatted_selling_price || ('₹' + (data.selling_price || ''));
    
    if (elRegularPrice) {
      if (data.regular_price && parseFloat(data.regular_price) > parseFloat(data.selling_price || 0)) {
        elRegularPrice.textContent = data.formatted_regular_price || ('₹' + data.regular_price);
        elRegularPrice.style.display = 'inline';
      } else {
        elRegularPrice.style.display = 'none';
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
      if (data.google_form_url && data.google_form_url.trim() !== '') {
        elCta.href = data.google_form_url;
        elCta.target = '_blank';
        elCta.rel = 'noopener';
      } else {
        elCta.href = '#start';
      }
    }

    // Open animations & lock scroll
    drawer.classList.add('is-active');
    document.body.classList.add('is-pkg-drawer-locked');
  }

  function closeDrawer() {
    drawer.classList.remove('is-active');
    document.body.classList.remove('is-pkg-drawer-locked');
  }

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

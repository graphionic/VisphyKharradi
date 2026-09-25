/**
 * Ftpreneur — Section 03: Your Reality Interaction & Floating Preview Script
 * Architecture: Vanilla JavaScript (No External Dependencies, High Performance)
 */
document.addEventListener('DOMContentLoaded', () => {
  const matrix = document.querySelector('.reality__matrix');
  if (!matrix) return;

  const rows = Array.from(matrix.querySelectorAll('.reality__row'));
  const responseNum = document.getElementById('reality-response-num');
  const line1 = document.getElementById('reality-response-line1');
  const line2 = document.getElementById('reality-response-line2');

  const preview = document.getElementById('reality-preview');
  const previewImg = document.getElementById('reality-preview-img');
  const previewLabel = document.getElementById('reality-preview-label');

  if (rows.length === 0) return;

  // Feature detection for desktop fine pointer & hover capability
  const isHoverSupported = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  // Preload preview WebP images on desktop hover-capable devices
  if (isHoverSupported) {
    rows.forEach(row => {
      const imgSrc = row.getAttribute('data-image');
      if (imgSrc) {
        const img = new Image();
        img.src = imgSrc;
      }
    });
  }

  let isUpdating = false;

  function activateRow(row) {
    if (!row || (row.classList.contains('reality__row--active') && !isUpdating)) return;

    const index = row.getAttribute('data-row-index') || '01';
    const textLine1 = row.getAttribute('data-line1') || '';
    const textLine2 = row.getAttribute('data-line2') || '';

    // Update active row classes and ARIA attributes
    rows.forEach(r => {
      const isActive = r === row;
      r.classList.toggle('reality__row--active', isActive);
      r.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    // Animate left contextual response message update
    if (line1 && line2) {
      isUpdating = true;
      line1.classList.add('reality__response-text--changing');
      line2.classList.add('reality__response-text--changing');

      setTimeout(() => {
        if (responseNum) responseNum.textContent = index.padStart(2, '0');
        line1.textContent = textLine1;
        line2.textContent = textLine2;

        line1.classList.remove('reality__response-text--changing');
        line2.classList.remove('reality__response-text--changing');
        isUpdating = false;
      }, 140);
    }
  }

  /* ==========================================================================
     FLOATING IMAGE PREVIEW & POINTER TRACKING (DESKTOP ONLY)
     ========================================================================== */
  let mouseX = 0;
  let mouseY = 0;
  let currentX = 0;
  let currentY = 0;
  let isPreviewVisible = false;
  let rafId = null;
  let currentImageSrc = '';

  const PREVIEW_WIDTH = 300;
  const PREVIEW_HEIGHT = 190;
  const OFFSET_X = 25;
  const OFFSET_Y = 25;

  function updatePreviewPosition() {
    if (!isPreviewVisible && Math.abs(currentX - mouseX) < 0.5 && Math.abs(currentY - mouseY) < 0.5) {
      rafId = null;
      return;
    }

    // Smooth interpolation (lerp)
    currentX += (mouseX - currentX) * 0.18;
    currentY += (mouseY - currentY) * 0.18;

    // Viewport Collision Detection
    let posX = currentX + OFFSET_X;
    let posY = currentY + OFFSET_Y;

    // Flip to left if overflowing right viewport edge
    if (posX + PREVIEW_WIDTH > window.innerWidth - 16) {
      posX = currentX - PREVIEW_WIDTH - OFFSET_X;
    }

    // Flip to top if overflowing bottom viewport edge
    if (posY + PREVIEW_HEIGHT > window.innerHeight - 16) {
      posY = currentY - PREVIEW_HEIGHT - OFFSET_Y;
    }

    // Clamp to top-left bounds
    posX = Math.max(12, posX);
    posY = Math.max(12, posY);

    if (preview) {
      preview.style.transform = `translate3d(${Math.round(posX)}px, ${Math.round(posY)}px, 0)`;
    }

    rafId = requestAnimationFrame(updatePreviewPosition);
  }

  function showPreview(row) {
    if (!isHoverSupported || !preview || !previewImg) return;

    const imgSrc = row.getAttribute('data-image');
    const labelText = row.getAttribute('data-label') || '';

    if (!imgSrc) return;

    // Update image and label with cross-fade transition
    if (currentImageSrc !== imgSrc) {
      currentImageSrc = imgSrc;
      previewImg.classList.add('reality__preview-img--changing');

      setTimeout(() => {
        previewImg.src = imgSrc;
        if (previewLabel) previewLabel.textContent = labelText;
        previewImg.classList.remove('reality__preview-img--changing');
      }, 90);
    }

    if (!isPreviewVisible) {
      isPreviewVisible = true;
      preview.classList.add('reality__preview--visible');
      if (!rafId) rafId = requestAnimationFrame(updatePreviewPosition);
    }
  }

  function hidePreview() {
    if (!isPreviewVisible || !preview) return;
    isPreviewVisible = false;
    preview.classList.remove('reality__preview--visible');
  }

  // Pointer move handler on matrix container
  if (isHoverSupported && matrix) {
    matrix.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      if (!rafId && isPreviewVisible) {
        rafId = requestAnimationFrame(updatePreviewPosition);
      }
    });

    matrix.addEventListener('mouseleave', () => {
      hidePreview();
    });
  }

  /* ==========================================================================
     ROW INTERACTION & ACCESSIBILITY LISTENERS
     ========================================================================== */
  rows.forEach((row, idx) => {
    // Mouseenter for desktop hover interaction & image preview
    row.addEventListener('mouseenter', (e) => {
      activateRow(row);
      if (isHoverSupported) {
        mouseX = e.clientX;
        mouseY = e.clientY;
        showPreview(row);
      }
    });

    // Focus for keyboard navigation
    row.addEventListener('focus', () => {
      activateRow(row);
    });

    // Click for mobile touch and explicit selection
    row.addEventListener('click', (e) => {
      e.preventDefault();
      activateRow(row);
    });

    // Keyboard Arrow Key Navigation
    row.addEventListener('keydown', (e) => {
      let targetRow = null;

      if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
        e.preventDefault();
        targetRow = rows[(idx + 1) % rows.length];
      } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
        e.preventDefault();
        targetRow = rows[(idx - 1 + rows.length) % rows.length];
      } else if (e.key === 'Home') {
        e.preventDefault();
        targetRow = rows[0];
      } else if (e.key === 'End') {
        e.preventDefault();
        targetRow = rows[rows.length - 1];
      }

      if (targetRow) {
        targetRow.focus();
        activateRow(targetRow);
      }
    });
  });

  // Ensure Row 01 is active on load
  if (rows[0] && !matrix.querySelector('.reality__row--active')) {
    activateRow(rows[0]);
  }
});

/* ==========================================================================
   FTPRENEUR — Section 05: The Approach (Concept 01 · Bold Conversion Manifesto)
   Vanilla JS Interactive Process Rail & Stage Explanations
   ========================================================================== */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var STAGES = [
      {
        num: '01',
        title: 'ASSESS',
        headline: 'Comprehensive 1:1 Health & Lifestyle Baseline',
        desc: 'We begin with your health history, daily routine, current nutrition, movement capacity and personal goals. No assumptions — only objective facts.'
      },
      {
        num: '02',
        title: 'PERSONALISE',
        headline: 'Tailored Nutrition, Strength & Lifestyle Strategy',
        desc: 'Nutrition, movement protocols and recovery routines are structured specifically around your body, schedule and health condition.'
      },
      {
        num: '03',
        title: 'BUILD',
        headline: 'Real-World Integration & Sustainable Habit Loop',
        desc: 'The plan is shaped to fit seamlessly within your everyday routine — built for real life, not an idealized environment.'
      },
      {
        num: '04',
        title: 'REVIEW',
        headline: 'Biweekly Check-ins & Data-Driven Refinements',
        desc: 'Progress, adherence and real-world feedback guide ongoing adjustments so your plan constantly evolves with you.'
      },
      {
        num: '05',
        title: 'PROGRESS',
        headline: 'Long-Term Vitality & Lasting Results',
        desc: 'The end goal is sustainable, compound health improvement you can maintain independently for life.'
      }
    ];

    var stepButtons = Array.prototype.slice.call(document.querySelectorAll('.approach-step'));
    var railFill = document.getElementById('approach-rail-fill');
    var cardEl = document.getElementById('approach-explanation');

    var expNum = document.getElementById('exp-num');
    var expTitle = document.getElementById('exp-title');
    var expHeadline = document.getElementById('exp-headline');
    var expDesc = document.getElementById('exp-desc');

    if (!stepButtons.length || !cardEl) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setStage(index) {
      if (index < 0 || index >= STAGES.length) return;

      var st = STAGES[index];

      // Update button active state
      stepButtons.forEach(function (btn, i) {
        var isActive = (i === index);
        btn.classList.toggle('is-active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });

      // Update progress line fill width
      if (railFill) {
        var percentage = (index / (STAGES.length - 1)) * 100;
        railFill.style.width = percentage.toFixed(1) + '%';
      }

      // Smooth card text update
      if (reduceMotion) {
        if (expNum) expNum.textContent = st.num;
        if (expTitle) expTitle.textContent = st.title;
        if (expHeadline) expHeadline.textContent = st.headline;
        if (expDesc) expDesc.textContent = st.desc;
        if (cardEl) cardEl.setAttribute('aria-labelledby', 'tab-' + st.num);
      } else {
        cardEl.classList.add('is-changing');
        setTimeout(function () {
          if (expNum) expNum.textContent = st.num;
          if (expTitle) expTitle.textContent = st.title;
          if (expHeadline) expHeadline.textContent = st.headline;
          if (expDesc) expDesc.textContent = st.desc;
          if (cardEl) cardEl.setAttribute('aria-labelledby', 'tab-' + st.num);

          requestAnimationFrame(function () {
            cardEl.classList.remove('is-changing');
          });
        }, 150);
      }
    }

    // Attach click and hover handlers
    stepButtons.forEach(function (btn, index) {
      btn.addEventListener('click', function () {
        setStage(index);
      });

      btn.addEventListener('mouseenter', function () {
        setStage(index);
      });

      btn.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') {
          e.preventDefault();
          var next = (index + 1) % STAGES.length;
          stepButtons[next].focus();
          setStage(next);
        } else if (e.key === 'ArrowLeft') {
          e.preventDefault();
          var prev = (index - 1 + STAGES.length) % STAGES.length;
          stepButtons[prev].focus();
          setStage(prev);
        }
      });
    });

    // Initialize at Stage 01
    setStage(0);
  });
})();

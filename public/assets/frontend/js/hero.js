/* FTPRENEUR hero — entrance trigger, pointer depth, YOU. interaction.
   Vanilla JS, no dependencies (drop-in for CodeIgniter views). */
(function () {
  "use strict";

  function init() {
    var root = document.documentElement;
    var hero = document.querySelector(".hero");
    if (!hero) return;

    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /* ---------- Entrance: fonts + portrait, capped so users never wait ---------- */
    var img = hero.querySelector(".portrait");
    var imgReady = new Promise(function (res) {
      if (!img) return res();
      if (img.complete && img.naturalWidth) return res();
      img.addEventListener("load", res, { once: true });
      img.addEventListener("error", res, { once: true });
    });
    var fontsReady = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
    var cap = new Promise(function (res) { setTimeout(res, 900); });

    function triggerReady() {
      if (!root.classList.contains("is-ready")) {
        root.classList.add("is-ready");
        root.classList.add("is-settled");
      }
    }

    triggerReady();
    Promise.race([Promise.all([imgReady, fontsReady]), cap]).then(triggerReady);
    setTimeout(triggerReady, 100);

    /* ---------- YOU. — solid ⇄ outline ---------- */
    var you = hero.querySelector(".you--solid");
    if (you) {
      you.addEventListener("mouseenter", function () { hero.classList.add("is-you"); });
      you.addEventListener("mouseleave", function () { hero.classList.remove("is-you"); });
      you.addEventListener("click", function () { hero.classList.toggle("is-you"); });
    }

    /* ---------- Navigation Drawer & Smooth Scroll ---------- */
    var menuBtn = document.querySelector(".nav__menu");
    var drawer = document.getElementById("nav-drawer");
    var body = document.body;

    function openNavDrawer() {
      if (!drawer) return;
      drawer.classList.add("is-open");
      drawer.setAttribute("aria-hidden", "false");
      if (menuBtn) {
        menuBtn.setAttribute("aria-expanded", "true");
        menuBtn.classList.add("is-active");
      }
      body.classList.add("is-nav-drawer-open");
    }

    function closeNavDrawer() {
      if (!drawer) return;
      drawer.classList.remove("is-open");
      drawer.setAttribute("aria-hidden", "true");
      if (menuBtn) {
        menuBtn.setAttribute("aria-expanded", "false");
        menuBtn.classList.remove("is-active");
      }
      body.classList.remove("is-nav-drawer-open");
    }

    if (menuBtn) {
      menuBtn.addEventListener("click", function () {
        var isOpen = drawer && drawer.classList.contains("is-open");
        if (isOpen) {
          closeNavDrawer();
        } else {
          openNavDrawer();
        }
      });
    }

    // Close on data-nav-close elements (scrim, close button)
    var closeEls = document.querySelectorAll("[data-nav-close]");
    for (var i = 0; i < closeEls.length; i++) {
      closeEls[i].addEventListener("click", closeNavDrawer);
    }

    // Close on Escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && drawer && drawer.classList.contains("is-open")) {
        closeNavDrawer();
      }
    });

    // Handle smooth navigation clicks and close drawer on link selection
    var navAnchors = document.querySelectorAll('a[href^="#"]');
    for (var k = 0; k < navAnchors.length; k++) {
      (function(anchor) {
        anchor.addEventListener("click", function (e) {
          var targetId = anchor.getAttribute("href");
          if (!targetId || targetId === "#") return;
          
          var targetEl = document.querySelector(targetId);
          if (targetEl) {
            e.preventDefault();
            closeNavDrawer();
            targetEl.scrollIntoView({ behavior: "smooth" });
            if (history.pushState) {
              history.pushState(null, null, targetId);
            }
          }
        });
      })(navAnchors[k]);
    }


    /* ---------- Editorial Text Loop ---------- */
    var loopEl = hero.querySelector(".editorial-loop");
    var wordEl = loopEl ? loopEl.querySelector(".editorial-loop__word") : null;
    if (wordEl && !reduce) {
      var words = ["MANAGE", "MOVE", "LIVE"];
      var idx = 0;

      setInterval(function () {
        wordEl.classList.add("is-out");

        setTimeout(function () {
          idx = (idx + 1) % words.length;
          wordEl.textContent = words[idx];
          wordEl.classList.remove("is-out");
          wordEl.classList.add("is-in-prep");

          requestAnimationFrame(function () {
            wordEl.classList.remove("is-in-prep");
          });
        }, 350);
      }, 3400);
    }

    /* ---------- Pointer depth — fine pointers only ---------- */
    var fine = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
    if (reduce || !fine) return;

    var layers = Array.prototype.map.call(hero.querySelectorAll("[data-depth]"), function (el) {
      return { el: el, d: parseFloat(el.getAttribute("data-depth")) || 0 };
    });
    var PORTRAIT = 4, YOU = 1.5;
    var tx = 0, ty = 0, cx = 0, cy = 0, raf = 0;

    function frame() {
      cx += (tx - cx) * 0.07;
      cy += (ty - cy) * 0.07;
      for (var i = 0; i < layers.length; i++) {
        var L = layers[i];
        L.el.style.transform = "translate3d(" + (cx * L.d).toFixed(2) + "px," + (cy * L.d * 0.7).toFixed(2) + "px,0)";
      }
      hero.style.setProperty("--mdx", (cx * (PORTRAIT - YOU)).toFixed(2) + "px");
      hero.style.setProperty("--mdy", (cy * (PORTRAIT - YOU) * 0.7).toFixed(2) + "px");
      raf = (Math.abs(tx - cx) > 0.001 || Math.abs(ty - cy) > 0.001) ? requestAnimationFrame(frame) : 0;
    }
    function kick() { if (!raf) raf = requestAnimationFrame(frame); }

    hero.addEventListener("pointermove", function (e) {
      var r = hero.getBoundingClientRect();
      tx = ((e.clientX - r.left) / r.width - 0.5) * 2;
      ty = ((e.clientY - r.top) / r.height - 0.5) * 2;
      kick();
    });
    hero.addEventListener("pointerleave", function () { tx = 0; ty = 0; kick(); });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

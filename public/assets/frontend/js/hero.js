/* FTPRENEUR hero — entrance trigger, pointer depth, YOU. interaction.
   Vanilla JS, no dependencies (drop-in for CodeIgniter views). */
(function () {
  "use strict";

  var root = document.documentElement;
  var hero = document.querySelector(".hero");
  if (!hero) return;

  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- Entrance: fonts + portrait, capped so users never wait ---------- */
  var img = hero.querySelector(".portrait");
  var imgReady = new Promise(function (res) {
    if (img.complete && img.naturalWidth) return res();
    img.addEventListener("load", res, { once: true });
    img.addEventListener("error", res, { once: true });
  });
  var fontsReady = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
  var cap = new Promise(function (res) { setTimeout(res, 900); });

  Promise.race([Promise.all([imgReady, fontsReady]), cap]).then(function () {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        root.classList.add("is-ready");
        setTimeout(function () { root.classList.add("is-settled"); }, reduce ? 0 : 2100);
      });
    });
  });

  /* ---------- YOU. — solid ⇄ outline ---------- */
  var you = hero.querySelector(".you--solid");
  if (you) {
    you.addEventListener("mouseenter", function () { hero.classList.add("is-you"); });
    you.addEventListener("mouseleave", function () { hero.classList.remove("is-you"); });
    you.addEventListener("click", function () { hero.classList.toggle("is-you"); });
  }

  /* ---------- Mobile menu (prototype: state only) ---------- */
  var menu = document.querySelector(".nav__menu");
  if (menu) menu.addEventListener("click", function () {
    menu.setAttribute("aria-expanded", menu.getAttribute("aria-expanded") === "true" ? "false" : "true");
  });

  /* ---------- Editorial Text Loop (lower-left) ---------- */
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

        void wordEl.offsetWidth;

        wordEl.classList.remove("is-in-prep");
      }, 350);
    }, 3200);
  }

  /* ---------- Pointer depth — fine pointers only ---------- */
  var fine = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  if (reduce || !fine) return;

  // data-depth = max px offset at the viewport edge
  var layers = Array.prototype.map.call(hero.querySelectorAll("[data-depth]"), function (el) {
    return { el: el, d: parseFloat(el.getAttribute("data-depth")) || 0 };
  });
  var PORTRAIT = 4, YOU = 1.5;   // keep in sync with markup — used to keep the YOU outline mask glued to Visphy
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
})();

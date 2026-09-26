/* FTPRENEUR — Package Experience · Concept 01 "The Training Log"
   Prototype interactions — vanilla JS, mock data (data.js), no backend. */
(function () {
  "use strict";

  var DATA = window.PK_PROGRAMS || [];
  var STEP = 6;
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  var grid = document.querySelector(".pk__grid");
  var filtersEl = document.querySelector(".pk__filters");
  var moreBtn = document.querySelector(".pk__more-btn");
  var moreT = document.querySelector(".pk__more-t");
  var countN = document.querySelector(".pk__count-n");
  var countT = document.querySelector(".pk__count-t");
  var progress = document.querySelector(".pk__progress");
  if (!grid || !DATA.length) return;

  function pad(n) { return (n < 10 ? "0" : "") + n; }
  function inr(n) { return "₹" + Number(n).toLocaleString("en-IN"); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); }
  function ticks(weeks, cls) {
    var n = Math.min(weeks, 24), h = "";                       // >24 weeks: each tick = 2 weeks
    for (var i = 0; i < n; i++) h += '<i style="--t:' + i + '"></i>';
    return h;
  }
  function pillars(f) {
    var names = ["Nutrition", "Strength", "Lifestyle"];
    return names.map(function (nm, i) { return f[i] === 3 ? "<b>" + nm + "</b>" : nm; }).join(" / ");
  }

  /* ---------- filter state ---------- */
  var CATS = ["All", "Weight", "Metabolic", "Strength", "Nutrition", "Lifestyle"];
  var cat = "All", list = DATA.slice(), shown = 0;

  filtersEl.innerHTML = CATS.map(function (c) {
    var n = c === "All" ? DATA.length : DATA.filter(function (p) { return p.category === c; }).length;
    return '<button class="pk__filter" type="button" data-cat="' + c + '" aria-pressed="' + (c === "All") + '">' + c + "<sup>" + pad(n) + "</sup></button>";
  }).join("");

  filtersEl.addEventListener("click", function (e) {
    var b = e.target.closest(".pk__filter");
    if (!b || b.getAttribute("data-cat") === cat) return;
    filtersEl.querySelectorAll(".pk__filter").forEach(function (x) { x.setAttribute("aria-pressed", String(x === b)); });
    cat = b.getAttribute("data-cat");
    list = cat === "All" ? DATA.slice() : DATA.filter(function (p) { return p.category === cat; });
    if (reduce) { reset(); return; }
    grid.classList.add("is-fading");
    setTimeout(function () { reset(); grid.classList.remove("is-fading"); }, 180);
  });

  /* ---------- card ---------- */
  function card(p, k) {
    return '<li class="pk__cell" style="--k:' + k + '">' +
      '<article class="pk-card' + (p.featured ? " is-featured" : "") + '" data-accent="' + p.accent + '" data-n="' + p.n + '">' +
        (p.featured ? '<span class="pk-card__edge" aria-hidden="true"></span>' : "") +
        '<div class="pk-card__top">' +
          (p.featured ? '<span class="pk-card__sig">Signature program</span>'
                      : '<span class="pk-card__prog">Program // <b>' + pad(p.n) + "</b></span>") +
          '<span class="pk-card__dur">' + p.weeks + " wk</span>" +
        "</div>" +
        '<p class="pk-card__cat"><i aria-hidden="true"></i>' + (p.featured ? "Recommended start · " : "") + esc(p.category) + "</p>" +
        '<span class="pk-card__num" aria-hidden="true">' + pad(p.n) + "</span>" +
        '<h3 class="pk-card__name"><button class="pk-card__open" type="button" aria-haspopup="dialog" aria-controls="pk-drawer" data-n="' + p.n + '">' + esc(p.name) + "</button></h3>" +
        '<p class="pk-card__purpose">' + esc(p.purpose) + "</p>" +
        '<div class="pk-card__track" aria-hidden="true">' + ticks(p.weeks) + "</div>" +
        '<div class="pk-card__signals"><span class="pk-card__weeks">' + p.weeks + ' weeks</span><p class="pk-card__pillars">' + pillars(p.focus) + "</p></div>" +
        '<div class="pk-card__foot">' +
          '<span class="pk-card__fill" aria-hidden="true"></span>' +
          '<p class="pk-card__price"><small>From</small>' + inr(p.price) + "</p>" +
          '<span class="pk-card__cta" aria-hidden="true">Explore <span class="pk-card__arrow"></span></span>' +
        "</div>" +
      "</article></li>";
  }

  /* ---------- grid batches ---------- */
  function renderTo(to, animate) {
    var from = shown, html = "";
    for (var i = from; i < to; i++) html += card(list[i], i - from);
    grid.insertAdjacentHTML("beforeend", html);
    var fresh = Array.prototype.slice.call(grid.children, from);
    shown = to;
    if (animate && !reduce) {
      fresh.forEach(function (li) { li.classList.add("is-new"); });
      grid.getBoundingClientRect();
      requestAnimationFrame(function () {
        fresh.forEach(function (li) { li.classList.add("is-in"); });
        setTimeout(function () { fresh.forEach(function (li) { li.classList.remove("is-new", "is-in"); }); }, 1300);
      });
    }
    sync();
    return fresh;
  }
  function reset() {
    grid.innerHTML = ""; shown = 0;
    renderTo(Math.min(STEP, list.length), true);
  }
  function sync() {
    countN.textContent = pad(shown);
    countT.textContent = pad(list.length);
    var h = "";
    for (var i = 0; i < list.length; i++) h += '<i class="' + (i < shown ? "on" : "") + '" style="--t:' + i + '"></i>';
    progress.innerHTML = h;
    var left = list.length - shown;
    moreBtn.hidden = list.length <= STEP;
    moreBtn.classList.toggle("is-less", left === 0);
    moreT.textContent = left === 0 ? "Show less" : "Show more programs";
    moreBtn.setAttribute("aria-label", left === 0 ? "Show fewer programs" : "Show " + Math.min(STEP, left) + " more programs");
  }
  moreBtn.addEventListener("click", function () {
    if (shown < list.length) {
      var fresh = renderTo(Math.min(shown + STEP, list.length), true);
      var b = fresh[0] && fresh[0].querySelector(".pk-card__open");
      if (b) b.focus({ preventScroll: true });
    } else {
      while (grid.children.length > STEP) grid.removeChild(grid.lastElementChild);
      shown = STEP; sync();
      document.getElementById("programs").scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "start" });
    }
  });
  renderTo(Math.min(STEP, list.length), false);

  /* =========================================================
     DRAWER
     ========================================================= */
  var drawer = document.getElementById("pk-drawer");
  var sheet = drawer.querySelector(".pkd__sheet");
  var scroller = drawer.querySelector(".pkd__scroll");
  var bodyEl = drawer.querySelector(".pkd__body");
  var toast = drawer.querySelector(".pkd__toast");
  var idxLinks = Array.prototype.slice.call(drawer.querySelectorAll(".pkd__index a"));
  var rail = drawer.querySelector(".pkd__rail i");
  var cur = null, opener = null, openCard = null, isOpen = false;

  function F(name) { return drawer.querySelectorAll('[data-f="' + name + '"]'); }
  function set(name, v) { F(name).forEach(function (el) { el.textContent = v; }); }
  function byN(n) { for (var i = 0; i < DATA.length; i++) if (DATA[i].n === n) return DATA[i]; }
  // prev/next move through the CURRENT filtered list (what the visitor is browsing)
  function neighbour(dir) {
    var i = list.indexOf(cur);
    if (i < 0) { list = DATA.slice(); i = list.indexOf(cur); }
    return list[(i + dir + list.length) % list.length];
  }

  function fill(p) {
    cur = p;
    drawer.setAttribute("data-accent", p.accent);
    set("n", pad(p.n));
    set("category", p.category);
    set("name", p.name);
    set("weeks", p.weeks);
    set("suitable", p.suitable);
    set("purpose", p.purpose);
    set("overview", p.overview);
    set("price", inr(p.price));
    set("durationNote", (p.weeks >= 24 ? Math.round(p.weeks / 4.3) + " months" : p.weeks + " weeks") +
        " of structured, personalised support — reviewed every two weeks so the plan keeps pace with your progress.");
    F("track")[0].innerHTML = ticks(p.weeks);
    F("medical")[0].hidden = !p.medical;

    var LV = { 3: "Lead", 2: "Core", 1: "Support" };
    F("work")[0].innerHTML = p.work.map(function (w, k) {
      var bars = "";
      for (var b = 1; b <= 3; b++) bars += '<i class="' + (b <= w.level ? "on" : "") + '" style="--t:' + (k * 3 + b) + '"></i>';
      return "<li><b>" + esc(w.name) + '</b><span class="pkd__lvl" aria-hidden="true">' + bars + "</span><em>" + LV[w.level] + "</em></li>";
    }).join("");
    F("includes")[0].innerHTML = p.includes.map(function (x, k) {
      return '<li data-n="' + pad(k + 1) + '"><b>' + esc(x.t) + "</b><span>" + esc(x.d) + "</span></li>";
    }).join("");

    var w = p.weeks, mid = Math.max(3, Math.round(w * .5));
    var J = [["Start", "Week 0 · welcome + onboarding"], ["Assess", "Week 1 · history, routine, goals"],
             ["Personalise", "Week 1–2 · your plan is built"], ["Build", "Weeks 2–" + mid + " · habits + training"],
             ["Review", "Every 2 weeks · adjust + refine"], ["Progress", "Week " + w + " · next-phase plan"]];
    F("journey")[0].innerHTML = J.map(function (j) { return "<li><b>" + j[0] + "</b><span>" + j[1] + "</span></li>"; }).join("");

    var nx = neighbour(1);
    set("nextN", pad(nx.n));
    set("nextName", nx.name);
    drawer.querySelectorAll("[data-choose]").forEach(function (b) { b.classList.remove("is-done"); });
  }

  function lock(on) {
    var sb = window.innerWidth - document.documentElement.clientWidth;
    document.documentElement.classList.toggle("pk-lock", on);
    document.body.style.paddingRight = on && sb > 0 ? sb + "px" : "";
  }
  function hash(p) { try { history.replaceState(null, "", location.pathname + location.search + (p ? "#program=" + p.slug : "")); } catch (e) {} }

  function open(p, btn) {
    opener = btn || document.activeElement;
    openCard = btn ? btn.closest(".pk-card") : null;
    var go = function () {
      fill(p);
      drawer.hidden = false;
      scroller.scrollTop = 0; bodyEl.scrollTop = 0;
      sheet.style.setProperty("--drag", "0px");
      drawer.getBoundingClientRect();
      requestAnimationFrame(function () { drawer.classList.add("is-open"); });
      lock(true); isOpen = true; hash(p); spy();
      setTimeout(function () { sheet.focus({ preventScroll: true }); }, reduce ? 0 : 140);
    };
    if (openCard && !reduce) {
      // 1 · the selected card responds (floods with its accent)  2 · the sheet rises
      openCard.classList.add("is-launch", "is-selected");
      setTimeout(go, 170);
      setTimeout(function () { openCard && openCard.classList.remove("is-launch"); }, 720);
    } else {
      if (openCard) openCard.classList.add("is-selected");
      go();
    }
  }

  function close() {
    if (!isOpen) return;
    isOpen = false;
    drawer.classList.add("is-closing");
    drawer.classList.remove("is-open");
    var done = function () {
      drawer.hidden = true;
      drawer.classList.remove("is-closing");
      lock(false);
      if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
      var c = openCard;
      setTimeout(function () { c && c.classList.remove("is-selected"); }, 900);   // "you were here"
    };
    reduce ? done() : setTimeout(done, 420);
    hash(null);
  }

  function step(dir) {
    var p = neighbour(dir);
    if (openCard) openCard.classList.remove("is-selected");
    var el = grid.querySelector('.pk-card[data-n="' + p.n + '"]');
    openCard = el; opener = el ? el.querySelector(".pk-card__open") : opener;
    if (el) el.classList.add("is-selected");
    if (reduce) { fill(p); scroller.scrollTop = 0; hash(p); return; }
    drawer.classList.add("is-swapping");
    setTimeout(function () {
      fill(p); scroller.scrollTop = 0; bodyEl.scrollTop = 0; hash(p); spy();
      requestAnimationFrame(function () { drawer.classList.remove("is-swapping"); });
    }, 200);
  }

  grid.addEventListener("click", function (e) {
    var b = e.target.closest(".pk-card__open");
    if (b) open(byN(parseInt(b.getAttribute("data-n"), 10)), b);
  });

  var toastT = 0;
  drawer.addEventListener("click", function (e) {
    var t = e.target;
    if (t.closest("[data-close]")) return close();
    if (t.closest("[data-prev]")) return step(-1);
    if (t.closest("[data-next]")) return step(1);
    var ch = t.closest("[data-choose]");
    if (ch) {
      drawer.querySelectorAll("[data-choose]").forEach(function (b) { b.classList.add("is-done"); });
      toast.textContent = "✓ " + cur.name + " selected — checkout would start here (prototype)";
      toast.classList.add("is-on"); clearTimeout(toastT);
      toastT = setTimeout(function () { toast.classList.remove("is-on"); }, 2600);
      return;
    }
    var a = t.closest(".pkd__index a");
    if (a) {
      e.preventDefault();
      var sec = drawer.querySelector(a.getAttribute("href"));
      if (sec) scroller.scrollTo({ top: sec.offsetTop - 4, behavior: reduce ? "auto" : "smooth" });
      return;
    }
    if (t.closest('a[href="#talk"]')) {
      e.preventDefault();
      toast.textContent = "Talk to us → WhatsApp / call-back form would open here (prototype)";
      toast.classList.add("is-on"); clearTimeout(toastT);
      toastT = setTimeout(function () { toast.classList.remove("is-on"); }, 2600);
    }
  });

  /* section index + progress rail (scroll-spy) */
  function spy() {
    var max = scroller.scrollHeight - scroller.clientHeight;
    rail.style.setProperty("--p", max > 0 ? (scroller.scrollTop / max).toFixed(3) : 1);
    var secs = drawer.querySelectorAll(".pkd__sec"), active = 0;
    for (var i = 0; i < secs.length; i++) if (secs[i].offsetTop - scroller.scrollTop < scroller.clientHeight * .35) active = i;
    if (max > 0 && scroller.scrollTop >= max - 2) active = secs.length - 1;
    idxLinks.forEach(function (a, k) { a.classList.toggle("is-active", k === active); });
  }
  scroller.addEventListener("scroll", function () { requestAnimationFrame(spy); }, { passive: true });

  /* keyboard: Esc · ← → · focus trap */
  document.addEventListener("keydown", function (e) {
    if (!isOpen) return;
    if (e.key === "Escape") { e.preventDefault(); close(); return; }
    if (e.key === "ArrowRight" || e.key === "ArrowLeft") { e.preventDefault(); step(e.key === "ArrowRight" ? 1 : -1); return; }
    if (e.key === "Tab") {
      var f = Array.prototype.filter.call(sheet.querySelectorAll('a[href], button:not([disabled])'), function (el) { return el.offsetParent !== null; });
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && (document.activeElement === first || document.activeElement === sheet)) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  /* drag the handle / top bar down to close */
  (function () {
    var y0 = 0, dy = 0, t0 = 0, on = false;
    function down(e) { if (e.button > 0 || e.target.closest("button, a")) return; on = true; y0 = e.clientY; dy = 0; t0 = performance.now(); drawer.classList.add("is-dragging"); e.currentTarget.setPointerCapture(e.pointerId); }
    function move(e) { if (!on) return; dy = Math.max(0, e.clientY - y0); sheet.style.setProperty("--drag", dy + "px"); }
    function up() {
      if (!on) return; on = false; drawer.classList.remove("is-dragging");
      if (dy > 140 || dy / Math.max(1, performance.now() - t0) > .7) close(); else sheet.style.setProperty("--drag", "0px");
    }
    [drawer.querySelector(".pkd__grab"), drawer.querySelector(".pkd__bar")].forEach(function (el) {
      el.addEventListener("pointerdown", down); el.addEventListener("pointermove", move);
      el.addEventListener("pointerup", up); el.addEventListener("pointercancel", up);
    });
  })();

  /* deep link #program=slug */
  var m = /#program=([\w-]+)/.exec(location.hash);
  if (m) {
    var p = DATA.filter(function (x) { return x.slug === m[1]; })[0];
    if (p) {
      var i = list.indexOf(p);
      if (i >= shown) renderTo(Math.min(list.length, Math.ceil((i + 1) / STEP) * STEP), false);
      open(p, grid.querySelector('.pk-card__open[data-n="' + p.n + '"]'));
    }
  }
})();

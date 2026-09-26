/* =========================================================
   FTPRENEUR — Package Experience · Concept 01 "The Training Log"
   Production JS Engine — Scoped execution with real DB data.
   ========================================================= */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
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

    if (!grid || !DATA || !DATA.length) return;

    function pad(n) { return (n < 10 ? "0" : "") + n; }
    function inr(n) { return "₹" + Number(n).toLocaleString("en-IN"); }
    function esc(s) {
      if (!s) return "";
      return String(s).replace(/[&<>"']/g, function (c) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
      });
    }

    function ticks(weeks) {
      var n = Math.min(weeks || 12, 24), h = "";
      for (var i = 0; i < n; i++) h += '<i style="--t:' + i + '"></i>';
      return h;
    }

    function pillars() {
      return '<b>Nutrition</b> / <b>Strength</b> / <b>Lifestyle</b>';
    }

    /* ---------- Category Filter State ---------- */
    var rawCats = ["All"];
    DATA.forEach(function (p) {
      if (p.category && rawCats.indexOf(p.category) === -1) {
        rawCats.push(p.category);
      }
    });
    var CATS = rawCats;
    var cat = "All", list = DATA.slice(), shown = 0;

    if (filtersEl) {
      filtersEl.innerHTML = CATS.map(function (c) {
        var n = c === "All" ? DATA.length : DATA.filter(function (p) { return p.category === c; }).length;
        return '<button class="pk__filter" type="button" data-cat="' + esc(c) + '" aria-pressed="' + (c === "All") + '">' + esc(c) + '<sup>' + pad(n) + '</sup></button>';
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
    }

    /* ---------- Card Renderer ---------- */
    function card(p, k) {
      var isFeatured = p.featured;
      var accent = p.accent || (isFeatured ? "ultra" : (k % 3 === 0 ? "ultra" : (k % 3 === 1 ? "mint" : "marigold")));
      var numStr = pad(p.n);
      var weeks = p.weeks || 12;

      return '<li class="pk__cell" style="--k:' + k + '">' +
        '<article class="pk-card' + (isFeatured ? " is-featured" : "") + '" data-accent="' + accent + '" data-n="' + p.n + '" data-slug="' + esc(p.slug) + '">' +
          (isFeatured ? '<span class="pk-card__edge" aria-hidden="true"></span>' : "") +
          '<div class="pk-card__top">' +
            (isFeatured ? '<span class="pk-card__sig">Signature program</span>'
                        : '<span class="pk-card__prog">Program // <b>' + numStr + '</b></span>') +
            '<span class="pk-card__dur">' + weeks + ' wk</span>' +
          '</div>' +
          '<p class="pk-card__cat"><i aria-hidden="true"></i>' + (isFeatured ? "Recommended start · " : "") + esc(p.category || "Personalised") + '</p>' +
          '<span class="pk-card__num" aria-hidden="true">' + numStr + '</span>' +
          '<h3 class="pk-card__name"><button class="pk-card__open" type="button" aria-haspopup="dialog" aria-controls="pk-drawer" data-n="' + p.n + '">' + esc(p.name) + '</button></h3>' +
          '<p class="pk-card__purpose">' + esc(p.purpose || p.short_description || "") + '</p>' +
          '<div class="pk-card__track" aria-hidden="true">' + ticks(weeks) + '</div>' +
          '<div class="pk-card__signals"><span class="pk-card__weeks">' + weeks + ' weeks</span><p class="pk-card__pillars">' + pillars() + '</p></div>' +
          '<div class="pk-card__foot">' +
            '<span class="pk-card__fill" aria-hidden="true"></span>' +
            '<p class="pk-card__price"><small>From</small>' + inr(p.price) + '</p>' +
            '<span class="pk-card__cta" aria-hidden="true">Explore <span class="pk-card__arrow"></span></span>' +
          '</div>' +
        '</article></li>';
    }

    /* ---------- Grid Batches & Pagination ---------- */
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
      if (countN) countN.textContent = pad(shown);
      if (countT) countT.textContent = pad(list.length);
      if (progress) {
        var h = "";
        for (var i = 0; i < list.length; i++) h += '<i class="' + (i < shown ? "on" : "") + '" style="--t:' + i + '"></i>';
        progress.innerHTML = h;
      }
      if (moreBtn) {
        var left = list.length - shown;
        moreBtn.hidden = list.length <= STEP;
        moreBtn.classList.toggle("is-less", left === 0);
        if (moreT) moreT.textContent = left === 0 ? "Show less" : "Show more programs";
        moreBtn.setAttribute("aria-label", left === 0 ? "Show fewer programs" : "Show " + Math.min(STEP, left) + " more programs");
      }
    }

    if (moreBtn) {
      moreBtn.addEventListener("click", function () {
        if (shown < list.length) {
          var fresh = renderTo(Math.min(shown + STEP, list.length), true);
          var b = fresh[0] && fresh[0].querySelector(".pk-card__open");
          if (b) b.focus({ preventScroll: true });
        } else {
          while (grid.children.length > STEP) grid.removeChild(grid.lastElementChild);
          shown = STEP; sync();
          var sec = document.getElementById("programs");
          if (sec) sec.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "start" });
        }
      });
    }

    renderTo(Math.min(STEP, list.length), false);

    /* =========================================================
       DRAWER INTERACTIONS & POPULATION
       ========================================================= */
    var drawer = document.getElementById("pk-drawer");
    if (!drawer) return;

    var sheet = drawer.querySelector(".pkd__sheet");
    var scroller = drawer.querySelector(".pkd__scroll");
    var bodyEl = drawer.querySelector(".pkd__body");
    var toast = drawer.querySelector(".pkd__toast");
    var idxLinks = Array.prototype.slice.call(drawer.querySelectorAll(".pkd__index a"));
    var rail = drawer.querySelector(".pkd__rail i");
    var cur = null, opener = null, openCard = null, isOpen = false;

    function F(name) { return drawer.querySelectorAll('[data-f="' + name + '"]'); }
    function set(name, v) { F(name).forEach(function (el) { el.textContent = v; }); }
    function byN(n) {
      for (var i = 0; i < DATA.length; i++) if (DATA[i].n === n) return DATA[i];
      return null;
    }

    function neighbour(dir) {
      var i = list.indexOf(cur);
      if (i < 0) { list = DATA.slice(); i = list.indexOf(cur); }
      return list[(i + dir + list.length) % list.length];
    }

    function fill(p) {
      cur = p;
      var accent = p.accent || (p.featured ? "ultra" : "ultra");
      drawer.setAttribute("data-accent", accent);
      set("n", pad(p.n));
      set("category", p.category || "Personalised Program");
      set("name", p.name);
      set("weeks", p.weeks || 12);
      set("suitable", p.suitable || "Anyone seeking scientific, structured guidance for nutrition, strength, and lifestyle habits.");
      set("purpose", p.purpose || p.short_description || "");
      set("overview", p.full_description || p.short_description || "Every program is structured 1:1 around your assessment, routine, and progress.");
      set("price", inr(p.price));

      var w = p.weeks || 12;
      set("durationNote", (w >= 24 ? Math.round(w / 4.3) + " months" : w + " weeks") +
          " of structured, personalised support — reviewed every two weeks so the plan keeps pace with your progress.");

      var tr = F("track")[0];
      if (tr) tr.innerHTML = ticks(w);

      var med = F("medical")[0];
      if (med) med.hidden = false;

      // Work Focus
      var workEl = F("work")[0];
      if (workEl) {
        var focusItems = [
          { name: "Nutrition & Metabolic Assessment", level: 3 },
          { name: "Strength & Movement Protocols", level: 3 },
          { name: "Lifestyle & Recovery Habits", level: 2 },
          { name: "Biweekly Progress Reviews", level: 3 }
        ];
        var LV = { 3: "Lead", 2: "Core", 1: "Support" };
        workEl.innerHTML = focusItems.map(function (item, k) {
          var bars = "";
          for (var b = 1; b <= 3; b++) bars += '<i class="' + (b <= item.level ? "on" : "") + '" style="--t:' + (k * 3 + b) + '"></i>';
          return "<li><b>" + esc(item.name) + '</b><span class="pkd__lvl" aria-hidden="true">' + bars + '</span><em>' + LV[item.level] + '</em></li>';
        }).join("");
      }

      // Features / Included
      var incEl = F("includes")[0];
      if (incEl) {
        var featList = (p.features && p.features.length) ? p.features : [
          "1:1 Comprehensive Health Assessment",
          "Custom Tailored Nutrition Plan",
          "Strength & Movement Routine",
          "Biweekly Progress Reviews & Adjustments",
          "Direct Coach Support & Guidance"
        ];
        incEl.innerHTML = featList.map(function (x, k) {
          var title = typeof x === 'string' ? x : (x.title || x.feature_text || 'Feature');
          var desc = typeof x === 'object' && x.desc ? x.desc : 'Personalised to your goals and daily routine.';
          return '<li data-n="' + pad(k + 1) + '"><b>' + esc(title) + '</b><span>' + esc(desc) + '</span></li>';
        }).join("");
      }

      // Journey
      var jEl = F("journey")[0];
      if (jEl) {
        var mid = Math.max(3, Math.round(w * .5));
        var J = [
          ["Start", "Week 0 · welcome + onboarding"],
          ["Assess", "Week 1 · history, routine, goals"],
          ["Personalise", "Week 1–2 · your plan is built"],
          ["Build", "Weeks 2–" + mid + " · habits + training"],
          ["Review", "Every 2 weeks · adjust + refine"],
          ["Progress", "Week " + w + " · next-phase plan"]
        ];
        jEl.innerHTML = J.map(function (j) { return "<li><b>" + j[0] + "</b><span>" + j[1] + "</span></li>"; }).join("");
      }

      // Next preview
      var nx = neighbour(1);
      set("nextN", pad(nx.n));
      set("nextName", nx.name);

      // Form CTA destination
      var ctaBtn = drawer.querySelector("[data-choose]");
      if (ctaBtn) {
        ctaBtn.classList.remove("is-done");
      }
      var ctaFormUrl = p.google_form_url || null;
      drawer.querySelectorAll(".pkd__cta").forEach(function (el) {
        if (ctaFormUrl && el.tagName.toLowerCase() === 'a') {
          el.href = ctaFormUrl;
          el.target = "_blank";
          el.rel = "noopener noreferrer";
        }
      });
    }

    function lock(on) {
      var sb = window.innerWidth - document.documentElement.clientWidth;
      document.documentElement.classList.toggle("pk-lock", on);
      document.body.style.paddingRight = on && sb > 0 ? sb + "px" : "";
    }

    function hash(p) {
      try {
        history.replaceState(null, "", location.pathname + location.search + (p ? "#program=" + p.slug : ""));
      } catch (e) {}
    }

    function open(p, btn) {
      opener = btn || document.activeElement;
      openCard = btn ? btn.closest(".pk-card") : null;
      var go = function () {
        fill(p);
        drawer.hidden = false;
        if (scroller) scroller.scrollTop = 0;
        if (bodyEl) bodyEl.scrollTop = 0;
        sheet.style.setProperty("--drag", "0px");
        drawer.getBoundingClientRect();
        requestAnimationFrame(function () { drawer.classList.add("is-open"); });
        lock(true); isOpen = true; hash(p); spy();
        setTimeout(function () { sheet.focus({ preventScroll: true }); }, reduce ? 0 : 140);
      };

      if (openCard && !reduce) {
        openCard.classList.add("is-launch", "is-selected");
        setTimeout(go, 170);
        setTimeout(function () { if (openCard) openCard.classList.remove("is-launch"); }, 720);
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
        setTimeout(function () { if (c) c.classList.remove("is-selected"); }, 900);
      };
      reduce ? done() : setTimeout(done, 420);
      hash(null);
    }

    function step(dir) {
      var p = neighbour(dir);
      if (openCard) openCard.classList.remove("is-selected");
      var el = grid.querySelector('.pk-card[data-n="' + p.n + '"]');
      openCard = el;
      opener = el ? el.querySelector(".pk-card__open") : opener;
      if (el) el.classList.add("is-selected");
      if (reduce) { fill(p); if (scroller) scroller.scrollTop = 0; hash(p); return; }
      drawer.classList.add("is-swapping");
      setTimeout(function () {
        fill(p);
        if (scroller) scroller.scrollTop = 0;
        if (bodyEl) bodyEl.scrollTop = 0;
        hash(p);
        spy();
        requestAnimationFrame(function () { drawer.classList.remove("is-swapping"); });
      }, 200);
    }

    grid.addEventListener("click", function (e) {
      var b = e.target.closest(".pk-card__open");
      if (b) {
        var nVal = parseInt(b.getAttribute("data-n"), 10);
        var pObj = byN(nVal);
        if (pObj) open(pObj, b);
      }
    });

    var toastT = 0;
    drawer.addEventListener("click", function (e) {
      var t = e.target;
      if (t.closest("[data-close]")) return close();
      if (t.closest("[data-prev]")) return step(-1);
      if (t.closest("[data-next]")) return step(1);
      var ch = t.closest("[data-choose]");
      if (ch && !cur.google_form_url) {
        drawer.querySelectorAll("[data-choose]").forEach(function (b) { b.classList.add("is-done"); });
        if (toast) {
          toast.textContent = "✓ " + cur.name + " selected — Redirecting to consultation...";
          toast.classList.add("is-on");
          clearTimeout(toastT);
          toastT = setTimeout(function () { toast.classList.remove("is-on"); }, 2600);
        }
        return;
      }
      var a = t.closest(".pkd__index a");
      if (a) {
        e.preventDefault();
        var sec = drawer.querySelector(a.getAttribute("href"));
        if (sec && scroller) scroller.scrollTo({ top: sec.offsetTop - 4, behavior: reduce ? "auto" : "smooth" });
        return;
      }
    });

    /* Section Index Scroll-Spy */
    function spy() {
      if (!scroller) return;
      var max = scroller.scrollHeight - scroller.clientHeight;
      if (rail) rail.style.setProperty("--p", max > 0 ? (scroller.scrollTop / max).toFixed(3) : 1);
      var secs = drawer.querySelectorAll(".pkd__sec"), active = 0;
      for (var i = 0; i < secs.length; i++) {
        if (secs[i].offsetTop - scroller.scrollTop < scroller.clientHeight * .35) active = i;
      }
      if (max > 0 && scroller.scrollTop >= max - 2) active = secs.length - 1;
      idxLinks.forEach(function (a, k) { a.classList.toggle("is-active", k === active); });
    }

    if (scroller) {
      scroller.addEventListener("scroll", function () { requestAnimationFrame(spy); }, { passive: true });
    }

    /* Keyboard Navigation */
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

    /* Drag Down to Close */
    (function () {
      var y0 = 0, dy = 0, t0 = 0, on = false;
      function down(e) { if (e.button > 0 || e.target.closest("button, a")) return; on = true; y0 = e.clientY; dy = 0; t0 = performance.now(); drawer.classList.add("is-dragging"); e.currentTarget.setPointerCapture(e.pointerId); }
      function move(e) { if (!on) return; dy = Math.max(0, e.clientY - y0); sheet.style.setProperty("--drag", dy + "px"); }
      function up() {
        if (!on) return; on = false; drawer.classList.remove("is-dragging");
        if (dy > 140 || dy / Math.max(1, performance.now() - t0) > .7) close(); else sheet.style.setProperty("--drag", "0px");
      }
      var grabEl = drawer.querySelector(".pkd__grab");
      var barEl = drawer.querySelector(".pkd__bar");
      [grabEl, barEl].forEach(function (el) {
        if (!el) return;
        el.addEventListener("pointerdown", down); el.addEventListener("pointermove", move);
        el.addEventListener("pointerup", up); el.addEventListener("pointercancel", up);
      });
    })();

    /* Deep Link #program=slug */
    var m = /#program=([\w-]+)/.exec(location.hash);
    if (m) {
      var pMatch = DATA.filter(function (x) { return x.slug === m[1]; })[0];
      if (pMatch) {
        var idx = list.indexOf(pMatch);
        if (idx >= shown) renderTo(Math.min(list.length, Math.ceil((idx + 1) / STEP) * STEP), false);
        var targetBtn = grid.querySelector('.pk-card__open[data-n="' + pMatch.n + '"]');
        open(pMatch, targetBtn);
      }
    }
  });
})();

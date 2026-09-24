/* Visphy Kharradi — vanilla JS interactions
   Replaces the three React useState widgets:
   1) S24 Forms  — live text input (native, nothing needed)
   2) S27 Conditions — selectable chips + "SELECTED →" label
   3) S31 FAQ — single-open accordion with +/− icon
*/
(function () {
  "use strict";

  var ICON_PLUS =
    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>';
  var ICON_MINUS =
    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-minus" aria-hidden="true"><path d="M5 12h14"></path></svg>';

  function swap(el, remove, add) {
    remove.forEach(function (c) { el.classList.remove(c); });
    add.forEach(function (c) { el.classList.add(c); });
  }

  /* ---------- S27: condition chips ---------- */
  var CHIP_ON = ["bg-[#141B31]", "text-white"];
  var CHIP_OFF = ["bg-white", "text-[#141B31]", "soft-card", "hover:bg-[#141B31]", "hover:text-white"];

  function initChips() {
    var section = document.getElementById("s27");
    if (!section) return;
    var chips = Array.prototype.slice.call(section.querySelectorAll("button.rounded-full"));
    if (!chips.length) return;
    var label = chips[0].parentElement.nextElementSibling; // "SELECTED → ..." line

    chips.forEach(function (chip) {
      chip.setAttribute("type", "button");
      chip.setAttribute("aria-pressed", chip.classList.contains("bg-[#141B31]") ? "true" : "false");
      chip.addEventListener("click", function () {
        chips.forEach(function (c) {
          var on = c === chip;
          swap(c, on ? CHIP_OFF : CHIP_ON, on ? CHIP_ON : CHIP_OFF);
          c.setAttribute("aria-pressed", on ? "true" : "false");
        });
        if (label) {
          label.textContent =
            "SELECTED \u2192 " + chip.textContent.trim().toUpperCase() +
            " \u00B7 CHIPS ARE SELECTORS, NOT DECORATION";
        }
      });
    });
  }

  /* ---------- S31: FAQ accordion ---------- */
  var ICON_ON = ["bg-[#2E47FF]", "text-white"];
  var ICON_OFF = ["border", "border-[#E4E8F0]", "bg-white"];
  var PANEL_ON = ["grid-rows-[1fr]", "pb-5", "opacity-100"];
  var PANEL_OFF = ["grid-rows-[0fr]", "opacity-0"];

  function initFaq() {
    var section = document.getElementById("s31");
    if (!section) return;
    var items = Array.prototype.slice.call(section.querySelectorAll("button.text-left")).map(function (btn) {
      return { btn: btn, icon: btn.lastElementChild, panel: btn.nextElementSibling };
    });

    function setOpen(item, open) {
      swap(item.icon, open ? ICON_OFF : ICON_ON, open ? ICON_ON : ICON_OFF);
      swap(item.panel, open ? PANEL_OFF : PANEL_ON, open ? PANEL_ON : PANEL_OFF);
      item.icon.innerHTML = open ? ICON_MINUS : ICON_PLUS;
      item.btn.setAttribute("aria-expanded", open ? "true" : "false");
    }

    items.forEach(function (item) {
      item.btn.setAttribute("type", "button");
      item.btn.setAttribute("aria-expanded", item.panel.classList.contains("opacity-100") ? "true" : "false");
      item.btn.addEventListener("click", function () {
        var wasOpen = item.btn.getAttribute("aria-expanded") === "true";
        items.forEach(function (it) { setOpen(it, it === item && !wasOpen); });
      });
    });
  }

  function init() {
    initChips();
    initFaq();
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();

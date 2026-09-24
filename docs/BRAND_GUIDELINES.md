# FTPRENEUR Brand Guidelines — Source of Truth

**Location:** `docs/brand-guidelines/`  
**Status:** Approved visual reference — **NOT production frontend code**  
**Purpose:** Permanent project documentation for all future public website work

---

## What `docs/brand-guidelines/` Is

`docs/brand-guidelines/` contains the **approved Visphy Kharradi — Brand Guidelines + Digital Visual System** as supplied:

```
docs/brand-guidelines/
├── index.html          — 43-section brand guideline (pre-rendered, icons inlined)
├── README.md           — static export notes (no build, breakpoints, external resources)
├── css/
│   └── styles.css      — compiled Tailwind + custom animations (standalone)
├── js/
│   └── main.js         — vanilla JS (condition chips S27, FAQ accordion S31)
└── assets/
    ├── image-1.png     — supplementary guideline imagery (as supplied)
    └── image-2.png
```

Open `docs/brand-guidelines/index.html` in a browser (or `python3 -m http.server docs/brand-guidelines`) to preview. It loads Google Fonts + Pexels images from the internet and requires no build step.

This folder is **documentation/reference only**.

---

## What It Is NOT

- **NOT** the Ftpreneur frontend theme
- **NOT** the public landing page (`/` → `Controllers/Home::index` → `Views/home/*`)
- **NOT** production frontend code (`public/`, `app/Views/`)
- **NOT** a CodeIgniter View template to be copied verbatim into `app/Views/`
- **NOT** a drop-in theme (`index.html` is 43 guideline stops, not a homepage)

**Preserve the distinction:**

- **BRAND GUIDELINE** = visual rules and design reference (colors, type, spacing, components, photography, motion)
- **FRONTEND** = future production implementation *based on* those rules, in CI4 architecture

---

## How Future Public Frontend Must Use It

All future public website work **must follow** this approved system:

- **Colors** — Harbour Ultramarine `#2E47FF`, Mint `#00B79B`, Marigold `#FF9E2C`, Ink `#141B31`, derived tints, usage ratios (75–80 light / 15–20 brand / 5–10 ink), and accent rules defined in S07–S11
- **Typography** — Display/body pairings, scale, hierarchy, and type treatments in S12–S14 (Bricolage/Schibsted/Karla/Golos etc. — guideline shows candidates, future `FRONTEND_ARCHITECTURE.md` will lock choice)
- **Spacing, radius, surfaces, rhythm** — S36–S38 (section rhythm, surfaces, radius 15–28)
- **Components** — Buttons (S23), Forms (S24), Cards vs editorial lists (S25–S27), Nav/Footer (S32–S33), Conditions/N×M×L compositions (S27–S28)
- **Imagery treatment** — Photography direction, masks, cutouts, journey line (S15–S20)
- **Motion** — Reveal/flow timings, micro-interactions, `prefers-reduced-motion` (S34–S35)

Implementation **may adapt markup** for:

- Accessibility (semantic HTML, ARIA, keyboard, contrast, focus)
- Responsiveness (breakpoints sm 640 · md 768 · lg 1024 · xl 1280 as in README, but layout may be simplified)
- SEO (headings, meta, structured data)
- CodeIgniter architecture (layout inheritance, partials, `Views/home/`, `public/assets/` pipeline)
- Performance (asset optimization, font loading)

Visual changes that **materially conflict** with the guideline — palette swap, type-system change, journey-line removal, wholesale component restyling — **require explicit approval** before implementation.

---

## What Must NOT Be Done

- Do not copy `docs/brand-guidelines/index.html` structure verbatim into `app/Views/home/` or `app/Views/layouts/`
- Do not treat `css/styles.css` as production `public/assets/` CSS (it is a standalone compiled 43-section stylesheet with guideline chrome)
- Do not extract guideline chrome (aside nav, 43 stops, proposed badges) into production pages
- Do not reinterpret or redesign the approved system before brand sign-off
- Do not modify `public/index.php`, `public/assets/`, or `app/Views/home/foundation.php` as part of brand-guideline storage

---

## Relationship to Project Phases

- **Phase 5E.3 Package Management** is **SEALED** (294 tests / 2205 assertions / 0 FK) — completely independent
- This brand guideline storage is **pure documentation**, not Phase 6
- **Frontend implementation (Phase 6) remains blocked** until brand guidelines are formally approved and this document is the locked source
- When frontend work begins, it will **reference** `docs/brand-guidelines/` — not duplicate it

---

## Preview

```bash
# Local preview (no build)
python3 -m http.server --directory docs/brand-guidelines 8001
# → http://localhost:8001/
```

Or double-click `docs/brand-guidelines/index.html`. Requires internet for Google Fonts/Pexels.

---

## Change Control

- This folder is the **permanent visual source of truth**. Do not edit `docs/brand-guidelines/` to “fix” production bugs — fix production `app/Views/` / `public/assets/` instead.
- If the guideline itself must change, replace the four supplied files as a set and update this document’s change log, with approval.
- Do not treat the guideline as a frontend theme — treat it as the **rulebook** for the future frontend theme.

*Stored: 2026-09-24 — Approved brand guidelines preserved as project documentation. Future public frontend must derive from this system.*

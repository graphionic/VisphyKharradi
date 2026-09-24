# FTPRENEUR — Admin Design System (Phase 4)

> **Status:** Phase 4 sealed — premium admin design system & application shell.
> **Scope:** Design tokens, typography, spacing, colors, surfaces, borders, shadows, icons, layout, components. No Package Management.
> **Principle:** Premium internal SaaS — calm, minimal, high clarity, strong hierarchy, editorial restraint. Not a Bootstrap/WordPress clone.

---

## 1. Design Principles

- **Calm & operational:** Neutral surfaces dominate (warm ivory #FAFAF8, white, stone). Brand navy used strategically for primary actions & sidebar.
- **Hierarchy via typography + spacing, not boxes:** Avoid bordering everything. Separation through whitespace, background contrast, subtle dividers.
- **Grounded elevation:** Most surfaces flat with hairline borders. Elevation (shadow-md/lg) reserved for dropdowns, modals, popovers.
- **One coherent icon system:** Outline SVG, `currentColor`, 16/18/20/24 sizes. No emoji.
- **Accessibility first:** Visible focus ring, keyboard navigable, `prefers-reduced-motion` honored.

---

## 2. Design Tokens — `public/assets/admin/css/tokens.css`

Centralized CSS custom properties. No scattered hex.

### Colors
```
--color-bg: #FAFAF8
--color-surface: #FFFFFF
--color-surface-subtle: #F8FAFC
--color-text: #0F172A (near-black)
--color-text-secondary: #334155
--color-text-muted: #64748B
--color-border: #E2E8F0
--color-border-strong: #CBD5E1
--color-border-subtle: #F1F5F9

--color-primary: #0F2A3D (deep navy)
--color-primary-hover: #143752
--color-primary-soft: #EEF2F7
--color-accent: #1A4FA3 (sapphire)
--color-gold: #B89A5B (muted, sparing)
--color-success: #0E7A5B / soft #ECFDF5
--color-warning: #92400E / soft #FFFBEB
--color-danger: #B91C1C / soft #FEF2F2
--color-info: #1E40AF / soft #EFF6FF
```

### Radius
`--radius-xs 6px, --radius-sm 8px, --radius-md 10px, --radius-lg 12px, --radius-xl 16px, --radius-full 9999px`

### Shadows
- `xs`: `0 1px 2px rgba(15,23,42,0.06)`
- `sm`: `0 1px 3px rgba(...,0.08)`
- `md`: `0 4px 12px rgba(...,0.08)`
- `lg`: `0 10px 24px rgba(...,0.10)`
- Focus: `0 0 0 3px rgba(15,42,61,0.18)`

### Spacing
`--space-1 4px … --space-16 64px` (4pt scale)

### Typography
`--font-sans`: system-ui / Inter / Segoe UI; `--font-mono` for code.
Sizes: `xs 12, sm 13, base 15, lg 17, xl 20, 2xl 24`

### Layout
`--sidebar-width 264px, --sidebar-collapsed 72px, --topbar-height 56px, --content-max 1280px`

### Motion
`--duration-fast 140ms, --duration-normal 200ms, --ease-default cubic-bezier(0.2,0,0,1)` — disabled when `prefers-reduced-motion: reduce`.

---

## 3. Typography

- **Page title:** 1.375rem / 600 / -0.02em / #0F172A
- **Section/card title:** 0.93–1rem / 600
- **Body:** 0.9375rem / 1.5 / #0F172A
- **Small/muted:** 0.8125rem / #64748B
- **Label:** 0.8125rem / 600
- **Caption:** 0.75rem / uppercase / 0.02em / muted
- **Numeric:** `font-variant-numeric: tabular-nums`, 600

All text escaped via `esc()` in views.

---

## 4. Borders & Shadows Philosophy

- **Borders have purpose:** Forms, tables, cards use hairline `1px solid var(--color-border)`. Layout regions (sidebar/main) separated by spacing, not nested boxes.
- **Shadows grounded:** Cards use `shadow-xs`; dropdowns/modals use `shadow-lg`. No glowing or dark large shadows.

---

## 5. Icons

- **System:** Inline outline SVG, one style, `currentColor`.
- **Sizes:** 16, 18, 20, 24.
- **Usage:** `aria-hidden="true"` for decorative; `aria-label` for icon-only buttons.
- **Library:** No external dependency (Heroicons-inspired paths). Keeps JS/CSS lightweight.

---

## 6. Layout — `layout.css`

**Shell:** CSS Grid
```
sidebar (264px) | topbar (56px)
sidebar          | main
```
- **Collapsed:** `admin-shell--collapsed` → 72px, icons-only, tooltips, persisted in `localStorage` (no DB).
- **Mobile:** Sidebar becomes off-canvas drawer (fixed, `translateX(-100%)` → `0`), overlay, `body overflow hidden`, Escape/overlay close, focus moves to first link.

**Topbar:** Sticky, white, border-bottom. Left: mobile menu + collapse + page context. Right: account dropdown (name/email + Profile + Logout POST).

**Main:** `max-width 1280px`, `padding 24px` (16px mobile).

**Auth layout:** Centered card (420px) on warm radial gradient background.

---

## 7. Sidebar — `partials/sidebar.php`

**Groups:** Overview → Management (disabled Soon) → System (disabled) → Account.

- Each group: `OVERVIEW` label (0.66rem uppercase, 0.12em) + list gap 2px.
- Link: 18px icon + label + optional `Soon` badge. Hover: `rgba(255,255,255,0.06)`. Active: white bg + navy text + semibold.
- Footer: avatar (initial) + name/email + Logout POST.
- Active derived from `uri_string()` via `ftpreneur_isActive($current, [...])` — no duplicate sidebars.

**Disabled future items:** `aria-disabled="true"`, `pointer-events: none`, `Soon` chip, no dead links.

---

## 8. Topbar — `partials/topbar.php`

- **Mobile trigger:** `data-sidebar-open` → drawer.
- **Collapse:** `data-sidebar-collapse` (desktop, persisted).
- **Account dropdown:** `data-dropdown` → menu with Profile + UI Preview (dev) + Logout POST. Keyboard: ArrowDown/Up, Escape, click outside.

No fake notifications/search.

---

## 9. Page Header — `partials/page_header.php`

```php
view('admin/partials/page_header', [
  'title' => 'Packages',
  'description' => 'Manage programs...',
  'actions' => '<a class="btn btn--primary">Add Package</a>' // pre-escaped/safe html
])
```
Renders title + description + optional actions. Also used via inline markup in dashboard/profile.

---

## 10. Buttons — `components.css`

- Variants: `btn--primary` (navy), `btn--accent` (sapphire), `btn--secondary` (white/border), `btn--ghost` (transparent), `btn--danger`, `btn--danger-soft`
- Sizes: `btn--sm` (30px), default 36px, `btn--lg` 42px
- States: hover, focus-visible (ring), active (translateY), disabled (0.52 opacity), loading (spinner `::after`, `currentColor`)
- Icon gap 8px, consistent radius/padding/weight.

**Icon buttons:** `icon-btn` 36px (30px sm), border, hover subtle, ghost variant.

---

## 11. Forms — `components.css`

- **Structure:** `.field` → label + input/select/textarea + hint + error. `.form__row--2` grid 2-col → 1-col mobile.
- **Input:** 38px, `1px solid --color-border-input`, radius 8px, hover strong, focus ring. Error: danger border + soft ring. Disabled: subtle bg.
- **Select:** native, custom arrow via CSS gradients, no Select2.
- **Password toggle:** `password-field` wrapper + absolute button, swaps `type`, `aria-pressed`, `aria-label`, swaps eye/eye-off SVG.
- **Checkbox/Radio:** custom 18px, checked navy bg + checkmark via mask.
- **Toggle:** 38×22 pill, smooth.
- All fields support label (required `*`), help text, validation error (associated via DOM, not just placeholder).

---

## 12. Cards / Surfaces — `components.css`

- `card`: white + border + radius-lg + shadow-xs
- `card--subtle`: `bg surface-subtle`, no shadow
- `card--interactive`: hover border-strong + shadow-sm
- Parts: `card__header` (flex, border-bottom), `card__body` (24px), `card__footer` (subtle bg, right-aligned actions)

No 10 variants.

---

## 13. Tables — `components.css`

- Wrap: `.table-wrap` → horizontal scroll, border, radius-lg, shadow-xs
- Table: `table th` uppercase 0.72rem, sticky top, muted, subtle bg. `td` 12px 14px, border-bottom subtle, hover row `rgba(248,250,252,0.9)`.
- Semantic: `table > thead > tbody > th/td`
- **Responsive strategy:** Horizontal scroll with sticky first column where useful (future). Documented as acceptable for dense financial tables. Mobile does not squeeze columns into 320px.
- **Actions:** `.table__actions` flex, icon-btn or dropdown. Empty: `.table__empty` centered.
- Pagination region below table uses `pagination` component.

---

## 14. Badges — `components.css`

- Height 22px, pill, `0.72rem` uppercase, 600, dot optional.
- Variants: `neutral, primary, success, warning, danger, info` — soft bg + border + semantic text, not neon.

---

## 15. Alerts / Flash — `components.css` + `partials/flash.php`

- Flex, icon + content + dismiss, radius-md, border.
- Variants: success / warning / danger / info (soft bg + border + text)
- Flash partial escapes content, `role="alert"` / `status`, auto-dismiss for success via JS (6s), manual dismiss via `[data-alert-dismiss]`.
- Security: no raw secrets, `esc()` all.

---

## 16. Modals — `components.css` + `modal.js`

- Overlay: fixed inset, `rgba(15,23,42,0.48)` + blur, `display:grid` when open, padding 24.
- Dialog: 480px (sm 400, lg 640), white, border, radius-xl, shadow-lg, max-height 90vh.
- Parts: `modal__header` (title+desc+close), `modal__body` (scroll), `modal__footer` (subtle bg, right actions)
- Behavior: click overlay/close/Esc, body scroll lock, focus trap (Tab loop), `aria-modal`, returns focus to trigger.
- Confirmation pattern demo uses `btn--danger` without excessive red.

---

## 17. Dropdowns — `components.css` + `dropdown.js`

- `dropdown` wrapper, `dropdown__menu` absolute top+6, min 200px, white, border, radius-lg, shadow-lg, hidden → `dropdown__menu--open` grid.
- Items: `dropdown__item` 9px 10px, hover subtle, danger variant.
- Behavior: single JS module for all dropdowns (account + row actions). Keyboard ArrowDown/Up, Escape, click outside, focus management, `aria-expanded`.

---

## 18. Tabs / Breadcrumbs / Pagination / Empty / Loading / Tooltip

- **Tabs:** flex, border-bottom, `tabs__tab` 10px 14px, active navy bottom border 2px. Keyboard: buttons, `aria-selected`.
- **Breadcrumbs:** flex 0.8rem muted, `/` separator, `aria-current="page"`.
- **Pagination:** flex between, list of 32px links, active navy, disabled 0.46, `aria-current="page"` + `aria-disabled`.
- **Empty:** centered 12/16 padding, 48px icon subtle bg, title 1rem semibold, desc 0.88rem muted + optional action.
- **Loading:** `btn--loading` spinner via `::after`, `skeleton` shimmer gradient (400% size, `shimmer` 1.4s). Respects `prefers-reduced-motion`.
- **Tooltip:** `[data-tooltip]` pseudo `::after`, bottom-center, dark bg, 0.75rem, fades in on hover/focus.

---

## 19. Responsive Behavior

- **1440/1280/1024:** Grid shell, sidebar 264px, content 1280 max, comfortable padding.
- **768:** Shell collapses to single column, sidebar drawer overlay.
- **390/360:** Topbar padding 16, main padding 16, page-header stacks, tables scroll horizontally, modals full-width with padding.

Verified: no horizontal scroll at 320px, long labels truncate/ellipsis, validation messages wrap.

---

## 20. Accessibility

- Color contrast: navy (#0F2A3D on white 15.8:1), muted text #64748B on white 4.6:1, badge text meets 4.5:1.
- Focus: `focus-visible` ring (`0 0 0 3px rgba(15,42,61,0.18)`) + outline 2px; never removed without replacement. Sidebar links have white ring on dark.
- Forms: `label for`, `aria-describedby` for hints, error text near field, `aria-label` for icon buttons.
- Modals: `role="dialog"`, `aria-modal`, `aria-labelledby`, focus trap, Escape, return focus.
- Dropdowns: `aria-haspopup="menu"`, `aria-expanded`, `role="menu"` / `menuitem`, keyboard arrows.
- Tables: semantic `th` scope, `thead` sticky.
- Touch: buttons 30–36px, icon-btn 36px.
- Skip link, `prefers-reduced-motion` honored, `aria-live="polite"` for flash.

---

## 21. CSS Architecture

```
public/assets/admin/css/
  tokens.css      — custom properties
  base.css        — reset, typography, focus, scrollbar
  layout.css      — shell, sidebar, topbar, main, auth
  components.css  — buttons, forms, cards, tables, badges, alerts, modal, dropdown, tabs, pagination, empty, skeleton, tooltip
  utilities.css   — helpers (stack, flex, truncate, etc.)
```

No Sass, no build step, shared-hosting friendly. Versioned via `?v=4.0`.

---

## 22. JS Architecture

```
public/assets/admin/js/
  navigation.js  — collapse (localStorage), drawer (overlay, Escape, resize)
  dropdown.js     — reusable, keyboard, click outside
  modal.js        — overlay, focus trap, scroll lock
  admin.js        — flash dismiss/auto-dismiss, password toggle, double-submit guard
```

Vanilla, `addEventListener` only, `defer`, no frameworks. Small (< 10KB total).

---

## 23. View Architecture

```
app/Views/admin/
  layouts/
    app.php       — shell + topbar + sidebar + flash + content section
    auth.php      — centered auth card
  partials/
    sidebar.php   — nav groups, active from uri_string(), disabled Soon
    topbar.php    — triggers + account dropdown (POST logout)
    flash.php     — success/error with esc + role
    page_header.php — title/desc/actions (reusable)
  dashboard/index.php — extends app, minimal content (keeps test strings)
  profile/index.php   — extends app, card + password form with toggle
  ui/preview.php      — development preview (static demo, no DB)
```

Partials escape by default, no queries in views.

---

## 24. UI Preview

- **Route:** `GET /admin/ui-preview` → `UiPreviewController::index` → `admin/ui/preview`
- **Restriction:** `ENVIRONMENT !== 'production'` else 404; `adminAuth` filter ensures authenticated. Unauthenticated 302 to login. Production 404 even if authenticated.
- **Demonstrates:** Typography, colors, buttons, icon buttons, inputs/textarea/select/checkbox/radio/toggle, cards, badges, alerts, table + pagination + empty, tabs/breadcrumbs, modal, dropdown, loading/skeleton. Uses `UI DEMO` labels, demo package `Demo Package`, `FTP-2026-000001` — clearly marked, no DB insert.

---

## 25. Security Regression

Auth shell does not weaken Phase 3: POST logout + CSRF, AdminAuth/AdminGuest, throttling, hashing, active-admin check, audit logging, no-store headers remain via `AdminAuth` filter after().

---

## 26. Quality Notes

- No emoji icons, no random hex, no excessive borders, no inline event handlers, no inline CSS for final components (demo preview uses minimal inline grid for layout brevity).
- Tables remain semantic div→table, not div grids.
- Mobile sidebar traps focus, prevents body scroll.

---

*Phase 4 constitution for Phase 5+ work — all future admin screens extend `admin/layouts/app` and use tokens/components.*

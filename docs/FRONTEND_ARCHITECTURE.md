# FTPRENEUR — Frontend Architecture

> **Approach:** Server-rendered CodeIgniter Views → semantic HTML, modern CSS, vanilla JS. No React/Vue/SPA.  
> **Target:** Lighthouse mobile Performance ≥90, Accessibility ≥95, SEO ≥95 — without sacrificing the premium identity.

---

## 1. Page Architecture

### Public Pages

| Route | View | Dynamic? | Notes |
|-------|------|----------|-------|
| `/` | `home/index.php` | **Hybrid** | 14 static sections + 1 dynamic packages loop. Single meaningful H1. |
| `/checkout/{slug}` | `checkout/index.php` | Dynamic (package) | 3-field form + order summary. No-index. |
| `/payment/success/{order_number}` | `payment/success.php` | Dynamic (order) | Verification-gated. No-index, no-cache. |
| `/payment/failed/{order_number}` | `payment/failed.php` | Dynamic | Retry CTA. No-index. |
| `/privacy-policy` | `legal/privacy.php` | Static | SEO indexable |
| `/terms` | `legal/terms.php` | Static | SEO indexable |
| `/refund-policy` | `legal/refund.php` | Static | SEO indexable |
| `/disclaimer` | `legal/disclaimer.php` | Static | SEO indexable |

### Admin Pages

See `ADMIN_ARCHITECTURE.md` — shell `layouts/admin.php`.

### Error Pages

`/404`, `/403`, `/500` — branded views in `Views/errors/html/` — no stack trace, with CTA to `/`.

---

## 2. Landing Section Structure (Static vs Dynamic)

**V1 boundary:** Only **Programs / Packages** is DB-driven. Every other section is static in the codebase — no CMS table, no admin editor.

```
┌─ layouts/public.php (header, nav, footer, OG, JSON-LD, asset links)
│
├─ home/index.php composes:
│   01 Navigation               static — logo, links (#about #approach #programs #faq), CTA
│   02 Hero                     static — H1 "Visphy Kharradi's Nutrition, Strength Training And Disease Management Plan", sub, CTA → #programs, trust line
│   03 Trust/Credibility        static — years, clients helped (only if verified — no fake numbers)
│   04 About Visphy Kharradi    static — photo, bio (from verified content)
│   05 Who This Is For          static — audience bullets
│   06 Areas Ftpreneur Helps    static — condition/lifestyle areas (no cure claims)
│   07 Approach / Philosophy    static — pillars (nutrition, movement, lifestyle)
│   08 How It Works             static — 3–4 step flow
│   09 Programs / Packages  ──► DYNAMIC — loop packages WHERE is_active=1 ORDER BY display_order
│   10 Why Ftpreneur            static — differentiators
│   11 Benefits / Outcomes      static — outcomes (guidance/support language)
│   12 Testimonials             static — only verified testimonials; no synthetic ratings schema
│   13 FAQ                      static — accordion, FAQ schema where truthful
│   14 Final CTA                static — repeats primary CTA → #programs
│   └─ Footer                   static — links (legal), contact, disclaimer snippet, copyright
```

**Why static:** No need to CRUD a heading. Code changes are versioned, fast, and do not require a CMS editor. Adding a CMS later is additive (one `sections` table) — not blocking.

**Dynamic loop example:**

```php
<?php foreach ($packages as $pkg): ?>
  <article class="package-card <?= $pkg['is_featured'] ? 'featured' : '' ?>">
    <?php if ($pkg['badge']): ?><span class="badge"><?= esc($pkg['badge']) ?></span><?php endif; ?>
    <h3><?= esc($pkg['name']) ?></h3>
    <p><?= esc($pkg['short_description']) ?></p>
    <p class="price">
      <?php if ($pkg['regular_price'] > $pkg['selling_price']): ?>
        <s>₹<?= number_format($pkg['regular_price'], 0) ?></s>
      <?php endif; ?>
      <strong>₹<?= number_format($pkg['selling_price'], 0) ?></strong>
    </p>
    <ul>
      <?php foreach ($pkg['features'] as $f): ?><li><?= esc($f['feature_text']) ?></li><?php endforeach; ?>
    </ul>
    <a href="<?= site_url('checkout/'.$pkg['slug']) ?>" class="btn"><?= esc($pkg['cta_label']) ?></a>
  </article>
<?php endforeach; ?>
```

---

## 3. Asset Organization

```
public/assets/
├── css/
│   ├── app.css        # source (or app.min.css committed for prod)
│   └── admin.css      # admin shell — separate bundle
├── js/
│   ├── app.js         # vanilla — nav, FAQ, checkout, Razorpay trigger
│   └── admin.js       # admin — reorder, toggles (vanilla)
├── images/
│   ├── hero/          # hero portrait, fallback jpg + webp
│   ├── about/
│   ├── icons/         # inline SVG preferred over icon font
│   └── favicon/       # ico, 180x180, 192, 512, svg
└── fonts/
    ├── serif.woff2    # editorial serif (e.g. Canela / Fraunces / Playfair tier)
    └── sans.woff2     # modern sans (e.g. Inter / General Sans)
```

**No `node_modules` in prod.** If a local build step (e.g. `postcss`, `esbuild`) is used, the **output** `app.min.*` is what deploys. A contributor must be able to run the site with just PHP + MySQL.

**Versioning:** `app.min.css?v=20260923` via `Config\App::$assetVersion` or filemtime query string — `.htaccess` caches assets immutably; HTML is no-cache.

---

## 4. CSS Strategy

- **Modern CSS, no framework dependency** — custom properties, logical properties, `clamp()`, grid, flex.
- **Design tokens (indicative — not locked):**

```css
:root {
  --navy: #0a1a3a; --navy-700: #12265a;
  --sapphire: #2a5ad6; --gold: #c9a86a; --gold-soft: #e8d9b8;
  --ivory: #fdfbf7; --stone: #e9e6e1; --ink: #0f0f0f;
  --radius: 16px; --shadow: 0 12px 40px rgba(10,26,58,.12);
  --serif: "Editorial Serif", ui-serif, Georgia, serif;
  --sans: "Modern Sans", ui-sans, system-ui, -apple-system, sans-serif;
}
```

- **Responsive:** Mobile-first — `320px → 768px → 1024px → 1280px`. Navigation collapses to hamburger < 1024px.
- **Accessibility:** Focus ring `outline: 2px solid var(--sapphire); outline-offset: 2px` always visible. Color contrast AA (navy on ivory 14:1). No `outline: none` without replacement.
- **Reduced motion:**

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
```

---

## 5. JavaScript Strategy (Vanilla Only)

**Budget:** < 30 KB minified (excluding Razorpay CDN).

| Module | Behavior |
|--------|----------|
| `nav` | Hamburger toggle, focus trap, close on Esc/click outside, body scroll lock |
| `faq` | `<details>/<summary>` or button + `aria-expanded`, keyboard (Enter/Space) — not div soup |
| `checkout` | Client validation mirrors server (name/email/phone), shows inline errors, disables Pay until valid, intercepts submit → `fetch /payment/create-order` → open Razorpay |
| `razorpay` | Thin wrapper around `Razorpay(options)` — no business logic beyond handler → `/payment/verify` |
| `admin-reorder` | Drag handle → POST ordered ids (vanilla `Sortable` mini-impl or native DnD — no heavy lib) |

**Rules:**

- Use `defer` for `app.js` — ` <script src="/assets/js/app.min.js" defer></script>`.
- No inline `onclick` with user data — use `addEventListener`.
- All `fetch` include `X-CSRF-TOKEN` header when POST.
- Razorpay CDN `https://checkout.razorpay.com/v1/checkout.js` loaded with `async` — checkout button disabled until loaded, with fallback message if CDN blocked.

---

## 6. Image & Font Strategy

- **Images:** Provide `webp` + `jpg` fallback via `<picture>`. Hero portrait preloaded (`<link rel="preload" as="image">`), below-fold `loading="lazy"` + `decoding="async"`. Sizes: responsive `srcset` (480, 768, 1024, 1440w). Optimize via Squoosh/Sharp locally before commit.
- **Fonts:** `woff2` self-hosted, `font-display: swap`, subset latin only. Preload serif for hero if above fold: `<link rel="preload" href="/assets/fonts/serif.woff2" as="font" type="font/woff2" crossorigin>`. Limit to 2 families, 3 weights max.
- **Icons:** Inline SVG sprite — no icon font, no extra request.

---

## 7. Accessibility (WCAG 2.2 AA Target)

- Semantic: `<header> <nav> <main> <section aria-labelledby> <footer>`, one `h1` per page, heading hierarchy not skipped.
- Navigation: keyboard operable, skip link `<a href="#main">Skip to content</a>`, visible focus, `aria-current="page"` for active link.
- Forms: `<label for="customer_name">Full Name</label>` + `required` + `aria-describedby` for errors + `autocomplete` (`name`, `email`, `tel`). Errors announced via `role="alert"`.
- Packages: `<article>` with heading, not div.
- FAQ: `<button aria-expanded>` + controlled region — not clickable div.
- Razorpay: overlay is third-party — we control only trigger button's accessible name (`aria-label`).
- Contrast: test with axe/WAVE in later phase.
- Touch targets: min 44×44 px; CTA buttons 48px.
- `alt` text: meaningful for informative images, `alt=""` for decorative.

---

## 8. SEO Foundation

| Item | V1 |
|------|----|
| **Titles** | Unique per page: `Ftpreneur — {Package/Section} | Visphy Kharradi` — 50–60 chars. From `View Data` + `settings` fallback. |
| **Meta description** | 140–160 chars, per page — management by view data, not CMS. |
| **Canonical** | `<link rel="canonical" href="https://ftpreneur.com/...">` via `site_url()` + `baseURL` env — no duplicate `/index.php/`. |
| **Robots** | `robots.txt` allows `/`, disallows `/admin/`, `/checkout/`, `/payment/`. `/` index, checkout/success noindex. |
| **Sitemap** | Static `sitemap.xml` listing `/`, `/checkout/{each active slug}`, legal pages. Or `SitemapController` generating from DB + cache (later). |
| **Open Graph** | `og:title`, `og:description`, `og:image` (1200×630), `og:url`, `og:type=website`. Twitter `summary_large_image`. |
| **Structured data** | `Organization` + `Person` (Visphy Kharradi) JSON-LD — **no fake aggregateRating/review schema** without verified data. FAQPage schema only if FAQ is truthful. |
| **Clean URLs** | No `index.php` in URL (`.htaccess` rewrite). Slug-based checkout, not `?id=1`. |
| **Hreflang** | Not needed V1 (single locale). |
| **Alt / headings** | Enforced in build — no empty `alt` on content images. |

**Example head:**

```html
<title>Visphy Kharradi — Nutrition, Strength And Disease Management | Ftpreneur</title>
<meta name="description" content="Personalised nutrition, movement and lifestyle guidance from Visphy Kharradi to manage health conditions and improve well-being.">
<link rel="canonical" href="https://ftpreneur.com/">
<meta property="og:title" content="Ftpreneur — Visphy Kharradi">
<meta property="og:image" content="https://ftpreneur.com/assets/images/og/home.jpg">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Person","name":"Visphy Kharradi","url":"https://ftpreneur.com/"}</script>
```

---

## 9. Performance

| Tactic | How |
|--------|-----|
| **Critical CSS** | Inline above-fold (nav + hero + package card skeleton) ≈ 8–12 KB; rest `app.min.css` |
| **Defer JS** | `app.min.js` `defer`; Razorpay `async` with load guard |
| **Preload** | Hero image, serif woff2 (if above fold), OG image not preloaded |
| **Lazy** | All below-fold sections, testimonial photos, package images |
| **Cache** | `.htaccess` `ExpiresByType text/css A31536000` etc. for assets; HTML `no-cache` |
| **DB** | Home: 2 queries (packages + features eager) — no N+1. Checkout: 1 package lookup. |
| **Third-party** | Only Razorpay + Google Fonts (self-host preferred) — no analytics heavier than GA4 (if ever) |
| **Font** | Subset, woff2, swap |
| **No SPA** | No hydration cost, no bundle, no virtual DOM |

---

## 10. Animation (Restraint)

- Subtle: fade-up on scroll for credibility/packages (`opacity + translateY 12px`, 160 ms) only if `prefers-reduced-motion: no-preference`.
- No parallax that breaks on mobile, no scroll-jacking.
- Use `IntersectionObserver` once; disconnect after.
- Respect `prefers-reduced-motion: reduce` — all keyframes disabled.
- Performance: animate `transform`/`opacity` only — never `top`/`left`.

---

## 11. Legal / Disclaimer Views

- Each legal page uses `layouts/public.php` but with `article` typography.
- Content is static — reviewed by operator, not admin-editable in V1 (prevents accidental legal change without review).
- Route: `/privacy-policy`, `/terms`, `/refund-policy`, `/disclaimer` — canonical, indexed.

---

## 12. Error States (Frontend)

- Network fail during `create-order` → inline banner + Retry (no redirect).
- Razorpay CDN fail → message "Payment system unavailable — please check connection and retry" + fallback link contact.
- Verify fail → `payment/failed` view (not alert) with order_number + support CTA.
- Empty packages (no active) → section shows "New programs coming soon — contact on WhatsApp" with wa.me link.


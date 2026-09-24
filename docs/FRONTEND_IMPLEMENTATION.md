# Ftpreneur — Production Frontend Architecture & Design System

**Phase:** Phase 6A (Foundation & Design System)  
**Status:** ACTIVE  
**Visual Source of Truth:** Locked Brand Guidelines (`docs/brand-guidelines/`, `docs/BRAND_GUIDELINES_SEAL.md`)  

---

### 1. Architectural Principles & Guidelines Relationship

1. **Visual Source of Truth:** All visual design decisions (colors, typography, radii, spacing, buttons, card primitives, motion) are derived directly from the approved and sealed Brand Guidelines.
2. **Reference vs Production Code:** `docs/brand-guidelines/` contains reference documentation. Guideline HTML is **never** copied directly into production views or used as a drop-in template.
3. **No Heavy Frontend Frameworks:** Native server-rendered CodeIgniter 4 views with vanilla HTML5, custom CSS (design tokens), and lightweight vanilla JavaScript. No React, Next.js, Vue, Angular, or Node production runtimes.
4. **Separation of Admin & Public Assets:** Public frontend assets (`public/assets/frontend/`) and views (`app/Views/frontend/`) are strictly isolated from Admin assets (`public/assets/admin/`) and views (`app/Views/admin/`).

---

### 2. View Directory Structure

```
app/Views/frontend/
├── layouts/
│   └── main.php         # Primary HTML5 shell, SEO slots, accessibility landmarks, asset includes
├── partials/
│   ├── head.php         # Charset, viewport, SEO & OpenGraph meta tags, Google Fonts, production CSS
│   └── scripts.php      # Production JavaScript includes
├── components/          # Reusable UI component views for future phases
├── pages/
│   └── home.php         # Foundation page view
```

---

### 3. Public Asset Structure

```
public/assets/frontend/
├── css/
│   ├── tokens.css       # Design tokens (colors, typography scales, spacing, radii, elevation, motion)
│   ├── base.css         # Modern reset, normalize, body defaults, base HTML elements, focus styles
│   ├── layout.css       # Container widths, responsive grid/flex layout structures, section padding
│   ├── components.css   # Generic primitives (buttons, typography, surfaces/cards, badges, media)
│   └── utilities.css    # Utility classes (.sr-only, spacing helpers, text alignments)
├── js/
│   └── main.js          # DOM-ready init, reduced-motion detection, progressive enhancement marker
├── images/              # Optimized public brand images
└── icons/               # SVG icon assets
```

---

### 4. Design Tokens & Primitives

- **Primary Color (Ultramarine):** `#2E47FF` (Hover: `#1A34E6`, Light: `#EEF2FF`)
- **Secondary Color (Mint):** `#00B79B` (Hover: `#009E86`, Light: `#E6F8F5`)
- **Accent Color (Marigold):** `#FF9E2C` (Hover: `#E88B1A`, Light: `#FFF6EB`)
- **Ink / Neutral Dark:** `#141B31`
- **Canvas / Background:** `#F8FAFC`
- **Typography:** Display: `'Outfit', sans-serif` | Body: `'Inter', sans-serif`
- **Button Primitives:** `.btn--primary`, `.btn--secondary`, `.btn--accent`, `.btn--outline`, `.btn--subtle`, `.btn--text`
- **Card Primitives:** `.card`, `.card--interactive`, `.card--featured`, `.card--dark`
- **Badge Primitives:** `.badge--primary`, `.badge--secondary`, `.badge--accent`, `.badge--subdued`, `.badge--dark`

---

### 5. Accessibility & Responsive Philosophy

- **Mobile-First Responsive Design:** Fluid typography and spacing using `clamp()`, `min()`, `max()`, CSS Grid, and Flexbox spanning 320px to 1440px+. Zero horizontal overflow.
- **Accessibility (WCAG 2.1 AA):**
  - Visible keyboard focus indicators (`:focus-visible` with 3px focus ring).
  - High contrast ratio text on all backgrounds.
  - Skip-to-main-content link (`.skip-to-content`).
  - Native semantic HTML5 landmarks (`<main>`, `<section>`, `<h1>`-`<h6>`).
  - Screen-reader utility (`.sr-only`).
  - Reduced-motion support (`prefers-reduced-motion: reduce` disables CSS transitions and smooth scroll).

---

### 6. Development Design System Preview

- **Route:** `/admin/frontend-preview`
- **Access Control:** Authenticated admin required (`adminAuth` filter).
- **Environment Control:** Restricted to `development` environment (`ENVIRONMENT === 'development'`). Returns HTTP 404 in production environment.
- **Function:** Renders production design tokens and component primitives live using the actual `public/assets/frontend/css/` files for visual verification without using the guideline documentation iframe.

---

### 7. Phase 6 Section Roadmap (Upcoming Phases)

- **Phase 6B:** Navigation Bar + Hero Section
- **Phase 6C:** Trust / Credibility Bar + About Visphy Kharradi Section
- **Phase 6D:** Problems / Target Audience + Conditions Helped
- **Phase 6E:** Approach + How It Works Step-by-Step
- **Phase 6F:** Dynamic Programs & Package Cards (pulling from `PackageModel`)
- **Phase 6G:** Why Ftpreneur + Core Program Benefits
- **Phase 6H:** Testimonials Carousel + Frequently Asked Questions (FAQ)
- **Phase 6I:** Final Call-to-Action (CTA) + Production Footer
- **Phase 6J:** Responsive, Motion, Accessibility, SEO & Performance Audit
- **Phase 6K:** Public Frontend Verification & Seal

# Ftpreneur Brand Guidelines — Formal Seal & Lock

**Seal Date:** 2026-09-24  
**Approval Status:** APPROVED, LOCKED, SOURCE OF TRUTH  
**Project:** Ftpreneur (Visphy Kharradi)  

---

### Declaration

```
FTPRENEUR BRAND GUIDELINES — APPROVED & LOCKED
```

The Ftpreneur Brand Guidelines have been visually reviewed by the project owner and formally APPROVED. The visual system defined herein is locked as the authoritative reference for all future frontend and application design work.

---

### Source of Truth Files

The authoritative Brand Guidelines source files are maintained under:

- `docs/brand-guidelines/index.html` — Approved visual guidelines page
- `docs/brand-guidelines/README.md` — Overview and architectural context
- `docs/brand-guidelines/css/styles.css` — Approved brand stylesheet
- `docs/brand-guidelines/js/main.js` — Approved brand interactive script
- `docs/brand-guidelines/assets/` — Approved visual asset assets
- `docs/BRAND_GUIDELINES.md` — Repository reference documentation

---

### Admin Preview Architecture & Security

- **Admin Preview Route:** `/admin/brand-guidelines` (Authenticated)
- **Authentication Protection:** Protected by `adminAuth` filter + in-controller `AdminAuthService` session verification. Unauthenticated access redirects to `/admin/login`.
- **Isolation Architecture:** Guidelines are rendered inside an isolated iframe view (`/admin/brand-guidelines/frame`) with dedicated whitelisted endpoints for CSS (`/admin/brand-guidelines/css/styles.css`) and JS (`/admin/brand-guidelines/js/main.js`).
- **Security Guardrails:**
  - Zero path parameter input — path traversal is impossible.
  - Hardcoded whitelisted file paths relative to `ROOTPATH`.
  - Brand CSS does not leak into Admin CSS; Admin CSS does not alter the guideline preview.
  - Header controls (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Cache-Control: no-store`) enforced.

---

### Approved Visual System Scope

Future frontend implementations (Phase 6+) must derive their visual system from these guidelines across all defined design tokens and components:

1. **Color System** (Primary, Secondary, Accent, Neutrals, Functional feedback)
2. **Typography** (Font family, scale, weights, line heights)
3. **Spacing & Layout** (Grid, container widths, paddings, margins)
4. **UI Components** (Buttons, form fields, cards, badges, modal overlays, navigation bars)
5. **Imagery & Icons** (Border-radius, shadows, aspect ratios, icon styling)
6. **Motion & Interaction** (Transitions, hover states, micro-animations)

---

### Relationship to Phase 6 & Implementation Guardrails

1. **Reference Only:** The guideline HTML is documentation and reference, NOT a drop-in production template.
2. **Adaptation Permitted:** Phase 6 implementation may adapt HTML markup for CodeIgniter 4 view architecture, semantic HTML5 structure, accessibility (WCAG 2.1 AA), SEO optimization, and performance.
3. **Approval Required for Visual Changes:** Any material deviation from the approved colors, typography, or component visual hierarchy requires explicit project owner approval.

---

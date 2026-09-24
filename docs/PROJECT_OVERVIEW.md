# FTPRENEUR — Project Overview

> **Owner:** Visphy Kharradi  
> **Positioning:** *Visphy Kharradi's Nutrition, Strength Training And Disease Management Plan*  
> **Phase:** 0 — Architecture & Project Constitution  
> **Status:** Documentation Only — No Implementation  
> **Last Updated:** 2026-09-23

---

## 1. Product Definition

**FTPRENEUR** is a conversion-focused personal service platform for **Visphy Kharradi**. It is not a marketplace, not a social network, and not a content CMS. It is a single-operator, high-trust, **server-rendered marketing + transaction site** whose sole commercial function in V1 is to let a visitor:

> learn → trust → choose a package → pay once via Razorpay → onboard via WhatsApp / Google Form.

There are **no customer accounts, no subscriptions, no recurring billing** in V1. The relationship after payment is deliberately human (WhatsApp + Form), not automated.

### Core Promise (V1 copy direction)

> *Get personalised nutrition, movement and lifestyle guidance directly from Visphy Kharradi to better manage your health conditions, disease management and improve your overall well-being.*

**Guardrail:** This promise is about *guidance, support, and management* — never cure, reversal guarantee, or medical outcome guarantees. See §7.

---

## 2. Target User

### Primary Persona — The Health-Seeking Adult (28–55)

- Living with a lifestyle-linked condition (weight, metabolic health, thyroid, PCOS/PCOD, diabetes risk, hypertension, low energy) **or** seeking structured fat-loss / strength improvement.
- Time-constrained, has tried generic diets/workouts, values a single trusted expert over an app.
- Comfortable paying online via UPI / Cards / NetBanking (Razorpay), comfortable continuing on WhatsApp.
- In India, mobile-first (70–80% traffic expected on mobile).

### Secondary Persona — The Gift Buyer / Family Decision Maker

- Partner/parent researching on behalf of someone else. Needs clarity, credibility, and low-friction checkout (only 3 fields).

### Non-Persona in V1

- Users expecting a self-serve dashboard, app, or instant digital delivery. Explicitly out of scope.

---

## 3. Business Flow (Happy Path)

```
Visitor lands (/) 
  → scrolls static credibility + approach sections
  → reaches dynamic Programs / Packages (DB-driven)
  → clicks CTA on a package (e.g. /checkout/90-day-transformation)
  → checkout: Name + Mobile + Email (3 fields)
  → clicks Pay → server creates Order + Razorpay Order
  → Razorpay Checkout overlay
  → payment completed → browser posts razorpay_* to server
  → server verifies signature (authoritative)
  → on success: /payment/success/{order_number} (verified only)
  → success page shows: name, package, order number, next steps
  → buttons: [Complete Assessment (Google Form per package)] [Continue on WhatsApp (pre-filled per-package message)]
```

**Failure branches** are first-class (see Payment Flow doc): cancelled, failed, network drop, refresh, duplicate callback, webhook reconciliation.

---

## 4. V1 Scope — Included

### Public Website

- Responsive, server-rendered landing page (CodeIgniter 4 + Views)
- Static marketing sections (hardcoded in views — not CMS)
- **Dynamic** packages section (DB-driven)
- Package detail → checkout (slug-based)
- Minimal checkout (Full Name, Mobile, Email)
- Razorpay **one-time** payment (server-side price authority + signature verification)
- Payment success / failure pages (verification-gated)
- WhatsApp + Google Form handoff (per-package configurable)
- Legal pages: Privacy Policy, Terms, Refund Policy, Disclaimer (route architecture + view shells)
- SEO foundation, performance baseline, accessibility baseline

### Admin

- Secure authentication (single role: admin)
- Dashboard (real metrics only — no fake analytics)
- Package CRUD + features + ordering + badges + pricing + duration + active/featured flags
- Google Form URL per package (validated URL)
- WhatsApp message/template per package (escaped generation)
- Orders list/detail (immutable snapshots)
- Payments list/detail + status
- Settings (key-value, typed)
- Admin profile / password change
- Activity / audit logs

---

## 5. V1 Scope — Explicitly Excluded

> If it is on this list, do **not** architect a table, route, or UI for it in V1. Future possibility ≠ V1 schema.

- Customer accounts / login / customer dashboard
- Membership / recurring subscriptions / Razorpay Subscriptions
- Coupons / discount engine (discount columns exist only for snapshot compatibility — no engine)
- Automated invoice / GST engine (snapshot totals only)
- Automated refund engine
- Full CMS / page builder (only `packages` + `package_features` are dynamic)
- Blog CMS
- CRM / ticketing
- Mobile app
- Automated WhatsApp API (only `wa.me` link generation)
- Advanced analytics dashboard / charting library
- React / Next.js / Vue / SPA / Node production runtime

---

## 6. Terminology (Ubiquitous Language)

| Term | Meaning | Notes |
|------|---------|-------|
| **Package** | A purchasable program (e.g. "90-Day Disease Management Plan") | DB-driven. Has price, duration, features, badge, Form, WhatsApp template |
| **Feature** | A bullet within a package | Normalized table `package_features`, ordered |
| **Order** | Local record of intent to purchase | Created *before* Razorpay order. Has public `order_number` like `FTP-2026-000001` |
| **Payment** | Local record of a Razorpay payment attempt | 1 order may have N payment attempts (retry). Only 1 can be `captured` |
| **Razorpay Order** | Object created via Razorpay API (`order_id` like `order_xxx`) | Server-only creation |
| **Verification** | Server-side HMAC-SHA256 signature check | Only after this does `orders.status = paid` |
| **Onboarding handoff** | Redirect to Google Form + WhatsApp after verified success | Not proof of payment |
| **Admin** | Sole V1 role | No RBAC matrix in V1 — single role, but schema allows future |
| **Snapshot fields** | `order.package_name_snapshot`, `package_price_snapshot` | Preserves history if package later edited |
| **Active / Featured** | Package flags | `is_active` = purchasable; `is_featured` = visual emphasis |
| **Slug** | URL-safe package identifier | Unique, used in `/checkout/{slug}` |

---

## 7. Health / Wellness Content Guardrails

FTPRENEUR operates in a sensitive domain (nutrition, strength, lifestyle, disease management).

**Architectural requirement:** Documentation and future copy must:

- Frame services as **guidance, support, management, personalised plans, lifestyle improvement**.
- Never promise *cure*, *guaranteed reversal*, *guaranteed medical result*, or invent credentials.
- Include a **Disclaimer** route and view from day one.
- Avoid collecting health-condition details at checkout (only post-payment via Form, owned by operator).
- Display testimonials/results only if sourced from verified project content — no synthetic schema.

This is enforced in `PROJECT_CONSTITUTION.md` and `SECURITY_ARCHITECTURE.md` (PII minimality).

---

## 8. Success Criteria for V1

1. Visitor can complete package → checkout (3 fields) → Razorpay → verified success → WhatsApp/Form handoff in < 90 seconds on 4G mobile.
2. Admin can create/edit/reorder/disable a package without developer intervention; change reflects on landing page immediately (or within cache TTL).
3. No price can be altered from browser; all payments verified server-side; duplicate callbacks are idempotent.
4. Audit log proves who changed what package/setting.
5. Site works on conventional shared hosting (`public/` as document root, no Node process, no build-step required to serve).
6. Lighthouse mobile Performance ≥ 90, Accessibility ≥ 95, SEO ≥ 95 (after design phase).

---

## 9. Out of Scope for Phase 0

Phase 0 delivers **documentation + constitution only**. No landing page, no admin UI, no Razorpay integration code, no migrations executed, no seed data beyond structure. See `DEVELOPMENT_PHASES.md`.

---

## 10. References

- `ARCHITECTURE.md` — system & CodeIgniter boundaries
- `DATABASE_SCHEMA.md` — normalized schema
- `PAYMENT_FLOW.md` — checkout & Razorpay security
- `SECURITY_ARCHITECTURE.md` — threat model
- `ADMIN_ARCHITECTURE.md` — admin modules
- `FRONTEND_ARCHITECTURE.md` — static vs dynamic boundary
- `PROJECT_CONSTITUTION.md` — non-negotiable rules

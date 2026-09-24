# FTPRENEUR — Development Phases

> **Approach:** Small auditable phases. Each phase has objective, scope, security, verification, and **explicit exit criteria**. No phase starts until the prior phase's exit criteria are met.  
> **Phase 0 is this document. Do not start Phase 1 until approval.**

---

## Phase 0 — Architecture, Constitution & Plan

**Status: COMPLETE (this submission)**

| Item | Detail |
|------|--------|
| **Objective** | Lock architecture, schema, payment flow, security, roadmap — before any code. |
| **Scope** | `docs/` (9 files) + `README.md` + `PROJECT_CONSTITUTION.md`. No app code. |
| **Files** | See docs index in README. |
| **Security** | Threat model + payment invariants documented. |
| **Verification** | Docs cross-reviewed for hosting/payment/schema/consistency. |
| **Exit criteria** | All docs present, internally consistent, Phase 0 Verdict = PASS, approval to proceed. |

---

## Phase 1 — CodeIgniter Foundation, Config & Environment

**Objective:** Reproducible CI4 project that boots on shared hosting and locally, with correct env isolation.

**Scope:**

- `composer create-project codeigniter4/appstarter` (latest stable PHP 8.1+ compatible).
- `.env.example`, `.gitignore` (exclude `.env`, `writable/logs/*`, `writable/cache/*`).
- `app/Config/App.php` (baseURL, session, CSRF, cookie hardening), `Database.php`, `Filters.php` (SecurityHeaders skeleton), `Routes.php` (placeholders).
- `public/.htaccess` (rewrite, -Indexes, security headers fallback), `public/robots.txt` placeholder.
- `composer require razorpay/razorpay` (installed but not used yet).
- `README` local setup section verified: `composer install && cp .env.example .env && php spark serve` boots.

**Security considerations:**

- CSRF enabled, session HttpOnly/Secure/SameSite set, toolbar only in development, `.env` never committed.

**Tests / Verification:**

- `php spark serve` → `/` returns CI4 welcome (replaced later) without error on PHP 8.1.
- `CI_ENVIRONMENT=production` hides toolbar and detailed errors.
- `composer audit` clean.

**Exit criteria:**

- Fresh clone → `composer install` → boots locally and (dry-run) on staging host via FTP of `public/` + `app/`.
- No business code merged.

---

## Phase 2 — Database, Migrations & Core Models

**Objective:** Normalized, reproducible schema — no phpMyAdmin manual creation as primary.

**Scope:**

- Migrations: `admins`, `packages`, `package_features`, `orders`, `payments`, `admin_activity_logs`, `settings`, `order_sequences` (if Option B chosen) — per `DATABASE_SCHEMA.md` types, indexes, FKs, unique constraints.
- Models: `PackageModel`, `PackageFeatureModel`, `OrderModel`, `PaymentModel`, `AdminModel`, `AdminActivityLogModel`, `SettingModel` — with `$allowedFields`, `useTimestamps`, `useSoftDeletes` where specified.
- Seeder: `AdminSeeder` (reads initial admin from env), `SettingsSeeder` (default keys). No fake orders/payments.
- `php spark migrate` and `migrate:rollback` tested.

**Security considerations:**

- DECIMAL for money, prepared queries, `allowedFields` to prevent mass assignment.

**Tests / Verification:**

- `migrate` → `migrate:status` all green; `migrate:rollback` → `migrate` again green on fresh DB.
- Unique constraints enforced (duplicate slug, duplicate order_number rejected).
- **ZERO FKs** verified (`information_schema` `FOREIGN KEY COUNT = 0`); reference columns indexed.
- Seeders create admin with `password_verify` true.

**Exit criteria:**

- Schema on local matches spec; can be recreated from migrations on a clean DB without manual steps.
- No controllers/views beyond verification stub.

---

## Phase 3 — Admin Authentication & Security Foundation

**Objective:** Secure admin login that the rest of the admin can build on.

**Scope:**

- `Controllers/Admin/Auth.php` (login form, attempt, logout), `Views/admin/auth/login.php`.
- `Filters/AdminAuth.php` + `Filters/Throttle.php` + `Filters/SecurityHeaders.php` wired in `Config/Filters.php`.
- `Validation/AuthRules.php`, session config hardening (HttpOnly, Secure, SameSite, regen).
- `AdminActivityLogModel` logging: `login`, `logout`, `login_failed`.
- `Config/Security.php` + headers (CSP, X-Frame, nosniff, Referrer).
- Custom error views `Views/errors/html/404.php` etc. that leak no internals.

**Security considerations:**

- Throttle (5/15 min), generic login error, `password_hash`/`verify`, session regenerate, CSRF on login POST, HSTS only under HTTPS, audit log.

**Tests / Verification:**

- Login with correct creds → session admin_id set, redirected to /admin, `last_login_at` updated, audit logged.
- 5 bad attempts → 429. After window → can retry.
- Direct `GET /admin/packages` without session → 302 to /admin/login.
- `POST /admin/logout` without CSRF → 403.
- Security headers present in response.

**Exit criteria:**

- Auth is demonstrably throttle-protected, CSRF-protected, audit-logged, and gates all /admin/* routes.
- Security headers verified with `curl -I`.

---

## Phase 4 — Admin Design System & Layout

**Objective:** Premium custom admin shell — not a template clone — that every admin screen will inherit.

**Scope:**

- `Views/layouts/admin.php` (topbar, sidebar per ADMIN_ARCHITECTURE nav, content slot, flash, footer).
- `public/assets/css/admin.css` + `public/assets/js/admin.js` (vanilla, defer) — sidebar collapse, mobile hamburger, focus trap, accessible flash with `role="alert"`.
- Dashboard placeholder `Controllers/Admin/Dashboard.php` → `Views/admin/dashboard/index.php` (KPI cards reading real DB — zero state if no orders yet).
- Typography, color tokens (navy/sapphire/gold/ivory/stone), focus ring, 44px targets, prefers-reduced-motion.

**Security considerations:**

- Admin layout escapes all interpolated vars, CSP-compatible (no inline script), no sensitive data in JS.

**Tests / Verification:**

- Layout renders on `/admin` after login; sidebar highlights active section; mobile hamburger operable by keyboard + screen reader.
- Lighthouse Accessibility ≥ 95 on admin pages.
- No horizontal scroll at 320px.

**Exit criteria:**

- All future admin screens can extend `admin.php` and inherit nav/flash/CSRF meta — no layout rewrite needed.

---

## Phase 5 — Package Management

**Objective:** Admin can CRUD packages + features + ordering without developer.

**Scope:**

- `Controllers/Admin/Packages.php` (index, create, store, edit, update, toggleActive, reorder, destroy→archive logic).
- `Services/PackageService.php` (slug uniq, transaction package+features, reorder).
- `Views/admin/packages/{index,create,edit}.php`.
- `Validation/PackageRules.php` (all fields + google_form_url allowlist + whatsapp_template limit).
- `POST /admin/packages/reorder` (CSRF, transaction).

**Security considerations:**

- CSRF on all writes, `$allowedFields`, `esc()` on render, URL validation, audit log on every write (create/update/disable/reorder/archive), delete blocked if orders exist (archive instead).

**Tests / Verification:**

- Create package → appears on index ordered by display_order.
- Create with duplicate slug → validation error, no insert.
- Reorder via drag → reload preserves new order; audit logged.
- Edit package that has orders → slug change warns but blocked or allowed with audit (document choice).
- Disable → package disappears from Home (WHERE is_active=1).
- Delete package with orders → archived not deleted; features remain for history.
- XSS attempt in name/description → rendered escaped.

**Exit criteria:**

- Full lifecycle exercised; `packages` + `package_features` CRUD auditable and safe for historical orders.

---

## Phase 6 — Order & Payment Domain Foundation (No Razorpay Calls Yet)

**Objective:** Order/payment models, order number generation, snapshot discipline, status machine — without calling Razorpay.

**Scope:**

- `Services/OrderService.php` → `generateOrderNumber()` (sequence row lock), `createForPackage(slug, checkoutData)` (snapshot, pending), `markPaid()`, `markFailed()` — transactional.
- `Services/PaymentService.php` → `recordAttempt()`, `markCaptured()`, `markFailed()`, idempotency guard.
- `Controllers/Admin/Orders.php` (index, show), `Payments.php` (index, show) — read-only admin views.
- Validation `CheckoutRules.php` (name/email/phone/slug) — shared with later payment phase.

**Security considerations:**

- Snapshot immutability after `paid`, `order_number` uniqueness under concurrency (test with parallel inserts), status transitions only via services, no public write route yet.

**Tests / Verification:**

- Parallel `generateOrderNumber()` (10 concurrent via test script) → 10 unique `FTP-YYYY-xxxxxx` with no gaps/duplicates beyond sequence increments.
- Creating order copies `selling_price` to `package_price_snapshot` even if package price later edited → snapshot unchanged.
- `status` transition `pending → paid` works; `pending → failed` works; `paid → failed` blocked (service enforces).

**Exit criteria:**

- Order/payment domain can be instantiated without Razorpay and survives concurrency + snapshot + idempotency reasoning.

---

## Phase 7 — Public Frontend Design System

**Objective:** Visual language + asset pipeline + shell that the landing page will be built on.

**Scope:**

- `Views/layouts/public.php` (doctype, head, SEO, OG, fonts preload, header/nav/footer, asset links with `?v=`).
- `public/assets/css/app.css` (+ `app.min.css` if build step), `public/assets/js/app.js` (nav, FAQ vanilla, reduced-motion guard).
- `Config/App` asset version, `SitemapController` placeholder or static `sitemap.xml`, `robots.txt` final.
- Legal pages: `Views/legal/{privacy,terms,refund,disclaimer}.php` + `Controllers/Legal.php`.

**Security considerations:**

- CSP via filter (not yet final with Razorpay frame-src), escaping of site_name in title, `robots.txt` disallow /admin.

**Tests / Verification:**

- `/`, `/privacy-policy`, `/terms`, `/refund-policy`, `/disclaimer` all render, validate (W3C), Lighthouse Performance ≥90, Accessibility ≥95, SEO ≥95 (pre-package).
- `view-source` shows single H1, canonical, OG, escaped title.
- Assets cached (`Cache-Control` 1y), HTML no-cache.

**Exit criteria:**

- Public shell is production-ready and every new section can be added as an `include` without rework.

---

## Phase 8 — Landing Page Sections (Incremental)

**Objective:** Build the 14 static sections + dynamic packages loop — incrementally, not as a monolith.

**Scope splits (each auditable):**

- 8a: Navigation + Hero + Trust/Credibility
- 8b: About Visphy + Who This Is For + Areas Ftpreneur Helps
- 8c: Approach + How It Works + Why Ftpreneur + Benefits
- 8d: Programs/Packages (dynamic loop against `PackageService::getActiveOrdered()`) — the critical hybrid.
- 8e: Testimonials (only if verified) + FAQ (accordion + optional FAQPage schema) + Final CTA + Footer

Each sub-phase: implement view partial in `Views/home/sections/*.php`, include in `home/index.php`, add images (webp + lazy), anchor links (#programs etc.), responsive checks.

**Security considerations:**

- `esc()` on all package fields, `alt` on all images, no raw HTML from DB, no fake schema.

**Tests / Verification per sub-phase:**

- Visual diff on 375px/768px/1280px; keyboard nav through new section; axe scan passes.
- Packages sub-phase: deactivate package → disappears; reorder in admin → landing order updates; no N+1 query (check query log = 2 queries).

**Exit criteria for full Phase 8:**

- Single-scroll landing page complete and server-rendered — no JS required to see content; works with JS disabled (except Razorpay).

---

## Phase 9 — Checkout

**Objective:** Minimal, validated, server-authoritative checkout that hands to Razorpay.

**Scope:**

- `Controllers/Checkout.php` → `GET /checkout/{slug}` (fetch active package, render summary + 3-field form).
- `Views/checkout/index.php` — summary (name, price, duration, features), form (name/email/phone), CSRF, client validation mirrors server, submit → `fetch POST /payment/create-order`.
- `Controllers/Payment.php` → `POST /payment/create-order` (validate, create Order + placeholder Payment, create Razorpay Order via `RazorpayService`).
- `Services/RazorpayService.php` skeleton (config wiring, `createOrder`, `fetchPayment`, `verifySignature` — not yet live keys).

**Security considerations:**

- Slug validated, price never from browser, CSRF, throttle, server validation authoritative, Razorpay secret never in view.

**Tests / Verification:**

- Invalid phone → 422 with errors (server) + inline (client).
- POST without CSRF → 403.
- Price tamper attempt (modified amount in POST) → server ignores, charges DB price.
- Throttle (10/min/IP) → 429.

**Exit criteria:**

- Checkout works end-to-end up to the point of opening Razorpay overlay (mocked Razorpay response acceptable with test keys in local).

---

## Phase 10 — Razorpay Integration, Verification & Post-Purchase

**Objective:** Live money movement + verified success — the critical security path.

**Scope:**

- `RazorpayService` final: `createOrder`, `fetchPayment`, `verifySignature`, `verifyWebhookSignature` (HMAC raw body).
- `Controllers/Payment.php` → `POST /payment/verify` (verify + idempotency + optional fetchPayment amount match + transaction → paid), `GET /payment/success/{order_number}` (gated), `GET /payment/failed/{order_number}`, `POST /payment/webhook` (CSRF-exempt, HMAC, idempotent).
- `Views/payment/{success,failed}.php` — success shows name, package, order_number, instructions, Google Form + WhatsApp buttons (per-package, escaped/encoded).
- `.env.example` Razorpay keys, `Config/Razorpay`.

**Security considerations:**

- `hash_equals` verification, amount integer check, success gated by `status=paid`, webhook HMAC on raw body, idempotency via unique constraints, secrets never logged, 500 never leaks payload.

**Tests / Verification:**

- Signature valid → order `paid`, payment `captured`, success page accessible.
- Signature invalid → order `failed`, success page **not** accessible.
- Amount mismatch → `failed`.
- Duplicate verify (same payId) → idempotent 200, no duplicate captured.
- Close overlay → retry creates new Payment attempt, can succeed.
- Webhook arrives after browser verify → 200 no-op.
- Webhook with bad signature → 400.
- Success page refresh → still shows success (idempotent view) — no re-verify.
- Direct `/payment/success/FTP-xxxx` without payment → redirect to failed/404.

**Exit criteria:**

- Happy path + all failure branches in PAYMENT_FLOW.md have been manually exercised with Razorpay test mode and verified via DB/logs.

---

## Phase 11 — Hardening, Performance & Launch Prep

**Objective:** Production-grade polish and safe deploy.

**Scope:**

- Security headers final CSP (with Razorpay frame-src), HSTS (if HTTPS), rate limit tuning, error page polish.
- Performance: image optimization final, font subset, CSS minify, JS <30KB, preload hero, lazy below fold, cache headers.
- Accessibility sweep (axe, keyboard, screen reader), SEO sweep (canonical, OG, sitemap, robots, structured data).
- Backup plan: hosting cron DB dump, `.env` rotation doc, log rotation.
- Deployment doc: `public_html` vs `public` mapping, `.env` upload via SFTP not repo, `CI_ENVIRONMENT=production`, permissions.

**Security considerations:**

- Prod debug off, no stack traces, `composer audit`, `.env` 600, no secrets in repo scan.

**Tests / Verification:**

- Lighthouse CI: Performance≥90, Accessibility≥95, Best Practices≥95, SEO≥95 on mobile.
- `curl -I` shows headers; `nmap`/securityheaders.com scan passes.
- Error pages leak no internals (trigger 404/500 and inspect).
- Deploy dry-run to staging host works via FTP only, no SSH required.

**Exit criteria:**

- Checklist in SECURITY_ARCHITECTURE §15 all green; site is deployable to shared hosting without a Node process and survives reception of money.

---

## Phase 12 — Post-Launch (Out of Initial Build)

Observability, manual reconciliation task, future extensibility discussion — **not in V1 build**. Logged as separate roadmap.

---

## How to Use This File

- Each phase is a **single PR/merge boundary** with its exit criteria as acceptance.
- No agent starts Phase N+1 without explicit approval that Phase N's exit criteria are met.
- If a phase discovers a schema gap, amend via **new migration** — never silently alter a sealed phase's migration.


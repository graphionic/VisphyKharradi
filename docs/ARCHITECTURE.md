# FTPRENEUR — Architecture

> **Stack:** CodeIgniter 4 · PHP 8.1+ · MySQL/MariaDB · Server-Rendered Views · Vanilla JS · Razorpay PHP SDK  
> **Hosting:** Conventional shared hosting (cPanel / public_html) — no Node runtime  
> **Pattern:** Traditional MVC + thin controllers + service layer (where justified) + filters

---

## 1. Overall System Architecture

```
                    ┌─────────────────────────────────┐
                    │         Visitor (Mobile/Desktop)|
                    └──────────────┬──────────────────┘
                                   │ HTTPS
                    ┌──────────────▼──────────────────┐
                    │  Shared Hosting (Apache/Nginx)  │
                    │  docroot → /public              │
                    │  PHP 8.1+  +  CodeIgniter 4     │
                    └──────────────┬──────────────────┘
                                   │
              ┌────────────────────┼────────────────────┐
              │                    │                    │
     ┌────────▼────────┐  ┌────────▼────────┐  ┌───────▼────────┐
     │ Public Routes   │  │ Admin Routes    │  │ Payment Routes │
     │ /               │  │ /admin/*        │  │ /checkout/*    │
     │ /checkout/{slug}│  │ Filter: auth    │  │ /payment/*     │
     │ /legal/*        │  │ CSRF + session  │  │ Razorpay SDK   │
     └────────┬────────┘  └────────┬────────┘  └───────┬────────┘
              │                    │                    │
              └────────────────────┼────────────────────┘
                                   │ Query Builder / PDO
                    ┌──────────────▼──────────────────┐
                    │  MySQL / MariaDB                │
                    │  packages, orders, payments,    │
                    │  admins, logs, settings         │
                    └─────────────────┬───────────────┘
                                      │
                    ┌─────────────────▼───────────────┐
                    │  External                       │
                    │  Razorpay Orders API            │
                    │  Razorpay Checkout (JS - CDN)   │
                    │  Razorpay Webhooks (optional)   │
                    │  WhatsApp wa.me (link only)     │
                    │  Google Forms (per-package URL) │
                    └─────────────────────────────────┘
```

**Key boundary:** The browser never decides price or success. Razorpay Checkout is a **presentation layer only**; authority is the server.

---

## 2. Frontend / Backend Boundary

| Layer | Technology | Responsibility |
|-------|------------|----------------|
| **Server Views** | CI4 `app/Views/` (PHP + HTML5) | Semantic HTML, Open Graph, canonical, escaping. No business logic. |
| **CSS** | Modern CSS (no framework lock-in) | Minified single bundle + critical inline if needed. No Tailwind runtime required. |
| **JS** | Vanilla ES6+ | Razorpay Checkout trigger, checkout validation (mirrored server-side), mobile nav, FAQ accordion. No SPA, no bundler required in production. |
| **Backend** | CI4 Controllers → Services → Models | Order creation, Razorpay order creation, signature verification, snapshots, audit. |
| **Razorpay** | `razorpay/razorpay` Composer package (server) + `checkout.js` (client CDN) | Orders API (server), overlay (client). |

**Rule:** Production must serve without `npm run dev` or any Node process. A local build step for minification is optional, not required to render.

---

## 3. Request Lifecycle (CodeIgniter 4)

```
1. Apache/Nginx → /public/index.php (single entry)
2. CI4 Bootstrap (app/Config, .env, Autoload)
3. Routing (app/Config/Routes.php)
4. Filters:
   - SecurityHeaders filter (global)
   - Csrf filter (on POST)
   - Auth filter (on /admin/* except /admin/login)
   - Throttle filter (on /admin/login, /payment/verify)
5. Controller (thin) → validates input → delegates to Service
6. Service → Model(s) → DB (Query Builder / prepared)
7. Service returns DTO/array → Controller chooses View or JSON
8. View escapes output (esc()) → Response
9. Logging (app log + admin_activity_logs where relevant)
```

---

## 4. Directory Strategy (Proposed)

```
/
├── app/
│   ├── Config/
│   │   ├── App.php, Database.php, Filters.php, Routes.php
│   │   ├── Razorpay.php        # reads $_ENV, never hardcodes
│   │   └── Security.php        # CSP, headers config
│   ├── Controllers/
│   │   ├── Home.php            # /
│   │   ├── Checkout.php        # /checkout/{slug}
│   │   ├── Payment.php         # create-order, verify, success, failed, webhook
│   │   ├── Legal.php           # /privacy-policy etc.
│   │   └── Admin/
│   │       ├── Auth.php        # login/logout
│   │       ├── Dashboard.php
│   │       ├── Packages.php
│   │       ├── Orders.php
│   │       ├── Payments.php
│   │       ├── Settings.php
│   │       ├── ActivityLogs.php
│   │       └── Profile.php
│   ├── Models/
│   │   ├── PackageModel.php
│   │   ├── PackageFeatureModel.php
│   │   ├── OrderModel.php
│   │   ├── PaymentModel.php
│   │   ├── AdminModel.php
│   │   ├── AdminActivityLogModel.php
│   │   └── SettingModel.php
│   ├── Entities/               # only where value objects help (e.g. Order entity for snapshot logic)
│   │   └── Order.php
│   ├── Services/               # business logic — not coupled to HTTP
│   │   ├── PackageService.php
│   │   ├── OrderService.php
│   │   ├── PaymentService.php
│   │   └── RazorpayService.php
│   ├── Filters/
│   │   ├── AdminAuth.php
│   │   ├── Throttle.php
│   │   └── SecurityHeaders.php
│   ├── Validation/
│   │   ├── CheckoutRules.php
│   │   ├── PackageRules.php
│   │   └── AuthRules.php
│   ├── Database/
│   │   ├── Migrations/         # reproducible schema — no phpMyAdmin manual creation
│   │   └── Seeds/              # AdminSeeder only (no fake orders)
│   ├── Helpers/
│   │   └── app_helper.php      # only if justified (e.g. money formatting, order number)
│   └── Views/
│       ├── layouts/
│       │   ├── public.php      # header/footer, OG, nav
│       │   └── admin.php       # admin shell
│       ├── home/index.php      # landing — static sections + dynamic packages loop
│       ├── checkout/index.php
│       ├── payment/success.php
│       ├── payment/failed.php
│       ├── legal/*.php
│       └── admin/**/*.php
├── public/
│   ├── index.php               # CI4 front controller — DOCUMENT ROOT
│   ├── assets/
│   │   ├── css/app.min.css
│   │   ├── js/app.min.js       # vanilla only
│   │   ├── images/             # optimized, webp + fallback
│   │   └── fonts/
│   ├── robots.txt
│   ├── sitemap.xml             # static or controller-generated in later phase
│   └── favicon.ico
├── writable/                   # CI4 logs, cache, sessions (outside public)
├── docs/                       # Phase 0 deliverables (this folder)
├── .env.example                # template — no secrets
├── composer.json
└── README.md
```

**Shared-hosting note:** On hosts that expose `public_html` not `public`, either (a) point domain to `public/` via cPanel, or (b) move `public/` contents to `public_html` and adjust `../app` path in `index.php` — documented in deployment guide. No symlink tricks that require shell beyond FTP.

---

## 5. Service Boundaries (Justified Minimalism)

| Service | Owns | Why a service (not just controller) |
|---------|------|--------------------------------------|
| **PackageService** | Active-package retrieval, slug lookup, feature ordering, display-order logic | Reused by Home, Checkout, Admin; keeps controllers thin |
| **OrderService** | Order number generation, snapshot creation, status transitions | Concurrency-safe number generation + snapshot isolation belongs in one place |
| **PaymentService** | Payment record lifecycle, idempotency, status mapping, retry | Multiple entry points (browser callback + webhook) must share logic |
| **RazorpayService** | Razorpay SDK wrapping, order creation, signature verification, webhook verification | Isolates SDK, centralizes credential access, testable without HTTP |

**Rule:** Do not add `UserService`, `InvoiceService`, `CouponService` etc. in V1 — they would be speculative.

Controllers remain thin:

```php
// Checkout.php — example intent (not implementation in Phase 0)
public function createOrder(string $slug) {
  $this->validateCheckout();               // server authoritative
  $order = $this->orderService->createForPackage($slug, $this->request->getPost());
  $rpay  = $this->razorpayService->createOrder($order);
  return $this->response->setJSON([...]);
}
```

---

## 6. Shared-Hosting Compatibility Decisions

| Decision | Rationale |
|----------|-----------|
| **No Node production runtime** | Shared hosting rarely allows long-running processes; CI4 serves without it |
| **Composer for Razorpay SDK only** | Avoid dependency sprawl; CI4 + razorpay/razorpay is sufficient for V1 |
| **File-based sessions or DB sessions (CI4 default)** | Works without Redis; configurable via `app/Config/App.php` |
| **No queue worker** | Webhook handling is synchronous + idempotent; no daemon needed |
| **No build step required to deploy** | If a local minifier is used, committed `app.min.css/js` is what deploys |
| **MySQL / MariaDB via PDO** | Universally available on shared hosting |
| **`writable/` outside document root** | CI4 default — logs/sessions not web-accessible |
| **Environment via `.env`** | CI4's `DotEnv` — no secrets in repo; `$_ENV` read via Config |

---

## 7. Configuration & Environments

| Env | How configured | Notes |
|-----|----------------|-------|
| **Local** | `.env` (from `.env.example`) | `CI_ENVIRONMENT = development`, `app.baseURL = http://localhost:8080/` |
| **Staging** (if available) | `.env` on staging host | `development` or `testing`, separate Razorpay test keys |
| **Production** | `.env` on shared host (FTP/uploaded, never committed) | `production`, `app.baseURL = https://ftpreneur.com`, live Razorpay keys |

**Secrets that are env-only:**
- `database.default.*`
- `app.baseURL`, `app.encryptionKey`
- `razorpay.keyId`, `razorpay.keySecret`, `razorpay.webhookSecret`

**Never:** commit `.env`, log secrets, expose `keySecret` to JS.

---

## 8. Dependency Strategy

| Package | Purpose | Justification |
|---------|---------|---------------|
| `codeigniter4/framework` | Core | Required |
| `razorpay/razorpay` | Razorpay Orders + signature verification | Official SDK — do not hand-roll HMAC |
| *No frontend framework* | — | Vanilla JS is sufficient for checkout trigger + accordions |
| *No admin template dependency* | — | Custom premium shell in later phase — no AdminLTE lock-in |

Adding a dependency requires: (a) justification in PR, (b) license check, (c) shared-hosting compatibility check. See Constitution.

---

## 9. Caching Strategy (V1 — Conservative)

- **No full-page cache** in V1 (packages infrequent, orders/payments must not be cached).
- **Query efficiency:** Home page loads `packages` + `package_features` in 2 queries (eager), not N+1.
- **Browser caching:** `Cache-Control` for `assets/` via `.htaccess` (`1 year` for hashed or versioned assets, `no-cache` for HTML).
- **Future:** If needed, CI4's file cache for package list with tag invalidation on admin save — not required at launch.

---

## 10. Anti-Patterns Explicitly Avoided

- Single giant `Home` controller that creates orders, verifies payments, and sends WhatsApp messages.
- Storing money as `FLOAT`/`DOUBLE`.
- Committing `.env` or logging `razorpay_signature`.
- Building a CMS for every heading/paragraph.
- Client-side `amount` hidden field as price source.
- Fat views with SQL queries.

---

## 11. Evolution Without Breaking V1

Future features (subscriptions, coupons, customer login) can be added as **new tables + new services + new route groups** without altering V1 tables in breaking ways:

- `coupons`, `subscriptions`, `customers` would be additive.
- V1 `orders.package_price_snapshot` already anticipates pricing variability.
- Webhook handler is designed to accept new event types later.

No speculative tables are created now.


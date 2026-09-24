# FTPRENEUR — Visphy Kharradi

> *Visphy Kharradi's Nutrition, Strength Training And Disease Management Plan*  
> Personalised nutrition, movement and lifestyle guidance — delivered as a conversion-focused, server-rendered platform.

**Phase:** 5D — Package Media, Rich Content & Lifecycle (MEDIA + LIFECYCLE READY) — 9 migrations, 9 models, 2 enums, 3 services (OrderNumber, Package, HtmlSanitizer)  
**Stack:** CodeIgniter 4.7.4 · PHP 8.4 · MariaDB 11.8 · HTMLPurifier 4.19 · Quill 1.3.6 · 189 tests PASS  
**Docs:** `docs/ARCHITECTURE.md` · `docs/DATABASE_SCHEMA.md` (Phase 5D) · `docs/DEVELOPMENT_PHASES.md`

---

## Overview

**FTPRENEUR** is the personal service platform for Visphy Kharradi.

Visitors:

1. Learn about Visphy Kharradi & the Ftpreneur approach (static, server-rendered sections)
2. View dynamic **Packages** (the only DB-driven landing content in V1)
3. Checkout with **3 fields** — Full Name, Mobile, Email
4. Pay **once** via **Razorpay** (server-verified)
5. Land on a **verified** success page → continue onboarding via **Google Form** (per-package) + **WhatsApp** (per-package template, `wa.me` link)

There are **no customer accounts** in V1. Post-payment onboarding is human via WhatsApp / Form — not automated.

---

## Technology Stack — Phase 2 Actual

| Layer | Choice | Locked Version / Notes |
|-------|--------|------------------------|
| **Framework** | CodeIgniter 4 | **4.7.4** ( `codeigniter4/framework ^4.7`, PHP `^8.2` ) |
| **PHP** | PHP 8.x | **PHP 8.4.24** verified; `composer.json` requires `^8.2` |
| **Database** | MySQL / MariaDB — **MariaDB 11.8.6** verified (InnoDB, `utf8mb4_unicode_ci`) | `MySQLi` driver, `DECIMAL(10,2)` for money, 8 migrations verified |
| **Frontend** | HTML5 + modern CSS + Vanilla JS + **Quill 1.3.6** (rich `full_description` only) | No React/Vue/SPA; no Node production runtime; Quill via CDN `cdn.quilljs.com` |
| **Sanitizer** | **ezyang/htmlpurifier 4.19.1** | Server-side allowlist `p,br,strong,b,em,i,u,h2,h3,ul,ol,li,blockquote,a[href|target|rel|title]` + `http/https` only; `writable/htmlpurifier` cache |
| **Uploads** | `public/uploads/packages/{id}/` | JPEG/PNG/WEBP, 5 MB max, `finfo` + `getimagesize` MIME, safe random filename, `.htaccess` non-exec (`Require all denied` for `php|phtml|phar`) |
| **Payments** | Razorpay (future) | **Not installed in Phase 5D** — placeholder vars only; no public/checkout/orders/payments in 5D |
| **Hosting** | Shared hosting (cPanel / public_html) | `public/` is document root — no daemon; `public/uploads/packages/.htaccess` denies PHP execution |
| **Env** | `.env` + `.env.example` | CI4 DotEnv, `.env` gitignored, `Asia/Kolkata` timezone |
| **Tooling** | Composer **2.8.8**, PHPUnit **10.5.64**, `pcov` | `composer test` **189 tests PASS** (158 base + 31 media) |

**Extensions verified:** `ext-intl`, `ext-mbstring`, `ext-curl`, `ext-openssl`, `ext-pdo_mysql`, `ext-pdo_sqlite`, `ext-xml`, `ext-zip`, `ext-json`, `ext-bcmath`, `ext-gd` (for test image generation), `ext-fileinfo` (MIME), `pcov`

---

## Architecture Summary

- **Pattern:** Traditional MVC + thin controllers + `Services` (OrderNumberService isolated) + `Filters` + `Models` (`Domain` enums for statuses)
- **Request lifecycle:** `public/index.php` → `Config/Routes` → `Filters` (`securityheaders` after) → `Controllers/Home::index` → `Views/home/foundation` → Response (foundation still `GET /` only)
- **Static vs Dynamic boundary:** Landing copy static; only `packages` + `package_features` DB-driven — no CMS for headings
- **Order number (LOCKED, verified Phase 2):** `FTP-YYYY-000001` via `order_sequences` (`year` PK, `last_number`) with `INSERT ... ON DUPLICATE KEY UPDATE last_number = last_number + 1` + `SELECT` in InnoDB transaction (row-lock). Verified: `FTP-2026-000001`, `000002`, `FTP-2027-000001` resets per year. `orders.order_number` `UNIQUE`, `LPAD(id)` rejected.
- **Timezone (LOCKED):** `Asia/Kolkata` in `app/Config/App.php` (`appTimezone`); DB `DATETIME` UTC-consistent, display localized
- **Money (locked):** `DECIMAL(10,2)` on `packages.regular_price/selling_price`, `orders.package_price_snapshot/subtotal/total_amount`, `payments.amount` — `FLOAT` forbidden, verified decimal `1234.56` preserved
- **Money math (Phase 5B/5C):** `ext-bcmath` required — `PackageService` uses `bccomp()` for DECIMAL-safe `selling_price <= regular_price` comparison; verified `php -m | grep bcmath` and `php -r "echo bccomp('1.00','2.00',2);"`; install via `sudo apt-get install php-bcmath` (php8.4-bcmath) and restart server; runtime check in `PackageService::updatePackage()` logs error if missing
- **Models:** 8 with `allowedFields` restricted, timestamps, soft-delete on `PackageModel` only; `AdminActivityLogModel` append-only (no `updated_at`)
- **SecurityHeaders filter:** baseline (`nosniff`, `SAMEORIGIN`, `strict-origin-when-cross-origin`, `Permissions-Policy`) — CSP with Razorpay deferred to Phase 10
- **Uploads (Phase 5D):** `public/uploads/packages/{package-id}/` — relative path stored (`uploads/packages/1/featured_...jpg`), never absolute/client name; `finfo`+`getimagesize` MIME (JPEG/PNG/WEBP, 5 MB, double-ext `*.php` rejected, SVG/HTML/JS rejected), safe `bin2hex(random_bytes(16)).ext`, `public/uploads/packages/.htaccess` (`<FilesMatch "\.(php|phtml|phar)$"> Require all denied` + `php_flag engine off`), `755` dir
- **Rich text (Phase 5D):** `full_description` via **Quill 1.3.6** (Bold/Italic/Underline/H2/H3/Bullets/Numbers/Blockquote/Link/Clear) → hidden `textarea` sync → `HtmlSanitizerService` (`HTMLPurifier 4.19`, `HTML.Allowed p,br,strong,b,em,i,u,h2,h3,ul,ol,li,blockquote,a[href|target|rel|title]`, `URI.AllowedSchemes http/https`, `ForbiddenElements script/style/iframe/object/embed/form/input/button`, `Cache.SerializerPath writable/htmlpurifier` 755)
- **Lifecycle (Phase 5D):** `packages.deleted_at` soft-delete only (`is_active=0`+`deleted_at`), `GET /admin/packages/deleted` (Restore), `POST .../delete` (modal "Delete Package? … You can restore it later."), `POST .../restore` (clears `deleted_at`, `is_active=0`, end `display_order`), `POST .../toggle-active|toggle-featured|reorder` (complete IDs, transactional, drag+Up/Down), audit `package.deleted|restored|activated|deactivated|featured|unfeatured|packages.reordered`
- **Models:** 9 with `allowedFields`, timestamps, soft-delete on `PackageModel`; `PackageGalleryImageModel` (gallery), `AdminActivityLogModel` append-only

See `docs/ARCHITECTURE.md` and `docs/DATABASE_SCHEMA.md` (Phase 2 Actual header) for diagram, ER, and migration details.

---

## Prerequisites

- **PHP:** `>=8.2` (8.4.24 verified). `php -v`
- **Extensions:** `intl`, `mbstring`, `curl`, `openssl`, `pdo_mysql` (and `pdo_sqlite` for tests fallback), `xml`, `zip`, `bcmath` (DECIMAL `bccomp`), `gd` (test image gen), `fileinfo` (MIME `finfo`), `pcov` (optional)
- **Composer:** `>=2.0` (2.8.8 verified)
- **Database:** MySQL 5.7+ / MariaDB 10.4+ **(MariaDB 11.8.6 verified)** — `utf8mb4_unicode_ci`, `InnoDB`; SQLite `:memory:` for isolated unit tests fallback
- **Web:** Apache `mod_rewrite` + `AllowOverride All` **or** `php spark serve` for local dev

No Node, no Redis, no queue worker required.

---

## Local Setup — Phase 2 Verified

```bash
# 1) Clone
git clone <repo> ftpreneur && cd ftpreneur

# 2) Install
composer install          # installs ci4 4.7.4, phpunit 10.5.64

# 3) Environment
cp .env.example .env      # then edit .env
# Required in .env:
#   CI_ENVIRONMENT=development
#   app.baseURL=http://localhost:8080/
#   database.default.*  → ftpreneur (dev)
#   database.tests.*    → ftpreneur_test (never production)
#   ADMIN_SEED_NAME / ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD (for seeder)
#   encryption.key (auto-generated via key:generate)

# 4) Database — create DBs (MariaDB example)
sudo mysql -e "CREATE DATABASE ftpreneur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE DATABASE ftpreneur_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'ftpreneur'@'localhost' IDENTIFIED BY 'ftpreneur'; GRANT ALL ON ftpreneur.* TO 'ftpreneur'@'localhost'; GRANT ALL ON ftpreneur_test.* TO 'ftpreneur'@'localhost';"

# 5) Migrations
php spark migrate                 # default (ftpreneur) — 9 migrations (8 base + AddPackageMedia)
php spark migrate:status         # should show 9 migrated (batch 1)
php spark db:seed AdminSeeder    # creates initial admin from ADMIN_SEED_* in .env (fails safely if missing)

# 5b) Uploads & Purifier cache (Phase 5D) — required for featured/gallery + Quill sanitizer
mkdir -p public/uploads/packages writable/htmlpurifier
chmod 755 public/uploads/packages writable/htmlpurifier
# Ensure public/uploads/packages/.htaccess denies PHP (see repo file) — verify:
cat public/uploads/packages/.htaccess  # <FilesMatch "\.(php|phtml|phar)$"> Require all denied
php -m | grep -E 'bcmath|gd|fileinfo'   # bcmath, gd, fileinfo must be enabled
composer show ezyang/htmlpurifier       # 4.19.1 — sanitizer

# 6) Run
php spark serve                   # http://localhost:8080  → “Foundation Ready”
php spark routes                  # GET / → Home::index

# 7) Test
composer test                     # 189 tests: 158 base + 31 media (Phase 5D) — zero FK, sanitizer, uploads, lifecycle
php spark migrate:rollback        # then php spark migrate  → verify re-migrate

# 8) Verify headers
curl -i http://localhost:8080/     # expect 200 + nosniff, SAMEORIGIN, Referrer-Policy
```

**Test DB strategy:** `database.tests` (`ftpreneur_test`) is separate from `database.default` (`ftpreneur`). `ENVIRONMENT=testing` (phpunit) automatically uses `tests` group — never touches production. `DatabaseTestTrait` with `$refresh = true` truncates `ftpreneur_test` per test via migrations, not production.

---

## Environment Strategy

| Env | File | `CI_ENVIRONMENT` | DB | Notes |
|-----|------|-------------------|----|-------|
| **Local** | `.env` (from `.env.example`) | `development` | `ftpreneur` | `app.baseURL=http://localhost:8080/`, `logger.threshold=9`, toolbar ON |
| **Testing** | `phpunit.dist.xml` + `database.tests` | `testing` | `ftpreneur_test` | `MySQLi`, `refresh true`, `migrate true` — never production; `pcov` coverage |
| **Production** | `.env` on host (SFTP, `600`, never repo) | `production` | `ftpreneur` (prod) | `app.baseURL=https://ftpreneur.com/`, `logger.threshold=4`, toolbar OFF |

**Secrets are env-only:** `database.*`, `encryption.key`, future `razorpay.*`, `ADMIN_SEED_*`. `.env` gitignored. Commit only `.env.example` with placeholders. Razorpay vars remain commented `UNUSED UNTIL PHASE 10`.

---

## Directory Layout — Phase 2 Actual

```
/home/user (project root)
├── app/
│   ├── Config/App.php, Filters.php, Security.php, Session.php, Database.php
│   ├── Controllers/Home.php
│   ├── Database/Migrations/2026-09-23-000001_*.php … 000008_*.php (8)
│   ├── Database/Seeds/AdminSeeder.php
│   ├── Domain/OrderStatus.php, PaymentStatus.php (enums)
│   ├── Filters/SecurityHeaders.php
│   ├── Models/AdminModel.php, PackageModel.php (softDelete), PackageFeatureModel.php,
│   │        OrderModel.php, PaymentModel.php, OrderSequenceModel.php,
│   │        AdminActivityLogModel.php, SettingModel.php (8)
│   ├── Services/OrderNumberService.php (FTP-YYYY-000001)
│   ├── Views/home/foundation.php
│   └── Views/welcome_message.php (unused)
├── public/index.php + .htaccess
├── writable/{cache,logs,session,uploads} (Require all denied)
├── tests/database/SchemaIntegrityTest.php (10 invariants)
├── tests/database/OrderSequenceTest.php (5 sequence tests)
├── tests/unit/FoundationSmokeTest.php + HealthTest.php
├── docs/ (Phase 0 sealed, Phase 2 Actual header in DATABASE_SCHEMA.md)
├── .env (gitignored) + .env.example + env
├── composer.json (php ^8.2, ci4 ^4.7) + composer.lock
└── spark
```

Confirmed: `docs/` preserved, `writable/` outside `public/`, `.env` not in git, `vendor/` gitignored, no speculative tables.

---

## Shared-Hosting Deployment — Phase 2 Strategy

**Preferred (when host allows docroot change):**

```
/home/account/ftpreneur/     ← repo root (app, writable, vendor, .env)
  app/  vendor/  writable/  .env  spark
/home/account/ftpreneur/public/  ← DocumentRoot (cPanel → Document Root)
  index.php  .htaccess
```

**Fallback (when docroot fixed to `public_html`):**

- Upload `public/` contents to `public_html/`
- Keep `app/`, `writable/`, `vendor/`, `.env` one level above
- Adjust `public_html/index.php` `$pathsPath = FCPATH . '../app/Config/Paths.php';` → `__DIR__ . '/../ftpreneur/app/Config/Paths.php'`

**Permissions:** `public 755`, `writable 755` (or `775`), `.env 600`, never `777` — verified `755`.

**DB on shared hosting:** Create `ftpreneur` DB via cPanel MySQL Wizard, import via `php spark migrate` if SSH available or via exported SQL from local `mysqldump`. Charset `utf8mb4_unicode_ci` ensured via migrations.

---

## Configuration — Phase 2 Actual

| Config | Value | Notes |
|--------|-------|-------|
| `App.baseURL` | `http://localhost:8080/` | Via `.env` |
| `App.indexPage` | `''` | Pretty URLs |
| `App.appTimezone` | `Asia/Kolkata` | Locked — India |
| `App.CSPEnabled` | `false` | Deferred |
| `Database.default` | `MySQLi`, `utf8mb4`, `utf8mb4_unicode_ci`, `InnoDB` | Via `.env`, verified |
| `Database.tests` | `MySQLi`, `ftpreneur_test` | Used by `DatabaseTestTrait` |
| `Cookie.secure` | `false` local / `true` prod | `httponly true`, `samesite Lax` |
| `Session.driver` | `FileHandler`, `ftpreneur_session`, `7200s` | `writable/session` |
| `Security.csrfProtection` | `cookie` | Globally off Phase 2 (no POST); enabled Phase 3 |
| `Logger.threshold` | `9` dev / `4` prod | Via `.env` |

---

## Database — Phase 2 Actual

**Engine:** `InnoDB`, `utf8mb4`, `utf8mb4_unicode_ci` — verified via `SHOW TABLE STATUS`

**Migrations (8, `2026-09-23-000001` … `000008`):**

| # | File | Table | Purpose |
|---|------|-------|---------|
| 1 | `CreateAdmins` | `admins` | `email` unique, `password_hash` 255, `is_active` idx |
| 2 | `CreatePackages` | `packages` | `slug` unique, `DECIMAL(10,2)` prices, `display_order` + `is_active` composite idx, soft delete `deleted_at` |
| 3 | `CreatePackageFeatures` | `package_features` | `package_id` **logical indexed** (no FK), `(package_id, display_order)` idx, normalized |
| 4 | `CreateOrderSequences` | `order_sequences` | `year` PK, `last_number`, row-lock for `FTP-YYYY-000001` |
| 5 | `CreateOrders` | `orders` | `order_number` unique, `razorpay_order_id` unique nullable, snapshots, `status VARCHAR(20)`, `package_id` logical indexed (no FK) |
| 6 | `CreatePayments` | `payments` | `razorpay_payment_id` unique nullable, `status VARCHAR(20)`, `order_id` logical indexed (no FK), `gateway_response TEXT` |
| 7 | `CreateAdminActivityLogs` | `admin_activity_logs` | `admin_id` logical indexed (no FK), append-only, indexes on `action`, `entity`, `created_at` |
| 8 | `CreateSettings` | `settings` | `setting_key` unique, `setting_type ENUM` |

**Relationships (logical, no DB FKs — `FOREIGN KEY COUNT = 0` verified):**
- `package_features.package_id → packages.id` — indexed, **no FK**, service validates
- `orders.package_id → packages.id` — indexed nullable, **no FK**, `OrderService` validates `is_active`; snapshots preserve history
- `payments.order_id → orders.id` — indexed, **no FK**, `PaymentService` validates; orders never hard-deleted
- `admin_activity_logs.admin_id → admins.id` — indexed, **no FK**, prefer `is_active=0` over hard delete

**Indexes verified:** `uq_*`, `idx_*` per `DATABASE_SCHEMA.md §2-8`.

**Domain statuses (enums):**
- `App\Domain\OrderStatus`: `pending`, `paid`, `failed`, `cancelled` (stored `VARCHAR(20)` not `ENUM`)
- `App\Domain\PaymentStatus`: `created`, `attempted`, `captured`, `failed`, `cancelled` (stored `VARCHAR(20)`)

**OrderNumber:** `OrderNumberService::generate(2026)` → `FTP-2026-000001`; `generateInTransaction()` for future `OrderService`; transaction `INSERT ... ON DUPLICATE KEY UPDATE` + `SELECT`.

**Seeders:** `AdminSeeder` reads `ADMIN_SEED_NAME/EMAIL/PASSWORD` from `.env`, `password_hash()`, fails safely if missing (<10 chars, invalid email, already exists).

---

## Routes — Phase 2 Actual

```php
// app/Config/Routes.php
$routes->get('/', 'Home::index');
```

No `/admin`, `/checkout`, `/payment` yet — Phase 3+. `404` safe.

---

## Security Foundation — Phase 2 (Data Layer)

- `admins.password_hash` `VARCHAR(255)` for `password_hash()`; never plaintext; seeder hashes.
- `allowedFields` restricted per Model (no `*`); `protectFields true`.
- `orders`/`payments` `DECIMAL(10,2)` — no `FLOAT`.
- **No DB FKs:** history preserved by service checks — `SELECT COUNT(*) FROM orders WHERE package_id=?` blocks delete of referenced package; audit kept via `is_active=0` on `admins` (no `SET NULL` via DB).
- `admin_activity_logs.metadata` `TEXT` sanitized JSON — never secrets; `orders.notes` `TEXT` no secrets.
- No health data in `orders`; only `name/email/phone`; no `health_records` table.
- Soft delete on `packages` hides via `deleted_at`; `withDeleted()` for audit.
- `php spark migrate:status` / `rollback` verified; `FOREIGN KEY COUNT = 0` verified on `ftpreneur` and `ftpreneur_test` (no `DROP` FK handling needed).

---

## How to Test — Phase 2

```bash
php -v ; composer --version          # 8.4.24, 2.8.8
composer validate                    # valid
php spark routes                     # GET / → Home::index
php spark migrate:status             # 8 migrated
php spark db:seed AdminSeeder        # reads ADMIN_SEED_* from .env
composer test                        # 23 tests: 8 Health/Foundation + 10 SchemaIntegrity + 5 OrderSequence
# Or: vendor/bin/phpunit --testsuite App
sudo mysql -e "SHOW TABLES FROM ftpreneur;"       # 8 tables + migrations
sudo mysql -e "SELECT * FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='ftpreneur';"
```

All green: `migrate`, `migrate:rollback` → `migrate`, `migrate:status`, `db:seed` idempotent, `23/23` tests, **zero-FK** & `UNIQUE` & `DECIMAL` invariants, `OrderSequence` year-reset, `softDelete` hidden.

---

## Documentation Index

| Document | Covers | Phase 2 Update |
|----------|--------|----------------|
| [`docs/PROJECT_OVERVIEW.md`](docs/PROJECT_OVERVIEW.md) | Product definition, V1 scope | Sealed |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | System, lifecycle, services | Domain & service noted |
| [`docs/DATABASE_SCHEMA.md`](docs/DATABASE_SCHEMA.md) | Tables, types, snapshots, zero-FK | **Phase 2 Actual** header + `VARCHAR` not `ENUM` + `TEXT` not `JSON` + **zero FK** (logical indexes + service validation) |
| [`docs/SECURITY_ARCHITECTURE.md`](docs/SECURITY_ARCHITECTURE.md) | Threat model, headers | Data layer added |
| [`docs/PAYMENT_FLOW.md`](docs/PAYMENT_FLOW.md) | Checkout & Razorpay | Unchanged (future) |
| [`docs/ADMIN_ARCHITECTURE.md`](docs/ADMIN_ARCHITECTURE.md) | Admin modules | Unchanged (future) |
| [`docs/FRONTEND_ARCHITECTURE.md`](docs/FRONTEND_ARCHITECTURE.md) | Landing sections | Unchanged (future) |
| [`docs/DEVELOPMENT_PHASES.md`](docs/DEVELOPMENT_PHASES.md) | Phased roadmap | Phase 2 exit criteria met |
| [`docs/PROJECT_CONSTITUTION.md`](docs/PROJECT_CONSTITUTION.md) | Non-negotiable rules | Sealed |

---

## Development Roadmap

| Phase | Name | Status |
|-------|------|--------|
| **0** | **Architecture & Constitution** | **Sealed ✓** |
| **1** | **CodeIgniter Foundation** | **Sealed ✓** |
| **2** | **Database & Migrations** | **PASS — this README** |
| 3 | Admin Auth & Security Foundation | **Sealed ✓** (158 tests) |
| 4 | Admin Design System & Layout | **Sealed ✓** |
| 5A | Package Listing | **Sealed ✓** |
| 5B | Package Create | **Sealed ✓** |
| 5C | Package Edit/Update | **Sealed ✓** (158/1098) |
| 5D | **Package Media, Rich Content & Lifecycle** | **PASS — this README (189 tests)** |
| 5E | Public Packages & Checkout (next) | **STOP — DO NOT START (per 5D exit)** |
| 6 | Order/Payment Domain (no Razorpay) | — |
| 7 | Public Frontend Design System | — |
| 8 | Landing Page Sections | — |
| 9 | Checkout | — |
| 10 | Razorpay Integration + Post-Purchase | — |
| 11 | Hardening & Launch Prep | — |

---

## Project Constitution

All contributors must read [`docs/PROJECT_CONSTITUTION.md`](docs/PROJECT_CONSTITUTION.md) first. Invariants: `DECIMAL` for money, migrations for schema, `FTP-YYYY-000001` via `order_sequences`, **zero DB FKs** (logical indexes + service validation) — order history via service `RESTRICT`, audit via `is_active=0`, never `FLOAT`, never speculative tables.

---

## License & Rights

Private project — all rights reserved to the Ftpreneur operator. Do not redistribute credentials, customer PII, or payment artifacts.

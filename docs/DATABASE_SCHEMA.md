# FTPRENEUR — Database Schema

> **Engine:** InnoDB · **Charset:** utf8mb4 (`utf8mb4_unicode_ci`) · **Migrations:** CodeIgniter 4 `app/Database/Migrations`  
> **Money rule:** `DECIMAL(10,2)` for INR (never FLOAT/DOUBLE) — amounts in rupees; Razorpay expects paise (×100 at service layer, never stored as paise)  
> **Time rule:** `DATETIME` (UTC) managed by CI4 `useTimestamps` → `created_at`, `updated_at`; display localized in views

> **Phase 2 Actual (2026-09-23 • Corrected — Zero FK):** Migrations verified on **MariaDB 11.8.6** (InnoDB, `utf8mb4_unicode_ci`). 8 migrations named `2026-09-23-000001` … `000008`. **LOCKED rules:** `orders.status`/`payments.status` are `VARCHAR(20)` (not `ENUM`); `payments.gateway_response` and `admin_activity_logs.metadata` are `TEXT` (filtered JSON as text) for shared-hosting compat; `order_sequences` is **required** (`FTP-YYYY-000001`); **ZERO database FKs** — relationships are logical indexes + application validation (see `PROJECT_CONSTITUTION`). All `DECIMAL(10,2)`, `UNIQUE`, `INDEX`, `utf8mb4_unicode_ci` verified. `FOREIGN KEY COUNT = 0`.

---

## 1. ER Overview — Logical (No Database FKs)

```
admins 1──∞ admin_activity_logs
               (admin_id → admins.id, logical, indexed, no FK — is_active=false preferred over hard delete)

packages 1──∞ package_features
               (package_id → packages.id, logical, indexed, no FK — soft delete on packages)

packages 1──∞ orders
               (orders.package_id → packages.id, logical, indexed, nullable, no FK — snapshots preserve history)
orders 1──∞ payments
               (payments.order_id → orders.id, logical, indexed, no FK — orders never hard-deleted)

settings (key-value, no reference)
order_sequences (standalone year counter)

orders  — snapshot fields duplicate package data at purchase time (immutable)
payments — stores razorpay_order_id, razorpay_payment_id (unique where not null)
```

**Delete philosophy (application-enforced, no DB cascade/restrict):**  
- `packages` → **soft archival** (`is_active=0`, `deleted_at via PackageModel`) if any `orders` reference it. Hard delete only if zero orders — **service checks** `SELECT COUNT(*) FROM orders WHERE package_id=?` — no DB `RESTRICT`.  
- `orders`/`payments` → **never hard-deleted** via normal UI (financial/historical records) — no `CASCADE`.  
- `admins` → deactivation via `is_active=0`, not hard delete; preserves `admin_activity_logs.admin_id` (no `SET NULL` DB). If hard delete ever needed, service explicitly handles logs.  
- `package_features` → managed explicitly by `PackageService` on package edit (no `CASCADE`).

**Integrity:** Via **service transactions + existence validation** (`PackageService verifies package exists`, `OrderService verifies package active`, `PaymentService verifies order exists`) — not `FOREIGN KEY`.

---

## 2. Table: `admins`

**Purpose:** Single-role admin authentication in V1. Schema allows future roles without breaking change.

| Column | Type | Null | Default | Notes |
|--------|------|------|---------|-------|
| `id` | `BIGINT UNSIGNED` | NO | auto_inc | PK |
| `name` | `VARCHAR(100)` | NO | — | Display name |
| `email` | `VARCHAR(190)` | NO | — | Unique, login identifier |
| `password_hash` | `VARCHAR(255)` | NO | — | `password_hash()` bcrypt/argon2 |
| `is_active` | `TINYINT(1)` | NO | `1` | Deactivation without delete |
| `last_login_at` | `DATETIME` | YES | NULL | — |
| `created_at` | `DATETIME` | YES | NULL | CI4 timestamps |
| `updated_at` | `DATETIME` | YES | NULL | — |

**Indexes / Constraints:**
- `PRIMARY KEY (id)`
- `UNIQUE KEY uq_admins_email (email)`
- `KEY idx_admins_is_active (is_active)`

**FK:** none (as per zero-FK rule)

**Sensitive:** `password_hash` never logged or returned.

---

## 3. Table: `packages`

**Purpose:** Purchasable programs. Only table (plus features) that is dynamic in V1. Landing page queries `WHERE is_active=1 AND deleted_at IS NULL ORDER BY display_order ASC, id ASC`.

| Column | Type | Null | Default | Notes |
|--------|------|------|---------|-------|
| `id` | `BIGINT UNSIGNED` | NO | auto_inc | PK |
| `name` | `VARCHAR(150)` | NO | — | e.g. "90-Day Disease Management Plan" |
| `slug` | `VARCHAR(190)` | NO | — | Unique, URL-safe, `/checkout/{slug}` |
| `short_description` | `VARCHAR(300)` | YES | NULL | Card teaser |
| `full_description` | `TEXT` | YES | NULL | Detail area (escaped) |
| `regular_price` | `DECIMAL(10,2)` | NO | — | MRP / strikethrough |
| `selling_price` | `DECIMAL(10,2)` | NO | — | Authoritative price |
| `duration_value` | `SMALLINT UNSIGNED` | YES | NULL | e.g. 90 |
| `duration_unit` | `ENUM('days','weeks','months')` | YES | NULL | — |
| `badge` | `VARCHAR(50)` | YES | NULL | e.g. "Most Popular" |
| `cta_label` | `VARCHAR(50)` | NO | `'Get Started'` | Per-package CTA |
| `google_form_url` | `VARCHAR(2048)` | YES | NULL | Validated HTTPS URL |
| `whatsapp_template` | `TEXT` | YES | NULL | Template with placeholders |
| `display_order` | `INT UNSIGNED` | NO | `100` | Lower = higher on page |
| `is_featured` | `TINYINT(1)` | NO | `0` | Visual emphasis |
| `is_active` | `TINYINT(1)` | NO | `1` | `0` = hidden & not purchasable |
| `created_at` | `DATETIME` | YES | NULL | — |
| `updated_at` | `DATETIME` | YES | NULL | — |
| `deleted_at` | `DATETIME` | YES | NULL | Soft delete (CI4 `useSoftDeletes`) |

**Indexes / Constraints:**
- `PRIMARY KEY (id)`
- `UNIQUE KEY uq_packages_slug (slug)`
- `KEY idx_packages_active_order (is_active, display_order)`
- `KEY idx_packages_is_featured (is_featured)`

**FK:** none

---

## 4. Table: `package_features`

**Purpose:** Normalized bullets per package. **Never CSV.**

| Column | Type | Null | Default | Notes |
|--------|------|------|---------|-------|
| `id` | `BIGINT UNSIGNED` | NO | auto_inc | PK |
| `package_id` | `BIGINT UNSIGNED` | NO | — | Logical reference to `packages.id` — **indexed, no FK** |
| `feature_text` | `VARCHAR(300)` | NO | — | Single bullet |
| `display_order` | `INT UNSIGNED` | NO | `100` | Ordering within package |
| `is_active` | `TINYINT(1)` | NO | `1` | Allow hiding without delete |
| `created_at` | `DATETIME` | YES | NULL | — |
| `updated_at` | `DATETIME` | YES | NULL | — |

**Indexes / Constraints:**
- `PRIMARY KEY (id)`
- `KEY idx_pf_package_order (package_id, display_order)` — composite
- `KEY idx_pf_package_id (package_id)` — reference index
- **No FK** — application `PackageService` verifies `package_id` exists before insert; explicit cleanup on package edit (no cascade)

**Query pattern:** `SELECT * FROM package_features WHERE package_id = ? AND is_active=1 ORDER BY display_order ASC, id ASC`

---

## 5. Table: `orders`

**Purpose:** Local order — the authoritative financial record. Holds **snapshots** so history survives package edits. Created **before** Razorpay order.

| Column | Type | Null | Default | Notes |
|--------|------|------|---------|-------|
| `id` | `BIGINT UNSIGNED` | NO | auto_inc | PK (internal — never exposed) |
| `order_number` | `VARCHAR(20)` | NO | — | Public `FTP-2026-000001`, `UNIQUE` |
| `package_id` | `BIGINT UNSIGNED` | YES | NULL | Logical reference to `packages.id` — **indexed, no FK**, nullable to allow archival |
| `package_name_snapshot` | `VARCHAR(150)` | NO | — | Immutable copy at purchase time |
| `package_slug_snapshot` | `VARCHAR(190)` | NO | — | For reference |
| `package_price_snapshot` | `DECIMAL(10,2)` | NO | — | `selling_price` at purchase time |
| `package_duration_snapshot` | `VARCHAR(30)` | YES | NULL | e.g. "90 days" |
| `customer_name` | `VARCHAR(100)` | NO | — | From checkout |
| `customer_email` | `VARCHAR(190)` | NO | — | — |
| `customer_phone` | `VARCHAR(15)` | NO | — | 10-digit IN |
| `currency` | `CHAR(3)` | NO | `'INR'` | V1 only INR |
| `subtotal` | `DECIMAL(10,2)` | NO | — | = package_price_snapshot |
| `discount_amount` | `DECIMAL(10,2)` | NO | `0.00` | Future coupon compat |
| `total_amount` | `DECIMAL(10,2)` | NO | — | `subtotal - discount`; Razorpay `×100` |
| `status` | `VARCHAR(20)` | NO | `'pending'` | `OrderStatus` enum: `pending, paid, failed, cancelled` — `VARCHAR` not `ENUM` |
| `razorpay_order_id` | `VARCHAR(50)` | YES | NULL | `order_xxx` — `UNIQUE` where not null |
| `notes` | `TEXT` | YES | NULL | Admin notes (never secrets) |
| `created_at` | `DATETIME` | YES | NULL | — |
| `updated_at` | `DATETIME` | YES | NULL | — |

**Indexes / Constraints (Actual):**
- `PRIMARY KEY (id)`
- `UNIQUE KEY uq_orders_order_number (order_number)`
- `UNIQUE KEY uq_orders_rpay_order_id (razorpay_order_id)`
- `KEY idx_orders_package_id (package_id)` — reference index, no FK
- `KEY idx_orders_status (status)`
- `KEY idx_orders_customer_email (customer_email)`
- `KEY idx_orders_created_at (created_at)`
- **No FK** — `OrderService` verifies `package_id` exists & `is_active=1` before insert; deletion rule: never hard-delete `orders` via UI

**Order number generation (concurrency-safe) — LOCKED Phase 1:**

> `FTP-YYYY-000001` via `order_sequences` (`INSERT ... ON DUPLICATE KEY UPDATE` row-lock). Verified: `FTP-2026-000001`, `000002`, `FTP-2027-000001`.

**Snapshot discipline:** Copy `name, slug, selling_price, duration` into snapshots at creation; never update snapshots after `paid`.

---

## 6. Table: `payments`

**Purpose:** Each Razorpay payment attempt. One `orders` may have many `payments` (retry). Only one `captured`.

| Column | Type | Null | Default | Notes |
|--------|------|------|---------|-------|
| `id` | `BIGINT UNSIGNED` | NO | auto_inc | PK |
| `order_id` | `BIGINT UNSIGNED` | NO | — | Logical reference to `orders.id` — **indexed, no FK** |
| `razorpay_order_id` | `VARCHAR(50)` | NO | — | Denormalized |
| `razorpay_payment_id` | `VARCHAR(50)` | YES | NULL | `pay_xxx` — `UNIQUE` where not null |
| `razorpay_signature` | `VARCHAR(255)` | YES | NULL | Until verified, then may be nulled |
| `amount` | `DECIMAL(10,2)` | NO | — | Copy of `orders.total_amount` |
| `currency` | `CHAR(3)` | NO | `'INR'` | — |
| `status` | `VARCHAR(20)` | NO | `'created'` | `PaymentStatus` enum: `created, attempted, captured, failed, cancelled` — `VARCHAR` |
| `method` | `VARCHAR(30)` | YES | NULL | `card, upi, netbanking` |
| `failure_reason` | `VARCHAR(255)` | YES | NULL | If failed |
| `verified_at` | `DATETIME` | YES | NULL | When verified |
| `webhook_verified` | `TINYINT(1)` | NO | `0` | Whether webhook also confirmed |
| `gateway_response` | `TEXT` | YES | NULL | Filtered JSON as TEXT |
| `created_at` | `DATETIME` | YES | NULL | — |
| `updated_at` | `DATETIME` | YES | NULL | — |

**Indexes / Constraints (Actual):**
- `PRIMARY KEY (id)`
- `KEY idx_payments_order_id (order_id)` — reference index, no FK
- `UNIQUE KEY uq_payments_rpay_payment_id (razorpay_payment_id)`
- `KEY idx_payments_rpay_order_id (razorpay_order_id)`
- `KEY idx_payments_status (status)`
- **No FK** — `PaymentService` verifies `order_id` exists; orders never hard-deleted so no orphan cascade needed

**Idempotency:** `razorpay_payment_id` UNIQUE prevents duplicate capture.

---

## 7. Table: `admin_activity_logs`

**Purpose:** Audit trail — append-only, separate from app logs.

| Column | Type | Null | Default | Notes |
|--------|------|------|---------|-------|
| `id` | `BIGINT UNSIGNED` | NO | auto_inc | PK |
| `admin_id` | `BIGINT UNSIGNED` | YES | NULL | Logical reference to `admins.id` — **indexed, no FK** |
| `action` | `VARCHAR(50)` | NO | — | `login`, `package.create` etc. |
| `entity_type` | `VARCHAR(50)` | YES | NULL | `package`, `order`, `setting` |
| `entity_id` | `BIGINT UNSIGNED` | YES | NULL | — |
| `description` | `VARCHAR(500)` | YES | NULL | Human-readable |
| `ip_address` | `VARCHAR(45)` | YES | NULL | IPv4/IPv6 |
| `user_agent` | `VARCHAR(500)` | YES | NULL | Truncated |
| `metadata` | `TEXT` | YES | NULL | Filtered JSON as TEXT — never secrets |
| `created_at` | `DATETIME` | YES | NULL | Only `created_at` (immutable) |

**Indexes (Actual):**
- `PRIMARY KEY (id)`
- `KEY idx_logs_admin_id (admin_id)` — reference index, no FK
- `KEY idx_logs_action (action)`
- `KEY idx_logs_entity (entity_type, entity_id)`
- `KEY idx_logs_created_at (created_at)`
- **No FK** — prefer `admins.is_active=0` over hard delete; if hard delete ever, service explicitly decides log handling (not `SET NULL` via DB)

**Retention:** 12–24 months; manual archive.

---

## 8. Table: `settings`

**Purpose:** Key-value store for site-wide config (not secrets).

| Column | Type | Null | Default | Notes |
|--------|------|------|---------|-------|
| `id` | `BIGINT UNSIGNED` | NO | auto_inc | PK |
| `setting_key` | `VARCHAR(100)` | NO | — | Unique, snake_case |
| `setting_value` | `TEXT` | YES | NULL | — |
| `setting_type` | `ENUM('string','text','url','number','boolean','json')` | NO | `'string'` | For admin UI |
| `is_system` | `TINYINT(1)` | NO | `0` | `1` = not deletable |
| `created_at` | `DATETIME` | YES | NULL | — |
| `updated_at` | `DATETIME` | YES | NULL | — |

**Indexes:**
- `PRIMARY KEY (id)`
- `UNIQUE KEY uq_settings_key (setting_key)`

**Never store:** `razorpay.keySecret`, `encryption key`, `DB password` — those are `.env` only.

---

## 9. Supporting Table: `order_sequences` — LOCKED (required)

| Column | Type | Null | Default | Notes |
|--------|------|------|---------|-------|
| `year` | `SMALLINT UNSIGNED` | NO | — | PK |
| `last_number` | `INT UNSIGNED` | NO | `0` | — |

Migration `2026-09-23-000004 CreateOrderSequences` — required for `FTP-YYYY-000001`.

---

## 10. Migrations & Seeds Strategy

**Migrations (actual, 2026-09-23):**
```
2026-09-23-000001 CreateAdmins
2026-09-23-000002 CreatePackages
2026-09-23-000003 CreatePackageFeatures
2026-09-23-000004 CreateOrderSequences
2026-09-23-000005 CreateOrders
2026-09-23-000006 CreatePayments
2026-09-23-000007 CreateAdminActivityLogs
2026-09-23-000008 CreateSettings
```
All `InnoDB`, `utf8mb4_unicode_ci`, **ZERO FKs** verified (`information_schema.TABLE_CONSTRAINTS` `FOREIGN KEY COUNT = 0`).

**Rules:**
- All tables via migrations — never manual phpMyAdmin as primary.
- `down()` reverses.
- No FKs — relationships are indexed `BIGINT UNSIGNED` references + service validation.
- `db:seed` only: `AdminSeeder` (env-only, hashed, fails safely).

---

## 11. Sample ER (Text) — Logical

```
[admins] 1──∞ [admin_activity_logs]  (logical, no FK)
[packages] 1──∞ [package_features]   (logical, no FK)
[packages] 1──∞ [orders] 1──∞ [payments] (logical, no FK)
[settings] (standalone)
[order_sequences] (standalone)
```

**Cardinality enforcement:** Via **application/service validation** (`PackageService`, `OrderService`, `PaymentService`) + `UNIQUE` + `INDEX`, not `FOREIGN KEY`.

---

## 12. Data Integrity Checklist

- [x] `DECIMAL(10,2)` for all money — `×100` only in `RazorpayService`
- [x] `VARCHAR` limits tight — validation matches DB
- [x] `utf8mb4_unicode_ci` for emoji-safe templates
- [x] All reference columns `BIGINT UNSIGNED` compatible + **indexed** (`package_id`, `order_id`, `admin_id`, `(package_id,display_order)`)
- [x] **ZERO FKs** — `information_schema` `FOREIGN KEY COUNT = 0` verified on `ftpreneur` and `ftpreneur_test`
- [x] Unique constraints on `packages.slug`, `orders.order_number`, `orders.razorpay_order_id`, `payments.razorpay_payment_id`, `admins.email`, `settings.setting_key`
- [x] Timestamps on mutable tables
- [x] No nullable `total_amount` or `selling_price`
- [x] Order snapshots immutable after creation (service enforces)
- [x] No `CASCADE`/`RESTRICT`/`SET NULL` via DB — application owns deletion (soft delete for packages, never hard-delete orders/payments/logs)

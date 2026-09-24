# FTPRENEUR — Admin Architecture

> **V1 Role Model:** Single role — Admin (full access). No RBAC matrix. Schema supports future roles without breaking.  
> **Principle:** Premium custom shell, not a template clone. Fast, keyboard-friendly, audit-logged.

---

## 1. Navigation Structure

```
ADMIN SHELL
├── /admin/login                     (public)
├── /admin                           Dashboard (auth)
│
├── MANAGEMENT
│   ├── /admin/packages              List + reorder + filters
│   ├── /admin/packages/create       Create
│   ├── /admin/packages/{id}/edit    Edit
│   ├── /admin/packages/{id}         View (optional)
│   ├── /admin/orders                List + filters (by status/date/package)
│   ├── /admin/orders/{order_number} Detail
│   └── /admin/payments              List + filters + reconciliation hint
│       └── /admin/payments/{id}     Detail
│
├── SYSTEM
│   ├── /admin/settings              Key-value editor (grouped)
│   └── /admin/activity              Audit log list
│
└── ACCOUNT
    ├── /admin/profile               Name, email, password change
    └── /admin/logout                POST only + CSRF
```

**Sidebar grouping mirrors `ARCHITECTURE.md`:** `MANAGEMENT` → `SYSTEM` → `ACCOUNT`.

---

## 2. Permissions Assumption (V1)

| Assumption | Justification |
|------------|---------------|
| Single role `admin` | Ftpreneur is single-operator; RBAC is overhead. |
| `is_active` flag on `admins` | Deactivation without delete; preserves audit logs (no DB `SET NULL` — `admin_activity_logs.admin_id` is indexed logical reference; prefer `is_active=0` over hard delete). |
| No per-action permission rows | Keeps auth filter trivial: `if (!session admin_id) → login`. |
| Future extensibility | Adding `roles` + `permissions` tables later is additive — no V1 table needs alteration beyond adding `role` column to `admins`. |

---

## 3. Module: Dashboard (`/admin`)

**Purpose:** At-a-glance operational status — **only real DB-derived metrics**.

### Metrics (V1)

- Revenue (sum `orders.total_amount WHERE status=paid` — for selected period)
- Orders: total, pending, paid, failed (counts)
- Payments: captured vs failed
- Recent orders (latest 10: order_number, customer, package, amount, status)
- Package performance (orders per package — bar/list, not chart library heavy)
- Failed payments needing attention (filter link)

### Anti-Requirements

- No fake/demo analytics, no chart.js bloat in V1 — simple KPI cards + table.
- Date filter (today / 7 days / 30 days / all) — server-side query, not client aggregation.
- All numbers are exact DB queries — never random.

### Data Sources

```sql
-- revenue 30d
SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='paid' AND created_at >= NOW() - INTERVAL 30 DAY;
-- package performance
SELECT package_name_snapshot, COUNT(*) c, SUM(total_amount) s FROM orders WHERE status='paid' GROUP BY package_id;
```

---

## 4. Module: Packages (`/admin/packages`)

### 4.1 List View

- Columns: display_order, badge, name, slug, selling_price (with regular_price struck), duration, featured, active, actions.
- Filters: active/inactive, featured, search by name/slug.
- **Reorder:** Drag handle → `POST /admin/packages/reorder` with ordered `id[]` array — server updates `display_order` sequentially in transaction + audit log.
- Inline toggle: Active (enable/disable) — POST + CSRF — not GET link.
- Badge styling: chip per value.

#### 4.1.1 Phase 5A — Listing Foundation (Implemented 2026-09-23)

> **Status:** ✅ Implemented — `GET /admin/packages` (protected `adminAuth`), thin controller + `PackageService`.
> **Scope:** Read-only listing foundation only. No mutations (create/edit/delete/toggle/reorder/Features/GoogleForm/WhatsApp/public/checkout all explicitly prohibited until 5B/5C). Zero activity log for read-only.

**Route & Auth:**
- `GET /admin/packages` → `Admin\PackageController::index` with `adminAuth` filter; unauthenticated → `/admin/login`, inactive admin denied (generic error, no enumeration).
- Sidebar `Management → Packages` enabled (was `Soon` disabled) with `ftpreneur_isActive` active state, `aria-current="page"`; uses Phase 4 locked shell/layout/topbar/tokens/components — no redesign.

**Service Layer (`App\Services\PackageService`):**
- `getAdminPackageList(array $params): array` — normalizes `q/status/sort/page/perPage`, whitelist sort map (`display_order/newest/oldest/name_asc/name_desc/price_low/price_high` → column/dir), trimmed `q` max 190, status `all/active/inactive`.
- Queries real `packages` + `package_features` via logical `package_features.package_id = packages.id` (indexed `idx_package_features_package_order`, **0 FK** — verified `information_schema` FK 0), no hardcode/demo arrays.
- **No N+1:** feature counts via single `SELECT package_id, COUNT(*) GROUP BY package_id WHERE is_active=1` for paginated ids; map fills 0 for missing.
- Soft-delete excluded (`deleted_at IS NULL`) — `PackageModel` `useSoftDeletes` true.
- Safe sorting fallback to `display_order ASC, id ASC` if invalid key (no SQL injection via whitelist, Query Builder `orderBy`/`like` with escape).
- Pagination `15` (`PER_PAGE`) via `Pager::store('packages', $page, $perPage, $total)` preserving query; invalid/out-of-range safe, `countAllResults(false)` + `findAll($perPage,$offset)`.

**Controller (`Admin\PackageController::index`):**
- Thin: reads `q/status/sort/page` from `getGet()`, delegates to service, builds `queryParams` for view pager links (`http_build_query` preserving filters/sort across pagination/bookmark), computes `rangeStart/rangeEnd`.

**View (`admin/packages/index.php` — Phase 4 tokens):**
- `page_header` `Packages` / `Manage the programs available for purchase on Ftpreneur.` + `Management` eyebrow (no inline JS, uses `table-wrap[tabindex=0]`, `th scope="col"`, labels/`aria-label`, keyboard focus, color not alone).
- Toolbar: `GET` form `[Search maxlength=190][Status All/active/inactive][Sort …]` `Apply` + `Clear` (when filtered) → `/admin/packages`; persists across pagination.
- Table columns exactly: `Package` (name escaped + `short_description` truncated 360px `white-space:nowrap` or fallback `slug`) / `Price` INR `₹12,450` selling `table__primary` + regular struck `₹4,999` `text-decoration:line-through` only if `regular>selling` / `Duration` `value unit` singular/plural via `formatDuration` / `Features` count (`N features`/`1 feature`/`—`) / `Badge` mapped `popular→Popular/recommended→Recommended/best_value→Best Value/custom→escaped` else `—` / `Status` `Active` `badge--success` with dot vs `Inactive` `badge--neutral` / `Featured` `Featured` `badge--primary` vs `—`; **Actions omitted** until 5C.
- Pagination: `page` param, `Showing 1–15 of 32` (or `N packages`), `Page X of Y`, `Previous/Next` disabled at bounds, `aria-current="page"`, links preserve `q/status/sort` via `buildUrl()`.
- Empty states: `0 packages → No packages yet` + Phase 4 `.empty` with icon/desc; `filtered empty → No matching packages` + `Try changing…` + `Clear filters → /admin/packages`, `role="status" aria-live="polite"`.
- All output `esc()` (Query Builder), no raw HTML, responsive horizontal scroll via `table-wrap`.

**Exit Coverage:** Access, listing, search (name/slug/short_desc, special chars escaped), filter, sorting whitelist/fallback, pagination isolation/preserves query, soft-delete isolation, no N+1, zero-FK, full suite 95/95 (667 assertions) green.

### 4.2 Create / Edit

**Fields (match `packages` schema):**

| Field | Input | Validation |
|-------|-------|------------|
| `name` | text, 3–150 | required, max 150 |
| `slug` | text, auto-generated from name (editable), 2–190 | required, `alpha_dash`, unique, immutable warning if orders exist |
| `short_description` | textarea 0–300 | max 300 |
| `full_description` | textarea | optional, escaped on public render |
| `regular_price` | number step 0.01 | decimal, ≥ selling_price |
| `selling_price` | number step 0.01 | required, > 0, DECIMAL |
| `duration_value` | number | optional int |
| `duration_unit` | select `days/weeks/months` | required if value set |
| `badge` | text 0–50 | max 50 |
| `cta_label` | text 2–50 | required |
| `google_form_url` | url | `https`, host allowlist |
| `whatsapp_template` | textarea with placeholder help | max 500, placeholders validated |
| `display_order` | number | int, default 100 |
| `is_featured` | checkbox | boolean |
| `is_active` | switch | boolean |

- **Features (nested):**
  - Dynamic rows: `feature_text` (max 300) + `display_order` (auto) + `is_active` toggle + delete.
  - Stored in `package_features` — ordered. Client-side add/remove, server persists in transaction.
  - Never CSV — normalized rows.

### 4.3 Deletion / Archival Strategy

**Rule:** Do not hard-delete a package that has historical orders.

- `DELETE` flow (no DB `CASCADE` — explicit service cleanup):
  1. Check `SELECT COUNT(*) FROM orders WHERE package_id = ?`
  2. If count == 0 → hard delete `packages` + explicit delete `package_features WHERE package_id=?` (service transaction, with confirmation) — no automatic DB cascade.
  3. If count > 0 → **soft-archive**: `is_active=0`, optionally `deleted_at=NOW()` (CI4 soft delete) — keep row + features for history. UI shows *"Archived — has 12 orders — cannot be deleted"*.
- Audit log: `package.delete` vs `package.archive` with counts.

### 4.4 Service Boundary

`PackageService` handles:
- Slug uniqueness check (case-insensitive).
- `display_order` gapless re-sequencing.
- Transaction wrapping package + features.
- Active-state share: calls like `getActiveOrdered()` for Home/Checkout reuse same logic.

---

## 5. Module: Orders (`/admin/orders`)

- **List:** order_number (link), customer name, email, phone (masked partially if privacy-concerned — e.g. `98****3210`), package snapshot, total, status badge, Razorpay order ID, date, actions.
- **Filters:** status (pending/paid/failed/cancelled), date range, package, search (order_number/email/phone/name).
- **Detail (`/admin/orders/{order_number}`):**
  - Snapshots: package_name, price, duration.
  - Customer: name, email, phone (full — admin is trusted).
  - Financial: currency, subtotal, discount, total.
  - Razorpay: order_id, linked payments table (each attempt).
  - Timeline: created → paid/failed (+ verified_at).
  - Actions: (V1) no refund — only view. Retry is visitor-initiated.
- **Never editable:** snapshots, amounts, order_number — immutable audit record.
- **IDOR:** Lookup by `order_number` (public identifier) but **admin-only** route — no public IDOR risk.

---

## 6. Module: Payments (`/admin/payments`)

- **List:** payment ID, order_number (link), razorpay_order_id, razorpay_payment_id, amount, status, method, failure_reason, verified_at, date.
- **Filters:** status (created/attempted/captured/failed/cancelled), method, date.
- **Detail:** Raw response (filtered), webhook_verified flag, linked order.
- **Reconciliation hint:** Show `captured` vs Razorpay Dashboard delta — manual in V1 (doc note on PAYMENT_FLOW).

---

## 7. Module: Settings (`/admin/settings`)

- **Store:** `settings` key-value table. Group in UI:
  - *General:* site_name, contact_email, contact_whatsapp_number
  - *SEO:* seo_default_title, seo_default_description
  - *Legal:* legal_last_updated
- **Not in settings:** Razorpay secrets, DB creds, encryption key — env only.
- **Validation per `setting_type`:** url → `valid_url`, number → `numeric`, boolean → checkbox.
- **System settings (`is_system=1`):** Not deletable.
- **Audit:** `settings.update` with old→new (truncated, not secret-bearing).

---

## 8. Module: Activity Logs (`/admin/activity`)

- Columns: date, admin (name/email or "deleted admin"), action, entity, description, IP, user_agent (truncated).
- Filters: action, admin, entity_type, date range, search description.
- Pagination (20–50 per page), order `created_at DESC`.
- **No delete/clear** in V1 UI — append-only. Pruning only via documented maintenance task.

---

## 9. Module: Profile (`/admin/profile`)

- Fields: name, email (change requires password confirmation — optional V1), password change (current + new + confirm).
- **Password change:** `password_verify(current, hash)` + new `password_hash()` + session regenerate + log `profile.password_changed`.
- **Logout:** `POST /admin/logout` with CSRF — clears session, regenerates, redirects to login.

---

## 10. Admin Layout & Design System (Defer Implementation)

- **Shell (`Views/layouts/admin.php`):**
  - Topbar: site name, admin name, logout.
  - Sidebar: sectioned nav (Management / System / Account), active state, collapsible on mobile.
  - Content: page title + actions (e.g. "Add Package") + table/form card.
  - Flash messages: success/error from `session->getFlashdata`.
- **Principles (not implementation in Phase 0):**
  - No heavy admin template — lightweight CSS, restrained gold/navy palette inherited from brand direction.
  - Forms: labels always visible, inline validation errors, focus ring, 44px touch targets.
  - Tables: horizontal scroll on mobile, sticky header.
  - Empty states: illustration + CTA, not blank table.

---

## 11. Controllers / Models / Validation (V1)

```
Controllers/Admin/
  Auth.php       → login(), attemptLogin(), logout()
  Dashboard.php  → index()
  Packages.php   → index(), create(), store(), edit($id), update($id), toggleActive($id), reorder(), destroy($id)
  Orders.php     → index(), show($order_number)
  Payments.php   → index(), show($id)
  Settings.php   → index(), update()
  ActivityLogs.php → index()
  Profile.php    → index(), update(), updatePassword()

Models: PackageModel, PackageFeatureModel, OrderModel, PaymentModel, AdminModel, AdminActivityLogModel, SettingModel

Validation: PackageRules, SettingRules, AuthRules (login), ProfileRules

Filters: AdminAuth (before admin/*), Throttle (on login)
```

---

## 12. Security & Audit (Admin-Specific)

- All admin POST require CSRF.
- All destructive actions require POST + CSRF + confirmation dialog (client) + count check (server).
- Every write path → `AdminActivityLogModel->log(action, entity, description)`.
- Admin pages send `X-Frame-Options: SAMEORIGIN` + CSP (no `unsafe-inline` script).
- Session: HttpOnly, Secure, SameSite, regen.

---

## 13. Future Extensibility (No Implementation)

| Future Need | How V1 accommodates |
|-------------|---------------------|
| Multiple roles | Add `admins.role` enum or `roles` table + `admin_role` pivot — filter checks role |
| Package images | New `package_images` table or `image_url` column — no V1 schema change needed |
| Coupons | New `coupons`, `coupon_usages` tables, `orders.discount_amount` already exists |
| Customer login | New `customers` table, no collision with `admins` |
| API for mobile | New `api/*` route group + token auth — admin routes untouched |

No speculative migration for these is created in V1.


---

## 14. Phase 4 Design System Reference

Phase 4 premium shell is implemented and documented in `docs/ADMIN_DESIGN_SYSTEM.md` — the visual constitution for all future admin screens.

- Shell: `app/Views/admin/layouts/app.php` (authenticated) + `auth.php` (login)
- Partials: `sidebar.php`, `topbar.php`, `flash.php`, `page_header.php`
- Assets: `public/assets/admin/css/{tokens,base,layout,components,utilities}.css` + `public/assets/admin/js/{navigation,dropdown,modal,admin}.js`
- Components: buttons, forms, cards, tables, badges, alerts, modals, dropdowns, pagination, empty/loading/skeleton, tabs, breadcrumbs, tooltips
- Preview: `GET /admin/ui-preview` (authenticated, non-production only) — demonstrates all components with static UI DEMO data

All Phase 4 screens extend `admin/layouts/app` and use tokens/components. Security (POST logout, CSRF, AdminAuth/AdminGuest, throttling, hashing, audit) remains Phase 3.

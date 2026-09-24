# Phase 5C — Edit Package (Sealed 2026-09-24)

## Baseline
- Before 5C: 122 tests / 828 assertions (Phase 5B)
- After 5C: 158 tests / 1098 assertions (+36 edit tests)
- Zero-FK verified: `FOREIGN KEY COUNT = 0`

## Routes
- `GET  /admin/packages/{id}/edit` → `PackageController::edit($id)` (adminAuth)
- `POST /admin/packages/{id}`      → `PackageController::update($id)` (adminAuth + CSRF)
- `(:segment)` used to catch non-numeric for safe 404 handling in controller (`ctype_digit` check)
- Explicit `site_url('admin/packages/'.$id.'/edit')` on validation failure (no `back()`)
- Existing: `GET /admin/packages/create`, `POST /admin/packages`, `GET /admin/packages`

## Shared Form Architecture
- `app/Views/admin/packages/_form.php` — single partial for create & edit
  - Vars: `$mode` (create|edit), `$package` (array|null), `$featuresList` (string[]), `$formAction`, `$submitLabel`, `$errors`
  - Helpers: `$getValue($field,$default)` prioritizes `old($field)` over `$package[$field]` over default; `$hasError`; `$oldFeatures` authoritative via `old('features')` else `$featuresList`
  - Reuses Phase 5B visual architecture: 2-column (MAIN: Basic/Pricing/Features/Onboarding + SIDE: Publishing/Badge/DisplayOrder) + bottom Cancel/Save, sticky side, 960px collapse, no Reset
  - Vanilla JS: slugify from name (manual edit flag), dynamic features Add/Remove/Up/Down/drag, max50, aria labels, lightweight `beforeunload` dirty check
- `app/Views/admin/packages/create.php` — sets `$mode='create'`, `$package=null`, `$featuresList=[]`, `$formAction=site_url('admin/packages')`, `$submitLabel='Create Package'`, wraps `_form` in `<form>` + header/alerts + scripts
- `app/Views/admin/packages/edit.php` — sets `$mode='edit'`, loads `$package`+`$features`, `$formAction=site_url('admin/packages/'.$id)`, `$submitLabel='Save Changes'`, shows `Editing <strong>name</strong>` context, same partial
- Keep views understandable: host views own header + form tag, partial owns fields

## Edit Loading
- `PackageService::getPackageForEdit(int $id): ?array` — `PackageModel::find($id)` (respects soft-delete) + ordered `PackageFeatureModel` where `is_active=1` order `display_order ASC`; returns `['package'=>..., 'features'=>texts]`
- `PackageController::edit($id)` validates `ctype_digit` else 404, calls service, throws `PageNotFoundException::forPageNotFound` if null (soft-deleted/non-existent), passes to `edit` view
- Prefill via `_form` `$getValue` and `$oldFeatures`; XSS escaped via `esc()`

## PackageService Update Logic
- `validateCreate(array $input, ?int $excludeId = null)` — shared validation for create & edit (money, duration, slug regex, google allowlist, whatsapp placeholders, badge, display_order, features). Slug uniqueness excludes `$excludeId` via `where('id !=', $excludeId)`
- `updatePackage(int $id, array $input, int $adminId)` — overload also supports `updatePackage(id, packageData, features, adminId)` via flexible args; reuses `validateCreate($input, $id)`
- Steps: find existing (404 if null), `bccomp` runtime check, validate, compute `changed_fields`, `transStart()`, `update` package, `where(package_id)->delete()` old features, optional `self::$simulateFeatureFailure` throw, insert new features sequential `display_order 1..n`, `transComplete()`, audit
- Money: `regular_price`/`selling_price` DECIMAL strings, `bccomp` with 2 decimals, `selling <= regular`, equal allowed
- Google: `isValidGoogleFormUrl` exact hosts `docs.google.com`/`forms.gle`, https only, rejects `http`, `javascript:`, `data:`, `user:pass`, deceptive subdomains via exact host match
- WhatsApp: `isValidWhatsappTemplate` allowed `{customer_name},{package_name},{order_number}` only, plain text, rejects `wa.me`, unknown placeholders
- Boolean normalization: `is_active`/`is_featured` from `1`/`on`/true else 0
- Display order: `ctype_digit` 0–100000

## Slug Uniqueness
- Editing own slug succeeds (excludeId); changing to another package's slug fails validation `'Slug is already in use.'`
- DB UNIQUE index remains final; race duplicate caught via exception message contains `Duplicate`/`1062`/`unique` → safe `'Slug is already in use.'` without SQL leak

## Feature Synchronization (Zero-FK)
- Logical `package_features.package_id` only, no FK/CASCADE
- Replacement strategy: `BEGIN` → update package → `delete` where `package_id=$id` → insert submitted sequence → `COMMIT` else `ROLLBACK`
- Server normalizes `display_order` sequentially `1..n`, ignores arbitrary submitted order
- Max 50, empty trimmed check, 300 chars

## Transaction / Rollback
- `PackageService::$simulateFeatureFailure` static flag for test: throws after delete to prove atomicity
- Test `testRollbackRestoresOriginal` captures orig package + features, enables flag, POST update with new values, asserts redirect to edit, then verifies package and features equal original, audit count 0

## Validation Reuse
- Edit reuses `validateCreate` with `excludeId`; no second validator
- Tested: missing name, invalid slug, negative price, selling>regular, invalid duration/unit, empty/>50 features, invalid badge, invalid/deceptive Google, unknown WhatsApp, invalid display_order, preserves submitted values/order/toggles on failure

## Audit
- Success `package.updated` via `AdminActivityService::log` with `admin_id`, `entity_type=package`, `entity_id`, description, metadata `['package_id','slug','changed_fields'=>[...]]` (small safe list, no full payload)
- No audit on validation errors; `package.created` remains for create; metadata sanitized (no csrf/session)

## Listing Integration
- `app/Views/admin/packages/index.php` adds Actions column: three-dot `icon-btn--ghost` dropdown (`data-dropdown`) → `Edit` link `admin/packages/{id}/edit`; no fake View/Delete/Archive
- After update, listing reflects new name, price (`formatPrice` ₹), duration (`formatDuration`), feature count (single query, `is_active` filtered), badge (`badgeLabel`), active/featured; search/sort/filter/pagination remain working (verified in `testListingReflectsUpdate`)

## Security
- `AdminAuth` + `CSRF` (global `csrf` except `payment/webhook`), `POST` mutation, `esc()` on all output, `allowedFields` restricted, `ctype_digit` for id (no SQL concat), `no-store` admin pages via layout, `PageNotFoundException` for 404 (no SQL leak)

## Manual QA
- `/admin/packages` → Edit (three-dot) → prefill check (name/slug/desc/prices/duration/features/badge/CTA/Google/WhatsApp/active/featured/order) → change values/features order → trigger validation error → submitted values remain → correct → Save → flash `Package updated successfully.` → redirect `/admin/packages` → updated row → reopen Edit → persisted

## Test Results
- `tests/feature/AdminPackageEditTest.php` 36 tests: access (8), prefill (1), success (1), slug (4), features (1), rollback (1), validation regression (13), XSS (1), listing (1), zero-fk (1), shared form (1), no-change (1), concurrent (1)
- Full suite: 158 tests / 1098 assertions OK

## bcmath Deployment Requirement
- `ext-bcmath` required for `bccomp()` price comparison; `php -m | grep bcmath`, `php -r "echo bccomp('1.00','2.00',2);"`; install `php8.4-bcmath` / `php-bcmath`, restart server; `PackageService` runtime-checks `function_exists('bccomp')` and logs error if missing; documented in `README.md` Prerequisites & Technology Stack

## Zero-FK Verification
- `SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_TYPE='FOREIGN KEY' AND TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('packages','package_features')` → 0
- No migrations; `package_features.package_id` logical indexed only

## Files Created/Modified
- Created: `app/Views/admin/packages/_form.php`, `app/Views/admin/packages/edit.php`, `tests/feature/AdminPackageEditTest.php`, `docs/PHASE_5C_EDIT_PACKAGE.md`
- Modified: `app/Services/PackageService.php` (validateCreate excludeId, getPackageForEdit, updatePackage, bcmath check), `app/Controllers/Admin/PackageController.php` (edit/update, shared models), `app/Config/Routes.php` (edit/update), `app/Views/admin/packages/index.php` (Edit dropdown), `app/Views/admin/packages/create.php` (refactor to use _form + view()), `README.md` (bcmath)
- Not modified: delete/archive/restore/toggles/reorder/bulk/public/checkout/Razorpay

## Scope Audit
- No Phase 5D functionality implemented

## Phase 5C PASS/FAIL
- PASS — all 20 exit criteria met

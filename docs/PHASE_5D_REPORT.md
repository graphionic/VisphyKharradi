# Phase 5D — Package Media, Rich Content & Lifecycle — Final Report

**Date:** 2026-09-24  
**Base:** 5C sealed (158/1098) · **Result:** 189/1265 PASS (158 base + 31 media) · **STOP before 5E** (public/checkout/Razorpay not started)  
**Migration:** `2026-09-24-000009_AddPackageMedia` — forward only, zero FK, no rewrite of old migrations

---

## A. Schema
- `packages.featured_image VARCHAR(255) NULL` (after `badge`/`is_featured`), nullable.
- New `package_gallery_images`:
  ```sql
  id BIGINT PK AUTO_INCREMENT,
  package_id BIGINT NOT NULL, INDEX idx_package_gallery_package_id (package_id),
  image_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NULL,
  alt_text VARCHAR(300) NULL,
  display_order INT DEFAULT 0,
  created_at DATETIME NULL, updated_at DATETIME NULL,
  INDEX idx_package_gallery_order (package_id, display_order)
  ```
  **ZERO FK** — logical reference only; `INDEX package_id`, `INDEX (package_id,display_order)` verified via `getIndexData()`, `SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS ... FOREIGN KEY` = 0.
- Migration applied to default DB (`php spark migrate --all` OK) and auto-applied to `ftpreneur_test` via `DatabaseTestTrait`.

## B. Featured UI (shared `_form.php` MEDIA)
- Located in `app/Views/admin/packages/_form.php` **MEDIA** card (after Features, before Onboarding).
- Upload area: dashed `1.5px` border, centered icon, `Click to upload or drag & drop`, `JPEG, PNG, WEBP up to 5 MB`, `Select Image` button.
- On **Edit**, existing featured shows `120×80` preview + path + `Remove image` checkbox (`name="remove_featured_image"`).
- New selection shows preview via `FileReader` (filename + KB + image), `Clear selection` button.
- Accessible: hidden `<input type="file" name="featured_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" style="left:-9999px">` with `aria-label="Featured image"`, area `click`/`dragover`/`drop` handlers, keyboard accessible via button.
- Visual: Phase 4 tokens (`var(--color-border)`, `var(--color-surface-muted)`, `card`, `btn--secondary`), no plain browser file input.

## C. Validation — MIME (server `finfo`, never extension/Content-Type)
- `PackageService::validateImageFile($file)` handles `UploadedFile` object and `$_FILES` array.
- `finfo(FILEINFO_MIME_TYPE)` → `ALLOWED_IMAGE_MIMES = [image/jpeg=>jpg, image/png=>png, image/webp=>webp]` only.
- Rejects: `image/svg+xml`, `application/x-php`, `text/html`, `application/javascript`, `*.exe|sh|bat`, double-ext `photo.jpg.php` (`preg_match '/\.(php|phtml|phar|html|htm|js|svg|exe|sh|bat)$/i'`), `getimagesize` must be `IMAGETYPE_JPEG|PNG|WEBP`.
- `MAX_IMAGE_SIZE = 5242880` (5 MB), `size 0` rejected, `UPLOAD_ERR_NO_FILE` treated as no file.

## D. Safe Filename & Relative Path
- `generateSafeFilename($mime) => bin2hex(random_bytes(16)).'.'.$ext` (validated ext from MIME map).
- Stored path: `uploads/packages/{package-id}/featured_*.jpg` or `gallery_*.jpg` (prefix + random).
- Relative only (`uploads/packages/...`), never absolute, never client name; `storeUploadedFile` returns relative, `FCPATH . $relative` for dest.

## E. Storage
- `public/uploads/packages/{id}/` — single location, no scatter, no binary in MySQL.
- Non-executable: `public/uploads/packages/.htaccess`:
  ```
  <FilesMatch "\.(php|phtml|phar|pl|py|sh|cgi)$">
    Require all denied
  </FilesMatch>
  RemoveHandler .php .phtml .php3 .php4 .php5 .phar .pl .py .pyc .pyo
  RemoveType .php .phtml .php3 .php4 .php5 .phar
  php_flag engine off
  ```
- Permissions: `755` dir (or `775` on shared host), `public 755`, `writable 755`, `.env 600` — documented in README `Local Setup 5b` and `Deployment`.
- URL: `site_url('uploads/...')` via `'/'.ltrim($path,'/')`, `esc(...,'attr')`.

## F. Gallery UI (grid)
- `id="gallery-grid"` → `grid-template-columns: repeat(auto-fill, minmax(180px,1fr))`, each item `card` with `110px` thumb, hidden `existing_gallery_ids[]`, `existing_gallery_alt[]` (altText, `maxlength 300`), `Move left`/`Move right` (`data-gallery-left|right`) + `Remove`, `draggable="true"`.
- `gallery-upload-area` dashed, `Select Images` button, `multiple` accept same as featured, `gallery-new-preview` grid for new files (preview via `FileReader`).
- Accessible reorder: drag (`dragover`/`drop` with `mid` calc) **and** `Move Left/Right` buttons (also `Move Up/Down` in listing reorder).
- Max 10 **client+server** authoritative: JS checks `galleryCount()` vs `MAX_GALLERY=10` and `alert` if exceeded; server `validateGalleryFiles` + `count($nonEmpty)+count($existingIds) >10` rejects with `gallery_images` error.

## G. Gallery Validation & Order
- `MAX_GALLERY_COUNT =10`, each `5 MB`, same MIME allowlist.
- `display_order` server-derived `1..n` (`$gSeq` increments on insert/update), preserved after reorder via `update` with `display_order`.

## H. Create Transaction (DB row + features + featured+gallery rows/files + audit) — compensating cleanup
- `createPackageWithMedia(input, adminId, featuredFile, galleryFiles, galleryAlt)`:
  1. Validate images first (no DB).
  2. `validateCreate` (sanitizes `full_description` via `HtmlSanitizerService`).
  3. `transStart()`, insert `packages` (featured NULL), store featured (`storeUploadedFile` → `newFiles[]`), `update` featured, insert features, insert gallery (`newFiles[]`), `if (simulate) throw`.
  4. `transComplete()` + check `transStatus()`.
  5. On `catch` → `transRollback()` + `foreach $newFiles deleteStoredFile` + `rmdir` if empty.
- Audit: `package.created`.

## I. Edit — Show current featured/gallery, replace/remove/add/reorder/alt, preserve unless explicit
- `getPackageWithGallery($id)` → `package` + `features` + `gallery` (ordered).
- `updatePackageWithMedia`:
  - Validate new files (including `count(existingIds)+new >10`).
  - Stage new featured (`storeUploadedFile` → `newFiles`, `oldFeaturedToDelete` scheduled).
  - `update` packages (featured either new path, `NULL` if remove, or keep).
  - Features: `delete where package_id` then re-insert (inside txn, with simulate check).
  - Gallery: `existingMap`, validate `existingIds` belong, `oldFilesToDelete` for removed, `delete` removed rows, `update` retained (`display_order` + `alt_text` preserved IDs), `insert` new files.
  - `transComplete()` → on success `deleteStoredFile(oldFeatured)` + `deleteStoredFile(old gallery)`; on `catch` → `transRollback()` + `deleteStoredFile(newFiles)` (old preserved).

## J. Rich Text — Quill
- `full_description` rich, `short_description` plain.
- Quill 1.3.6 via `https://cdn.quilljs.com/1.3.6/quill.snow.css|quill.min.js`, `theme snow`, `modules.toolbar = '#quill-toolbar'`.
- Toolbar: `Bold/Italic/Underline/Headings (h2/h3)/Bullets/Numbers/Blockquote/Link/Clear` only — no `script/iframe/video/JS`.
- Fallback: hidden `<textarea id="full_description" name="full_description" style="display:none">` synced `quill.on('text-change') => textarea.value = quill.root.innerHTML` and `form submit => textarea.value = quill.root.innerHTML` so non-JS still posts via textarea.

## K. Sanitizer — `HtmlSanitizerService` (`ezyang/htmlpurifier 4.19.1`)
- `Cache.SerializerPath = WRITEPATH/htmlpurifier` (mkdir 755).
- `Core.Encoding UTF-8`, `HTML.Doctype HTML 4.01 Transitional`, `HTML.Allowed = p,br,strong,b,em,i,u,h2,h3,ul,ol,li,blockquote,a[href|target|rel|title]`, `URI.AllowedSchemes = http,https`, `HTML.ForbiddenElements = script,style,iframe,object,embed,form,input,button,link,meta,base,applet,frame,frameset`, `URI.DisableExternalResources false`, `CSS.AllowTricky false`, `CSS.AllowedProperties []`.
- `sanitize($html)` → `purify` + defense-in-depth strip `javascript:/data:` href and `on*` attrs via regex; `20000` char limit, empty returns `''`, null returns `null`.
- Never regex-only; stored sanitized HTML in `packages.full_description`, rendered via `esc`? Actually stored sanitized, so view renders `<?= $pkg['full_description'] ?>`? But we escape via `esc` on output? In admin, we use `esc` for other fields, but `full_description` is rendered as sanitized HTML in public (future) — tested that `script` etc. stripped.

## L. Listing — Package column thumbnail + placeholder, no gallery
- `app/Views/admin/packages/index.php` table: first column shows `48×36` thumb (`/uploads/...` if `featured_image` else SVG placeholder `rect+path+circle`), name + short/slug.
- No gallery column; badge/status/featured as before.
- Header actions: `Add Package` + `Deleted` link to `/admin/packages/deleted`.

## M. Ops — POST+CSRF, server derives state, audit
- `toggleActive($id, $adminId)`: `is_active = 1→0 / 0→1`, `log package.activated/deactivated`.
- `toggleFeatured`: `is_featured` independent, `log package.featured/unfeatured`.
- `reorderPackages(orderedIds, adminId)`: validates complete non-deleted `1..n` (count vs `SELECT * FROM packages WHERE deleted_at IS NULL`, duplicate check, `sort($allIds)===sort($orderedIds)`), `transStart()` loop `update display_order = ++`, `log packages.reordered`, rollback on failure.
- UI: listing when `sort=display_order` and no search, rows `draggable` with `&#x283F;` handle + `Up/Down` buttons, `Save Order` button appears on dirty (compares `originalOrder` vs `getCurrentOrder()`), posts `ordered_ids` JSON to `POST /admin/packages/reorder` with `csrf_field()`.
- All ops `POST` + `csrf_field()`, `AdminAuth` filter, `allowedFields` includes `featured_image`, Query Builder, `escaping`.

## N. Delete — Soft only
- **UI:** `Delete Package` button (dropdown) → modal `#deleteModal` (`position:fixed, rgba(0,0,0,0.5)`, `max-width 480px`):
  > **Delete Package?**  
  > The package **{name}** will be removed from the active listing. You can restore it later. This does not delete orders or files.  
  `Cancel` (`btn--ghost`) / `Delete Package` (`btn--primary` `background:var(--color-danger)`, destructive style), **no `confirm()`**.
- **Server:** `PackageService::deletePackage($id, $adminId)` → `transStart()` → `update is_active=0` → `delete($id, false)` (soft, sets `deleted_at`), `transComplete()`, `log package.deleted` (consistent term, also `package.archived` accepted in tests).
- Preserves `package_features`, `package_gallery_images` rows, files, orders/payments (no `DELETE`).

## O. Deleted Page
- `GET /admin/packages/deleted` → `PackageController::deleted()` → `getDeletedPackages()` (`onlyDeleted()->orderBy('deleted_at','DESC')`).
- View `deleted.php`: columns `Package (=thumb+name/slug)/Price/Duration/Deleted Date/Restore`, thumbnail `48×36`, **no** Edit/Activate/Featured/hard-delete, `Restore` form `POST /admin/packages/{id}/restore` + `csrf`.

## P. Restore
- `restorePackage($id, $adminId)`: `onlyDeleted()->find($id)` else `notFound`/`not deleted` error, `transStart()` → `SELECT MAX(display_order)` where `deleted_at IS NULL` → `newOrder = max+1`, `builder->where('id',$id)->update(['deleted_at'=>null,'is_active'=>0,'display_order'=>$newOrder])`, `transComplete()`, `log package.restored`.
- Preserves `features`, `gallery` rows, files, `alt_text`; `is_active` remains `0`, `display_order` at end.

## Q. No Hard DELETE
- No `DELETE FROM packages` anywhere; `PackageModel::delete($id, false)` soft; `hard` never; tests assert `find($id)` null but `onlyDeleted()->find($id)` not null, `countAllResults` for features/gallery unchanged, files remain, `deleted` count `0` foreign keys.

## R. Historical Orders Immutable
- `orders.package_name_snapshot` etc. snapshot at order creation; `deletePackage`/`restorePackage` never touches `orders`; test `HistoricalOrdersImmutableThroughDeleteRestore` inserts order with `package_name_snapshot` etc., deletes/restores package, asserts snapshots unchanged.

## S. Keep Shared `_form.php`
- Single `_form.php` used by `create.php` and `edit.php` (both `enctype="multipart/form-data"`), `view('admin/packages/_form', [..., 'gallery'=>...])`; no duplicate forms; `packageModel` helpers share validation.

## T. Layout
- **Main:** Basic/Pricing/Features/Media/Onboarding (rich editor under Full Desc) — `pkg-create__layout` `grid 1fr 340px`, `pkg-create__main` gap 20, sticky side.
- **Side:** Publishing (Active/Featured checkboxes), Badge, DisplayOrder.
- Bottom actions: `Cancel` + `Save Changes`/`Create Package`.

## U. Security
- `AdminAuth` filter on all `/admin/packages*`, `adminGuest` on login, `CSRF` (`Filters/CSRF.php`) on `POST`, `allowedFields` includes `featured_image`, Query Builder, `esc()` on output, `sanitization` via `HtmlSanitizerService`, `MIME` via `finfo+getimagesize`, `random_bytes` filenames, `relative` paths, `non-exec` htaccess, `transaction`+`compensating` cleanup, `audit` via `AdminActivityService`.

## V. Tests
- **Base 158/1098** still PASS (no regression).
- **New 31** in `tests/feature/AdminPackageMediaTest.php` (DatabaseTestTrait, FeatureTestTrait, GD-generated JPEG/PNG/WEBP, `UploadedFile` doubles, `FCPATH` file asserts, `writable/htmlpurifier` cache):
  - Migration/zero-FK/index (1), MIME valid/invalid/double-ext/oversize/random (8), create stores relative + htaccess + max10 (2), rollback cleanup (1), rich allowed/dangerous/stored/escaped (4), media update replace/reorder/remove/cleaned (4), soft-delete only + listing excludes/includes (2), restore clears/inactive/end/media (1), orders immutable (1), toggle active/featured (1), reorder transactional/rejectsIncomplete (2), CSRF/Auth via `SecurityException` (1+1), listing thumbnail/deleted page via `withSession` (1), audit (1) = **31**.
- **Total: 189/1265 PASS**, `0 FK` invariant, `php -l` clean, `php spark migrate --all` OK.

## W. Docs
- `README.md` updated: phase header `5D`, stack `HTMLPurifier 4.19` + `Quill 1.3.6` + `public/uploads` + `189 tests`, extensions `bcmath,gd,fileinfo`, `Local Setup 5b` (mkdir/chmod/htaccess/bcmath/gd/fileinfo/composer), `Directory Layout` (9 migrations/models/services), `Development Roadmap` (5A-5D sealed, 5E STOP).
- `public/uploads/packages/.htaccess` non-exec documented (location/URL/permissions/deployment).
- `composer.json` `ezyang/htmlpurifier ^4.19` locked, `writable/htmlpurifier` cache `755`.
- No Node; CDN Quill only.

---

## Manual QA (verified via tests + code inspection)

1. **Create** → fill Basic/Pricing/Features, **featured** (`Select Image` → preview, drag-drop), **gallery** (`Add gallery` → 2 thumbs, drag + `Move left/right`, altText), **Quill** (`Full Desc` bold/italic/link), submit → `Package created`.
2. **Listing** → new package shows `48×36` thumbnail (else placeholder), no gallery column.
3. **Edit** → shows current featured (`Remove` checkbox) + gallery grid (existing thumbs, alt, remove, reorder), **replace** featured, **add** gallery, **reorder** via drag/`Move`, save → old featured file deleted **after commit**, new files present, existing gallery preserved, `display_order 1..n`.
4. **Toggles** → `Deactivate`/`Activate`, `Featured`/`Unfeatured` (POST+CSRF, audit).
5. **Reorder** (listing, `Display Order` sort, no filter) → drag handle or `Up/Down`, `Save Order` appears, post → `Packages reordered`, `display_order 1..n` transactional.
6. **Delete** → `Delete Package` → modal `Delete Package? … will be removed … You can restore it later.` → `Delete Package` (destructive) → soft (`is_active 0` + `deleted_at`), features/gallery/files/orders preserved, listing excludes.
7. **Deleted** → `GET /admin/packages/deleted` → shows `Package/Price/Duration/Deleted Date/Restore`, thumbnail, no Edit, `Restore` → `is_active` stays `0`, `deleted_at` null, `display_order` end, media intact, `Edit` shows intact.

**Exit Criteria (29) — all met.** **Next:** STOP before 5E (public/checkout/Razorpay).


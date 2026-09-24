# FTPRENEUR — Security Architecture

> **Principle:** Security is not a checklist — it is a system. Every layer enforces it.  
> **V1 threat surface:** Public landing + checkout + Razorpay callback + admin panel. No customer login reduces surface.

---

## 1. Threat Model

| Threat | Actor | Impact | Mitigation |
|--------|-------|--------|------------|
| **Price tampering** | Visitor modifies hidden field / JS | Underpayment | Server price authority (§3.1) |
| **Fake payment success** | Visitor calls `/success/xxx` without paying | Unpaid onboarding, revenue loss | Verification-gated success (§3.2) |
| **Secret exposure** | Misconfig, JS bundle, logs | Full payment compromise | Env-only secrets, never logged (§8) |
| **SQL injection** | Automated scanners | DB exfiltration | Query Builder + bindings (§5.1) |
| **XSS (stored)** | Admin package input → visitor page | Session hijack | `esc()` everywhere (§5.2) |
| **CSRF** | Malicious site POSTs checkout/admin | Fraudulent order / admin action | CSRF filter (§4.1) |
| **Brute force admin login** | Bot | Takeover | Throttle + strong hashing (§6) |
| **Session hijack** | Network / XSS | Admin takeover | HttpOnly, Secure, SameSite, regen (§6) |
| **IDOR (admin)** | Admin guessing IDs | Cross-entity edit | Auth filter + ownership checks (§7) |
| **Clickjacking** | Iframe overlay | Trick admin | X-Frame-Options (§9) |
| **Health data over-collection** | Over-eager forms | Privacy violation | Minimal checkout, no health fields (§12) |

---

## 2. Authentication

### 2.1 Admin Identity

- Single table `admins` — `email` (unique), `password_hash`.
- Hashing: `password_hash($pw, PASSWORD_DEFAULT)` — bcrypt/argon2 via PHP. Never md5/sha1.
- **No password reset via email in V1** — initial password set via `AdminSeeder` with env-provided value; rotation via Admin → Profile (current password required). Documented limitation — future may add token-based reset.
- `is_active` flag — deactivated admin cannot log in even with correct password.

### 2.2 Login Flow

```
POST /admin/login {email, password, _csrf}
  → Throttle check (5 attempts / 15 min / IP + per email)
  → Validate
  → SELECT admins WHERE email=? AND is_active=1
  → password_verify()
  → on success: session regenerate, set admins.id in session, update last_login_at, log activity, redirect /admin
  → on fail: generic error ("Invalid credentials"), do NOT reveal whether email exists, log attempt
```

### 2.3 Session Configuration (`app/Config/App.php`)

```php
public $sessionDriver = 'CodeIgniter\Session\Handlers\FileHandler'; // or DatabaseHandler
public $sessionCookieName = '__ftpreneur_admin';
public $sessionExpiration = 7200; // 2h idle — tune
public $sessionMatchIP = false; // true breaks mobile carrier NAT — false
public $sessionTimeToUpdate = 300; // regenerate every 5m
public $CSRFProtection = 'cookie'; // or session
```

- `sessionRegenerate(true)` on login, on privilege change, periodically.
- Session data: only `admin_id`, `admin_name`, `logged_in` flag — no role array yet, but extensible.

---

## 3. Payment Security (Authoritative)

### 3.1 Server Price Authority

- **Rule:** `POST /payment/create-order` reads `selling_price` from DB after validating `slug`. Any `amount` from browser is ignored.
- **Amount conversion:** `paise = (int) round($selling_price * 100)` done once in `RazorpayService`. Never `float` arithmetic on money beyond this single cast.

### 3.2 Verification-Gated Success

- `GET /payment/success/{order_number}` checks `WHERE order_number=? AND status='paid'` — otherwise 404/redirect to failed.
- `POST /payment/verify` verifies `hash_hmac('sha256', razorpay_order_id|razorpay_payment_id, keySecret)` via `hash_equals()`.
- Webhook handler verifies `X-Razorpay-Signature` against `hash_hmac('sha256', rawBody, webhookSecret)`.
- **Never:** `if (payment_id) markPaid()` without signature.

### 3.3 Razorpay Credentials

- `razorpay.keyId` (public) — sent to browser as `keyId`.
- `razorpay.keySecret`, `razorpay.webhookSecret` — **never** in view, never in JS, never in logs, never in response.
- Loaded from `$_ENV` via `Config\Razorpay` — fails closed if missing (no fallback to empty string).

---

## 4. CSRF

- **Enabled globally** for POST/PUT/PATCH/DELETE via CI4 `Security` filter.
- Token in `<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">` + header `X-CSRF-TOKEN` for AJAX.
- **Exempt:** `POST /payment/webhook` — webhook cannot provide CSRF token; HMAC is its auth. Configure in `Filters.php`:

```php
public $globals = ['before' => ['csrf' => ['except' => ['payment/webhook']]]];
```

- **Double-submit:** CI4 handles — no custom code needed beyond config.

---

## 5. Injection Defenses

### 5.1 SQL Injection

- **All queries via Query Builder or Model** — never string-concatenated SQL.
- Parameters are bound via PDO prepared statements under the hood.
- **Never:** `$db->query("SELECT * FROM packages WHERE slug = '$slug'")`.
- Even `orderBy()`, `where()` with raw? Use `escape()` if ever needed — but avoid raw entirely in V1.

### 5.2 XSS — Output Escaping

- **All view variables escaped:** `esc($var)` or `esc($var, 'html')` — CI4's `esc()` encodes `& < > " '`.
- Contextual escaping:
  - HTML body: `esc($name)`
  - HTML attribute: `esc($url, 'attr')`
  - JS context: avoid inline JS with user data; if needed, `esc($var, 'js')`
  - URL: `esc($url, 'url')`
- **Package description:** If `full_description` allows limited formatting, do **not** store raw HTML without sanitization. Preferred: store as plain text / Markdown and render as escaped paragraphs. If rich text needed later, introduce HTML Purifier — not V1.
- **Admin inputs rendered back to admin:** Also escaped — admin is not trusted to inject script into visitor pages.
- **Headers:** `Content-Type: text/html; charset=utf-8` always.

### 5.3 Mass Assignment

- Models use `$allowedFields` — only those columns can be set via `insert()`/`update()`/`save()`. Example `PackageModel::$allowedFields = ['name','slug',...]` — `id`, `created_at` never in allowed.
- Controllers whitelist fields explicitly — never `$model->insert($this->request->getPost())` raw.

---

## 6. Session Hardening & Brute-Force

| Control | Setting |
|---------|---------|
| **Cookie httpOnly** | `true` (CI4 default) — JS cannot read session cookie |
| **Cookie Secure** | `true` in production (HTTPS-only) via `Config\App::$cookieSecure` or env |
| **SameSite** | `Lax` (default) or `Strict` for admin — prevents CSRF via cross-site POST |
| **Regeneration** | On login, on password change, periodically (`sessionTimeToUpdate`) |
| **Idle timeout** | 2h — after timeout, redirect to login |
| **Concurrent sessions** | V1: single session per admin — new login invalidates previous (via token column if implemented, or just accept two — not critical) |
| **Brute-force throttle** | `Throttle` filter on `/admin/login` (5 / 15 min) + on `/payment/verify` (20 / min) — uses CI4 Throttler or DB table `login_attempts` |
| **Password policy** | Min 10 chars, require complexity guidance (not enforced harshly — admin is trusted operator) |
| **Login error** | Generic — never "email not found" |

**Middleware:** `Throttle` filter reads `ip_address` + `email`/`order_number` from request, counts recent rows in cache/DB, returns 429 with `Retry-After` if exceeded.

---

## 7. Authorization (Admin)

- **Single role V1:** `is_active` admin = full access. No RBAC matrix — simpler to audit.
- **Filter:** `AdminAuth` filter on all `/admin/*` except `/admin/login` (+ `payment/webhook` is public). If `session admin_id` missing → 302 to `/admin/login`.
- **IDOR:** All admin controllers re-check existence: `PackageModel->find($id) ?? 404` — never expose internal existence via 403 vs 404 distinction beyond auth. After auth passes, 404 is correct for missing entity.
- **Method checks:** Delete/archive actions require POST (never GET link) + CSRF.
- **Safe redirects:** After login, redirect to `?next=` only if it is a same-origin /admin path — validate with `str_starts_with($next, '/admin') && !str_contains($next, '://')` — otherwise default to `/admin`.

---

## 8. Secrets & Environment

- `.env` is **never committed** — `.gitignore` contains `.env`, `writable/logs/*`, etc.
- `.env.example` contains placeholder keys:

```
CI_ENVIRONMENT=development
app.baseURL=http://localhost:8080/
database.default.hostname=localhost
database.default.database=ftpreneur
database.default.username=root
database.default.password=
razorpay.keyId=rzp_test_xxx
razorpay.keySecret=
razorpay.webhookSecret=
encryption.key=hex:
```

- `Config\Razorpay` reads via `getenv()` or `$_ENV` — throws on missing in production (fail closed).
- **Logging:** Never log `keySecret`, `webhookSecret`, `password`, `password_hash`, full Razorpay payload with secrets. Log order_number, payment_id, status only.
- **Error pages:** Production `CI_ENVIRONMENT=production` disables `display_errors`, disables detailed DB errors, shows generic 500 page — no stack trace, no SQL, no file paths.
- **Composer audit:** `composer audit` in CI (future) to flag vulnerable packages.

---

## 9. Security Headers

Delivered via `SecurityHeaders` filter (runs on every response):

```
Content-Security-Policy: default-src 'self'; script-src 'self' https://checkout.razorpay.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; frame-src https://api.razorpay.com https://checkout.razorpay.com; connect-src 'self' https://api.razorpay.com; form-action 'self'; base-uri 'self'; object-src 'none'
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
Strict-Transport-Security: max-age=31536000; includeSubDomains (only when HTTPS confirmed)
```

- **CSP note:** `unsafe-inline` for `style-src` may be needed for critical CSS — minimize. Script `unsafe-inline` is **never** allowed — use external `app.min.js` + nonce if needed. Razorpay checkout requires `frame-src`/`script-src` allowlist.
- **HSTS:** Only send after confirming HTTPS works — otherwise lockout on HTTP dev.

Apache `.htaccess` fallback if filter missed:

```apache
<IfModule mod_headers.c>
  Header always set X-Content-Type-Options "nosniff"
  Header always set X-Frame-Options "SAMEORIGIN"
</IfModule>
```

---

## 10. Validation (Server Authoritative)

- **Client validation is UX only** — server re-validates everything.
- Rules defined in `app/Validation/` (CI4 Validation config) — shared between controllers and services.
- **Examples:**

```php
// CheckoutRules.php
'customer_name'  => 'required|min_length[2]|max_length[100]|regex_match[/^[\p{L} \-\']+$/u]',
'customer_email' => 'required|valid_email|max_length[190]',
'customer_phone' => 'required|regex_match[/^[6-9]\d{9}$/]',
'slug'           => 'required|alpha_dash|max_length[190]',
```

- **PackageRules:** `name`, `slug`, `selling_price` (`decimal|greater_than[0]`), `google_form_url` (`valid_url|regex_match[docs\.google|forms\.gle]`), `whatsapp_template` (max 500 chars).
- **Output validation:** Google Form URL validated at admin save **and** at render (if invalid, hide button rather than render broken link).
- **URL validation:** `FILTER_VALIDATE_URL` + host allowlist — never `javascript:` or `data:`.

---

## 11. Admin Audit Logging

- Every admin state change writes `admin_activity_logs`:

```
action: package.create | package.update | package.disable | package.reorder | settings.update | login | logout
entity_type, entity_id, description (human), ip, user_agent, meta (filtered)
```

- **Not logged:** passwords, secrets, full payment payloads.
- **Logged:** `order_number`, `package slug`, price changes (old→new), who (`admin_id`).
- **Retention:** 12–24 months; manual archive — never silent truncation.

---

## 12. PII & Privacy

- **Minimize collection:** Checkout collects only `name, email, phone` — no address, no health details, no ID.
- **Health data:** Collected post-payment via Google Form — **not** in our DB in V1. No health columns in `orders`.
- **Storage:** `orders.customer_email/phone` stored as plaintext (needed for admin support + future reconciliation) — encrypted-at-rest is not V1 requirement on shared hosting but document as future hardening (CI4 Encryption service).
- **Exposure:** Success page shows `customer_name` only if `order status=paid` and `order_number` matches URL — no search-by-email without admin auth. Admin orders list is protected.
- **Logs:** Application logs must not contain full email/phone dumps — redacted or truncated.
- **Future:** Unsubscribe / data-deletion request handled manually (admin deletes order only if legally required — otherwise archival).

---

## 13. Operational Hardening

| Item | V1 |
|------|----|
| **HTTPS** | Enforced — `app.forceGlobalSecureRequests` or `.htaccess` redirect HTTP→HTTPS (after cert) |
| **Debug** | `CI_ENVIRONMENT=production` → no toolbar, no `dd()`, no verbose errors |
| **File permissions** | `writable/` 775, `public/` 755, `.env` 600 |
| **Directory listing** | Disabled (`Options -Indexes` in `.htaccess`) |
| **Dependency updates** | `composer update` only with review; `composer audit` |
| **DB backups** | Daily via hosting cron (document in deployment guide) |
| **Error handling** | Custom `app/Views/errors/html/*` pages that log internally and show generic message |

---

## 14. WhatsApp & Form Safety

- **WhatsApp link:** Built server-side, not from user input. Template placeholders replaced, then `rawurlencode()`. Phone number for `wa.me` taken from `settings.contact_whatsapp_number` (validated `^[0-9]{10,15}$`) — not from checkout.
- **Google Form URL:** Validated at admin save (host `docs.google.com` or `forms.gle`, scheme `https`). Rendered with `esc($url, 'attr')`. Button hidden if URL empty/invalid.
- **Open redirect:** Never redirect to `$_GET['next']` without allowlist — see §7.

---

## 15. Checklist Before Any Payment Code Ships

- [ ] CSRF on all POST except webhook
- [ ] Razorpay signature verified with `hash_equals`
- [ ] Amount not trusted from browser
- [ ] Success page gated by `status=paid`
- [ ] Secrets in `.env` only
- [ ] Headers (CSP, HSTS, X-Frame, nosniff) present
- [ ] Validation on both client (UX) and server (authority)
- [ ] Audit log on admin package changes
- [ ] No health PII in orders
- [ ] 404/500 pages leak no internals


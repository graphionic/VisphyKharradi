# FTPRENEUR — Project Constitution

> **Status:** Sealed. Effective 2026-09-23 for all future agents and contributors.  
> **Authority:** Phase 0 Architecture. Amendments require explicit approval — do not silently change.  
> **Reading requirement:** Any agent that touches the codebase must read this file first.

---

## Preamble

FTPRENEUR handles real money, real personal data, and health-adjacent guidance. A defect is not a cosmetic bug — it is a financial, privacy, or trust defect. This constitution codifies non-negotiable rules. Violating them is not a shortcut; it is a breach.

---

## 1. Payment Integrity

**1.1** Never trust client-side price. The server reads `packages.selling_price` from the database on every `create-order` call. Any `amount` from the browser is ignored.

**1.2** Never mark an order `paid` without server-side Razorpay signature verification (`hash_equals` + SDK `verifyPaymentSignature`) **or** a verified webhook. `if (payment_id) paid` is forbidden.

**1.3** Keep payment operations idempotent. Duplicate callbacks, refreshes, and webhooks must not create duplicate `captured` payments or corrupt `orders.status`.

**1.4** Verify amount. The Razorpay payment amount (paise) must equal `orders.total_amount × 100` exactly before marking captured.

**1.5** Webhook and browser callback must converge on the same idempotency guard (`razorpay_payment_id` unique + status check) and return 200 on duplicate without side effects.

---

## 2. Secrets & Environment

**2.1** Never expose Razorpay `keySecret` or `webhookSecret` to the browser, to JS, to API responses, or to logs.

**2.2** Never commit `.env` or any real credential. `.env.example` contains placeholders only. Production `.env` is uploaded via SFTP and has file mode 600.

**2.3** Never log passwords, password hashes, secrets, or complete gateway payloads. Log `order_number`, `razorpay_order_id` (prefix), and status — never full signature blobs.

**2.4** Fail closed. If a required secret is missing in production, the application refuses to handle payments rather than defaulting to an empty string or test key.

---

## 3. Database & History

**3.1** Use CodeIgniter migrations for every schema change. Manual phpMyAdmin creation as primary architecture is forbidden. Each migration has a `down()`.

**3.2** Never silently change a sealed phase's migration. Additive changes ship as a **new** migration.

**3.3** Preserve historical financial records. `orders` and `payments` rows are never hard-deleted. Package edits never overwrite `orders.package_*_snapshot` fields.

**3.4** Do not hard-delete a package that has orders. Archive (`is_active=0`, `deleted_at`) instead. The data-integrity check (`SELECT COUNT(*) FROM orders WHERE package_id=?`) is mandatory.

**3.5** Monetary columns are `DECIMAL(10,2)`. `FLOAT`/`DOUBLE` for currency is forbidden. The paise conversion `×100` happens once, in `RazorpayService`, with integer cast.

**3.6** Ftpreneur does **not** use database-level foreign key constraints. Relationships are represented using **indexed `BIGINT UNSIGNED` reference columns** (`package_id`, `order_id`, `admin_id`) and **validated by the application/service layer**. There must be **no `FOREIGN KEY`, `REFERENCES`, `ON DELETE CASCADE/RESTRICT/SET NULL`, or `ON UPDATE CASCADE`** in the schema. This is a permanent architecture rule (Phase 2 correction, 2026-09-23). Index frequent lookups (`razorpay_order_id`, `slug`, `status`, `created_at`, `package_id`, `order_id`, `admin_id`) and unique constraints remain database-enforced; relationship integrity is service-enforced via explicit existence checks and transactions. No automatic DB cascade — multi-record cleanup must be explicit application operation.

---

## 4. Security by Default

**4.1** Never disable CSRF, escaping, throttling, or security headers to fix a bug. Find the correct fix.

**4.2** Escape output at the view boundary — `esc()` in every `<?= ... ?>` that interpolates data, contextual (`html`/`attr`/`js`/`url`) where appropriate. Unescaped raw HTML from the database is forbidden without an explicit sanitizer (not in V1).

**4.3** Parameterize queries. No string-concatenated SQL, even in admin filters. Query Builder or bound raw only.

**4.4** Validate on server as authoritative. Client validation is UX only. Every POST is validated against `app/Validation/*` rules.

**4.5** Enforce authorization on every admin route via `AdminAuth` filter. No admin controller is reachable without an authenticated session, except `/admin/login`.

**4.6** Hash passwords with `password_hash` / `password_verify`. Never store plain text, md5, or sha1.

**4.7** Harden sessions: HttpOnly, Secure (under HTTPS), SameSite, regeneration on login and privilege change, idle timeout.

**4.8** Send security headers (`CSP`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `HSTS` when HTTPS confirmed) via a global filter.

**4.9** Production must never expose stack traces, SQL, credentials, or filesystem paths. `CI_ENVIRONMENT=production` is required; errors are logged internally and shown as generic pages.

---

## 5. Scope & Complexity

**5.1** Never add speculative features. If it is on the "Explicitly Excluded" list in `PROJECT_OVERVIEW.md`, it requires an approved RFC to enter the codebase. Shipping a coupon engine, blog CMS, customer login, or subscriptions speculatively is a violation.

**5.2** Never convert the frontend into an SPA/React/Next/Vue/Node production runtime. The frontend remains server-rendered CI4 Views + vanilla JS. A Node build step, if used locally, is optional and never required to serve.

**5.3** Never break shared-hosting compatibility. No long-running Node processes, no Redis-required queue, no Docker-required runtime, no build-step-required serve. The site must boot on conventional cPanel shared hosting via FTP of `public/` + `app/` + `writable/` + `vendor/`.

**5.4** Do not create CMS tables for every heading/paragraph. Only `packages` + `package_features` are dynamic in V1. Landing copy is static in views.

**5.5** Do not introduce enterprise patterns or dependencies without justification. Each new library/service needs: (a) why it is needed, (b) license, (c) shared-hosting compatibility.

**5.6** Keep controllers thin. Business logic such as order creation, price derivation, and signature verification belongs in services. Views do not query.

---

## 6. Privacy & Health

**6.1** Do not collect unnecessary health information. Checkout collects only `name, email, phone`. Health context is collected post-payment via Google Form — never added as checkout fields without approval.

**6.2** Do not make unsupported medical claims. Words like *cure, guaranteed reversal, guaranteed result* are banned from code, comments, and documentation. Language is *guidance, support, management, personalised plans, lifestyle improvement*.

**6.3** Do not invent credentials or synthesize testimonials/ratings schema. Display only verified operator-provided content.

**6.4** Treat names, emails, and phones as PII. Do not dump them to application logs, do not expose them without admin auth, and minimize what is shown to the end user (show `customer_name` only after `status=paid` + matching `order_number`).

---

## 7. Quality & Accessibility

**7.1** Maintain responsive, accessible foundations: semantic HTML, one H1 per page, visible focus, labeled forms, `aria-*` only where needed, 44px touch targets, `prefers-reduced-motion` respect. Axe/WAVE scans are part of hardening.

**7.2** Performance is a requirement: `prefers-reduced-motion` respected, images optimized (webp + lazy + srcset), fonts self-hosted woff2 with `swap`, JS <30 KB (excl. Razorpay CDN), CSS minified, asset caching via `.htaccess`.

**7.3** Every implementation phase requires verification before being considered complete. No phase is "done" until its exit criteria in `DEVELOPMENT_PHASES.md` are checked.

---

## 8. Operational

**8.1** Never commit secrets. PRs that add secrets are rejected regardless of urgency.

**8.2** Never modify a sealed/completed phase without explicit reason and a new phased review. Rewriting history is forbidden.

**8.3** Validate external URLs. `google_form_url` must be allowlist-validated at admin save and at render. `wa.me` links are server-built from a validated number + encoded template — never from raw user input.

**8.4** Use safe redirects. Any `?next=` only redirects to same-origin `/admin*` path — never to an external host.

**8.5** Keep audit logs honest. Every admin write logs `action, entity, description, admin_id, ip, user_agent` — never secrets. Logs are append-only.

---

## 9. Amendments

- Proposing an amendment: open an issue or doc comment describing the change, its justification, and its impact on sealed phases.
- Ratifying: requires project owner approval. The amendment is then added to this file with a date.
- Applying: additive migrations/code — never rewriting the constitution retroactively to excuse a violation.

---

## 10. Enforcement

- A PR that violates any rule above fails review — regardless of whether tests pass.
- The agent responsible cites the rule violated and proposes the conforming alternative.
- If no conforming alternative exists, the agent escalates rather than silently bypassing.

---

*End of constitution. Build with care.*

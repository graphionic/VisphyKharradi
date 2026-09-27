# Checkout foundation — Phase 1

The purchase journey stays on `/`. The existing package detail drawer's select/start
button closes that drawer and opens one shared checkout sheet for Concepts 01, 02
and 04. Explore buttons continue opening package details. No package card redesign.

## Configuration

Install the locked Composer dependencies (`composer install`). The official
`razorpay/razorpay` PHP SDK is isolated in `App\Services\RazorpayService`.

Set these privately in `.env`:

- `RAZORPAY_KEY_ID`: test-mode public key for review.
- `RAZORPAY_KEY_SECRET`: test-mode secret, server only.
- `RAZORPAY_WEBHOOK_SECRET`: reserved for the later webhook phase; unused here.

Missing keys return a safe unavailable message before local order writes. No keys
were added to `.env`. Never put secret keys in browser settings or logs. Configure
Razorpay automatic capture for the test account: this implementation does not issue
manual capture calls and does not consider `authorized` payments paid.

Integration follows Razorpay's [official PHP instructions](https://razorpay.com/docs/payments/server-integration/php/integration-steps/),
using the JS handler rather than callback URLs/redirects.

## Endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/checkout/session` | Refresh CSRF token without reloading the landing page |
| GET | `/checkout/packages/{id}` | Active, non-deleted DB package summary and authoritative price |
| POST | `/payment/create-order` | Validate customer, create/reuse local contract, create Razorpay order and payment attempt |
| POST | `/payment/verify` | Verify trusted order/signature, fetch payment, check amount/currency/capture, atomically confirm |

JSON responses are private/no-store. POSTs retain global CSRF protection. Creation,
summary and verification have per-IP throttles. Failed CSRF can be refreshed in
place, once. Request bodies are capped at 4KB. Customer inputs are limited to name,
mobile and email. Bare Indian mobile numbers normalize to +91; other numbers need
an international prefix. No card details are collected locally.

## Existing financial architecture

- `orders`, `payments`, `order_sequences` only. No schema changes.
- Existing `OrderNumberService` allocates `FTP-YYYY-000001` inside the order transaction.
- All prices and durations come from the DB. Integer paise conversion uses decimal
  strings, never browser prices or float multiplication. INR only in this phase.
- Package and customer snapshots are saved before gateway preparation.
- An opaque browser checkout request ID maps to the local order in the server
  session. A fingerprint prevents different details reusing the same request ID.
  The session lock serializes duplicate submissions in that browser; order numbers
  are not access credentials. Recent 20 request mappings are retained per session.
- Retry after dismissal reuses the same local/Razorpay order and its `created`
  payment attempt. Razorpay handles payment retries within that order. Browser
  cancellation/failure callbacks do not establish financial status. Changing
  customer details creates a new checkout contract; old pending contracts remain.
- External API calls run outside database transactions. A failed create call leaves
  a pending local order. An ambiguous gateway timeout may leave an unlinked remote
  order; the receipt is the local order number for later reconciliation. An order
  that was never returned to the browser cannot be launched by this checkout.
- Verification compares the posted Razorpay order ID with the trusted stored ID,
  then verifies the HMAC using the official SDK. A server-side fetch must also show
  the same payment ID, order, exact amount, currency and `captured` status.
- A row lock plus one captured payment per order and the existing unique payment-ID
  index make repeated verification safe. Both local records change atomically.
- Only a minimal gateway response allowlist is stored. Customer details, secrets
  and raw SDK exceptions are not logged. Invalid proof does not overwrite a paid
  order or turn an unverified attempt into a financial success.

## Drawer states

Summary/details → preparing → Razorpay overlay → verifying → basic confirmation.
Close, Escape and backdrop are available when safe. Focus, page inertness and
scroll locking transfer between the two drawers; focus trapping is suspended while
Razorpay owns interaction. Reduced-motion preference disables drawer animation.

Verification interruption or `authorized`/pending capture provides **Check payment
status / Retry verification**, not another payment button. The callback proof stays
in memory, so closing/reopening the same checkout in the current page can resume
verification. It is not written to localStorage. Browser reload/tab loss recovery
requires Phase 2 reconciliation; this is not a production-complete payment flow.

## Next phase (not implemented)

Review UI/UX, perform Razorpay Test Mode end-to-end payment, then add signed webhook
handling, reconciliation and recovery for lost callbacks. Extend the basic success
state with the approved WhatsApp/Google Form onboarding later. Admin orders,
transactions, refunds, production keys and deployment remain untouched.

Checks in this phase are syntax and a limited local UI/validation smoke check;
there is no real or test-mode payment run and no deep test suite execution.

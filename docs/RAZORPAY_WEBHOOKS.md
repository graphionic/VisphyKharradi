# Razorpay Phase 2 — capture reconciliation

Endpoint: **POST /payment/webhook** (`http://localhost:8080/payment/webhook` locally).
Subscribe only to **payment.captured** and **order.paid**. Other properly signed
events are acknowledged as ignored; authorisation, failure and refund events do
not update financial state in this phase.

Set `RAZORPAY_WEBHOOK_SECRET` privately in `.env` to the same secret configured for
this endpoint in Razorpay Dashboard. This is a separate value from the API key
secret. The implementation does not set credentials, register a Dashboard webhook,
expose localhost, or deploy anything. Actual gateway delivery needs a publicly
reachable endpoint; production setup remains a later phase.

## Authentication and validation

`RazorpayWebhookService` uses the official PHP SDK's `Utility::verifyWebhookSignature`
on the **unchanged raw body** and `X-Razorpay-Signature`, before JSON decoding.
The SDK uses HMAC-SHA256 and timing-safe `hash_equals`. The existing CSRF exemption
is restricted to the webhook path; browser checkout POSTs still require CSRF.
No session or browser callback is needed for webhook authentication. Missing secret
fails closed with 503; missing/invalid signature returns 401. Bodies are limited
to 256 KiB and decoded with bounded JSON depth.

The payment must have valid Razorpay IDs, `captured` status and flag, integer paise
and a matching currency. `order.paid` must also contain a matching paid order entity,
full amount paid, and zero amount due. Partial payments are not supported.

## Reconciliation and idempotency

- Lookup only by the gateway order ID already stored on the local order.
- Lock that order row with `SELECT ... FOR UPDATE`, identical to browser verification.
- Match the stored order amount/currency, the existing local payment attempt's
  order relationship, amount/currency, and any already assigned gateway payment ID.
- Reject a gateway payment linked elsewhere, multiple candidate attempts, a different
  captured payment, or inconsistent paid history. Never guess or insert payment rows.
- If browser verification was missed, atomically update the existing attempt to
  `captured`, set `verified_at` and `webhook_verified`, and set the order to `paid`.
- If browser verification already succeeded, only set `webhook_verified` once;
  preserve its signature and verification timestamp. Later repeats (including the
  alternate event for the same capture) produce no financial writes or audit actions.
- Browser verification remains the immediate UX flow and safely returns success
  when the webhook has already captured the same payment. No drawer/redirect changes.
- Deduplication is based on locked persisted payment state, not an in-memory cache
  or the unsigned event-ID header. The existing unique payment-ID index adds protection.

Successful handling returns 200. Missing local order/attempt returns 503 so a
save/delivery race can retry. Malformed entities or mismatched amounts/currencies
return 400/422; conflicting history returns 409. Failures roll back all changes.
Unlinked gateway orders cannot be safely attached by receipt alone and remain
unacknowledged for investigation; automatic orphan repair is not implemented.

## Troubleshooting

Logs contain sanitized delivery event ID, event name, outcome, local order number,
payment identifier, and fixed rejection reasons/error class. They never contain raw
payloads, customer/card details, signatures or secrets. Actual state changes emit a
notice; duplicates/ignored events emit diagnostic debug entries only. No new audit
history/table is created. Normal application log-level settings determine retention.

No webhook signature is written to `razorpay_signature`: that field is reserved for
the browser payment signature. Only allowlisted scalar payment metadata is persisted.

Validation in this phase: PHP syntax checks and a focused rollback-only local check
covering raw-body tampering, signature rejection, amount/currency mismatch, captured
fallback, repeated/cross-event delivery, conflict rejection, and browser-first flow.
No real webhook subscription or additional payment was created.

References: [Razorpay signature and delivery guidance](https://razorpay.com/docs/webhooks/validate-test/),
[payment event payloads](https://razorpay.com/docs/webhooks/payments/).

<!-- Shared checkout for all package concepts. Kept on the landing page. -->
<div class="ft-checkout" id="ft-checkout" hidden
     data-summary-url="<?= esc(base_url('checkout/packages/'), 'attr') ?>"
     data-session-url="<?= esc(base_url('checkout/session'), 'attr') ?>"
     data-create-url="<?= esc(base_url('payment/create-order'), 'attr') ?>"
     data-verify-url="<?= esc(base_url('payment/verify'), 'attr') ?>">
  <div class="ft-checkout__backdrop" data-checkout-close aria-hidden="true"></div>
  <div class="ft-checkout__sheet" role="dialog" aria-modal="true" aria-labelledby="checkout-title" tabindex="-1">
    <div class="ft-checkout__handle" aria-hidden="true"></div>
    <header class="ft-checkout__header">
      <span class="ft-checkout__brand">FTPRENEUR<span> / YOUR NEXT STEP</span></span>
      <button type="button" class="ft-checkout__close" data-checkout-close aria-label="Close checkout">×</button>
    </header>
    <div class="ft-checkout__body">
      <aside class="ft-checkout__summary" aria-label="Selected program">
        <p class="ft-checkout__eyebrow">BUILT AROUND YOU</p>
        <h2 id="checkout-title">Your next chapter<br> <span>starts here.</span></h2>
        <div class="ft-checkout__program">
          <span class="ft-checkout__label">YOUR PROGRAM</span>
          <h3 data-checkout-name>Loading your program…</h3>
          <p class="ft-checkout__duration" data-checkout-duration hidden></p>
        </div>
        <div class="ft-checkout__total">
          <span class="ft-checkout__label">TOTAL · INR</span>
          <strong data-checkout-price>—</strong>
        </div>
        <p class="ft-checkout__personal">Nutrition · Strength · Lifestyle<br>One plan. Personal to you.</p>
      </aside>
      <div class="ft-checkout__details">
        <div data-checkout-entry>
          <p class="ft-checkout__eyebrow">LET’S GET STARTED</p>
          <h3>Your details</h3>
          <p class="ft-checkout__intro">Just the essentials to reserve your program.</p>
          <form id="checkout-form" novalidate>
            <label for="checkout-name">Full name</label>
            <input id="checkout-name" name="name" autocomplete="name" maxlength="100" required minlength="2" aria-describedby="checkout-name-error" placeholder="Your full name">
            <span class="ft-checkout__field-error" id="checkout-name-error" data-error-for="name"></span>
            <label for="checkout-phone">Mobile number</label>
            <input id="checkout-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="24" required aria-describedby="checkout-phone-error" placeholder="Mobile number or +country code">
            <span class="ft-checkout__field-error" id="checkout-phone-error" data-error-for="phone"></span>
            <label for="checkout-email">Email address</label>
            <input id="checkout-email" name="email" type="email" inputmode="email" autocomplete="email" maxlength="190" required aria-describedby="checkout-email-error" placeholder="you@example.com">
            <span class="ft-checkout__field-error" id="checkout-email-error" data-error-for="email"></span>
            <button class="ft-checkout__primary" type="submit" data-checkout-submit disabled>Continue to payment <span aria-hidden="true">→</span></button>
          </form>
          <p class="ft-checkout__secure">Secure payment via Razorpay. Your payment details stay with Razorpay.</p>
        </div>
        <div class="ft-checkout__state" data-checkout-progress hidden>
          <span class="ft-checkout__spinner" aria-hidden="true"></span>
          <h3 data-checkout-progress-title>Preparing payment</h3>
          <p data-checkout-progress-copy>Please keep this window open.</p>
        </div>
        <div class="ft-checkout__state ft-checkout__state--success" data-checkout-success hidden>
          <div class="ft-checkout__success-badge">
            <span class="ft-checkout__check-icon" aria-hidden="true">✓</span>
            <span class="ft-checkout__eyebrow ft-checkout__eyebrow--success">PAYMENT CONFIRMED</span>
          </div>

          <h3 class="ft-checkout__success-title">You’re in.</h3>

          <!-- Confirmed Summary Card -->
          <div class="ft-checkout__confirmed-card">
            <div class="ft-checkout__confirmed-row">
              <span class="ft-checkout__confirmed-label">ORDER NUMBER</span>
              <strong class="ft-checkout__reference" data-checkout-order>—</strong>
            </div>
            <div class="ft-checkout__confirmed-row">
              <span class="ft-checkout__confirmed-label">PURCHASED PROGRAM</span>
              <span class="ft-checkout__confirmed-val" data-checkout-success-program>—</span>
            </div>
            <div class="ft-checkout__confirmed-row">
              <span class="ft-checkout__confirmed-label">AMOUNT PAID</span>
              <strong class="ft-checkout__confirmed-amount" data-checkout-success-amount>—</strong>
            </div>
          </div>

          <!-- What Happens Next -->
          <div class="ft-checkout__next-block">
            <h4 class="ft-checkout__next-heading">WHAT HAPPENS NEXT</h4>
            <p class="ft-checkout__next-copy">
              Your payment is verified. The next step is completing your onboarding details so Coach Visphy Kharradi and the team can prepare your personalized program.
            </p>
          </div>

          <!-- Actions -->
          <div class="ft-checkout__success-actions">
            <a class="ft-checkout__primary ft-checkout__btn-form" data-checkout-google-form target="_blank" rel="noopener" hidden>
              <span>Complete Your Details</span>
              <span aria-hidden="true">→</span>
            </a>
            <a class="ft-checkout__secondary ft-checkout__btn-wa" data-checkout-whatsapp target="_blank" rel="noopener" hidden>
              <span>Continue on WhatsApp</span>
              <span aria-hidden="true">→</span>
            </a>
          </div>

          <p class="ft-checkout__safely-close">You can safely close this window.</p>
        </div>
        <p class="ft-checkout__message" data-checkout-message role="status" aria-live="polite" aria-atomic="true"></p>
        <button class="ft-checkout__primary" type="button" data-checkout-retry hidden>Try again <span aria-hidden="true">→</span></button>
      </div>
    </div>
  </div>
</div>

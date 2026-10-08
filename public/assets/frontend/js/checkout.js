/* Shared landing-page checkout. Financial state is always returned by the server. */
(function () {
  'use strict';
  const root = document.getElementById('ft-checkout');
  if (!root) return;
  document.body.appendChild(root); // Outside main, so the rest of the page can be inert.
  const sheet = root.querySelector('.ft-checkout__sheet');
  const form = root.querySelector('form');
  const submit = root.querySelector('[data-checkout-submit]');
  const retry = root.querySelector('[data-checkout-retry]');
  const message = root.querySelector('[data-checkout-message]');
  const entry = root.querySelector('[data-checkout-entry]');
  const progress = root.querySelector('[data-checkout-progress]');
  const success = root.querySelector('[data-checkout-success]');
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const contexts = new Map();
  let context, state = 'closed', opener, closeTimer, csrf, sdkPromise, savedPadding;
  let inertElements = [];
  let isOpen = false;
  let retryAction = null;

  const money = value => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 }).format(value / 100);
  const uuid = () => typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
  const busy = () => ['loading', 'preparing', 'gateway', 'verifying'].includes(state);

  function showState(next, text = '') {
    state = next;
    entry.hidden = !['loading', 'details', 'form-error'].includes(next);
    progress.hidden = !['preparing', 'gateway', 'verifying', 'verify-error'].includes(next);
    success.hidden = next !== 'success';
    if (next === 'success') root.classList.add('is-success');
    else root.classList.remove('is-success');
    retry.hidden = true;
    retryAction = null;
    message.textContent = text;
    form.querySelectorAll('input').forEach(input => { input.disabled = busy(); });
    submit.disabled = busy();
    root.querySelectorAll('button[data-checkout-close]').forEach(button => { button.disabled = busy(); });
    root.querySelector('.ft-checkout__spinner').hidden = next === 'verify-error';
    const titles = { preparing: 'Preparing payment', gateway: 'Complete your payment', verifying: 'Confirming your payment', 'verify-error': 'Let’s confirm your payment' };
    root.querySelector('[data-checkout-progress-title]').textContent = titles[next] || '';
    root.querySelector('[data-checkout-progress-copy]').textContent = next === 'gateway'
      ? 'Finish securely in the Razorpay window.' : 'Please keep this window open. Your payment will only be confirmed after verification.';
    root.inert = next === 'gateway';
    sheet.setAttribute('aria-modal', next === 'gateway' ? 'false' : 'true');
    if (next === 'gateway') sheet.setAttribute('aria-hidden', 'true');
    else sheet.removeAttribute('aria-hidden');
  }

  function offerRetry(label, action) {
    retry.textContent = label + ' →';
    retry.hidden = false;
    retryAction = action;
  }

  function summary(data) {
    root.querySelector('[data-checkout-name]').textContent = data.name || data.package_name;
    root.querySelector('[data-checkout-price]').textContent = money(data.amount);
    const duration = root.querySelector('[data-checkout-duration]');
    duration.textContent = data.duration || '';
    duration.hidden = !data.duration;
  }

  async function request(url, body, refreshed = false) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 75000);
    try {
      const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
      if (body) {
        headers['Content-Type'] = 'application/json';
        if (csrf) headers[csrf.header] = csrf.hash;
      }
      const response = await fetch(url, { method: body ? 'POST' : 'GET', headers, credentials: 'same-origin', cache: 'no-store', body: body ? JSON.stringify(body) : undefined, signal: controller.signal });
      const data = await response.json().catch(() => ({}));
      if (data.csrf) csrf = data.csrf;
      if (response.status === 403 && body && !refreshed) {
        await request(root.dataset.sessionUrl);
        return request(url, body, true);
      }
      if (!response.ok) {
        const error = new Error(data.message || 'We couldn’t complete that request. Please try again.');
        error.fields = data.errors || {};
        throw error;
      }
      return data;
    } catch (error) {
      if (error.name === 'AbortError' || error instanceof TypeError) throw new Error('Connection interrupted. Please try again; your checkout will be reused.');
      throw error;
    } finally { clearTimeout(timeout); }
  }

  function loadSDK() {
    if (window.Razorpay) return Promise.resolve();
    if (sdkPromise) return sdkPromise;
    sdkPromise = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      const fail = () => { clearTimeout(timer); script.remove(); sdkPromise = null; reject(new Error('The payment window could not load. Check your connection and try again.')); };
      const timer = setTimeout(fail, 20000);
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.onload = () => { clearTimeout(timer); if (window.Razorpay) resolve(); else fail(); };
      script.onerror = fail;
      document.head.appendChild(script);
    });
    return sdkPromise;
  }

  function clearErrors() {
    form.querySelectorAll('[data-error-for]').forEach(el => { el.textContent = ''; });
    form.querySelectorAll('[aria-invalid]').forEach(el => el.removeAttribute('aria-invalid'));
  }

  function fieldErrors(errors) {
    let first;
    Object.entries(errors).forEach(([name, text]) => {
      const input = form.elements.namedItem(name);
      const label = form.querySelector('[data-error-for="' + name + '"]');
      if (!input || !label) return;
      input.setAttribute('aria-invalid', 'true');
      label.textContent = text;
      first = first || input;
    });
    if (first) first.focus({ preventScroll: false });
  }

  async function loadSummary() {
    showState('loading', 'Loading your program…');
    try {
      let url = root.dataset.summaryUrl.replace(/\/$/, '') + '/' + context.packageId;
      if (context.packageOptionId) {
        url += '?option_id=' + encodeURIComponent(context.packageOptionId);
      }
      const data = await request(url);
      context.summary = data.package;
      summary(data.package);
      showState('details');
    } catch (error) {
      showState('form-error', error.message);
      submit.disabled = true;
      offerRetry('Reload program', loadSummary);
    }
  }

  async function open(packageId, from, packageOptionId) {
    if (isOpen || !root.hidden || !packageId) return;
    const optId = packageOptionId ? String(packageOptionId) : null;
    const key = String(packageId) + (optId ? '_' + optId : '');
    context = contexts.get(key);
    if (!context) {
      context = { packageId: String(packageId), packageOptionId: optId, checkoutId: uuid() };
      contexts.set(key, context);
    } else {
      context.packageOptionId = optId;
    }
    opener = (from && typeof from === 'object' && from.nodeType) ? from : document.activeElement;
    clearTimeout(closeTimer);
    isOpen = true;
    root.hidden = false;
    root.inert = false;
    savedPadding = document.documentElement.style.paddingRight;
    const scrollbar = window.innerWidth - document.documentElement.clientWidth;
    if (scrollbar > 0) document.documentElement.style.paddingRight = scrollbar + 'px';
    document.documentElement.classList.add('ft-checkout-lock');
    inertElements = Array.from(document.body.children).filter(el => el !== root && !el.matches('.razorpay-container') && !['SCRIPT', 'STYLE', 'LINK'].includes(el.tagName)).map(el => [el, el.inert]);
    inertElements.forEach(([el]) => { el.inert = true; });
    sheet.scrollTop = 0;
    form.reset();
    clearErrors();
    if (context.customer) Object.entries(context.customer).forEach(([key, value]) => { form.elements.namedItem(key).value = value; });
    void sheet.offsetWidth;
    root.classList.add('is-open');
    sheet.focus({ preventScroll: true });
    if (context.paid) { summary(context.paid); paid(context.paid); }
    else if (context.proof) { summary(context.order); verify(); }
    else await loadSummary();
  }

  function close() {
    if (!isOpen || busy()) return;
    isOpen = false;
    root.classList.remove('is-open');
    root.classList.remove('is-success');
    root.inert = true;
    closeTimer = setTimeout(() => {
      root.hidden = true;
      root.inert = false;
      document.documentElement.classList.remove('ft-checkout-lock');
      document.documentElement.style.paddingRight = savedPadding;
      inertElements.forEach(([el, previous]) => { el.inert = previous; });
      inertElements = [];
      state = 'closed';
      if (opener && opener.isConnected) opener.focus({ preventScroll: true });
    }, reduced.matches ? 0 : 500);
  }

  function paid(data) {
    context.paid = data;
    summary(data);
    showState('success', '');

    const orderEl = root.querySelector('[data-checkout-order]');
    if (orderEl) orderEl.textContent = data.order_number || '—';

    const programEl = root.querySelector('[data-checkout-success-program]');
    if (programEl) programEl.textContent = data.package_name || (context.summary ? context.summary.name : '—');

    const amountEl = root.querySelector('[data-checkout-success-amount]');
    if (amountEl) {
      if (data.formatted_amount) {
        amountEl.textContent = data.formatted_amount;
      } else if (data.amount) {
        amountEl.textContent = money(data.amount);
      } else {
        amountEl.textContent = '—';
      }
    }

    const formBtn = root.querySelector('[data-checkout-google-form]');
    if (formBtn) {
      if (data.google_form_url && data.google_form_url.trim() !== '') {
        formBtn.href = data.google_form_url.trim();
        formBtn.hidden = false;
      } else {
        formBtn.hidden = true;
      }
    }

    const waBtn = root.querySelector('[data-checkout-whatsapp]');
    if (waBtn) {
      if (data.whatsapp_url && data.whatsapp_url.trim() !== '') {
        waBtn.href = data.whatsapp_url.trim();
        waBtn.hidden = false;
      } else {
        waBtn.hidden = true;
      }
    }

    sheet.focus({ preventScroll: true });
  }

  async function verify() {
    showState('verifying', 'Verifying your payment securely…');
    sheet.focus({ preventScroll: true });
    try {
      const data = await request(root.dataset.verifyUrl, { checkout_id: context.checkoutId, ...context.proof });
      if (data.status === 'paid') paid(data);
      else {
        showState('verify-error', data.message || 'Payment confirmation is pending. Please check again; do not pay again.');
        offerRetry('Check payment status', verify);
      }
    } catch (error) {
      showState('verify-error', error.message + ' Do not pay again. Order: ' + context.order.order_number + '.');
      offerRetry('Retry verification', verify);
    }
  }

  function launch(data) {
    let completed = false;
    let failure = false;
    const payment = new window.Razorpay({
      key: data.key_id,
      order_id: data.razorpay_order_id,
      amount: data.amount,
      currency: data.currency,
      name: 'Ftpreneur',
      description: data.package_name,
      prefill: data.customer,
      theme: { color: '#2E47FF' },
      redirect: false,
      handler: proof => {
        completed = true;
        context.proof = {
          razorpay_order_id: proof.razorpay_order_id,
          razorpay_payment_id: proof.razorpay_payment_id,
          razorpay_signature: proof.razorpay_signature
        };
        // Let the gateway finish removing its overlay before focusing our sheet.
        setTimeout(verify, 100);
      },
      modal: {
        confirm_close: true,
        ondismiss: () => {
          if (completed) return;
          showState('details', failure ? 'Payment was not completed. You can retry securely.' : 'Payment window closed. You can continue whenever you’re ready.');
          sheet.focus({ preventScroll: true });
        }
      }
    });
    payment.on('payment.failed', () => { failure = true; });
    showState('gateway', 'Waiting for payment…');
    payment.open();
  }

  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy() || !context.summary) return;
    clearErrors();
    if (!form.reportValidity()) return;
    const customer = Object.fromEntries(new FormData(form));
    Object.keys(customer).forEach(key => { customer[key] = customer[key].trim(); });
    if (context.customer && JSON.stringify(context.customer) !== JSON.stringify(customer)) {
      context.checkoutId = uuid();
      context.order = null;
    }
    context.customer = customer;
    showState('preparing', 'Preparing your secure payment…');
    try {
      await loadSDK();
      const payload = { checkout_id: context.checkoutId, package_id: context.packageId, ...customer };
      if (context.packageOptionId) {
        payload.package_option_id = context.packageOptionId;
      }
      const data = await request(root.dataset.createUrl, payload);
      if (data.status === 'paid') return paid(data);
      context.order = data;
      summary(data); // Authoritative snapshot amount, not the card's displayed amount.
      launch(data);
    } catch (error) {
      showState('form-error', error.message);
      fieldErrors(error.fields || {});
      sheet.focus({ preventScroll: true });
      const invalid = form.querySelector('[aria-invalid="true"]');
      if (invalid) invalid.focus();
    }
  });

  root.addEventListener('click', event => {
    if (event.target.closest('[data-checkout-close]')) close();
  });
  retry.addEventListener('click', () => { if (retryAction && !busy()) retryAction(); });
  document.addEventListener('keydown', event => {
    if (!isOpen || state === 'gateway') return;
    if (event.key === 'Escape') { event.preventDefault(); event.stopImmediatePropagation(); close(); }
    if (event.key !== 'Tab') return;
    const focusable = Array.from(sheet.querySelectorAll('button:not(:disabled), input:not(:disabled), a[href]')).filter(el => el.getClientRects().length);
    if (!focusable.length) { event.preventDefault(); sheet.focus(); return; }
    const first = focusable[0], last = focusable[focusable.length - 1];
    if (event.shiftKey && (document.activeElement === first || document.activeElement === sheet)) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && (document.activeElement === last || !sheet.contains(document.activeElement))) { event.preventDefault(); first.focus(); }
  }, true);
  document.addEventListener('focusin', event => {
    if (isOpen && state !== 'gateway' && !sheet.contains(event.target)) sheet.focus({ preventScroll: true });
  });
  window.FtCheckout = { open };
})();

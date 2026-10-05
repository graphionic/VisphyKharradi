<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= esc($siteName) ?> — Launching Soon</title>

  <!-- Preloads -->
  <link rel="preload" href="<?= base_url('assets/frontend/fonts/bricolage-grotesque.woff2') ?>" as="font" type="font/woff2" crossorigin />
  <link rel="stylesheet" href="<?= base_url('assets/frontend/css/fonts.css?v=' . time()) ?>" />

  <style>
    :root {
      --canvas: #F8FAFC;
      --ink: #141B31;
      --ultra: #2E47FF;
      --mint: #00B79B;
      --marigold: #FF9E2C;
      --body: #3C4763;
      --hair: rgba(20, 27, 49, 0.08);
      --f-display: "Bricolage Grotesque", system-ui, sans-serif;
      --f-body: "Karla", system-ui, sans-serif;
      --f-mono: "JetBrains Mono", monospace;
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      height: 100%;
      background: var(--canvas);
      color: var(--ink);
      font-family: var(--f-body);
      -webkit-font-smoothing: antialiased;
    }

    body {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;
      min-height: 100vh;
      padding: 32px 24px;
      position: relative;
      overflow-x: hidden;
    }

    /* Ambient Background Aura */
    body::before {
      content: "";
      position: absolute;
      top: 15%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: min(600px, 90vw);
      height: min(600px, 90vw);
      background: radial-gradient(circle, rgba(46, 71, 255, 0.07) 0%, rgba(0, 183, 155, 0.04) 50%, rgba(248, 250, 252, 0) 70%);
      pointer-events: none;
      z-index: 0;
    }

    .cs-header {
      position: relative;
      z-index: 1;
      margin-bottom: 24px;
    }

    .cs-logo {
      height: 48px;
      width: auto;
      object-fit: contain;
      display: block;
    }

    .cs-card {
      position: relative;
      z-index: 1;
      max-width: 640px;
      width: 100%;
      text-align: center;
      background: #ffffff;
      border: 1px solid var(--hair);
      border-radius: 24px;
      padding: 48px 36px;
      box-shadow: 0 20px 50px -15px rgba(20, 27, 49, 0.06);
      margin: auto 0;
    }

    @media (max-width: 640px) {
      .cs-card {
        padding: 36px 24px;
        border-radius: 20px;
      }
    }

    .cs-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: 100px;
      background: rgba(46, 71, 255, 0.06);
      border: 1px solid rgba(46, 71, 255, 0.12);
      color: var(--ultra);
      font-family: var(--f-mono);
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      margin-bottom: 24px;
    }

    .cs-badge__dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--ultra);
      box-shadow: 0 0 0 3px rgba(46, 71, 255, 0.2);
    }

    .cs-title {
      font-family: var(--f-display);
      font-size: clamp(28px, 4.5vw, 42px);
      font-weight: 800;
      line-height: 1.15;
      letter-spacing: -0.02em;
      color: var(--ink);
      margin-bottom: 18px;
    }

    .cs-message {
      font-family: var(--f-body);
      font-size: clamp(15px, 2vw, 17px);
      line-height: 1.6;
      color: var(--body);
      max-width: 520px;
      margin: 0 auto 32px;
    }

    /* Contact Actions */
    .cs-contact {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
      gap: 12px;
      padding-top: 24px;
      border-top: 1px solid var(--hair);
    }

    .cs-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-decoration: none;
      transition: all 0.25s cubic-bezier(0.16, 0.84, 0.24, 1);
    }

    .cs-btn--whatsapp {
      background: #25D366;
      color: #ffffff;
      box-shadow: 0 4px 14px rgba(37, 211, 102, 0.25);
    }

    .cs-btn--whatsapp:hover {
      background: #20bd5a;
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(37, 211, 102, 0.35);
    }

    .cs-btn--phone {
      background: var(--ink);
      color: #ffffff;
    }

    .cs-btn--phone:hover {
      background: var(--ultra);
      transform: translateY(-1px);
    }

    .cs-btn--email {
      background: rgba(20, 27, 49, 0.05);
      color: var(--ink);
      border: 1px solid var(--hair);
    }

    .cs-btn--email:hover {
      background: rgba(20, 27, 49, 0.09);
      color: var(--ultra);
    }

    .cs-footer {
      position: relative;
      z-index: 1;
      margin-top: 24px;
      font-family: var(--f-body);
      font-size: 13px;
      color: rgba(20, 27, 49, 0.45);
      text-align: center;
    }
  </style>
</head>
<body>

  <!-- Brand Header -->
  <header class="cs-header">
    <img class="cs-logo" src="<?= base_url($logo) ?>" alt="<?= esc($siteName) ?>">
  </header>

  <!-- Main Coming Soon Card -->
  <main class="cs-card">
    <div class="cs-badge">
      <span class="cs-badge__dot"></span>
      <span>Launching Soon</span>
    </div>

    <h1 class="cs-title"><?= esc($comingSoonHeading) ?></h1>

    <p class="cs-message"><?= nl2br(esc($comingSoonMessage)) ?></p>

    <?php if ($comingSoonShowContact === 'enabled' && (!empty($whatsappNumber) || !empty($contactPhone) || !empty($contactEmail))): ?>
      <div class="cs-contact">
        <?php if (!empty($whatsappNumber)): ?>
          <?php $cleanWa = preg_replace('/[^0-9]/', '', $whatsappNumber); ?>
          <a class="cs-btn cs-btn--whatsapp" href="https://wa.me/<?= $cleanWa ?>" target="_blank" rel="noopener">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.031 2c-5.517 0-9.997 4.48-9.997 9.998 0 1.763.459 3.487 1.33 5.006l-1.413 5.161 5.279-1.385c1.463.798 3.111 1.218 4.797 1.218 5.518 0 9.999-4.48 9.999-9.998 0-2.673-1.042-5.185-2.932-7.076-1.89-1.89-4.402-2.934-7.063-2.934zm5.726 14.237c-.244.688-1.428 1.309-1.96 1.386-.499.071-1.144.116-3.32-.782-2.784-1.151-4.577-4.004-4.717-4.19-.138-.186-1.134-1.509-1.134-2.879 0-1.37.718-2.045.975-2.324.257-.279.56-.349.747-.349.186 0 .372.002.535.01.173.008.406-.066.634.481.233.56.793 1.936.863 2.076.07.14.116.303.023.49-.093.186-.14.302-.279.465-.14.163-.293.364-.418.49-.14.14-.286.292-.123.571.163.279.725 1.196 1.558 1.937 1.07.953 1.972 1.248 2.251 1.387.279.14.442.116.605-.07.163-.186.7-.814.886-1.093.186-.279.372-.233.628-.14.256.093 1.63.769 1.91.908.279.14.465.209.535.325.07.116.07.674-.174 1.362z"/></svg>
            <span>WhatsApp Us</span>
          </a>
        <?php endif; ?>

        <?php if (!empty($contactPhone)): ?>
          <a class="cs-btn cs-btn--phone" href="tel:<?= preg_replace('/[^\+0-9]/', '', $contactPhone) ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            <span><?= esc($contactPhone) ?></span>
          </a>
        <?php endif; ?>

        <?php if (!empty($contactEmail)): ?>
          <a class="cs-btn cs-btn--email" href="mailto:<?= esc($contactEmail) ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span><?= esc($contactEmail) ?></span>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>

  <!-- Footer Copyright -->
  <footer class="cs-footer">
    <p>&copy; <?= date('Y') ?> <?= esc($siteName) ?>. All rights reserved.</p>
  </footer>

</body>
</html>

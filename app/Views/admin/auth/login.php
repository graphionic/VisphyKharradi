<?= $this->extend('admin/layouts/auth') ?>

<?= $this->section('content') ?>
<div class="auth-shell auth-layout">
    <div class="auth-panel--brand" aria-hidden="true">
        <div class="auth-brand-block">
            <div class="auth-brand-mark">
                <div class="auth-brand-mark__box">F</div>
                <div class="auth-brand-mark__text">FTP<span>RENEUR</span></div>
            </div>
            <h1 class="auth-hero-title">Operations for modern wellness.</h1>
            <p class="auth-hero-desc">A calm, precise workspace to manage programs, orders and customer trust — designed for daily operational clarity.</p>
            <ul class="auth-hero-list">
                <li><span class="auth-hero-list__dot"></span> Structured program management</li>
                <li><span class="auth-hero-list__dot"></span> Orders &amp; payments oversight</li>
                <li><span class="auth-hero-list__dot"></span> Audited, role-aware administration</li>
            </ul>
        </div>
        <div class="auth-footer-note">© <?= esc(date('Y')) ?> Ftpreneur — Visphy Kharradi. Private operations surface.</div>
    </div>

    <div class="auth-panel--form">
        <main id="main-content" class="auth-card" role="main" aria-labelledby="auth-title">
            <div class="auth-card__header">
                <div class="auth-card__kicker">Ftpreneur Admin</div>
                <h1 id="auth-title" class="auth-card__title">Sign in to manage Ftpreneur.</h1>
                <p class="auth-card__sub">Use your operator credentials to continue.</p>
            </div>

            <?php if (session()->getFlashdata('message')): ?>
                <div class="alert alert--success" role="status"><div class="alert__content"><?= esc(session()->getFlashdata('message')) ?></div></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert--danger" role="alert"><div class="alert__content"><?= esc(session()->getFlashdata('error')) ?></div></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert--danger" role="alert"><div class="alert__content"><?= esc($error) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert--success" role="status"><div class="alert__content"><?= esc($message) ?></div></div>
            <?php endif; ?>

            <?php if (isset($validation) && $validation->getErrors()): ?>
                <div class="alert alert--danger" role="alert">
                    <div class="alert__content">
                        <div class="alert__title">Please check your entries</div>
                        <ul class="auth-card__error-list">
                            <?php foreach ($validation->getErrors() as $field => $msg): ?>
                                <li><?= esc($msg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('admin/login') ?>" novalidate class="auth-card__form" data-prevent-double>
                <?= csrf_field() ?>
                <div class="field">
                    <label class="field__label" for="email">Email</label>
                    <input class="field__input" id="email" name="email" type="email" autocomplete="username" required
                           value="<?= esc(old('email') ?? '') ?>" inputmode="email" spellcheck="false" autofocus aria-describedby="email-hint">
                    <div id="email-hint" class="field__hint">Operator email address</div>
                    <?php if (isset($validation) && $validation->hasError('email')): ?>
                        <div class="field__error"><?= esc($validation->getError('email')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label class="field__label" for="password">Password</label>
                    <div class="password-field">
                        <input class="field__input" id="password" name="password" type="password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                            <svg data-eye width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2 10s3-5.5 8-5.5S18 10 18 10s-3 5.5-8 5.5S2 10 2 10Z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.3" stroke="currentColor" stroke-width="1.4"/></svg>
                            <svg data-eye-off width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="display:none"><path d="M3 3l14 14M9.8 5.2a5.5 5.5 0 0 1 5.7 3.8M14.5 10a5.5 5.5 0 0 1-7.1 4.3M2.5 10a9.5 9.5 0 0 1 5.2-4.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                    <?php if (isset($validation) && $validation->hasError('password')): ?>
                        <div class="field__error"><?= esc($validation->getError('password')) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn--primary btn--block" data-prevent-double>Sign in</button>
            </form>

            <div class="auth-card__foot">Secure operator access — session is monitored and expires after inactivity.</div>
        </main>
    </div>
</div>
<?= $this->endSection() ?>

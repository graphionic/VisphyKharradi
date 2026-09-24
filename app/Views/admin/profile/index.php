<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php $pageContext = 'Profile'; ?>

<?= $this->include('admin/partials/page_header', ['title' => 'Profile', 'description' => 'Manage your operator account and security preferences. Changes are audited.', 'eyebrow' => 'Account']) ?>

<div class="u-stack-lg" style="max-width:780px;">

    <!-- Identity — refined, not giant bordered card -->
    <section class="card">
        <div class="card__header">
            <div>
                <div class="card__title">Account identity</div>
                <div class="card__subtitle">Operator credentials for Ftpreneur administration</div>
            </div>
            <span class="badge badge--success"><span class="badge__dot"></span> Active</span>
        </div>
        <div class="card__body">
            <div class="u-flex" style="gap:16px; align-items:flex-start; flex-wrap:wrap;">
                <div class="admin-account-mini__avatar" style="width:48px;height:48px;font-size:1rem; background:var(--color-sidebar); color:#fff; flex-shrink:0;"><?= esc(strtoupper(substr($admin['name'] ?? 'A', 0, 1))) ?></div>
                <div style="display:grid; gap:14px; flex:1; min-width:240px;">
                    <div style="display:grid; gap:4px;">
                        <div class="caption" style="margin:0;">Name</div>
                        <div style="font-weight:600; color:var(--color-text); font-size:0.9375rem; letter-spacing:-0.01em;"><?= esc($admin['name'] ?? '') ?></div>
                    </div>
                    <div style="display:grid; gap:4px;">
                        <div class="caption" style="margin:0;">Email</div>
                        <div style="font-weight:500; color:var(--color-text-secondary);"><?= esc($admin['email'] ?? '') ?></div>
                        <div class="field__hint">Contact system owner to change email — email changes require re-provisioning.</div>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:4px;">
                        <span class="badge badge--neutral">Role: Operator</span>
                        <span class="badge badge--primary">ID <?= esc((string)($admin['id'] ?? '')) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Security — password change -->
    <section class="card">
        <div class="card__header">
            <div>
                <div class="card__title">Change Password</div>
                <div class="card__subtitle">Security — minimum 10 characters. No composition rules enforced.</div>
            </div>
        </div>
        <div class="card__body">
            <?php if (!empty($error)): ?>
                <div class="alert alert--danger" role="alert" style="margin-bottom:16px;">
                    <div class="alert__content"><?= esc($error) ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($message)): ?>
                <div class="alert alert--success" role="status" style="margin-bottom:16px;">
                    <div class="alert__content"><?= esc($message) ?></div>
                </div>
            <?php endif; ?>
            <?php if (isset($validation) && $validation->getErrors()): ?>
                <div class="alert alert--danger" role="alert" style="margin-bottom:16px;">
                    <div class="alert__content">
                        <div class="alert__title">Please correct the fields below</div>
                        <ul style="margin:6px 0 0 16px; padding:0; display:grid; gap:4px;">
                        <?php foreach ($validation->getErrors() as $field => $msg): ?>
                            <li><?= esc($msg) ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('admin/profile/password') ?>" novalidate class="form">
                <?= csrf_field() ?>

                <div class="field">
                    <label class="field__label" for="current_password">Current password</label>
                    <div class="password-field">
                        <input class="field__input" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                            <svg data-eye width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2 10s3-5.5 8-5.5S18 10 18 10s-3 5.5-8 5.5S2 10 2 10Z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.3" stroke="currentColor" stroke-width="1.4"/></svg>
                            <svg data-eye-off width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="display:none"><path d="M3 3l14 14M9.8 5.2a5.5 5.5 0 0 1 5.7 3.8M14.5 10a5.5 5.5 0 0 1-7.1 4.3M2.5 10a9.5 9.5 0 0 1 5.2-4.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                    <?php if (isset($validation) && $validation->hasError('current_password')): ?>
                        <div class="field__error"><?= esc($validation->getError('current_password')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form__row form__row--2">
                    <div class="field">
                        <label class="field__label" for="new_password">New password</label>
                        <div class="password-field">
                            <input class="field__input" id="new_password" name="new_password" type="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                                <svg data-eye width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2 10s3-5.5 8-5.5S18 10 18 10s-3 5.5-8 5.5S2 10 2 10Z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.3" stroke="currentColor" stroke-width="1.4"/></svg>
                                <svg data-eye-off width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="display:none"><path d="M3 3l14 14M9.8 5.2a5.5 5.5 0 0 1 5.7 3.8M14.5 10a5.5 5.5 0 0 1-7.1 4.3M2.5 10a9.5 9.5 0 0 1 5.2-4.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <div class="field__hint">At least 10 characters.</div>
                        <?php if (isset($validation) && $validation->hasError('new_password')): ?>
                            <div class="field__error"><?= esc($validation->getError('new_password')) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label class="field__label" for="confirm_password">Confirm new password</label>
                        <div class="password-field">
                            <input class="field__input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                                <svg data-eye width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M2 10s3-5.5 8-5.5S18 10 18 10s-3 5.5-8 5.5S2 10 2 10Z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="10" r="2.3" stroke="currentColor" stroke-width="1.4"/></svg>
                                <svg data-eye-off width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="display:none"><path d="M3 3l14 14M9.8 5.2a5.5 5.5 0 0 1 5.7 3.8M14.5 10a5.5 5.5 0 0 1-7.1 4.3M2.5 10a9.5 9.5 0 0 1 5.2-4.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <?php if (isset($validation) && $validation->hasError('confirm_password')): ?>
                            <div class="field__error"><?= esc($validation->getError('confirm_password')) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="u-flex" style="justify-content:flex-end; gap:12px; margin-top:4px;">
                    <button type="reset" class="btn btn--ghost">Reset</button>
                    <button type="submit" class="btn btn--primary">Update password</button>
                </div>
            </form>
        </div>
        <div class="card__footer">
            <span class="small u-muted">Password changes are logged — you will be asked to sign in again after updating.</span>
        </div>
    </section>

</div>
<?= $this->endSection() ?>

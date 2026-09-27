<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php
$oStatus = strtolower((string) $order['status']);
$pStatus = strtolower((string) ($latestPaymentStatus ?? ($order['payment_status'] ?? '')));
?>

<div class="order-detail-page">
    <?= $this->include('admin/partials/page_header', [
        'title'       => 'Order #' . $order['order_number'],
        'description' => $description,
        'eyebrow'     => 'MANAGEMENT / ORDER DETAIL',
        'breadcrumbs' => [
            ['label' => 'Admin', 'url' => site_url('admin')],
            ['label' => 'Orders', 'url' => site_url('admin/orders')],
            ['label' => '#' . $order['order_number']],
        ],
        'actionSlot'  => '<a href="' . esc(site_url('admin/orders'), 'attr') . '" class="btn btn--secondary">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 4l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Back to Orders
                          </a>',
    ]) ?>

    <?= $this->include('admin/partials/flash') ?>

    <!-- 1. ORDER HEADER BANNER CARD -->
    <section class="card" style="margin-bottom: 24px; padding: 24px; background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px;">
            <div>
                <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); color: var(--text-muted, #64748B); letter-spacing: 0.08em; text-transform: uppercase;">
                    ORDER REFERENCE
                </div>
                <h2 style="font-size: 26px; font-weight: 800; font-family: var(--f-display, sans-serif); color: var(--text-color, #0F172A); margin: 4px 0 8px;">
                    <?= esc($order['order_number']) ?>
                </h2>
                <div style="font-size: 13px; color: var(--text-muted, #64748B);">
                    Created on <?= esc(date('F j, Y \a\t h:i A', strtotime($order['created_at']))) ?>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px;">
                <!-- Payment Status Pill -->
                <div style="text-align: center;">
                    <div style="font-size: 10px; font-weight: 700; font-family: var(--f-mono, monospace); color: var(--text-muted, #64748B); margin-bottom: 4px; text-transform: uppercase;">
                        PAYMENT STATUS
                    </div>
                    <?php if ($pStatus === 'captured'): ?>
                        <span class="badge badge--success" style="padding: 6px 14px; font-size: 12px; background: rgba(0, 183, 155, 0.12); color: #00B79B; font-weight: 700;">Captured</span>
                    <?php elseif ($pStatus === 'attempted'): ?>
                        <span class="badge badge--info" style="padding: 6px 14px; font-size: 12px; background: rgba(46, 71, 255, 0.1); color: #2E47FF; font-weight: 700;">Attempted</span>
                    <?php elseif ($pStatus === 'created'): ?>
                        <span class="badge badge--neutral" style="padding: 6px 14px; font-size: 12px;">Created</span>
                    <?php elseif ($pStatus === 'failed'): ?>
                        <span class="badge badge--danger" style="padding: 6px 14px; font-size: 12px; background: rgba(225, 29, 72, 0.1); color: #E11D48; font-weight: 700;">Failed</span>
                    <?php elseif ($pStatus === 'cancelled'): ?>
                        <span class="badge badge--muted" style="padding: 6px 14px; font-size: 12px;">Cancelled</span>
                    <?php else: ?>
                        <span class="badge badge--muted" style="padding: 6px 14px; font-size: 12px; opacity: 0.6;">Uninitiated</span>
                    <?php endif; ?>
                </div>

                <!-- Order Status Pill -->
                <div style="text-align: center;">
                    <div style="font-size: 10px; font-weight: 700; font-family: var(--f-mono, monospace); color: var(--text-muted, #64748B); margin-bottom: 4px; text-transform: uppercase;">
                        ORDER STATUS
                    </div>
                    <?php if ($oStatus === 'paid'): ?>
                        <span class="badge badge--success" style="padding: 6px 14px; font-size: 12px; background: #DDF5F0; color: #075B50; font-weight: 700;">Paid</span>
                    <?php elseif ($oStatus === 'pending'): ?>
                        <span class="badge badge--warning" style="padding: 6px 14px; font-size: 12px; background: #FEF3C7; color: #92400E; font-weight: 700;">Pending</span>
                    <?php elseif ($oStatus === 'failed'): ?>
                        <span class="badge badge--danger" style="padding: 6px 14px; font-size: 12px;">Failed</span>
                    <?php else: ?>
                        <span class="badge badge--muted" style="padding: 6px 14px; font-size: 12px;"><?= esc(ucfirst($oStatus)) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Amount Box -->
                <div style="padding: 12px 20px; background: #EAF0FF; border: 1px solid #C6D2F3; border-radius: 12px; text-align: right; min-width: 140px;">
                    <div style="font-size: 10px; font-weight: 700; font-family: var(--f-mono, monospace); color: #2E47FF; letter-spacing: 0.08em;">
                        TOTAL AMOUNT
                    </div>
                    <div style="font-size: 22px; font-weight: 800; font-family: var(--f-display, sans-serif); color: #141B31; margin-top: 2px;">
                        ₹<?= number_format((float) $order['total_amount'], 2) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. TWO-COLUMN LAYOUT -->
    <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px;">

        <!-- LEFT COLUMN: PROGRAM SNAPSHOT & PAYMENT ATTEMPTS -->
        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- 2A. PURCHASED PROGRAM SNAPSHOT CARD -->
            <section class="card">
                <div class="card__header">
                    <div>
                        <h3 class="card__title">Purchased Program</h3>
                        <p class="card__subtitle">Immutable order snapshot captured at checkout time.</p>
                    </div>
                    <span class="badge badge--neutral" style="font-family: var(--f-mono, monospace); font-size: 10px;">ID #<?= esc($order['package_id']) ?></span>
                </div>

                <div class="card__body">
                    <div style="padding: 20px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 12px;">
                            <div>
                                <h4 style="font-size: 18px; font-weight: 800; font-family: var(--f-display, sans-serif); color: var(--text-color, #0F172A); margin: 0 0 4px;">
                                    <?= esc($order['package_name_snapshot']) ?>
                                </h4>
                                <?php if (!empty($order['package_duration_snapshot'])): ?>
                                    <span class="badge badge--neutral" style="font-size: 11px;">
                                        Duration: <?= esc($order['package_duration_snapshot']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 18px; font-weight: 800; font-family: var(--f-display, sans-serif); color: #2E47FF;">
                                ₹<?= number_format((float) $order['package_price_snapshot'], 2) ?>
                            </div>
                        </div>

                        <?php if (!empty($order['package_slug_snapshot'])): ?>
                            <div style="font-size: 12px; color: var(--text-muted, #64748B); font-family: var(--f-mono, monospace);">
                                Slug: <?= esc($order['package_slug_snapshot']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Financial Breakdown Table -->
                    <div style="display: flex; flex-direction: column; gap: 10px; padding: 16px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13.5px;">
                            <span style="color: var(--text-muted, #64748B);">Program Subtotal</span>
                            <span style="font-weight: 600; color: var(--text-color, #0F172A);">₹<?= number_format((float) $order['subtotal'], 2) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13.5px;">
                            <span style="color: var(--text-muted, #64748B);">Discount Applied</span>
                            <span style="font-weight: 600; color: var(--text-color, #0F172A);">₹<?= number_format((float) $order['discount_amount'], 2) ?></span>
                        </div>
                        <div style="border-top: 1px dashed #E2E8F0; padding-top: 10px; margin-top: 2px; display: flex; justify-content: space-between; font-size: 15px;">
                            <strong style="color: var(--text-color, #0F172A);">Total Charged</strong>
                            <strong style="color: #2E47FF; font-family: var(--f-display, sans-serif);">₹<?= number_format((float) $order['total_amount'], 2) ?> <?= esc($order['currency']) ?></strong>
                        </div>
                    </div>

                    <div style="margin-top: 14px; font-size: 12px; color: var(--text-muted, #64748B); display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        Snapshot preserved safely. Remains unchanged if package is edited or archived.
                    </div>
                </div>
            </section>

            <!-- 2B. PAYMENT ATTEMPTS CARD -->
            <section class="card">
                <div class="card__header">
                    <div>
                        <h3 class="card__title">Payment Attempts</h3>
                        <p class="card__subtitle">All payment initialization records associated with this order.</p>
                    </div>
                    <span class="badge badge--neutral"><?= count($payments) ?> Attempt(s)</span>
                </div>

                <div class="card__body" style="padding: 0;">
                    <?php if (empty($payments)): ?>
                        <div style="padding: 32px 20px; text-align: center; color: var(--text-muted, #64748B);">
                            <p style="font-size: 14px; margin: 0;">No payment attempts recorded yet.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 12px 16px; text-align: left;">Attempt</th>
                                        <th style="padding: 12px 16px; text-align: left;">Payment Status</th>
                                        <th style="padding: 12px 16px; text-align: left;">Razorpay Payment ID</th>
                                        <th style="padding: 12px 16px; text-align: right;">Amount</th>
                                        <th style="padding: 12px 16px; text-align: left;">Method</th>
                                        <th style="padding: 12px 16px; text-align: left;">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $idx => $p): ?>
                                        <?php
                                        $isCaptured = strtolower((string) ($p['status'] ?? '')) === 'captured';
                                        $bgStyle = $isCaptured ? 'background: rgba(0, 183, 155, 0.04);' : '';
                                        ?>
                                        <tr style="border-bottom: 1px solid var(--border-color, #E2E8F0); <?= $bgStyle ?>">
                                            <!-- Attempt Number -->
                                            <td style="padding: 14px 16px; vertical-align: middle;">
                                                <span style="font-family: var(--f-mono, monospace); font-weight: 700; font-size: 12px; color: var(--text-muted, #64748B);">
                                                    #<?= $idx + 1 ?>
                                                </span>
                                                <?php if ($isCaptured): ?>
                                                    <span class="badge badge--success" style="font-size: 9px; margin-left: 4px; padding: 2px 6px;">CAPTURED</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Payment Status -->
                                            <td style="padding: 14px 16px; vertical-align: middle;">
                                                <?php
                                                $st = strtolower((string) ($p['status'] ?? 'created'));
                                                if ($st === 'captured'): ?>
                                                    <span class="badge badge--success" style="font-weight: 700;">Captured</span>
                                                <?php elseif ($st === 'attempted'): ?>
                                                    <span class="badge badge--info" style="font-weight: 700;">Attempted</span>
                                                <?php elseif ($st === 'created'): ?>
                                                    <span class="badge badge--neutral">Created</span>
                                                <?php elseif ($st === 'failed'): ?>
                                                    <span class="badge badge--danger" style="font-weight: 700;">Failed</span>
                                                <?php else: ?>
                                                    <span class="badge badge--muted"><?= esc(ucfirst($st)) ?></span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Razorpay Payment ID -->
                                            <td style="padding: 14px 16px; vertical-align: middle;">
                                                <?php if (!empty($p['razorpay_payment_id'])): ?>
                                                    <span style="font-family: var(--f-mono, monospace); font-size: 12px; font-weight: 700; color: #141B31; background: #EAF0FF; padding: 4px 8px; border-radius: 6px;">
                                                        <?= esc($p['razorpay_payment_id']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="font-size: 12px; color: var(--text-muted, #94A3B8);">Pending Gateway ID</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Amount -->
                                            <td style="padding: 14px 16px; text-align: right; vertical-align: middle; font-weight: 700; font-size: 13.5px;">
                                                ₹<?= number_format((float) $p['amount'], 2) ?>
                                            </td>

                                            <!-- Method -->
                                            <td style="padding: 14px 16px; vertical-align: middle; font-size: 12.5px;">
                                                <?= !empty($p['method']) ? esc(ucfirst($p['method'])) : '—' ?>
                                            </td>

                                            <!-- Date -->
                                            <td style="padding: 14px 16px; vertical-align: middle; font-size: 12px; color: var(--text-muted, #64748B);">
                                                <?= esc(date('d M Y, h:i A', strtotime($p['verified_at'] ?? $p['created_at']))) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- RIGHT COLUMN: CUSTOMER DETAILS & PAYMENT TIMELINE -->
        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- 2C. CUSTOMER DETAILS CARD -->
            <section class="card">
                <div class="card__header">
                    <h3 class="card__title">Customer Details</h3>
                </div>
                <div class="card__body" style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); color: var(--text-muted, #64748B); text-transform: uppercase;">
                            FULL NAME
                        </div>
                        <div style="font-size: 16px; font-weight: 700; color: var(--text-color, #0F172A); margin-top: 2px;">
                            <?= esc($order['customer_name']) ?>
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); color: var(--text-muted, #64748B); text-transform: uppercase;">
                            EMAIL ADDRESS
                        </div>
                        <div style="font-size: 14px; color: #2E47FF; margin-top: 2px;">
                            <a href="mailto:<?= esc($order['customer_email'], 'attr') ?>" style="color: inherit; text-decoration: none;">
                                <?= esc($order['customer_email']) ?>
                            </a>
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); color: var(--text-muted, #64748B); text-transform: uppercase;">
                            MOBILE NUMBER
                        </div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-color, #0F172A); margin-top: 2px;">
                            <?= esc($order['customer_phone']) ?>
                        </div>
                    </div>

                    <?php if (!empty($order['razorpay_order_id'])): ?>
                        <div style="border-top: 1px solid #E2E8F0; padding-top: 14px; margin-top: 4px;">
                            <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); color: var(--text-muted, #64748B); text-transform: uppercase;">
                                GATEWAY ORDER ID
                            </div>
                            <div style="font-family: var(--f-mono, monospace); font-size: 12px; font-weight: 700; color: #141B31; margin-top: 4px; overflow-wrap: anywhere;">
                                <?= esc($order['razorpay_order_id']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 2D. PAYMENT TIMELINE CARD -->
            <section class="card">
                <div class="card__header">
                    <h3 class="card__title">Payment Timeline</h3>
                </div>
                <div class="card__body">
                    <div class="timeline" style="display: flex; flex-direction: column; gap: 20px; position: relative;">
                        <?php foreach ($timeline as $index => $item): ?>
                            <div style="display: flex; gap: 14px; position: relative;">
                                <!-- Connector dot -->
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 12px; height: 12px; border-radius: 50%; background: <?= $item['status'] === 'success' ? '#00B79B' : ($item['status'] === 'danger' ? '#E11D48' : '#2E47FF') ?>; margin-top: 3px; flex-shrink: 0; box-shadow: 0 0 0 3px rgba(46, 71, 255, 0.12);"></div>
                                    <?php if ($index < count($timeline) - 1): ?>
                                        <div style="width: 2px; flex-grow: 1; background: #E2E8F0; margin-top: 4px;"></div>
                                    <?php endif; ?>
                                </div>

                                <div style="flex-grow: 1; padding-bottom: 4px;">
                                    <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 10px;">
                                        <h5 style="font-size: 13.5px; font-weight: 700; color: var(--text-color, #0F172A); margin: 0;">
                                            <?= esc($item['title']) ?>
                                        </h5>
                                        <span class="badge badge--neutral" style="font-size: 9px; padding: 1px 5px;">
                                            <?= esc($item['badge']) ?>
                                        </span>
                                    </div>
                                    <p style="font-size: 12px; color: var(--text-muted, #64748B); margin: 3px 0 0; line-height: 1.4;">
                                        <?= esc($item['description']) ?>
                                    </p>
                                    <div style="font-size: 11px; font-family: var(--f-mono, monospace); color: var(--text-muted, #94A3B8); margin-top: 4px;">
                                        <?= esc(date('d M Y, h:i:s A', strtotime($item['timestamp']))) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>
<?= $this->endSection() ?>

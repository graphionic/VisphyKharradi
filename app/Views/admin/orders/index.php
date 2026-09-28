<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php
$hasFilters = ($filters['q'] !== '' || $filters['order_status'] !== 'all' || $filters['payment_status'] !== 'all');
$hasOrders  = !empty($orders);
?>

<div class="orders-page">
    <?= $this->include('admin/partials/page_header', [
        'title'       => $title,
        'description' => $description,
        'eyebrow'     => 'MANAGEMENT / ORDERS',
        'breadcrumbs' => [
            ['label' => 'Admin', 'url' => site_url('admin')],
            ['label' => 'Management', 'url' => '#'],
            ['label' => 'Orders'],
        ],
    ]) ?>

    <?= $this->include('admin/partials/flash') ?>

    <!-- 1. SUMMARY METRICS CARDS (OVERALL DATABASE TOTALS) -->
    <div class="orders-metrics" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 20px;">
            <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); letter-spacing: 0.08em; color: var(--text-muted, #64748B); text-transform: uppercase; margin-bottom: 8px;">
                TOTAL ORDERS
            </div>
            <div style="font-size: 28px; font-weight: 800; font-family: var(--f-display, sans-serif); color: var(--text-color, #0F172A);">
                <?= number_format($totalOrdersCount) ?>
            </div>
            <div style="font-size: 12px; color: var(--text-muted, #64748B); margin-top: 4px;">Lifetime checkout attempts</div>
        </div>

        <div class="card" style="padding: 20px; border-left: 4px solid #00B79B;">
            <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); letter-spacing: 0.08em; color: #075B50; text-transform: uppercase; margin-bottom: 8px;">
                PAID ORDERS
            </div>
            <div style="font-size: 28px; font-weight: 800; font-family: var(--f-display, sans-serif); color: #00B79B;">
                <?= number_format($paidOrdersCount) ?>
            </div>
            <div style="font-size: 12px; color: var(--text-muted, #64748B); margin-top: 4px;">Confirmed &amp; captured</div>
        </div>

        <div class="card" style="padding: 20px; border-left: 4px solid #FF9E2C;">
            <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); letter-spacing: 0.08em; color: #7A4D00; text-transform: uppercase; margin-bottom: 8px;">
                PENDING / UNPAID
            </div>
            <div style="font-size: 28px; font-weight: 800; font-family: var(--f-display, sans-serif); color: #D97706;">
                <?= number_format($pendingOrdersCount) ?>
            </div>
            <div style="font-size: 12px; color: var(--text-muted, #64748B); margin-top: 4px;">Awaiting verification</div>
        </div>

        <div class="card" style="padding: 20px; border-left: 4px solid #2E47FF; background: linear-gradient(135deg, #FFFFFF 0%, #EAF0FF 100%);">
            <div style="font-size: 11px; font-weight: 700; font-family: var(--f-mono, monospace); letter-spacing: 0.08em; color: #2E47FF; text-transform: uppercase; margin-bottom: 8px;">
                TOTAL COLLECTED
            </div>
            <div style="font-size: 26px; font-weight: 800; font-family: var(--f-display, sans-serif); color: #141B31;">
                ₹<?= number_format($totalCollected, 2) ?>
            </div>
            <div style="font-size: 12px; color: var(--text-muted, #64748B); margin-top: 4px;">Successful paid orders only</div>
        </div>
    </div>

    <!-- 2. SEARCH & FILTER TOOLBAR -->
    <section class="card" style="margin-bottom: 24px;">
        <div class="card__body">
            <form method="get" action="<?= esc(site_url('admin/orders'), 'attr') ?>" style="display: grid; grid-template-columns: minmax(260px, 1.8fr) minmax(160px, 1fr) minmax(160px, 1fr) auto; gap: 12px; align-items: end;">
                <!-- Search Query -->
                <div class="field" style="margin: 0;">
                    <label class="field__label" for="q">Search Orders</label>
                    <input class="field__input <?= $filters['q'] !== '' ? 'field__input--active-filter' : '' ?>"
                           id="q" name="q" type="search"
                           placeholder="Order #, customer, email or mobile"
                           value="<?= esc($filters['q']) ?>"
                           maxlength="100" autocomplete="off">
                </div>

                <!-- Order Status Filter -->
                <div class="field" style="margin: 0;">
                    <label class="field__label" for="order_status">Order Status</label>
                    <select class="field__select <?= $filters['order_status'] !== 'all' ? 'field__select--active-filter' : '' ?>"
                            id="order_status" name="order_status">
                        <option value="all" <?= $filters['order_status'] === 'all' ? 'selected' : '' ?>>All Order Statuses</option>
                        <option value="paid" <?= $filters['order_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="pending" <?= $filters['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="failed" <?= $filters['order_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="cancelled" <?= $filters['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <!-- Payment Status Filter -->
                <div class="field" style="margin: 0;">
                    <label class="field__label" for="payment_status">Payment Status</label>
                    <select class="field__select <?= $filters['payment_status'] !== 'all' ? 'field__select--active-filter' : '' ?>"
                            id="payment_status" name="payment_status">
                        <option value="all" <?= $filters['payment_status'] === 'all' ? 'selected' : '' ?>>All Payment Statuses</option>
                        <option value="captured" <?= $filters['payment_status'] === 'captured' ? 'selected' : '' ?>>Captured</option>
                        <option value="attempted" <?= $filters['payment_status'] === 'attempted' ? 'selected' : '' ?>>Attempted</option>
                        <option value="created" <?= $filters['payment_status'] === 'created' ? 'selected' : '' ?>>Created</option>
                        <option value="failed" <?= $filters['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="cancelled" <?= $filters['payment_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <!-- Toolbar Action Buttons -->
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn--primary">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= esc(site_url('admin/orders'), 'attr') ?>" class="btn btn--secondary" style="border-color: #CBD5E1;" title="Clear active filters">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Active Filter Feedback Tags Bar -->
            <?php if ($hasFilters): ?>
                <div class="active-filter-bar" style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #E2E8F0; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 12px; color: var(--text-muted, #64748B);">
                        <span style="font-weight: 700; font-family: var(--f-mono, monospace); text-transform: uppercase; font-size: 10px; color: #2E47FF; letter-spacing: 0.06em;">ACTIVE FILTERS:</span>
                        <?php if ($filters['q'] !== ''): ?>
                            <span class="badge badge--info" style="background: rgba(46, 71, 255, 0.08); color: #2E47FF; border: 1px solid rgba(46, 71, 255, 0.2); font-weight: 600;">
                                Query: "<?= esc($filters['q']) ?>"
                            </span>
                        <?php endif; ?>
                        <?php if ($filters['order_status'] !== 'all'): ?>
                            <span class="badge badge--info" style="background: rgba(46, 71, 255, 0.08); color: #2E47FF; border: 1px solid rgba(46, 71, 255, 0.2); font-weight: 600;">
                                Order Status: <?= esc(ucfirst($filters['order_status'])) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($filters['payment_status'] !== 'all'): ?>
                            <span class="badge badge--info" style="background: rgba(46, 71, 255, 0.08); color: #2E47FF; border: 1px solid rgba(46, 71, 255, 0.2); font-weight: 600;">
                                Payment Status: <?= esc(ucfirst($filters['payment_status'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= esc(site_url('admin/orders'), 'attr') ?>" style="font-size: 12px; color: #2E47FF; text-decoration: none; font-weight: 600;">
                        Clear all &times;
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- 3. ORDERS REGISTRY TABLE CARD -->
    <section class="card">
        <div class="card__header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 class="card__title">Orders Registry</h3>
                <p class="card__subtitle">Showing <?= esc($rangeStart) ?>–<?= esc($rangeEnd) ?> of <?= esc($totalFiltered) ?> items</p>
            </div>
        </div>

        <div class="card__body" style="padding: 0;">
            <?php if (!$hasOrders): ?>
                <?php if ($totalOrdersCount === 0): ?>
                    <!-- EMPTY STATE A: NO ORDERS IN DATABASE YET -->
                    <div style="padding: 56px 24px; text-align: center; color: var(--text-muted, #64748B);">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #F1F5F9; color: #64748B; display: grid; place-items: center; margin: 0 auto 16px;">
                            <svg width="24" height="24" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="14" height="14" rx="2"/><path d="M7 8h6M7 12h4"/></svg>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0 0 6px;">No orders recorded yet</h4>
                        <p style="font-size: 13.5px; color: #64748B; margin: 0 auto; max-width: 400px; line-height: 1.5;">
                            Customer program purchases and checkout attempts will automatically appear here once initiated.
                        </p>
                    </div>
                <?php else: ?>
                    <!-- EMPTY STATE B: SEARCH / FILTER RETURNED NO RESULTS -->
                    <div style="padding: 56px 24px; text-align: center; color: var(--text-muted, #64748B);">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF3C7; color: #D97706; display: grid; place-items: center; margin: 0 auto 16px;">
                            <svg width="24" height="24" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 14A6 6 0 1 0 8 2a6 6 0 0 0 0 12zM12.5 12.5L17 17"/></svg>
                        </div>
                        <h4 style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0 0 6px;">No matching orders found</h4>
                        <p style="font-size: 13.5px; color: #64748B; margin: 0 auto 18px; max-width: 420px; line-height: 1.5;">
                            No orders matched your current search term or filter selection. Try adjusting your criteria or clearing filters.
                        </p>
                        <a href="<?= esc(site_url('admin/orders'), 'attr') ?>" class="btn btn--primary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 4v5h5M16 16v-5h-5M4.5 9A7 7 0 0 1 16 7M15.5 11A7 7 0 0 1 4 13"/></svg>
                            Reset Filters &amp; Search
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 12px 16px; text-align: left; white-space: nowrap;">Order #</th>
                                <th style="padding: 12px 16px; text-align: left;">Customer</th>
                                <th style="padding: 12px 16px; text-align: left;">Program</th>
                                <th style="padding: 12px 16px; text-align: right; white-space: nowrap;">Amount</th>
                                <th style="padding: 12px 16px; text-align: center; white-space: nowrap;">Payment Status</th>
                                <th style="padding: 12px 16px; text-align: center; white-space: nowrap;">Order Status</th>
                                <th style="padding: 12px 16px; text-align: left; white-space: nowrap;">Order Date</th>
                                <th style="padding: 12px 16px; text-align: right; white-space: nowrap;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr style="border-bottom: 1px solid var(--border-color, #E2E8F0);">
                                    <!-- Order Number -->
                                    <td style="padding: 14px 16px; vertical-align: middle; white-space: nowrap;">
                                        <a href="<?= esc(site_url('admin/orders/' . $order['id']), 'attr') ?>" style="font-family: var(--f-mono, monospace); font-weight: 700; color: #2E47FF; font-size: 13px; text-decoration: none;">
                                            <?= esc($order['order_number']) ?>
                                        </a>
                                    </td>

                                    <!-- Customer Details -->
                                    <td style="padding: 14px 16px; vertical-align: middle;">
                                        <div style="font-weight: 700; color: var(--text-color, #0F172A); font-size: 14px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= esc($order['customer_name']) ?>
                                        </div>
                                        <div style="font-size: 12px; color: var(--text-muted, #64748B); margin-top: 2px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= esc($order['customer_email']) ?> <?= !empty($order['customer_phone']) ? '• ' . esc($order['customer_phone']) : '' ?>
                                        </div>
                                    </td>

                                    <!-- Program Details -->
                                    <td style="padding: 14px 16px; vertical-align: middle;">
                                        <div style="font-weight: 600; color: var(--text-color, #0F172A); font-size: 13.5px; max-width: 220px; line-height: 1.35; word-break: break-word; overflow-wrap: anywhere;">
                                            <?= esc($order['package_name_snapshot']) ?>
                                        </div>
                                        <?php if (!empty($order['package_duration_snapshot'])): ?>
                                            <span class="badge badge--neutral" style="font-size: 10px; margin-top: 3px; display: inline-block;">
                                                <?= esc($order['package_duration_snapshot']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Amount -->
                                    <td style="padding: 14px 16px; text-align: right; vertical-align: middle; white-space: nowrap;">
                                        <strong style="font-family: var(--f-display, sans-serif); font-size: 15px; color: var(--text-color, #0F172A);">
                                            ₹<?= number_format((float) $order['total_amount'], 2) ?>
                                        </strong>
                                    </td>

                                    <!-- Payment Status Badge -->
                                    <td style="padding: 14px 16px; text-align: center; vertical-align: middle; white-space: nowrap;">
                                        <?php
                                        $pStatus = strtolower((string) ($order['payment_status'] ?? ''));
                                        if ($pStatus === 'captured'): ?>
                                            <span class="badge badge--success" style="background: rgba(0, 183, 155, 0.12); color: #00B79B; font-weight: 700;">Captured</span>
                                        <?php elseif ($pStatus === 'attempted'): ?>
                                            <span class="badge badge--info" style="background: rgba(46, 71, 255, 0.1); color: #2E47FF; font-weight: 700;">Attempted</span>
                                        <?php elseif ($pStatus === 'created'): ?>
                                            <span class="badge badge--neutral">Created</span>
                                        <?php elseif ($pStatus === 'failed'): ?>
                                            <span class="badge badge--danger" style="background: rgba(225, 29, 72, 0.1); color: #E11D48; font-weight: 700;">Failed</span>
                                        <?php elseif ($pStatus === 'cancelled'): ?>
                                            <span class="badge badge--muted">Cancelled</span>
                                        <?php else: ?>
                                            <span class="badge badge--muted" style="opacity: 0.6;">Uninitiated</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Order Status Badge -->
                                    <td style="padding: 14px 16px; text-align: center; vertical-align: middle; white-space: nowrap;">
                                        <?php
                                        $oStatus = strtolower((string) $order['status']);
                                        if ($oStatus === 'paid'): ?>
                                            <span class="badge badge--success" style="background: #DDF5F0; color: #075B50; font-weight: 700;">Paid</span>
                                        <?php elseif ($oStatus === 'pending'): ?>
                                            <span class="badge badge--warning" style="background: #FEF3C7; color: #92400E; font-weight: 700;">Pending</span>
                                        <?php elseif ($oStatus === 'failed'): ?>
                                            <span class="badge badge--danger">Failed</span>
                                        <?php else: ?>
                                            <span class="badge badge--muted"><?= esc(ucfirst($oStatus)) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Order Date -->
                                    <td style="padding: 14px 16px; vertical-align: middle; font-size: 12.5px; color: var(--text-muted, #64748B); white-space: nowrap;">
                                        <?= esc(date('d M Y, h:i A', strtotime($order['created_at']))) ?>
                                    </td>

                                    <!-- View Action -->
                                    <td style="padding: 14px 16px; text-align: right; vertical-align: middle; white-space: nowrap;">
                                        <a href="<?= esc(site_url('admin/orders/' . $order['id']), 'attr') ?>"
                                           class="btn btn--secondary btn--sm"
                                           style="padding: 5px 12px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                            <span>View</span>
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($pagerHtml)): ?>
                    <div style="padding: 16px 24px; border-top: 1px solid var(--border-color, #E2E8F0); display: flex; justify-content: flex-end;">
                        <?= $pagerHtml ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
.input--active-filter {
    border-color: #2E47FF !important;
    background-color: rgba(46, 71, 255, 0.03) !important;
}
</style>
<?= $this->endSection() ?>

<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\PaymentModel;
use App\Services\AdminAuthService;
use Config\Database;

/**
 * OrderController — Admin Orders Management
 *
 * Phase A: Orders Listing
 * Phase B: Order Detail & Payment Attempts
 */
class OrderController extends BaseController
{
    private AdminAuthService $authService;
    public const PER_PAGE = 15;

    public function __construct()
    {
        $this->authService = new AdminAuthService();
    }

    /**
     * Phase A: Admin Orders Listing
     */
    public function index()
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        $q             = trim((string) ($this->request->getGet('q') ?? ''));
        $orderStatus   = trim((string) ($this->request->getGet('order_status') ?? 'all'));
        $paymentStatus = trim((string) ($this->request->getGet('payment_status') ?? 'all'));
        $page          = (int) ($this->request->getGet('page') ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $orderModel = new OrderModel();

        // 1. Calculate overall summary metrics
        $totalOrdersCount   = $orderModel->countAllResults();
        $paidOrdersCount    = (new OrderModel())->where('status', 'paid')->countAllResults();
        $pendingOrdersCount = (new OrderModel())->where('status', 'pending')->countAllResults();

        $sumRow = (new OrderModel())->selectSum('total_amount', 'total_collected')
                                    ->where('status', 'paid')
                                    ->first();
        $totalCollected = (float) ($sumRow['total_collected'] ?? 0.00);

        // 2. Build filtered listing query with latest payment status join
        $db = Database::connect();
        $builder = $db->table('orders');
        $builder->select('orders.*, latest_p.status AS payment_status, latest_p.razorpay_payment_id, latest_p.method AS payment_method');
        $builder->join(
            '(SELECT p1.* FROM payments p1 INNER JOIN (SELECT order_id, MAX(id) as max_id FROM payments GROUP BY order_id) p2 ON p1.id = p2.max_id) AS latest_p',
            'latest_p.order_id = orders.id',
            'left'
        );

        if ($q !== '') {
            $builder->groupStart()
                ->like('orders.order_number', $q)
                ->orLike('orders.customer_name', $q)
                ->orLike('orders.customer_email', $q)
                ->orLike('orders.customer_phone', $q)
                ->groupEnd();
        }

        if (in_array($orderStatus, ['pending', 'paid', 'failed', 'cancelled'], true)) {
            $builder->where('orders.status', $orderStatus);
        }

        if (in_array($paymentStatus, ['created', 'attempted', 'captured', 'failed', 'cancelled'], true)) {
            $builder->where('latest_p.status', $paymentStatus);
        }

        $totalFilteredBuilder = clone $builder;
        $totalFiltered = $totalFilteredBuilder->countAllResults();

        $builder->orderBy('orders.created_at', 'DESC')
                ->orderBy('orders.id', 'DESC')
                ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        $orders = $builder->get()->getResultArray();

        $pager = service('pager');
        $pagerHtml = $pager->makeLinks($page, self::PER_PAGE, $totalFiltered, 'default_full', 0, 'default');

        $rangeStart = $totalFiltered === 0 ? 0 : ($page - 1) * self::PER_PAGE + 1;
        $rangeEnd   = min($totalFiltered, $page * self::PER_PAGE);

        return view('admin/orders/index', [
            'title'              => 'Orders',
            'description'        => 'Monitor program sales, customer details, and payment statuses.',
            'admin'              => $admin,
            'orders'             => $orders,
            'totalOrdersCount'   => $totalOrdersCount,
            'paidOrdersCount'    => $paidOrdersCount,
            'pendingOrdersCount' => $pendingOrdersCount,
            'totalCollected'     => $totalCollected,
            'totalFiltered'      => $totalFiltered,
            'rangeStart'         => $rangeStart,
            'rangeEnd'           => $rangeEnd,
            'currentPage'        => $page,
            'perPage'            => self::PER_PAGE,
            'pagerHtml'          => $pagerHtml,
            'filters'            => [
                'q'              => $q,
                'order_status'   => $orderStatus,
                'payment_status' => $paymentStatus,
            ],
        ]);
    }

    /**
     * Phase B: Order Detail & Payment Attempts Page
     * GET /admin/orders/{id}
     */
    public function show($id)
    {
        $admin = $this->authService->getCurrentAdmin();
        if ($admin === null) {
            return service('response')->redirect('/admin/login', 'auto', 302);
        }

        if (!is_numeric($id) || (int) $id <= 0) {
            return redirect()->to(site_url('admin/orders'))->with('error', 'Invalid order ID requested.');
        }

        $orderId = (int) $id;
        $orderModel = new OrderModel();
        $order = $orderModel->find($orderId);

        if (!$order) {
            return redirect()->to(site_url('admin/orders'))->with('error', 'Order not found.');
        }

        // Fetch associated payment attempts
        $paymentModel = new PaymentModel();
        $payments = $paymentModel->where('order_id', $orderId)
                                 ->orderBy('id', 'ASC')
                                 ->findAll();

        // Identify captured attempt and latest payment status
        $capturedPayment = null;
        $latestPaymentStatus = null;

        if (!empty($payments)) {
            foreach ($payments as $p) {
                if (($p['status'] ?? '') === 'captured') {
                    $capturedPayment = $p;
                }
            }
            $latestPaymentStatus = end($payments)['status'] ?? null;
        }

        // Construct Payment Timeline strictly from stored timestamps
        $timeline = [];

        // 1. Order Created
        $timeline[] = [
            'type'        => 'order_created',
            'title'       => 'Checkout Order Created',
            'description' => 'Local order contract created for ' . ($order['package_name_snapshot'] ?? 'Program'),
            'timestamp'   => $order['created_at'],
            'badge'       => 'Order',
            'status'      => 'info',
        ];

        // 2. Gateway Order Created (if razorpay_order_id set on order)
        if (!empty($order['razorpay_order_id'])) {
            $timeline[] = [
                'type'        => 'gateway_order',
                'title'       => 'Razorpay Order Prepared',
                'description' => 'Gateway order ' . $order['razorpay_order_id'] . ' generated',
                'timestamp'   => $order['created_at'],
                'badge'       => 'Gateway',
                'status'      => 'info',
            ];
        }

        // 3. Payment Attempts
        if (!empty($payments)) {
            foreach ($payments as $idx => $p) {
                $attemptNum = $idx + 1;
                $pStatus = strtolower((string) ($p['status'] ?? 'created'));
                
                if ($pStatus === 'captured') {
                    $timeline[] = [
                        'type'        => 'payment_captured',
                        'title'       => "Payment Attempt #{$attemptNum} Captured",
                        'description' => "Signature verified & captured. Payment ID: " . ($p['razorpay_payment_id'] ?? '—'),
                        'timestamp'   => $p['verified_at'] ?? $p['updated_at'] ?? $p['created_at'],
                        'badge'       => 'Captured',
                        'status'      => 'success',
                    ];
                } elseif ($pStatus === 'failed') {
                    $timeline[] = [
                        'type'        => 'payment_failed',
                        'title'       => "Payment Attempt #{$attemptNum} Failed",
                        'description' => !empty($p['failure_reason']) ? "Reason: {$p['failure_reason']}" : 'Gateway or signature verification failed',
                        'timestamp'   => $p['updated_at'] ?? $p['created_at'],
                        'badge'       => 'Failed',
                        'status'      => 'danger',
                    ];
                } elseif ($pStatus === 'attempted') {
                    $timeline[] = [
                        'type'        => 'payment_attempted',
                        'title'       => "Payment Attempt #{$attemptNum} Initialized",
                        'description' => 'Checkout payment window opened by user',
                        'timestamp'   => $p['created_at'],
                        'badge'       => 'Attempted',
                        'status'      => 'warning',
                    ];
                } else {
                    $timeline[] = [
                        'type'        => 'payment_created',
                        'title'       => "Payment Attempt #{$attemptNum} Created",
                        'description' => 'Payment record initialized',
                        'timestamp'   => $p['created_at'],
                        'badge'       => 'Created',
                        'status'      => 'neutral',
                    ];
                }
            }
        }

        // 4. Order Status Final Transition
        if (strtolower((string) $order['status']) === 'paid') {
            $timeline[] = [
                'type'        => 'order_paid',
                'title'       => 'Order Confirmed & Paid',
                'description' => 'Purchase status marked as Paid.',
                'timestamp'   => $order['updated_at'] ?? $order['created_at'],
                'badge'       => 'Paid',
                'status'      => 'success',
            ];
        }

        // Sort timeline by timestamp ascending
        usort($timeline, function ($a, $b) {
            return strtotime($a['timestamp']) <=> strtotime($b['timestamp']);
        });

        return view('admin/orders/show', [
            'title'               => "Order #{$order['order_number']}",
            'description'         => 'Detailed purchase snapshot, customer information, and payment attempts history.',
            'admin'               => $admin,
            'order'               => $order,
            'payments'            => $payments,
            'capturedPayment'     => $capturedPayment,
            'latestPaymentStatus' => $latestPaymentStatus,
            'timeline'            => $timeline,
        ]);
    }
}

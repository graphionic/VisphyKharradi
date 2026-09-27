<?php

namespace App\Services;

use App\Domain\OrderStatus;
use App\Domain\PaymentStatus;
use App\Models\OrderModel;
use App\Models\PackageModel;
use App\Models\PaymentModel;
use App\Services\Checkout\CheckoutException;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/** Local purchase contracts and atomic, verification-gated payment transitions. */
class CheckoutService
{
    private BaseConnection $db;
    private OrderModel $orders;
    private PaymentModel $payments;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->orders = new OrderModel($this->db);
        $this->payments = new PaymentModel($this->db);
    }

    public function package(int $id): array
    {
        $package = (new PackageModel($this->db))->where('is_active', 1)->find($id);
        if (!$package) {
            throw new CheckoutException('This program is no longer available. Please choose another program.', 404);
        }
        return $package;
    }

    public function summary(array $package): array
    {
        return [
            'id' => (int) $package['id'],
            'name' => $package['name'],
            'amount' => RazorpayService::paise((string) $package['selling_price']),
            'currency' => 'INR',
            'duration' => $this->duration($package),
        ];
    }

    private function duration(array $package): ?string
    {
        return empty($package['duration_value']) || empty($package['duration_unit']) ? null
            : PackageService::formatDuration((int) $package['duration_value'], $package['duration_unit']);
    }

    public function createLocal(int $packageId, array $customer): array
    {
        // No client-supplied price or duration enters this contract.
        $package = $this->package($packageId);
        RazorpayService::paise((string) $package['selling_price']);
        return $this->transaction(function () use ($package, $customer) {
            $id = $this->orders->insert([
                'order_number' => (new OrderNumberService($this->db))->generateInTransaction((int) date('Y')),
                'package_id' => $package['id'],
                'package_name_snapshot' => $package['name'],
                'package_slug_snapshot' => $package['slug'],
                'package_price_snapshot' => $package['selling_price'],
                'package_duration_snapshot' => $this->duration($package),
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'currency' => 'INR',
                'subtotal' => $package['selling_price'],
                'discount_amount' => '0.00',
                'total_amount' => $package['selling_price'],
                'status' => OrderStatus::Pending->value,
            ]);
            if (!$id) throw new \RuntimeException('Unable to create checkout order.');
            return $this->order((int) $id);
        });
    }

    public function order(int $id): array
    {
        $order = $this->orders->find($id);
        if (!$order) throw new CheckoutException('Checkout has expired. Please select your program again.', 404);
        return $order;
    }

    public function prepare(int $id, RazorpayService $gateway): array
    {
        $order = $this->order($id);
        if ($order['status'] === OrderStatus::Paid->value) return $this->success($order);
        if ($order['status'] !== OrderStatus::Pending->value) {
            throw new CheckoutException('This checkout is no longer available. Please select your program again.', 409);
        }
        $this->package((int) $order['package_id']);
        if (empty($order['razorpay_order_id'])) {
            // External API outside DB transaction. Failed API calls leave a recoverable pending order.
            $remote = $gateway->createOrder($order);
            if (!preg_match('/^order_[A-Za-z0-9]+$/D', (string) ($remote['id'] ?? ''))
                || (int) ($remote['amount'] ?? 0) !== RazorpayService::paise((string) $order['total_amount'])
                || ($remote['currency'] ?? '') !== $order['currency']) {
                throw new \RuntimeException('Invalid gateway order response.');
            }
            $this->transaction(function () use ($order, $remote) {
                $locked = $this->lockOrder((int) $order['id']);
                if (!empty($locked['razorpay_order_id'])) return;
                $this->must($this->orders->update($order['id'], ['razorpay_order_id' => $remote['id']]));
                $this->must($this->payments->insert([
                    'order_id' => $order['id'],
                    'razorpay_order_id' => $remote['id'],
                    'amount' => $order['total_amount'],
                    'currency' => $order['currency'],
                    'status' => PaymentStatus::Created->value,
                ]));
            });
            $order = $this->order($id);
        }
        return [
            'status' => 'ready',
            'key_id' => $gateway->publicKey(),
            'order_number' => $order['order_number'],
            'razorpay_order_id' => $order['razorpay_order_id'],
            'amount' => RazorpayService::paise((string) $order['total_amount']),
            'currency' => $order['currency'],
            'package_name' => $order['package_name_snapshot'],
            'duration' => $order['package_duration_snapshot'],
            'customer' => ['name' => $order['customer_name'], 'email' => $order['customer_email'], 'contact' => $order['customer_phone']],
        ];
    }

    public function verify(int $id, array $proof, RazorpayService $gateway): array
    {
        $order = $this->order($id);
        $trustedId = (string) $order['razorpay_order_id'];
        if ($trustedId === '' || !hash_equals($trustedId, $proof['razorpay_order_id'])) {
            throw new CheckoutException('Payment does not belong to this checkout.', 422);
        }
        $gateway->verifySignature($trustedId, $proof['razorpay_payment_id'], $proof['razorpay_signature']);
        // Signature alone proves authenticity, not capture. Confirm payment server-to-server.
        $remote = $gateway->fetchPayment($proof['razorpay_payment_id']);
        if (($remote['id'] ?? '') !== $proof['razorpay_payment_id']
            || ($remote['order_id'] ?? '') !== $trustedId
            || ($remote['currency'] ?? '') !== $order['currency']
            || (int) ($remote['amount'] ?? 0) !== RazorpayService::paise((string) $order['total_amount'])) {
            throw new CheckoutException('Payment details could not be confirmed. Please retry verification.', 422);
        }
        if (($remote['status'] ?? '') !== 'captured') {
            // Never turn an authorised payment into "paid" or invite a second charge.
            return ['status' => 'pending', 'order_number' => $order['order_number'],
                'message' => 'Your payment is still being confirmed. Please check again; do not pay again.'];
        }
        return $this->transaction(function () use ($order, $proof, $remote) {
            $locked = $this->lockOrder((int) $order['id']);
            $captured = $this->payments->where('order_id', $order['id'])->where('status', PaymentStatus::Captured->value)->first();
            if ($captured) {
                if ($captured['razorpay_payment_id'] !== $proof['razorpay_payment_id']) {
                    throw new CheckoutException('This order already has a confirmed payment.', 409);
                }
                if ($locked['status'] !== OrderStatus::Paid->value) throw new \RuntimeException('Inconsistent payment state.');
                return $this->success($locked);
            }
            if ($locked['status'] === OrderStatus::Paid->value) throw new \RuntimeException('Inconsistent order state.');
            $attempt = $this->payments->where('order_id', $order['id'])
                ->where('razorpay_order_id', $order['razorpay_order_id'])->where('status', PaymentStatus::Created->value)->first();
            if (!$attempt) throw new \RuntimeException('Payment attempt is missing.');
            $filtered = array_intersect_key($remote, array_flip(['id', 'order_id', 'amount', 'currency', 'status', 'method']));
            $this->must($this->payments->update($attempt['id'], [
                'razorpay_payment_id' => $proof['razorpay_payment_id'],
                'razorpay_signature' => $proof['razorpay_signature'],
                'status' => PaymentStatus::Captured->value,
                'method' => substr((string) ($remote['method'] ?? ''), 0, 30),
                'verified_at' => date('Y-m-d H:i:s'),
                'gateway_response' => json_encode($filtered, JSON_THROW_ON_ERROR),
            ]));
            $this->must($this->orders->update($order['id'], ['status' => OrderStatus::Paid->value]));
            return $this->success($locked);
        });
    }

    private function success(array $order): array
    {
        $settingModel = new \App\Models\SettingModel();

        $rowForm = $settingModel->where('setting_key', 'post_payment.google_form_url')->first();
        $googleFormUrl = trim((string) ($rowForm['setting_value'] ?? ''));

        $rowWaNum = $settingModel->where('setting_key', 'post_payment.whatsapp_number')->first();
        $whatsappNumber = trim((string) ($rowWaNum['setting_value'] ?? ''));

        $rowWaMsg = $settingModel->where('setting_key', 'post_payment.whatsapp_message')->first();
        $whatsappTemplate = (string) ($rowWaMsg['setting_value'] ?? '');
        if (trim($whatsappTemplate) === '') {
            $whatsappTemplate = "Hi Visphy, I have completed payment for my order {order_number} ({program_name}). My name is {customer_name}.";
        }

        $replacements = [
            '{order_number}'  => $order['order_number'] ?? '',
            '{program_name}'  => $order['package_name_snapshot'] ?? '',
            '{customer_name}' => $order['customer_name'] ?? '',
        ];
        $formattedMsg = str_replace(array_keys($replacements), array_values($replacements), $whatsappTemplate);

        $whatsappUrl = '';
        if ($whatsappNumber !== '') {
            $digits = preg_replace('/[^\d]/', '', $whatsappNumber);
            if ($digits !== '') {
                $whatsappUrl = 'https://wa.me/' . $digits . '?text=' . rawurlencode($formattedMsg);
            }
        }

        return [
            'status'           => 'paid',
            'order_number'     => $order['order_number'],
            'customer_name'    => $order['customer_name'] ?? '',
            'package_name'     => $order['package_name_snapshot'],
            'duration'         => $order['package_duration_snapshot'],
            'amount'           => RazorpayService::paise((string) $order['total_amount']),
            'formatted_amount' => '₹' . number_format((float) $order['total_amount'], 2),
            'currency'         => $order['currency'],
            'google_form_url'  => $googleFormUrl,
            'whatsapp_url'     => $whatsappUrl,
        ];
    }

    private function lockOrder(int $id): array
    {
        $table = $this->db->protectIdentifiers($this->db->prefixTable('orders'));
        $row = $this->db->query("SELECT * FROM {$table} WHERE id = ? FOR UPDATE", [$id])->getRowArray();
        if (!$row) throw new CheckoutException('Order not found.', 404);
        return $row;
    }

    private function must(mixed $result): void
    {
        if ($result === false) throw new \RuntimeException('Checkout could not be saved.');
    }

    private function transaction(callable $operation): mixed
    {
        if (!$this->db->transBegin()) throw new \RuntimeException('Could not begin checkout transaction.');
        try {
            $result = $operation();
            if (!$this->db->transStatus() || !$this->db->transCommit()) throw new \RuntimeException('Checkout transaction failed.');
            return $result;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}

<?php

namespace App\Services;

use App\Domain\OrderStatus;
use App\Domain\PaymentStatus;
use App\Models\OrderModel;
use App\Models\PaymentModel;
use App\Services\Checkout\CheckoutException;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Config\Razorpay;
use Razorpay\Api\Errors\SignatureVerificationError;
use Razorpay\Api\Utility;

/** Authenticated capture reconciliation; never inserts orders or payment attempts. */
class RazorpayWebhookService
{
    private BaseConnection $db;
    private Razorpay $config;

    public function __construct(?BaseConnection $db = null, ?Razorpay $config = null)
    {
        $this->db = $db ?? Database::connect();
        $this->config = $config ?? config(Razorpay::class);
    }

    public function process(string $rawBody, string $signature): array
    {
        if ($rawBody === '' || strlen($rawBody) > 262144) {
            throw new CheckoutException('Invalid webhook body size.', 413);
        }
        if (!preg_match('/^[a-f0-9]{64}$/D', $signature)) {
            throw new CheckoutException('Missing or invalid webhook signature.', 401);
        }
        if ($this->config->webhookSecret === '') {
            throw new CheckoutException('Webhook secret is not configured.', 503);
        }
        try {
            // Verify the exact received bytes BEFORE JSON parsing. No API-key dependency.
            (new Utility())->verifyWebhookSignature($rawBody, $signature, $this->config->webhookSecret);
        } catch (SignatureVerificationError $e) {
            throw new CheckoutException('Webhook signature verification failed.', 401);
        }
        try {
            $event = json_decode($rawBody, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CheckoutException('Malformed webhook JSON.', 400);
        }
        if (!is_array($event) || ($event['entity'] ?? '') !== 'event'
            || !is_string($event['event'] ?? null) || !preg_match('/^[a-z_.]{1,64}$/D', $event['event'])) {
            throw new CheckoutException('Invalid webhook envelope.', 400);
        }
        $type = $event['event'];
        if (!in_array($type, ['payment.captured', 'order.paid'], true)) {
            return ['status' => 'ignored', 'event' => $type];
        }
        $payment = $this->entity($event, 'payment');
        if (($payment['entity'] ?? '') !== 'payment'
            || !is_string($payment['id'] ?? null) || !preg_match('/^pay_[A-Za-z0-9]{1,40}$/D', $payment['id'])
            || !is_string($payment['order_id'] ?? null) || !preg_match('/^order_[A-Za-z0-9]{1,40}$/D', $payment['order_id'])
            || ($payment['status'] ?? '') !== 'captured' || ($payment['captured'] ?? null) !== true
            || !is_int($payment['amount'] ?? null) || $payment['amount'] <= 0
            || !is_string($payment['currency'] ?? null) || !preg_match('/^[A-Z]{3}$/D', $payment['currency'])) {
            throw new CheckoutException('Invalid captured payment entity.', 422);
        }
        if ($type === 'order.paid') {
            $remoteOrder = $this->entity($event, 'order');
            if (($remoteOrder['entity'] ?? '') !== 'order' || ($remoteOrder['id'] ?? '') !== $payment['order_id']
                || ($remoteOrder['status'] ?? '') !== 'paid' || ($remoteOrder['currency'] ?? '') !== $payment['currency']
                || ($remoteOrder['amount'] ?? null) !== $payment['amount']
                || ($remoteOrder['amount_paid'] ?? null) !== $payment['amount'] || ($remoteOrder['amount_due'] ?? null) !== 0) {
                throw new CheckoutException('Order and payment webhook entities do not match.', 422);
            }
        }
        return $this->reconcile($payment, $type);
    }

    private function entity(array $event, string $name): array
    {
        $payload = $event['payload'] ?? null;
        $wrapper = is_array($payload) ? ($payload[$name] ?? null) : null;
        $entity = is_array($wrapper) ? ($wrapper['entity'] ?? null) : null;
        if (!is_array($entity)) throw new CheckoutException('Missing webhook entity.', 422);
        return $entity;
    }

    private function reconcile(array $payment, string $type): array
    {
        if (!$this->db->transBegin()) throw new \RuntimeException('Could not begin webhook transaction.');
        try {
            // Same order-row lock as CheckoutService: browser and webhook cannot race the transition.
            $table = $this->db->protectIdentifiers($this->db->prefixTable('orders'));
            $order = $this->db->query("SELECT * FROM {$table} WHERE razorpay_order_id = ? FOR UPDATE", [$payment['order_id']])->getRowArray();
            if (!$order) {
                // A delivery may race the local save. Do not acknowledge and lose the capture.
                throw new CheckoutException('Local webhook order is not yet available.', 503);
            }
            $expected = RazorpayService::paise((string) $order['total_amount']);
            if ($expected !== $payment['amount'] || $order['currency'] !== $payment['currency']) {
                throw new CheckoutException('Webhook amount or currency does not match the order.', 422);
            }
            $payments = new PaymentModel($this->db);
            $attempts = $payments->where('order_id', $order['id'])->findAll();
            $captured = array_values(array_filter($attempts, static fn ($row) => $row['status'] === PaymentStatus::Captured->value));
            if (count($captured) > 1 || ($captured && ($captured[0]['razorpay_payment_id'] !== $payment['id']
                || $captured[0]['razorpay_order_id'] !== $payment['order_id']))) {
                throw new CheckoutException('Order already has a different captured payment.', 409);
            }
            if ($order['status'] === OrderStatus::Paid->value && !$captured) {
                throw new CheckoutException('Paid order has an inconsistent payment history.', 409);
            }
            // A known gateway payment must belong to this exact local order and gateway order.
            $known = $payments->where('razorpay_payment_id', $payment['id'])->first();
            if ($known && ((string) $known['order_id'] !== (string) $order['id']
                || $known['razorpay_order_id'] !== $payment['order_id'])) {
                throw new CheckoutException('Gateway payment is linked to a different order.', 409);
            }
            $attempt = $known;
            if (!$attempt) {
                $available = array_values(array_filter($attempts, static fn ($row) =>
                    $row['razorpay_order_id'] === $payment['order_id'] && empty($row['razorpay_payment_id'])
                    && in_array($row['status'], [PaymentStatus::Created->value, PaymentStatus::Attempted->value], true)));
                if (!$available) throw new CheckoutException('Local webhook payment attempt is not yet available.', 503);
                if (count($available) !== 1) throw new CheckoutException('Webhook payment attempt is ambiguous.', 409);
                $attempt = $available[0];
            }
            if (RazorpayService::paise((string) $attempt['amount']) !== $expected || $attempt['currency'] !== $order['currency']) {
                throw new CheckoutException('Payment attempt amount or currency does not match.', 422);
            }
            $alreadyPaid = $order['status'] === OrderStatus::Paid->value;
            $changes = [];
            if (!(bool) $attempt['webhook_verified']) $changes['webhook_verified'] = 1;
            if ($attempt['status'] !== PaymentStatus::Captured->value) {
                $changes += [
                    'razorpay_payment_id' => $payment['id'],
                    'status' => PaymentStatus::Captured->value,
                    'failure_reason' => null,
                ];
                $snapshot = array_intersect_key($payment, array_flip(['id', 'order_id', 'amount', 'currency', 'status']));
                if (isset($payment['method']) && is_string($payment['method']) && preg_match('/^[a-z_]{1,30}$/D', $payment['method'])) {
                    $changes['method'] = $payment['method'];
                    $snapshot['method'] = $payment['method'];
                }
            }
            if (isset($snapshot)) $changes['gateway_response'] = json_encode($snapshot, JSON_THROW_ON_ERROR);
            if (empty($attempt['verified_at'])) $changes['verified_at'] = date('Y-m-d H:i:s');
            // Preserve any browser signature; webhook signatures authenticate a different message.
            if ($changes && !$payments->update($attempt['id'], $changes)) throw new \RuntimeException('Could not reconcile payment.');
            if (!$alreadyPaid && !(new OrderModel($this->db))->update($order['id'], ['status' => OrderStatus::Paid->value])) {
                throw new \RuntimeException('Could not reconcile order.');
            }
            if (!$this->db->transStatus() || !$this->db->transCommit()) throw new \RuntimeException('Webhook transaction failed.');
            return ['status' => !$alreadyPaid ? 'reconciled' : ($changes ? 'confirmed' : 'duplicate'),
                'event' => $type, 'order_number' => $order['order_number'],
                'razorpay_order_id' => $payment['order_id'], 'razorpay_payment_id' => $payment['id']];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}

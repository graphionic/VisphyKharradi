<?php

namespace App\Services;

use App\Services\Checkout\CheckoutException;
use Config\Razorpay;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

/** Only server-side code may access the SDK or secret keys. */
class RazorpayService
{
    private Api $api;
    private Razorpay $config;

    public function __construct()
    {
        $this->config = config(Razorpay::class);
        if ($this->config->keyId === '' || $this->config->keySecret === '') {
            throw new CheckoutException('Online payment is not available yet. Please try again later.', 503);
        }
        $this->api = new Api($this->config->keyId, $this->config->keySecret);
    }

    public function publicKey(): string
    {
        return $this->config->keyId;
    }

    /** Decimal DB money -> integer paise, without floating point arithmetic. */
    public static function paise(string $amount): int
    {
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $amount)) {
            throw new CheckoutException('This program cannot be purchased at the moment.', 409);
        }
        [$rupees, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $paise = (int) $rupees * 100 + (int) str_pad($fraction, 2, '0');
        if ($paise < 100) {
            throw new CheckoutException('This program cannot be purchased online at the moment.', 409);
        }
        return $paise;
    }

    public function createOrder(array $order): array
    {
        return $this->api->order->create([
            'receipt' => $order['order_number'],
            'amount' => self::paise((string) $order['total_amount']),
            'currency' => $order['currency'],
            'partial_payment' => false,
        ])->toArray();
    }

    public function verifySignature(string $trustedOrderId, string $paymentId, string $signature): void
    {
        try {
            $this->api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $trustedOrderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);
        } catch (SignatureVerificationError $e) {
            throw new CheckoutException('Payment could not be verified. Please retry verification.', 422);
        }
    }

    public function fetchPayment(string $paymentId): array
    {
        return $this->api->payment->fetch($paymentId)->toArray();
    }
}

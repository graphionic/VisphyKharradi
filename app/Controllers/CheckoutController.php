<?php

namespace App\Controllers;

use App\Services\Checkout\CheckoutException;
use App\Services\CheckoutService;
use App\Services\RazorpayService;
use CodeIgniter\HTTP\ResponseInterface;

/** JSON-only checkout endpoints: no checkout or success-page redirects. */
class CheckoutController extends BaseController
{
    public function session(): ResponseInterface
    {
        return $this->respond([]);
    }

    public function package(int $id): ResponseInterface
    {
        return $this->handle(function () use ($id) {
            $this->throttle('summary', 60);
            $service = new CheckoutService();
            return ['package' => $service->summary($service->package($id))];
        });
    }

    public function createOrder(): ResponseInterface
    {
        return $this->handle(function () {
            $this->throttle('create', 10);
            $input = $this->input();
            $data = [
                'checkout_id' => $this->string($input, 'checkout_id'),
                'package_id' => $this->string($input, 'package_id'),
                'name' => trim($this->string($input, 'name')),
                'email' => trim($this->string($input, 'email')),
                'phone' => preg_replace('/[\s().-]+/', '', trim($this->string($input, 'phone'))),
            ];
            $errors = [];
            if (!preg_match('/^[a-f0-9-]{36}$/D', $data['checkout_id'])) $errors['checkout_id'] = 'Please reopen checkout.';
            if (!ctype_digit($data['package_id']) || (int) $data['package_id'] < 1) $errors['package_id'] = 'Please select a program.';
            if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 100 || preg_match('/[\x00-\x1F\x7F]/u', $data['name'])) $errors['name'] = 'Enter your full name (2–100 characters).';
            if (strlen($data['email']) > 190 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
            if (preg_match('/^[6-9]\d{9}$/D', $data['phone'])) $data['phone'] = '+91' . $data['phone'];
            elseif (preg_match('/^91[6-9]\d{9}$/D', $data['phone'])) $data['phone'] = '+' . $data['phone'];
            elseif (!preg_match('/^\+[1-9]\d{7,13}$/D', $data['phone'])) $errors['phone'] = 'Enter a 10-digit Indian mobile number or include your country code.';
            if ($errors) return ['_code' => 422, 'message' => 'Please check your details.', 'errors' => $errors];

            // PHP session lock serializes duplicate submissions from this browser.
            // Order numbers alone never grant access to another customer's purchase.
            $session = session();
            $map = $session->get('checkout_orders') ?? [];
            $key = $data['checkout_id'];
            $fingerprint = hash('sha256', json_encode([$data['package_id'], $data['name'], $data['email'], $data['phone']], JSON_THROW_ON_ERROR));
            $gateway = new RazorpayService(); // Configuration check before local financial writes.
            $service = new CheckoutService();
            if (isset($map[$key])) {
                if (!hash_equals($map[$key]['fingerprint'], $fingerprint)) {
                    throw new CheckoutException('This checkout already uses different details. Please reopen it to make changes.', 409);
                }
                $id = (int) $map[$key]['id'];
            } else {
                $order = $service->createLocal((int) $data['package_id'], $data);
                $id = (int) $order['id'];
                // Bound memory; preserve recent requests for safe retries after network loss.
                if (count($map) >= 20) array_shift($map);
                $map[$key] = ['id' => $id, 'fingerprint' => $fingerprint];
                $session->set('checkout_orders', $map);
            }
            return $service->prepare($id, $gateway);
        });
    }

    public function verify(): ResponseInterface
    {
        return $this->handle(function () {
            $this->throttle('verify', 30);
            $input = $this->input();
            $key = $this->string($input, 'checkout_id');
            $map = session()->get('checkout_orders') ?? [];
            if (!isset($map[$key])) throw new CheckoutException('Checkout session has expired. Keep your payment reference and contact support if you have paid.', 403);
            $proof = [];
            foreach (['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'] as $field) $proof[$field] = $this->string($input, $field);
            if (!preg_match('/^order_[A-Za-z0-9]{1,40}$/D', $proof['razorpay_order_id'])
                || !preg_match('/^pay_[A-Za-z0-9]{1,40}$/D', $proof['razorpay_payment_id'])
                || !preg_match('/^[a-f0-9]{64}$/D', $proof['razorpay_signature'])) {
                throw new CheckoutException('Payment response is incomplete. Please retry verification.', 422);
            }
            $result = (new CheckoutService())->verify((int) $map[$key]['id'], $proof, new RazorpayService());
            if ($result['status'] === 'pending') $result['_code'] = 202;
            return $result;
        });
    }

    private function input(): array
    {
        if (strlen($this->request->getBody()) > 4096) throw new CheckoutException('Checkout request is too large.', 413);
        try {
            $data = $this->request->getJSON(true);
        } catch (\Throwable $e) {
            throw new CheckoutException('Invalid checkout request.', 400);
        }
        if (!is_array($data)) throw new CheckoutException('Invalid checkout request.', 400);
        return $data;
    }

    private function string(array $input, string $key): string
    {
        return isset($input[$key]) && (is_string($input[$key]) || is_int($input[$key])) ? (string) $input[$key] : '';
    }

    private function throttle(string $action, int $limit): void
    {
        $key = 'checkout_' . $action . '_' . hash('sha256', $this->request->getIPAddress());
        if (!service('throttler')->check($key, $limit, 60)) {
            $this->response->setHeader('Retry-After', '60');
            throw new CheckoutException('Please wait a minute before trying again.', 429);
        }
    }

    private function handle(callable $operation): ResponseInterface
    {
        try {
            $result = $operation();
            $code = $result['_code'] ?? 200;
            unset($result['_code']);
            return $this->respond($result, $code);
        } catch (CheckoutException $e) {
            return $this->respond(['message' => $e->getMessage()], $e->httpStatus);
        } catch (\Throwable $e) {
            // Never log SDK response bodies, customer details or credentials.
            log_message('error', 'Checkout request failed [{type}]: {message} in {file}:{line}', [
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return $this->respond(['message' => 'Payment service is temporarily unavailable. Please try again.'], 503);
        }
    }

    private function respond(array $data, int $code = 200): ResponseInterface
    {
        $data['csrf'] = ['header' => csrf_header(), 'hash' => csrf_hash()];
        return $this->response->setStatusCode($code)->setHeader('Cache-Control', 'no-store, private')->setJSON($data);
    }
}

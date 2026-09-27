<?php

namespace App\Controllers;

use App\Services\Checkout\CheckoutException;
use App\Services\RazorpayWebhookService;
use CodeIgniter\HTTP\ResponseInterface;

class RazorpayWebhookController extends BaseController
{
    public function receive(): ResponseInterface
    {
        // Event ID is only a sanitized delivery correlation label, not trusted deduplication data.
        $eventId = $this->request->getHeaderLine('X-Razorpay-Event-Id');
        $eventId = preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $eventId) ? $eventId : 'unavailable';
        $this->response->setHeader('Cache-Control', 'no-store, private');
        try {
            $result = (new RazorpayWebhookService())->process(
                $this->request->getBody(),
                $this->request->getHeaderLine('X-Razorpay-Signature')
            );
            // One notice per actual transition; repeated deliveries are diagnostic debug entries only.
            $level = in_array($result['status'], ['reconciled', 'confirmed'], true) ? 'notice' : 'debug';
            log_message($level, 'Razorpay webhook {delivery}: {event} {outcome}; order={order}, payment={payment}.', [
                'delivery' => $eventId, 'event' => $result['event'], 'outcome' => $result['status'],
                'order' => $result['order_number'] ?? '-', 'payment' => $result['razorpay_payment_id'] ?? '-',
            ]);
            return $this->response->setJSON(['received' => true, 'status' => $result['status']]);
        } catch (CheckoutException $e) {
            log_message('warning', 'Razorpay webhook {delivery} rejected ({code}): {reason}', [
                'delivery' => $eventId, 'code' => $e->httpStatus, 'reason' => $e->getMessage(),
            ]);
            return $this->response->setStatusCode($e->httpStatus)->setJSON(['received' => false]);
        } catch (\Throwable $e) {
            // Do not log raw requests, signatures, secrets, customer data or SDK/SQL exception bodies.
            log_message('error', 'Razorpay webhook {delivery} processing failed ({type}).', [
                'delivery' => $eventId, 'type' => get_class($e),
            ]);
            return $this->response->setStatusCode(503)->setJSON(['received' => false]);
        }
    }
}

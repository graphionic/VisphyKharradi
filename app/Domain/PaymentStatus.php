<?php

namespace App\Domain;

/**
 * PaymentStatus
 *
 * Central definition for payments.status — one order may have multiple attempts.
 * Only one captured per order (enforced via razorpay_payment_id UNIQUE + app logic).
 */
enum PaymentStatus: string
{
    case Created   = 'created';   // locally created before Razorpay call
    case Attempted = 'attempted'; // Razorpay overlay opened
    case Captured  = 'captured';  // signature verified — money captured
    case Failed    = 'failed';    // failed at gateway or verification
    case Cancelled = 'cancelled'; // user dismissed
}

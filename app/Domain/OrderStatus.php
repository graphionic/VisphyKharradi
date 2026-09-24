<?php

namespace App\Domain;

/**
 * OrderStatus
 *
 * Central definition for orders.status — prevents magic strings.
 * Stored as VARCHAR(20) in DB for migration ease (no ENUM).
 *
 * Values align with DATABASE_SCHEMA.md & PAYMENT_FLOW.md:
 *  - pending  — created locally, Razorpay order not yet created or payment not attempted
 *  - paid     — signature verified (authoritative)
 *  - failed   — verification failed, gateway failure, or amount mismatch
 *  - cancelled— user cancelled or admin cancelled
 *
 * Note: payment_pending is intentionally not a separate state — pending covers
 * all pre-paid states. If needed later, add without ENUM migration pain.
 */
enum OrderStatus: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Failed    = 'failed';
    case Cancelled = 'cancelled';
}

<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PaymentModel
 *
 * Separate from orders — one order may have multiple attempts (retry).
 * Only one captured per order. No secrets stored.
 */
class PaymentModel extends Model
{
    protected $table          = 'payments';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'order_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'amount',
        'currency',
        'status',
        'method',
        'failure_reason',
        'verified_at',
        'webhook_verified',
        'gateway_response',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'order_id'            => 'required|is_natural_no_zero',
        'razorpay_order_id'   => 'required|max_length[50]',
        'amount'              => 'required|decimal',
        'currency'            => 'required|exact_length[3]',
        'status'              => 'required|in_list[created,attempted,captured,failed,cancelled]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}

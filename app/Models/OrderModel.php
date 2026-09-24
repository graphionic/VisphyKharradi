<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * OrderModel
 *
 * Historical purchase contract — snapshots preserve history. Never rely on
 * package row to reconstruct purchase. Financial history immutable.
 */
class OrderModel extends Model
{
    protected $table          = 'orders';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'order_number',
        'package_id',
        'package_name_snapshot',
        'package_slug_snapshot',
        'package_price_snapshot',
        'package_duration_snapshot',
        'customer_name',
        'customer_email',
        'customer_phone',
        'currency',
        'subtotal',
        'discount_amount',
        'total_amount',
        'status',
        'razorpay_order_id',
        'notes',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'order_number'            => 'required|max_length[20]|is_unique[orders.order_number,id,{id}]',
        'package_name_snapshot'   => 'required|max_length[150]',
        'package_slug_snapshot'   => 'required|max_length[190]',
        'package_price_snapshot'  => 'required|decimal',
        'customer_name'           => 'required|min_length[2]|max_length[100]',
        'customer_email'          => 'required|valid_email|max_length[190]',
        'customer_phone'          => 'required|max_length[15]',
        'currency'                => 'required|exact_length[3]',
        'subtotal'                => 'required|decimal',
        'total_amount'            => 'required|decimal',
        'status'                  => 'required|in_list[pending,paid,failed,cancelled]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}

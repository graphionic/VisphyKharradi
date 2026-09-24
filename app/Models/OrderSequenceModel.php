<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * OrderSequenceModel
 *
 * Year-scoped counter for FTP-YYYY-000001. No timestamps. PK is year.
 */
class OrderSequenceModel extends Model
{
    protected $table          = 'order_sequences';
    protected $primaryKey     = 'year';
    protected $useAutoIncrement = false;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'year',
        'last_number',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'year'        => 'required|is_natural_no_zero|greater_than[2000]|less_than[2100]',
        'last_number' => 'required|is_natural',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}

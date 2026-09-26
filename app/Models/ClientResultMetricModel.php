<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ClientResultMetricModel — Phase 07A: Extensible Before/After Metrics Model
 *
 * Zero Foreign Keys compliant. Stores flexible string values for metric before/after data.
 */
class ClientResultMetricModel extends Model
{
    protected $table            = 'client_result_metrics';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_result_id',
        'metric_name',
        'before_value',
        'after_value',
        'unit',
        'context',
        'measurement_start_date',
        'measurement_end_date',
        'display_order',
        'is_public',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation rules
    protected $validationRules = [
        'client_result_id'       => 'required|is_natural_no_zero',
        'metric_name'            => 'required|min_length[2]|max_length[100]',
        'before_value'           => 'required|max_length[100]',
        'after_value'            => 'required|max_length[100]',
        'unit'                   => 'permit_empty|max_length[50]',
        'context'                => 'permit_empty|max_length[255]',
        'measurement_start_date' => 'permit_empty|valid_date',
        'measurement_end_date'   => 'permit_empty|valid_date',
        'display_order'          => 'required|is_natural',
        'is_public'              => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}

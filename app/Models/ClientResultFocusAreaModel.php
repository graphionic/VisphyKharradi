<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ClientResultFocusAreaModel — Phase 07B.1: Repeatable Transformation / Focus Area Pills
 *
 * Zero Foreign Keys compliant. Stores repeatable short focus labels for Client Results.
 */
class ClientResultFocusAreaModel extends Model
{
    protected $table            = 'client_result_focus_areas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_result_id',
        'label',
        'display_order',
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
        'client_result_id' => 'required|is_natural_no_zero',
        'label'            => 'required|min_length[1]|max_length[100]',
        'display_order'    => 'required|is_natural',
    ];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}

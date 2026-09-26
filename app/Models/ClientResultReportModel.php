<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ClientResultReportModel — Phase 07A: Supporting Evidence & Lab Reports Model
 *
 * Zero Foreign Keys compliant. Mandatory privacy default (is_public = 0).
 */
class ClientResultReportModel extends Model
{
    protected $table            = 'client_result_reports';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_result_id',
        'report_title',
        'report_type',
        'report_date',
        'file_path',
        'file_mime',
        'file_size',
        'description',
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
        'client_result_id' => 'required|is_natural_no_zero',
        'report_title'     => 'required|min_length[3]|max_length[150]',
        'report_type'      => 'permit_empty|max_length[50]',
        'report_date'      => 'permit_empty|valid_date',
        'file_path'        => 'required|max_length[255]',
        'file_mime'        => 'required|max_length[100]',
        'file_size'        => 'required|is_natural_no_zero',
        'description'      => 'permit_empty|max_length[500]',
        'display_order'    => 'required|is_natural',
        'is_public'        => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}

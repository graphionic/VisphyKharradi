<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ClientResultMediaModel — Phase 07A: Visual Proof & Gallery Media Model
 *
 * Zero Foreign Keys compliant. Supports before, after, progress, and gallery types.
 */
class ClientResultMediaModel extends Model
{
    protected $table            = 'client_result_media';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'client_result_id',
        'media_type',
        'file_path',
        'thumbnail_path',
        'caption',
        'media_date',
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
        'media_type'       => 'required|in_list[before,after,progress,gallery]',
        'file_path'        => 'required|max_length[255]',
        'thumbnail_path'   => 'permit_empty|max_length[255]',
        'caption'          => 'permit_empty|max_length[300]',
        'media_date'       => 'permit_empty|valid_date',
        'display_order'    => 'required|is_natural',
        'is_public'        => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}

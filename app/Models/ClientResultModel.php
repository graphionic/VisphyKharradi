<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ClientResultModel — Phase 07A: Client Transformation / Testimonial Master Model
 *
 * Soft delete enabled. Zero Foreign Keys compliant.
 */
class ClientResultModel extends Model
{
    protected $table            = 'client_results';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'package_id',
        'program_name_snapshot',
        'client_display_name',
        'client_subtitle',
        'short_testimonial',
        'full_story',
        'journey_duration',
        'cover_image',
        'display_order',
        'is_featured',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Timestamps + soft delete
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation rules
    protected $validationRules = [
        'client_display_name'   => 'required|min_length[2]|max_length[100]',
        'short_testimonial'     => 'required|min_length[10]|max_length[500]',
        'full_story'            => 'required|min_length[20]',
        'package_id'            => 'permit_empty|is_natural_no_zero',
        'program_name_snapshot' => 'permit_empty|max_length[150]',
        'client_subtitle'       => 'permit_empty|max_length[150]',
        'journey_duration'      => 'permit_empty|max_length[50]',
        'cover_image'           => 'permit_empty|max_length[255]',
        'display_order'         => 'required|is_natural',
        'is_featured'           => 'permit_empty|in_list[0,1]',
        'is_active'             => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}

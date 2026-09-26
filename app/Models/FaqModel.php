<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * FaqModel — Phase 06: Dynamic FAQ System
 *
 * Manages FAQ entities with soft-deletes and validation.
 * Zero Foreign Keys constraint compliant.
 */
class FaqModel extends Model
{
    protected $table            = 'faqs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'question',
        'answer',
        'category',
        'display_order',
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
        'question'      => 'required|min_length[5]|max_length[500]',
        'answer'        => 'required|min_length[5]',
        'category'      => 'permit_empty|max_length[100]',
        'display_order' => 'required|is_natural',
        'is_active'     => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages = [];
    protected $skipValidation     = false;
    protected $beforeInsert       = [];
    protected $beforeUpdate       = [];
}

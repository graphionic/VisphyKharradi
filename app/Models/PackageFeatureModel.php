<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PackageFeatureModel
 *
 * Normalized features — never JSON/CSV. Ordered via (package_id, display_order).
 */
class PackageFeatureModel extends Model
{
    protected $table          = 'package_features';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'package_id',
        'feature_text',
        'display_order',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = '';

    protected $validationRules = [
        'package_id'   => 'required|is_natural_no_zero',
        'feature_text' => 'required|max_length[300]',
        'display_order'=> 'required|is_natural',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}

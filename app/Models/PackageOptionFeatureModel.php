<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PackageOptionFeatureModel
 *
 * Stores specific features/inclusions for a package duration option.
 * Logical reference to package_option_id (zero FK).
 */
class PackageOptionFeatureModel extends Model
{
    protected $table          = 'package_option_features';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'package_option_id',
        'feature_text',
        'sort_order',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'package_option_id' => 'required|is_natural_no_zero',
        'feature_text'      => 'required|min_length[1]|max_length[300]',
        'sort_order'        => 'permit_empty|is_natural',
    ];
}

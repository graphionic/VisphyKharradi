<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PackageOptionModel
 *
 * Stores dynamic duration and pricing options for parent packages.
 * Logical reference to package_id (zero FK).
 */
class PackageOptionModel extends Model
{
    protected $table          = 'package_options';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'package_id',
        'name',
        'duration_value',
        'duration_unit',
        'price',
        'short_description',
        'sort_order',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'package_id'     => 'required|is_natural_no_zero',
        'name'           => 'required|min_length[2]|max_length[150]',
        'duration_value' => 'required|is_natural_no_zero',
        'duration_unit'  => 'required|in_list[day,week,month,year,days,weeks,months,years]',
        'price'          => 'required|decimal|greater_than[0]',
        'sort_order'     => 'permit_empty|is_natural',
    ];
}

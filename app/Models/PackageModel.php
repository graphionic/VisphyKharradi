<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PackageModel
 *
 * Soft delete enabled — archive instead of hard delete when orders exist.
 * Only dynamic content in V1 landing.
 */
class PackageModel extends Model
{
    protected $table          = 'packages';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'name',
        'slug',
        'short_description',
        'full_description',
        'regular_price',
        'selling_price',
        'duration_value',
        'duration_unit',
        'badge',
        'cta_label',
        'google_form_url',
        'whatsapp_template',
        'featured_image',
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

    protected $validationRules = [
        'name'           => 'required|min_length[3]|max_length[150]',
        'slug'           => 'required|alpha_dash|max_length[190]|is_unique[packages.slug,id,{id}]',
        'selling_price'  => 'required|decimal|greater_than[0]',
        'regular_price'  => 'permit_empty|decimal|greater_than[0]',
        'duration_value' => 'permit_empty|is_natural_no_zero',
        'duration_unit'  => 'permit_empty|in_list[day,days,week,weeks,month,months,year,years]',
        'badge'          => 'permit_empty|max_length[50]',
        'cta_label'      => 'required|max_length[50]',
        'google_form_url'=> 'permit_empty|valid_url|max_length[2048]',
        'display_order'  => 'required|is_natural',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $beforeInsert = [];
    protected $beforeUpdate = [];
}

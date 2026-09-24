<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PackageGalleryImageModel — Phase 5D
 * Logical package_id reference only, no FK.
 */
class PackageGalleryImageModel extends Model
{
    protected $table          = 'package_gallery_images';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'package_id',
        'image_path',
        'original_name',
        'alt_text',
        'display_order',
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
        'image_path'   => 'required|max_length[255]',
        'display_order'=> 'required|is_natural',
    ];
}

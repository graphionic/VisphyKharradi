<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AdminModel
 *
 * Phase 2 — Admin data foundation only. No auth logic yet.
 */
class AdminModel extends Model
{
    protected $table          = 'admins';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'name',
        'email',
        'password_hash',
        'is_active',
        'last_login_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Timestamps
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = '';

    // Validation - minimal, service layer validates stricter
    protected $validationRules = [
        'name'          => 'required|min_length[2]|max_length[100]',
        'email'         => 'required|valid_email|max_length[190]',
        'password_hash' => 'required|max_length[255]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $beforeInsert = [];
    protected $beforeUpdate = [];
}

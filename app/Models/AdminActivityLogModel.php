<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AdminActivityLogModel
 *
 * Append-only audit trail. No updated_at. admin_id SET NULL on delete.
 */
class AdminActivityLogModel extends Model
{
    protected $table          = 'admin_activity_logs';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'admin_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $validationRules = [
        'action' => 'required|max_length[50]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}

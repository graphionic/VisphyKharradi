<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SettingModel
 *
 * Key-value store — never for secrets. Secrets stay in .env.
 */
class SettingModel extends Model
{
    protected $table          = 'settings';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields  = true;
    protected $allowedFields  = [
        'setting_key',
        'setting_value',
        'setting_type',
        'is_system',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'setting_key'  => 'required|max_length[100]|is_unique[settings.setting_key,id,{id}]',
        'setting_type' => 'required|in_list[string,text,url,number,boolean,json]',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}

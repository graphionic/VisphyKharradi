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
    public const PACKAGE_DESIGN_KEY = 'frontend.package_design';
    public const DEFAULT_PACKAGE_DESIGN = 'concept_02';
    public const ALLOWED_PACKAGE_DESIGNS = [
        'concept_01',
        'concept_02',
        'concept_04',
    ];

    /**
     * Resolve active package display design with concept_02 fallback
     */
    public function getPackageDisplayDesign(): string
    {
        $row = $this->where('setting_key', self::PACKAGE_DESIGN_KEY)->first();
        $val = trim((string) ($row['setting_value'] ?? ''));

        if (in_array($val, self::ALLOWED_PACKAGE_DESIGNS, true)) {
            return $val;
        }

        return self::DEFAULT_PACKAGE_DESIGN;
    }

    /**
     * Upsert a setting key-value pair cleanly
     */
    public function setSetting(string $key, ?string $value, string $type = 'string', bool $isSystem = false): bool
    {
        $existing = $this->where('setting_key', $key)->first();
        if ($existing) {
            return $this->update($existing['id'], [
                'setting_value' => $value,
                'setting_type'  => $type,
                'is_system'     => $isSystem ? 1 : 0,
            ]);
        }

        return (bool) $this->insert([
            'setting_key'   => $key,
            'setting_value' => $value,
            'setting_type'  => $type,
            'is_system'     => $isSystem ? 1 : 0,
        ]);
    }
}


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

    public const PACKAGE_LAYOUT_KEYS = [
        'desktop' => 'frontend.package_layout_desktop',
        'tablet'  => 'frontend.package_layout_tablet',
        'mobile'  => 'frontend.package_layout_mobile',
    ];
    public const DEFAULT_PACKAGE_LAYOUTS = [
        'desktop' => 'grid',
        'tablet'  => 'carousel',
        'mobile'  => 'carousel',
    ];
    public const ALLOWED_PACKAGE_LAYOUTS = ['grid', 'carousel'];

    public const PACKAGE_COUNT_PREFIXES = [
        'grid' => 'frontend.package_grid_columns.',
        'carousel' => 'frontend.package_carousel_slides.',
    ];
    public const PACKAGE_DEVICE_LIMITS = ['desktop' => 6, 'tablet' => 4, 'mobile' => 2];
    public const DEFAULT_PACKAGE_COUNTS = ['desktop' => 3, 'tablet' => 2, 'mobile' => 1];

    public function getPackageDisplayCounts(): array
    {
        $counts = array_fill_keys(array_keys(self::PACKAGE_COUNT_PREFIXES), self::DEFAULT_PACKAGE_COUNTS);
        $keys = [];
        foreach (self::PACKAGE_COUNT_PREFIXES as $prefix) {
            foreach (self::PACKAGE_DEVICE_LIMITS as $device => $max) {
                $keys[] = $prefix . $device;
            }
        }
        $rows = $this->whereIn('setting_key', $keys)->findAll();
        $values = array_column($rows, 'setting_value', 'setting_key');
        foreach (self::PACKAGE_COUNT_PREFIXES as $mode => $prefix) {
            foreach (self::PACKAGE_DEVICE_LIMITS as $device => $max) {
                $value = filter_var($values[$prefix . $device] ?? null, FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1, 'max_range' => $max]]);
                if ($value !== false) {
                    $counts[$mode][$device] = $value;
                }
            }
        }
        return $counts;
    }

    public function getPackageDisplayLayouts(): array
    {
        $layouts = self::DEFAULT_PACKAGE_LAYOUTS;
        $rows = $this->whereIn('setting_key', array_values(self::PACKAGE_LAYOUT_KEYS))->findAll();
        foreach ($rows as $row) {
            $device = array_search($row['setting_key'], self::PACKAGE_LAYOUT_KEYS, true);
            if ($device !== false && in_array($row['setting_value'], self::ALLOWED_PACKAGE_LAYOUTS, true)) {
                $layouts[$device] = $row['setting_value'];
            }
        }
        return $layouts;
    }

    /** Save design and layouts together, using the existing key-value store. */
    public function setPackageDisplay(string $design, array $layouts, ?array $counts = null): bool
    {
        if (!in_array($design, self::ALLOWED_PACKAGE_DESIGNS, true)) {
            return false;
        }
        foreach (self::PACKAGE_LAYOUT_KEYS as $device => $key) {
            if (!in_array($layouts[$device] ?? null, self::ALLOWED_PACKAGE_LAYOUTS, true)) {
                return false;
            }
        }
        $counts = $counts ?? $this->getPackageDisplayCounts();
        foreach (self::PACKAGE_COUNT_PREFIXES as $mode => $prefix) {
            foreach (self::PACKAGE_DEVICE_LIMITS as $device => $max) {
                $value = $counts[$mode][$device] ?? null;
                if (!is_int($value) || $value < 1 || $value > $max) {
                    return false;
                }
            }
        }
        try {
            if (!$this->db->transBegin()) {
                return false;
            }
            $saved = $this->setSetting(self::PACKAGE_DESIGN_KEY, $design, 'string', true);
            foreach (self::PACKAGE_LAYOUT_KEYS as $device => $key) {
                $saved = $this->setSetting($key, $layouts[$device], 'string', true) && $saved;
            }
            foreach (self::PACKAGE_COUNT_PREFIXES as $mode => $prefix) {
                foreach (self::PACKAGE_DEVICE_LIMITS as $device => $max) {
                    $saved = $this->setSetting($prefix . $device, (string) $counts[$mode][$device], 'number', true) && $saved;
                }
            }
            if (!$saved || !$this->db->transStatus()) {
                $this->db->transRollback();
                return false;
            }
            if (!$this->db->transCommit()) {
                $this->db->transRollback();
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Package display settings could not be saved: {message}', ['message' => $e->getMessage()]);
            return false;
        }
    }

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


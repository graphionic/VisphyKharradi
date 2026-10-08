<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: ModifyPackageDurationUnitToVarchar
 *
 * Converts packages.duration_unit from ENUM('days','weeks','months') to VARCHAR(20) NULL.
 * Allows generic duration units such as 'minutes' (e.g. 30-minute counselling sessions).
 * Safe forward migration — preserves existing values. ZERO Foreign Keys compliant.
 */
class ModifyPackageDurationUnitToVarchar extends Migration
{
    public function up(): void
    {
        $fields = [
            'duration_unit' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
            ],
        ];

        $this->forge->modifyColumn('packages', $fields);
    }

    public function down(): void
    {
        // Safe rollback check: verify no rows contain values outside historical ENUM('days','weeks','months')
        $builder = $this->db->table('packages');
        $builder->whereNotIn('duration_unit', ['days', 'weeks', 'months']);
        $builder->where('duration_unit IS NOT NULL', null, false);
        $builder->where('duration_unit !=', '');

        $unsupported = $builder->get()->getResultArray();

        if (!empty($unsupported)) {
            $invalidValues = array_unique(array_column($unsupported, 'duration_unit'));
            throw new \RuntimeException(
                'Cannot rollback migration ModifyPackageDurationUnitToVarchar: packages table contains duration_unit values ('
                . implode(', ', array_map(fn($v) => "'{$v}'", $invalidValues))
                . ') not supported by historical ENUM.'
            );
        }

        $fields = [
            'duration_unit' => [
                'type'       => 'ENUM',
                'constraint' => ['days', 'weeks', 'months'],
                'null'       => true,
                'default'    => null,
            ],
        ];

        $this->forge->modifyColumn('packages', $fields);
    }
}

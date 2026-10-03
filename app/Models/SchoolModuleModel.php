<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolModuleModel extends Model
{
    protected $table      = 'school_modules';
    protected $primaryKey = 'school_id';

    protected $returnType = 'object';

    protected $allowedFields = [
        'school_id',
        'module_id',
        'status',
    ];

    /**
     * Get the ids of all enabled modules for a school.
     *
     * @return int[]
     */
    public function getEnabledModuleIds(int $schoolId): array
    {
        $rows = $this->where('school_id', $schoolId)
            ->where('status', 1)
            ->findAll();

        return array_map(static fn($row): int => (int) $row->module_id, $rows);
    }

    /**
     * Get the slugs of all enabled modules for a school (joins modules table).
     *
     * @return string[]
     */
    public function getEnabledSlugs(int $schoolId): array
    {
        $rows = $this->select('modules.slug')
            ->join('modules', 'modules.id = school_modules.module_id')
            ->where('school_modules.school_id', $schoolId)
            ->where('school_modules.status', 1)
            ->findAll();

        $slugs = array_map(static fn($row) => $row->slug, $rows);

        return array_values(array_unique(array_filter($slugs)));
    }

    /**
     * Replace the enabled modules for a school with the given module ids.
     */
    public function syncModules(int $schoolId, array $moduleIds): bool
    {
        $builder = db_connect()->table($this->table);

        $builder->where('school_id', $schoolId)->delete();

        $moduleIds = array_values(array_unique(array_filter(array_map('intval', $moduleIds))));

        if (empty($moduleIds)) {
            return true;
        }

        $data = [];
        foreach ($moduleIds as $moduleId) {
            $data[] = [
                'school_id' => $schoolId,
                'module_id' => (int) $moduleId,
                'status'    => 1,
            ];
        }

        return (bool) $builder->insertBatch($data);
    }
}

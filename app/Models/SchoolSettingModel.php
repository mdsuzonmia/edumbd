<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolSettingModel extends Model
{
    protected $table      = 'school_settings';
    protected $primaryKey = 'school_id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'school_id',
        'group',
        'key',
        'value',
        'status',
    ];

    /**
     * Get all enabled settings for a school within a group.
     */
    public function getByGroup(int $schoolId, string $group): array
    {
        return $this->where('school_id', $schoolId)
            ->where('group', $group)
            ->where('status', 1)
            ->orderBy('key', 'ASC')
            ->findAll();
    }

    /**
     * Get a single school setting value.
     */
    public function getValue(int $schoolId, string $group, string $key, $default = null)
    {
        $row = db_connect()->table($this->table)
            ->where('school_id', $schoolId)
            ->where('group', $group)
            ->where('key', $key)
            ->where('status', 1)
            ->get()
            ->getRowArray();

        return $row['value'] ?? $default;
    }

    /**
     * Insert or update a school setting (keyed by school_id + group + key).
     */
    public function saveSetting(int $schoolId, string $group, string $key, $value, int $status = 1): bool
    {
        $value = is_array($value) ? json_encode($value) : $value;

        $builder = db_connect()->table($this->table);

        $existing = $builder->where('school_id', $schoolId)
            ->where('group', $group)
            ->where('key', $key)
            ->get()
            ->getRowArray();

        if ($existing) {
            return (bool) $builder->where('school_id', $schoolId)
                ->where('group', $group)
                ->where('key', $key)
                ->update([
                    'value'  => $value,
                    'status' => $status,
                ]);
        }

        return (bool) $builder->insert([
            'school_id' => $schoolId,
            'group'     => $group,
            'key'       => $key,
            'value'     => $value,
            'status'    => $status,
        ]);
    }
}

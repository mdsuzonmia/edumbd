<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Get settings by group
     */
    public function getByGroup(string $group): array
    {
        return $this->where('group', $group)
                    ->where('status', 1)
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * Get single setting value
     */
    public function getValue(string $group, string $key, $default = null)
    {
        $row = $this->where([
                    'group' => $group,
                    'key'   => $key,
                    'status'=> 1
                ])->first();

        return $row['value'] ?? $default;
    }

    /**
     * Save or update setting
     */
    public function saveSetting(string $group, string $key, $value, string $type = 'string')
    {
        $data = [
            'group' => $group,
            'key'   => $key,
            'value' => $value,
            'type'  => $type,
            'status'=> 1,
        ];

        $existing = $this->where(['group'=>$group,'key'=>$key])->first();

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        return $this->insert($data);
    }
}

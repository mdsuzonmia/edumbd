<?php

namespace App\Models\CustomField;

use CodeIgniter\Model;

class CustomFieldGroupModel extends Model
{
    protected $table      = 'custom_field_groups';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'entity_id',
        'title',
        'description',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';

    /**
     * Get all groups across all schools (for super admin)
     */
    public function getAllGroups()
    {
        return $this
            ->orderBy('school_id')
            ->orderBy('sort_order')
            ->findAll();
    }
}

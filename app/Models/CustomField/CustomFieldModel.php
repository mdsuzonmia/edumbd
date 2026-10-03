<?php

namespace App\Models\CustomField;

use CodeIgniter\Model;

class CustomFieldModel extends Model
{
    protected $table      = 'custom_fields';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'school_id',
        'entity_id',
        'group_id',
        'field_type_id',
        'label',
        'field_key',
        'placeholder',
        'help_text',
        'default_value',
        'validation_rules',
        'is_required',
        'is_unique',
        'is_searchable',
        'is_system_field',
        'encrypt_value',
        'show_on_registration',
        'show_on_admission',
        'show_on_api',
        'allow_bulk_import',
        'allow_bulk_export',
        'show_on_profile',
        'show_on_list',
        'show_on_idcard',
        'show_on_resultcard',
        'sort_order',
        'status',
        'school_owner_uid',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';

    /**
     * Get fields list with school name (for school owner with multiple schools)
     */
    public function getFieldsList(int $schoolId, array $schoolIds = [])
    {
        $cfTable  = $this->db->prefixTable('custom_fields');
        $cfgTable = $this->db->prefixTable('custom_field_groups');
        $cftTable = $this->db->prefixTable('custom_field_types');

        $builder = $this->select("
                {$cfTable}.*,
                {$cfgTable}.title AS group_name,
                {$cftTable}.title AS field_type
            ")
            ->join(
                'custom_field_groups',
                "{$cfgTable}.id = {$cfTable}.group_id",
                'left'
            )
            ->join(
                'custom_field_types',
                "{$cftTable}.id = {$cfTable}.field_type_id",
                'left'
            );

        if ($schoolId && in_array($schoolId, $schoolIds)) {
            $builder->where("{$cfTable}.school_id", $schoolId);
        } elseif (!empty($schoolIds)) {
            $builder->whereIn("{$cfTable}.school_id", $schoolIds);
        }

        return $builder
            ->orderBy('school_id')
            ->orderBy('entity_id')
            ->orderBy('sort_order')
            ->findAll();
    }

    /**
     * Get all fields across all schools (for super admin)
     */
    public function getAllFields(bool $onlyActive = false, ?string $search = null, int $schoolId = 0)
    {
        $cfTable  = $this->db->prefixTable('custom_fields');
        $cfgTable = $this->db->prefixTable('custom_field_groups');
        $cftTable = $this->db->prefixTable('custom_field_types');

        $builder = $this->select("
                {$cfTable}.*,
                {$cfgTable}.title AS group_name,
                {$cftTable}.title AS field_type
            ")
            ->join(
                'custom_field_groups',
                "{$cfgTable}.id = {$cfTable}.group_id",
                'left'
            )
            ->join(
                'custom_field_types',
                "{$cftTable}.id = {$cfTable}.field_type_id",
                'left'
            );

        if ($onlyActive) {
            $builder->where("{$cfTable}.status", 1);
        }

        if ($schoolId) {
            $builder->where("{$cfTable}.school_id", $schoolId);
        }

        if (!empty($search)) {
            $builder->groupStart()
                ->like("{$cfTable}.label", $search)
                ->orLike("{$cfTable}.field_key", $search)
                ->groupEnd();
        }

        return $builder
            ->orderBy('school_id')
            ->orderBy('entity_id')
            ->orderBy('sort_order')
            ->findAll();
    }

    /**
     * Get all fields for an entity
     */
    public function getFields(
        int $schoolId,
        int $entityId,
        bool $onlyActive = true
    ) {
        $cfTable  = $this->db->prefixTable('custom_fields');
        $cfgTable = $this->db->prefixTable('custom_field_groups');
        $cftTable = $this->db->prefixTable('custom_field_types');

        $builder = $this->select("
                {$cfTable}.*,
                {$cfgTable}.title AS group_name,
                {$cftTable}.title AS field_type
            ")
            ->join(
                'custom_field_groups',
                "{$cfgTable}.id = {$cfTable}.group_id",
                'left'
            )
            ->join(
                'custom_field_types',
                "{$cftTable}.id = {$cfTable}.field_type_id",
                'left'
            )
            ->where("{$cfTable}.school_id", $schoolId)
            ->where("{$cfTable}.entity_id", $entityId);

        if ($onlyActive) {
            $builder->where("{$cfTable}.status", 1);
        }

        return $builder
            ->orderBy('group_id')
            ->orderBy('sort_order')
            ->findAll();
    }

    /**
     * Get one field
     */
    public function getField(int $fieldId)
    {
        return $this
            ->where('id', $fieldId)
            ->first();
    }

    /**
     * Check unique field key
     */
    public function fieldKeyExists(
        int $schoolId,
        string $fieldKey,
        ?string $excludeToken = null
    ): bool {

        $builder = $this
            ->where('school_id', $schoolId)
            ->where('field_key', $fieldKey);

        if ($excludeToken) {
            $builder->where('token !=', $excludeToken);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Registration fields
     */
    public function getRegistrationFields(
        int $schoolId,
        int $entityId
    ) {
        return $this
            ->where('school_id', $schoolId)
            ->where('entity_id', $entityId)
            ->where('show_on_registration', 1)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->findAll();
    }

    /**
     * Admission fields
     */
    public function getAdmissionFields(
        int $schoolId,
        int $entityId
    ) {
        return $this
            ->where('school_id', $schoolId)
            ->where('entity_id', $entityId)
            ->where('show_on_admission', 1)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->findAll();
    }

    /**
     * Importable fields
     */
    public function getImportFields(
        int $schoolId,
        int $entityId
    ) {
        return $this
            ->where('school_id', $schoolId)
            ->where('entity_id', $entityId)
            ->where('allow_bulk_import', 1)
            ->where('status', 1)
            ->findAll();
    }

    /**
     * Exportable fields
     */
    public function getExportFields(
        int $schoolId,
        int $entityId
    ) {
        return $this
            ->where('school_id', $schoolId)
            ->where('entity_id', $entityId)
            ->where('allow_bulk_export', 1)
            ->where('status', 1)
            ->findAll();
    }
}
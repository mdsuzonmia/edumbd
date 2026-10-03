<?php

namespace App\Models\CustomField;

use CodeIgniter\Model;

class CustomFieldValueModel extends Model
{
    protected $table      = 'custom_field_values';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'field_id',
        'entity_id',
        'record_id',
        'value',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';

    /**
     * Save a field value (insert or update if exists)
     */
    public function saveFieldValue(
        int $schoolId,
        int $fieldId,
        int $entityId,
        int $recordId,
        ?string $value = null
    ) {
        $existing = $this
            ->where('school_id', $schoolId)
            ->where('field_id', $fieldId)
            ->where('entity_id', $entityId)
            ->where('record_id', $recordId)
            ->first();

        if ($existing) {
            return $this
                ->where('id', $existing->id)
                ->set('value', $value)
                ->update();
        }

        return $this->insert([
            'school_id' => $schoolId,
            'field_id'  => $fieldId,
            'entity_id' => $entityId,
            'record_id' => $recordId,
            'value'     => $value,
        ]);
    }

    /**
     * Get all field values for a specific record
     */
    public function getRecordValues(
        int $schoolId,
        int $entityId,
        int $recordId
    ) {
        $cfvTable = $this->db->prefixTable('custom_field_values');
        $cfTable  = $this->db->prefixTable('custom_fields');

        return $this
            ->select("
                {$cfvTable}.*,
                {$cfTable}.field_key,
                {$cfTable}.label,
                {$cfTable}.field_type_id
            ")
            ->join(
                'custom_fields',
                "{$cfTable}.id = {$cfvTable}.field_id",
                'left'
            )
            ->where("{$cfvTable}.school_id", $schoolId)
            ->where("{$cfvTable}.entity_id", $entityId)
            ->where("{$cfvTable}.record_id", $recordId)
            ->findAll();
    }
}
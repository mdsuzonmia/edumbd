<?php

namespace App\Models\CustomField;

use CodeIgniter\Model;

class CustomFieldOptionModel extends Model
{
    protected $table      = 'custom_field_options';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'field_id',
        'option_label',
        'option_value',
        'sort_order',
        'status'
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';

     public function getOptions(int $fieldId)
    {
        return $this
            ->where('field_id', $fieldId)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->findAll();
    }
}
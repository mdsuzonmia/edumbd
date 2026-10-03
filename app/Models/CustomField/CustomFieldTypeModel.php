<?php

namespace App\Models\CustomField;

use CodeIgniter\Model;

class CustomFieldTypeModel extends Model
{
    protected $table      = 'custom_field_types';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'title',
        'slug',
        'status',
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';
}
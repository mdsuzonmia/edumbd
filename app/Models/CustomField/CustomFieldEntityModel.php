<?php

namespace App\Models\CustomField;

use CodeIgniter\Model;

class CustomFieldEntityModel extends Model
{
    protected $table      = 'custom_field_entities';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'title',
        'slug',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
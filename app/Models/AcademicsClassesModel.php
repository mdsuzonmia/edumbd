<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicsClassesModel extends Model
{
    protected $table      = 'academic_classes';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'title',
        'school_id',
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'school_owner_uid'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
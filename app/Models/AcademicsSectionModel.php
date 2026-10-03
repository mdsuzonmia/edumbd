<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicsSectionModel extends Model
{
    protected $table      = 'academic_sections';
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
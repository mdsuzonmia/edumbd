<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class GradeSystemModel extends Model
{
    protected $table      = 'examination_grade_systems';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'title',
        'description',
        'total_mark',
        'school_id',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'status',
        'school_owner_uid'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
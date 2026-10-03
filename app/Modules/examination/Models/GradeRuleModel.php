<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class GradeRuleModel extends Model
{
    protected $table      = 'examination_grade_rules';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'grade_system_id',
        'title',
        'grade_point',
        'mark_from',
        'mark_to',
        'remarks',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'status',
        'school_owner_uid',
        'field_order'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
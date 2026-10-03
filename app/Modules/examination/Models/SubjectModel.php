<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class SubjectModel extends Model
{
    protected $table      = 'examination_subjects';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'title',
        'short_title',
        'subject_code',
        'grade_system_id',
        'school_id',
        'school_owner_uid',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',

        'optional',
        'order_number',
        'enabled_exclude_mark',
        'exclude_mark',
        'exclude_percentage',
        'exclude_grade_point',
        'mark_calculation',

        'combine_group',
        'combine_order',
        'combine_method',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
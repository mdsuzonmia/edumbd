<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class MarkModel extends Model
{
    protected $table      = 'examination_marks';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'exam_id',
        'student_id',
        'roll_no',
        'session_id',
        'class_id',
        'enrollment_id',
        'section_id',
        'subject_id',
        'distribution_id',
        'full_mark',
        'obtained_mark',
        'is_absent',
        'is_locked',
        'locked_at',
        'locked_by',
        'remarks',
        'school_id',
        'school_owner_uid',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'status',
        'is_locked'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
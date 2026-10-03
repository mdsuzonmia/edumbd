<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentEnrollmentModel extends Model
{
    protected $table      = 'student_enrollments';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'school_owner_uid',
        'student_id',
        'session_id',
        'class_id',
        'section_id',
        'department_id',
        'group_id',
        'shift_id',
        'category_id',
        'roll_no',
        
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
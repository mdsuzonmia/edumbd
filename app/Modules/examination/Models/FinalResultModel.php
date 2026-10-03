<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class FinalResultModel extends Model
{
    protected $table      = 'examination_final_results';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $allowedFields = [
        'school_id',
        'token',
        'school_owner_uid',
        'student_uid',
        'enrollment_id',
        'session_id',
        'class_id',
        'section_id',
        'total_marks',
        'total_full_marks',
        'percentage',
        'gpa',
        'grade_letter',
        'grade_name',
        'class_position',
        'section_position',
        'working_days',
        'present_days',
        'absent_days',
        'late_days',
        'result_status',
        'promotion_status',
        'next_session_id',
        'next_class_id',
        'next_section_id',
        'next_roll',
        'principal_remark',
        'teacher_remark',
        'is_generated',
        'generated_at',
        'is_published',
        'published_at',
        'created_by',
        'updated_by',

        
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
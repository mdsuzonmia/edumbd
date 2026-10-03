<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ExamResultModel extends Model
{
    protected $table      = 'examination_results';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $allowedFields = [
        'token',

        'school_id',
        'school_owner_uid',

        'exam_id',

        'session_id',

        'class_id',
        'section_id',

        'student_id',
        'enrollment_id',

        'total_subjects',
        'passed_subjects',
        'failed_subjects',

        'total_marks',
        'obtained_marks',

        'percentage',

        'gpa',
        'total_grade_point',

        'grade',
        'letter_grade',

        'result_status',

        'class_rank',
        'section_rank',
        'exam_rank',

        'working_days',
        'present_days',
        'absent_days',
        'attendance_percentage',

        'principal_remarks',
        'teacher_remarks',

        'published_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
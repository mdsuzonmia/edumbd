<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ResultHistoryModel extends Model
{
    protected $table      = 'examination_result_history';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $allowedFields = [

        'school_id',
        'school_owner_uid',

        'exam_result_id',

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

        'grade',
        'letter_grade',

        'result_status',

        'class_rank',
        'section_rank',
        'exam_rank',

        'attendance_percentage',

        'principal_remarks',
        'teacher_remarks',

        'version_no',

        'change_reason',

        'action_type',

        'changed_by'
    ];

    protected $useTimestamps = false;
}
<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class SubjectResultModel extends Model
{
    protected $table            = 'examination_subject_results';
    protected $primaryKey       = 'id';

    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'school_id',
        'school_owner_uid',

        'exam_id',

        'session_id',
        'class_id',
        'section_id',

        'student_id',
        'enrollment_id',

        'subject_id',

        'full_mark',
        'obtained_mark',

        'exclude_mark',
        'exclude_grade_point',
        'include_mark',
        'include_grade_point',

        'percentage',

        'grade_point',
        'grade',
        'letter_grade',

        'highest_mark',

        'is_fail',

        'remarks',

        'combined_mark',
        'combined_gp',
        'combined_percentage',
        'average_mark',
        'average_gp',
        'average_grade',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
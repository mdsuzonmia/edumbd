<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class SubjectResultHistoryModel extends Model
{
    protected $table      = 'examination_subject_result_history';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $allowedFields = [

        'subject_result_id',

        'exam_id',

        'student_id',
        'enrollment_id',

        'subject_id',

        'full_mark',
        'obtained_mark',

        'percentage',

        'grade_point',
        'grade',
        'letter_grade',

        'highest_mark',

        'is_fail',

        'version_no',

        'action_type',

        'changed_by'
    ];

    protected $useTimestamps = false;
}
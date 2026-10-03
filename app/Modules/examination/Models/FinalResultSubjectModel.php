<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class FinalResultSubjectModel extends Model
{
    protected $table      = 'examination_final_result_subjects';
    protected $primaryKey = 'id';

    protected $returnType = 'object';

    protected $allowedFields = [
        'school_id',
        'school_owner_uid',
        'student_uid',
        'enrollment_id',
        'session_id',
        'class_id',
        'section_id',
        'subject_id',
        'aggregate_full_mark',
        'aggregate_obtained_mark',
        'aggregate_percentage',
        'grade_point',
        'letter_grade',
        'is_fail',
        'is_generated',
        'generated_at',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
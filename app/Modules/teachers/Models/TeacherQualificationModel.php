<?php

namespace App\Modules\teachers\Models;

use CodeIgniter\Model;

class TeacherQualificationModel extends Model
{
    protected $table      = 'edum_teacher_qualifications';
    protected $primaryKey = 'qualification_id';

    protected $allowedFields = [
        'school_owner_uid',
        'teacher_id',
        'degree_name',
        'group_name',
        'subject_name',
        'institution_name',
        'board_university',
        'passing_year',
        'result',
        'result_type',
        'attachment',
        'remarks',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $returnType = 'object';

    protected $validationRules = [
        'school_owner_uid' => 'required|max_length[36]',
        'teacher_id'       => 'required|is_natural_no_zero',
        'degree_name'      => 'required|max_length[150]',
        'result_type'      => 'permit_empty|in_list[GPA,CGPA,Division,Class,Grade,Percentage,Other]',
    ];
}
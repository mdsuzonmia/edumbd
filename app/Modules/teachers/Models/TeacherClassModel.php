<?php

namespace App\Modules\teachers\Models;

use CodeIgniter\Model;

class TeacherClassModel extends Model
{
    protected $table      = 'edum_teacher_classes';
    protected $primaryKey = 'teacher_class_id';

    protected $allowedFields = [
        'school_owner_uid',
        'teacher_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'is_class_teacher',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $returnType = 'object';

    protected $validationRules = [
        'school_owner_uid' => 'required|max_length[36]',
        'teacher_id'       => 'required|is_natural_no_zero',
        'academic_year_id' => 'required|is_natural_no_zero',
        'class_id'         => 'required|is_natural_no_zero',
        'is_class_teacher' => 'permit_empty|in_list[0,1]',
    ];
}
<?php

namespace App\Modules\teachers\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table      = 'edum_teachers';
    protected $primaryKey = 'teacher_id';

    protected $allowedFields = [
        'school_owner_uid',
        'teacher_uid',
        'employee_code',
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'phone',
        'email',
        'designation_id',
        'department_id',
        'joining_date',
        'employment_type',
        'salary',
        'photo',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $returnType = 'object';

    protected $validationRules = [
        'school_owner_uid' => 'required|max_length[36]',
        'teacher_uid'      => 'required|max_length[36]',
        'employee_code'    => 'required|max_length[30]',
        'first_name'       => 'required|max_length[100]',
        'gender'           => 'required|in_list[Male,Female,Other]',
        'phone'            => 'required|max_length[30]',
        'status'           => 'permit_empty|in_list[Active,Inactive,On Leave,Resigned]',
        'employment_type'  => 'permit_empty|in_list[Permanent,Contract,Part Time,Guest]',
    ];
}
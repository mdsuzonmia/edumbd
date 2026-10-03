<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentGuardianModel extends Model
{
    protected $table      = 'student_guardians';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'student_id',
        'relation_type',
        'name',
        'phone',
        'email',
        'occupation',
        'photo',
        'address',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';
}
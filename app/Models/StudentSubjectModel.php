<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentSubjectModel extends Model
{
    protected $table      = 'student_subjects';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'enrollment_id',
        'subject_id',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';
}
<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentPromotionModel extends Model
{
    protected $table      = 'student_promotions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'school_owner_uid',
        'from_session_id',
        'to_session_id',
        'from_class_id',
        'to_class_id',
        'total_students',
        'success_count',
        'failed_count',
        'status',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
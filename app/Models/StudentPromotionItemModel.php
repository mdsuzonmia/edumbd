<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentPromotionItemModel extends Model
{
    protected $table      = 'student_promotion_items';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'promotion_id',
        'student_id',
        'from_enrollment_id',
        'to_enrollment_id',
        'roll_no',
        'to_roll_no',
        'status',
        'error_message',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';
}
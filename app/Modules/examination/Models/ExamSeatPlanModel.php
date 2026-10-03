<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ExamSeatPlanModel extends Model
{
    protected $table      = 'examination_seat_plans';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'token', 'school_id', 'school_owner_uid', 'exam_id', 'session_id', 'shift_id', 'department_id',
        'class_ids_json', 'section_ids_json',
        'title', 'exam_date', 'allocation_method', 'seat_number_format', 'random_seed', 'status',
        'is_locked', 'locked_at', 'locked_by', 'created_by', 'updated_by',
        'created_at', 'updated_at',
    ];
}

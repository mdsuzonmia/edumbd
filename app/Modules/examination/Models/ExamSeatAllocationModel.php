<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ExamSeatAllocationModel extends Model
{
    protected $table      = 'examination_seat_allocations';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'school_id', 'seat_plan_id', 'exam_id', 'room_id', 'student_id', 'enrollment_id',
        'session_id', 'class_id', 'section_id', 'roll_no_snapshot', 'seat_no', 'row_no',
        'column_no', 'created_at', 'updated_at',
    ];
}

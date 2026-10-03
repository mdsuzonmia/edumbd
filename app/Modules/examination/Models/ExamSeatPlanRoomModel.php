<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ExamSeatPlanRoomModel extends Model
{
    protected $table      = 'examination_seat_plan_rooms';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'school_id', 'seat_plan_id', 'room_id', 'room_name_snapshot', 'room_no_snapshot',
        'capacity', 'rows_count', 'columns_count', 'sort_order', 'created_at', 'updated_at',
    ];
}

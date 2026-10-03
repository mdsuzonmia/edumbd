<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class ExamRoomModel extends Model
{
    protected $table      = 'examination_rooms';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'token',
        'school_id',
        'school_owner_uid',
        'room_name',
        'room_no',
        'building',
        'floor',
        'capacity',
        'rows_count',
        'columns_count',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
}

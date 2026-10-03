<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class AdmitCardSettingModel extends Model
{
    protected $table = 'examination_admit_card_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'token', 'school_id', 'exam_id', 'session_id', 'title', 'instructions',
        'show_student_photo', 'show_student_id', 'show_registration_no', 'show_exam_time',
        'show_room', 'show_seat', 'show_qr_code', 'require_seat_plan',
        'created_by', 'updated_by', 'created_at', 'updated_at',
    ];
}

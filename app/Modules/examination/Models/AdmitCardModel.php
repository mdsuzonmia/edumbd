<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class AdmitCardModel extends Model
{
    protected $table = 'examination_admit_cards';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'token', 'setting_id', 'school_id', 'exam_id', 'session_id', 'student_id',
        'enrollment_id', 'status', 'generated_at', 'generated_by', 'created_at', 'updated_at',
    ];
}

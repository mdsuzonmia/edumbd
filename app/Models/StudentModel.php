<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table      = 'students';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'school_owner_uid',
        'student_code',
        'registration_no',
        'first_name',
        'middle_name',
        'last_name',
        'alias',
        'token',
        'gender',
        'date_of_birth',
        'blood_group',
        'religion',
        'nationality',
        'phone',
        'email',
        'photo',
        'user_id',
        'admission_date',
        'student_status',
        'admission_source',
        'student_qr_code',
        'rfid_number',
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
}
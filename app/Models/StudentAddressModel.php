<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentAddressModel extends Model
{
    protected $table      = 'student_addresses';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'student_id',
        'present_address',
        'permanent_address',
        'city',
        'district',
        'state',
        'postal_code',
        'country',
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';
}
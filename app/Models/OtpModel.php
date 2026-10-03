<?php

namespace App\Models;

use CodeIgniter\Model;

class OtpModel extends Model
{
    protected $table      = 'otp_codes';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'school_id', 'user_id', 'phone', 'country_code', 'otp', 'type', 'is_verified', 'attempt_count', 'expires_at', 'verified_at', 'ip_address', 'created_at'
    ];
   
    // Set the return type as an object
    protected $returnType = 'object';

    
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class UserEmailVerificationModel extends Model
{
    protected $table      = 'email_verifications';
    protected $primaryKey = 'id';
    protected $allowedFields = [
       'user_id', 'email', 'token', 'otp', 'type', 'is_verified', 'expires_at', 'verified_at', 'ip_address', 'user_agent', 'created_at', 'updated_at'
    ];
   
    // Set the return type as an object
    protected $returnType = 'object';

    
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class PrtModel extends Model
{
    protected $table      = 'password_reset_tokens';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'token', 'expires_at', 'used_at', 'created_at'
    ];
   
    // Set the return type as an object
    protected $returnType = 'object';

    
}

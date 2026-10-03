<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'token',
        'name', 
        'email', 
        'password', 
        'phone', 
        'plan_id', 
        'photo',  
        'last_login_at', 
        'status',  
        'created_at', 
        'updated_at', 
        'created_by', 
        'updated_by'
    ];
    protected $useTimestamps = true;

    // Set the return type as an object
    protected $returnType = 'object';

    
}

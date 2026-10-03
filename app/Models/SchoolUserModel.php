<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolUserModel extends Model
{
    protected $table      = 'school_user_relation ';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'school_id', 'user_id'
    ];
    
    // Set the return type as an object
    protected $returnType = 'object';

    
}

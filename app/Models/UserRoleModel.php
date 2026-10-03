<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table      = 'user_roles';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'role_id', 
        'user_id', 
        'school_id',
        'school_owner_uid',
        ];

    protected $returnType = 'object';
}

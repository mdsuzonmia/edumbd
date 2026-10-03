<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table = 'roles';
    protected $allowedFields = ['id', 'school_id', 'name','description', 'slug', 'is_system', 'status'];
    protected $returnType = 'object';
}

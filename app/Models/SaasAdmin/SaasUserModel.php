<?php

namespace App\Models\SaasAdmin;

use CodeIgniter\Model;

class SaasUserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'email', 'role', 'status', 'created_at', 'updated_at'];

    // Add any custom methods or queries here
}
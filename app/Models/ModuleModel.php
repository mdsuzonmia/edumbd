<?php

namespace App\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $table      = 'modules';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name', 'slug', 'description', 'author', 'version', 'status', 'sidebar_menu', 'menu_access', 'menu_icon', 'super_admin_dashboard', 'admin_dashboard','teacher_dashboard','student_dashboard','parent_dashboard', 'params', 'created_by', 'updated_by'
    ];
    
    // Set the return type as an object
    protected $returnType = 'object';
}

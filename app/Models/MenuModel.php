<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table      = 'menus';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'title', 'link', 'menu_icon', 'parent', 'child', 'access', 'menu_order'
    ];

    public function getMenuValue($field, $id)
    {
        $setting = $this->where('id', $id)->first();
        return $setting ? $setting[$field] : null;
    }
  
}

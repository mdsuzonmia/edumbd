<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuRoleModel extends Model
{
    protected $table      = 'menu_roles';
    protected $primaryKey = 'id';

    protected $allowedFields = ['menu_id', 'role_id'];

    protected $returnType = 'object';

    public function getRolesByMenuId(int $menuId): array
    {
        return $this->where('menu_id', $menuId)->findAll();
    }

    public function getMenusByRoleId(int $roleId): array
    {
        return $this->where('role_id', $roleId)->findAll();
    }

    public function syncRoles(int $menuId, array $roleIds): void
    {
        $this->where('menu_id', $menuId)->delete();

        if (!empty($roleIds)) {
            $insertData = [];
            foreach ($roleIds as $roleId) {
                $insertData[] = [
                    'menu_id' => $menuId,
                    'role_id' => (int) $roleId,
                ];
            }
            $this->insertBatch($insertData);
        }
    }
}
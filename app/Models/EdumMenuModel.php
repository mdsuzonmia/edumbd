<?php

namespace App\Models;

use CodeIgniter\Model;

class EdumMenuModel extends Model
{
    protected $table      = 'menus';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'module_id', 'parent_id', 'title', 'slug', 'route', 'icon', 'menu_order', 'status',
    ];

    protected $returnType = 'object';

    public function getMenuTree(): array
    {
        $menus = $this->orderBy('menu_order', 'ASC')
                       ->orderBy('id', 'ASC')
                       ->findAll();

        return $this->buildTree($menus);
    }

    public function buildTree(array $elements, ?int $parentId = null): array
    {
        $branch = [];
        foreach ($elements as $element) {
            $elementParentId = $element->parent_id !== null ? (int) $element->parent_id : null;
            if ($elementParentId === $parentId) {
                $children = $this->buildTree($elements, (int) $element->id);
                if ($children) {
                    $element->children = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    public function getParentMenus(): array
    {
        // where null or 0 
        $where_value = null;
        return $this->where('parent_id', $where_value)
                     ->orderBy('menu_order', 'ASC')
                     ->findAll();
    }
}
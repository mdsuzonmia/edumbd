<?php

namespace Modules\examination\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExaminationSeatPlanMenu extends Migration
{
    public function up()
    {
        $menus = $this->db->table('menus');
        $parent = $menus->where('slug', 'school-exams')->get()->getRow();
        if (!$parent || $menus->where('slug', 'exam-seat-plans')->countAllResults()) {
            return;
        }

        $menus->where('parent_id', $parent->id)->where('menu_order >=', 12)->increment('menu_order', 1);
        $menus->insert([
            'module_id' => $parent->module_id,
            'parent_id'  => $parent->id,
            'title'      => 'Seat Plan',
            'slug'       => 'exam-seat-plans',
            'route'      => 'examination/seat-plans',
            'icon'       => 'bi bi-grid-3x3-gap',
            'menu_order' => 12,
            'status'     => 1,
        ]);
        $menuId = $this->db->insertID();

        $ownerRole = $this->db->table('roles')->where('slug', 'school-owner')->get()->getRow();
        if ($ownerRole) {
            $this->db->table('menu_roles')->insert(['menu_id' => $menuId, 'role_id' => $ownerRole->id]);
        }
    }

    public function down()
    {
        $menus = $this->db->table('menus');
        $menu = $menus->where('slug', 'exam-seat-plans')->get()->getRow();
        if (!$menu) {
            return;
        }

        $this->db->table('menu_roles')->where('menu_id', $menu->id)->delete();
        $menus->where('id', $menu->id)->delete();
        $menus->where('parent_id', $menu->parent_id)->where('menu_order >', 12)->decrement('menu_order', 1);
    }
}

<?php

namespace Modules\admission\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdmissionReportsMenu extends Migration
{
    public function up()
    {
        $menus=$this->db->table('menus');
        if ($menus->where('slug','admission-reports')->countAllResults()) return;
        $parent=$menus->where('slug','school-admission')->get()->getRow();
        if (!$parent) return;
        $menus->insert(['module_id'=>$parent->module_id,'parent_id'=>$parent->id,'title'=>'Admission Reports','slug'=>'admission-reports','route'=>'school/admission/applications','icon'=>'bi bi-file-earmark-bar-graph','menu_order'=>6,'status'=>1]);
        $menuId=$this->db->insertID(); $role=$this->db->table('roles')->where('slug','school-owner')->get()->getRow();
        if ($role) $this->db->table('menu_roles')->insert(['menu_id'=>$menuId,'role_id'=>$role->id]);
    }

    public function down()
    {
        $menu=$this->db->table('menus')->where('slug','admission-reports')->get()->getRow();
        if (!$menu) return;
        $this->db->table('menu_roles')->where('menu_id',$menu->id)->delete();
        $this->db->table('menus')->where('id',$menu->id)->delete();
    }
}

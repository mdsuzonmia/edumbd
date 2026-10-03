<?php

namespace Modules\admission\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdmissionPaymentSettings extends Migration
{
    private const MENU_SLUG = 'admission-payment-settings';
    private const PERMISSION_SLUG = 'admission_payment_settings';

    public function up()
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'school_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'currency'=>['type'=>'VARCHAR','constraint'=>10,'default'=>'BDT'],
            'manual_enabled'=>['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'manual_instructions'=>['type'=>'TEXT','null'=>true],
            'stripe_enabled'=>['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'stripe_publishable_key'=>['type'=>'VARCHAR','constraint'=>255,'null'=>true],
            'stripe_secret_key'=>['type'=>'TEXT','null'=>true],
            'paypal_enabled'=>['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'paypal_client_id'=>['type'=>'TEXT','null'=>true],
            'paypal_client_secret'=>['type'=>'TEXT','null'=>true],
            'paypal_sandbox'=>['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'created_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],
            'updated_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],
            'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id',true);
        $this->forge->addUniqueKey('school_id','uq_admission_payment_settings_school');
        $this->forge->createTable('admission_payment_settings',true);

        $permissions=$this->db->table('permissions');
        if(!$permissions->where('slug',self::PERMISSION_SLUG)->countAllResults())$permissions->insert(['name'=>'Configure Admission Payment Methods','slug'=>self::PERMISSION_SLUG,'module'=>'admission','status'=>1]);
        $role=$this->db->table('roles')->where('slug','school-owner')->get()->getRow();
        $permission=$permissions->where('slug',self::PERMISSION_SLUG)->get()->getRow();
        if($role&&$permission&&!$this->db->table('role_permissions')->where('role_id',$role->id)->where('permission_id',$permission->id)->countAllResults())$this->db->table('role_permissions')->insert(['role_id'=>$role->id,'permission_id'=>$permission->id]);

        $menus=$this->db->table('menus');$parent=$menus->where('slug','school-admission')->get()->getRow();
        if($parent&&!$menus->where('slug',self::MENU_SLUG)->countAllResults()){
            $menus->insert(['module_id'=>$parent->module_id,'parent_id'=>$parent->id,'title'=>'Payment Settings','slug'=>self::MENU_SLUG,'route'=>'school/admission/payment-settings','icon'=>'bi bi-gear','menu_order'=>15,'status'=>1]);
            if($role)$this->db->table('menu_roles')->insert(['menu_id'=>$this->db->insertID(),'role_id'=>$role->id]);
        }
    }

    public function down()
    {
        $menu=$this->db->table('menus')->where('slug',self::MENU_SLUG)->get()->getRow();if($menu){$this->db->table('menu_roles')->where('menu_id',$menu->id)->delete();$this->db->table('menus')->where('id',$menu->id)->delete();}
        $permission=$this->db->table('permissions')->where('slug',self::PERMISSION_SLUG)->get()->getRow();if($permission){$this->db->table('role_permissions')->where('permission_id',$permission->id)->delete();$this->db->table('permissions')->where('id',$permission->id)->delete();}
        $this->forge->dropTable('admission_payment_settings',true);
    }
}

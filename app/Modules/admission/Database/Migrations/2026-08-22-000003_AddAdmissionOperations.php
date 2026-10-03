<?php

namespace Modules\admission\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdmissionOperations extends Migration
{
    private const PERMISSIONS=[
        'admission_form_builder'=>'Manage Admission Forms','admission_payments'=>'Manage Admission Payments',
        'admission_documents'=>'Verify Admission Documents','admission_tests'=>'Manage Admission Tests',
        'admission_seat_plans'=>'Manage Admission Seat Plans','admission_admit_cards'=>'Manage Admission Admit Cards',
    ];

    public function up()
    {
        $this->forge->addColumn('admission_payments',[
            'token'=>['type'=>'VARCHAR','constraint'=>64,'null'=>true,'after'=>'id'],
            'payer_reference'=>['type'=>'VARCHAR','constraint'=>150,'null'=>true,'after'=>'transaction_id'],
            'proof_file'=>['type'=>'VARCHAR','constraint'=>255,'null'=>true,'after'=>'payer_reference'],
            'reviewed_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true,'after'=>'paid_at'],
            'reviewed_at'=>['type'=>'DATETIME','null'=>true,'after'=>'reviewed_by'],
            'rejection_reason'=>['type'=>'TEXT','null'=>true,'after'=>'reviewed_at'],
        ]);
        $this->forge->addUniqueKey('token','uq_admission_payment_token');
        $this->forge->processIndexes('admission_payments');
        foreach($this->db->table('admission_payments')->select('id')->where('token',null)->get()->getResult() as $payment) {
            $this->db->table('admission_payments')->where('id',$payment->id)->update(['token'=>bin2hex(random_bytes(24))]);
        }
        $this->forge->addColumn('admission_documents',[
            'verification_note'=>['type'=>'TEXT','null'=>true,'after'=>'verification_status'],
        ]);
        $this->forge->addColumn('admission_applications',[
            'application_fee_status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'not_required','after'=>'payment_status'],
            'admission_fee_status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'not_required','after'=>'application_fee_status'],
        ]);
        $prefix=$this->db->getPrefix();
        $this->db->query("UPDATE {$prefix}admission_applications a JOIN {$prefix}admission_circulars c ON c.id=a.circular_id SET a.application_fee_status=CASE WHEN c.application_fee<=0 THEN 'not_required' ELSE a.payment_status END, a.admission_fee_status=CASE WHEN c.admission_fee<=0 THEN 'not_required' ELSE 'unpaid' END");
        $this->createTests(); $this->createRooms(); $this->createPlans(); $this->createPlanRooms(); $this->createAllocations(); $this->createCards();
        $this->permissions(); $this->menus();
    }

    public function down()
    {
        $this->removeMenus(); $this->removePermissions();
        foreach(['admission_test_admit_cards','admission_test_seat_allocations','admission_test_seat_plan_rooms','admission_test_seat_plans','admission_test_rooms','admission_tests'] as $table) $this->forge->dropTable($table,true);
        foreach(['application_fee_status','admission_fee_status'] as $column) if($this->db->fieldExists($column,'admission_applications')) $this->forge->dropColumn('admission_applications',$column);
        if($this->db->fieldExists('verification_note','admission_documents')) $this->forge->dropColumn('admission_documents','verification_note');
        foreach(['token','payer_reference','proof_file','reviewed_by','reviewed_at','rejection_reason'] as $column) if($this->db->fieldExists($column,'admission_payments')) $this->forge->dropColumn('admission_payments',$column);
    }

    private function createTests(): void
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'token'=>['type'=>'VARCHAR','constraint'=>64],
            'school_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'circular_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'title'=>['type'=>'VARCHAR','constraint'=>180],'test_date'=>['type'=>'DATE'],'start_time'=>['type'=>'TIME'],'end_time'=>['type'=>'TIME','null'=>true],
            'reporting_time'=>['type'=>'TIME','null'=>true],'total_marks'=>['type'=>'DECIMAL','constraint'=>'8,2','default'=>0],
            'pass_marks'=>['type'=>'DECIMAL','constraint'=>'8,2','default'=>0],'instructions'=>['type'=>'TEXT','null'=>true],
            'candidate_statuses'=>['type'=>'VARCHAR','constraint'=>255,'default'=>'eligible'],'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'draft'],
            'created_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],'updated_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addUniqueKey('token'); $this->forge->addKey(['school_id','status']); $this->forge->createTable('admission_tests',true);
    }

    private function createRooms(): void
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'token'=>['type'=>'VARCHAR','constraint'=>64],
            'school_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'room_name'=>['type'=>'VARCHAR','constraint'=>150],
            'room_no'=>['type'=>'VARCHAR','constraint'=>50],'building'=>['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'floor'=>['type'=>'VARCHAR','constraint'=>50,'null'=>true],'capacity'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'rows_count'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],'columns_count'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'status'=>['type'=>'TINYINT','constraint'=>1,'default'=>1],'created_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],
            'updated_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addUniqueKey('token'); $this->forge->addUniqueKey(['school_id','room_no']); $this->forge->createTable('admission_test_rooms',true);
    }

    private function createPlans(): void
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'token'=>['type'=>'VARCHAR','constraint'=>64],
            'school_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'test_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'title'=>['type'=>'VARCHAR','constraint'=>180],'allocation_method'=>['type'=>'VARCHAR','constraint'=>30,'default'=>'sequential'],
            'seat_number_format'=>['type'=>'VARCHAR','constraint'=>30,'default'=>'numeric'],'random_seed'=>['type'=>'VARCHAR','constraint'=>64,'null'=>true],
            'candidate_count'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'default'=>0],'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'draft'],
            'is_locked'=>['type'=>'TINYINT','constraint'=>1,'default'=>0],'locked_at'=>['type'=>'DATETIME','null'=>true],
            'created_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],'updated_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addUniqueKey('token'); $this->forge->addKey(['school_id','test_id']); $this->forge->createTable('admission_test_seat_plans',true);
    }

    private function createPlanRooms(): void
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'school_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'seat_plan_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'room_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'room_name_snapshot'=>['type'=>'VARCHAR','constraint'=>150],'room_no_snapshot'=>['type'=>'VARCHAR','constraint'=>50],
            'capacity'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],'rows_count'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'columns_count'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],'sort_order'=>['type'=>'INT','constraint'=>11,'default'=>0],
            'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addUniqueKey(['seat_plan_id','room_id']); $this->forge->createTable('admission_test_seat_plan_rooms',true);
    }

    private function createAllocations(): void
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'school_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'seat_plan_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'test_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'room_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'application_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'application_no_snapshot'=>['type'=>'VARCHAR','constraint'=>40],'seat_no'=>['type'=>'VARCHAR','constraint'=>30],
            'row_no'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],'column_no'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addUniqueKey(['seat_plan_id','application_id']); $this->forge->addUniqueKey(['seat_plan_id','room_id','row_no','column_no']); $this->forge->createTable('admission_test_seat_allocations',true);
    }

    private function createCards(): void
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],'token'=>['type'=>'VARCHAR','constraint'=>64],
            'school_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'test_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'application_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],'seat_allocation_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],
            'card_no'=>['type'=>'VARCHAR','constraint'=>50],'status'=>['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'generated_at'=>['type'=>'DATETIME'],'generated_by'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addUniqueKey('token'); $this->forge->addUniqueKey(['test_id','application_id']); $this->forge->addUniqueKey(['school_id','card_no']); $this->forge->createTable('admission_test_admit_cards',true);
    }

    private function permissions(): void
    {
        $table=$this->db->table('permissions'); foreach(self::PERMISSIONS as $slug=>$name) if(!$table->where('slug',$slug)->countAllResults()) $table->insert(['name'=>$name,'slug'=>$slug,'module'=>'admission','status'=>1]);
        $role=$this->db->table('roles')->where('slug','school-owner')->get()->getRow(); if(!$role)return;
        foreach($table->whereIn('slug',array_keys(self::PERMISSIONS))->get()->getResult() as $permission){$pivot=$this->db->table('role_permissions');if(!$pivot->where('role_id',$role->id)->where('permission_id',$permission->id)->countAllResults())$pivot->insert(['role_id'=>$role->id,'permission_id'=>$permission->id]);}
    }

    private function menus(): void
    {
        $menus=$this->db->table('menus');$parent=$menus->where('slug','school-admission')->get()->getRow();if(!$parent)return;
        $items=[['Form Builder','admission-form-builder','school/admission/form-builder','bi bi-ui-checks-grid'],['Payments','admission-payments','school/admission/payments','bi bi-credit-card'],['Document Verification','admission-documents','school/admission/documents','bi bi-file-earmark-check'],['Admission Tests','admission-tests','school/admission/tests','bi bi-pencil-square'],['Test Rooms','admission-test-rooms','school/admission/test-rooms','bi bi-door-open'],['Seat Plans','admission-seat-plans','school/admission/seat-plans','bi bi-grid-3x3-gap'],['Admit Cards','admission-admit-cards','school/admission/admit-cards','bi bi-person-badge']];
        $role=$this->db->table('roles')->where('slug','school-owner')->get()->getRow();foreach($items as $i=>[$title,$slug,$route,$icon]){if($menus->where('slug',$slug)->countAllResults())continue;$menus->insert(['module_id'=>$parent->module_id,'parent_id'=>$parent->id,'title'=>$title,'slug'=>$slug,'route'=>$route,'icon'=>$icon,'menu_order'=>7+$i,'status'=>1]);if($role)$this->db->table('menu_roles')->insert(['menu_id'=>$this->db->insertID(),'role_id'=>$role->id]);}
    }

    private function removePermissions(): void {$rows=$this->db->table('permissions')->select('id')->whereIn('slug',array_keys(self::PERMISSIONS))->get()->getResultArray();$ids=array_column($rows,'id');if($ids){$this->db->table('role_permissions')->whereIn('permission_id',$ids)->delete();$this->db->table('permissions')->whereIn('id',$ids)->delete();}}
    private function removeMenus(): void {$slugs=['admission-form-builder','admission-payments','admission-documents','admission-tests','admission-test-rooms','admission-seat-plans','admission-admit-cards'];$rows=$this->db->table('menus')->select('id')->whereIn('slug',$slugs)->get()->getResultArray();$ids=array_column($rows,'id');if($ids){$this->db->table('menu_roles')->whereIn('menu_id',$ids)->delete();$this->db->table('menus')->whereIn('id',$ids)->delete();}}
}

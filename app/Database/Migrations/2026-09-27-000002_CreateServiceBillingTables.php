<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServiceBillingTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true], 'service'=>['type'=>'VARCHAR','constraint'=>30], 'service_mode'=>['type'=>'VARCHAR','constraint'=>30,'null'=>true],
            'pricing_type'=>['type'=>'VARCHAR','constraint'=>30], 'min_students'=>['type'=>'INT','null'=>true], 'max_students'=>['type'=>'INT','null'=>true],
            'amount'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0], 'sort_order'=>['type'=>'INT','default'=>0], 'status'=>['type'=>'TINYINT','default'=>1],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addKey(['service','status']); $this->forge->createTable('service_pricing_rules',true);

        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true], 'setting_key'=>['type'=>'VARCHAR','constraint'=>80], 'setting_value'=>['type'=>'TEXT','null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addUniqueKey('setting_key'); $this->forge->createTable('service_billing_settings',true);

        $this->forge->addField([
            'id'=>['type'=>'BIGINT','unsigned'=>true,'auto_increment'=>true], 'token'=>['type'=>'VARCHAR','constraint'=>64], 'invoice_no'=>['type'=>'VARCHAR','constraint'=>40],
            'school_id'=>['type'=>'BIGINT','unsigned'=>true], 'user_id'=>['type'=>'BIGINT','unsigned'=>true], 'service'=>['type'=>'VARCHAR','constraint'=>30], 'service_mode'=>['type'=>'VARCHAR','constraint'=>30,'null'=>true],
            'reference_type'=>['type'=>'VARCHAR','constraint'=>60,'null'=>true], 'reference_id'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true], 'exam_id'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true],
            'student_count'=>['type'=>'INT','default'=>0], 'pricing_type'=>['type'=>'VARCHAR','constraint'=>30], 'applied_rate'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0],
            'subtotal'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0], 'discount'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0], 'total'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0],
            'currency'=>['type'=>'VARCHAR','constraint'=>3,'default'=>'BDT'], 'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'draft'], 'pricing_snapshot'=>['type'=>'LONGTEXT'],
            'payment_method'=>['type'=>'VARCHAR','constraint'=>30,'null'=>true], 'transaction_id'=>['type'=>'VARCHAR','constraint'=>120,'null'=>true], 'payment_note'=>['type'=>'TEXT','null'=>true],
            'paid_at'=>['type'=>'DATETIME','null'=>true], 'completed_at'=>['type'=>'DATETIME','null'=>true], 'approved_by'=>['type'=>'BIGINT','unsigned'=>true,'null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]); $this->forge->addKey('id',true); $this->forge->addUniqueKey('token'); $this->forge->addUniqueKey('invoice_no'); $this->forge->addKey(['school_id','status']); $this->forge->createTable('service_orders',true);

        $now=date('Y-m-d H:i:s'); $prefix=$this->db->getPrefix();
        $this->db->table($prefix.'service_pricing_rules')->insertBatch([
            ['service'=>'RESULT','service_mode'=>'SELF_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>1,'max_students'=>299,'amount'=>15,'sort_order'=>1,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'RESULT','service_mode'=>'SELF_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>300,'max_students'=>499,'amount'=>12,'sort_order'=>2,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'RESULT','service_mode'=>'SELF_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>500,'max_students'=>null,'amount'=>10,'sort_order'=>3,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'RESULT','service_mode'=>'MANAGED_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>1,'max_students'=>299,'amount'=>25,'sort_order'=>1,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'RESULT','service_mode'=>'MANAGED_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>300,'max_students'=>499,'amount'=>20,'sort_order'=>2,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'RESULT','service_mode'=>'MANAGED_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>500,'max_students'=>null,'amount'=>18,'sort_order'=>3,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'SEAT_PLAN','pricing_type'=>'FIXED_TIER','min_students'=>100,'max_students'=>299,'amount'=>1500,'sort_order'=>1,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'SEAT_PLAN','pricing_type'=>'FIXED_TIER','min_students'=>300,'max_students'=>499,'amount'=>2000,'sort_order'=>2,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'SEAT_PLAN','pricing_type'=>'FIXED_TIER','min_students'=>500,'max_students'=>799,'amount'=>2500,'sort_order'=>3,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'SEAT_PLAN','pricing_type'=>'FIXED_TIER','min_students'=>800,'max_students'=>1199,'amount'=>3500,'sort_order'=>4,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['service'=>'ADMIT_CARD','pricing_type'=>'PER_STUDENT','min_students'=>null,'max_students'=>null,'amount'=>5,'sort_order'=>1,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
        ]);
        $this->db->table($prefix.'service_billing_settings')->insertBatch([
            ['setting_key'=>'result_minimum_charge','setting_value'=>'1500','created_at'=>$now,'updated_at'=>$now],
            ['setting_key'=>'result_minimum_enabled','setting_value'=>'1','created_at'=>$now,'updated_at'=>$now],
            ['setting_key'=>'demo_student_limit','setting_value'=>'20','created_at'=>$now,'updated_at'=>$now],
            ['setting_key'=>'manual_payment_enabled','setting_value'=>'1','created_at'=>$now,'updated_at'=>$now],
        ]);
    }
    public function down(){ $this->forge->dropTable('service_orders',true);$this->forge->dropTable('service_billing_settings',true);$this->forge->dropTable('service_pricing_rules',true); }
}

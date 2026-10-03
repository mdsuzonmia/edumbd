<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServiceModes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('service_pricing_rules') && !$this->db->fieldExists('service_mode', 'service_pricing_rules')) {
            $this->forge->addColumn('service_pricing_rules', ['service_mode' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'service']]);
        }
        if ($this->db->tableExists('service_orders') && !$this->db->fieldExists('service_mode', 'service_orders')) {
            $this->forge->addColumn('service_orders', ['service_mode' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'service']]);
        }
        if ($this->db->tableExists('result_wizard_progress') && !$this->db->fieldExists('service_mode', 'result_wizard_progress')) {
            $this->forge->addColumn('result_wizard_progress', ['service_mode' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'SELF_SERVICE', 'after' => 'student_preview']]);
        }

        if ($this->db->tableExists('service_pricing_rules')) {
            $this->db->table('service_pricing_rules')->where('service', 'RESULT')->where('service_mode', null)->update(['service_mode' => 'SELF_SERVICE']);
            $exists = $this->db->table('service_pricing_rules')->where(['service' => 'RESULT', 'service_mode' => 'MANAGED_SERVICE'])->countAllResults();
            if (!$exists) {
                $now = date('Y-m-d H:i:s');
                $this->db->table('service_pricing_rules')->insertBatch([
                    ['service'=>'RESULT','service_mode'=>'MANAGED_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>1,'max_students'=>299,'amount'=>25,'sort_order'=>1,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
                    ['service'=>'RESULT','service_mode'=>'MANAGED_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>300,'max_students'=>499,'amount'=>20,'sort_order'=>2,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
                    ['service'=>'RESULT','service_mode'=>'MANAGED_SERVICE','pricing_type'=>'PER_STUDENT_TIER','min_students'=>500,'max_students'=>null,'amount'=>18,'sort_order'=>3,'status'=>1,'created_at'=>$now,'updated_at'=>$now],
                ]);
            }
        }
    }

    public function down()
    {
        // Historical orders are intentionally preserved. No destructive rollback.
    }
}

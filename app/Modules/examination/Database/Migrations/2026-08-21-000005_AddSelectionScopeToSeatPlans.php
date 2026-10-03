<?php

namespace Modules\examination\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSelectionScopeToSeatPlans extends Migration
{
    public function up()
    {
        $fields = [];
        if (!$this->db->fieldExists('department_id', 'examination_seat_plans')) {
            $fields['department_id'] = ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'shift_id'];
        }
        if (!$this->db->fieldExists('class_ids_json', 'examination_seat_plans')) {
            $fields['class_ids_json'] = ['type' => 'TEXT', 'null' => true, 'after' => 'department_id'];
        }
        if (!$this->db->fieldExists('section_ids_json', 'examination_seat_plans')) {
            $fields['section_ids_json'] = ['type' => 'TEXT', 'null' => true, 'after' => 'class_ids_json'];
        }
        if ($fields !== []) {
            $this->forge->addColumn('examination_seat_plans', $fields);
        }
    }

    public function down()
    {
        foreach (['section_ids_json', 'class_ids_json', 'department_id'] as $field) {
            if ($this->db->fieldExists($field, 'examination_seat_plans')) {
                $this->forge->dropColumn('examination_seat_plans', $field);
            }
        }
    }
}

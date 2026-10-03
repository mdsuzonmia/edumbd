<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCategoryToResultWizardProgress extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('result_wizard_progress') && !$this->db->fieldExists('category_id', 'result_wizard_progress')) {
            $this->forge->addColumn('result_wizard_progress', [
                'category_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'section_id'],
            ]);
        }

        if ($this->db->tableExists('schools')) {
            $this->db->query(
                "UPDATE {$this->db->prefixTable('schools')} SET params = JSON_SET(COALESCE(NULLIF(params, ''), '{}'), '$.academic_class_roll_enabled', 1, '$.academic_section_enabled', 1, '$.academic_category_enabled', 1)"
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('result_wizard_progress') && $this->db->fieldExists('category_id', 'result_wizard_progress')) {
            $this->forge->dropColumn('result_wizard_progress', 'category_id');
        }
    }
}

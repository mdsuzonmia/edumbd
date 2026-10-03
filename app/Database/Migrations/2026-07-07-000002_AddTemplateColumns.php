<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTemplateColumns extends Migration
{
    public function up()
    {
        // Add missing columns to result_card_templates table
        $fields = [
            'school_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'id'],
            'support_multiple_exams' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'is_default'],
            'support_aggregated_result' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'support_multiple_exams'],
        ];
        
        $this->forge->addColumn('result_card_templates', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('result_card_templates', ['school_id', 'support_multiple_exams', 'support_aggregated_result']);
    }
}
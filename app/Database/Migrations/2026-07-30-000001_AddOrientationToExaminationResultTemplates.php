<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrientationToExaminationResultTemplates extends Migration
{
    public function up()
    {
        // Add orientation column to examination_result_templates table
        $this->forge->addColumn('examination_result_templates', [
            'orientation' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'template_type'
            ],
        ]);
    }

    public function down()
    {
        // Remove orientation column from examination_result_templates table
        $this->forge->dropColumn('examination_result_templates', 'orientation');
    }
}
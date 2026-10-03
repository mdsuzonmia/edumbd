<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExaminationResultTemplatesTable extends Migration
{
    public function up()
    {
        // Create examination_result_templates table
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'school_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'school_owner_uid' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'template_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'template_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'template_content' => ['type' => 'TEXT'],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'support_multiple_exams' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'support_aggregated_result' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('school_id');
        $this->forge->addKey('school_owner_uid');
        $this->forge->createTable('examination_result_templates', true);
    }

    public function down()
    {
        $this->forge->dropTable('examination_result_templates', true);
    }
}
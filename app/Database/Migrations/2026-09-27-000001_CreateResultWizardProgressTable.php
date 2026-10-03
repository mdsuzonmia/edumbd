<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResultWizardProgressTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'token' => ['type' => 'VARCHAR', 'constraint' => 64],
            'school_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'current_step' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'class_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'exam_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'year_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'section_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'subject_ids' => ['type' => 'TEXT', 'null' => true],
            'student_preview' => ['type' => 'LONGTEXT', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addKey(['school_id', 'user_id']);
        $this->forge->createTable('result_wizard_progress', true);
    }

    public function down()
    {
        $this->forge->dropTable('result_wizard_progress', true);
    }
}

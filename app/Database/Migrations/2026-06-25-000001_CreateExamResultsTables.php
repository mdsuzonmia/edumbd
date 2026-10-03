<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExamResultsTables extends Migration
{
    public function up()
    {
        // Create exam_results table
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'school_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'school_owner_uid' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'exam_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'session_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'class_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'section_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'enrollment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'total_subjects' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'passed_subjects' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'failed_subjects' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'total_marks' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'obtained_marks' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'percentage' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'gpa' => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0],
            'grade' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'letter_grade' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'result_status' => ['type' => 'ENUM', 'constraint' => ['passed', 'failed'], 'default' => 'passed'],
            'class_rank' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'section_rank' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'exam_rank' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'attendance_percentage' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 100],
            'principal_remarks' => ['type' => 'TEXT', 'null' => true],
            'teacher_remarks' => ['type' => 'TEXT', 'null' => true],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('school_id');
        $this->forge->addKey('exam_id');
        $this->forge->addKey('class_id');
        $this->forge->addKey('student_id');
        $this->forge->createTable('exam_results', true);

        // Create subject_results table
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'school_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'school_owner_uid' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'exam_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'session_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'class_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'section_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'enrollment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'subject_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'full_mark' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'obtained_mark' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'percentage' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'grade_point' => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0],
            'grade' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'letter_grade' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'highest_mark' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'is_fail' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('school_id');
        $this->forge->addKey('exam_id');
        $this->forge->addKey('class_id');
        $this->forge->addKey('student_id');
        $this->forge->addKey('subject_id');
        $this->forge->createTable('subject_results', true);

        // Create result_card_templates table
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'school_owner_uid' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'template_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'template_content' => ['type' => 'TEXT'],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('school_owner_uid');
        $this->forge->createTable('result_card_templates', true);
    }

    public function down()
    {
        $this->forge->dropTable('exam_results', true);
        $this->forge->dropTable('subject_results', true);
        $this->forge->dropTable('result_card_templates', true);
    }
}
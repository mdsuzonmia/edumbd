<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMarkLocksTable extends Migration
{
    public function up()
    {
        // Create mark_locks table
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'school_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'school_owner_uid' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'exam_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'session_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'class_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'section_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'subject_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'distribution_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_locked' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'locked_at' => ['type' => 'DATETIME', 'null' => true],
            'locked_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'unlocked_at' => ['type' => 'DATETIME', 'null' => true],
            'unlocked_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'lock_reason' => ['type' => 'TEXT', 'null' => true],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('school_id');
        $this->forge->addKey('exam_id');
        $this->forge->addKey('class_id');
        $this->forge->addKey('is_locked');
        $this->forge->addKey('school_owner_uid');
        $this->forge->addKey(['school_id', 'exam_id', 'class_id', 'section_id', 'subject_id', 'distribution_id'], false, 'UNIQUE');
        $this->forge->createTable('mark_locks', true);
    }

    public function down()
    {
        $this->forge->dropTable('mark_locks', true);
    }
}
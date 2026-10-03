<?php

namespace Modules\examination\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExaminationSeatPlans extends Migration
{
    private const PERMISSIONS = [
        'exam_seat_plan_view'     => 'View Exam Seat Plans',
        'exam_seat_plan_generate' => 'Generate Exam Seat Plans',
        'exam_seat_plan_edit'     => 'Edit Exam Seat Plans',
        'exam_seat_plan_lock'     => 'Lock Exam Seat Plans',
        'exam_seat_plan_unlock'   => 'Unlock Exam Seat Plans',
        'exam_seat_plan_print'    => 'Print Exam Seat Plans',
    ];

    public function up()
    {
        $this->createSeatPlansTable();
        $this->createSeatPlanRoomsTable();
        $this->createSeatAllocationsTable();
        $this->installPermissions();
    }

    public function down()
    {
        $this->removePermissions();
        $this->forge->dropTable('examination_seat_allocations', true);
        $this->forge->dropTable('examination_seat_plan_rooms', true);
        $this->forge->dropTable('examination_seat_plans', true);
    }

    private function createSeatPlansTable(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'token'              => ['type' => 'VARCHAR', 'constraint' => 64],
            'school_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'school_owner_uid'   => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'exam_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'session_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'shift_id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'title'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'exam_date'          => ['type' => 'DATE', 'null' => true],
            'allocation_method'  => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'sequential'],
            'seat_number_format' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'numeric'],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'draft'],
            'is_locked'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'locked_at'          => ['type' => 'DATETIME', 'null' => true],
            'locked_by'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_by'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'updated_by'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token', 'uq_examination_seat_plans_token');
        $this->forge->addKey(['school_id', 'exam_id', 'session_id'], false, false, 'idx_seat_plans_school_exam_session');
        $this->forge->addKey(['school_id', 'status'], false, false, 'idx_seat_plans_school_status');
        $this->forge->createTable('examination_seat_plans', true);
    }

    private function createSeatPlanRoomsTable(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'school_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'seat_plan_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'room_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'room_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 150],
            'room_no_snapshot'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'capacity'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'rows_count'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'columns_count'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'sort_order'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['school_id', 'seat_plan_id', 'room_id'], 'uq_seat_plan_rooms_plan_room');
        $this->forge->addKey(['school_id', 'seat_plan_id', 'sort_order'], false, false, 'idx_seat_plan_rooms_order');
        $this->forge->createTable('examination_seat_plan_rooms', true);
    }

    private function createSeatAllocationsTable(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'school_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'seat_plan_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'exam_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'room_id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'student_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'enrollment_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'session_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'class_id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'section_id'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'roll_no_snapshot' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'seat_no'          => ['type' => 'VARCHAR', 'constraint' => 30],
            'row_no'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'column_no'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['school_id', 'seat_plan_id', 'student_id'], 'uq_seat_allocations_plan_student');
        $this->forge->addUniqueKey(['school_id', 'seat_plan_id', 'room_id', 'row_no', 'column_no'], 'uq_seat_allocations_plan_position');
        $this->forge->addUniqueKey(['school_id', 'seat_plan_id', 'room_id', 'seat_no'], 'uq_seat_allocations_plan_seat');
        $this->forge->addKey(['school_id', 'exam_id', 'student_id'], false, false, 'idx_seat_allocations_exam_student');
        $this->forge->addKey(['school_id', 'seat_plan_id', 'room_id'], false, false, 'idx_seat_allocations_plan_room');
        $this->forge->addKey('enrollment_id');
        $this->forge->createTable('examination_seat_allocations', true);
    }

    private function installPermissions(): void
    {
        $permissionTable = $this->db->table('permissions');
        foreach (self::PERMISSIONS as $slug => $name) {
            if (!$permissionTable->where('slug', $slug)->countAllResults()) {
                $permissionTable->insert([
                    'name'   => $name,
                    'slug'   => $slug,
                    'module' => 'examination',
                    'status' => 1,
                ]);
            }
        }

        $ownerRole = $this->db->table('roles')->where('slug', 'school-owner')->get()->getRow();
        if (!$ownerRole) {
            return;
        }

        $rolePermissions = $this->db->table('role_permissions');
        $permissions = $permissionTable->whereIn('slug', array_keys(self::PERMISSIONS))->get()->getResult();
        foreach ($permissions as $permission) {
            $exists = $rolePermissions
                ->where('role_id', $ownerRole->id)
                ->where('permission_id', $permission->id)
                ->countAllResults();
            if (!$exists) {
                $rolePermissions->insert(['role_id' => $ownerRole->id, 'permission_id' => $permission->id]);
            }
        }
    }

    private function removePermissions(): void
    {
        $permissions = $this->db->table('permissions')
            ->select('id')
            ->whereIn('slug', array_keys(self::PERMISSIONS))
            ->get()
            ->getResultArray();
        $permissionIds = array_column($permissions, 'id');
        if ($permissionIds !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
}

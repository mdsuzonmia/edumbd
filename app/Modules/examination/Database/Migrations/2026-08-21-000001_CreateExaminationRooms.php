<?php

namespace Modules\examination\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExaminationRooms extends Migration
{
    private const PERMISSIONS = [
        'exam_room_view'   => 'View Exam Rooms',
        'exam_room_create' => 'Create Exam Rooms',
        'exam_room_edit'   => 'Edit Exam Rooms',
        'exam_room_delete' => 'Delete Exam Rooms',
    ];

    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'token'            => ['type' => 'VARCHAR', 'constraint' => 64],
            'school_id'        => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'school_owner_uid' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'room_name'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'room_no'          => ['type' => 'VARCHAR', 'constraint' => 50],
            'building'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'floor'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'capacity'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'rows_count'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'columns_count'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'updated_by'       => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token', 'uq_examination_rooms_token');
        $this->forge->addUniqueKey(['school_id', 'room_no'], 'uq_examination_rooms_school_room');
        $this->forge->addKey(['school_id', 'status'], false, false, 'idx_examination_rooms_school_status');
        $this->forge->createTable('examination_rooms', true);

        $this->installPermissions();
        $this->installMenu();
    }

    public function down()
    {
        $this->removeMenu();
        $this->removePermissions();
        $this->forge->dropTable('examination_rooms', true);
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
                $rolePermissions->insert([
                    'role_id'       => $ownerRole->id,
                    'permission_id' => $permission->id,
                ]);
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

    private function installMenu(): void
    {
        $menus = $this->db->table('menus');
        $parent = $menus->where('slug', 'school-exams')->get()->getRow();
        if (!$parent || $menus->where('slug', 'exam-rooms')->countAllResults()) {
            return;
        }

        $menus->where('parent_id', $parent->id)->where('menu_order >=', 11)->increment('menu_order', 1);
        $menus->insert([
            'module_id' => $parent->module_id,
            'parent_id'  => $parent->id,
            'title'      => 'Exam Rooms',
            'slug'       => 'exam-rooms',
            'route'      => 'examination/exam-rooms',
            'icon'       => 'bi bi-door-open',
            'menu_order' => 11,
            'status'     => 1,
        ]);
        $menuId = $this->db->insertID();

        $ownerRole = $this->db->table('roles')->where('slug', 'school-owner')->get()->getRow();
        if ($ownerRole) {
            $this->db->table('menu_roles')->insert([
                'menu_id' => $menuId,
                'role_id' => $ownerRole->id,
            ]);
        }
    }

    private function removeMenu(): void
    {
        $menus = $this->db->table('menus');
        $menu = $menus->where('slug', 'exam-rooms')->get()->getRow();
        if (!$menu) {
            return;
        }

        $this->db->table('menu_roles')->where('menu_id', $menu->id)->delete();
        $menus->where('id', $menu->id)->delete();
        $menus->where('parent_id', $menu->parent_id)->where('menu_order >', 11)->decrement('menu_order', 1);
    }
}

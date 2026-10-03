<?php

namespace Modules\examination\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExaminationAdmitCards extends Migration
{
    private const PERMISSIONS = [
        'exam_admit_card_view' => 'View Exam Admit Cards',
        'exam_admit_card_generate' => 'Generate Exam Admit Cards',
        'exam_admit_card_print' => 'Print Exam Admit Cards',
    ];

    public function up()
    {
        $this->createSettingsTable();
        $this->createCardsTable();
        $this->installPermissions();
        $this->installMenus();
    }

    public function down()
    {
        $this->removeMenus();
        $this->removePermissions();
        $this->forge->dropTable('examination_admit_cards', true);
        $this->forge->dropTable('examination_admit_card_settings', true);
    }

    private function createSettingsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'token' => ['type' => 'VARCHAR', 'constraint' => 64],
            'school_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'exam_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'session_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'ADMIT CARD'],
            'instructions' => ['type' => 'TEXT', 'null' => true],
            'show_student_photo' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'show_student_id' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'show_registration_no' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'show_exam_time' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'show_room' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'show_seat' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'show_qr_code' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'require_seat_plan' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token', 'uq_admit_card_settings_token');
        $this->forge->addUniqueKey(['school_id', 'exam_id', 'session_id'], 'uq_admit_card_settings_scope');
        $this->forge->createTable('examination_admit_card_settings', true);
    }

    private function createCardsTable(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'token' => ['type' => 'VARCHAR', 'constraint' => 64],
            'setting_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'school_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'exam_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'session_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'student_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'enrollment_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'status' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'generated_at' => ['type' => 'DATETIME', 'null' => true],
            'generated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token', 'uq_admit_cards_token');
        $this->forge->addUniqueKey(['school_id', 'exam_id', 'enrollment_id'], 'uq_admit_cards_exam_enrollment');
        $this->forge->addKey(['school_id', 'exam_id', 'student_id'], false, false, 'idx_admit_cards_exam_student');
        $this->forge->addKey(['student_id', 'status'], false, false, 'idx_admit_cards_student_status');
        $this->forge->createTable('examination_admit_cards', true);
    }

    private function installPermissions(): void
    {
        $permissions = $this->db->table('permissions');
        foreach (self::PERMISSIONS as $slug => $name) {
            if (!$permissions->where('slug', $slug)->countAllResults()) {
                $permissions->insert(['name' => $name, 'slug' => $slug, 'module' => 'examination', 'status' => 1]);
            }
        }
        $ownerRole = $this->db->table('roles')->where('slug', 'school-owner')->get()->getRow();
        if (!$ownerRole) {
            return;
        }
        foreach ($permissions->whereIn('slug', array_keys(self::PERMISSIONS))->get()->getResult() as $permission) {
            $relation = $this->db->table('role_permissions');
            if (!$relation->where('role_id', $ownerRole->id)->where('permission_id', $permission->id)->countAllResults()) {
                $relation->insert(['role_id' => $ownerRole->id, 'permission_id' => $permission->id]);
            }
        }
    }

    private function installMenus(): void
    {
        $menus = $this->db->table('menus');
        $parent = $menus->where('slug', 'school-exams')->get()->getRow();
        if ($parent && !$menus->where('slug', 'exam-admit-cards')->countAllResults()) {
            $menus->where('parent_id', $parent->id)->where('menu_order >=', 13)->increment('menu_order', 1);
            $menus->insert(['module_id' => $parent->module_id, 'parent_id' => $parent->id, 'title' => 'Admit Card', 'slug' => 'exam-admit-cards', 'route' => 'examination/admit-cards', 'icon' => 'bi bi-person-badge', 'menu_order' => 13, 'status' => 1]);
            $this->attachMenuToRole((int) $this->db->insertID(), 'school-owner');
        }

        $studentParent = $menus->where('slug', 'results')->get()->getRow();
        if ($studentParent && !$menus->where('slug', 'student-admit-cards')->countAllResults()) {
            $menus->insert(['module_id' => $studentParent->module_id, 'parent_id' => $studentParent->id, 'title' => 'Admit Card', 'slug' => 'student-admit-cards', 'route' => 'examination/student/admit-cards', 'icon' => 'bi bi-person-badge', 'menu_order' => 3, 'status' => 1]);
            $this->attachMenuToRole((int) $this->db->insertID(), 'student');
        }
    }

    private function attachMenuToRole(int $menuId, string $roleSlug): void
    {
        $role = $this->db->table('roles')->where('slug', $roleSlug)->get()->getRow();
        if ($role) {
            $this->db->table('menu_roles')->insert(['menu_id' => $menuId, 'role_id' => $role->id]);
        }
    }

    private function removePermissions(): void
    {
        $ids = array_column($this->db->table('permissions')->select('id')->whereIn('slug', array_keys(self::PERMISSIONS))->get()->getResultArray(), 'id');
        if ($ids !== []) {
            $this->db->table('role_permissions')->whereIn('permission_id', $ids)->delete();
            $this->db->table('permissions')->whereIn('id', $ids)->delete();
        }
    }

    private function removeMenus(): void
    {
        $menus = $this->db->table('menus');
        foreach (['exam-admit-cards', 'student-admit-cards'] as $slug) {
            $menu = $menus->where('slug', $slug)->get()->getRow();
            if ($menu) {
                $this->db->table('menu_roles')->where('menu_id', $menu->id)->delete();
                $menus->where('id', $menu->id)->delete();
            }
        }
    }
}

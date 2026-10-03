<?php

namespace Modules\admission\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdmissionModule extends Migration
{
    private const TABLES = [
        'admission_confirmations', 'admission_lottery_results', 'admission_lottery_candidates',
        'admission_lotteries', 'admission_status_history', 'admission_reviews',
        'admission_payments', 'admission_documents', 'admission_application_values',
        'admission_applications', 'admission_form_fields', 'admission_circulars', 'admission_sessions',
    ];

    private const PERMISSIONS = [
        'admission_view' => 'View Admission', 'admission_manage' => 'Manage Admission',
        'admission_review' => 'Review Applications', 'admission_lottery' => 'Manage Admission Lottery',
        'admission_enroll' => 'Complete Student Admission', 'admission_reports' => 'View Admission Reports',
    ];

    public function up()
    {
        $this->sessions();
        $this->circulars();
        $this->formFields();
        $this->applications();
        $this->applicationValues();
        $this->documents();
        $this->payments();
        $this->reviews();
        $this->statusHistory();
        $this->lotteries();
        $this->lotteryCandidates();
        $this->lotteryResults();
        $this->confirmations();
        $this->installPermissions();
        $this->installMenus();
    }

    public function down()
    {
        $this->removeMenus();
        $this->removePermissions();
        foreach (self::TABLES as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    private function id(): array { return ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true]; }
    private function bigint(bool $null = false): array { return ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'null'=>$null]; }
    private function varchar(int $length, bool $null = false): array { return ['type'=>'VARCHAR','constraint'=>$length,'null'=>$null]; }
    private function timestamps(): array { return ['created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true]]; }
    private function create(string $table, array $fields, array $indexes = [], array $unique = []): void
    {
        $this->forge->addField($fields + $this->timestamps());
        $this->forge->addKey('id', true);
        foreach ($indexes as $index) $this->forge->addKey($index);
        foreach ($unique as $index) $this->forge->addUniqueKey($index);
        $this->forge->createTable($table, true);
    }

    private function sessions(): void
    {
        $this->create('admission_sessions', [
            'id'=>$this->id(), 'token'=>$this->varchar(64), 'school_id'=>$this->bigint(),
            'school_owner_uid'=>$this->bigint(true), 'title'=>$this->varchar(180),
            'academic_year_id'=>$this->bigint(), 'application_start'=>['type'=>'DATETIME'],
            'application_end'=>['type'=>'DATETIME'], 'admission_start'=>['type'=>'DATETIME','null'=>true],
            'admission_end'=>['type'=>'DATETIME','null'=>true], 'status'=>$this->varchar(20),
            'created_by'=>$this->bigint(true), 'updated_by'=>$this->bigint(true),
        ], [['school_id','status'], 'academic_year_id'], ['token']);
    }

    private function circulars(): void
    {
        $this->create('admission_circulars', [
            'id'=>$this->id(), 'token'=>$this->varchar(64), 'school_id'=>$this->bigint(),
            'admission_session_id'=>$this->bigint(), 'title'=>$this->varchar(200),
            'description'=>['type'=>'TEXT','null'=>true], 'class_id'=>$this->bigint(),
            'shift_id'=>$this->bigint(true), 'version_id'=>$this->bigint(true),
            'department_id'=>$this->bigint(true), 'group_id'=>$this->bigint(true),
            'available_seats'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'default'=>0],
            'minimum_age'=>['type'=>'DECIMAL','constraint'=>'4,1','null'=>true],
            'maximum_age'=>['type'=>'DECIMAL','constraint'=>'4,1','null'=>true],
            'application_start'=>['type'=>'DATETIME'], 'application_deadline'=>['type'=>'DATETIME'],
            'application_fee'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0],
            'admission_fee'=>['type'=>'DECIMAL','constraint'=>'12,2','default'=>0],
            'lottery_required'=>['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'instructions'=>['type'=>'TEXT','null'=>true], 'required_documents'=>['type'=>'TEXT','null'=>true],
            'status'=>$this->varchar(20), 'created_by'=>$this->bigint(true), 'updated_by'=>$this->bigint(true),
        ], [['school_id','status'], ['admission_session_id','class_id']], ['token']);
    }

    private function formFields(): void
    {
        $this->create('admission_form_fields', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'circular_id'=>$this->bigint(),
            'field_key'=>$this->varchar(80), 'label'=>$this->varchar(150), 'field_type'=>$this->varchar(30),
            'options_json'=>['type'=>'TEXT','null'=>true], 'validation_rules'=>$this->varchar(255,true),
            'placeholder'=>$this->varchar(180,true), 'is_required'=>['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'sort_order'=>['type'=>'INT','constraint'=>11,'default'=>0], 'status'=>['type'=>'TINYINT','constraint'=>1,'default'=>1],
        ], [['school_id','circular_id','sort_order']], [['circular_id','field_key']]);
    }

    private function applications(): void
    {
        $this->create('admission_applications', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'admission_session_id'=>$this->bigint(),
            'circular_id'=>$this->bigint(), 'application_no'=>$this->varchar(40), 'token'=>$this->varchar(64),
            'academic_year_id'=>$this->bigint(), 'class_id'=>$this->bigint(), 'section_id'=>$this->bigint(true),
            'shift_id'=>$this->bigint(true), 'version_id'=>$this->bigint(true), 'department_id'=>$this->bigint(true),
            'group_id'=>$this->bigint(true), 'student_name'=>$this->varchar(180), 'student_name_bn'=>$this->varchar(180,true),
            'dob'=>['type'=>'DATE'], 'gender'=>$this->varchar(20), 'birth_registration_no'=>$this->varchar(60,true),
            'nationality'=>$this->varchar(80,true), 'religion'=>$this->varchar(80,true), 'blood_group'=>$this->varchar(10,true),
            'photo'=>$this->varchar(255,true), 'father_name'=>$this->varchar(180,true), 'father_occupation'=>$this->varchar(120,true),
            'father_mobile'=>$this->varchar(30,true), 'father_email'=>$this->varchar(150,true), 'father_nid'=>$this->varchar(50,true),
            'mother_name'=>$this->varchar(180,true), 'mother_occupation'=>$this->varchar(120,true),
            'mother_mobile'=>$this->varchar(30,true), 'mother_email'=>$this->varchar(150,true), 'mother_nid'=>$this->varchar(50,true),
            'guardian_name'=>$this->varchar(180,true), 'guardian_mobile'=>$this->varchar(30), 'guardian_relation'=>$this->varchar(60,true),
            'present_address'=>['type'=>'TEXT','null'=>true], 'permanent_address'=>['type'=>'TEXT','null'=>true],
            'previous_school'=>$this->varchar(200,true), 'previous_class'=>$this->varchar(80,true),
            'previous_roll'=>$this->varchar(50,true), 'previous_result'=>$this->varchar(80,true),
            'board'=>$this->varchar(100,true), 'passing_year'=>$this->varchar(10,true),
            'application_status'=>$this->varchar(30), 'payment_status'=>$this->varchar(20),
            'correction_message'=>['type'=>'TEXT','null'=>true], 'submitted_at'=>['type'=>'DATETIME','null'=>true],
            'reviewed_at'=>['type'=>'DATETIME','null'=>true], 'admitted_at'=>['type'=>'DATETIME','null'=>true],
            'student_id'=>$this->bigint(true), 'created_by'=>$this->bigint(true), 'updated_by'=>$this->bigint(true),
        ], [['school_id','application_status'], ['circular_id','application_status'], ['school_id','guardian_mobile']], [['school_id','application_no'], 'token']);
    }

    private function applicationValues(): void
    {
        $this->create('admission_application_values', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'application_id'=>$this->bigint(),
            'field_id'=>$this->bigint(), 'value'=>['type'=>'TEXT','null'=>true],
        ], ['application_id'], [['application_id','field_id']]);
    }

    private function documents(): void
    {
        $this->create('admission_documents', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'application_id'=>$this->bigint(),
            'document_type'=>$this->varchar(80), 'original_name'=>$this->varchar(255), 'stored_name'=>$this->varchar(255),
            'mime_type'=>$this->varchar(100,true), 'file_size'=>$this->bigint(true), 'verification_status'=>$this->varchar(20),
            'verified_by'=>$this->bigint(true), 'verified_at'=>['type'=>'DATETIME','null'=>true],
        ], [['school_id','application_id']]);
    }

    private function payments(): void
    {
        $this->create('admission_payments', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'application_id'=>$this->bigint(),
            'payment_type'=>$this->varchar(30), 'amount'=>['type'=>'DECIMAL','constraint'=>'12,2'],
            'currency'=>$this->varchar(10), 'gateway'=>$this->varchar(50,true), 'transaction_id'=>$this->varchar(120,true),
            'status'=>$this->varchar(20), 'paid_at'=>['type'=>'DATETIME','null'=>true], 'metadata'=>['type'=>'TEXT','null'=>true],
        ], [['school_id','status'], 'application_id']);
    }

    private function reviews(): void
    {
        $this->create('admission_reviews', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'application_id'=>$this->bigint(),
            'decision'=>$this->varchar(30), 'notes'=>['type'=>'TEXT','null'=>true], 'reviewed_by'=>$this->bigint(),
        ], [['school_id','application_id']]);
    }

    private function statusHistory(): void
    {
        $this->create('admission_status_history', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'application_id'=>$this->bigint(),
            'from_status'=>$this->varchar(30,true), 'to_status'=>$this->varchar(30), 'note'=>['type'=>'TEXT','null'=>true],
            'created_by'=>$this->bigint(true),
        ], [['school_id','application_id']]);
    }

    private function lotteries(): void
    {
        $this->create('admission_lotteries', [
            'id'=>$this->id(), 'token'=>$this->varchar(64), 'school_id'=>$this->bigint(),
            'admission_session_id'=>$this->bigint(), 'circular_id'=>$this->bigint(), 'name'=>$this->varchar(180),
            'academic_year_id'=>$this->bigint(), 'class_id'=>$this->bigint(), 'shift_id'=>$this->bigint(true),
            'version_id'=>$this->bigint(true), 'seat_count'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'waiting_count'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'default'=>0],
            'candidate_count'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'default'=>0],
            'revision'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'default'=>1], 'status'=>$this->varchar(20),
            'algorithm_version'=>$this->varchar(40), 'candidate_hash'=>$this->varchar(64,true), 'result_hash'=>$this->varchar(64,true),
            'locked_at'=>['type'=>'DATETIME','null'=>true], 'drawn_at'=>['type'=>'DATETIME','null'=>true],
            'finalized_at'=>['type'=>'DATETIME','null'=>true], 'cancelled_at'=>['type'=>'DATETIME','null'=>true],
            'cancelled_by'=>$this->bigint(true), 'cancellation_reason'=>['type'=>'TEXT','null'=>true],
            'created_by'=>$this->bigint(true), 'updated_by'=>$this->bigint(true),
        ], [['school_id','status'], ['circular_id','revision']], ['token']);
    }

    private function lotteryCandidates(): void
    {
        $this->create('admission_lottery_candidates', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'lottery_id'=>$this->bigint(),
            'application_id'=>$this->bigint(), 'application_no'=>$this->varchar(40), 'snapshot_hash'=>$this->varchar(64),
        ], [['school_id','lottery_id']], [['lottery_id','application_id']]);
    }

    private function lotteryResults(): void
    {
        $this->create('admission_lottery_results', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'lottery_id'=>$this->bigint(),
            'application_id'=>$this->bigint(), 'draw_position'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'result'=>$this->varchar(20), 'waiting_position'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
        ], [['school_id','lottery_id','result']], [['lottery_id','application_id'],['lottery_id','draw_position']]);
    }

    private function confirmations(): void
    {
        $this->create('admission_confirmations', [
            'id'=>$this->id(), 'school_id'=>$this->bigint(), 'application_id'=>$this->bigint(),
            'student_id'=>$this->bigint(), 'enrollment_id'=>$this->bigint(), 'admission_no'=>$this->varchar(50),
            'roll_no'=>$this->varchar(30,true), 'confirmed_by'=>$this->bigint(), 'confirmed_at'=>['type'=>'DATETIME'],
            'notes'=>['type'=>'TEXT','null'=>true],
        ], [['school_id','student_id']], ['application_id']);
    }

    private function installPermissions(): void
    {
        $table = $this->db->table('permissions');
        foreach (self::PERMISSIONS as $slug => $name) {
            if (!$table->where('slug', $slug)->countAllResults()) {
                $table->insert(['name'=>$name,'slug'=>$slug,'module'=>'admission','status'=>1]);
            }
        }
        $role = $this->db->table('roles')->where('slug','school-owner')->get()->getRow();
        if (!$role) return;
        $permissions = $table->whereIn('slug', array_keys(self::PERMISSIONS))->get()->getResult();
        foreach ($permissions as $permission) {
            $pivot = $this->db->table('role_permissions');
            if (!$pivot->where('role_id',$role->id)->where('permission_id',$permission->id)->countAllResults()) {
                $pivot->insert(['role_id'=>$role->id,'permission_id'=>$permission->id]);
            }
        }
    }

    private function installMenus(): void
    {
        $module = $this->db->table('modules')->where('slug','admission')->get()->getRow();
        $menus = $this->db->table('menus');
        if ($menus->where('slug','school-admission')->countAllResults()) return;
        $menus->insert(['module_id'=>$module->id ?? null,'parent_id'=>null,'title'=>'Admission','slug'=>'school-admission','route'=>'school/admission','icon'=>'bi bi-person-plus','menu_order'=>50,'status'=>1]);
        $parentId = $this->db->insertID();
        $children = [
            ['Admission Dashboard','admission-dashboard','school/admission'],
            ['Admission Sessions','admission-sessions','school/admission/sessions'],
            ['Admission Circulars','admission-circulars','school/admission/circulars'],
            ['Applications','admission-applications','school/admission/applications'],
            ['Lottery','admission-lotteries','school/admission/lotteries'],
        ];
        $role = $this->db->table('roles')->where('slug','school-owner')->get()->getRow();
        if ($role) $this->db->table('menu_roles')->insert(['menu_id'=>$parentId,'role_id'=>$role->id]);
        foreach ($children as $order => [$title,$slug,$route]) {
            $menus->insert(['module_id'=>$module->id ?? null,'parent_id'=>$parentId,'title'=>$title,'slug'=>$slug,'route'=>$route,'icon'=>'bi bi-chevron-right','menu_order'=>$order + 1,'status'=>1]);
            if ($role) $this->db->table('menu_roles')->insert(['menu_id'=>$this->db->insertID(),'role_id'=>$role->id]);
        }
    }

    private function removePermissions(): void
    {
        $rows=$this->db->table('permissions')->select('id')->whereIn('slug',array_keys(self::PERMISSIONS))->get()->getResultArray();
        $ids=array_column($rows,'id');
        if ($ids) { $this->db->table('role_permissions')->whereIn('permission_id',$ids)->delete(); $this->db->table('permissions')->whereIn('id',$ids)->delete(); }
    }

    private function removeMenus(): void
    {
        $rows=$this->db->table('menus')->select('id')->whereIn('slug',['school-admission','admission-dashboard','admission-sessions','admission-circulars','admission-applications','admission-lotteries'])->get()->getResultArray();
        $ids=array_column($rows,'id');
        if ($ids) { $this->db->table('menu_roles')->whereIn('menu_id',$ids)->delete(); $this->db->table('menus')->whereIn('id',$ids)->delete(); }
    }
}

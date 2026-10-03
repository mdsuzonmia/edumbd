<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentPromotionModel;
use App\Models\StudentPromotionItemModel;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsDepartmentModel;
use App\Models\AcademicsCategoryModel;
use App\Models\AcademicsShiftModel;

class StudentsPromotion extends BaseController
{
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentPromotionModel $PromotionModel;
    protected StudentPromotionItemModel $PromotionItemModel;
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsDepartmentModel $DepartmentModel;
    protected AcademicsCategoryModel $CategoryModel;
    protected AcademicsShiftModel $ShiftModel;

    public function __construct()
    {
        $this->StudentModel       = new StudentModel();
        $this->EnrollmentModel    = new StudentEnrollmentModel();
        $this->PromotionModel     = new StudentPromotionModel();
        $this->PromotionItemModel = new StudentPromotionItemModel();
        $this->SchoolModel        = new SchoolModel();
        $this->YearModel          = new AcademicsYearModel();
        $this->ClassModel         = new AcademicsClassesModel();
        $this->SectionModel       = new AcademicsSectionModel();
        $this->DepartmentModel    = new AcademicsDepartmentModel();
        $this->CategoryModel      = new AcademicsCategoryModel();
        $this->ShiftModel         = new AcademicsShiftModel();
    }

    protected function getUserId(): int
    {
        return (int) session('user_id');
    }

    protected function getUserSchools(): array
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return [];
        }

        return $this->SchoolModel
            ->select('schools.id, schools.name, schools.params')
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('school_user_relation.user_id', $user_id)
            ->where('schools.status', 1)
            ->orderBy('schools.name', 'ASC')
            ->findAll();
    }

    protected function getSchoolDropdown(): array
    {
        $schools = $this->getUserSchools();
        $list = [];
        foreach ($schools as $s) {
            $list[$s->id] = $s->name;
        }
        return $list;
    }

    protected function getAllowedSchoolId(int $schoolId): int
    {
        if (!$schoolId) {
            return 0;
        }

        $schoolIds = array_map(static function ($school) {
            return (int) $school->id;
        }, $this->getUserSchools());

        return in_array($schoolId, $schoolIds, true) ? $schoolId : 0;
    }

    protected function getActiveOptions(int $school_id, string $modelProperty): array
    {
        $list = [];
        $records = $this->{$modelProperty}
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        foreach ($records as $r) {
            $list[$r->id] = $r->title;
        }
        return $list;
    }

    /**
     * Promotion page - select school, from session/class, to session/class
     */
    public function index()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('Promotion.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $school_id = (int) $this->request->getGet('school_id') ?: 0;
        $from_session_id = (int) $this->request->getGet('from_session_id') ?: 0;
        $to_session_id   = (int) $this->request->getGet('to_session_id') ?: 0;
        $from_class_id   = (int) $this->request->getGet('from_class_id') ?: 0;
        $to_class_id     = (int) $this->request->getGet('to_class_id') ?: 0;

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

        if ($school_id && !in_array($school_id, $schoolIds)) {
            $school_id = 0;
        }

        $data = [
            'school_list'      => $this->getSchoolDropdown(),
            'selected_school'  => $school_id,
            'from_session_id'  => $from_session_id,
            'to_session_id'    => $to_session_id,
            'from_class_id'    => $from_class_id,
            'to_class_id'      => $to_class_id,
            'year_list'        => $school_id ? $this->getActiveOptions($school_id, 'YearModel') : [],
            'class_list'       => $school_id ? $this->getActiveOptions($school_id, 'ClassModel') : [],
        ];

        return view('header', $header_data)
            . view('school_owner/students/promotion/index', $data)
            . view('footer', $footer_data);
    }

    /**
     * Preview eligible students for promotion
     */
    public function preview()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $school_id      = (int) $this->request->getPost('school_id');
        $from_session_id = (int) $this->request->getPost('from_session_id');
        $to_session_id   = (int) $this->request->getPost('to_session_id');
        $from_class_id   = (int) $this->request->getPost('from_class_id');
        $to_class_id     = (int) $this->request->getPost('to_class_id');

        // Validate
        $school_id = $this->getAllowedSchoolId($school_id);
        if (!$school_id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Promotion.msg_invalid_school'),
            ]);
        }

        if (!$from_session_id || !$from_class_id || !$to_session_id || !$to_class_id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Promotion.msg_select_session_class'),
            ]);
        }

        // Get students enrolled in the from session/class
        $students = $this->EnrollmentModel
            ->select('
                students.id AS student_id,
                students.student_code,
                students.first_name,
                students.middle_name,
                students.last_name,
                students.photo,
                students.gender,
                student_enrollments.id AS enrollment_id,
                student_enrollments.roll_no,
                student_enrollments.section_id,
                student_enrollments.department_id,
                student_enrollments.group_id,
                student_enrollments.shift_id,
                student_enrollments.category_id,
                academic_classes.title AS class_title,
                academic_years.title AS session_title
            ')
            ->join('students', 'students.id = student_enrollments.student_id', 'inner')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $from_session_id)
            ->where('student_enrollments.class_id', $from_class_id)
            ->where('students.school_owner_uid', $user_id)
            ->where('students.student_status', 'Active')
            ->where('students.status', 1)
            ->groupBy('students.id')
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->orderBy('students.first_name', 'ASC')
            ->findAll();

        // Check which students are already enrolled in the target session/class
        $existingEnrollments = [];
        if (!empty($students)) {
            $studentIds = array_map(function ($s) {
                return $s->student_id;
            }, $students);

            $existing = $this->EnrollmentModel
                ->where('school_id', $school_id)
                ->where('session_id', $to_session_id)
                ->where('class_id', $to_class_id)
                ->whereIn('student_id', $studentIds)
                ->findAll();

            foreach ($existing as $e) {
                $existingEnrollments[$e->student_id] = true;
            }
        }

        // Get from/to session and class titles
        $fromSession = $this->YearModel->find($from_session_id);
        $toSession   = $this->YearModel->find($to_session_id);
        $fromClass   = $this->ClassModel->find($from_class_id);
        $toClass     = $this->ClassModel->find($to_class_id);

        $eligibleCount = 0;
        $alreadyCount  = 0;
        foreach ($students as $s) {
            if (isset($existingEnrollments[$s->student_id])) {
                $s->already_enrolled = true;
                $alreadyCount++;
            } else {
                $s->already_enrolled = false;
                $eligibleCount++;
            }
        }

        $html = view('school_owner/students/promotion/preview', [
            'students'          => $students,
            'existing_enrollments' => $existingEnrollments,
            'school_id'         => $school_id,
            'from_session_id'   => $from_session_id,
            'to_session_id'     => $to_session_id,
            'from_class_id'     => $from_class_id,
            'to_class_id'       => $to_class_id,
            'from_session'      => $fromSession,
            'to_session'        => $toSession,
            'from_class'        => $fromClass,
            'to_class'          => $toClass,
            'eligible_count'    => $eligibleCount,
            'already_count'     => $alreadyCount,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'html'    => $html,
            'eligible_count' => $eligibleCount,
            'already_count'  => $alreadyCount,
        ]);
    }

    /**
     * Process promotion
     */
    public function process()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $school_id       = (int) $this->request->getPost('school_id');
        $from_session_id = (int) $this->request->getPost('from_session_id');
        $to_session_id   = (int) $this->request->getPost('to_session_id');
        $from_class_id   = (int) $this->request->getPost('from_class_id');
        $to_class_id     = (int) $this->request->getPost('to_class_id');
        $student_ids     = $this->request->getPost('student_ids');
        $roll_nos        = $this->request->getPost('roll_nos');
        $notes           = $this->request->getPost('notes');

        $school_id = $this->getAllowedSchoolId($school_id);
        if (!$school_id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Promotion.msg_invalid_school'),
            ]);
        }

        if (!$from_session_id || !$from_class_id || !$to_session_id || !$to_class_id) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Promotion.msg_select_session_class'),
            ]);
        }

        if (empty($student_ids) || !is_array($student_ids)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Promotion.msg_select_students'),
            ]);
        }

        // Begin transaction
        $this->EnrollmentModel->db->transBegin();

        try {
            // Create promotion batch record
            $promotionData = [
                'school_id'        => $school_id,
                'school_owner_uid' => $user_id,
                'from_session_id'  => $from_session_id,
                'to_session_id'    => $to_session_id,
                'from_class_id'    => $from_class_id,
                'to_class_id'      => $to_class_id,
                'total_students'   => count($student_ids),
                'success_count'    => 0,
                'failed_count'     => 0,
                'status'           => 'completed',
                'notes'            => $notes ?? '',
            ];

            $this->PromotionModel->insert($promotionData);
            $promotionId = $this->PromotionModel->getInsertID();

            $successCount = 0;
            $failedCount  = 0;

            foreach ($student_ids as $index => $studentId) {
                $studentId = (int) $studentId;

                // Get the current enrollment
                $currentEnrollment = $this->EnrollmentModel
                    ->where('student_id', $studentId)
                    ->where('session_id', $from_session_id)
                    ->where('class_id', $from_class_id)
                    ->where('school_id', $school_id)
                    ->first();

                if (!$currentEnrollment) {
                    // Log failure
                    $this->PromotionItemModel->insert([
                        'promotion_id'       => $promotionId,
                        'student_id'         => $studentId,
                        'from_enrollment_id' => 0,
                        'to_enrollment_id'   => null,
                        'roll_no'            => $roll_nos[$index] ?? '',
                        'status'             => 'failed',
                        'error_message'      => 'Current enrollment not found.',
                        'created_at'         => date('Y-m-d H:i:s'),
                    ]);
                    $failedCount++;
                    continue;
                }

                // Check if already enrolled in target
                $existingEnrollment = $this->EnrollmentModel
                    ->where('student_id', $studentId)
                    ->where('session_id', $to_session_id)
                    ->where('class_id', $to_class_id)
                    ->where('school_id', $school_id)
                    ->first();

                if ($existingEnrollment) {
                    $this->PromotionItemModel->insert([
                        'promotion_id'       => $promotionId,
                        'student_id'         => $studentId,
                        'from_enrollment_id' => $currentEnrollment->id,
                        'to_enrollment_id'   => $existingEnrollment->id,
                        'roll_no'            => $roll_nos[$index] ?? $currentEnrollment->roll_no,
                        'status'             => 'failed',
                        'error_message'      => lang('Promotion.msg_student_already_enrolled'),
                        'created_at'         => date('Y-m-d H:i:s'),
                    ]);
                    $failedCount++;
                    continue;
                }

                // Create new enrollment for the target session/class
                $newEnrollmentData = [
                    'school_id'        => $school_id,
                    'school_owner_uid' => $user_id,
                    'student_id'       => $studentId,
                    'session_id'       => $to_session_id,
                    'class_id'         => $to_class_id,
                    'section_id'       => $currentEnrollment->section_id ?: 0,
                    'department_id'    => $currentEnrollment->department_id ?: 0,
                    'group_id'         => $currentEnrollment->group_id ?: 0,
                    'shift_id'         => $currentEnrollment->shift_id ?: 0,
                    'category_id'      => $currentEnrollment->category_id ?: 0,
                    'roll_no'          => $roll_nos[$index] ?? $currentEnrollment->roll_no,
                    'status'           => 1,
                    'created_by'       => $user_id,
                ];

                $this->EnrollmentModel->insert($newEnrollmentData);
                $newEnrollmentId = $this->EnrollmentModel->getInsertID();

                // Log success
                $this->PromotionItemModel->insert([
                    'promotion_id'       => $promotionId,
                    'student_id'         => $studentId,
                    'from_enrollment_id' => $currentEnrollment->id,
                    'to_enrollment_id'   => $newEnrollmentId,
                    'roll_no'            => $roll_nos[$index] ?? $currentEnrollment->roll_no,
                    'status'             => 'success',
                    'error_message'      => null,
                    'created_at'         => date('Y-m-d H:i:s'),
                ]);

                $successCount++;
            }

            // Update promotion batch counts
            $batchStatus = ($failedCount === 0) ? 'completed' : 'partial';
            $this->PromotionModel->update($promotionId, [
                'success_count' => $successCount,
                'failed_count'  => $failedCount,
                'status'        => $batchStatus,
            ]);

            if ($this->EnrollmentModel->db->transStatus() === false) {
                $this->EnrollmentModel->db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database transaction failed.',
                ]);
            }

            $this->EnrollmentModel->db->transCommit();

            $message = ($failedCount === 0)
                ? lang('Promotion.msg_promotion_success')
                : lang('Promotion.msg_promotion_partial');

            return $this->response->setJSON([
                'success'       => true,
                'message'       => $message,
                'promotion_id'  => $promotionId,
                'success_count' => $successCount,
                'failed_count'  => $failedCount,
            ]);

        } catch (\Exception $e) {
            $this->EnrollmentModel->db->transRollback();
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Promotion history list
     */
    public function history()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('Promotion.page_title_history'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $school_id = (int) $this->request->getGet('school_id') ?: 0;

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

        if ($school_id && !in_array($school_id, $schoolIds)) {
            $school_id = 0;
        }

        $db = \Config\Database::connect();
        $prefix = $db->getPrefix(); // e.g. 'edum_'

        // Build query using raw SQL to avoid prefix being applied to aliases
        $sql = "SELECT 
                    p.*,
                    fs.title AS from_session_title,
                    ts.title AS to_session_title,
                    fc.title AS from_class_title,
                    tc.title AS to_class_title,
                    s.name AS school_name
                FROM {$prefix}student_promotions p
                LEFT JOIN {$prefix}academic_years fs ON fs.id = p.from_session_id
                LEFT JOIN {$prefix}academic_years ts ON ts.id = p.to_session_id
                LEFT JOIN {$prefix}academic_classes fc ON fc.id = p.from_class_id
                LEFT JOIN {$prefix}academic_classes tc ON tc.id = p.to_class_id
                LEFT JOIN {$prefix}schools s ON s.id = p.school_id
                WHERE p.school_owner_uid = ?
                AND p.school_id IN (" . implode(',', array_map('intval', $schoolIds)) . ")
                ORDER BY p.id DESC";

        $query = $db->query($sql, [$user_id]);
        $allItems = $query->getResult();

        if ($school_id) {
            $allItems = array_filter($allItems, function($item) use ($school_id) {
                return $item->school_id == $school_id;
            });
        }

        $perPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $page    = (int) ($this->request->getGet('page') ?? 1);
        $total   = count($allItems);
        $offset  = ($page - 1) * $perPage;
        $items   = array_slice(array_values($allItems), $offset, $perPage);

        // Create a simple pager
        $pager = \CodeIgniter\Config\Services::pager();
        $pager->makeLinks($page, $perPage, $total, 'custom_pagination');

        $data = [
            'items'          => $items,
            'pager'          => $pager,
            'pagerTemplate'  => 'custom_pagination',
            'school_list'    => $this->getSchoolDropdown(),
            'selected_school' => $school_id,
        ];

        return view('header', $header_data)
            . view('school_owner/students/promotion/history', $data)
            . view('footer', $footer_data);
    }

    /**
     * Promotion details
     */
    public function details(int $promotionId)
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('Promotion.page_title_details'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();

        // Get promotion batch using raw SQL
        $sql = "SELECT 
                    p.*,
                    fs.title AS from_session_title,
                    ts.title AS to_session_title,
                    fc.title AS from_class_title,
                    tc.title AS to_class_title,
                    s.name AS school_name
                FROM {$prefix}student_promotions p
                LEFT JOIN {$prefix}academic_years fs ON fs.id = p.from_session_id
                LEFT JOIN {$prefix}academic_years ts ON ts.id = p.to_session_id
                LEFT JOIN {$prefix}academic_classes fc ON fc.id = p.from_class_id
                LEFT JOIN {$prefix}academic_classes tc ON tc.id = p.to_class_id
                LEFT JOIN {$prefix}schools s ON s.id = p.school_id
                WHERE p.id = ? AND p.school_owner_uid = ?
                LIMIT 1";

        $promotion = $db->query($sql, [$promotionId, $user_id])->getRow();

        if (!$promotion) {
            return redirect()->to('school-owner/students/promotion/history')
                ->with('error', 'Promotion record not found.');
        }

        // Get promotion items with student details using raw SQL
        $sqlItems = "SELECT 
                        pi.*,
                        st.student_code,
                        st.first_name,
                        st.middle_name,
                        st.last_name,
                        st.photo,
                        fe.roll_no AS from_roll_no,
                        te.roll_no AS to_roll_no
                    FROM {$prefix}student_promotion_items pi
                    LEFT JOIN {$prefix}students st ON st.id = pi.student_id
                    LEFT JOIN {$prefix}student_enrollments fe ON fe.id = pi.from_enrollment_id
                    LEFT JOIN {$prefix}student_enrollments te ON te.id = pi.to_enrollment_id
                    WHERE pi.promotion_id = ?
                    ORDER BY pi.id ASC";

        $items = $db->query($sqlItems, [$promotionId])->getResult();

        $data = [
            'promotion' => $promotion,
            'items'     => $items,
        ];

        return view('header', $header_data)
            . view('school_owner/students/promotion/details', $data)
            . view('footer', $footer_data);
    }

    /**
     * Rollback a promotion
     */
    public function rollback(int $promotionId)
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        // Get promotion batch
        $promotion = $this->PromotionModel
            ->where('id', $promotionId)
            ->where('school_owner_uid', $user_id)
            ->first();

        if (!$promotion) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Promotion record not found.',
            ]);
        }

        if ($promotion->status === 'rolled_back') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'This promotion has already been rolled back.',
            ]);
        }

        // Begin transaction
        $this->EnrollmentModel->db->transBegin();

        try {
            // Get all successful promotion items
            $successItems = $this->PromotionItemModel
                ->where('promotion_id', $promotionId)
                ->where('status', 'success')
                ->findAll();

            $deletedCount = 0;
            foreach ($successItems as $item) {
                if ($item->to_enrollment_id) {
                    // Delete the new enrollment created during promotion
                    $this->EnrollmentModel->delete($item->to_enrollment_id);
                    $deletedCount++;
                }
            }

            // Update promotion status to rolled_back
            $this->PromotionModel->update($promotionId, [
                'status' => 'rolled_back',
            ]);

            if ($this->EnrollmentModel->db->transStatus() === false) {
                $this->EnrollmentModel->db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database transaction failed.',
                ]);
            }

            $this->EnrollmentModel->db->transCommit();

            return $this->response->setJSON([
                'success' => true,
                'message' => lang('Promotion.msg_rollback_success'),
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            $this->EnrollmentModel->db->transRollback();
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
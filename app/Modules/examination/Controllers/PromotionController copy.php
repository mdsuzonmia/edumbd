<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsYearModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentPromotionModel;
use App\Models\StudentPromotionItemModel;
use App\Models\SchoolModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\FinalResultModel;

class PromotionController extends BaseController
{
    protected ExamModel $ExamModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsYearModel $YearModel;
    protected ExamResultModel $ExamResultModel;
    protected SubjectResultModel $SubjectResultModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected SchoolModel $SchoolModel;
    protected FinalResultModel $FinalResultModel;
    protected StudentPromotionModel $PromotionModel;
    protected StudentPromotionItemModel $PromotionItemModel;

    public function __construct()
    {
        $this->SchoolModel        = new SchoolModel();
        $this->ExamModel          = new ExamModel();
        $this->ClassModel         = new AcademicsClassesModel();
        $this->SectionModel       = new AcademicsSectionModel();
        $this->YearModel          = new AcademicsYearModel();
        $this->ExamResultModel    = new ExamResultModel();
        $this->SubjectResultModel = new SubjectResultModel();
        $this->EnrollmentModel    = new StudentEnrollmentModel();
        $this->FinalResultModel   = new FinalResultModel();
        $this->PromotionModel     = new StudentPromotionModel();
        $this->PromotionItemModel = new StudentPromotionItemModel();
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

    protected function getActiveOptions(int $school_id, string $modelProperty): array
    {
        $list = [];
        $model = $this->{$modelProperty};
        $records = $model
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();
        foreach ($records as $r) {
            $list[$r->id] = $r->title;
        }
        return $list;
    }

    public function index()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $school_id = (int) $this->request->getGet('school_id');
        $from_session_id = (int) $this->request->getGet('from_session_id');
        $to_session_id = (int) $this->request->getGet('to_session_id');
        $from_class_id = (int) $this->request->getGet('from_class_id');
        $to_class_id = (int) $this->request->getGet('to_class_id');
        $result_source = $this->request->getGet('result_source') ?? 'aggregate';

        $data = compact('school_id', 'from_session_id', 'to_session_id', 'from_class_id', 'to_class_id', 'result_source');
        $data['school_list'] = $this->getSchoolDropdown();
        $data['year_list'] = [];
        $data['class_list'] = [];
        $data['section_list'] = [];
        $data['preview_html'] = '';

        if ($school_id) {
            $data['year_list'] = $this->getActiveOptions($school_id, 'YearModel');
            $data['class_list'] = $this->getActiveOptions($school_id, 'ClassModel');
        }

        if ($school_id && $from_class_id) {
            $sections = $this->SectionModel
                ->where('school_id', $school_id)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
            $section_list = [];
            foreach ($sections as $s) {
                $section_list[$s->id] = $s->title;
            }
            $data['section_list'] = $section_list;
        }

        return view('header', ['page_title' => 'Promotion', 'body_class' => 'nav-md', 'admin_area' => 'yes'])
            . view('App\Modules\examination\Views\promotion\index', $data)
            . view('footer', ['admin_area' => 'yes']);
    }

    // ======================================================================
    // HISTORY, DETAILS, ROLLBACK
    // ======================================================================

    /**
     * Promotion history list
     */
    public function history()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $school_id = (int) $this->request->getGet('school_id') ?: 0;

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

        if ($school_id && !in_array($school_id, $schoolIds)) {
            $school_id = 0;
        }

        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();

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

        $perPage = 10;
        $page    = (int) ($this->request->getGet('page') ?? 1);
        $total   = count($allItems);
        $offset  = ($page - 1) * $perPage;
        $items   = array_slice(array_values($allItems), $offset, $perPage);

        $pager = \CodeIgniter\Config\Services::pager();
        $pager->makeLinks($page, $perPage, $total, 'custom_pagination');

        $data = [
            'items'          => $items,
            'pager'          => $pager,
            'pagerTemplate'  => 'custom_pagination',
            'school_list'    => $this->getSchoolDropdown(),
            'selected_school' => $school_id,
        ];

        return view('header', ['page_title' => 'Promotion History', 'body_class' => 'nav-md', 'admin_area' => 'yes'])
            . view('App\Modules\examination\Views\promotion\history', $data)
            . view('footer', ['admin_area' => 'yes']);
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

        $db = \Config\Database::connect();
        $prefix = $db->getPrefix();

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
            return redirect()->to('examination/promotion/history')
                ->with('error', 'Promotion record not found.');
        }

        $sqlItems = "SELECT 
                        pi.*,
                        st.student_code,
                        st.first_name,
                        st.middle_name,
                        st.last_name,
                        st.photo,
                        COALESCE(fe.roll_no, pi.roll_no) AS from_roll_no,
                        COALESCE(te.roll_no, pi.to_roll_no) AS to_roll_no
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

        return view('header', ['page_title' => 'Promotion Details', 'body_class' => 'nav-md', 'admin_area' => 'yes'])
            . view('App\Modules\examination\Views\promotion\details', $data)
            . view('footer', ['admin_area' => 'yes']);
    }

    /**
     * Rollback a promotion
     */
    public function rollback(int $promotionId)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/promotion/history');
        }

        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $promotion = $this->PromotionModel
            ->where('id', $promotionId)
            ->where('school_owner_uid', $user_id)
            ->first();

        if (!$promotion) {
            return $this->response->setJSON(['success' => false, 'message' => 'Promotion record not found.']);
        }

        if ($promotion->status === 'rolled_back') {
            return $this->response->setJSON(['success' => false, 'message' => 'This promotion has already been rolled back.']);
        }

        $this->EnrollmentModel->db->transBegin();

        try {
            $successItems = $this->PromotionItemModel
                ->where('promotion_id', $promotionId)
                ->where('status', 'success')
                ->findAll();

            $deletedCount = 0;
            foreach ($successItems as $item) {
                if ($item->to_enrollment_id) {
                    $this->EnrollmentModel->delete($item->to_enrollment_id);
                    $deletedCount++;
                }
            }

            $this->PromotionModel->update($promotionId, [
                'status' => 'rolled_back',
            ]);

            if ($this->EnrollmentModel->db->transStatus() === false) {
                $this->EnrollmentModel->db->transRollback();
                return $this->response->setJSON(['success' => false, 'message' => 'Database transaction failed.']);
            }

            $this->EnrollmentModel->db->transCommit();

            return $this->response->setJSON([
                'success' => true,
                'message' => "Rollback completed. {$deletedCount} enrollments deleted.",
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            $this->EnrollmentModel->db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function preview()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/promotion');
        }

        log_message('info', '=== PROMOTION PREVIEW STARTED ===');

        try {
            $user_id = $this->getUserId();
            if (!$user_id) {
                return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated']);
            }

            $school_id = (int) $this->request->getPost('school_id');
            $from_session_id = (int) $this->request->getPost('from_session_id');
            $to_session_id = (int) $this->request->getPost('to_session_id');
            $from_class_id = (int) $this->request->getPost('from_class_id');
            $to_class_id = (int) $this->request->getPost('to_class_id');
            $result_source = $this->request->getPost('result_source') ?? 'aggregate';
            $exam_id = (int) $this->request->getPost('exam_id');

            log_message('info', "Parameters: school=$school_id, from_session=$from_session_id, to_session=$to_session_id, from_class=$from_class_id, to_class=$to_class_id, source=$result_source, exam_id=$exam_id");
        } catch (\Exception $e) {
            log_message('error', 'Parameter error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }

        try {
            $userSchools = $this->getUserSchools();
            $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
            if (!in_array($school_id, $schoolIds)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
            }

            if (!$from_session_id || !$from_class_id || !$to_session_id || !$to_class_id) {
                return $this->response->setJSON(['success' => false, 'message' => 'Please select all required fields']);
            }

            // Get all students from the from session/class
            $enrollments = $this->EnrollmentModel
                ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code')
                ->join('students', 'students.id = student_enrollments.student_id', 'left')
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.session_id', $from_session_id)
                ->where('student_enrollments.class_id', $from_class_id)
                ->where('students.status', 1)
                ->orderBy('student_enrollments.roll_no', 'ASC')
                ->findAll();
            log_message('info', 'Found ' . count($enrollments) . ' total enrollments');
        } catch (\Exception $e) {
            log_message('error', 'Database error fetching enrollments: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }

        if (empty($enrollments)) {
            return $this->response->setJSON(['success' => true, 'html' => '<p class="text-danger p-2 text-center">No students found.</p>', 'eligible_count' => 0]);
        }

        try {
            // Check existing enrollments in target
            $studentIds = array_map(function ($e) { return $e->student_id; }, $enrollments);
            $existingEnrollments = [];
            if (!empty($studentIds)) {
                try {
                    $existing = $this->EnrollmentModel
                        ->where('school_id', $school_id)
                        ->where('session_id', $to_session_id)
                        ->where('class_id', $to_class_id)
                        ->whereIn('student_id', $studentIds)
                        ->findAll();
                    foreach ($existing as $e) {
                        $existingEnrollments[$e->student_id] = true;
                    }
                } catch (\Exception $e) {
                    log_message('info', 'Existing enrollment check error: ' . $e->getMessage());
                }
            }

            $fromSession = $this->YearModel->find($from_session_id);
            $toSession = $this->YearModel->find($to_session_id);
            $fromClass = $this->ClassModel->find($from_class_id);
            $toClass = $this->ClassModel->find($to_class_id);

            $eligibleCount = 0;
            $alreadyCount = 0;
            $studentData = [];

            foreach ($enrollments as $enr) {
                try {
                    $studentName = trim(($enr->first_name ?? '') . ' ' . ($enr->middle_name ?? '') . ' ' . ($enr->last_name ?? ''));
                    $studentName = preg_replace('/\s+/', ' ', $studentName);

                    $isAlreadyEnrolled = isset($existingEnrollments[$enr->student_id]);
                    
                    if ($isAlreadyEnrolled) {
                        $alreadyCount++;
                    } else {
                        $eligibleCount++;
                    }

                    // Get result
                    $gpa = 0;
                    $resultStatus = 'Incomplete';
                    $recommendation = 'Hold';
                    $failedSubjects = 0;
                    $finalResult = null;
                    $examResult = null;
                    $srs = null;

                    $grade_letter = '';
                    $grade_name = '';
                    $class_position = 0;
                    $result_source = 'aggregate';

                    if ($isAlreadyEnrolled) {
                        $resultStatus = 'ALREADY ENROLLED';
                        $recommendation = 'Skip';
                    } elseif ($result_source === 'aggregate') {
                        try {
                            $finalResult = $this->FinalResultModel
                                ->where('school_id', $school_id)
                                ->where('session_id', $from_session_id)
                                ->where('class_id', $from_class_id)
                                ->where('student_uid', $enr->student_id)
                                ->first();
                        } catch (\Exception $e) {
                            log_message('info', 'FinalResultModel query error: ' . $e->getMessage());
                        }

                        

                        if ($finalResult) {
                            $gpa = $finalResult->gpa ?? 0;
                            $resultStatus = strtoupper($finalResult->result_status ?? 'FAIL');
                            if ($resultStatus === 'PASS') {
                                $recommendation = 'Promote';
                            } elseif ($resultStatus === 'FAIL') {
                                $recommendation = 'Repeat';
                                try {
                                    $failedSubjects = $this->countFailedSubjects($school_id, $from_session_id, $from_class_id, $enr->student_id);
                                } catch (\Exception $e) {
                                    $failedSubjects = 0;
                                }
                            } else {
                                $recommendation = 'Hold';
                            }
                        } else {
                            try {
                                $srs = $this->SubjectResultModel
                                    ->where('school_id', $school_id)
                                    ->where('session_id', $from_session_id)
                                    ->where('class_id', $from_class_id)
                                    ->where('student_id', $enr->student_id)
                                    ->findAll();
                                if (!empty($srs)) {
                                    $totalGp = 0; $cnt = 0; $hasFail = false;
                                    foreach ($srs as $sr) {
                                        $totalGp += $sr->grade_point ?? 0;
                                        $cnt++;
                                        if ($sr->is_fail == 1) { $hasFail = true; $failedSubjects++; }
                                    }
                                    $gpa = $cnt > 0 ? round($totalGp / $cnt, 2) : 0;
                                    if (!$hasFail) { $resultStatus = 'PASS'; $recommendation = 'Promote'; }
                                    else { $resultStatus = 'FAIL'; $recommendation = 'Repeat'; }
                                }
                            } catch (\Exception $e) {
                                $resultStatus = 'INCOMPLETE';
                                $recommendation = 'Hold';
                            }
                        }
                    } else {
                        try {
                            $examQuery = $this->ExamResultModel
                                ->where('school_id', $school_id)
                                ->where('session_id', $from_session_id)
                                ->where('class_id', $from_class_id)
                                ->where('student_id', $enr->student_id);
                            
                            if ($exam_id > 0) {
                                $examQuery->where('exam_id', $exam_id);
                            }
                            
                            $examResult = $examQuery->first();
                            
                            if ($examResult) {
                                $gpa = $examResult->gpa ?? 0;
                                $resultStatus = strtoupper($examResult->result_status ?? 'FAIL');
                                if ($resultStatus === 'PASS') $recommendation = 'Promote';
                                elseif ($resultStatus === 'FAIL') {
                                    $recommendation = 'Repeat';
                                    try {
                                        $failedSubjects = $this->countFailedSubjects($school_id, $from_session_id, $from_class_id, $enr->student_id, $examResult->exam_id ?? 0);
                                    } catch (\Exception $e) {
                                        $failedSubjects = 0;
                                    }
                                } else $recommendation = 'Hold';
                            } else {
                                // Fallback: compute from subject results
                                try {
                                    $srs = $this->SubjectResultModel
                                        ->where('school_id', $school_id)
                                        ->where('session_id', $from_session_id)
                                        ->where('class_id', $from_class_id)
                                        ->where('student_id', $enr->student_id);
                                    
                                    if ($exam_id > 0) {
                                        $srs->where('exam_id', $exam_id);
                                    }
                                    
                                    $srs = $srs->findAll();
                                    if (!empty($srs)) {
                                        $totalGp = 0; $cnt = 0; $hasFail = false;
                                        foreach ($srs as $sr) {
                                            $totalGp += $sr->grade_point ?? 0;
                                            $cnt++;
                                            if ($sr->is_fail == 1) { $hasFail = true; $failedSubjects++; }
                                        }
                                        $gpa = $cnt > 0 ? round($totalGp / $cnt, 2) : 0;
                                        if (!$hasFail) { $resultStatus = 'PASS'; $recommendation = 'Promote'; }
                                        else { $resultStatus = 'FAIL'; $recommendation = 'Repeat'; }
                                    } else {
                                        // Last resort: try subject results without exam_id filter
                                        $srs = $this->SubjectResultModel
                                            ->where('school_id', $school_id)
                                            ->where('session_id', $from_session_id)
                                            ->where('class_id', $from_class_id)
                                            ->where('student_id', $enr->student_id)
                                            ->findAll();
                                        if (!empty($srs)) {
                                            $totalGp = 0; $cnt = 0; $hasFail = false;
                                            foreach ($srs as $sr) {
                                                $totalGp += $sr->grade_point ?? 0;
                                                $cnt++;
                                                if ($sr->is_fail == 1) { $hasFail = true; $failedSubjects++; }
                                            }
                                            $gpa = $cnt > 0 ? round($totalGp / $cnt, 2) : 0;
                                            if (!$hasFail) { $resultStatus = 'PASS'; $recommendation = 'Promote'; }
                                            else { $resultStatus = 'FAIL'; $recommendation = 'Repeat'; }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $resultStatus = 'INCOMPLETE';
                                    $recommendation = 'Hold';
                                }
                            }
                        } catch (\Exception $e) {
                            $resultStatus = 'INCOMPLETE';
                            $recommendation = 'Hold';
                        }
                    }

                    // Get section title
                    $sectionTitle = 'Same';
                    if ($enr->section_id) {
                        $section = $this->SectionModel->find($enr->section_id);
                        $sectionTitle = $section ? $section->title : 'Same';
                    }
                    
                    // Get grade and position
                    $grade = '-';
                    $position = '-';
                    if ($finalResult) {
                        $grade = $finalResult->grade_letter ?? $finalResult->grade ?? '-';
                        $position = $finalResult->class_position ?? '-';
                    } elseif ($examResult) {
                        $grade = $examResult->letter_grade ?? $examResult->grade ?? '-';
                        $position = $examResult->class_rank ?? '-';
                    } elseif (!empty($srs)) {
                        // Extract grade from subject results (fallback when no final/exam result)
                        $lastGrade = '';
                        foreach ($srs as $sr) {
                            if (!empty($sr->letter_grade)) {
                                $lastGrade = $sr->letter_grade;
                            }
                        }
                        if (!empty($lastGrade)) {
                            $grade = $lastGrade;
                        }
                    }
                    
                    // Auto-generate new roll based on position
                    $newRoll = $enr->roll_no ?? '';
                    if ($position !== '-' && is_numeric($position)) {
                        $newRoll = str_pad($position, 2, '0', STR_PAD_LEFT);
                    }
                    
                    $sortPosition = ($position !== '-' && is_numeric($position)) ? (int)$position : 9999;
                    
                    $studentData[] = [
                        'student_id' => $enr->student_id,
                        'roll_no' => $enr->roll_no ?? '',
                        'name' => $studentName,
                        'grade' => $grade,
                        'position' => $position,
                        'sort_position' => $sortPosition,
                        'gpa' => $gpa,
                        'result_status' => $resultStatus,
                        'recommendation' => $recommendation,
                        'failed_subjects' => $failedSubjects,
                        'to_session_id' => $to_session_id,
                        'to_class_id' => $to_class_id,
                        'to_section_id' => $enr->section_id ?? 0,
                        'to_section_title' => $sectionTitle,
                        'new_roll' => $newRoll,
                    ];
                } catch (\Exception $e) {
                    log_message('error', 'Error processing student ' . ($enr->student_id ?? 'unknown') . ': ' . $e->getMessage());
                    continue;
                }
            }
            
            // Sort students by position
            usort($studentData, function($a, $b) {
                return $a['sort_position'] - $b['sort_position'];
            });
            
            // Recalculate counts
            $eligibleCount = 0;
            $alreadyCount = 0;
            foreach ($studentData as &$student) {
                if ($student['result_status'] === 'ALREADY ENROLLED') {
                    $alreadyCount++;
                } else {
                    $eligibleCount++;
                }
            }
            unset($student);

            // Build HTML
            try {
                $yearList = $this->getActiveOptions($school_id, 'YearModel');
                $classList = $this->getActiveOptions($school_id, 'ClassModel');
                $sectionList = [];
                if ($school_id) {
                    $sections = $this->SectionModel
                        ->where('school_id', $school_id)
                        ->where('status', 1)
                        ->orderBy('title', 'ASC')
                        ->findAll();
                    foreach ($sections as $s) {
                        $sectionList[$s->id] = $s->title;
                    }
                }
                
                $html = view('App\Modules\examination\Views\promotion\preview', [
                    'students' => $studentData,
                    'school_id' => $school_id,
                    'from_session_id' => $from_session_id,
                    'to_session_id' => $to_session_id,
                    'from_class_id' => $from_class_id,
                    'to_class_id' => $to_class_id,
                    'from_session' => $fromSession,
                    'to_session' => $toSession,
                    'from_class' => $fromClass,
                    'to_class' => $toClass,
                    'eligible_count' => $eligibleCount,
                    'already_count' => $alreadyCount,
                    'result_source' => $result_source,
                    'year_list' => $yearList,
                    'class_list' => $classList,
                    'section_list' => $sectionList,
                ]);
            } catch (\Exception $e) {
                return $this->response->setJSON(['success' => false, 'message' => 'View error: ' . $e->getMessage()]);
            }

            return $this->response->setJSON([
                'success' => true,
                'html' => $html,
                'eligible_count' => $eligibleCount,
                'already_count' => $alreadyCount,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error building preview: ' . $e->getMessage()]);
        }
    }

    public function process()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/promotion');
        }

        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        // Check subscription
        $subscription_check = check_subscription('examination/promotion');
        if ($subscription_check) {
            return $this->response->setJSON(['success' => false, 'message' => $subscription_check['message']]);
        }

        $school_id = (int) $this->request->getPost('school_id');
        $from_session_id = (int) $this->request->getPost('from_session_id');
        $from_class_id = (int) $this->request->getPost('from_class_id');
        $student_ids = $this->request->getPost('student_ids');
        $roll_nos = $this->request->getPost('roll_nos');
        $recommendations = $this->request->getPost('recommendations');
        $to_session_ids = $this->request->getPost('to_session_ids');
        $to_class_ids = $this->request->getPost('to_class_ids');
        $to_section_ids = $this->request->getPost('to_section_ids');
        $notes = $this->request->getPost('notes');
        $result_source = $this->request->getPost('result_source') ?? 'aggregate';

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        if (empty($student_ids) || !is_array($student_ids)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No students selected']);
        }

        $this->EnrollmentModel->db->transBegin();

        try {
            $successCount = 0;
            $failedCount = 0;
            $promotionItems = [];

            // Create the promotion batch record first
            $promotionData = [
                'school_id' => $school_id,
                'school_owner_uid' => $user_id,
                'from_session_id' => $from_session_id,
                'to_session_id' => 0,
                'from_class_id' => $from_class_id,
                'to_class_id' => 0,
                'total_students' => count($student_ids),
                'success_count' => 0,
                'failed_count' => 0,
                'status' => 'completed',
                'notes' => $notes ?? '',
                'created_by' => $user_id,
            ];
            $promotionId = $this->PromotionModel->insert($promotionData);

            foreach ($student_ids as $index => $studentId) {
                $studentId = (int) $studentId;
                $recommendation = $recommendations[$index] ?? 'Hold';
                $newRoll = $roll_nos[$index] ?? '';
                $toSessionId = (int) ($to_session_ids[$index] ?? 0);
                $toClassId = (int) ($to_class_ids[$index] ?? 0);
                $toSectionId = (int) ($to_section_ids[$index] ?? 0);

                // Get current enrollment
                $currentEnrollment = $this->EnrollmentModel
                    ->where('student_id', $studentId)
                    ->where('session_id', $from_session_id)
                    ->where('class_id', $from_class_id)
                    ->where('school_id', $school_id)
                    ->first();

                if (!$currentEnrollment) {
                    $failedCount++;
                    $this->PromotionItemModel->insert([
                        'promotion_id' => $promotionId,
                        'student_id' => $studentId,
                        'from_enrollment_id' => 0,
                        'to_enrollment_id' => 0,
                        'roll_no' => '',
                        'to_roll_no' => '',
                        'status' => 'failed',
                        'error_message' => 'Current enrollment not found',
                    ]);
                    continue;
                }

                if ($recommendation === 'Promote') {
                    // Check if already enrolled in target session/class
                    $existing = $this->EnrollmentModel
                        ->where('student_id', $studentId)
                        ->where('session_id', $toSessionId)
                        ->where('class_id', $toClassId)
                        ->where('school_id', $school_id)
                        ->first();

                    if ($existing) {
                        $failedCount++;
                        $this->PromotionItemModel->insert([
                            'promotion_id' => $promotionId,
                            'student_id' => $studentId,
                            'from_enrollment_id' => $currentEnrollment->id,
                            'to_enrollment_id' => 0,
                            'roll_no' => $currentEnrollment->roll_no ?? '',
                            'to_roll_no' => '',
                            'status' => 'failed',
                            'error_message' => 'Already enrolled in target class',
                        ]);
                        continue;
                    }

                    // Create new enrollment
                    $newEnrollmentData = [
                        'school_id' => $school_id,
                        'school_owner_uid' => $user_id,
                        'student_id' => $studentId,
                        'session_id' => $toSessionId,
                        'class_id' => $toClassId,
                        'section_id' => $toSectionId ?: ($currentEnrollment->section_id ?? 0),
                        'roll_no' => $newRoll ?: $currentEnrollment->roll_no,
                        'status' => 1,
                        'created_by' => $user_id,
                    ];

                    $newEnrollmentId = $this->EnrollmentModel->insert($newEnrollmentData);

                    // Save promotion item record
                    $this->PromotionItemModel->insert([
                        'promotion_id' => $promotionId,
                        'student_id' => $studentId,
                        'from_enrollment_id' => $currentEnrollment->id,
                        'to_enrollment_id' => $newEnrollmentId,
                        'roll_no' => $currentEnrollment->roll_no ?? '',
                        'to_roll_no' => $newRoll ?: $currentEnrollment->roll_no ?? '',
                        'status' => 'success',
                        'error_message' => null,
                    ]);

                    // Update final result with next session/class
                    if ($result_source === 'aggregate') {
                        try {
                            $this->FinalResultModel->builder()
                                ->where('school_id', $school_id)
                                ->where('session_id', $from_session_id)
                                ->where('class_id', $from_class_id)
                                ->where('student_uid', $studentId)
                                ->set('next_session_id', $toSessionId)
                                ->set('next_class_id', $toClassId)
                                ->update();
                        } catch (\Exception $e) {
                            log_message('error', 'FinalResultModel update error: ' . $e->getMessage());
                        }
                    }

                    $successCount++;
                } elseif ($recommendation === 'Repeat') {
                    $this->EnrollmentModel->update($currentEnrollment->id, ['status' => 1]);
                    $successCount++;
                    $this->PromotionItemModel->insert([
                        'promotion_id' => $promotionId,
                        'student_id' => $studentId,
                        'from_enrollment_id' => $currentEnrollment->id,
                        'to_enrollment_id' => 0,
                        'roll_no' => $currentEnrollment->roll_no ?? '',
                        'to_roll_no' => '',
                        'status' => 'success',
                        'error_message' => 'Repeated same class',
                    ]);
                } else {
                    // Hold: save as skipped
                    $this->PromotionItemModel->insert([
                        'promotion_id' => $promotionId,
                        'student_id' => $studentId,
                        'from_enrollment_id' => $currentEnrollment->id,
                        'to_enrollment_id' => 0,
                        'roll_no' => $currentEnrollment->roll_no ?? '',
                        'to_roll_no' => '',
                        'status' => 'skipped',
                        'error_message' => 'Held back',
                    ]);
                }
            }

            // Update promotion batch with counts
            $this->PromotionModel->update($promotionId, [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ]);

            if ($this->EnrollmentModel->db->transStatus() === false) {
                $this->EnrollmentModel->db->transRollback();
                return $this->response->setJSON(['success' => false, 'message' => 'Transaction failed']);
            }

            $this->EnrollmentModel->db->transCommit();

            return $this->response->setJSON([
                'success' => true,
                'message' => "Promotion completed. Success: $successCount, Failed: $failedCount",
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ]);

        } catch (\Exception $e) {
            $this->EnrollmentModel->db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    protected function countFailedSubjects(int $school_id, int $session_id, int $class_id, int $student_id, int $exam_id = 0): int
    {
        try {
            $query = $this->SubjectResultModel
                ->where('school_id', $school_id)
                ->where('session_id', $session_id)
                ->where('class_id', $class_id)
                ->where('student_id', $student_id)
                ->where('is_fail', 1);

            if ($exam_id) {
                $query->where('exam_id', $exam_id);
            }

            return $query->countAllResults();
        } catch (\Exception $e) {
            log_message('info', 'countFailedSubjects error: ' . $e->getMessage());
            return 0;
        }
    }

    // ======================================================================
    // AJAX ENDPOINTS
    // ======================================================================

    public function ajaxGetYears()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/promotion');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $years = $school_id ? $this->getActiveOptions($school_id, 'YearModel') : [];
        return $this->response->setJSON(['success' => true, 'data' => $years]);
    }

    public function ajaxGetClasses()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/promotion');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $classes = $school_id ? $this->getActiveOptions($school_id, 'ClassModel') : [];
        return $this->response->setJSON(['success' => true, 'data' => $classes]);
    }

    public function ajaxGetSections()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/promotion');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $sections = [];
        if ($school_id) {
            $records = $this->SectionModel
                ->where('school_id', $school_id)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
            foreach ($records as $s) {
                $sections[$s->id] = $s->title;
            }
        }
        return $this->response->setJSON(['success' => true, 'data' => $sections]);
    }

    public function ajaxGetExams()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/promotion');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $session_id = (int) $this->request->getPost('session_id');
        $class_id = (int) $this->request->getPost('class_id');
        
        $exams = [];
        if ($school_id && $session_id && $class_id) {
            $records = $this->ExamModel
                ->where('school_id', $school_id)
                ->where('year_id', $session_id)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
            foreach ($records as $e) {
                $exams[$e->id] = $e->title;
            }
        }
        return $this->response->setJSON(['success' => true, 'data' => $exams]);
    }
}
<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\GradeRuleModel;

class ExamRemarkController extends BaseController
{
    protected ExamModel $ExamModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsYearModel $YearModel;
    protected ExamResultModel $ExamResultModel;
    protected SubjectResultModel $SubjectResultModel;
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected SubjectModel $SubjectModel;
    protected GradeRuleModel $GradeRuleModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        
        $this->SchoolModel          = new SchoolModel();
        $this->ExamModel            = new ExamModel();
        $this->ClassModel           = new AcademicsClassesModel();
        $this->SectionModel         = new AcademicsSectionModel();
        $this->YearModel            = new AcademicsYearModel();
        $this->ExamResultModel      = new ExamResultModel();
        $this->SubjectResultModel   = new SubjectResultModel();
        $this->StudentModel         = new StudentModel();
        $this->EnrollmentModel      = new StudentEnrollmentModel();
        $this->SubjectModel         = new SubjectModel();
        $this->GradeRuleModel       = new GradeRuleModel();
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
            ->select('schools.id, schools.name')
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

    /**
     * Get active options (dropdown data) for a school.
     */
    protected function getActiveOptions(int $school_id, string $modelProperty): array
    {
        $list = [];
        $model = $this->{$modelProperty};
        
        $orderColumn = 'title';
        $valueColumn = 'title';
        
        $records = $model
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy($orderColumn, 'ASC')
            ->findAll();

        foreach ($records as $r) {
            $list[$r->id] = $r->$valueColumn;
        }
        return $list;
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    /**
     * Principal Remark Page
     * Display tabulation sheet with filters and textarea for principal remarks
     */
    public function index()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Principal Remarks',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $school_id   = (int) $this->request->getGet('school_id');
        $exam_id     = (int) $this->request->getGet('exam_id');
        $class_id    = (int) $this->request->getGet('class_id');
        $section_id  = (int) $this->request->getGet('section_id');
        $year_id     = (int) $this->request->getGet('year_id');

        $data = compact('school_id', 'exam_id', 'class_id', 'section_id', 'year_id');
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;
        $data['year_list']       = $this->getActiveOptions($school_id ?: 0, 'YearModel');

        if ($school_id) {
            $data['exam_list']    = $this->getActiveOptions($school_id, 'ExamModel');
            $data['class_list']   = $this->getActiveOptions($school_id, 'ClassModel');
            $data['section_list'] = $this->getActiveOptions($school_id, 'SectionModel');
        } else {
            $data['exam_list']    = [];
            $data['class_list']   = [];
            $data['section_list'] = [];
        }

        // If filters are set, get tabulation data
        if ($school_id && $exam_id && $class_id) {
            $this->ExamResultModel
                ->select('examination_results.*, 
                    students.first_name, students.middle_name, students.last_name, students.student_code,
                    student_enrollments.roll_no,
                    academic_classes.title AS class_title,
                    academic_sections.title AS section_title,
                    examination_exams.title AS exam_title')
                ->join('students', 'students.id = examination_results.student_id', 'left')
                ->join('student_enrollments', 'student_enrollments.id = examination_results.enrollment_id', 'left')
                ->join('academic_classes', 'academic_classes.id = examination_results.class_id', 'left')
                ->join('academic_sections', 'academic_sections.id = examination_results.section_id', 'left')
                ->join('examination_exams', 'examination_exams.id = examination_results.exam_id', 'left')
                ->where('examination_results.school_id', $school_id)
                ->where('examination_results.exam_id', $exam_id)
                ->where('examination_results.class_id', $class_id);

            if ($section_id) {
                $this->ExamResultModel->where('examination_results.section_id', $section_id);
            }

            if ($year_id) {
                $this->ExamResultModel->where('examination_results.session_id', $year_id);
            }

            // Order by class rank/position
            $this->ExamResultModel->orderBy('examination_results.class_rank', 'ASC');
            $results = $this->ExamResultModel->findAll();
            
            // Get subject marks for each student
            $data['results'] = $results;
            $data['subject_marks'] = [];
            $data['grade_remarks'] = [];
            $data['teacher_remarks'] = [];
            
            if (!empty($results)) {
                $studentIds = array_map(function($r) { return $r->student_id; }, $results);
                
                // Get all subjects for this class/school
                $subjects = $this->SubjectModel
                    ->where('school_id', $school_id)
                    ->where('status', 1)
                    ->orderBy('title', 'ASC')
                    ->findAll();
                
                $data['subjects'] = $subjects;
                
                // Get marks for all students
                if (!empty($studentIds)) {
                    $this->SubjectResultModel
                        ->select('examination_subject_results.*, examination_subjects.title as subject_title')
                        ->join('examination_subjects', 'examination_subjects.id = examination_subject_results.subject_id', 'left')
                        ->where('examination_subject_results.school_id', $school_id)
                        ->where('examination_subject_results.exam_id', $exam_id)
                        ->where('examination_subject_results.class_id', $class_id)
                        ->whereIn('examination_subject_results.student_id', $studentIds);
                    
                    if ($year_id) {
                        $this->SubjectResultModel->where('examination_subject_results.session_id', $year_id);
                    }
                    
                    $subjectResults = $this->SubjectResultModel->findAll();
                    
                    // Organize by student_id
                    foreach ($subjectResults as $sr) {
                        $studentId = $sr->student_id;
                        if (!isset($data['subject_marks'][$studentId])) {
                            $data['subject_marks'][$studentId] = [];
                        }
                        $data['subject_marks'][$studentId][$sr->subject_id] = $sr;
                    }
                }
                
                // Get grade remarks for each student based on their overall grade
                $gradeSystemIds = [];
                if (!empty($subjects)) {
                    foreach ($subjects as $subject) {
                        if ($subject->grade_system_id && !in_array($subject->grade_system_id, $gradeSystemIds)) {
                            $gradeSystemIds[] = $subject->grade_system_id;
                        }
                    }
                }
                
                if (!empty($gradeSystemIds)) {
                    $gradeRules = $this->GradeRuleModel
                        ->whereIn('grade_system_id', $gradeSystemIds)
                        ->where('status', 1)
                        ->orderBy('mark_from', 'DESC')
                        ->findAll();
                    
                    // Organize grade rules by grade_system_id and grade title
                    $gradeRulesBySystem = [];
                    foreach ($gradeRules as $rule) {
                        $gsId = $rule->grade_system_id;
                        if (!isset($gradeRulesBySystem[$gsId])) {
                            $gradeRulesBySystem[$gsId] = [];
                        }
                        $gradeRulesBySystem[$gsId][] = $rule;
                    }
                    
                    // Match grade remarks for each student
                    foreach ($results as $result) {
                        $studentId = $result->student_id;
                        $grade = $result->letter_grade ?? $result->grade ?? '';
                        
                        if (empty($grade)) {
                            $data['grade_remarks'][$studentId] = '';
                            continue;
                        }
                        
                        // Find the grade system for this student's subjects
                        $remarks = '';
                        foreach ($gradeSystemIds as $gsId) {
                            if (isset($gradeRulesBySystem[$gsId])) {
                                foreach ($gradeRulesBySystem[$gsId] as $rule) {
                                    if ($rule->title === $grade && !empty($rule->remarks)) {
                                        $remarks = $rule->remarks;
                                        break 2;
                                    }
                                }
                            }
                        }
                        
                        $data['grade_remarks'][$studentId] = $remarks;
                    }
                }
            } else {
                $data['subjects'] = [];
                $data['subject_marks'] = [];
                $data['grade_remarks'] = [];
            }
        } else {
            $data['results'] = [];
            $data['subjects'] = [];
            $data['subject_marks'] = [];
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\principal_remark', $data)
            . view('footer', $footer_data);
    }

    // ======================================================================
    // AJAX: Get principal remark results
    // ======================================================================
    public function ajaxGetResults()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Invalid request']);
        }

        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated']);
        }

        $school_id = (int) $this->request->getPost('school_id');
        $year_id   = (int) $this->request->getPost('year_id');
        $class_id  = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');
        $exam_id   = (int) $this->request->getPost('exam_id');

        if (!$school_id || !$exam_id || !$class_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields']);
        }

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return $this->jsonResponse(['status' => false, 'message' => 'Access denied']);
        }

        // Get exam results
        $results = $this->ExamResultModel
            ->select('examination_results.*, 
                students.first_name, students.middle_name, students.last_name, students.student_code,
                student_enrollments.roll_no,
                academic_classes.title AS class_title,
                academic_sections.title AS section_title,
                examination_exams.title AS exam_title')
            ->join('students', 'students.id = examination_results.student_id', 'left')
            ->join('student_enrollments', 'student_enrollments.id = examination_results.enrollment_id', 'left')
            ->join('academic_classes', 'academic_classes.id = examination_results.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = examination_results.section_id', 'left')
            ->join('examination_exams', 'examination_exams.id = examination_results.exam_id', 'left')
            ->where('examination_results.school_id', $school_id)
            ->where('examination_results.exam_id', $exam_id)
            ->where('examination_results.class_id', $class_id);

        if ($section_id) {
            $results->where('examination_results.section_id', $section_id);
        }
        if ($year_id) {
            $results->where('examination_results.session_id', $year_id);
        }

        $results = $results->orderBy('examination_results.class_rank', 'ASC')->findAll();

        // Get subjects
        $subjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        // Get subject marks
        $subject_marks = [];
        $grade_remarks = [];
        if (!empty($results)) {
            $studentIds = array_map(function($r) { return $r->student_id; }, $results);
            
            if (!empty($studentIds)) {
                $subjectResults = $this->SubjectResultModel
                    ->select('examination_subject_results.*, examination_subjects.title as subject_title')
                    ->join('examination_subjects', 'examination_subjects.id = examination_subject_results.subject_id', 'left')
                    ->where('examination_subject_results.school_id', $school_id)
                    ->where('examination_subject_results.exam_id', $exam_id)
                    ->whereIn('examination_subject_results.student_id', $studentIds)
                    ->findAll();
                
                foreach ($subjectResults as $sr) {
                    $studentId = $sr->student_id;
                    if (!isset($subject_marks[$studentId])) {
                        $subject_marks[$studentId] = [];
                    }
                    $subject_marks[$studentId][$sr->subject_id] = $sr;
                }
            }

            // Get grade remarks
            $gradeSystemIds = [];
            if (!empty($subjects)) {
                foreach ($subjects as $subject) {
                    if ($subject->grade_system_id && !in_array($subject->grade_system_id, $gradeSystemIds)) {
                        $gradeSystemIds[] = $subject->grade_system_id;
                    }
                }
            }

            if (!empty($gradeSystemIds)) {
                $gradeRules = $this->GradeRuleModel
                    ->whereIn('grade_system_id', $gradeSystemIds)
                    ->where('status', 1)
                    ->orderBy('mark_from', 'DESC')
                    ->findAll();
                
                $gradeRulesBySystem = [];
                foreach ($gradeRules as $rule) {
                    $gsId = $rule->grade_system_id;
                    if (!isset($gradeRulesBySystem[$gsId])) {
                        $gradeRulesBySystem[$gsId] = [];
                    }
                    $gradeRulesBySystem[$gsId][] = $rule;
                }
                
                foreach ($results as $result) {
                    $studentId = $result->student_id;
                    $grade = $result->letter_grade ?? $result->grade ?? '';
                    
                    if (empty($grade)) {
                        $grade_remarks[$studentId] = '';
                        continue;
                    }
                    
                    $remarks = '';
                    foreach ($gradeSystemIds as $gsId) {
                        if (isset($gradeRulesBySystem[$gsId])) {
                            foreach ($gradeRulesBySystem[$gsId] as $rule) {
                                if ($rule->title === $grade && !empty($rule->remarks)) {
                                    $remarks = $rule->remarks;
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    $grade_remarks[$studentId] = $remarks;
                }
            }
        }

        // Build HTML
        $html = '';
        if (empty($results)) {
            $html = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> No results found. Please generate exam results first.</div>';
        } else {
            $html = view('App\Modules\examination\Views\reports\principal_remark_table', [
                'results' => $results,
                'subjects' => $subjects,
                'subject_marks' => $subject_marks,
                'grade_remarks' => $grade_remarks,
            ]);
        }

        return $this->jsonResponse([
            'status' => true,
            'html' => $html,
            'count' => count($results),
        ]);
    }

    /**
     * Store Principal Remark via AJAX
     */
    public function store()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Unauthorized access.'
            ]);
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks');
        if ($subscription_check) {
            return $this->jsonResponse(['status' => false, 'message' => $subscription_check['message']]);
        }

        $result_id = (int) $this->request->getPost('result_id');
        $remark_type = $this->request->getPost('remark_type');
        $principal_remarks = $this->request->getPost('principal_remarks');
        $teacher_remarks = $this->request->getPost('teacher_remarks');
        $working_days = $this->request->getPost('working_days');
        $present_days = $this->request->getPost('present_days');
        $absent_days = $this->request->getPost('absent_days');

        if (!$result_id) {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Invalid result ID.'
            ]);
        }

        // Verify the result belongs to user's school
        $result = $this->ExamResultModel
            ->select('examination_results.*, schools.id AS school_id')
            ->join('schools', 'schools.id = examination_results.school_id', 'left')
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('examination_results.id', $result_id)
            ->where('school_user_relation.user_id', $user_id)
            ->first();

        if (!$result) {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Result not found or unauthorized access.'
            ]);
        }

        // Build update data based on remark type
        $updateData = [];
        
        // Handle remarks
        if ($remark_type === 'principal') {
            $updateData['principal_remarks'] = $principal_remarks;
        } elseif ($remark_type === 'teacher') {
            $updateData['teacher_remarks'] = $teacher_remarks;
        } elseif ($remark_type === 'attendance') {
            // Only attendance update
        } else {
            // If no type specified, update both
            $updateData['principal_remarks'] = $principal_remarks;
            $updateData['teacher_remarks'] = $teacher_remarks;
        }
        
        // Handle attendance fields if provided
        // Only update if at least one field has a value
        if (($working_days !== null && $working_days !== '') || 
            ($present_days !== null && $present_days !== '') || 
            ($absent_days !== null && $absent_days !== '')) {
            
            $updateData['working_days'] = $working_days !== null && $working_days !== '' ? (int) $working_days : 0;
            $updateData['present_days'] = $present_days !== null && $present_days !== '' ? (int) $present_days : 0;
            $updateData['absent_days'] = $absent_days !== null && $absent_days !== '' ? (int) $absent_days : 0;
            
            // Calculate attendance percentage
            if ($updateData['working_days'] > 0) {
                $attendancePercentage = ($updateData['present_days'] / $updateData['working_days']) * 100;
                $updateData['attendance_percentage'] = round($attendancePercentage, 2);
            } else {
                $updateData['attendance_percentage'] = 0;
            }
        }

        $updated = $this->ExamResultModel->update($result_id, $updateData);

        if ($updated) {
            $message = 'Data saved successfully.';
            if ($remark_type === 'principal') {
                $message = 'Principal remark saved.';
            } elseif ($remark_type === 'teacher') {
                $message = 'Teacher remark saved.';
            } elseif ($remark_type === 'attendance') {
                $message = 'Attendance saved.';
            }
            
            return $this->jsonResponse([
                'status' => true,
                'message' => $message,
                'attendance_percentage' => $updateData['attendance_percentage'] ?? 0
            ]);
        } else {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Failed to save data.'
            ]);
        }
    }
}
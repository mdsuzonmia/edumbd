<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Modules\examination\Models\ExamModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsYearModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\ResultTemplateModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\AcademicsShiftModel;

class ReportsController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected ExamModel $ExamModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsYearModel $YearModel;
    protected ExamResultModel $ExamResultModel;
    protected SubjectResultModel $SubjectResultModel;
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected ResultTemplateModel $TemplateModel;
    protected SubjectModel $SubjectModel;
    protected MarkDistributionModel $MarkDistributionModel;
    protected AcademicsShiftModel $ShiftModel;

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
        $this->TemplateModel        = new ResultTemplateModel();
        $this->SubjectModel         = new SubjectModel();
        $this->MarkDistributionModel = new MarkDistributionModel();
        $this->ShiftModel           = new AcademicsShiftModel();
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

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function individualResult()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Individual Result',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\individual_result', $data)
            . view('footer', $footer_data);
    }

    public function aggregateResult()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Aggregate Result',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\aggregate_result', $data)
            . view('footer', $footer_data);
    }

    public function transcript()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Transcript',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\transcript', $data)
            . view('footer', $footer_data);
    }

    public function tabulationSheet()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Tabulation Sheet',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\tabulation_sheet', $data)
            . view('footer', $footer_data);
    }

    public function meritList()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Merit List',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\merit_list', $data)
            . view('footer', $footer_data);
    }

    public function subjectAnalysis()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Subject Analysis',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\subject_analysis', $data)
            . view('footer', $footer_data);
    }

    public function classStatistics()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Class Statistics',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\class_statistics', $data)
            . view('footer', $footer_data);
    }

    public function gpaAnalysis()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'GPA Analysis',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\gpa_analysis', $data)
            . view('footer', $footer_data);
    }

    public function passFailReport()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Pass/Fail Report',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\pass_fail_report', $data)
            . view('footer', $footer_data);
    }

    // ===================================================================
    // AJAX: Get academic data (years, classes, exams) by school
    // ===================================================================
    public function getAcademicDataBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/individual-result');
        }

        $school_id = (int) $this->request->getPost('school_id');
        if (!$school_id) {
            return $this->jsonResponse(['status' => false]);
        }

        $yearList = [];
        $years = $this->YearModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'DESC')
            ->findAll();
        foreach ($years as $y) {
            $yearList[$y->id] = $y->title;
        }

        $classList = [];
        $classes = $this->ClassModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();
        foreach ($classes as $c) {
            $classList[$c->id] = $c->title;
        }

        $examList = [];
        $exams = $this->ExamModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('exam_date', 'DESC')
            ->findAll();
        foreach ($exams as $e) {
            $examList[$e->id] = $e->title;
        }

        return $this->jsonResponse([
            'status'     => true,
            'year_list'  => $yearList,
            'class_list' => $classList,
            'exam_list'  => $examList,
        ]);
    }

    // ===================================================================
    // AJAX: Get individual result for a student by roll number
    // ===================================================================
    public function getIndividualResult()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/individual-result');
        }

        $school_id = (int) $this->request->getPost('school_id');
        $year_id   = (int) $this->request->getPost('year_id');
        $exam_id   = (int) $this->request->getPost('exam_id');
        $roll_no   = $this->request->getPost('roll_no');

        if (!$school_id || !$year_id || !$exam_id || !$roll_no) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required parameters.']);
        }

        // Find student by roll number in this school/year
        $enrollment = $this->EnrollmentModel
            ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code, students.registration_no, students.photo')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.roll_no', $roll_no)
            ->where('students.status', 1)
            ->first();

        if (!$enrollment) {
            return $this->jsonResponse(['status' => false, 'message' => 'No student found with this roll number.']);
        }

        // Get exam result
        $examResult = $this->ExamResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $enrollment->class_id)
            ->where('student_id', $enrollment->student_id)
            ->first();

        if (!$examResult) {
            return $this->jsonResponse(['status' => false, 'message' => 'Result not found for this student. Please generate results first.']);
        }

        // Get subject results
        $subjectResults = $this->SubjectResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $enrollment->class_id)
            ->where('student_id', $enrollment->student_id)
            ->findAll();

        // Get subjects
        $subjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->findAll();
        $subjectMap = [];
        foreach ($subjects as $s) {
            $subjectMap[$s->id] = $s;
        }

        // Get mark distributions to identify MCQ, CQ, PRA
        $distributions = $this->MarkDistributionModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        // Get class/section info
        $classInfo = $this->ClassModel->find($enrollment->class_id);
        $sectionInfo = $enrollment->section_id ? $this->SectionModel->find($enrollment->section_id) : null;
        $shiftInfo = $enrollment->shift_id ? $this->ShiftModel->find($enrollment->shift_id) : null;
        $yearInfo = $this->YearModel->find($year_id);
        $schoolInfo = $this->SchoolModel->find($school_id);

        // Get template by school_id (only templates for single exam - support_multiple_exams=0)
        $template = $this->TemplateModel
            ->where('school_id', $school_id)
            ->where('school_owner_uid', $this->getUserId())
            ->where('support_multiple_exams', 0)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        // Build student name
        $studentName = trim($enrollment->first_name . ' ' . ($enrollment->middle_name ?? '') . ' ' . $enrollment->last_name);

        // Build subject marks data with distribution breakdown
        $subjectRows = [];
        $totalObtained = 0;
        $totalFull = 0;
        $totalSubjects = count($subjectResults);
        $passedSubjects = 0;
        $failedSubjects = 0;

        foreach ($subjectResults as $sr) {
            $subject = $subjectMap[$sr->subject_id] ?? null;
            $subjectName = $subject ? $subject->title : 'Subject #' . $sr->subject_id;

            // Get marks breakdown by distribution from examination_marks table
            $marksBreakdown = [];
            $marksData = $this->getMarksBreakdown($school_id, $exam_id, $enrollment->class_id, $sr->subject_id, $enrollment->student_id);
            
            $mcq = $marksData['mcq'] ?? '-';
            $cq  = $marksData['cq'] ?? '-';
            $pra = $marksData['pra'] ?? '-';

            $obtained = (float) $sr->obtained_mark;
            $fullMark = (float) $sr->full_mark;
            $highest  = (float) $sr->highest_mark;
            $gpa      = (float) $sr->grade_point;
            $grade    = $sr->grade ?? '';
            $isFail   = (int) $sr->is_fail;

            $totalObtained += $obtained;
            $totalFull += $fullMark;
            if ($isFail) {
                $failedSubjects++;
            } else {
                $passedSubjects++;
            }

            $subjectRows[] = [
                'name'     => $subjectName,
                'mcq'      => $mcq,
                'cq'       => $cq,
                'pra'      => $pra,
                'total'    => $obtained,
                'full'     => $fullMark,
                'highest'  => $highest,
                'gpa'      => number_format($gpa, 2),
                'grade'    => $grade,
                'is_fail'  => $isFail,
            ];
        }

        // Calculate summary
        $percentage = $totalFull > 0 ? round(($totalObtained / $totalFull) * 100, 2) : 0;
        $overallGpa = (float) $examResult->gpa;
        $overallGrade = $examResult->grade ?? '';
        $resultStatus = $examResult->result_status ?? 'PASS';
        $classRank = (int) ($examResult->class_rank ?? 0);
        $sectionRank = (int) ($examResult->section_rank ?? 0);

        // Build HTML using template if available, otherwise use default format
        if ($template && !empty($template->template_content)) {
            $html = $this->renderWithTemplate($template->template_content, [
                'school_name'       => $schoolInfo->name ?? '',
                'school_address'    => $schoolInfo->address ?? '',
                'school_phone'      => $schoolInfo->phone ?? '',
                'school_email'      => $schoolInfo->email ?? '',
                'school_website'    => $schoolInfo->website ?? '',
                'student_name'      => $studentName,
                'student_code'      => $enrollment->student_code ?? '',
                'roll_no'           => $enrollment->roll_no ?? '',
                'registration_no'   => $enrollment->registration_no ?? '',
                'class_name'        => $classInfo->title ?? '',
                'section_name'      => $sectionInfo->title ?? '',
                'shift_name'        => $shiftInfo->title ?? '',
                'session_name'      => $yearInfo->title ?? '',
                'exam_name'         => '',
                'total_subjects'    => $totalSubjects,
                'passed_subjects'   => $passedSubjects,
                'failed_subjects'   => $failedSubjects,
                'total_marks'       => $totalFull,
                'obtained_marks'    => $totalObtained,
                'percentage'        => number_format($percentage, 2),
                'gpa'               => number_format($overallGpa, 2),
                'grade'             => $overallGrade,
                'result_status'     => $resultStatus,
                'class_rank'        => $classRank,
                'section_rank'      => $sectionRank,
                'attendance'        => $examResult->attendance_percentage ?? '',
                'principal_remarks' => $examResult->principal_remarks ?? '',
                'teacher_remarks'   => $examResult->teacher_remarks ?? '',
                'current_date'      => date('d-m-Y'),
                'subject_table'     => $this->buildSubjectTableHtml($subjectRows),
            ]);
        } else {
            // Default formatted HTML result
            $html = $this->buildDefaultResultHtml(
                $schoolInfo, $studentName, $enrollment, $classInfo, $sectionInfo, $shiftInfo, $yearInfo,
                $subjectRows, $totalObtained, $totalFull, $percentage, $overallGpa, $overallGrade,
                $resultStatus, $classRank, $sectionRank, $examResult
            );
        }

        return $this->jsonResponse([
            'status' => true,
            'html'   => $html,
        ]);
    }

    // ===================================================================
    // Helper: Get marks breakdown by distribution (MCQ, CQ, PRA)
    // ===================================================================
    private function getMarksBreakdown(int $school_id, int $exam_id, int $class_id, int $subject_id, int $student_id): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('examination_marks');
        $builder->select('examination_marks.*, examination_mark_distributions.name as dist_name, examination_mark_distributions.code as dist_code');
        $builder->join('examination_mark_distributions', 'examination_mark_distributions.id = examination_marks.distribution_id', 'left');
        $builder->where('examination_marks.school_id', $school_id);
        $builder->where('examination_marks.exam_id', $exam_id);
        $builder->where('examination_marks.class_id', $class_id);
        $builder->where('examination_marks.subject_id', $subject_id);
        $builder->where('examination_marks.student_id', $student_id);
        $query = $builder->get();
        $records = $query->getResult();

        $result = ['mcq' => '-', 'cq' => '-', 'pra' => '-'];

        foreach ($records as $r) {
            $code = strtolower($r->dist_code ?? '');
            $obtained = $r->obtained_mark ?? 0;
            if ($code === 'mcq' || strpos($code, 'mcq') !== false) {
                $result['mcq'] = (int) $obtained;
            } elseif ($code === 'cq' || strpos($code, 'cq') !== false) {
                $result['cq'] = (int) $obtained;
            } elseif ($code === 'pra' || strpos($code, 'pra') !== false || strpos($code, 'practical') !== false) {
                $result['pra'] = (int) $obtained;
            } else {
                // If no code match, put in CQ as default written
                if ($result['cq'] === '-') {
                    $result['cq'] = (int) $obtained;
                } else {
                    $result['cq'] = (int) $result['cq'] + (int) $obtained;
                }
            }
        }

        return $result;
    }

    // ===================================================================
    // Helper: Build subject table HTML
    // ===================================================================
    private function buildSubjectTableHtml(array $subjectRows): string
    {
        $html = '<table class="marks-table" style="width:100%;border-collapse:collapse;margin-bottom:20px;">';
        $html .= '<thead><tr>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:left;font-weight:600;">Subject</th>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:center;font-weight:600;">MCQ</th>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:center;font-weight:600;">CQ</th>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:center;font-weight:600;">PRA</th>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:center;font-weight:600;">Total</th>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:center;font-weight:600;">Highest</th>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:center;font-weight:600;">GPA</th>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:center;font-weight:600;">Grade</th>';
        $html .= '<th style="background:#1a73e8;color:#fff;padding:10px 8px;text-align:center;font-weight:600;">Result</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($subjectRows as $row) {
            $gradeClass = '';
            $grade = $row['grade'];
            if (strpos($grade, 'A+') !== false || strpos($grade, 'A') !== false) $gradeClass = 'grade-aplus';
            elseif (strpos($grade, 'B') !== false) $gradeClass = 'grade-b';
            elseif (strpos($grade, 'C') !== false) $gradeClass = 'grade-c';
            elseif (strpos($grade, 'D') !== false) $gradeClass = 'grade-d';
            elseif (strpos($grade, 'F') !== false) $gradeClass = 'grade-f';

            $resultText = $row['is_fail'] ? 'Fail' : 'Pass';
            $resultClass = $row['is_fail'] ? 'result-fail' : 'result-pass';

            $html .= '<tr style="border-bottom:1px solid #e0e0e0;">';
            $html .= '<td style="padding:8px;text-align:left;font-weight:500;">' . esc($row['name']) . '</td>';
            $html .= '<td style="padding:8px;text-align:center;">' . $row['mcq'] . '</td>';
            $html .= '<td style="padding:8px;text-align:center;">' . $row['cq'] . '</td>';
            $html .= '<td style="padding:8px;text-align:center;">' . $row['pra'] . '</td>';
            $html .= '<td style="padding:8px;text-align:center;font-weight:600;">' . (int) $row['total'] . '</td>';
            $html .= '<td style="padding:8px;text-align:center;">' . (int) $row['highest'] . '</td>';
            $html .= '<td style="padding:8px;text-align:center;">' . $row['gpa'] . '</td>';
            $html .= '<td style="padding:8px;text-align:center;font-weight:700;" class="' . $gradeClass . '">' . esc($grade) . '</td>';
            $html .= '<td style="padding:8px;text-align:center;font-weight:700;" class="' . $resultClass . '">' . $resultText . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    // ===================================================================
    // Helper: Build default result HTML (when no template is set)
    // ===================================================================
    private function buildDefaultResultHtml(
        $schoolInfo, string $studentName, $enrollment, $classInfo, $sectionInfo, $shiftInfo, $yearInfo,
        array $subjectRows, float $totalObtained, float $totalFull, float $percentage,
        float $overallGpa, string $overallGrade, string $resultStatus,
        int $classRank, int $sectionRank, $examResult
    ): string {
        $resultClass = strtoupper($resultStatus) === 'PASS' ? 'result-pass' : 'result-fail';
        $gradeClass = '';
        if (strpos($overallGrade, 'A+') !== false || strpos($overallGrade, 'A') !== false) $gradeClass = 'grade-aplus';
        elseif (strpos($overallGrade, 'B') !== false) $gradeClass = 'grade-b';
        elseif (strpos($overallGrade, 'C') !== false) $gradeClass = 'grade-c';
        elseif (strpos($overallGrade, 'D') !== false) $gradeClass = 'grade-d';
        elseif (strpos($overallGrade, 'F') !== false) $gradeClass = 'grade-f';

        $attendance = $examResult->attendance_percentage ?? '';
        $workingDays = '';
        if ($attendance) {
            $parts = explode('/', $attendance);
            $workingDays = count($parts) > 1 ? trim($parts[1]) : '';
        }

        $html = '<div class="result-card">';

        // School Header
        $html .= '<div class="school-header">';
        $html .= '<h2>' . esc($schoolInfo->name ?? '') . '</h2>';
        $html .= '<p>' . esc($schoolInfo->address ?? '') . '</p>';
        $html .= '<p>Phone: ' . esc($schoolInfo->phone ?? '') . ' | Email: ' . esc($schoolInfo->email ?? '') . '</p>';
        $html .= '</div>';

        // Result Title
        $html .= '<div class="result-title">';
        $html .= '<h4>Individual Result Card</h4>';
        $html .= '</div>';

        // Student Information
        $html .= '<table class="info-table">';
        $html .= '<tr><td class="label">Student Name</td><td>: ' . esc($studentName) . '</td></tr>';
        $html .= '<tr><td class="label">Student ID</td><td>: ' . esc($enrollment->student_code ?? '') . '</td></tr>';
        $html .= '<tr><td class="label">Roll</td><td>: ' . esc($enrollment->roll_no ?? '') . '</td></tr>';
        $html .= '<tr><td class="label">Registration No</td><td>: ' . esc($enrollment->registration_no ?? '') . '</td></tr>';
        $html .= '<tr><td class="label">Session</td><td>: ' . esc($yearInfo->title ?? '') . '</td></tr>';
        $html .= '<tr><td class="label">Class</td><td>: ' . esc($classInfo->title ?? '') . '</td></tr>';
        if ($sectionInfo) {
            $html .= '<tr><td class="label">Section</td><td>: ' . esc($sectionInfo->title ?? '') . '</td></tr>';
        }
        if ($shiftInfo) {
            $html .= '<tr><td class="label">Shift</td><td>: ' . esc($shiftInfo->title ?? '') . '</td></tr>';
        }
        $html .= '</table>';

        // Subject Result Table
        $html .= '<h5 style="margin-bottom:10px;color:#333;font-weight:600;">Subject Result</h5>';
        $html .= $this->buildSubjectTableHtml($subjectRows);

        // Result Summary
        $html .= '<h5 style="margin-bottom:10px;color:#333;font-weight:600;">Result Summary</h5>';
        $html .= '<table class="summary-table">';
        $html .= '<tr><td class="label">Total Marks</td><td class="value">: ' . (int) $totalObtained . ' / ' . (int) $totalFull . '</td></tr>';
        $html .= '<tr><td class="label">Percentage</td><td class="value">: ' . number_format($percentage, 2) . ' %</td></tr>';
        $html .= '<tr><td class="label">Overall GPA</td><td class="value">: ' . number_format($overallGpa, 2) . '</td></tr>';
        $html .= '<tr><td class="label">Overall Grade</td><td class="value ' . $gradeClass . '">: ' . esc($overallGrade) . '</td></tr>';
        $html .= '<tr><td class="label">Result</td><td class="value ' . $resultClass . '">: ' . esc($resultStatus) . '</td></tr>';
        $html .= '<tr><td class="label">Merit Position</td><td class="value">: ' . ($classRank ?: '-') . '</td></tr>';
        if ($sectionInfo) {
            $html .= '<tr><td class="label">Section Position</td><td class="value">: ' . ($sectionRank ?: '-') . '</td></tr>';
        }
        if ($attendance) {
            $html .= '<tr><td class="label">Attendance</td><td class="value">: ' . esc($attendance) . '</td></tr>';
        }
        if ($workingDays) {
            $html .= '<tr><td class="label">Working Days</td><td class="value">: ' . esc($workingDays) . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '</div>';

        return $html;
    }

    // ===================================================================
    // Helper: Render template with placeholder replacement
    // ===================================================================
    private function renderWithTemplate(string $templateContent, array $data): string
    {
        $replacements = [
            '{school_name}'       => $data['school_name'] ?? '',
            '{school_address}'    => $data['school_address'] ?? '',
            '{school_phone}'      => $data['school_phone'] ?? '',
            '{school_email}'      => $data['school_email'] ?? '',
            '{school_website}'    => $data['school_website'] ?? '',
            '{student_name}'      => $data['student_name'] ?? '',
            '{student_code}'      => $data['student_code'] ?? '',
            '{roll_no}'           => $data['roll_no'] ?? '',
            '{class_name}'        => $data['class_name'] ?? '',
            '{section_name}'      => $data['section_name'] ?? '',
            '{session_name}'      => $data['session_name'] ?? '',
            '{exam_name}'         => $data['exam_name'] ?? '',
            '{total_subjects}'    => $data['total_subjects'] ?? '',
            '{passed_subjects}'   => $data['passed_subjects'] ?? '',
            '{failed_subjects}'   => $data['failed_subjects'] ?? '',
            '{total_marks}'       => $data['total_marks'] ?? '',
            '{obtained_marks}'    => $data['obtained_marks'] ?? '',
            '{percentage}'        => $data['percentage'] ?? '',
            '{gpa}'               => $data['gpa'] ?? '',
            '{grade}'             => $data['grade'] ?? '',
            '{result_status}'     => $data['result_status'] ?? '',
            '{class_rank}'        => $data['class_rank'] ?? '',
            '{section_rank}'      => $data['section_rank'] ?? '',
            '{attendance}'        => $data['attendance'] ?? '',
            '{principal_remarks}' => $data['principal_remarks'] ?? '',
            '{teacher_remarks}'   => $data['teacher_remarks'] ?? '',
            '{current_date}'      => $data['current_date'] ?? '',
            '{subject_table}'     => $data['subject_table'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $templateContent);
    }
}

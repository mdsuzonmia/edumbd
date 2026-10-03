<?php

namespace App\Modules\examination\Controllers\Reports;

use App\Models\SchoolModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsYearModel;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class MeritListController extends BaseReportController
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
    protected SubjectModel $SubjectModel;

    public function __construct()
    {
        $this->SchoolModel        = new SchoolModel();
        $this->ExamModel          = new ExamModel();
        $this->ClassModel         = new AcademicsClassesModel();
        $this->SectionModel       = new AcademicsSectionModel();
        $this->YearModel          = new AcademicsYearModel();
        $this->ExamResultModel    = new ExamResultModel();
        $this->SubjectResultModel = new SubjectResultModel();
        $this->StudentModel       = new StudentModel();
        $this->EnrollmentModel    = new StudentEnrollmentModel();
        $this->SubjectModel       = new SubjectModel();
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

    /**
     * INDEX - Show filter form with Session, Exam, Class, Section, Limit
     */
    public function index()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $school_id  = (int) $this->request->getGet('school_id');
        $year_id    = (int) $this->request->getGet('year_id');
        $exam_id    = (int) $this->request->getGet('exam_id');
        $class_id   = (int) $this->request->getGet('class_id');
        $section_id = (int) $this->request->getGet('section_id');
        $limit      = (int) $this->request->getGet('limit');

        $data = compact('school_id', 'year_id', 'exam_id', 'class_id', 'section_id', 'limit');
        $data['school_list']   = $this->getSchoolDropdown();
        $data['year_list']     = [];
        $data['exam_list']     = [];
        $data['class_list']    = [];
        $data['section_list']  = [];
        $data['merit_html']    = '';

        if ($school_id) {
            $data['year_list']  = $this->getActiveOptions($school_id, 'YearModel');
            $data['class_list'] = $this->getActiveOptions($school_id, 'ClassModel');
        }

        if ($school_id && $year_id) {
            $exams = $this->ExamModel
                ->where('school_id', $school_id)
                ->where('year_id', $year_id)
                ->where('status', 1)
                ->orderBy('exam_order', 'ASC')
                ->findAll();
            $exam_list = [];
            foreach ($exams as $e) {
                $exam_list[$e->id] = $e->title;
            }
            $data['exam_list'] = $exam_list;
        }

        if ($school_id && $class_id) {
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

        // Generate merit list if all required filters selected
        if ($school_id && $year_id && $exam_id && $class_id) {
            $userSchools = $this->getUserSchools();
            $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

            if (!in_array($school_id, $schoolIds)) {
                return redirect()->to('examination/reports/merit-list')->with('error', 'Access denied.');
            }

            $data['merit_html'] = $this->generateMeritList($school_id, $year_id, $exam_id, $class_id, $section_id, $limit);
        }

        return $this->renderWithHeaderFooter('Merit List', 'App\Modules\examination\Views\reports\merit_list', $data);
    }

    /**
     * Generate merit list HTML.
     */
    protected function generateMeritList(int $school_id, int $year_id, int $exam_id, int $class_id, int $section_id = 0, int $limit = 0): string
    {
        // Get exam info
        $exam = $this->ExamModel->find($exam_id);
        if (!$exam) {
            return '<div class="alert alert-danger">Exam not found.</div>';
        }

        $class = $this->ClassModel->find($class_id);
        $className = $class ? $class->title : '';

        $year = $this->YearModel->find($year_id);
        $sessionName = $year ? $year->title : '';

        $sectionName = '';
        if ($section_id) {
            $section = $this->SectionModel->find($section_id);
            $sectionName = $section ? $section->title : '';
        }

        // Calculate total full marks from subjects
        $allSubjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        $fullMarksSum = 0;
        foreach ($allSubjects as $subject) {
            $fullMarksSum += $subject->full_mark ?? 100;
        }

        // Get enrolled students
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);

        if ($section_id) {
            $enrollments->where('student_enrollments.section_id', $section_id);
        }

        $enrollments = $enrollments->orderBy('student_enrollments.roll_no', 'ASC')->findAll();

        if (empty($enrollments)) {
            return '<div class="alert alert-info">No students found for the selected filters.</div>';
        }

        // Build merit data
        $meritRows = [];
        foreach ($enrollments as $enr) {
            $studentName = trim(($enr->first_name ?? '') . ' ' . ($enr->middle_name ?? '') . ' ' . ($enr->last_name ?? ''));
            $studentName = preg_replace('/\s+/', ' ', $studentName);

            $examResult = $this->ExamResultModel
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('student_id', $enr->student_id)
                ->first();

            if ($examResult) {
                $meritRows[] = [
                    'roll_no'      => $enr->roll_no ?? '',
                    'student_name' => $studentName,
                    'total'        => $examResult->obtained_marks ?? 0,
                    'full_marks'   => $fullMarksSum,
                    'percentage'   => $examResult->percentage ?? 0,
                    'gpa'          => $examResult->gpa ?? 0,
                    'grade'        => $examResult->letter_grade ?? $examResult->grade ?? '',
                ];
            } else {
                // If no exam result, calculate from subject results
                $subjectResults = $this->SubjectResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('student_id', $enr->student_id)
                    ->findAll();

                if (!empty($subjectResults)) {
                    $total = 0;
                    $totalGp = 0;
                    $count = 0;
                    foreach ($subjectResults as $sr) {
                        $total += $sr->obtained_mark ?? 0;
                        $totalGp += $sr->grade_point ?? 0;
                        $count++;
                    }
                    $percentage = $fullMarksSum > 0 ? ($total / $fullMarksSum) * 100 : 0;
                    $gpa = $count > 0 ? round($totalGp / $count, 2) : 0;
                    $grade = $this->getGradeFromGpa($gpa);

                    $meritRows[] = [
                        'roll_no'      => $enr->roll_no ?? '',
                        'student_name' => $studentName,
                        'total'        => $total,
                        'full_marks'   => $fullMarksSum,
                        'percentage'   => round($percentage, 2),
                        'gpa'          => $gpa,
                        'grade'        => $grade,
                    ];
                } else {
                    $meritRows[] = [
                        'roll_no'      => $enr->roll_no ?? '',
                        'student_name' => $studentName,
                        'total'        => 0,
                        'full_marks'   => $fullMarksSum,
                        'percentage'   => 0,
                        'gpa'          => 0,
                        'grade'        => '-',
                    ];
                }
            }
        }

        // Sort by total marks descending
        usort($meritRows, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // Assign positions and handle ties
        $position = 1;
        $prevTotal = null;
        foreach ($meritRows as &$row) {
            if ($prevTotal !== null && $row['total'] < $prevTotal) {
                $position++;
            }
            $row['position'] = $position;
            $prevTotal = $row['total'];
        }
        unset($row);

        // Apply limit
        if ($limit > 0 && $limit < count($meritRows)) {
            $meritRows = array_slice($meritRows, 0, $limit);
        }

        // Get school info
        $school = $this->SchoolModel->find($school_id);
        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';

        // Build HTML
        $html = '<style>
            .merit-table { border-collapse: collapse; width: 100%; }
            .merit-table th, .merit-table td { border: 1px solid #000; padding: 5px; text-align: center; font-size: 12px; }
            .merit-table td.td-name { text-align: left; }
            @media print {
                body * { visibility: hidden; }
                #meritPrintArea {margin: 0 !important; padding: 20px !important;}
                #meritPrintArea, #meritPrintArea * { visibility: visible; }
                #meritPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
                .no-print { display: none !important; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }
                .print-header { text-align: center; margin-bottom: 20px; }
                .print-header h2 { margin: 0; font-size: 20px; }
                .print-header h3 { margin: 5px 0; font-size: 16px; }
                .print-header p { margin: 3px 0; font-size: 13px; }
                .print-footer { text-align: center; margin-top: 15px; font-size: 12px; }
            }
            @media screen {
                .print-only { display: none; }
            }
        </style>';

        $html .= '<div id="meritPrintArea">';

        // Print Header
        $html .= '<div class="print-header print-only">';
        $html .= '<h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddress) . '</p>';
        $html .= '<p>Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Merit List</h3>';
        $html .= '<p><strong>Exam:</strong> ' . esc($exam->title) . ' | <strong>Class:</strong> ' . esc($className);
        if ($sectionName) {
            $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        }
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName) . '</p>';
        if ($limit > 0) {
            $html .= '<p><strong>Showing Top ' . $limit . ' Students</strong></p>';
        }
        
        $html .= '</div>';

        $pdfUrl = base_url('examination/reports/merit-list/download-pdf?school_id=' . $school_id . '&year_id=' . $year_id . '&exam_id=' . $exam_id . '&class_id=' . $class_id . '&section_id=' . $section_id . '&limit=' . $limit);

        $html .= '<div class="row mt-3">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="card">';
        $html .= '<div class="card-header d-flex justify-content-between align-items-center no-print">';
        $html .= '<h5 class="mb-0"><i class="bi bi-trophy"></i> Merit List';
        if ($limit > 0) {
            $html .= ' <small class="text-muted">(Top ' . $limit . ')</small>';
        }
        $html .= '</h5>';
        $html .= '<div>';
        $html .= '<a href="' . $pdfUrl . '" class="btn btn-sm btn-danger me-2"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>';
        $html .= '<button type="button" class="btn btn-sm btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="card-body p-0">';
        $html .= '<div class="table-responsive">';

        $html .= '<table class="merit-table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Position</th>';
        $html .= '<th>Roll</th>';
        $html .= '<th>Student Name</th>';
        $html .= '<th>Total</th>';
        $html .= '<th>Percentage</th>';
        $html .= '<th>GPA</th>';
        $html .= '<th>Grade</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($meritRows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . $row['position'] . '</td>';
            $html .= '<td>' . esc($row['roll_no']) . '</td>';
            $html .= '<td class="td-name">' . esc($row['student_name']) . '</td>';
            $html .= '<td>' . number_format($row['total'], 2) . '</td>';
            $html .= '<td>' . number_format($row['percentage'], 2) . '%</td>';
            $html .= '<td>' . number_format($row['gpa'], 2) . '</td>';
            $html .= '<td>' . esc($row['grade']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="card-footer text-muted no-print">';
        $html .= '<strong>Exam:</strong> ' . esc($exam->title) . ' | ';
        $html .= '<strong>Class:</strong> ' . esc($className);
        if ($sectionName) {
            $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        }
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName);
        if ($limit > 0) {
            $html .= ' | <strong>Showing:</strong> Top ' . $limit;
        } else {
            $html .= ' | <strong>Showing:</strong> All Students';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Print footer
        $html .= '<div class="print-footer print-only">';
        $html .= '<p>Generated on: ' . date('d-m-Y h:i A') . '</p>';
        $html .= '</div>';

        $html .= '</div>'; // end meritPrintArea

        return $html;
    }

    /**
     * Get grade letter from GPA (simple mapping - can be improved with grade rules)
     */
    protected function getGradeFromGpa(float $gpa): string
    {
        if ($gpa >= 5.00) return 'A+';
        if ($gpa >= 4.50) return 'A';
        if ($gpa >= 4.00) return 'A-';
        if ($gpa >= 3.50) return 'B+';
        if ($gpa >= 3.00) return 'B';
        if ($gpa >= 2.50) return 'B-';
        if ($gpa >= 2.00) return 'C';
        if ($gpa >= 1.00) return 'D';
        return 'F';
    }

    // ======================================================================
    // AJAX ENDPOINTS
    // ======================================================================

    public function ajaxGetYears()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/merit-list');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $years = [];
        if ($school_id) {
            $years = $this->getActiveOptions($school_id, 'YearModel');
        }
        return $this->response->setJSON(['success' => true, 'data' => $years]);
    }

    public function ajaxGetExams()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/merit-list');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $year_id   = (int) $this->request->getPost('year_id');
        $exams = [];
        if ($school_id && $year_id) {
            $examRecords = $this->ExamModel
                ->where('school_id', $school_id)
                ->where('year_id', $year_id)
                ->where('status', 1)
                ->orderBy('exam_order', 'ASC')
                ->findAll();
            foreach ($examRecords as $e) {
                $exams[$e->id] = $e->title;
            }
        }
        return $this->response->setJSON(['success' => true, 'data' => $exams]);
    }

    public function ajaxGetClasses()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/merit-list');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $classes = [];
        if ($school_id) {
            $classes = $this->getActiveOptions($school_id, 'ClassModel');
        }
        return $this->response->setJSON(['success' => true, 'data' => $classes]);
    }

    public function ajaxGetSections()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/merit-list');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $sections = [];
        if ($school_id) {
            $sectionRecords = $this->SectionModel
                ->where('school_id', $school_id)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
            foreach ($sectionRecords as $s) {
                $sections[$s->id] = $s->title;
            }
        }
        return $this->response->setJSON(['success' => true, 'data' => $sections]);
    }

    public function ajaxGenerateMerit()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/merit-list');
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $year_id    = (int) $this->request->getPost('year_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');
        $limit      = (int) $this->request->getPost('limit');

        if (!$school_id || !$year_id || !$exam_id || !$class_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'School, Session, Exam, and Class are required']);
        }

        $user_id = $this->getUserId();
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);

        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        $html = $this->generateMeritList($school_id, $year_id, $exam_id, $class_id, $section_id, $limit);

        return $this->response->setJSON(['success' => true, 'html' => $html]);
    }

    /**
     * Download Merit List as PDF
     */
    public function downloadPdf()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $school_id  = (int) $this->request->getGet('school_id');
        $year_id    = (int) $this->request->getGet('year_id');
        $exam_id    = (int) $this->request->getGet('exam_id');
        $class_id   = (int) $this->request->getGet('class_id');
        $section_id = (int) $this->request->getGet('section_id');
        $limit      = (int) $this->request->getGet('limit');

        if (!$school_id || !$year_id || !$exam_id || !$class_id) {
            return redirect()->to('examination/reports/merit-list')->with('error', 'All required fields must be selected');
        }

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return redirect()->to('examination/reports/merit-list')->with('error', 'Access denied');
        }

        // Get info
        $exam = $this->ExamModel->find($exam_id);
        $class = $this->ClassModel->find($class_id);
        $year = $this->YearModel->find($year_id);
        $section = $section_id ? $this->SectionModel->find($section_id) : null;
        $school = $this->SchoolModel->find($school_id);

        $examTitle    = $exam ? $exam->title : '';
        $className    = $class ? $class->title : '';
        $sessionName  = $year ? $year->title : '';
        $sectionName  = $section ? $section->title : '';
        $schoolName   = $school ? $school->name : '';
        $schoolAddr   = $school ? $school->address : '';
        $schoolPhone  = $school ? $school->phone : '';
        $schoolEmail  = $school ? $school->email : '';

        // Get subjects for full marks
        $allSubjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();
        $fullMarksSum = 0;
        foreach ($allSubjects as $subject) {
            $fullMarksSum += $subject->full_mark ?? 100;
        }

        // Get enrollments
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);

        if ($section_id) {
            $enrollments->where('student_enrollments.section_id', $section_id);
        }

        $enrollments = $enrollments->orderBy('student_enrollments.roll_no', 'ASC')->findAll();

        // Build merit data
        $meritRows = [];
        foreach ($enrollments as $enr) {
            $studentName = trim(($enr->first_name ?? '') . ' ' . ($enr->middle_name ?? '') . ' ' . ($enr->last_name ?? ''));
            $studentName = preg_replace('/\s+/', ' ', $studentName);

            $examResult = $this->ExamResultModel
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('student_id', $enr->student_id)
                ->first();

            if ($examResult) {
                $meritRows[] = [
                    'roll_no'      => $enr->roll_no ?? '',
                    'student_name' => $studentName,
                    'total'        => $examResult->obtained_marks ?? 0,
                    'percentage'   => $examResult->percentage ?? 0,
                    'gpa'          => $examResult->gpa ?? 0,
                    'grade'        => $examResult->letter_grade ?? $examResult->grade ?? '',
                ];
            } else {
                $subjectResults = $this->SubjectResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('student_id', $enr->student_id)
                    ->findAll();

                if (!empty($subjectResults)) {
                    $total = 0;
                    $totalGp = 0;
                    $count = 0;
                    foreach ($subjectResults as $sr) {
                        $total += $sr->obtained_mark ?? 0;
                        $totalGp += $sr->grade_point ?? 0;
                        $count++;
                    }
                    $percentage = $fullMarksSum > 0 ? ($total / $fullMarksSum) * 100 : 0;
                    $gpa = $count > 0 ? round($totalGp / $count, 2) : 0;
                    $grade = $this->getGradeFromGpa($gpa);

                    $meritRows[] = [
                        'roll_no'      => $enr->roll_no ?? '',
                        'student_name' => $studentName,
                        'total'        => $total,
                        'percentage'   => round($percentage, 2),
                        'gpa'          => $gpa,
                        'grade'        => $grade,
                    ];
                } else {
                    $meritRows[] = [
                        'roll_no'      => $enr->roll_no ?? '',
                        'student_name' => $studentName,
                        'total'        => 0,
                        'percentage'   => 0,
                        'gpa'          => 0,
                        'grade'        => '-',
                    ];
                }
            }
        }

        // Sort and assign positions
        usort($meritRows, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $position = 1;
        $prevTotal = null;
        foreach ($meritRows as &$row) {
            if ($prevTotal !== null && $row['total'] < $prevTotal) {
                $position++;
            }
            $row['position'] = $position;
            $prevTotal = $row['total'];
        }
        unset($row);

        if ($limit > 0) {
            $meritRows = array_slice($meritRows, 0, $limit);
        }

        // Build PDF HTML
        $title = 'Merit List';
        if ($limit > 0) {
            $title .= ' (Top ' . $limit . ')';
        }

        $html = '<html><head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
            .header { text-align: center; margin-bottom: 20px; }
            .header h2 { margin: 0; font-size: 18px; }
            .header p { margin: 2px 0; font-size: 12px; }
            .header h3 { margin: 10px 0 5px; font-size: 15px; }
            .header hr { border: 1px solid #000; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background: #1a73e8; color: #fff; padding: 6px 4px; border: 1px solid #000; text-align: center; font-size: 10px; }
            td { padding: 4px; border: 1px solid #000; text-align: center; font-size: 10px; }
            td.name { text-align: left; }
            .footer { text-align: center; margin-top: 15px; font-size: 10px; color: #666; }
            .total { font-weight: bold; }
            .position { font-weight: bold; }
        </style>
        </head><body>';

        $html .= '<div class="header">';
        $html .= '<h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddr) . '</p>';
        $html .= '<p>Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>' . $title . '</h3>';
        $html .= '<p><strong>Exam:</strong> ' . esc($examTitle) . ' | <strong>Class:</strong> ' . esc($className);
        if ($sectionName) {
            $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        }
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName) . '</p>';
        $html .= '<hr>';
        $html .= '</div>';

        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Position</th>';
        $html .= '<th>Roll</th>';
        $html .= '<th>Student Name</th>';
        $html .= '<th>Total</th>';
        $html .= '<th>Percentage</th>';
        $html .= '<th>GPA</th>';
        $html .= '<th>Grade</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($meritRows as $row) {
            $html .= '<tr>';
            $html .= '<td class="position">' . $row['position'] . '</td>';
            $html .= '<td>' . esc($row['roll_no']) . '</td>';
            $html .= '<td class="name">' . esc($row['student_name']) . '</td>';
            $html .= '<td class="total">' . number_format($row['total'], 2) . '</td>';
            $html .= '<td>' . number_format($row['percentage'], 2) . '%</td>';
            $html .= '<td>' . number_format($row['gpa'], 2) . '</td>';
            $html .= '<td>' . esc($row['grade']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $html .= '<div class="footer">';
        $html .= '<p>Generated on: ' . date('d-m-Y h:i A') . '</p>';
        $html .= '</div>';

        $html .= '</body></html>';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Merit_List_' . $examTitle . '_' . $className . '_' . $sessionName . '.pdf';
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
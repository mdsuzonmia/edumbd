<?php

namespace App\Modules\examination\Controllers\Reports;

use App\Models\SchoolModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsYearModel;
use App\Models\StudentEnrollmentModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class ClassStatisticsController extends BaseReportController
{
    protected SchoolModel $SchoolModel;
    protected ExamModel $ExamModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsYearModel $YearModel;
    protected ExamResultModel $ExamResultModel;
    protected SubjectResultModel $SubjectResultModel;
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
     * INDEX
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
        $result_source = $this->request->getGet('result_source') ?? 'exam';

        $data = compact('school_id', 'year_id', 'exam_id', 'class_id', 'section_id', 'result_source');
        $data['school_list']   = $this->getSchoolDropdown();
        $data['year_list']     = [];
        $data['exam_list']     = [];
        $data['class_list']    = [];
        $data['section_list']  = [];
        $data['statistics_html'] = '';

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

        if ($school_id && $year_id && $class_id) {
            $userSchools = $this->getUserSchools();
            $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);
            if (!in_array($school_id, $schoolIds)) {
                return redirect()->to('examination/reports/class-statistics')->with('error', 'Access denied.');
            }
            $data['statistics_html'] = $this->generateStatistics(
                $school_id, $year_id, $exam_id, $class_id, $section_id, $result_source
            );
        }

        return $this->renderWithHeaderFooter('Class Statistics', 'App\Modules\examination\Views\reports\class_statistics', $data);
    }

    /**
     * Generate class statistics HTML.
     */
    protected function generateStatistics(
        int $school_id, int $year_id, int $exam_id, int $class_id,
        int $section_id = 0, string $result_source = 'exam'
    ): string {
        $class = $this->ClassModel->find($class_id);
        $className = $class ? $class->title : '';
        $year = $this->YearModel->find($year_id);
        $sessionName = $year ? $year->title : '';

        $examName = '';
        $sourceLabel = 'Aggregate Result';
        if ($result_source === 'exam' && $exam_id) {
            $exam = $this->ExamModel->find($exam_id);
            $examName = $exam ? $exam->title : '';
            $sourceLabel = $examName;
        }

        $sectionName = '';
        if ($section_id) {
            $section = $this->SectionModel->find($section_id);
            $sectionName = $section ? $section->title : '';
        }

        // Get subjects for full marks
        $subjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        $totalFullMarks = 0;
        foreach ($subjects as $sub) {
            $totalFullMarks += $sub->full_mark ?? 100;
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

        // Process each student
        $totalStudents = count($enrollments);
        $appeared = 0;
        $passed = 0;
        $failed = 0;
        $absent = 0;
        $percentages = [];
        $gpas = [];
        $gradeDistribution = [];
        $resultDistribution = ['Passed' => 0, 'Failed' => 0, 'Absent' => 0];

        foreach ($enrollments as $enr) {
            if ($result_source === 'exam' && $exam_id) {
                $examResult = $this->ExamResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('student_id', $enr->student_id)
                    ->first();

                if ($examResult) {
                    $appeared++;
                    if ($examResult->result_status === 'PASS' || $examResult->result_status === 'Passed') {
                        $passed++;
                        $resultDistribution['Passed']++;
                    } else {
                        $failed++;
                        $resultDistribution['Failed']++;
                    }
                    $percentages[] = $examResult->percentage ?? 0;
                    $gpas[] = $examResult->gpa ?? 0;
                    $grade = $examResult->letter_grade ?? $examResult->grade ?? '-';

                    if (!isset($gradeDistribution[$grade])) $gradeDistribution[$grade] = 0;
                    $gradeDistribution[$grade]++;
                } else {
                    $absent++;
                    $resultDistribution['Absent']++;
                }
            } else {
                // Aggregate: get all subject results for this student in this class/session
                $allSubjectResults = $this->SubjectResultModel
                    ->where('school_id', $school_id)
                    ->where('class_id', $class_id)
                    ->where('student_id', $enr->student_id)
                    ->where('session_id', $year_id)
                    ->findAll();

                if (!empty($allSubjectResults)) {
                    $appeared++;
                    $totalObtained = 0;
                    $totalGp = 0;
                    $count = 0;
                    $isFail = false;
                    foreach ($allSubjectResults as $sr) {
                        $totalObtained += $sr->obtained_mark ?? 0;
                        $totalGp += $sr->grade_point ?? 0;
                        $count++;
                        if ($sr->is_fail == 1) $isFail = true;
                    }
                    if (!$isFail) {
                        $passed++;
                        $resultDistribution['Passed']++;
                    } else {
                        $failed++;
                        $resultDistribution['Failed']++;
                    }
                    $pct = $totalFullMarks > 0 ? round(($totalObtained / $totalFullMarks) * 100, 2) : 0;
                    $percentages[] = $pct;
                    $gpa = $count > 0 ? round($totalGp / $count, 2) : 0;
                    $gpas[] = $gpa;

                    // Use the first result's grade or calculate from average
                    $grade = $allSubjectResults[0]->letter_grade ?? $allSubjectResults[0]->grade ?? '-';
                    if (!isset($gradeDistribution[$grade])) $gradeDistribution[$grade] = 0;
                    $gradeDistribution[$grade]++;
                } else {
                    $absent++;
                    $resultDistribution['Absent']++;
                }
            }
        }

        $passRate = $appeared > 0 ? round(($passed / $appeared) * 100, 2) : 0;
        $avgPercentage = !empty($percentages) ? round(array_sum($percentages) / count($percentages), 2) : 0;
        $highestPercentage = !empty($percentages) ? max($percentages) : 0;
        $lowestPercentage = !empty($percentages) ? min($percentages) : 0;
        $avgGpa = !empty($gpas) ? round(array_sum($gpas) / count($gpas), 2) : 0;
        $highestGpa = !empty($gpas) ? max($gpas) : 0;

        // Sort grade distribution
        $gradeOrder = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'F'];
        $sortedGrades = [];
        foreach ($gradeOrder as $g) {
            if (isset($gradeDistribution[$g])) $sortedGrades[$g] = $gradeDistribution[$g];
        }
        foreach ($gradeDistribution as $g => $c) {
            if (!isset($sortedGrades[$g])) $sortedGrades[$g] = $c;
        }

        // School info
        $school = $this->SchoolModel->find($school_id);
        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';

        // Build HTML
        $html = '<style>
            @media print {
                body * { visibility: hidden; }
                #statsPrintArea, #statsPrintArea * { visibility: visible; }
                #statsPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
                .no-print { display: none !important; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }
                .print-header { text-align: center; margin-bottom: 20px; }
                .print-header h2 { margin: 0; font-size: 20px; }
                .print-header p { margin: 3px 0; font-size: 13px; }
                .print-footer { text-align: center; margin-top: 15px; font-size: 12px; }
            }
            @media screen { .print-only { display: none; } }
            .stats-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
            .stats-card h4 { color: #1a73e8; border-bottom: 2px solid #1a73e8; padding-bottom: 10px; margin-bottom: 20px; }
            .stat-item { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px; }
            .stat-item .stat-value { font-size: 24px; font-weight: bold; color: #1a73e8; }
            .stat-item .stat-label { font-size: 13px; color: #6c757d; margin-top: 5px; }
            .stat-item.pass .stat-value { color: #28a745; }
            .stat-item.fail .stat-value { color: #dc3545; }
            .stat-item.high .stat-value { color: #17a2b8; }
        </style>';

        $pdfUrl = base_url('examination/reports/class-statistics/download-pdf?school_id=' . $school_id . '&year_id=' . $year_id . '&exam_id=' . $exam_id . '&class_id=' . $class_id . '&section_id=' . $section_id . '&result_source=' . $result_source);

        $html .= '<div id="statsPrintArea">';

        // Print Header
        $html .= '<div class="print-header print-only">';
        $html .= '<h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddress) . '</p>';
        $html .= '<p>Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Class Statistics</h3>';
        $html .= '<p><strong>Class:</strong> ' . esc($className);
        if ($sectionName) $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName);
        $html .= ' | <strong>Source:</strong> ' . esc($sourceLabel) . '</p>';
        $html .= '<hr style="border:1px solid #000;">';
        $html .= '</div>';

        $html .= '<div class="row mt-3">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="card">';
        $html .= '<div class="card-header d-flex justify-content-between align-items-center no-print">';
        $html .= '<h5 class="mb-0"><i class="bi bi-bar-chart"></i> Class Statistics</h5>';
        $html .= '<div>';
        $html .= '<a href="' . $pdfUrl . '" class="btn btn-sm btn-danger me-2"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>';
        $html .= '<button type="button" class="btn btn-sm btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>';
        $html .= '</div></div>';
        $html .= '<div class="card-body">';

        // Info bar
        $html .= '<div class="alert alert-info no-print">';
        $html .= '<strong>Class:</strong> ' . esc($className) . ' &nbsp;|&nbsp; ';
        if ($sectionName) $html .= '<strong>Section:</strong> ' . esc($sectionName) . ' &nbsp;|&nbsp; ';
        $html .= '<strong>Session:</strong> ' . esc($sessionName) . ' &nbsp;|&nbsp; ';
        $html .= '<strong>Source:</strong> ' . esc($sourceLabel);
        $html .= '</div>';

        // Statistics Cards
        $html .= '<div class="row">';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item"><div class="stat-value">' . $totalStudents . '</div><div class="stat-label">Total Students</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item"><div class="stat-value">' . $appeared . '</div><div class="stat-label">Appeared</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item pass"><div class="stat-value">' . $passed . '</div><div class="stat-label">Passed</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item fail"><div class="stat-value">' . $failed . '</div><div class="stat-label">Failed</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item"><div class="stat-value">' . $absent . '</div><div class="stat-label">Absent</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item pass"><div class="stat-value">' . number_format($passRate, 2) . '%</div><div class="stat-label">Pass Rate</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item high"><div class="stat-value">' . number_format($avgPercentage, 2) . '%</div><div class="stat-label">Avg Percentage</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item high"><div class="stat-value">' . number_format($highestPercentage, 2) . '%</div><div class="stat-label">Highest %</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item"><div class="stat-value">' . number_format($lowestPercentage, 2) . '%</div><div class="stat-label">Lowest %</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item high"><div class="stat-value">' . number_format($avgGpa, 2) . '</div><div class="stat-label">Average GPA</div></div></div>';
        $html .= '<div class="col-md-3 col-6"><div class="stat-item high"><div class="stat-value">' . number_format($highestGpa, 2) . '</div><div class="stat-label">Highest GPA</div></div></div>';
        $html .= '</div>';

        // Grade Distribution + Result Distribution
        $html .= '<div class="row mt-4">';
        $html .= '<div class="col-md-6"><div class="stats-card"><h4><i class="bi bi-pie-chart"></i> Grade Distribution</h4>';
        if (!empty($sortedGrades)) {
            $html .= '<table class="table table-bordered table-hover">';
            $html .= '<thead><tr style="background:#1a73e8;color:#fff;"><th style="padding:8px;border:1px solid #ccc;">Grade</th><th style="padding:8px;border:1px solid #ccc;text-align:center;">Students</th><th style="padding:8px;border:1px solid #ccc;text-align:center;">Percentage</th></tr></thead><tbody>';
            foreach ($sortedGrades as $grade => $count) {
                $pct = $appeared > 0 ? round(($count / $appeared) * 100, 2) : 0;
                $html .= '<tr><td style="padding:5px;border:1px solid #ccc;font-weight:bold;">' . esc($grade) . '</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . $count . '</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . number_format($pct, 2) . '%</td></tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="text-muted">No grade data available.</p>';
        }
        $html .= '</div></div>';

        $html .= '<div class="col-md-6"><div class="stats-card"><h4><i class="bi bi-check-circle"></i> Result Distribution</h4>';
        $html .= '<table class="table table-bordered">';
        $html .= '<thead><tr style="background:#1a73e8;color:#fff;"><th style="padding:8px;border:1px solid #ccc;">Result</th><th style="padding:8px;border:1px solid #ccc;text-align:center;">Students</th><th style="padding:8px;border:1px solid #ccc;text-align:center;">Percentage</th></tr></thead><tbody>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;color:#28a745;font-weight:bold;">Passed</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($resultDistribution['Passed'] ?? 0) . '</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($totalStudents > 0 ? number_format(($resultDistribution['Passed'] / $totalStudents) * 100, 2) : 0) . '%</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;color:#dc3545;font-weight:bold;">Failed</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($resultDistribution['Failed'] ?? 0) . '</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($totalStudents > 0 ? number_format(($resultDistribution['Failed'] / $totalStudents) * 100, 2) : 0) . '%</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;font-weight:bold;">Absent</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($resultDistribution['Absent'] ?? 0) . '</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($totalStudents > 0 ? number_format(($resultDistribution['Absent'] / $totalStudents) * 100, 2) : 0) . '%</td></tr>';
        $html .= '</tbody></table>';
        $html .= '</div></div></div>';

        // Summary table
        $html .= '<div class="row mt-2"><div class="col-md-12"><div class="stats-card"><h4><i class="bi bi-table"></i> Performance Summary</h4>';
        $html .= '<table class="table table-bordered">';
        $html .= '<thead><tr style="background:#1a73e8;color:#fff;"><th style="padding:8px;border:1px solid #ccc;">Metric</th><th style="padding:8px;border:1px solid #ccc;text-align:center;">Value</th></tr></thead><tbody>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Total Students</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;">' . $totalStudents . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Appeared</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . $appeared . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Passed</td><td style="padding:5px;border:1px solid #ccc;text-align:center;color:#28a745;font-weight:bold;">' . $passed . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Failed</td><td style="padding:5px;border:1px solid #ccc;text-align:center;color:#dc3545;font-weight:bold;">' . $failed . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Absent</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . $absent . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;font-weight:bold;">Pass Rate</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;color:#28a745;">' . number_format($passRate, 2) . '%</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Average Percentage</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;">' . number_format($avgPercentage, 2) . '%</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Highest Percentage</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;color:#17a2b8;">' . number_format($highestPercentage, 2) . '%</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Lowest Percentage</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;">' . number_format($lowestPercentage, 2) . '%</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Average GPA</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;">' . number_format($avgGpa, 2) . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Highest GPA</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;color:#17a2b8;">' . number_format($highestGpa, 2) . '</td></tr>';
        $html .= '</tbody></table>';
        $html .= '</div></div></div>';

        $html .= '</div>'; // card-body
        $html .= '<div class="card-footer text-muted no-print">';
        $html .= '<strong>Class:</strong> ' . esc($className);
        if ($sectionName) $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName);
        $html .= ' | <strong>Source:</strong> ' . esc($sourceLabel);
        $html .= '</div></div></div></div>';

        $html .= '<div class="print-footer print-only"><p>Generated on: ' . date('d-m-Y h:i A') . '</p></div>';
        $html .= '</div>';

        return $html;
    }

    // ======================================================================
    // AJAX ENDPOINTS
    // ======================================================================

    public function ajaxGetYears()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/class-statistics');
        $school_id = (int) $this->request->getPost('school_id');
        $years = $school_id ? $this->getActiveOptions($school_id, 'YearModel') : [];
        return $this->response->setJSON(['success' => true, 'data' => $years]);
    }

    public function ajaxGetExams()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/class-statistics');
        $school_id = (int) $this->request->getPost('school_id');
        $year_id   = (int) $this->request->getPost('year_id');
        $exams = [];
        if ($school_id && $year_id) {
            $records = $this->ExamModel->where('school_id', $school_id)->where('year_id', $year_id)->where('status', 1)->orderBy('exam_order', 'ASC')->findAll();
            foreach ($records as $e) $exams[$e->id] = $e->title;
        }
        return $this->response->setJSON(['success' => true, 'data' => $exams]);
    }

    public function ajaxGetClasses()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/class-statistics');
        $school_id = (int) $this->request->getPost('school_id');
        $classes = $school_id ? $this->getActiveOptions($school_id, 'ClassModel') : [];
        return $this->response->setJSON(['success' => true, 'data' => $classes]);
    }

    public function ajaxGetSections()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/class-statistics');
        $school_id = (int) $this->request->getPost('school_id');
        $sections = [];
        if ($school_id) {
            $records = $this->SectionModel->where('school_id', $school_id)->where('status', 1)->orderBy('title', 'ASC')->findAll();
            foreach ($records as $s) $sections[$s->id] = $s->title;
        }
        return $this->response->setJSON(['success' => true, 'data' => $sections]);
    }

    public function ajaxGenerateStatistics()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/class-statistics');

        $school_id  = (int) $this->request->getPost('school_id');
        $year_id    = (int) $this->request->getPost('year_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');
        $result_source = $this->request->getPost('result_source') ?? 'exam';

        if (!$school_id || !$year_id || !$class_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'School, Session, and Class are required']);
        }

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        $html = $this->generateStatistics($school_id, $year_id, $exam_id, $class_id, $section_id, $result_source);
        return $this->response->setJSON(['success' => true, 'html' => $html]);
    }

    public function downloadPdf()
    {
        $user_id = $this->getUserId();
        if (!$user_id) return redirect()->to('login');

        $school_id  = (int) $this->request->getGet('school_id');
        $year_id    = (int) $this->request->getGet('year_id');
        $exam_id    = (int) $this->request->getGet('exam_id');
        $class_id   = (int) $this->request->getGet('class_id');
        $section_id = (int) $this->request->getGet('section_id');
        $result_source = $this->request->getGet('result_source') ?? 'exam';

        if (!$school_id || !$year_id || !$class_id) {
            return redirect()->to('examination/reports/class-statistics')->with('error', 'All required fields must be selected');
        }

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) return redirect()->to('examination/reports/class-statistics')->with('error', 'Access denied');

        $class = $this->ClassModel->find($class_id);
        $year = $this->YearModel->find($year_id);
        $section = $section_id ? $this->SectionModel->find($section_id) : null;
        $exam = ($result_source === 'exam' && $exam_id) ? $this->ExamModel->find($exam_id) : null;
        $school = $this->SchoolModel->find($school_id);

        $className    = $class ? $class->title : '';
        $sessionName  = $year ? $year->title : '';
        $sectionName  = $section ? $section->title : '';
        $examName     = $exam ? $exam->title : '';
        $sourceLabel  = $result_source === 'exam' ? $examName : 'Aggregate Result';
        $schoolName   = $school ? $school->name : '';
        $schoolAddr   = $school ? $school->address : '';
        $schoolPhone  = $school ? $school->phone : '';
        $schoolEmail  = $school ? $school->email : '';

        // Get subjects for full marks
        $subjects = $this->SubjectModel->where('school_id', $school_id)->where('status', 1)->findAll();
        $totalFullMarks = 0;
        foreach ($subjects as $sub) $totalFullMarks += $sub->full_mark ?? 100;

        // Get enrollments
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);
        if ($section_id) $enrollments->where('student_enrollments.section_id', $section_id);
        $enrollments = $enrollments->orderBy('student_enrollments.roll_no', 'ASC')->findAll();

        $totalStudents = count($enrollments);
        $appeared = 0; $passed = 0; $failed = 0; $absent = 0;
        $percentages = []; $gpas = [];
        $gradeDistribution = [];

        foreach ($enrollments as $enr) {
            if ($result_source === 'exam' && $exam_id) {
                $er = $this->ExamResultModel->where('school_id', $school_id)->where('exam_id', $exam_id)->where('class_id', $class_id)->where('student_id', $enr->student_id)->first();
                if ($er) {
                    $appeared++;
                    if ($er->result_status === 'PASS' || $er->result_status === 'Passed') $passed++; else $failed++;
                    $percentages[] = $er->percentage ?? 0;
                    $gpas[] = $er->gpa ?? 0;
                    $g = $er->letter_grade ?? $er->grade ?? '-';
                    if (!isset($gradeDistribution[$g])) $gradeDistribution[$g] = 0;
                    $gradeDistribution[$g]++;
                } else { $absent++; }
            } else {
                $srs = $this->SubjectResultModel->where('school_id', $school_id)->where('class_id', $class_id)->where('student_id', $enr->student_id)->where('session_id', $year_id)->findAll();
                if (!empty($srs)) {
                    $appeared++;
                    $total = 0; $tg = 0; $cnt = 0; $fail = false;
                    foreach ($srs as $sr) {
                        $total += $sr->obtained_mark ?? 0;
                        $tg += $sr->grade_point ?? 0;
                        $cnt++;
                        if ($sr->is_fail == 1) $fail = true;
                    }
                    if (!$fail) $passed++; else $failed++;
                    $pct = $totalFullMarks > 0 ? round(($total / $totalFullMarks) * 100, 2) : 0;
                    $percentages[] = $pct;
                    $gpas[] = $cnt > 0 ? round($tg / $cnt, 2) : 0;
                    $g = $srs[0]->letter_grade ?? $srs[0]->grade ?? '-';
                    if (!isset($gradeDistribution[$g])) $gradeDistribution[$g] = 0;
                    $gradeDistribution[$g]++;
                } else { $absent++; }
            }
        }

        $passRate = $appeared > 0 ? round(($passed / $appeared) * 100, 2) : 0;
        $avgPct = !empty($percentages) ? round(array_sum($percentages) / count($percentages), 2) : 0;
        $highPct = !empty($percentages) ? max($percentages) : 0;
        $lowPct = !empty($percentages) ? min($percentages) : 0;
        $avgGpa = !empty($gpas) ? round(array_sum($gpas) / count($gpas), 2) : 0;
        $highGpa = !empty($gpas) ? max($gpas) : 0;

        // Build PDF HTML
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
            .header { text-align: center; margin-bottom: 20px; }
            .header h2 { margin: 0; font-size: 18px; }
            .header p { margin: 2px 0; font-size: 12px; }
            .header h3 { margin: 10px 0 5px; font-size: 15px; }
            .header hr { border: 1px solid #000; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background: #1a73e8; color: #fff; padding: 6px 4px; border: 1px solid #000; text-align: center; font-size: 10px; }
            td { padding: 4px; border: 1px solid #000; font-size: 10px; }
            td.center { text-align: center; }
            td.bold { font-weight: bold; }
            td.label { font-weight: bold; width: 220px; }
            .footer { text-align: center; margin-top: 15px; font-size: 10px; color: #666; }
        </style></head><body>';

        $html .= '<div class="header"><h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddr) . ' | Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Class Statistics</h3>';
        $html .= '<p><strong>Class:</strong> ' . esc($className);
        if ($sectionName) $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName) . ' | <strong>Source:</strong> ' . esc($sourceLabel) . '</p><hr></div>';

        $html .= '<table>';
        $html .= '<tr><td class="label">Total Students</td><td class="center bold">' . $totalStudents . '</td></tr>';
        $html .= '<tr><td class="label">Appeared</td><td class="center">' . $appeared . '</td></tr>';
        $html .= '<tr><td class="label">Passed</td><td class="center bold" style="color:#28a745;">' . $passed . '</td></tr>';
        $html .= '<tr><td class="label">Failed</td><td class="center bold" style="color:#dc3545;">' . $failed . '</td></tr>';
        $html .= '<tr><td class="label">Absent</td><td class="center">' . $absent . '</td></tr>';
        $html .= '<tr><td class="label">Pass Rate</td><td class="center bold" style="color:#28a745;">' . number_format($passRate, 2) . '%</td></tr>';
        $html .= '<tr><td class="label">Average Percentage</td><td class="center bold">' . number_format($avgPct, 2) . '%</td></tr>';
        $html .= '<tr><td class="label">Highest Percentage</td><td class="center bold" style="color:#17a2b8;">' . number_format($highPct, 2) . '%</td></tr>';
        $html .= '<tr><td class="label">Lowest Percentage</td><td class="center bold">' . number_format($lowPct, 2) . '%</td></tr>';
        $html .= '<tr><td class="label">Average GPA</td><td class="center bold">' . number_format($avgGpa, 2) . '</td></tr>';
        $html .= '<tr><td class="label">Highest GPA</td><td class="center bold" style="color:#17a2b8;">' . number_format($highGpa, 2) . '</td></tr>';
        $html .= '</table>';

        if (!empty($gradeDistribution)) {
            $html .= '<h4 style="margin-top:20px;">Grade Distribution</h4><table><thead><tr><th>Grade</th><th>Students</th><th>Percentage</th></tr></thead><tbody>';
            $gOrder = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'F'];
            foreach ($gOrder as $g) {
                if (isset($gradeDistribution[$g])) {
                    $pct = $appeared > 0 ? round(($gradeDistribution[$g] / $appeared) * 100, 2) : 0;
                    $html .= '<tr><td class="center bold">' . $g . '</td><td class="center">' . $gradeDistribution[$g] . '</td><td class="center">' . number_format($pct, 2) . '%</td></tr>';
                }
            }
            $html .= '</tbody></table>';
        }

        $html .= '<div class="footer"><p>Generated on: ' . date('d-m-Y h:i A') . '</p></div>';
        $html .= '</body></html>';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Class_Statistics_' . $className . '_' . $sessionName . '.pdf';
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
<?php

namespace App\Modules\examination\Controllers\Reports;

use App\Models\SchoolModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsYearModel;
use App\Models\StudentEnrollmentModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\SubjectDistributionModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class SubjectAnalysisController extends BaseReportController
{
    protected SchoolModel $SchoolModel;
    protected ExamModel $ExamModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsYearModel $YearModel;
    protected SubjectResultModel $SubjectResultModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected SubjectModel $SubjectModel;
    protected SubjectDistributionModel $SubjectDistributionModel;

    public function __construct()
    {
        $this->SchoolModel        = new SchoolModel();
        $this->ExamModel                = new ExamModel();
        $this->ClassModel               = new AcademicsClassesModel();
        $this->SectionModel             = new AcademicsSectionModel();
        $this->YearModel                = new AcademicsYearModel();
        $this->SubjectResultModel       = new SubjectResultModel();
        $this->EnrollmentModel          = new StudentEnrollmentModel();
        $this->SubjectModel             = new SubjectModel();
        $this->SubjectDistributionModel = new SubjectDistributionModel();
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
     * Get subjects linked to a specific class via subject distributions for the school.
     * Falls back to all school subjects if no class linkage exists.
     */
    protected function getSubjectsForClass(int $school_id, int $class_id): array
    {
        // Try to get subjects from subject_distributions that match this school
        $db = \Config\Database::connect();
        $subjectIds = $db->table('examination_subject_distributions')
            ->select('DISTINCT(subject_id)')
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->get()
            ->getResult();

        if (!empty($subjectIds)) {
            $ids = array_map(function ($s) { return $s->subject_id; }, $subjectIds);
            return $this->SubjectModel
                ->where('school_id', $school_id)
                ->whereIn('id', $ids)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
        }

        // Fallback: all subjects for the school
        return $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();
    }

    /**
     * INDEX - Filter form
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
        $subject_id = (int) $this->request->getGet('subject_id');
        $result_source = $this->request->getGet('result_source') ?? 'exam'; // 'exam' or 'aggregate'

        $data = compact('school_id', 'year_id', 'exam_id', 'class_id', 'section_id', 'subject_id', 'result_source');
        $data['school_list']  = $this->getSchoolDropdown();
        $data['year_list']    = [];
        $data['exam_list']    = [];
        $data['class_list']   = [];
        $data['section_list'] = [];
        $data['subject_list'] = [];
        $data['analysis_html'] = '';

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

            // Load subjects for class
            $subjects = $this->getSubjectsForClass($school_id, $class_id);
            $subject_list = [];
            foreach ($subjects as $sub) {
                $subject_list[$sub->id] = $sub->title;
            }
            $data['subject_list'] = $subject_list;
        }

        // Generate analysis if all required filters selected
        if ($school_id && $year_id && $class_id && $subject_id) {
            $userSchools = $this->getUserSchools();
            $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

            if (!in_array($school_id, $schoolIds)) {
                return redirect()->to('examination/reports/subject-analysis')->with('error', 'Access denied.');
            }

            $data['analysis_html'] = $this->generateSubjectAnalysis(
                $school_id, $year_id, $exam_id, $class_id, $section_id, $subject_id, $result_source
            );
        }

        return $this->renderWithHeaderFooter('Subject Analysis', 'App\Modules\examination\Views\reports\subject_analysis', $data);
    }

    /**
     * Generate subject analysis HTML.
     */
    protected function generateSubjectAnalysis(
        int $school_id, int $year_id, int $exam_id, int $class_id,
        int $section_id = 0, int $subject_id = 0, string $result_source = 'exam'
    ): string {
        // Get info
        $subject = $this->SubjectModel->find($subject_id);
        if (!$subject) {
            return '<div class="alert alert-danger">Subject not found.</div>';
        }

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

        // Get subject results
        $totalStudents = count($enrollments);
        $appeared = 0;
        $passed = 0;
        $failed = 0;
        $absent = 0;
        $marks = [];
        $gradeDistribution = [];

        foreach ($enrollments as $enr) {
            $subjectResult = $this->SubjectResultModel
                ->where('school_id', $school_id);

            if ($result_source === 'exam' && $exam_id) {
                $subjectResult->where('exam_id', $exam_id);
            }

            $subjectResult = $subjectResult
                ->where('class_id', $class_id)
                ->where('student_id', $enr->student_id)
                ->where('subject_id', $subject_id)
                ->first();

            if ($subjectResult) {
                $obtained = $subjectResult->obtained_mark ?? 0;
                $marks[] = $obtained;
                $appeared++;

                // Check pass/fail
                $isFail = $subjectResult->is_fail ?? 0;
                if ($isFail == 0 && $obtained > 0) {
                    $passed++;
                } else {
                    $failed++;
                }

                // Grade distribution
                $grade = $subjectResult->letter_grade ?? $subjectResult->grade ?? ($isFail ? 'F' : '-');
                if (!isset($gradeDistribution[$grade])) {
                    $gradeDistribution[$grade] = 0;
                }
                $gradeDistribution[$grade]++;
            } else {
                $absent++;
            }
        }

        // Calculate statistics
        $averageMark = !empty($marks) ? round(array_sum($marks) / count($marks), 2) : 0;
        $highestMark = !empty($marks) ? max($marks) : 0;
        $lowestMark  = !empty($marks) ? min($marks) : 0;
        $passRate    = $appeared > 0 ? round(($passed / $appeared) * 100, 2) : 0;

        // Sort grade distribution
        $gradeOrder = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'F'];
        $sortedGrades = [];
        foreach ($gradeOrder as $g) {
            if (isset($gradeDistribution[$g])) {
                $sortedGrades[$g] = $gradeDistribution[$g];
            }
        }
        // Add any grades not in standard order
        foreach ($gradeDistribution as $g => $count) {
            if (!isset($sortedGrades[$g])) {
                $sortedGrades[$g] = $count;
            }
        }

        // Get school info
        $school = $this->SchoolModel->find($school_id);
        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';

        // Build HTML
        $html = '<style>
            @media print {
                body * { visibility: hidden; }
                #analysisPrintArea, #analysisPrintArea * { visibility: visible; }
                #analysisPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
                .no-print { display: none !important; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }
                .print-header { text-align: center; margin-bottom: 20px; }
                .print-header h2 { margin: 0; font-size: 20px; }
                .print-header p { margin: 3px 0; font-size: 13px; }
                .print-footer { text-align: center; margin-top: 15px; font-size: 12px; }
            }
            @media screen {
                .print-only { display: none; }
            }
            .analysis-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
            .analysis-card h4 { color: #1a73e8; border-bottom: 2px solid #1a73e8; padding-bottom: 10px; margin-bottom: 20px; }
            .stat-item { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px; }
            .stat-item .stat-value { font-size: 24px; font-weight: bold; color: #1a73e8; }
            .stat-item .stat-label { font-size: 13px; color: #6c757d; margin-top: 5px; }
            .stat-item.pass .stat-value { color: #28a745; }
            .stat-item.fail .stat-value { color: #dc3545; }
            .stat-item.high .stat-value { color: #17a2b8; }
            .stat-item.low .stat-value { color: #ffc107; }
        </style>';

        $pdfUrl = base_url('examination/reports/subject-analysis/download-pdf?school_id=' . $school_id . '&year_id=' . $year_id . '&exam_id=' . $exam_id . '&class_id=' . $class_id . '&section_id=' . $section_id . '&subject_id=' . $subject_id . '&result_source=' . $result_source);

        $html .= '<div id="analysisPrintArea">';

        // Print Header
        $html .= '<div class="print-header print-only">';
        $html .= '<h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddress) . '</p>';
        $html .= '<p>Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Subject Analysis</h3>';
        $html .= '<p><strong>Subject:</strong> ' . esc($subject->title) . ' | <strong>Class:</strong> ' . esc($className);
        if ($sectionName) {
            $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        }
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName);
        $html .= ' | <strong>Source:</strong> ' . esc($sourceLabel) . '</p>';
        $html .= '<hr style="border:1px solid #000;">';
        $html .= '</div>';

        $html .= '<div class="row mt-3">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="card">';
        $html .= '<div class="card-header d-flex justify-content-between align-items-center no-print">';
        $html .= '<h5 class="mb-0"><i class="bi bi-graph-up"></i> Subject Analysis</h5>';
        $html .= '<div>';
        $html .= '<a href="' . $pdfUrl . '" class="btn btn-sm btn-danger me-2"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>';
        $html .= '<button type="button" class="btn btn-sm btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="card-body">';

        // Subject Info Bar
        $html .= '<div class="alert alert-info no-print">';
        $html .= '<strong>Subject:</strong> ' . esc($subject->title) . ' &nbsp;|&nbsp; ';
        $html .= '<strong>Class:</strong> ' . esc($className) . ' &nbsp;|&nbsp; ';
        if ($sectionName) {
            $html .= '<strong>Section:</strong> ' . esc($sectionName) . ' &nbsp;|&nbsp; ';
        }
        $html .= '<strong>Session:</strong> ' . esc($sessionName) . ' &nbsp;|&nbsp; ';
        $html .= '<strong>Source:</strong> ' . esc($sourceLabel);
        $html .= '</div>';

        // Statistics Cards Row
        $html .= '<div class="row">';
        
        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item">';
        $html .= '<div class="stat-value">' . $totalStudents . '</div>';
        $html .= '<div class="stat-label">Total Students</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item">';
        $html .= '<div class="stat-value">' . $appeared . '</div>';
        $html .= '<div class="stat-label">Appeared</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item pass">';
        $html .= '<div class="stat-value">' . $passed . '</div>';
        $html .= '<div class="stat-label">Passed</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item fail">';
        $html .= '<div class="stat-value">' . $failed . '</div>';
        $html .= '<div class="stat-label">Failed</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item">';
        $html .= '<div class="stat-value">' . $absent . '</div>';
        $html .= '<div class="stat-label">Absent</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item pass">';
        $html .= '<div class="stat-value">' . number_format($passRate, 2) . '%</div>';
        $html .= '<div class="stat-label">Pass Rate</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item high">';
        $html .= '<div class="stat-value">' . number_format($averageMark, 2) . '</div>';
        $html .= '<div class="stat-label">Average Mark</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item high">';
        $html .= '<div class="stat-value">' . number_format($highestMark, 2) . '</div>';
        $html .= '<div class="stat-label">Highest Mark</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-md-3 col-6">';
        $html .= '<div class="stat-item low">';
        $html .= '<div class="stat-value">' . number_format($lowestMark, 2) . '</div>';
        $html .= '<div class="stat-label">Lowest Mark</div>';
        $html .= '</div></div>';

        $html .= '</div>'; // end row

        // Grade Distribution Table
        $html .= '<div class="row mt-4">';
        $html .= '<div class="col-md-6">';
        $html .= '<div class="analysis-card">';
        $html .= '<h4><i class="bi bi-pie-chart"></i> Grade Distribution</h4>';
        
        if (!empty($sortedGrades)) {
            $html .= '<table class="table table-bordered table-hover">';
            $html .= '<thead><tr style="background:#1a73e8;color:#fff;">';
            $html .= '<th style="padding:8px;border:1px solid #ccc;">Grade</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">Students</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">Percentage</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($sortedGrades as $grade => $count) {
                $pct = $appeared > 0 ? round(($count / $appeared) * 100, 2) : 0;
                $html .= '<tr>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;font-weight:bold;">' . esc($grade) . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;">' . $count . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;">' . number_format($pct, 2) . '%</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html .= '<p class="text-muted">No grade data available.</p>';
        }

        $html .= '</div></div>';

        // Mark Distribution Summary
        $html .= '<div class="col-md-6">';
        $html .= '<div class="analysis-card">';
        $html .= '<h4><i class="bi bi-bar-chart"></i> Mark Summary</h4>';
        $html .= '<table class="table table-bordered">';
        $html .= '<thead><tr style="background:#1a73e8;color:#fff;">';
        $html .= '<th style="padding:8px;border:1px solid #ccc;">Metric</th>';
        $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">Value</th>';
        $html .= '</tr></thead><tbody>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Total Students</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;">' . $totalStudents . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Appeared</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . $appeared . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Passed</td><td style="padding:5px;border:1px solid #ccc;text-align:center;color:#28a745;font-weight:bold;">' . $passed . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Failed</td><td style="padding:5px;border:1px solid #ccc;text-align:center;color:#dc3545;font-weight:bold;">' . $failed . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Absent</td><td style="padding:5px;border:1px solid #ccc;text-align:center;">' . $absent . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;font-weight:bold;">Pass Rate</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;color:#28a745;">' . number_format($passRate, 2) . '%</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Average Mark</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;">' . number_format($averageMark, 2) . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Highest Mark</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;color:#17a2b8;">' . number_format($highestMark, 2) . '</td></tr>';
        $html .= '<tr><td style="padding:5px;border:1px solid #ccc;">Lowest Mark</td><td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;color:#ffc107;">' . number_format($lowestMark, 2) . '</td></tr>';
        $html .= '</tbody></table>';
        $html .= '</div></div>';

        $html .= '</div>'; // end row

        $html .= '</div>'; // end card-body
        $html .= '<div class="card-footer text-muted no-print">';
        $html .= '<strong>Subject:</strong> ' . esc($subject->title) . ' | ';
        $html .= '<strong>Class:</strong> ' . esc($className);
        if ($sectionName) {
            $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        }
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName);
        $html .= ' | <strong>Source:</strong> ' . esc($sourceLabel);
        $html .= '</div>';
        $html .= '</div>'; // end card
        $html .= '</div>'; // end col
        $html .= '</div>'; // end row

        // Print footer
        $html .= '<div class="print-footer print-only">';
        $html .= '<p>Generated on: ' . date('d-m-Y h:i A') . '</p>';
        $html .= '</div>';

        $html .= '</div>'; // end analysisPrintArea

        return $html;
    }

    // ======================================================================
    // AJAX ENDPOINTS
    // ======================================================================

    public function ajaxGetYears()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/subject-analysis');
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
            return redirect()->to('examination/reports/subject-analysis');
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
            return redirect()->to('examination/reports/subject-analysis');
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
            return redirect()->to('examination/reports/subject-analysis');
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

    public function ajaxGetSubjects()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/subject-analysis');
        }
        $school_id = (int) $this->request->getPost('school_id');
        $class_id  = (int) $this->request->getPost('class_id');
        $subjects = [];

        if ($school_id && $class_id) {
            $subjectRecords = $this->getSubjectsForClass($school_id, $class_id);
            foreach ($subjectRecords as $s) {
                $subjects[$s->id] = $s->title;
            }
        } elseif ($school_id) {
            $subjectRecords = $this->SubjectModel
                ->where('school_id', $school_id)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
            foreach ($subjectRecords as $s) {
                $subjects[$s->id] = $s->title;
            }
        }

        return $this->response->setJSON(['success' => true, 'data' => $subjects]);
    }

    public function ajaxGenerateAnalysis()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/subject-analysis');
        }

        $school_id     = (int) $this->request->getPost('school_id');
        $year_id       = (int) $this->request->getPost('year_id');
        $exam_id       = (int) $this->request->getPost('exam_id');
        $class_id      = (int) $this->request->getPost('class_id');
        $section_id    = (int) $this->request->getPost('section_id');
        $subject_id    = (int) $this->request->getPost('subject_id');
        $result_source = $this->request->getPost('result_source') ?? 'exam';

        if (!$school_id || !$year_id || !$class_id || !$subject_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'School, Session, Class, and Subject are required']);
        }

        if ($result_source === 'exam' && !$exam_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Please select an Exam when using Specific Exam mode']);
        }

        $user_id = $this->getUserId();
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);

        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        $html = $this->generateSubjectAnalysis($school_id, $year_id, $exam_id, $class_id, $section_id, $subject_id, $result_source);

        return $this->response->setJSON(['success' => true, 'html' => $html]);
    }

    /**
     * Download Subject Analysis as PDF
     */
    public function downloadPdf()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $school_id     = (int) $this->request->getGet('school_id');
        $year_id       = (int) $this->request->getGet('year_id');
        $exam_id       = (int) $this->request->getGet('exam_id');
        $class_id      = (int) $this->request->getGet('class_id');
        $section_id    = (int) $this->request->getGet('section_id');
        $subject_id    = (int) $this->request->getGet('subject_id');
        $result_source = $this->request->getGet('result_source') ?? 'exam';

        if (!$school_id || !$year_id || !$class_id || !$subject_id) {
            return redirect()->to('examination/reports/subject-analysis')->with('error', 'All required fields must be selected');
        }

        if ($result_source === 'exam' && !$exam_id) {
            return redirect()->to('examination/reports/subject-analysis')->with('error', 'Please select an Exam');
        }

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return redirect()->to('examination/reports/subject-analysis')->with('error', 'Access denied');
        }

        // Get info
        $subject = $this->SubjectModel->find($subject_id);
        $class = $this->ClassModel->find($class_id);
        $year = $this->YearModel->find($year_id);
        $exam = $exam_id ? $this->ExamModel->find($exam_id) : null;
        $section = $section_id ? $this->SectionModel->find($section_id) : null;
        $school = $this->SchoolModel->find($school_id);

        $subjectTitle = $subject ? $subject->title : '';
        $className    = $class ? $class->title : '';
        $sessionName  = $year ? $year->title : '';
        $examName     = $exam ? $exam->title : '';
        $sectionName  = $section ? $section->title : '';
        $schoolName   = $school ? $school->name : '';
        $schoolAddr   = $school ? $school->address : '';
        $schoolPhone  = $school ? $school->phone : '';
        $schoolEmail  = $school ? $school->email : '';
        $sourceLabel  = $result_source === 'exam' ? $examName : 'Aggregate Result';

        // Get enrolled students
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);

        if ($section_id) {
            $enrollments->where('student_enrollments.section_id', $section_id);
        }

        $enrollments = $enrollments->orderBy('student_enrollments.roll_no', 'ASC')->findAll();

        $totalStudents = count($enrollments);
        $appeared = 0;
        $passed = 0;
        $failed = 0;
        $absent = 0;
        $marks = [];
        $gradeDistribution = [];

        foreach ($enrollments as $enr) {
            $subjectResult = $this->SubjectResultModel
                ->where('school_id', $school_id);

            if ($result_source === 'exam' && $exam_id) {
                $subjectResult->where('exam_id', $exam_id);
            }

            $subjectResult = $subjectResult
                ->where('class_id', $class_id)
                ->where('student_id', $enr->student_id)
                ->where('subject_id', $subject_id)
                ->first();

            if ($subjectResult) {
                $obtained = $subjectResult->obtained_mark ?? 0;
                $marks[] = $obtained;
                $appeared++;

                $isFail = $subjectResult->is_fail ?? 0;
                if ($isFail == 0 && $obtained > 0) {
                    $passed++;
                } else {
                    $failed++;
                }

                $grade = $subjectResult->letter_grade ?? $subjectResult->grade ?? ($isFail ? 'F' : '-');
                if (!isset($gradeDistribution[$grade])) {
                    $gradeDistribution[$grade] = 0;
                }
                $gradeDistribution[$grade]++;
            } else {
                $absent++;
            }
        }

        $averageMark = !empty($marks) ? round(array_sum($marks) / count($marks), 2) : 0;
        $highestMark = !empty($marks) ? max($marks) : 0;
        $lowestMark  = !empty($marks) ? min($marks) : 0;
        $passRate    = $appeared > 0 ? round(($passed / $appeared) * 100, 2) : 0;

        // Build PDF HTML
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
            td { padding: 4px; border: 1px solid #000; font-size: 10px; }
            td.center { text-align: center; }
            td.bold { font-weight: bold; }
            td.label { font-weight: bold; width: 200px; }
            .footer { text-align: center; margin-top: 15px; font-size: 10px; color: #666; }
        </style>
        </head><body>';

        $html .= '<div class="header">';
        $html .= '<h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddr) . '</p>';
        $html .= '<p>Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Subject Analysis</h3>';
        $html .= '<p><strong>Subject:</strong> ' . esc($subjectTitle) . ' | <strong>Class:</strong> ' . esc($className);
        if ($sectionName) {
            $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        }
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName);
        $html .= ' | <strong>Source:</strong> ' . esc($sourceLabel) . '</p>';
        $html .= '<hr>';
        $html .= '</div>';

        // Statistics table
        $html .= '<table>';
        $html .= '<tr><td class="label">Total Students</td><td class="center bold">' . $totalStudents . '</td></tr>';
        $html .= '<tr><td class="label">Appeared</td><td class="center">' . $appeared . '</td></tr>';
        $html .= '<tr><td class="label">Passed</td><td class="center bold" style="color:#28a745;">' . $passed . '</td></tr>';
        $html .= '<tr><td class="label">Failed</td><td class="center bold" style="color:#dc3545;">' . $failed . '</td></tr>';
        $html .= '<tr><td class="label">Absent</td><td class="center">' . $absent . '</td></tr>';
        $html .= '<tr><td class="label">Pass Rate</td><td class="center bold" style="color:#28a745;">' . number_format($passRate, 2) . '%</td></tr>';
        $html .= '<tr><td class="label">Average Mark</td><td class="center bold">' . number_format($averageMark, 2) . '</td></tr>';
        $html .= '<tr><td class="label">Highest Mark</td><td class="center bold" style="color:#17a2b8;">' . number_format($highestMark, 2) . '</td></tr>';
        $html .= '<tr><td class="label">Lowest Mark</td><td class="center bold" style="color:#ffc107;">' . number_format($lowestMark, 2) . '</td></tr>';
        $html .= '</table>';

        // Grade distribution table
        if (!empty($gradeDistribution)) {
            $html .= '<h4 style="margin-top:20px;">Grade Distribution</h4>';
            $html .= '<table>';
            $html .= '<thead><tr><th>Grade</th><th>Students</th><th>Percentage</th></tr></thead><tbody>';
            $gradeOrder = ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'F'];
            foreach ($gradeOrder as $g) {
                if (isset($gradeDistribution[$g])) {
                    $pct = $appeared > 0 ? round(($gradeDistribution[$g] / $appeared) * 100, 2) : 0;
                    $html .= '<tr><td class="center bold">' . $g . '</td><td class="center">' . $gradeDistribution[$g] . '</td><td class="center">' . number_format($pct, 2) . '%</td></tr>';
                }
            }
            $html .= '</tbody></table>';
        }

        $html .= '<div class="footer">';
        $html .= '<p>Generated on: ' . date('d-m-Y h:i A') . '</p>';
        $html .= '</div>';

        $html .= '</body></html>';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Subject_Analysis_' . $subjectTitle . '_' . $className . '_' . $sessionName . '.pdf';
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
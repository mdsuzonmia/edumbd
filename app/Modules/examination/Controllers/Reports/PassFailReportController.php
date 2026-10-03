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

class PassFailReportController extends BaseReportController
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
        $result_status = $this->request->getGet('result_status') ?? 'all';

        $data = compact('school_id', 'year_id', 'exam_id', 'class_id', 'section_id', 'result_source', 'result_status');
        $data['school_list']   = $this->getSchoolDropdown();
        $data['year_list']     = [];
        $data['exam_list']     = [];
        $data['class_list']    = [];
        $data['section_list']  = [];
        $data['report_html']   = '';

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
                return redirect()->to('examination/reports/pass-fail-report')->with('error', 'Access denied.');
            }
            $data['report_html'] = $this->generatePassFailReport(
                $school_id, $year_id, $exam_id, $class_id, $section_id, $result_source, $result_status
            );
        }

        return $this->renderWithHeaderFooter('Pass/Fail Report', 'App\Modules\examination\Views\reports\pass_fail_report', $data);
    }

    protected function generatePassFailReport(
        int $school_id, int $year_id, int $exam_id, int $class_id,
        int $section_id = 0, string $result_source = 'exam', string $result_status = 'all'
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

        // Get enrolled students
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);
        if ($section_id) $enrollments->where('student_enrollments.section_id', $section_id);
        $enrollments = $enrollments->orderBy('student_enrollments.roll_no', 'ASC')->findAll();

        if (empty($enrollments)) {
            return '<div class="alert alert-info">No students found for the selected filters.</div>';
        }

        $totalStudents = count($enrollments);
        $passed = 0; $failed = 0; $absent = 0; $incomplete = 0;
        $studentRows = [];

        foreach ($enrollments as $enr) {
            $studentName = trim(($enr->first_name ?? '') . ' ' . ($enr->middle_name ?? '') . ' ' . ($enr->last_name ?? ''));
            $studentName = preg_replace('/\s+/', ' ', $studentName);
            $rollNo = $enr->roll_no ?? '';
            $total = 0; $percentage = 0; $gpa = 0; $grade = '-'; $status = 'ABSENT'; $failedSubjects = 0; $failedList = [];

            if ($result_source === 'exam' && $exam_id) {
                $er = $this->ExamResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('student_id', $enr->student_id)
                    ->first();
                if ($er) {
                    $total = $er->obtained_marks ?? 0;
                    $percentage = $er->percentage ?? 0;
                    $gpa = $er->gpa ?? 0;
                    $grade = $er->letter_grade ?? $er->grade ?? '-';
                    $status = strtoupper($er->result_status ?? 'FAIL');
                    if ($status === 'PASS') $passed++;
                    elseif ($status === 'FAIL') {
                        $failed++;
                        // Get failed subjects
                        $failedSubjects = $this->getFailedSubjects($school_id, $exam_id, $class_id, $enr->student_id);
                    }
                    else $incomplete++;
                } else {
                    $absent++;
                }
            } else {
                // Aggregate
                $srs = $this->SubjectResultModel
                    ->where('school_id', $school_id)
                    ->where('class_id', $class_id)
                    ->where('student_id', $enr->student_id)
                    ->where('session_id', $year_id)
                    ->findAll();
                if (!empty($srs)) {
                    $totalObtained = 0; $totalGp = 0; $cnt = 0; $hasFail = false;
                    foreach ($srs as $sr) {
                        $totalObtained += $sr->obtained_mark ?? 0;
                        $totalGp += $sr->grade_point ?? 0;
                        $cnt++;
                        if ($sr->is_fail == 1) {
                            $hasFail = true;
                            $failedSubjects++;
                            $sub = $this->SubjectModel->find($sr->subject_id);
                            $failedList[] = [
                                'name' => $sub ? $sub->title : 'Subject ' . $sr->subject_id,
                                'obtained' => $sr->obtained_mark ?? 0,
                                'full' => $sr->full_mark ?? 100
                            ];
                        }
                    }
                    $total = $totalObtained;
                    $gpa = $cnt > 0 ? round($totalGp / $cnt, 2) : 0;
                    $grade = $srs[0]->letter_grade ?? $srs[0]->grade ?? '-';
                    if (!$hasFail) { $status = 'PASS'; $passed++; }
                    else { $status = 'FAIL'; $failed++; }
                } else {
                    $absent++;
                }
            }

            // Apply result status filter
            if ($result_status !== 'all') {
                $filterStatus = strtoupper($result_status);
                if ($filterStatus === 'FAIL' && $status !== 'FAIL') continue;
                if ($filterStatus === 'PASS' && $status !== 'PASS') continue;
                if ($filterStatus === 'ABSENT' && $status !== 'ABSENT') continue;
                if ($filterStatus === 'INCOMPLETE' && $status === 'PASS' || $status === 'FAIL' || $status === 'ABSENT') continue;
            }

            $studentRows[] = [
                'roll_no' => $rollNo,
                'name' => $studentName,
                'total' => $total,
                'percentage' => $percentage,
                'gpa' => $gpa,
                'grade' => $grade,
                'failed_subjects' => $failedSubjects,
                'status' => $status,
                'failed_list' => $failedList
            ];
        }

        $passRate = $totalStudents > 0 ? round(($passed / $totalStudents) * 100, 2) : 0;
        $failRate = $totalStudents > 0 ? round(($failed / $totalStudents) * 100, 2) : 0;

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
                #pfPrintArea, #pfPrintArea * { visibility: visible; }
                #pfPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
                .no-print { display: none !important; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }
                .print-header { text-align: center; margin-bottom: 20px; }
                .print-header h2 { margin: 0; font-size: 20px; }
                .print-header p { margin: 3px 0; font-size: 13px; }
                .print-footer { text-align: center; margin-top: 15px; font-size: 12px; }
            }
            @media screen { .print-only { display: none; } }
            .stat-item { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px; }
            .stat-item .stat-value { font-size: 24px; font-weight: bold; color: #1a73e8; }
            .stat-item .stat-label { font-size: 13px; color: #6c757d; margin-top: 5px; }
            .stat-item.pass .stat-value { color: #28a745; }
            .stat-item.fail .stat-value { color: #dc3545; }
            .stat-item.absent .stat-value { color: #ffc107; }
            .badge-pass { background: #28a745; color: #fff; padding: 4px 8px; border-radius: 4px; }
            .badge-fail { background: #dc3545; color: #fff; padding: 4px 8px; border-radius: 4px; }
            .badge-absent { background: #ffc107; color: #000; padding: 4px 8px; border-radius: 4px; }
            .badge-incomplete { background: #6c757d; color: #fff; padding: 4px 8px; border-radius: 4px; }
            .failed-subjects { margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 4px; }
            .failed-subjects table { width: 100%; }
        </style>';

        $pdfUrl = base_url('examination/reports/pass-fail-report/download-pdf?school_id=' . $school_id . '&year_id=' . $year_id . '&exam_id=' . $exam_id . '&class_id=' . $class_id . '&section_id=' . $section_id . '&result_source=' . $result_source . '&result_status=' . $result_status);

        $html .= '<div id="pfPrintArea">';

        // Print Header
        $html .= '<div class="print-header print-only">';
        $html .= '<h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddress) . '</p>';
        $html .= '<p>Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Pass/Fail Report</h3>';
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
        $html .= '<h5 class="mb-0"><i class="bi bi-check-circle"></i> Pass/Fail Report</h5>';
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

        // Summary cards
        $html .= '<div class="row">';
        $html .= '<div class="col-md-2 col-6"><div class="stat-item"><div class="stat-value">' . $totalStudents . '</div><div class="stat-label">Total Students</div></div></div>';
        $html .= '<div class="col-md-2 col-6"><div class="stat-item pass"><div class="stat-value">' . $passed . '</div><div class="stat-label">Passed</div></div></div>';
        $html .= '<div class="col-md-2 col-6"><div class="stat-item fail"><div class="stat-value">' . $failed . '</div><div class="stat-label">Failed</div></div></div>';
        $html .= '<div class="col-md-2 col-6"><div class="stat-item absent"><div class="stat-value">' . $absent . '</div><div class="stat-label">Absent</div></div></div>';
        $html .= '<div class="col-md-2 col-6"><div class="stat-item"><div class="stat-value">' . $incomplete . '</div><div class="stat-label">Incomplete</div></div></div>';
        $html .= '<div class="col-md-2 col-6"><div class="stat-item pass"><div class="stat-value">' . number_format($passRate, 2) . '%</div><div class="stat-label">Pass Rate</div></div></div>';
        $html .= '</div>';

        // Student list table
        $html .= '<div class="row mt-4">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="stats-card">';
        $html .= '<h4><i class="bi bi-people"></i> Student List</h4>';

        if (!empty($studentRows)) {
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-bordered table-hover">';
            $html .= '<thead><tr style="background:#1a73e8;color:#fff;">';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">Roll</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:left;">Student</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">Total</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">%</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">GPA</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">Grade</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">Failed Subjects</th>';
            $html .= '<th style="padding:8px;border:1px solid #ccc;text-align:center;">Status</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($studentRows as $row) {
                $statusClass = strtolower($row['status']);
                $html .= '<tr>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;">' . esc($row['roll_no']) . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;">' . esc($row['name']) . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($row['total'] > 0 ? $row['total'] : '—') . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($row['percentage'] > 0 ? number_format($row['percentage'], 2) . '%' : '—') . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($row['gpa'] > 0 ? number_format($row['gpa'], 2) : '—') . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;font-weight:bold;">' . esc($row['grade']) . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;">' . ($row['failed_subjects'] > 0 ? '<button class="btn btn-sm btn-warning view-details" data-student="' . esc($row['name']) . '" data-failed=\'' . json_encode($row['failed_list']) . '\'>View Details</button>' : '—') . '</td>';
                $html .= '<td style="padding:5px;border:1px solid #ccc;text-align:center;"><span class="badge-' . $statusClass . '">' . $row['status'] . '</span></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            $html .= '</div>';
        } else {
            $html .= '<p class="text-muted">No students found for the selected filters.</p>';
        }

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

        // Add modal for failed subjects
        $html .= '<div class="modal fade" id="failedSubjectsModal" tabindex="-1" role="dialog">';
        $html .= '<div class="modal-dialog" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header"><h5 class="modal-title">Failed Subjects</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>';
        $html .= '<div class="modal-body" id="failedSubjectsContent"></div>';
        $html .= '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>';
        $html .= '</div></div></div>';

        $html .= '<script>
            $(document).ready(function() {
                $(".view-details").click(function() {
                    const studentName = $(this).data("student");
                    const failedList = $(this).data("failed");
                    let html = "<p><strong>" + studentName + "</strong></p>";
                    html += "<table class=\"table table-bordered\"><thead><tr><th>Subject</th><th>Obtained</th><th>Full Marks</th></tr></thead><tbody>";
                    failedList.forEach(function(item) {
                        html += "<tr><td>" + item.name + "</td><td>" + item.obtained + "</td><td>" + item.full + "</td></tr>";
                    });
                    html += "</tbody></table>";
                    $("#failedSubjectsContent").html(html);
                    $("#failedSubjectsModal").modal("show");
                });
            });
        </script>';

        return $html;
    }

    protected function getFailedSubjects(int $school_id, int $exam_id, int $class_id, int $student_id): int
    {
        $count = 0;
        $results = $this->SubjectResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->where('student_id', $student_id)
            ->where('is_fail', 1)
            ->findAll();
        return count($results);
    }

    // ======================================================================
    // AJAX ENDPOINTS
    // ======================================================================

    public function ajaxGetYears()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/pass-fail-report');
        $school_id = (int) $this->request->getPost('school_id');
        $years = $school_id ? $this->getActiveOptions($school_id, 'YearModel') : [];
        return $this->response->setJSON(['success' => true, 'data' => $years]);
    }

    public function ajaxGetExams()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/pass-fail-report');
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
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/pass-fail-report');
        $school_id = (int) $this->request->getPost('school_id');
        $classes = $school_id ? $this->getActiveOptions($school_id, 'ClassModel') : [];
        return $this->response->setJSON(['success' => true, 'data' => $classes]);
    }

    public function ajaxGetSections()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/pass-fail-report');
        $school_id = (int) $this->request->getPost('school_id');
        $sections = [];
        if ($school_id) {
            $records = $this->SectionModel->where('school_id', $school_id)->where('status', 1)->orderBy('title', 'ASC')->findAll();
            foreach ($records as $s) $sections[$s->id] = $s->title;
        }
        return $this->response->setJSON(['success' => true, 'data' => $sections]);
    }

    public function ajaxGenerateReport()
    {
        if (!$this->request->isAJAX()) return redirect()->to('examination/reports/pass-fail-report');

        $school_id  = (int) $this->request->getPost('school_id');
        $year_id    = (int) $this->request->getPost('year_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');
        $result_source = $this->request->getPost('result_source') ?? 'exam';
        $result_status = $this->request->getPost('result_status') ?? 'all';

        if (!$school_id || !$year_id || !$class_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'School, Session, and Class are required']);
        }

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        $html = $this->generatePassFailReport($school_id, $year_id, $exam_id, $class_id, $section_id, $result_source, $result_status);
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
        $result_status = $this->request->getGet('result_status') ?? 'all';

        if (!$school_id || !$year_id || !$class_id) {
            return redirect()->to('examination/reports/pass-fail-report')->with('error', 'All required fields must be selected');
        }

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) return redirect()->to('examination/reports/pass-fail-report')->with('error', 'Access denied');

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

        // Get enrollments
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);
        if ($section_id) $enrollments->where('student_enrollments.section_id', $section_id);
        $enrollments = $enrollments->orderBy('student_enrollments.roll_no', 'ASC')->findAll();

        $totalStudents = count($enrollments);
        $passed = 0; $failed = 0; $absent = 0; $incomplete = 0;
        $studentRows = [];

        foreach ($enrollments as $enr) {
            $studentName = trim(($enr->first_name ?? '') . ' ' . ($enr->middle_name ?? '') . ' ' . ($enr->last_name ?? ''));
            $studentName = preg_replace('/\s+/', ' ', $studentName);
            $rollNo = $enr->roll_no ?? '';
            $total = 0; $percentage = 0; $gpa = 0; $grade = '-'; $status = 'ABSENT'; $failedSubjects = 0;

            if ($result_source === 'exam' && $exam_id) {
                $er = $this->ExamResultModel->where('school_id', $school_id)->where('exam_id', $exam_id)->where('class_id', $class_id)->where('student_id', $enr->student_id)->first();
                if ($er) {
                    $total = $er->obtained_marks ?? 0;
                    $percentage = $er->percentage ?? 0;
                    $gpa = $er->gpa ?? 0;
                    $grade = $er->letter_grade ?? $er->grade ?? '-';
                    $status = strtoupper($er->result_status ?? 'FAIL');
                    if ($status === 'PASS') $passed++;
                    elseif ($status === 'FAIL') $failed++;
                    else $incomplete++;
                } else { $absent++; }
            } else {
                $srs = $this->SubjectResultModel->where('school_id', $school_id)->where('class_id', $class_id)->where('student_id', $enr->student_id)->where('session_id', $year_id)->findAll();
                if (!empty($srs)) {
                    $totalObtained = 0; $totalGp = 0; $cnt = 0; $hasFail = false;
                    foreach ($srs as $sr) {
                        $totalObtained += $sr->obtained_mark ?? 0;
                        $totalGp += $sr->grade_point ?? 0;
                        $cnt++;
                        if ($sr->is_fail == 1) { $hasFail = true; $failedSubjects++; }
                    }
                    $total = $totalObtained;
                    $gpa = $cnt > 0 ? round($totalGp / $cnt, 2) : 0;
                    $grade = $srs[0]->letter_grade ?? $srs[0]->grade ?? '-';
                    if (!$hasFail) { $status = 'PASS'; $passed++; }
                    else { $status = 'FAIL'; $failed++; }
                } else { $absent++; }
            }

            $studentRows[] = [
                'roll_no' => $rollNo, 'name' => $studentName, 'total' => $total,
                'percentage' => $percentage, 'gpa' => $gpa, 'grade' => $grade,
                'failed_subjects' => $failedSubjects, 'status' => $status
            ];
        }

        $passRate = $totalStudents > 0 ? round(($passed / $totalStudents) * 100, 2) : 0;
        $failRate = $totalStudents > 0 ? round(($failed / $totalStudents) * 100, 2) : 0;

        // Build PDF
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
            .footer { text-align: center; margin-top: 15px; font-size: 10px; color: #666; }
        </style></head><body>';

        $html .= '<div class="header"><h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddr) . ' | Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Pass/Fail Report</h3>';
        $html .= '<p><strong>Class:</strong> ' . esc($className);
        if ($sectionName) $html .= ' | <strong>Section:</strong> ' . esc($sectionName);
        $html .= ' | <strong>Session:</strong> ' . esc($sessionName) . ' | <strong>Source:</strong> ' . esc($sourceLabel) . '</p><hr></div>';

        $html .= '<table>';
        $html .= '<tr><td class="label">Total Students</td><td class="center bold">' . $totalStudents . '</td></tr>';
        $html .= '<tr><td class="label">Passed</td><td class="center bold" style="color:#28a745;">' . $passed . '</td></tr>';
        $html .= '<tr><td class="label">Failed</td><td class="center bold" style="color:#dc3545;">' . $failed . '</td></tr>';
        $html .= '<tr><td class="label">Absent</td><td class="center">' . $absent . '</td></tr>';
        $html .= '<tr><td class="label">Incomplete</td><td class="center">' . $incomplete . '</td></tr>';
        $html .= '<tr><td class="label">Pass Rate</td><td class="center bold" style="color:#28a745;">' . number_format($passRate, 2) . '%</td></tr>';
        $html .= '<tr><td class="label">Fail Rate</td><td class="center bold" style="color:#dc3545;">' . number_format($failRate, 2) . '%</td></tr>';
        $html .= '</table>';

        if (!empty($studentRows)) {
            $html .= '<h4 style="margin-top:20px;">Student List</h4><table><thead><tr><th>Roll</th><th>Student</th><th>Total</th><th>%</th><th>GPA</th><th>Grade</th><th>Failed Subjects</th><th>Status</th></tr></thead><tbody>';
            foreach ($studentRows as $row) {
                $html .= '<tr>';
                $html .= '<td class="center">' . $row['roll_no'] . '</td>';
                $html .= '<td>' . $row['name'] . '</td>';
                $html .= '<td class="center">' . ($row['total'] > 0 ? $row['total'] : '—') . '</td>';
                $html .= '<td class="center">' . ($row['percentage'] > 0 ? number_format($row['percentage'], 2) . '%' : '—') . '</td>';
                $html .= '<td class="center">' . ($row['gpa'] > 0 ? number_format($row['gpa'], 2) : '—') . '</td>';
                $html .= '<td class="center bold">' . $row['grade'] . '</td>';
                $html .= '<td class="center">' . $row['failed_subjects'] . '</td>';
                $html .= '<td class="center">' . $row['status'] . '</td>';
                $html .= '</tr>';
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
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Pass_Fail_Report_' . $className . '_' . $sessionName . '.pdf';
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
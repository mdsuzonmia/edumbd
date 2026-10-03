<?php

namespace App\Modules\examination\Controllers\Reports;

use App\Models\SchoolModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsYearModel;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Modules\examination\Models\StudentSubjectModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\FinalResultModel;
use App\Modules\examination\Models\FinalResultSubjectModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class TabulationSheetController extends BaseReportController
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
    protected FinalResultModel $FinalResultModel;
    protected FinalResultSubjectModel $FinalResultSubjectModel;
    protected StudentSubjectModel $StudentSubjectModel;

    public function __construct()
    {
        $this->SchoolModel          = new SchoolModel();
        $this->ExamModel               = new ExamModel();
        $this->ClassModel              = new AcademicsClassesModel();
        $this->SectionModel            = new AcademicsSectionModel();
        $this->YearModel               = new AcademicsYearModel();
        $this->ExamResultModel         = new ExamResultModel();
        $this->SubjectResultModel      = new SubjectResultModel();
        $this->StudentModel            = new StudentModel();
        $this->EnrollmentModel         = new StudentEnrollmentModel();
        $this->SubjectModel            = new SubjectModel();
        $this->FinalResultModel        = new FinalResultModel();
        $this->FinalResultSubjectModel = new FinalResultSubjectModel();
        $this->StudentSubjectModel  = new StudentSubjectModel();
    }

    /**
     * Get active options for dropdowns.
     */
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
     * Build a grouped column list from a flat list of subjects.
     *
     * Subjects sharing the same `combine_group` are merged into a single
     * "combined" column (one header + one mark per student), matching how
     * combined subjects are treated in the transcript. Standalone subjects
     * keep their own column.
     */
    protected function buildGroupedColumns(array $subjects): array
    {
        $columns = [];

        foreach ($subjects as $subject) {
            $group = trim((string) ($subject->combine_group ?? ''));

            if ($group !== '') {
                $key = 'group_' . $group;
                if (!isset($columns[$key])) {
                    $columns[$key] = [
                        'type'        => 'group',
                        'group'       => $group,
                        'title'       => $group,
                        'subject_ids' => [],
                        'subjects'    => [],
                    ];
                }
                $columns[$key]['subject_ids'][] = (int) $subject->id;
                $columns[$key]['subjects'][]    = $subject;
            } else {
                $key = 'subject_' . $subject->id;
                $columns[$key] = [
                    'type'        => 'single',
                    'group'       => '',
                    'title'       => $subject->title,
                    'subject_ids' => [(int) $subject->id],
                    'subjects'    => [$subject],
                ];
            }
        }

        return $columns;
    }

    /**
     * Build the shared tabulation sheet data (students, grouped columns,
     * per-student marks and summary columns). Used by both the on-screen
     * tabulation sheet and the downloadable PDF so they always match.
     */
    protected function buildTabulationSheetData(int $school_id, int $year_id, int $exam_id, int $class_id): array
    {
        // Get all subjects for this school (same order as the on-screen sheet)
        $subjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('order_number', 'ASC')
            ->findAll();

        $subjectsEmpty = empty($subjects);

        // Group combined subjects (same combine_group) into single columns
        $columns = $this->buildGroupedColumns($subjects);

        // Get all enrolled students in this class (roll number order)
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1)
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->findAll();

        $rows = [];
        foreach ($enrollments as $enr) {
            $studentName = trim(($enr->first_name ?? '') . ' ' . ($enr->middle_name ?? '') . ' ' . ($enr->last_name ?? ''));
            $studentName = preg_replace('/\s+/', ' ', $studentName);

            // Per-column marks (same logic as the on-screen sheet)
            $marks = [];
            foreach ($columns as $key => $column) {
                if ($column['type'] === 'group') {
                    // Combined group - show the group's average mark once
                    $markText = '';
                    foreach ($column['subject_ids'] as $sid) {
                        $subjectResult = $this->SubjectResultModel
                            ->where('school_id', $school_id)
                            ->where('exam_id', $exam_id)
                            ->where('class_id', $class_id)
                            ->where('student_id', $enr->student_id)
                            ->where('subject_id', $sid)
                            ->first();

                        if ($subjectResult && $subjectResult->average_mark !== null && $subjectResult->average_mark !== '' && (float) $subjectResult->average_mark > 0) {
                            $markText = round((float) $subjectResult->average_mark, 2);
                            break;
                        }
                    }
                    $marks[$key] = $markText;
                } else {
                    // Standalone subject - show obtained mark
                    $subjectResult = $this->SubjectResultModel
                        ->where('school_id', $school_id)
                        ->where('exam_id', $exam_id)
                        ->where('class_id', $class_id)
                        ->where('student_id', $enr->student_id)
                        ->where('subject_id', $column['subject_ids'][0])
                        ->first();

                    $markText = '';
                    if ($subjectResult) {
                        $obtained_mark = $subjectResult->obtained_mark;
                        if ($obtained_mark !== null && $obtained_mark !== '' && $obtained_mark !== '0') {
                            $markText = round((float) $obtained_mark, 2);
                        }
                    }
                    $marks[$key] = $markText;
                }
            }

            // Summary columns from the exam result
            $examResult = $this->ExamResultModel
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('student_id', $enr->student_id)
                ->first();

            $totalText      = $examResult && $examResult->obtained_marks !== null ? (float) $examResult->obtained_marks : '';
            $percentageText = $examResult && $examResult->percentage !== null ? number_format((float) $examResult->percentage, 2) . '%' : '';
            $gpaText        = $examResult && $examResult->gpa !== null ? number_format((float) $examResult->gpa, 2) : '';
            $gradeText      = $examResult && !empty($examResult->letter_grade) ? $examResult->letter_grade : ($examResult->grade ?? '');
            $positionText   = $examResult && $examResult->exam_rank !== null ? $examResult->exam_rank : '';

            $rows[] = [
                'roll_no'      => $enr->roll_no ?? '',
                'student_name' => $studentName,
                'marks'        => $marks,
                'total'        => $totalText,
                'percentage'   => $percentageText,
                'gpa'          => $gpaText,
                'grade'        => $gradeText,
                'position'     => $positionText,
            ];
        }

        return [
            'columns'           => $columns,
            'rows'              => $rows,
            'subjects_empty'    => $subjectsEmpty,
            'enrollments_empty' => empty($enrollments),
        ];
    }

    /**
     * Render a table header label vertically for the PDF.
     *
     * Dompdf does not support CSS transform:rotate / writing-mode, so we stack
     * each character on its own line. This produces a reliable vertical header
     * that matches the look of the on-screen (browser) tabulation sheet.
     */
    protected function verticalHeaderText(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $stacked = implode('<br>', array_map('esc', $chars));

        return '<div style="text-align:center;line-height:1.1;font-weight:bold;">' . $stacked . '</div>';
    }

    /**
     * INDEX - Show filter form with Session, Exam, Class
     */
    public function index()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $school_id = (int) $this->request->getGet('school_id');
        $year_id   = (int) $this->request->getGet('year_id');
        $exam_id   = (int) $this->request->getGet('exam_id');
        $class_id  = (int) $this->request->getGet('class_id');

        $data = compact('school_id', 'year_id', 'exam_id', 'class_id');
        $data['school_list'] = $this->getSchoolDropdown();
        $data['year_list']   = [];
        $data['exam_list']   = [];
        $data['class_list']  = [];
        $data['tabulation_html'] = '';

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

        // If all filters selected, generate tabulation sheet
        if ($school_id && $year_id && $exam_id && $class_id) {
            $userSchools = $this->getUserSchools();
            $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

            if (!in_array($school_id, $schoolIds)) {
                return redirect()->to('examination/reports/tabulation-sheet')->with('error', 'Access denied.');
            }

            $data['tabulation_html'] = $this->generateTabulationSheet($school_id, $year_id, $exam_id, $class_id);
        }

        return $this->renderWithHeaderFooter('Tabulation Sheet', 'App\Modules\examination\Views\reports\tabulation_sheet', $data);
    }

    /**
     * Generate the tabulation sheet HTML for a class in a specific exam.
     */
    protected function generateTabulationSheet(int $school_id, int $year_id, int $exam_id, int $class_id): string
    {
        // Get exam info
        $exam = $this->ExamModel->find($exam_id);
        if (!$exam) {
            return '<div class="alert alert-danger">Exam not found.</div>';
        }

        // Get class info
        $class = $this->ClassModel->find($class_id);
        $className = $class ? $class->title : '';

        // Get year/session info
        $year = $this->YearModel->find($year_id);
        $sessionName = $year ? $year->title : '';

        // Build shared tabulation data (students, grouped columns, marks, summary)
        $data = $this->buildTabulationSheetData($school_id, $year_id, $exam_id, $class_id);

        if ($data['subjects_empty']) {
            return '<div class="alert alert-warning">No subjects found for this school.</div>';
        }

        if ($data['enrollments_empty']) {
            return '<div class="alert alert-info">No students found for the selected filters.</div>';
        }

        $columns = $data['columns'];
        $rows    = $data['rows'];

        // Get school info for print header
        $school = $this->SchoolModel->find($school_id);
        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';

        // Build HTML
        $html = '<style>
            body { margin: 0; padding: 0; }
            #tabulationPrintArea { margin: 0; padding: 20px; width: 100%; }
            .tabulation-table {  margin: 0px; width: 100%; border-collapse: collapse; }
            .tabulation-table th{vertical-align: bottom;}
            .tabulation-table td.td-name{text-align: left;}
            .tabulation-table th, .tabulation-table td { border: 1px solid #000; padding: 5px; font-size: 12px; text-align: center; }
            @media print {
                body * { visibility: hidden; }
                #tabulationPrintArea, #tabulationPrintArea * { visibility: visible; }
                #tabulationPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
                .no-print { display: none !important; }
                
                .print-header { text-align: center; margin-bottom: 20px; }
                .print-header h2 { margin: 0; font-size: 20px; }
                .print-header h3 { margin: 0; font-size: 16px; }
                .print-header p { margin: 3px 0; font-size: 13px; }
                .print-footer { text-align: center; margin-top: 15px; font-size: 12px; }
            }
            @media screen {
                .print-only { display: none; }
            }
        </style>';

        $html .= '<div id="tabulationPrintArea">';

        // Print Header (visible only in print)
        $html .= '<div class="print-header print-only">';
        $html .= '<h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddress) . '</p>';
        $html .= '<p>Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Tabulation Sheet</h3>';
        $html .= '<p><strong>Exam:</strong> ' . esc($exam->title) . ' | <strong>Class:</strong> ' . esc($className) . ' | <strong>Session:</strong> ' . esc($sessionName) . '</p>';
        //$html .= '<hr style="border:1px solid #000;">';
        $html .= '</div>';

        $html .= '<div class="row mt-3">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="card">';
        $pdfUrl = base_url('examination/reports/tabulation-sheet/download-pdf?school_id=' . $school_id . '&year_id=' . $year_id . '&exam_id=' . $exam_id . '&class_id=' . $class_id);
        $html .= '<div class="card-header d-flex justify-content-between align-items-center no-print">';
        $html .= '<h5 class="mb-0"><i class="bi bi-table"></i> Tabulation Sheet</h5>';
        $html .= '<div>';
        $html .= '<a href="' . $pdfUrl . '" class="btn btn-sm btn-danger me-2"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>';
        $html .= '<button type="button" class="btn btn-sm btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="card-body p-0">';
        $html .= '<div class="table-responsive">';

        $html .= '<table class="tabulation-table" id="tabulationTable">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="th-roll">Roll</th>';
        $html .= '<th class="th-name">Name</th>';

        foreach ($columns as $column) {
            $html .= '<th>';
            $html .= '<div style="writing-mode:vertical-rl;transform:rotate(180deg);height:auto;display:inline-block;align-items:center;">'
                . esc($column['title']) . '</div>';
            $html .= '</th>';
        }


        $html .= '<th><div style="writing-mode:vertical-rl;transform:rotate(180deg);height:auto;display:inline-block;align-items:center;">Total</div></th>';
        $html .= '<th><div style="writing-mode:vertical-rl;transform:rotate(180deg);height:auto;display:inline-block;align-items:center;">Percentage</div></th>';
        $html .= '<th><div style="writing-mode:vertical-rl;transform:rotate(180deg);height:auto;display:inline-block;align-items:center;">Grade Points</div></th>';
        $html .= '<th><div style="writing-mode:vertical-rl;transform:rotate(180deg);height:auto;display:inline-block;align-items:center;">Letter Grade</div></th>';
        
        
        $html .= '<th>';
        $html .= '<div style="writing-mode:vertical-rl;transform:rotate(180deg);height:auto;display:inline-block;align-items:center;">Position</div>';
        $html .= '</th>';
       
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . esc($row['roll_no']) . '</td>';
            $html .= '<td class="td-name">' . esc($row['student_name']) . '</td>';

            foreach ($columns as $key => $column) {
                $html .= '<td>' . esc($row['marks'][$key] ?? '') . '</td>';
            }

            $html .= '<td>' . esc($row['total']) . '</td>';
            $html .= '<td>' . esc($row['percentage']) . '</td>';
            $html .= '<td>' . esc($row['gpa']) . '</td>';
            $html .= '<td>' . esc($row['grade']) . '</td>';
            $html .= '<td>' . esc($row['position']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="card-footer text-muted no-print">';
        $html .= '<strong>Exam:</strong> ' . esc($exam->title) . ' | ';
        $html .= '<strong>Class:</strong> ' . esc($className) . ' | ';
        $html .= '<strong>Session:</strong> ' . esc($sessionName);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Print footer
        $html .= '<div class="print-footer print-only">';
        $html .= '<p>Generated on: ' . date('d-m-Y h:i A') . '</p>';
        $html .= '</div>';

        $html .= '</div>'; // end tabulationPrintArea

        return $html;
    }

    // ======================================================================
    // AJAX ENDPOINTS
    // ======================================================================

    /**
     * AJAX: Get year/session list by school
     */
    public function ajaxGetYears()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/tabulation-sheet');
        }

        $school_id = (int) $this->request->getPost('school_id');
        $years = [];

        if ($school_id) {
            $years = $this->getActiveOptions($school_id, 'YearModel');
        }

        return $this->response->setJSON(['success' => true, 'data' => $years]);
    }

    /**
     * AJAX: Get exam list by school and year
     */
    public function ajaxGetExams()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/tabulation-sheet');
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

    /**
     * AJAX: Get class list by school
     */
    public function ajaxGetClasses()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/tabulation-sheet');
        }

        $school_id = (int) $this->request->getPost('school_id');
        $classes = [];

        if ($school_id) {
            $classes = $this->getActiveOptions($school_id, 'ClassModel');
        }

        return $this->response->setJSON(['success' => true, 'data' => $classes]);
    }

    /**
     * Download Tabulation Sheet as PDF
     */
    public function downloadPdf()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $school_id = (int) $this->request->getGet('school_id');
        $year_id   = (int) $this->request->getGet('year_id');
        $exam_id   = (int) $this->request->getGet('exam_id');
        $class_id  = (int) $this->request->getGet('class_id');

        if (!$school_id || !$year_id || !$exam_id || !$class_id) {
            return redirect()->to('examination/reports/tabulation-sheet')->with('error', 'All fields are required');
        }

        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);

        if (!in_array($school_id, $schoolIds)) {
            return redirect()->to('examination/reports/tabulation-sheet')->with('error', 'Access denied');
        }

        // Get exam info
        $exam = $this->ExamModel->find($exam_id);
        $class = $this->ClassModel->find($class_id);
        $year = $this->YearModel->find($year_id);
        $school = $this->SchoolModel->find($school_id);

        $examTitle    = $exam ? $exam->title : '';
        $className    = $class ? $class->title : '';
        $sessionName  = $year ? $year->title : '';
        $schoolName   = $school ? $school->name : '';
        $schoolAddr   = $school ? $school->address : '';
        $schoolPhone  = $school ? $school->phone : '';
        $schoolEmail  = $school ? $school->email : '';

        // Build the same tabulation data used by the on-screen tabulation sheet
        $data    = $this->buildTabulationSheetData($school_id, $year_id, $exam_id, $class_id);
        $columns = $data['columns'];
        $rows    = $data['rows'];

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
            th {background-color: #fff; color: #000; padding: 6px 4px; border: 1px solid #000; text-align: center; font-size: 10px; }
            td { padding: 4px; border: 1px solid #000; text-align: center; font-size: 10px; }
            td.name { text-align: left; }
            .footer { text-align: center; margin-top: 15px; font-size: 10px; color: #666; }
            .total { font-weight: bold; }
        </style>
        </head><body>';

        $html .= '<div class="header">';
        $html .= '<h2>' . esc($schoolName) . '</h2>';
        $html .= '<p>' . esc($schoolAddr) . '</p>';
        $html .= '<p>Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3>Tabulation Sheet</h3>';
        $html .= '<p><strong>Exam:</strong> ' . esc($examTitle) . ' | <strong>Class:</strong> ' . esc($className) . ' | <strong>Session:</strong> ' . esc($sessionName) . '</p>';
        //$html .= '<hr>';
        $html .= '</div>';

        $html .= '<table>';
        $html .= '<thead><tr style="background:#1a73e8;color:#fff;">';
        $html .= '<th style="text-align:center;padding:8px;">Roll</th>';
        $html .= '<th style="text-align:left;padding:8px;">Name</th>';

        foreach ($columns as $column) {
            $html .= '<th style="text-align:center;padding:4px;height:auto;vertical-align:bottom;">' . $this->verticalHeaderText($column['title']) . '</th>';
        }

        $html .= '<th style="text-align:center;padding:4px;height:auto;vertical-align:bottom;">' . $this->verticalHeaderText('Total') . '</th>';
        $html .= '<th style="text-align:center;padding:4px;height:auto;vertical-align:bottom;">' . $this->verticalHeaderText('Percentage') . '</th>';
        $html .= '<th style="text-align:center;padding:4px;height:auto;vertical-align:bottom;">' . $this->verticalHeaderText('Grade Points') . '</th>';
        $html .= '<th style="text-align:center;padding:4px;height:auto;vertical-align:bottom;">' . $this->verticalHeaderText('Letter Grade') . '</th>';
        $html .= '<th style="text-align:center;padding:4px;height:auto;vertical-align:bottom;">' . $this->verticalHeaderText('Position') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . esc($row['roll_no']) . '</td>';
            $html .= '<td class="name">' . esc($row['student_name']) . '</td>';
            foreach ($columns as $key => $column) {
                $html .= '<td>' . esc($row['marks'][$key] ?? '') . '</td>';
            }
            $html .= '<td class="total">' . esc($row['total']) . '</td>';
            $html .= '<td>' . esc($row['percentage']) . '</td>';
            $html .= '<td>' . esc($row['gpa']) . '</td>';
            $html .= '<td>' . esc($row['grade']) . '</td>';
            $html .= '<td>' . esc($row['position']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $html .= '<div class="footer">';
        $html .= '<p>Generated on: ' . date('d-m-Y h:i A') . '</p>';
        $html .= '</div>';

        $html .= '</body></html>';

        // Generate PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Tabulation_Sheet_' . $examTitle . '_' . $className . '_' . $sessionName . '.pdf';
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * AJAX: Generate tabulation sheet and return HTML
     */
    public function ajaxGenerateTabulation()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/tabulation-sheet');
        }

        $school_id = (int) $this->request->getPost('school_id');
        $year_id   = (int) $this->request->getPost('year_id');
        $exam_id   = (int) $this->request->getPost('exam_id');
        $class_id  = (int) $this->request->getPost('class_id');

        if (!$school_id || !$year_id || !$exam_id || !$class_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'All fields are required']);
        }

        $user_id = $this->getUserId();
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);

        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        $html = $this->generateTabulationSheet($school_id, $year_id, $exam_id, $class_id);

        return $this->response->setJSON(['success' => true, 'html' => $html]);
    }
}
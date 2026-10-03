<?php

namespace App\Modules\examination\Controllers\Reports;

use App\Models\SchoolModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsYearModel;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\AcademicsShiftModel;
use App\Models\StudentGuardianModel;
use App\Modules\examination\Models\StudentSubjectModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\ResultTemplateModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\MarkDistributionModel;

use App\Modules\examination\Models\GradeSystemModel;
use App\Modules\examination\Models\GradeRuleModel;
use Exception;

class IndividualResultController extends BaseReportController
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
    protected GradeSystemModel $GradeSystemModel;
    protected GradeRuleModel $GradeRuleModel;
    protected StudentGuardianModel $StudentGuardianModel;
    protected StudentSubjectModel $StudentSubjectModel;

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
        $this->GradeSystemModel     = new GradeSystemModel();
        $this->GradeRuleModel       = new GradeRuleModel();
        $this->StudentGuardianModel = new StudentGuardianModel();
        $this->StudentSubjectModel  = new StudentSubjectModel();

        // Load helpers
        helper('Modules\examination\Helpers\results_helper');
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

    /**
     * Get the appropriate template for a school (individual = support_multiple_exams=0)
     */
    protected function getTemplateForSchool(
        int $school_id,
        ?int $schoolOwnerUid = null,
        bool $restrictToOwner = true
    ): ?object
    {
        $user_id = $schoolOwnerUid ?? $this->getUserId();

        // Try school-specific template first
        $templateQuery = $this->TemplateModel
            ->where('school_id', $school_id)
            ->where('template_type', 'result_card')
            ->where('support_multiple_exams', 0)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'DESC');

        if ($restrictToOwner) {
            $templateQuery->where('school_owner_uid', $user_id);
        }

        $template = $templateQuery->first();

        if ($template) {
            return $template;
        }

        // A platform administrator may assign a template directly to this
        // school. It should apply even though the administrator is not the
        // school's owner.
        if ($restrictToOwner) {
            $template = $this->TemplateModel
                ->where('school_id', $school_id)
                ->where('template_type', 'result_card')
                ->where('support_multiple_exams', 0)
                ->orderBy('is_default', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Fallback to default template (no school_id)
        $templateQuery = $this->TemplateModel
            ->groupStart()
                ->where('school_id', 0)
                ->orWhere('school_id IS NULL')
            ->groupEnd()
            ->where('template_type', 'result_card')
            ->where('is_default', 1);

        $templateQuery
            ->orderBy('id', 'DESC');

        $template = $templateQuery->first();

        return $template ?: $this->getBuiltInDefaultTemplate($user_id);
    }

    /**
     * Always keep individual results usable for a new school. Custom templates
     * remain optional and take precedence over this built-in fallback.
     */
    protected function getBuiltInDefaultTemplate(int $schoolOwnerUid = 0): object
    {
        return (object) [
            'id'                      => 0,
            'school_owner_uid'        => $schoolOwnerUid,
            'template_name'           => 'Built-in Default Result Card',
            'template_type'           => 'result_card',
            'is_default'              => 1,
            'support_multiple_exams'  => 0,
            'support_aggregated_result' => 0,
            'orientation'             => 'portrait',
            'template_bg'             => '',
            'class_teacher_signature' => '',
            'principal_signature'     => '',
            'template_style'          => '
                .default-result-card{max-width:900px;margin:0 auto;padding:28px;background:#fff;color:#1f2937;font-family:Arial,"Noto Sans Bengali",sans-serif}
                .default-result-card h2,.default-result-card h3,.default-result-card p{margin:5px 0}
                .default-result-card .school-header{text-align:center;border-bottom:2px solid #2563eb;padding-bottom:14px;margin-bottom:18px}
                .default-result-card .student-info,.default-result-card .summary{width:100%;border-collapse:collapse;margin:14px 0}
                .default-result-card .student-info td,.default-result-card .summary td{border:1px solid #d1d5db;padding:8px}
                .default-result-card .signatures{display:flex;justify-content:space-between;gap:25px;margin-top:55px;text-align:center}
                .default-result-card .signature{flex:1;border-top:1px solid #374151;padding-top:7px}
                @media(max-width:600px){.default-result-card{padding:14px}.default-result-card .signatures{gap:10px;font-size:12px}}
            ',
            'template_content'        => '<div class="default-result-card">
                <div class="school-header">
                    <h2>{school_name}</h2>
                    <p>{school_address}</p>
                    <h3>পরীক্ষার ফলাফল</h3>
                    <p><strong>পরীক্ষা:</strong> {exam_name} &nbsp; <strong>শ্রেণি:</strong> {class_name} &nbsp; <strong>শিক্ষাবর্ষ:</strong> {session_name}</p>
                </div>
                <table class="student-info">
                    <tr><td><strong>শিক্ষার্থীর নাম</strong></td><td>{student_name}</td><td><strong>রোল</strong></td><td>{roll_no}</td></tr>
                    <tr><td><strong>শিক্ষার্থী আইডি</strong></td><td>{student_code}</td><td><strong>শাখা</strong></td><td>{section_name}</td></tr>
                </table>
                {subject_table}
                <table class="summary">
                    <tr><td><strong>মোট নম্বর</strong><br>{total_marks} / {full_marks}</td><td><strong>শতকরা</strong><br>{percentage}%</td><td><strong>GPA</strong><br>{gpa}</td></tr>
                    <tr><td><strong>গ্রেড</strong><br>{grade}</td><td><strong>ফলাফল</strong><br>{result_status}</td><td><strong>মেধাক্রম</strong><br>{class_rank}</td></tr>
                </table>
                {optional_summary}
                <div class="signatures"><div class="signature">শ্রেণি শিক্ষক</div><div class="signature">অভিভাবক</div><div class="signature">প্রধান শিক্ষক</div></div>
            </div>',
        ];
    }

    /**
     * Build the student information formatted table.
     */
    protected function buildStudentInfoTable(array $data): string
    {
        $html = '<table class="student-info-table table table-bordered" border="1" cellpadding="6" >';
        $html .= '<tr><td><strong>Student Name</strong></td><td>: ' . esc($data['student_name'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Student ID</strong></td><td>: ' . esc($data['student_code'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Roll No</strong></td><td>: ' . esc($data['roll_no'] ?? '') . '</td></tr>';
        // $html .= '<tr><td><strong>Registration</strong></td><td>: ' . esc($data['registration_no'] ?? '') . '</td></tr>';
         $html .= '<tr><td><strong>Father Name</strong></td><td>: ' . esc($data['father_name'] ?? '') . '</td></tr>';
         $html .= '<tr><td><strong>Session</strong></td><td>: ' . esc($data['session_name'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Class</strong></td><td>: ' . esc($data['class_name'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Section</strong></td><td>: ' . esc($data['section_name'] ?? '') . '</td></tr>';
        // $html .= '<tr><td><strong>Shift</strong></td><td>: ' . esc($data['shift_name'] ?? '') . '</td></tr>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Build the exam wise summary table.
     */
    protected function buildExamWiseSummary(array $data): string
    {
        $html = '<table class="exam-wise-summary-table table table-bordered" border="1" cellpadding="6">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="text-align:left;">Exam</th>';
        $html .= '<th style="text-align:center;">Total</th>';
        $html .= '<th style="text-align:center;">Percentage</th>';
        $html .= '<th style="text-align:center;">GPA</th>';
        $html .= '<th style="text-align:center;">Grade</th>';
        $html .= '<th style="text-align:center;">Position</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $examRows = $data['exam_wise_summary_rows'] ?? [];
        if (!empty($examRows)) {
            foreach ($examRows as $row) {
                $html .= '<tr>';
                $html .= '<td>' . esc($row['exam_name'] ?? '') . '</td>';
                $html .= '<td style="text-align:center;">' . (int) ($row['total'] ?? 0) . '</td>';
                $html .= '<td style="text-align:center;">' . number_format($row['percentage'] ?? 0, 2) . '%</td>';
                $html .= '<td style="text-align:center;">' . number_format($row['gpa'] ?? 0, 2) . '</td>';
                $html .= '<td style="text-align:center; font-weight:bold;">' . esc($row['grade'] ?? '') . '</td>';
                $html .= '<td style="text-align:center;">' . ($row['position'] ?? '-') . '</td>';
                $html .= '</tr>';
            }
        }

        // Final academic row
        $html .= '<tr style="background:#f9f9f9; font-weight:bold;">';
        $html .= '<td>Final Academic</td>';
        $html .= '<td style="text-align:center;">' . number_format($data['final_average_total'] ?? 0, 2) . '</td>';
        $html .= '<td style="text-align:center;">' . number_format($data['final_average_percentage'] ?? 0, 2) . '%</td>';
        $html .= '<td style="text-align:center;">' . number_format($data['final_average_gpa'] ?? 0, 2) . '</td>';
        $html .= '<td style="text-align:center; font-weight:bold;">' . esc($data['final_average_grade'] ?? '') . '</td>';
        $html .= '<td style="text-align:center;">' . ($data['final_average_position'] ?? '-') . '</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Build the exam summary box (single exam result summary).
     */
    protected function buildExamSummary(array $data): string
    {
        $html = '<table class="exam-summary-table table table-bordered" border="1" cellpadding="8">';
        $html .= '<tr>';
        $html .= '<td>Total Marks</td>';
        $html .= '<td> ' . number_format($data['total_marks'] ?? 0, 2) . ' / ' . number_format($data['full_marks'] ?? 0, 2) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>Percentage</td>';
        $html .= '<td> ' . number_format($data['percentage'] ?? 0, 2) . ' %</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>Final GPA</td>';
        $html .= '<td> ' . number_format($data['gpa'] ?? 0, 2) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>Letter Grade</td>';
        $html .= '<td> ' . esc($data['grade'] ?? '') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>Merit Position</td>';
        $html .= '<td> ' . ($data['class_rank'] ?? '-') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>Section Position</td>';
        $html .= '<td> ' . ($data['section_rank'] ?? '-') . '</td>';
        $html .= '</tr>';
        
        $html .= '<tr>';
        $html .= '<td>Result</td>';
        $result_status_class = $data['result_status_class'];
        $html .= '<td class="' . $result_status_class . '"> <strong> ' . esc($data['result_status'] ?? '') . '</strong></td>';
        $html .= '</tr>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Build exam summary in a single row table format
     */
    protected function buildExamSummaryOneRow(array $data): string
    {
        $html = '<table width="100%" style="width:100%;" class="exam-summary-table table table-bordered" border="1" cellpadding="8">';
        $html .= '<tr>';
        $html .= '<td><strong>Total Marks:</strong> ' . number_format($data['total_marks'] ?? 0, 2) . ' / ' . number_format($data['full_marks'] ?? 0, 2) . '</td>';
        $html .= '<td><strong>Percentage:</strong> ' . number_format($data['percentage'] ?? 0, 2) . ' %</td>';
        $html .= '<td><strong>GPA:</strong> ' . number_format($data['gpa'] ?? 0, 2) . '</td>';
        $html .= '<td><strong>Letter Grade:</strong> ' . esc($data['grade'] ?? '') . '</td>';
        $html .= '<td><strong>Merit Position:</strong> ' . ($data['class_rank'] ?? '-') . '</td>';
        $html .= '<td><strong>Section Position:</strong> ' . ($data['section_rank'] ?? '-') . '</td>';
        
        if (isset($data['working_days']) && $data['working_days'] !== '') {
            $html .= '<td><strong>Working Days:</strong> ' . (int) ($data['working_days'] ?? 0) . '</td>';
        }
        if (isset($data['present_days']) && $data['present_days'] !== '') {
            $html .= '<td><strong>Present:</strong> ' . (int) ($data['present_days'] ?? 0) . '</td>';
        }
        if (isset($data['absent_days']) && $data['absent_days'] !== '') {
            $html .= '<td><strong>Absent:</strong> ' . (int) ($data['absent_days'] ?? 0) . '</td>';
        }
        
        $html .= '<td><strong>Result:</strong> ' . esc($data['result_status'] ?? '') . '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        return $html;
    }
    
    /**
     * Build attendance summary table
     */
    protected function buildAttendanceSummary(array $data): string
    {
        if (!empty($data['working_days']) && !empty($data['present_days']) && !empty($data['absent_days'])) {
        $html = '<table class="attendance-summary-table table table-bordered" border="1" cellpadding="8"  style="display: inline-table;" ">';
        $html .= '<tr>';
        $html .= '<th>Attendance Details</th>';
        $html .= '<th>Days</th>';
        $html .= '</tr>';
       
        if (isset($data['working_days']) && $data['working_days'] !== '') {
            $html .= '<tr>';
            $html .= '<td>Working Days</td>';
            $html .= '<td style="text-align:center;">' . (int) ($data['working_days'] ?? 0) . '</td>';
            $html .= '</tr>';
        }
        
        if (isset($data['present_days']) && $data['present_days'] !== '') {
            $html .= '<tr>';
            $html .= '<td>Present Days</td>';
            $html .= '<td style="text-align:center;">' . (int) ($data['present_days'] ?? 0) . '</td>';
            $html .= '</tr>';
        }
        
        if (isset($data['absent_days']) && $data['absent_days'] !== '') {
            $html .= '<tr>';
            $html .= '<td>Absent Days</td>';
            $html .= '<td style="text-align:center;">' . (int) ($data['absent_days'] ?? 0) . '</td>';
            $html .= '</tr>';
        }
        
        if (isset($data['attendance']) && $data['attendance'] !== '') {
            $attPct = $data['attendance'];
            $attClass = $attPct >= 75 ? 'text-success' : ($attPct >= 60 ? 'text-warning' : 'text-danger');
            $html .= '<tr>';
            $html .= '<td>Attendance Percentage</td>';
            $html .= '<td style="text-align:center;" class="' . $attClass . '">' . number_format($attPct, 1) . '%</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
        }else{
            return '';
        }
    }

    /**
     * Render a template by replacing placeholders with actual data.
     */
    protected function renderTemplate(string $template_content, array $data): string
    {
        $replacements = [
            '{school_name}'    => $data['school_name'] ?? '',
            '{school_address}' => $data['school_address'] ?? '',
            '{school_phone}'   => $data['school_phone'] ?? '',
            '{school_email}'   => $data['school_email'] ?? '',
            '{school_website}' => $data['school_website'] ?? '',
            '{school_logo}'    => $data['school_logo'] ?? '',
            '{student_name}'   => $data['student_name'] ?? '',
            '{student_code}'   => $data['student_code'] ?? '',
            '{roll_no}'        => $data['roll_no'] ?? '',
            '{class_name}'     => $data['class_name'] ?? '',
            '{section_name}'   => $data['section_name'] ?? '',
            '{session_name}'   => $data['session_name'] ?? '',
            '{shift_name}'     => $data['shift_name'] ?? '',
            '{registration_no}' => $data['registration_no'] ?? '',
            '{guardian_name}'  => $data['guardian_name'] ?? '',
            '{student_phone}'  => $data['student_phone'] ?? '',
            '{student_info_table}' => $this->buildStudentInfoTable($data),
            '{exam_name}'      => $data['exam_name'] ?? '',
            '{exam_year}'      => $data['exam_year'] ?? '',
            '{total_subjects}' => $data['total_subjects'] ?? '',
            '{passed_subjects}' => $data['passed_subjects'] ?? '',
            '{failed_subjects}' => $data['failed_subjects'] ?? '',
            '{total_marks}'    => $data['total_marks'] ?? '',
            '{obtained_marks}' => $data['obtained_marks'] ?? '',
            '{percentage}'     => $data['percentage'] ?? '',
            '{gpa}'            => $data['gpa'] ?? '',
            '{grade}'          => $data['grade'] ?? '',
            '{grade_remarks}'  => $data['grade_remarks'] ?? '',
            '{result_status}'  => $data['result_status'] ?? '',
            '{result_status_class}' => $data['result_status_class'] ?? '',
            '{class_rank}'     => $data['class_rank'] ?? '',
            '{section_rank}'   => $data['section_rank'] ?? '',
            '{attendance}'     => $data['attendance'] ?? '',
            '{working_days}'   => $data['working_days'] ?? '',
            '{present_days}'   => $data['present_days'] ?? '',
            '{absent_days}'    => $data['absent_days'] ?? '',
            '{principal_remarks}' => $data['principal_remarks'] ?? '',
            '{teacher_remarks}'   => $data['teacher_remarks'] ?? '',
            '{subject_table}'  => $data['subject_table'] ?? '',
            '{exam_table}'     => $data['exam_table'] ?? '',
            '{aggregated_table}' => $data['aggregated_table'] ?? '',
            '{overall_summary}' => $data['overall_summary'] ?? '',
            '{template_bg}'    => $data['template_bg'] ?? '',
            '{exam_wise_summary}' => $this->buildExamWiseSummary($data),
            '{exam_summary}'   => $this->buildExamSummary($data),
            '{optional_summary}' => $data['optional_summary'] ?? '',
            '{optional_summary_horizontal}' => $data['optional_summary_horizontal'] ?? '',
            // buildExamSummaryOneRow
            '{exam_summary_one_row}' => $this->buildExamSummaryOneRow($data),
            '{attendance_summary}' => $this->buildAttendanceSummary($data),
            '{promotion_info}' => $data['promotion_info'] ?? '',
            '{highest_mark}'   => $data['highest_mark'] ?? '',
            '{student_photo}'  => $data['student_photo'] ?? '',
            '{principal_signature}' => $data['principal_signature'] ?? '',
            '{class_teacher_signature}' => $data['class_teacher_signature'] ?? '',
            '{grading_chart}'  => $data['grading_chart'] ?? '',
            '{qr_code}'        => $data['qr_code'] ?? '',
            '{current_date}'   => date('d-m-Y'),
            '{current_year}'   => date('Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template_content);
    }

    /**
     * Generate the subject-wise marks table HTML for single exam (matches ResultCardController pattern).
     * 
     * @param array|object $subject_marks Can be either:
     *   - Flat array of subject objects
     *   - Categorized array with keys: compulsory_subjects, combined_subjects, optional_subjects
     * @param object|null $result
     * @param bool $showHighestMark
     * @param array $distributionData
     * @return string
     */
    protected function generateSubjectTable($subject_marks, ?object $result = null, bool $showHighestMark = false, array $distributionData = [])
    {
       
        $processedSubjects = [];

        /*
        |--------------------------------------------------------------------------
        | 1. Combined Subjects
        |--------------------------------------------------------------------------
        */
        if (!empty($subject_marks['combined_subjects'])) {

            $combinedGroups = [];

            foreach ($subject_marks['combined_subjects'] as $sm) {
                $group = $sm->combine_group ?? '';

                if (!empty($group)) {
                    $combinedGroups[$group][] = $sm;
                } else {
                    // Just in case a subject exists without combine_group
                    $processedSubjects[] = $sm;
                }
            }

            foreach ($combinedGroups as $groupSubjects) {
                // Bangla 1st + Bangla 2nd as one item
                $processedSubjects[] = $groupSubjects;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Compulsory Subjects
        |--------------------------------------------------------------------------
        */
        if (!empty($subject_marks['compulsory_subjects'])) {
            foreach ($subject_marks['compulsory_subjects'] as $sm) {
                $processedSubjects[] = $sm;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Optional Subjects
        |--------------------------------------------------------------------------
        */
        if (!empty($subject_marks['optional_subjects'])) {
            foreach ($subject_marks['optional_subjects'] as $sm) {
                $processedSubjects[] = $sm;
            }
        }
        
        // Collect ALL unique distribution names across all subjects
        $allDistNames = [];
        foreach ($distributionData as $subjectDistributions) {
            foreach ($subjectDistributions as $distName => $distMark) {
                if (!in_array($distName, $allDistNames)) {
                    $allDistNames[] = $distName;
                }
            }
        }

        $html = '<table class="marks-table table table-bordered" border="1" cellpadding="5">
            <thead>
                <tr>
                    <th width="30px" style="text-align:center;">#</th>
                    <th style="text-align:left;">Subject</th>';

        // Distribution columns (e.g. MCQ, Written)
        foreach ($allDistNames as $distName) {
            $html .= '<th style="text-align:center;">' . esc(ucfirst($distName)) . '</th>';
        }

        $html .= '<th style="text-align:center;">Total Obtained</th>';

        if ($showHighestMark) {
            $html .= '<th style="text-align:center;">Highest</th>';
        }

        $html .= '<th style="text-align:center;">Average</th>
                    <th style="text-align:center;">%</th>
                    <th style="text-align:center;">GPA</th>
                    <th style="text-align:center;">Grade</th>
                </tr>
            </thead>
            <tbody>';

        $sn = 1;
        $totalCompulsorySubjects = 0;
        $totalGP = 0;
        $optionalBonusGP = 0;
        $hasOptional = false;
        $total_included_mark = [];
        $total_include_parcentage = [];
        $total_include_grade_point = [];
            
        foreach ($processedSubjects as $subjectItem) {
            // Check if this is a combined group (array of subjects) or single subject
            $isCombinedGroup = is_array($subjectItem);
            
            if ($isCombinedGroup) {
                // Combined subjects - display each subject in separate rows with shared Average/%/GPA/Grade
                $count = count($subjectItem);
                
                // Calculate combined values
                $totalObtained = 0;
                $totalPercentage = 0;
                $totalGradePoint = 0;
                $combinedNames = [];
                
                foreach ($subjectItem as $sm) {
                    $combinedNames[] = $sm->title ?? 'Unknown';
                    $totalObtained += $sm->obtained_mark ?? 0;
                    $totalPercentage += $sm->percentage ?? 0;
                    $totalGradePoint += $sm->grade_point ?? 0;
                }
                
                $avgObtained = $count > 0 ? round($totalObtained / $count, 2) : 0;
                $avgPercentage = $count > 0 ? round($totalPercentage / $count, 2) : 0;
                $avgGradePoint = $count > 0 ? round($totalGradePoint / $count, 2) : 0;

                

                $letterGrade = $subjectItem[0]->letter_grade ?? '-';
                $optional = $subjectItem[0]->optional ?? 0;

                $total_included_mark[] = $avgObtained;
                $total_include_parcentage[] = $avgPercentage;
                $total_include_grade_point[] = $avgGradePoint;
                
                // Display each subject in the group separately
                foreach ($subjectItem as $index => $sm) {
                    $subjectId = $sm->subject_id ?? 0;
                    $html .= '<tr>';
                    
                    $html .= '<td style="text-align:center;">' . $sn++ . '</td>';
                    
                    
                    $html .= '<td>' . esc($sm->title ?? '-') . '</td>';
                    $totalCompulsorySubjects = 1;
                    
                    
                    // Show distribution values for this subject
                    $subjectDistributions = $distributionData[$subjectId] ?? [];
                    foreach ($allDistNames as $distName) {
                        if (isset($subjectDistributions[$distName])) {
                            $distMark = $subjectDistributions[$distName];
                            $html .= '<td style="text-align:center;">' . round($distMark['obtained'] ?? 0) . '/' . round($distMark['full'] ?? 0) . '</td>';
                        } else {
                            $html .= '<td style="text-align:center;"></td>';
                        }
                    }
                    
                    
                    $html .= '<td style="text-align:center;">' . round($sm->obtained_mark ?? 0) . '</td>';
                    
                    
                    if ($showHighestMark) {
                        $html .= '<td style="text-align:center;">' . round($sm->highest_mark ?? 0) . '</td>';
                    }
                    
                    // For combined subjects: Average, %, GPA, Grade with rowspan on first subject only
                    if ($index === 0) {
                        // First subject in group - show Average, %, GPA, Grade with rowspan
                        $average_mark = $sm->average_mark ?? 0;
                        $average_percentage = $sm->combined_percentage ?? 0;
                        $average_gp = $sm->average_gp ?? 0;
                        $average_grade = $sm->average_grade ?? '';

                        $html .= '<td style="text-align:center;vertical-align: middle;" rowspan="' . $count . '">' . round($average_mark) . '</td>';
                        $html .= '<td style="text-align:center;vertical-align: middle;" rowspan="' . $count . '">' . $average_percentage . '%</td>';
                        $html .= '<td style="text-align:center;vertical-align: middle;" rowspan="' . $count . '">' . $average_gp . '</td>';
                        $html .= '<td style="text-align:center;vertical-align: middle;" rowspan="' . $count . '">' . esc($average_grade) . '</td>';
                        
                        // Add to total GP for compulsory subjects (use sum for final GPA calculation)
                        if (!$optional) {
                            $totalGP = $average_gp;
                        }
                    }
                    // For subsequent subjects in group, these columns are skipped (covered by rowspan)
                    
                    $html .= '</tr>';
                }
            } else {
                // Regular single subject
                $sm = $subjectItem;
                $subjectId = $sm->subject_id ?? 0;
                $optional = $sm->optional ?? 0;
                
                $html .= '<tr>';

                $html .= '<td style="text-align:center;">' . $sn++ . '</td>';

                if($optional) {
                    $html .= '<td>' . esc($sm->title) . ' <sup><b>Optional</b></sup></td>';
                    $hasOptional = true;
                }else{
                    $html .= '<td>' . esc($sm->title ?? '-') . '</td>';
                    $totalCompulsorySubjects++;
                }

                // Show distribution values for this subject
                $subjectDistributions = $distributionData[$subjectId] ?? [];
                foreach ($allDistNames as $distName) {
                    if (isset($subjectDistributions[$distName])) {
                        $distMark = $subjectDistributions[$distName];
                        $html .= '<td style="text-align:center;">' . round($distMark['obtained'] ?? 0) . '/' . round($distMark['full'] ?? 0) . '</td>';
                    } else {
                        $html .= '<td style="text-align:center;"></td>';
                    }
                }

                if($optional) {
                    $include_mark = $sm->include_mark ?? 0;
                    $exclude_mark = $sm->exclude_mark ?? 0;
                    $html .= '<td style="text-align:center;">' . ($include_mark + $exclude_mark) . '</td>';
                }else{
                    $html .= '<td style="text-align:center;">' . round($sm->obtained_mark ?? 0) . '</td>';
                }
                

                if ($showHighestMark) {
                    $html .= '<td style="text-align:center;">' . round($sm->highest_mark ?? 0) . '</td>';
                }

                // Calculate Average (total obtained / number of exams)
                $examCount =  1;
                $average = $examCount > 0 ? round(($sm->obtained_mark ?? 0) / $examCount, 2) : 0;

                $total_included_mark[] = $sm->obtained_mark ?? 0;
                $total_include_parcentage[] = $sm->percentage ?? 0;
                $total_include_grade_point[] = $sm->grade_point ?? 0;

                // Regular subject
                $gp = 0;
                if($optional) {
                    $gp = $sm->include_grade_point ?? 0;
                    $html .= '<td style="text-align:center;">' . round($average) . '</td>';
                    $html .= '<td style="text-align:center;">' . round($sm->percentage ?? 0) . '%</td>';
                    $html .= '<td style="text-align:center;">' . $gp . '</td>';
                    $html .= '<td style="text-align:center;">' . esc($sm->letter_grade ?? '-') . '</td>';
                    // Optional subject bonus: if GP > 2, bonus = GP - 2, else bonus = 0
                    $optionalBonusGP += $gp;
                    
                } else {
                    $gp = $sm->grade_point ?? 0;
                    $html .= '<td style="text-align:center;">' . round($average) . '</td>';
                    $html .= '<td style="text-align:center;">' . round($sm->percentage ?? 0) . '%</td>';
                    $html .= '<td style="text-align:center;">' . $gp . '</td>';
                    $html .= '<td style="text-align:center;">' . esc($sm->letter_grade ?? '-') . '</td>';
                    $totalGP += $gp;
                }
                
                $html .= '</tr>';
            }
        }

        // Calculate Final GPA
        $finalGPA = $totalCompulsorySubjects > 0 ? round(($totalGP + $optionalBonusGP) / $totalCompulsorySubjects, 2) : 0;

        $html .= '</tbody>
            <tfoot>
                <tr style="font-weight:bold; background:#f9f9f9;">
                    <td></td>
                    <td>Total</td>';

        foreach ($allDistNames as $distName) {
            $html .= '<td style="text-align:center;"></td>';
        }

        $html .= '<td style="text-align:center;"></td>';

        if ($showHighestMark) {
            $html .= '<td style="text-align:center;"></td>';
        }
        
        $sum_total_included_mark = array_sum($total_included_mark);
       

        $html .= '<td style="text-align:center;">'.$sum_total_included_mark.'</td>
                    <td style="text-align:center;">' . $result->percentage . '%</td>
                    <td style="text-align:center;">' . ($result->total_grade_point ?? 0) . '</td>
                    <td style="text-align:center;"></td>
                </tr>
            </tfoot>
        </table>';

        // Summary table - single row format
        $optinal_summary = '';
        $optinal_summary .= '<table width="100%" class="exam-optinal-summary-table table table-bordered" border="1" cellpadding="6" style="width:100%;" >';
        $optinal_summary .= '<tr>';
        $optinal_summary .= '<td ><strong>Total Compulsory Subjects:</strong> ' . $totalCompulsorySubjects . '</td>';
        $optinal_summary .= '<td><strong>Total GP:</strong> ' . number_format($totalGP, 2) . '</td>';
        if ($hasOptional) {
            $optinal_summary .= '<td><strong>Optional Subject Bonus:</strong> +' . number_format($optionalBonusGP, 2) . '</td>';
        }
        $optinal_summary .= '<td style="font-weight:bold; background:#f0f0f0;"><strong>Final GPA:</strong> ' . number_format($finalGPA, 2) . '</td>';
        $optinal_summary .= '</tr>';
        $optinal_summary .= '</table>';

        // Horizontal summary table
        $optinal_summary_horizontal = '';
        $optinal_summary_horizontal .= '<table width="100%" class="exam-optinal-summary-table table table-bordered" border="1" cellpadding="6" style="width:100%;" >';
        $optinal_summary_horizontal .= '<tr>';
        $optinal_summary_horizontal .= '<td class="label">Total Compulsory Subjects</td>';
        $optinal_summary_horizontal .= '<td>' . $totalCompulsorySubjects . '</td>';
        $optinal_summary_horizontal .= '</tr>';
        $optinal_summary_horizontal .= '<tr>';
        $optinal_summary_horizontal .= '<td class="label">Total GP</td>';
        $optinal_summary_horizontal .= '<td>' . number_format($totalGP, 2) . '</td>';
        $optinal_summary_horizontal .= '</tr>';
        if ($hasOptional) {
            $optinal_summary_horizontal .= '<tr>';
            $optinal_summary_horizontal .= '<td class="label">Optional Subject Bonus</td>';
            $optinal_summary_horizontal .= '<td> +' . number_format($optionalBonusGP, 2) . '</td>';
            $optinal_summary_horizontal .= '</tr>';
        }
        
        $optinal_summary_horizontal .= '</table>';

        return [
            'html' => $html,
            'optinal_summary' => $optinal_summary,
            'optinal_summary_horizontal' => $optinal_summary_horizontal
        ];
    }

    /**
     * Fetch distribution marks for a student's subjects (from examination_marks).
     */
    protected function getDistributionMarks(int $school_id, int $exam_id, int $class_id, int $student_id): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('examination_marks');
        $builder->select('examination_marks.*, examination_mark_distributions.name AS distribution_name');
        $builder->join('examination_mark_distributions', 'examination_mark_distributions.id = examination_marks.distribution_id', 'left');
        $builder->where('examination_marks.school_id', $school_id);
        $builder->where('examination_marks.exam_id', $exam_id);
        $builder->where('examination_marks.class_id', $class_id);
        $builder->where('examination_marks.student_id', $student_id);
        $builder->orderBy('examination_mark_distributions.sort_order', 'ASC');
        $builder->orderBy('examination_mark_distributions.name', 'ASC');
        $query = $builder->get();
        $distributionMarks = $query->getResult();

        $distributionData = [];
        foreach ($distributionMarks as $dm) {
            $subjectId = $dm->subject_id;
            $distName = $dm->distribution_name ?: 'Dist_' . $dm->distribution_id;
            if (!isset($distributionData[$subjectId])) {
                $distributionData[$subjectId] = [];
            }
            $distributionData[$subjectId][$distName] = [
                'obtained' => $dm->obtained_mark ?? 0,
                'full' => $dm->full_mark ?? 0,
            ];
        }

        return $distributionData;
    }

    /**
     * Get grade remarks based on the student's grade.
     */
    protected function getGradeRemarks(string $grade, int $school_owner_uid): string
    {
        $db = \Config\Database::connect();
        $builder = $db->table('examination_grade_rules');
        $builder->where('school_owner_uid', $school_owner_uid);
        $builder->where('status', 1);
        $builder->where('title', $grade);
        $builder->orderBy('field_order', 'ASC');
        $query = $builder->get();
        $gradeRule = $query->getRow();

        return $gradeRule ? ($gradeRule->remarks ?? '') : '';
    }

    /**
     * Generate grading chart HTML from grade rules.
     */
    protected function generateGradingChart(int $school_owner_uid, int $school_id = 0): string
    {
        // Get school's grading system from params
        $gradingSystemId = null;
        if ($school_id) {
            $school = $this->SchoolModel->find($school_id);
            if ($school && !empty($school->params)) {
                $params = json_decode($school->params, true);
                if (isset($params['grading_system']) && !empty($params['grading_system'])) {
                    $gradingSystemId = (int) $params['grading_system'];
                }
            }
        }

        // Build query
        $query = $this->GradeRuleModel
            ->where('school_owner_uid', $school_owner_uid)
            ->where('status', 1);

        // Filter by grading system if available
        if ($gradingSystemId) {
           $query->where('grade_system_id', $gradingSystemId);
        }

        $gradeRules = $query->orderBy('field_order', 'ASC')->findAll();

        if (empty($gradeRules)) {
            return '';
        }

        $html = '<table class="grading-chart-table table table-bordered" border="1" cellpadding="4">
            <thead>
                <tr>
                    <th style="text-align:center;">Grade</th>
                    <th style="text-align:center;">Marks Range</th>
                    <th style="text-align:center;">Grade Point</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($gradeRules as $rule) {
            $html .= '<tr>
                <td style="text-align:center; font-weight:bold;">' . esc($rule->title) . '</td>
                <td style="text-align:center;">' . ($rule->mark_from ?? 0) . ' - ' . ($rule->mark_to ?? 0) . '</td>
                <td style="text-align:center;">' . ($rule->grade_point ?? 0) . '</td>
            </tr>';
        }

        $html .= '</tbody>
        </table>';

        return $html;
    }

    // ======================================================================
    // INDIVIDUAL RESULT LIST (load students like transcript)
    // ======================================================================
    public function index()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Individual Result',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $school_id    = (int) $this->request->getGet('school_id');
        $year_id      = (int) $this->request->getGet('year_id');
        $class_id     = (int) $this->request->getGet('class_id');
        $exam_id      = (int) $this->request->getGet('exam_id');

        $data = compact('school_id', 'year_id', 'class_id', 'exam_id');
        $data['school_list'] = $this->getSchoolDropdown();
        $data['year_list']   = [];
        $data['class_list']  = [];
        $data['exam_list']   = [];
        $data['students']    = [];
        $data['not_found']   = false;

        if ($school_id) {
            $data['year_list']  = $this->getActiveOptions($school_id, 'YearModel');
            $data['class_list'] = $this->getActiveOptions($school_id, 'ClassModel');
            $data['exam_list']  = $this->getActiveOptions($school_id, 'ExamModel');
        }

        // If all filters selected, load students list
        if ($school_id && $year_id && $class_id && $exam_id) {
            $userSchools = $this->getUserSchools();
            $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

            if (!in_array($school_id, $schoolIds)) {
                return redirect()->to('examination/reports/individual-result')->with('error', 'Access denied.');
            }

            // Get all students in this class (query students table first, then check enrollment)
            $this->StudentModel
                ->select('students.*, student_enrollments.roll_no, students.registration_no as enrollment_reg')
                ->join('student_enrollments', 'student_enrollments.student_id = students.id', 'left')
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.class_id', $class_id)
                ->where('students.status', 1)
                ->orderBy('student_enrollments.roll_no', 'ASC');

            $students = $this->StudentModel->findAll();
            
            // Get all result tokens for this exam in one query
            $resultTokens = $this->ExamResultModel
                ->select('student_id, token, result_status')
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->findAll();
            
            // Index by student_id for quick lookup
            $resultMap = [];
            foreach ($resultTokens as $r) {
                $resultMap[$r->student_id] = $r;
            }
            
            // Attach result info to each student
            foreach ($students as $stu) {
                $stu->token = $resultMap[$stu->id]->token ?? '';
                $stu->result_status = $resultMap[$stu->id]->result_status ?? '';
                $stu->has_result = isset($resultMap[$stu->id]) ? 1 : 0;
            }
            
            $data['students'] = $students;
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\individual_result', $data)
            . view('footer', $footer_data);
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
            return redirect()->to('examination/reports/individual-result');
        }
        
        $school_id = (int) $this->request->getPost('school_id');
        $years = [];
        
        if ($school_id) {
            $years = $this->getActiveOptions($school_id, 'YearModel');
        }
        
        return $this->response->setJSON(['success' => true, 'data' => $years]);
    }
    
    /**
     * AJAX: Get class list by school
     */
    public function ajaxGetClasses()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/individual-result');
        }
        
        $school_id = (int) $this->request->getPost('school_id');
        $classes = [];
        
        if ($school_id) {
            $classes = $this->getActiveOptions($school_id, 'ClassModel');
        }
        
        return $this->response->setJSON(['success' => true, 'data' => $classes]);
    }
    
    /**
     * AJAX: Get exam list by school
     */
    public function ajaxGetExams()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/individual-result');
        }
        
        $school_id = (int) $this->request->getPost('school_id');
        $exams = [];
        
        if ($school_id) {
            $exams = $this->getActiveOptions($school_id, 'ExamModel');
        }
        
        return $this->response->setJSON(['success' => true, 'data' => $exams]);
    }
    
    /**
     * AJAX: Get students by filters
     */
    public function ajaxGetStudents()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/individual-result');
        }
        
        $school_id = (int) $this->request->getPost('school_id');
        $year_id = (int) $this->request->getPost('year_id');
        $class_id = (int) $this->request->getPost('class_id');
        $exam_id = (int) $this->request->getPost('exam_id');
        
        if (!$school_id || !$year_id || !$class_id || !$exam_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'All fields are required']);
        }
        
        $user_id = $this->getUserId();
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        
        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }
        
        // Get all students in this class
        $this->StudentModel
            ->select('students.*, student_enrollments.roll_no, students.registration_no as enrollment_reg')
            ->join('student_enrollments', 'student_enrollments.student_id = students.id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1)
            ->orderBy('student_enrollments.roll_no', 'ASC');
        
        $students = $this->StudentModel->findAll();
        
        // Get all result tokens for this exam
        $resultTokens = $this->ExamResultModel
            ->select('student_id, token, result_status')
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->findAll();
        
        // Index by student_id
        $resultMap = [];
        foreach ($resultTokens as $r) {
            $resultMap[$r->student_id] = $r;
        }
        
        // Attach result info
        foreach ($students as $stu) {
            $stu->token = $resultMap[$stu->id]->token ?? '';
            $stu->result_status = $resultMap[$stu->id]->result_status ?? '';
            $stu->has_result = isset($resultMap[$stu->id]) ? 1 : 0;
        }
        
        // Generate HTML for students table
        $html = '';
        if (!empty($students)) {
            $html .= '<div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Students in Class (' . count($students) . ' found)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Student ID</th>
                                            <th>Registration No</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
            
            $i = 1;
            foreach ($students as $stu) {
                $studentName = trim(($stu->first_name ?? '') . ' ' . ($stu->middle_name ?? '') . ' ' . ($stu->last_name ?? ''));
                $studentName = preg_replace('/\s+/', ' ', $studentName);
                
                $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . esc($stu->roll_no ?? '') . '</td>
                    <td>' . esc($studentName) . '</td>
                    <td>' . esc($stu->student_code ?? '') . '</td>
                    <td>' . esc($stu->registration_no ?? '') . '</td>
                    <td>';
                
                if ($stu->has_result) {
                    $html .= '<span class="badge bg-success">Result Available</span>';
                } else {
                    $html .= '<span class="badge bg-warning">Not Generated</span>';
                }
                
                $html .= '</td><td>';
                
                if ($stu->has_result && !empty($stu->token)) {
                    $html .= '<a href="' . base_url('examination/reports/individual-result/details/' . $stu->token) . '" target="_blank" class="btn btn-sm btn-info">
                        <i class="bi bi-eye"></i> View
                    </a>';
                } else {
                    $html .= '<button class="btn btn-sm btn-secondary" disabled>
                        <i class="bi bi-eye-slash"></i> Unavailable
                    </button>';
                }
                
                $html .= '</td></tr>';
            }
            
            $html .= '</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>';
        } else {
            $html = '<div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No students found for the selected filters.
                    </div>
                </div>
            </div>';
        }
        
        return $this->response->setJSON(['success' => true, 'html' => $html]);
    }
    
    /**
     * Download individual result as PDF
     * Route: individual-result/download-pdf/(:segment)
     */
    public function downloadPdf(string $token)
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        // Find result by token
        $result = $this->ExamResultModel
            ->where('token', $token)
            ->first();

        if (!$result) {
            return redirect()->to('examination/reports/individual-result')->with('error', 'Invalid result token.');
        }

        $school_id  = $result->school_id;
        $exam_id    = $result->exam_id;
        $class_id   = $result->class_id;
        $student_id = $result->student_id;
        $year_id    = $result->session_id ?? 0;

        // Access check
        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return redirect()->to('examination/reports/individual-result')->with('error', 'Access denied.');
        }

        // Get school, exam, class, year names
        $school = $this->SchoolModel->find($school_id);
        $exam   = $this->ExamModel->find($exam_id);
        $class  = $this->ClassModel->find($class_id);
        $year   = $year_id ? $this->YearModel->find($year_id) : null;

        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';
        $examName      = $exam ? $exam->title : '';
        $className     = $class ? $class->title : '';
        $sessionName   = $year ? $year->title : '';

        // School logo
        $schoolLogoPath = $school && !empty($school->logo) ? base_url('uploads/' . $school->logo) : base_url('uploads/default.png');
        $schoolLogoHtml = '<img src="' . $schoolLogoPath . '" alt="School Logo" style="max-width:100px;">';

        // Find student
        $student = $this->StudentModel
            ->select('students.*, student_enrollments.roll_no')
            ->join('student_enrollments', 'student_enrollments.student_id = students.id', 'left')
            ->where('students.id', $student_id)
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1)
            ->first();

        if (!$student) {
            return redirect()->to('examination/reports/individual-result')->with('error', 'Student not found.');
        }

        $enrollment = $this->EnrollmentModel
            ->where('student_id', $student_id)
            ->where('school_id', $school_id)
            ->where('class_id', $class_id)
            ->where('session_id', $year_id)
            ->first();

        if (!$enrollment) {
            // Fallback: get any enrollment for this student
            $enrollment = $this->EnrollmentModel
                ->where('student_id', $student_id)
                ->where('school_id', $school_id)
                ->where('class_id', $class_id)
                ->orderBy('session_id', 'DESC')
                ->first();
            
            if (!$enrollment) {
                return redirect()->to('examination/reports/individual-result')->with('error', 'Student enrollment not found.');
            }
        }

        $studentName = trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
        $studentName = preg_replace('/\s+/', ' ', $studentName);

        // Get guardian info
        $guardian = $this->StudentGuardianModel
            ->where('school_id', $school_id)
            ->where('student_id', $student->id)
            ->orderBy('id', 'ASC')
            ->first();

        // Get guardian info
        $guardians = $this->StudentGuardianModel
            ->where('school_id', $school_id)
            ->where('student_id', $student->id)
            ->orderBy('id', 'ASC')
            ->findAll();
        $guardianName  = $guardian ? ($guardian->name ?? '') : '';
        $studentPhone  = $student->phone ?? ($guardian->phone ?? '');

        $fatherName = '';
        $motherName = '';
        $guardianName = '';
        foreach ($guardians as $g) {
            $relation = strtolower($g->relation_type ?? '');
            if (strpos($relation, 'father') !== false) {
                $fatherName = $g->name ?? '';
            } elseif (strpos($relation, 'mother') !== false) {
                $motherName = $g->name ?? '';
            } else {
                $guardianName = $g->name ?? '';
            }
        }

        // Section/Shift info
        $sectionInfo = $enrollment->section_id ? $this->SectionModel->find($enrollment->section_id) : null;
        $shiftInfo   = $enrollment->shift_id ? $this->ShiftModel->find($enrollment->shift_id) : null;
        $sectionName = $sectionInfo ? $sectionInfo->title : '';
        $shiftName   = $shiftInfo ? $shiftInfo->title : '';

        // Get categorized subjects using the model method (same as details())
        $subjectMarks = $this->StudentSubjectModel->get_student_subject_result_categories(
            $school_id, 
            $enrollment->id, 
            $exam_id, 
            $class_id, 
            $year_id, 
            $student
        );

        // Get template for school
        $template = $this->getTemplateForSchool($school_id);
        $renderedContent = '';
        $templateStyle = '';

        // orientation
        $orientation = $template->orientation ?? 'portrait';

        if ($template) {
            $passed = (int) ($result->passed_subjects ?? 0);
            $failed = (int) ($result->failed_subjects ?? 0);
            $total  = (int) ($result->total_subjects ?? 0);
            $resultStatus = $failed > 0 ? "FAILED ({$passed}/{$total})" : "PASSED";
            $resultStatusClass = $failed > 0 ? 'text-danger' : 'text-success';

            // Grade remarks
            $gradeRemarks = $this->getGradeRemarks($result->letter_grade ?? $result->grade ?? '', $school->school_owner_uid ?? 0);

            // Build subject table
            $distributionData = $this->getDistributionMarks($school_id, $exam_id, $class_id, $student->id);
            $showHighestMark = false;
            if ($school && !empty($school->params)) {
                $schoolParams = json_decode($school->params, true);
                $showHighestMark = !empty($schoolParams['show_highest_mark']);
            }
            $subjectTable = $this->generateSubjectTable($subjectMarks, $result, $showHighestMark, $distributionData);

            // Promotion info
            $promotionInfo = '';
            if ($failed > 0) {
                $promotionInfo = '<div style="padding:10px; background:#fff3cd; border-left:4px solid #ffc107; margin-top:15px;">';
                $promotionInfo .= '<strong>Remedial Required:</strong> Student has failed in ' . $failed . ' subject(s).';
                $promotionInfo .= '</div>';
            } else {
                $promotionInfo = '<div style="padding:10px; background:#d4edda; border-left:4px solid #28a745; margin-top:15px;">';
                $promotionInfo .= '<strong>Promoted:</strong> Student has passed all subjects.';
                $promotionInfo .= '</div>';
            }

            // Student photo
            $studentPhotoPath = $student->photo && !empty($student->photo)
                ? base_url('uploads/' . $student->photo)
                : base_url('uploads/default.png');
            $studentPhoto = '<img src="' . $studentPhotoPath . '" class="student-photo" alt="Student Photo" style="width:100px;height:auto;">';

            // Class teacher signature from template
            $classTeacherSignature = '';
            if (!empty($template->class_teacher_signature)) {
                $classTeacherSignature_path = base_url('public/uploads/templates/'.$template->class_teacher_signature);
                $classTeacherSignature = '<img src="' . $classTeacherSignature_path . '" alt="Class Teacher Signature" style="width: 100px; height: auto; ">';
            }

            // Principal signature from template
            $principalSignature = '';
            if (!empty($template->principal_signature)) {
                $principalSignature_path = base_url('public/uploads/templates/'.$template->principal_signature);
                $principalSignature = '<img src="' . $principalSignature_path . '" alt="Principal Signature" style="width: 100px; height: auto; ">';
            }

            // Grading chart
            $gradingChart = $this->generateGradingChart($user_id, $school_id);
            

            // QR Code
            $qrCodeUrl = base_url('examination/result/' . $token);
            $qrCode = '<div style="text-align:center;padding:10px;">';
            $qrCode .= '<img src="' . generate_qr_code($qrCodeUrl, 80) . '" alt="QR Code" style="width:80px;height:80px;">';
            $qrCode .= '<p style="margin-top:0px;font-size:10px;color:#666;">Scan to verify result authenticity</p>';
            $qrCode .= '</div>';

            // Get highest mark for this exam
            $highestMarkRecord = $this->SubjectResultModel
                ->select('MAX(obtained_mark) as highest_mark')
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('student_id', $student->id)
                ->first();
            $highestMark = $highestMarkRecord ? $highestMarkRecord->highest_mark : 0;

            // Get distribution marks for this student
            $distributionData = $this->getDistributionMarks($school_id, $exam_id, $class_id, $student->id);
            
            // Generate subject table with distribution data
            $showHighestMark = false;
            if ($school && !empty($school->params)) {
                $schoolParams = json_decode($school->params, true);
                $showHighestMark = !empty($schoolParams['show_highest_mark']);
            }
            $subjectTable = $this->generateSubjectTable($subjectMarks, $result, $showHighestMark, $distributionData);

            // Placeholder data
            $placeholderData = [
                'school_name'       => $schoolName,
                'school_logo'       => $schoolLogoHtml,
                'school_address'    => $schoolAddress,
                'school_phone'      => $schoolPhone,
                'school_email'      => $schoolEmail,
                'school_website'    => $school->website ?? '',
                'class_name'        => $className,
                'section_name'      => $sectionName,
                'session_name'      => $sessionName,
                'shift_name'        => $shiftName,
                'exam_name'         => $examName,
                'exam_year'         => $year ? $year->title : '',
                'student_name'      => $studentName,
                'father_name'       => $fatherName,
                'student_code'      => $student->student_code ?? '',
                'roll_no'           => $enrollment->roll_no,
                'registration_no'   => $student->registration_no ?? '',
                'guardian_name'     => $guardianName,
                'student_phone'     => $studentPhone,
                'total_subjects'    => $total,
                'passed_subjects'   => $passed,
                'failed_subjects'   => $failed,
                'total_marks'       => $result->obtained_marks ?? 0,
                'full_marks'        => $result->total_marks ?? 0,
                'percentage'        => $result->percentage ?? 0,
                'gpa'               => $result->gpa ?? 0,
                'grade'             => $result->letter_grade ?? $result->grade ?? '',
                'grade_remarks'     => $gradeRemarks,
                'result_status'     => $resultStatus,
                'result_status_class' => $resultStatusClass ?? '',
                'class_rank'        => $result->class_rank ?? '',
                'section_rank'      => $result->section_rank ?? '',
                'attendance'        => $result->attendance_percentage ?? '',
                'attendance_percentage' => $result->attendance_percentage ?? '',
                'working_days'      => $result->working_days ?? '',
                'present_days'      => $result->present_days ?? '',
                'absent_days'       => $result->absent_days ?? '',
                'principal_remarks' => $result->principal_remarks ?? '',
                'teacher_remarks'   => $result->teacher_remarks ?? '',
                'subject_table'     => is_array($subjectTable) ? ($subjectTable['html'] ?? '') : $subjectTable,
                'exam_table'        => '',
                'aggregated_table'  => '',
                'overall_summary'   => '',
                'exam_summary'      => '',
                'optional_summary' => is_array($subjectTable) ? ($subjectTable['optinal_summary'] ?? '') : '',
                'optional_summary_horizontal' => is_array($subjectTable) ? ($subjectTable['optinal_summary_horizontal'] ?? '') : '',
                'exam_wise_summary_rows' => [],
                'final_average_total' => 0,
                'final_average_percentage' => 0,
                'final_average_gpa' => 0,
                'final_average_grade' => '',
                'final_average_position' => '-',
                'promotion_info'    => $promotionInfo,
                'highest_mark'      => $highestMark,
                'student_photo'     => $studentPhoto,
                'class_teacher_signature' => $classTeacherSignature,
                'principal_signature' => $principalSignature,
                'grading_chart'     => $gradingChart,
                'qr_code'           => $qrCode,
                'template_bg'       => !empty($template->template_bg) ? base_url('public/uploads/templates/' . $template->template_bg) : '',
                'current_date'      => date('d-m-Y'),
                'current_year'      => date('Y'),
            ];

            $renderedContent = $this->renderTemplate($template->template_content, $placeholderData);
            $templateStyle = $template->template_style ?? '';
        }

        if (!$renderedContent) {
            return redirect()->to('examination/reports/individual-result')->with('error', 'No template found for this school.');
        }

        // Build full HTML document for PDF
        $pdfHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Result - ' . esc($schoolName) . '</title>';
        $pdfHtml .= '<style>';
        $pdfHtml .= 'body { font-family: Arial, sans-serif; margin: 0; padding: 50px 30px; position: relative; font-size: 12px; }';
        $pdfHtml .= 'html { margin: 0; padding: 0; }';
        $pdfHtml .= '* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }';
        
        // Add background image CSS if template has one
        $templateBg = $template->template_bg ?? '';
        if (!empty($templateBg)) {
            $bgPath = base_url('public/uploads/templates/' . $templateBg);
            $pdfHtml .= '.result-background {';
            $pdfHtml .= 'position: fixed;';
            $pdfHtml .= 'top: 0;';
            $pdfHtml .= 'left: 0;';
            $pdfHtml .= 'width: 100%;';
            $pdfHtml .= 'height: 100%;';
            $pdfHtml .= 'z-index: -1;';
            $pdfHtml .= 'background-image: url(\'' . $bgPath . '\');';
            $pdfHtml .= 'background-size: cover;';
            $pdfHtml .= 'background-position: center;';
            $pdfHtml .= 'background-repeat: no-repeat;';
            $pdfHtml .= '}';
        }
        
        if ($templateStyle) {
            $pdfHtml .= $templateStyle;
        }
        $pdfHtml .= '
        p {
            margin: 0;
            padding: 0;
        }
        ';
        $pdfHtml .= '</style></head><body>';
        
        // Add background div if template has background image
        if (!empty($templateBg)) {
            $pdfHtml .= '<div class="result-background"></div>';
        }
        
        $pdfHtml .= $renderedContent;
        $pdfHtml .= '</body></html>';

        // Generate PDF using Dompdf
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('margin_top', 0.5);
        $options->set('margin_bottom', 0.5);
        $options->set('margin_left', 0.5);
        $options->set('margin_right', 0.5);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($pdfHtml);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        $filename = 'Result_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $schoolName) . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $studentName) . '_' . date('Ymd-His') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    // getHighestMark
    protected function getHighestMark(int $school_id, int $exam_id, int $class_id, int $student_id): float
    {
        $highestMark = $this->ExamResultModel
            ->select('MAX(obtained_marks) as highest_mark')
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->first();

        return $highestMark ? (float) ($highestMark->highest_mark ?? 0) : 0;
    }

    /**
     * Send result PDF via email
     * Route: individual-result/send-email
     */
    public function sendEmail()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $token = $this->request->getPost('token');
        $email = $this->request->getPost('email');
        $subject = $this->request->getPost('subject');
        $message = $this->request->getPost('message');

        if (!$token || !$email || !$subject) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing required fields']);
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid email address']);
        }

        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        // Find result by token
        $result = $this->ExamResultModel
            ->where('token', $token)
            ->first();

        if (!$result) {
            return $this->response->setJSON(['success' => false, 'message' => 'Result not found']);
        }

        $school_id = $result->school_id;
        $exam_id = $result->exam_id;
        $class_id = $result->class_id;
        $student_id = $result->student_id;
        $year_id = $result->session_id ?? 0;

        // Access check
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }

        try {
            // Get school info
            $school = $this->SchoolModel->find($school_id);
            if (!$school) {
                return $this->response->setJSON(['success' => false, 'message' => 'School not found']);
            }

            // Get student info
            $student = $this->StudentModel
                ->select('students.*, student_enrollments.roll_no')
                ->join('student_enrollments', 'student_enrollments.student_id = students.id', 'left')
                ->where('students.id', $student_id)
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.class_id', $class_id)
                ->where('students.status', 1)
                ->first();

            if (!$student) {
                return $this->response->setJSON(['success' => false, 'message' => 'Student not found']);
            }

            // Get enrollment
            $enrollment = $this->EnrollmentModel
                ->where('student_id', $student_id)
                ->where('school_id', $school_id)
                ->where('class_id', $class_id)
                ->where('session_id', $year_id)
                ->first();

            if (!$enrollment) {
                $enrollment = $this->EnrollmentModel
                    ->where('student_id', $student_id)
                    ->where('school_id', $school_id)
                    ->where('class_id', $class_id)
                    ->orderBy('session_id', 'DESC')
                    ->first();
            }

            // Generate PDF content (reuse downloadPdf logic)
            $pdfHtml = $this->generatePdfHtml($token, $school, $student, $enrollment, $result, $year_id);
            
            // Load Dompdf
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('margin_top', 0.5);
            $options->set('margin_bottom', 0.5);
            $options->set('margin_left', 0.5);
            $options->set('margin_right', 0.5);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($pdfHtml);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Get PDF content
            $pdfContent = $dompdf->output();
            
            // Generate filename
            $studentName = trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
            $studentName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $studentName);
            $filename = 'Result_' . $studentName . '_' . date('Ymd-His') . '.pdf';

            // Send email using email_helper configuration
            $emailConfig = set_email_config();
            $emailService = \Config\Services::email($emailConfig);
            
            $fromEmail = setting('application', 'smtp_username');
            $fromName = setting('application', 'app_name') ?? 'School';
            
            $emailService->setFrom($fromEmail, $fromName);
            $emailService->setTo($email);
            $emailService->setSubject($subject);
            $emailService->setMessage($message);
            $emailService->setMailType('html');
            $emailService->attach($pdfContent, 'application/pdf', $filename, false);

            if ($emailService->send()) {
                return $this->response->setJSON(['success' => true, 'message' => 'Email sent successfully']);
            } else {
                $error = $emailService->printDebugger(['headers']);
                log_message('error', 'Email sending failed: ' . $error);
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to send email. Please check SMTP configuration in application settings.']);
            }

        } catch (Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate PDF HTML for email attachment
     */
    protected function generatePdfHtml(string $token, $school, $student, $enrollment, $result, int $year_id): string
    {
        $school_id = $school->id;
        $exam_id = $result->exam_id;
        $class_id = $result->class_id;
        $student_id = $student->id;

        $schoolName = $school->name ?? '';
        $schoolAddress = $school->address ?? '';
        $schoolPhone = $school->phone ?? '';
        $schoolEmail = $school->email ?? '';
        $exam = $this->ExamModel->find($exam_id);
        $class = $this->ClassModel->find($class_id);
        $year = $year_id ? $this->YearModel->find($year_id) : null;
        $examName = $exam ? $exam->title : '';
        $className = $class ? $class->title : '';
        $sessionName = $year ? $year->title : '';

        $schoolLogoPath = $school && !empty($school->logo) ? base_url('uploads/' . $school->logo) : base_url('uploads/default.png');
        $schoolLogoHtml = '<img src="' . $schoolLogoPath . '" alt="School Logo" style="max-width:100px;">';

        $studentName = trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
        $studentName = preg_replace('/\s+/', ' ', $studentName);

        $guardian = $this->StudentGuardianModel
            ->where('school_id', $school_id)
            ->where('student_id', $student->id)
            ->orderBy('id', 'ASC')
            ->first();
        $guardianName = $guardian ? ($guardian->name ?? '') : '';
        $studentPhone = $student->phone ?? ($guardian->phone ?? '');

        $sectionInfo = $enrollment->section_id ? $this->SectionModel->find($enrollment->section_id) : null;
        $shiftInfo = $enrollment->shift_id ? $this->ShiftModel->find($enrollment->shift_id) : null;
        $sectionName = $sectionInfo ? $sectionInfo->title : '';
        $shiftName = $shiftInfo ? $shiftInfo->title : '';

        $subjectMarks = $this->StudentSubjectModel->get_student_subject_result_categories(
            $school_id,
            $enrollment->id,
            $exam_id,
            $class_id,
            $year_id,
            $student
        );

        $template = $this->getTemplateForSchool($school_id);
        $renderedContent = '';
        $templateStyle = '';

        if ($template) {
            $passed = (int) ($result->passed_subjects ?? 0);
            $failed = (int) ($result->failed_subjects ?? 0);
            $total = (int) ($result->total_subjects ?? 0);
            $resultStatus = $failed > 0 ? "FAILED ({$passed}/{$total})" : "PASSED";
            $resultStatusClass = $failed > 0 ? 'text-danger' : 'text-success';

            $gradeRemarks = $this->getGradeRemarks($result->letter_grade ?? $result->grade ?? '', $school->school_owner_uid ?? 0);

            $distributionData = $this->getDistributionMarks($school_id, $exam_id, $class_id, $student->id);
            $showHighestMark = false;
            if ($school && !empty($school->params)) {
                $schoolParams = json_decode($school->params, true);
                $showHighestMark = !empty($schoolParams['show_highest_mark']);
            }
            $subjectTable = $this->generateSubjectTable($subjectMarks, $result, $showHighestMark, $distributionData);

            $promotionInfo = '';
            if ($failed > 0) {
                $promotionInfo = '<div style="padding:10px; background:#fff3cd; border-left:4px solid #ffc107; margin-top:15px;">';
                $promotionInfo .= '<strong>Remedial Required:</strong> Student has failed in ' . $failed . ' subject(s).';
                $promotionInfo .= '</div>';
            } else {
                $promotionInfo = '<div style="padding:10px; background:#d4edda; border-left:4px solid #28a745; margin-top:15px;">';
                $promotionInfo .= '<strong>Promoted:</strong> Student has passed all subjects.';
                $promotionInfo .= '</div>';
            }

            $studentPhotoPath = $student->photo && !empty($student->photo)
                ? base_url('uploads/' . $student->photo)
                : base_url('uploads/default.png');
            $studentPhoto = '<img src="' . $studentPhotoPath . '" class="student-photo" alt="Student Photo" style="width:100px;height:auto;">';

            $classTeacherSignature = '';
            if (!empty($template->class_teacher_signature)) {
                $classTeacherSignature_path = base_url('public/uploads/templates/' . $template->class_teacher_signature);
                $classTeacherSignature = '<img src="' . $classTeacherSignature_path . '" alt="Class Teacher Signature" style="width: 100px; height: auto; ">';
            }

            $principalSignature = '';
            if (!empty($template->principal_signature)) {
                $principalSignature_path = base_url('public/uploads/templates/' . $template->principal_signature);
                $principalSignature = '<img src="' . $principalSignature_path . '" alt="Principal Signature" style="width: 100px; height: auto; ">';
            }

            $gradingChart = $this->generateGradingChart($school->school_owner_uid ?? 0, $school_id);

            $qrCodeUrl = base_url('examination/result/' . $token);
            $qrCode = '<div style="text-align:center;padding:10px;">';
            $qrCode .= '<img src="' . generate_qr_code($qrCodeUrl, 80) . '" alt="QR Code" style="width:80px;height:80px;">';
            $qrCode .= '<p style="margin-top:5px;font-size:10px;color:#666;">Scan to verify result authenticity</p>';
            $qrCode .= '</div>';

            $highestMarkRecord = $this->SubjectResultModel
                ->select('MAX(obtained_mark) as highest_mark')
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('student_id', $student->id)
                ->first();
            $highestMark = $highestMarkRecord ? $highestMarkRecord->highest_mark : 0;

            $distributionData = $this->getDistributionMarks($school_id, $exam_id, $class_id, $student->id);
            $showHighestMark = false;
            if ($school && !empty($school->params)) {
                $schoolParams = json_decode($school->params, true);
                $showHighestMark = !empty($schoolParams['show_highest_mark']);
            }
            $subjectTable = $this->generateSubjectTable($subjectMarks, $result, $showHighestMark, $distributionData);

            $placeholderData = [
                'school_name' => $schoolName,
                'school_logo' => $schoolLogoHtml,
                'school_address' => $schoolAddress,
                'school_phone' => $schoolPhone,
                'school_email' => $schoolEmail,
                'school_website' => $school->website ?? '',
                'class_name' => $className,
                'section_name' => $sectionName,
                'session_name' => $sessionName,
                'shift_name' => $shiftName,
                'exam_name' => $examName,
                'exam_year' => $year ? $year->title : '',
                'student_name' => $studentName,
                'student_code' => $student->student_code ?? '',
                'roll_no' => $enrollment->roll_no,
                'registration_no' => $student->registration_no ?? '',
                'guardian_name' => $guardianName,
                'student_phone' => $studentPhone,
                'total_subjects' => $total,
                'passed_subjects' => $passed,
                'failed_subjects' => $failed,
                'total_marks' => $result->obtained_marks ?? 0,
                'full_marks' => $result->total_marks ?? 0,
                'percentage' => $result->percentage ?? 0,
                'gpa' => $result->gpa ?? 0,
                'grade' => $result->letter_grade ?? $result->grade ?? '',
                'grade_remarks' => $gradeRemarks,
                'result_status' => $resultStatus,
                'result_status_class' => $resultStatusClass ?? '',
                'class_rank' => $result->class_rank ?? '',
                'section_rank' => $result->section_rank ?? '',
                'attendance' => $result->attendance_percentage ?? '',
                'attendance_percentage' => $result->attendance_percentage ?? '',
                'working_days' => $result->working_days ?? '',
                'present_days' => $result->present_days ?? '',
                'absent_days' => $result->absent_days ?? '',
                'principal_remarks' => $result->principal_remarks ?? '',
                'teacher_remarks' => $result->teacher_remarks ?? '',
                'subject_table' => is_array($subjectTable) ? ($subjectTable['html'] ?? '') : $subjectTable,
                'exam_table' => '',
                'aggregated_table' => '',
                'overall_summary' => '',
                'exam_summary' => '',
                'optional_summary' => is_array($subjectTable) ? ($subjectTable['optinal_summary'] ?? '') : '',
                'optional_summary_horizontal' => is_array($subjectTable) ? ($subjectTable['optinal_summary_horizontal'] ?? '') : '',
                'exam_wise_summary_rows' => [],
                'final_average_total' => 0,
                'final_average_percentage' => 0,
                'final_average_gpa' => 0,
                'final_average_grade' => '',
                'final_average_position' => '-',
                'promotion_info' => $promotionInfo,
                'highest_mark' => $highestMark,
                'student_photo' => $studentPhoto,
                'class_teacher_signature' => $classTeacherSignature,
                'principal_signature' => $principalSignature,
                'grading_chart' => $gradingChart,
                'qr_code' => $qrCode,
                'template_bg' => !empty($template->template_bg) ? base_url('public/uploads/templates/' . $template->template_bg) : '',
                'current_date' => date('d-m-Y'),
                'current_year' => date('Y'),
            ];

            $renderedContent = $this->renderTemplate($template->template_content, $placeholderData);
            $templateStyle = $template->template_style ?? '';
        }

        if (!$renderedContent) {
            throw new Exception('No template found for this school');
        }

        // Build full HTML document for PDF
        $pdfHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Result - ' . esc($schoolName) . '</title>';
        $pdfHtml .= '<style>';
        $pdfHtml .= 'body { font-family: Arial, sans-serif; margin: 0; padding: 0; position: relative; }';
        $pdfHtml .= 'html { margin: 0; padding: 0; }';
        $pdfHtml .= '* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }';

        $templateBg = $template->template_bg ?? '';
        if (!empty($templateBg)) {
            $bgPath = base_url('public/uploads/templates/' . $templateBg);
            $pdfHtml .= '.result-background {';
            $pdfHtml .= 'position: fixed;';
            $pdfHtml .= 'top: 0;';
            $pdfHtml .= 'left: 0;';
            $pdfHtml .= 'width: 100%;';
            $pdfHtml .= 'height: 100%;';
            $pdfHtml .= 'z-index: -1;';
            $pdfHtml .= 'background-image: url(\'' . $bgPath . '\');';
            $pdfHtml .= 'background-size: cover;';
            $pdfHtml .= 'background-position: center;';
            $pdfHtml .= 'background-repeat: no-repeat;';
            $pdfHtml .= '}';
        }

        if ($templateStyle) {
            $pdfHtml .= $templateStyle;
        }
        $pdfHtml .= '
        p {
            margin: 0;
            padding: 0;
        }
        ';
        $pdfHtml .= '</style></head><body>';

        if (!empty($templateBg)) {
            $pdfHtml .= '<div class="result-background"></div>';
        }

        $pdfHtml .= $renderedContent;
        $pdfHtml .= '</body></html>';

        return $pdfHtml;
    }

    /**
     * Show individual result details by token
     */
    public function details($token = null, bool $publicAccess = false)
    {
        $user_id = $this->getUserId();
        if (!$user_id && !$publicAccess) {
            return redirect()->to('login');
        }

        if (!$token) {
            if ($publicAccess) {
                return view('App\Modules\examination\Views\public\result_not_found');
            }

            return redirect()->to('examination/reports/individual-result')->with('error', 'Invalid token.');
        }

        $header_data = [
            'page_title' => 'Individual Result Details',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        // Find result by token
        $result = $this->ExamResultModel
            ->where('token', $token)
            ->first();

        if (!$result) {
            if ($publicAccess) {
                return view('App\Modules\examination\Views\public\result_not_found');
            }

            return redirect()->to('examination/reports/individual-result')->with('error', 'Result not found.');
        }

        $school_id  = $result->school_id;
        $exam_id    = $result->exam_id;
        $class_id   = $result->class_id;
        $student_id = $result->student_id;
        $year_id    = $result->session_id ?? 0;

        $data = compact('school_id', 'exam_id', 'class_id', 'student_id', 'year_id');
        $data['school_list'] = $this->getSchoolDropdown();
        $data['year_list']   = [];
        $data['class_list']  = [];
        $data['exam_list']   = [];
        $data['result']      = null;
        $data['subject_marks'] = [];
        $data['school_name'] = '';
        $data['exam_name']   = '';
        $data['class_name']  = '';
        $data['session_name'] = '';
        $data['student']     = null;
        $data['rendered_content'] = '';
        $data['template_type'] = '';
        $data['template_style'] = '';

        if ($school_id) {
            $data['year_list']  = $this->getActiveOptions($school_id, 'YearModel');
            $data['class_list'] = $this->getActiveOptions($school_id, 'ClassModel');
            $data['exam_list']  = $this->getActiveOptions($school_id, 'ExamModel');
        }

        // Get school, exam, class, year names
        $school = $this->SchoolModel->find($school_id);
        $exam   = $this->ExamModel->find($exam_id);
        $class  = $this->ClassModel->find($class_id);
        $year   = $year_id ? $this->YearModel->find($year_id) : null;

        if (!$school && $publicAccess) {
            return view('App\Modules\examination\Views\public\result_not_found');
        }

        if ($publicAccess) {
            $user_id = (int) ($school->school_owner_uid ?? 0);
        }
        $data['school_name'] = $school ? $school->name : '';
        $data['school_address'] = $school ? $school->address : '';
        $data['school_phone'] = $school ? $school->phone : '';
        $data['school_email'] = $school ? $school->email : '';

        // Get school logo
        $school_logo_path = $school && !empty($school->logo) ? base_url('uploads/'.$school->logo) : base_url('uploads/default.png');
        $school_logo_html = '<img src="' . $school_logo_path . '" alt="School Logo" class="school-logo">';
       
        $data['school_logo'] = $school_logo_html;
        $data['exam_name']   = $exam ? $exam->title : '';
        $data['class_name']  = $class ? $class->title : '';
        $data['session_name'] = $year ? $year->title : '';

        // Find student
        $student = $this->StudentModel
            ->select('students.*, student_enrollments.roll_no')
            ->join('student_enrollments', 'student_enrollments.student_id = students.id', 'left')
            ->where('students.id', $student_id)
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1)
            ->first();

        $enrollment = $this->EnrollmentModel
            ->where('student_id', $student_id)
            ->where('school_id', $school_id)
            ->where('class_id', $class_id)
            ->where('session_id', $year_id)
            ->first();

        // Check if student exists
        if (!$student) {
            if ($publicAccess) {
                return view('App\Modules\examination\Views\public\result_not_found');
            }

            return redirect()->to('examination/reports/individual-result')->with('error', 'Student not found.');
        }
        
        // Check if enrollment exists, if not try to get any enrollment for this student
        if (!$enrollment) {
            $enrollment = $this->EnrollmentModel
                ->where('student_id', $student_id)
                ->where('school_id', $school_id)
                ->where('class_id', $class_id)
                ->orderBy('session_id', 'DESC')
                ->first();
            
            if (!$enrollment) {
                if ($publicAccess) {
                    return view('App\Modules\examination\Views\public\result_not_found');
                }

                return redirect()->to('examination/reports/individual-result')->with('error', 'Student enrollment not found.');
            }
        }

        $studentObj = new \stdClass();
        $studentObj->id              = $student->id;
        $studentObj->first_name      = $student->first_name;
        $studentObj->middle_name     = $student->middle_name;
        $studentObj->last_name       = $student->last_name;
        $studentObj->student_code    = $student->student_code;
        $studentObj->registration_no = $student->registration_no ?? '';
        $studentObj->photo           = $student->photo;
        $studentObj->roll_no         = $enrollment->roll_no;
        $studentObj->enrollment_id   = $enrollment->id;
        $studentObj->phone           = $student->phone ?? '';

        $data['student'] = $studentObj;

        // Get guardian info
        $guardian = $this->StudentGuardianModel
            ->where('school_id', $school_id)
            ->where('student_id', $student->id)
            ->orderBy('id', 'ASC')
            ->first();
            // Get guardian info
        $guardians = $this->StudentGuardianModel
            ->where('school_id', $school_id)
            ->where('student_id', $student->id)
            ->orderBy('id', 'ASC')
            ->findAll();

        $fatherName = '';
        $motherName = '';
        $guardianName = '';
        foreach ($guardians as $g) {
            $relation = strtolower($g->relation_type ?? '');
            if (strpos($relation, 'father') !== false) {
                $fatherName = $g->name ?? '';
            } elseif (strpos($relation, 'mother') !== false) {
                $motherName = $g->name ?? '';
            } else {
                $guardianName = $g->name ?? '';
            }
        }

        $data['father_name'] = $fatherName;
        $data['mother_name'] = $motherName;
        $data['guardian_name'] = $guardianName;
        $data['student_phone'] = $studentObj->phone ?: ($guardian->phone ?? '');

        // Get section/shift info
        $sectionInfo = $enrollment->section_id ? $this->SectionModel->find($enrollment->section_id) : null;
        $shiftInfo = $enrollment->shift_id ? $this->ShiftModel->find($enrollment->shift_id) : null;
        $data['section_name'] = $sectionInfo ? $sectionInfo->title : '';
        $data['shift_name'] = $shiftInfo ? $shiftInfo->title : '';

        $data['result'] = $result;

        
        // Now jain the main subjects and optional subjects to get the final subject list for this student
        //$subject_ids = array_merge(array_column($main_subjects, 'subject_id'), array_column($optional_subjects, 'optional_subject_id'));

        // Get categorized subjects using the model method
        $subjectMarks = $this->StudentSubjectModel->get_student_subject_result_categories($school_id, $enrollment->id, $exam_id, $class_id, $year_id, $student);
        $data['subject_marks'] = $subjectMarks;

        // Render template
        $template = $this->getTemplateForSchool($school_id, $user_id, !$publicAccess);
        if ($publicAccess && $template) {
            $user_id = (int) ($template->school_owner_uid ?? 0);
        }
        if ($template) {
            $studentName = trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
            $studentName = preg_replace('/\s+/', ' ', $studentName);
            
            $passed = (int) ($result->passed_subjects ?? 0);
            $failed = (int) ($result->failed_subjects ?? 0);
            $total  = (int) ($result->total_subjects ?? 0);
            $resultStatus = $failed > 0 ? "FAILED ({$passed}/{$total})" : "PASSED";
            $resultStatusClass = $failed > 0 ? 'text-danger' : 'text-success';

            $gradeRemarks = $this->getGradeRemarks($result->letter_grade ?? $result->grade ?? '', $user_id);
            
            // Get highest mark for this exam
            $highestMarkRecord = $this->SubjectResultModel
                ->select('MAX(obtained_mark) as highest_mark')
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('student_id', $student->id)
                ->first();
            $highestMark = $highestMarkRecord ? $highestMarkRecord->highest_mark : 0;
            
            // Get student photo
            $studentPhoto_path = $student->photo && !empty($student->photo) 
                ? base_url('uploads/'.$student->photo) 
                : base_url('uploads/default.png');

            $studentPhoto = '<img src="' . $studentPhoto_path . '" class="student-photo" alt="Student Photo" style="width: 100px; height: auto; ">';

            // class_teacher_signature
            $classTeacherSignature = '';
            if (!empty($template->class_teacher_signature)) {
                $classTeacherSignature_path = base_url('public/uploads/templates/'.$template->class_teacher_signature);
                $classTeacherSignature = '<img src="' . $classTeacherSignature_path . '" alt="Class Teacher Signature" style="width: 100px; height: auto; ">';
            }
            
            // Get principal signature from school params
            $principalSignature = '';
            if (!empty($template->principal_signature)) {
                $principalSignature_path = base_url('public/uploads/templates/'.$template->principal_signature);
                $principalSignature = '<img src="' . $principalSignature_path . '" alt="Principal Signature" style="width: 100px; height: auto; ">';
            }
            
            // Generate QR Code with verification link using token
            $qrCodeUrl = base_url('examination/result/' . $token);
            $qrCode = '<div style="text-align:center;padding:10px;">';
            $qrCode .= '<img src="' . generate_qr_code($qrCodeUrl, 80) . '" alt="QR Code" style="width:80px;height:80px;">';
            $qrCode .= '<p style="margin-top:5px;font-size:10px;color:#666;">Scan to verify result authenticity</p>';
            $qrCode .= '</div>';
            
            // Generate grading chart
            $gradingChart = $this->generateGradingChart($user_id, $school_id);
            
            // Check if highest mark should be shown
            $showHighestMark = false;
            if ($school && !empty($school->params)) {
                $schoolParams = json_decode($school->params, true);
                $showHighestMark = !empty($schoolParams['show_highest_mark']);
            }
            
            // Get distribution marks for this student
            $distributionData = $this->getDistributionMarks($school_id, $exam_id, $class_id, $student->id);
            
            // For individual result, only one exam
            $subjectTable = $this->generateSubjectTable($subjectMarks, $result, $showHighestMark, $distributionData);

            // Set placeholders
            $examTable = '';
            $aggregatedTable = '';
            $overallSummary = '';
            $examSummary = '';

           
            
            // Promotion info
            $promotionInfo = '';
            if ($failed > 0) {
                $promotionInfo = '<div style="padding:10px; background:#fff3cd; border-left:4px solid #ffc107; margin-top:15px;">';
                $promotionInfo .= '<strong>Remedial Required:</strong> Student has failed in ' . $failed . ' subject(s). ';
                $promotionInfo .= 'Additional classes and re-examination required for promotion.';
                $promotionInfo .= '</div>';
            } else {
                $promotionInfo = '<div style="padding:10px; background:#d4edda; border-left:4px solid #28a745; margin-top:15px;">';
                $promotionInfo .= '<strong>Promoted:</strong> Student has passed all subjects and is eligible for promotion to the next class.';
                $promotionInfo .= '</div>';
            }

            // Pass Token
            $data['token'] = $token;

            $data['template_type'] = $template->template_type ?? '';
            $data['template_style'] = $template->template_style ?? '';

            // orientation
            $data['orientation'] = $template->orientation ?? 'portrait';
            
            // Template background image
            $data['template_bg'] = '';
            if (!empty($template->template_bg)) {
                $data['template_bg'] = base_url('public/uploads/templates/' . $template->template_bg);
            }

            // Add attendance data to main $data array for view access
            // $data['working_days'] = $result->working_days ?? '';
            // $data['present_days'] = $result->present_days ?? '';
            // $data['absent_days'] = $result->absent_days ?? '';
            // $data['attendance_percentage'] = $result->attendance_percentage ?? '';
            
            $data['rendered_content'] = $this->renderTemplate($template->template_content, [
                'school_name'      => $data['school_name'],
                'school_logo'      => $data['school_logo'],
                'school_address'   => $data['school_address'],
                'school_phone'     => $data['school_phone'],
                'school_email'     => $data['school_email'],
                'school_website'   => $school->website ?? '',
                'class_name'       => $data['class_name'],
                'section_name'     => $data['section_name'],
                'session_name'     => $data['session_name'],
                'shift_name'       => $data['shift_name'],
                'exam_name'        => $data['exam_name'],
                'exam_year'        => $year ? $year->title : '',
                'student_name'     => $studentName,
                'father_name'      => $data['father_name'] ?? '',
                'student_code'     => $student->student_code ?? '',
                'roll_no'          => $enrollment->roll_no,
                'registration_no'  => $studentObj->registration_no ?? '',
                'guardian_name'    => $data['guardian_name'] ?? '',
                'student_phone'    => $data['student_phone'] ?? '',
                'total_subjects'   => $total,
                'passed_subjects'  => $passed,
                'failed_subjects'  => $failed,
                'total_marks'      => $result->obtained_marks ?? 0,
                'full_marks'       => $result->total_marks ?? 0,
                'percentage'       => $result->percentage ?? 0,
                'gpa'              => $result->gpa ?? 0,
                'grade'            => $result->letter_grade ?? $result->grade ?? '',
                'grade_remarks'    => $gradeRemarks,
                'result_status'    => $resultStatus,
                'result_status_class' => $resultStatusClass ?? '',
                'class_rank'       => $result->class_rank ?? '',
                'section_rank'     => $result->section_rank ?? '',
                'attendance'       => $result->attendance_percentage ?? '',
                'working_days'     => $result->working_days ?? '',
                'present_days'     => $result->present_days ?? '',
                'absent_days'      => $result->absent_days ?? '',
                'principal_remarks' => $result->principal_remarks ?? '',
                'teacher_remarks'  => $result->teacher_remarks ?? '',
                'subject_table'    => is_array($subjectTable) ? ($subjectTable['html'] ?? '') : $subjectTable,
                'exam_table'       => $examTable,
                'aggregated_table' => $aggregatedTable,
                'overall_summary'  => $overallSummary,
                'exam_summary'     => $examSummary,
                'optional_summary' => is_array($subjectTable) ? ($subjectTable['optinal_summary'] ?? '') : '',
                'optional_summary_horizontal' => is_array($subjectTable) ? ($subjectTable['optinal_summary_horizontal'] ?? '') : '',
                'exam_wise_summary_rows' => [],
                'final_average_total' => 0,
                'final_average_percentage' => 0,
                'final_average_gpa' => 0,
                'final_average_grade' => '',
                'final_average_position' => '-',
                'promotion_info'   => $promotionInfo,
                'highest_mark'     => $highestMark,
                'student_photo'    => $studentPhoto,
                'class_teacher_signature' => $classTeacherSignature,
                'principal_signature' => $principalSignature,
                'grading_chart'    => $gradingChart,
                'qr_code'          => $qrCode,
            ]);
        }

        if ($publicAccess) {
            return view('App\Modules\examination\Views\public\result_view', $data);
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\individual_result_details', $data)
            . view('footer', $footer_data);
    }

    /**
     * Render details without authentication for the token-based verification URL.
     */
    public function publicDetails(string $token)
    {
        return $this->details($token, true);
    }
}

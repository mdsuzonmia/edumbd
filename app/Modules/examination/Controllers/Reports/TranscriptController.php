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
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\ResultTemplateModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Modules\examination\Models\FinalResultModel;
use App\Modules\examination\Models\FinalResultSubjectModel;
use App\Modules\examination\Models\GradeSystemModel;
use App\Modules\examination\Models\GradeRuleModel;
use App\Modules\examination\Models\StudentSubjectModel;

class TranscriptController extends BaseReportController
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
    protected FinalResultModel $FinalResultModel;
    protected FinalResultSubjectModel $FinalResultSubjectModel;
    protected GradeSystemModel $GradeSystemModel;
    protected GradeRuleModel $GradeRuleModel;
    protected StudentGuardianModel $StudentGuardianModel;
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
        $this->TemplateModel           = new ResultTemplateModel();
        $this->SubjectModel            = new SubjectModel();
        $this->MarkDistributionModel   = new MarkDistributionModel();
        $this->ShiftModel              = new AcademicsShiftModel();
        $this->FinalResultModel        = new FinalResultModel();
        $this->FinalResultSubjectModel = new FinalResultSubjectModel();
        $this->GradeSystemModel        = new GradeSystemModel();
        $this->GradeRuleModel          = new GradeRuleModel();
        $this->StudentGuardianModel    = new StudentGuardianModel();
        $this->StudentSubjectModel     = new StudentSubjectModel();
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
     * Get the transcript template (support_multiple_exams=1 AND support_aggregated_result=1)
     */
    protected function getTemplateForSchool(int $school_id, bool $restrictToCurrentUser = true): ?object
    {
        $user_id = $this->getUserId();

        $templateQuery = $this->TemplateModel
            ->where('school_id', $school_id)
            ->where('support_multiple_exams', 1)
            ->where('support_aggregated_result', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'DESC');

        if ($restrictToCurrentUser) {
            $templateQuery->where('school_owner_uid', $user_id);
        }

        $template = $templateQuery->first();

        if ($template) {
            return $template;
        }

        // Allow a platform administrator to assign a transcript template to
        // this school without becoming the school owner.
        if ($restrictToCurrentUser) {
            $template = $this->TemplateModel
                ->where('school_id', $school_id)
                ->where('support_multiple_exams', 1)
                ->where('support_aggregated_result', 1)
                ->orderBy('is_default', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();

            if ($template) {
                return $template;
            }
        }

        // Fallback to default template
        $templateQuery = $this->TemplateModel
            ->groupStart()
                ->where('school_id', 0)
                ->orWhere('school_id IS NULL')
            ->groupEnd()
            ->where('support_multiple_exams', 1)
            ->where('support_aggregated_result', 1)
            ->where('is_default', 1);

        $template = $templateQuery->first();

        return $template;
    }

    /**
     * Render template with placeholder replacement.
     */
    protected function renderTemplate(string $template_content, array $data): string
    {
        $replacements = [
            '{school_name}'           => $data['school_name'] ?? '',
            '{school_address}'        => $data['school_address'] ?? '',
            '{school_phone}'          => $data['school_phone'] ?? '',
            '{school_email}'          => $data['school_email'] ?? '',
            '{school_website}'        => $data['school_website'] ?? '',
            '{school_logo}'           => $data['school_logo'] ?? '',
            '{student_name}'          => $data['student_name'] ?? '',
            '{student_code}'          => $data['student_code'] ?? '',
            '{roll_no}'               => $data['roll_no'] ?? '',
            '{registration_no}'       => $data['registration_no'] ?? '',
            '{class_name}'            => $data['class_name'] ?? '',
            '{section_name}'          => $data['section_name'] ?? '',
            '{session_name}'          => $data['session_name'] ?? '',
            '{shift_name}'            => $data['shift_name'] ?? '',
            '{group_name}'            => $data['group_name'] ?? '',
            '{father_name}'           => $data['father_name'] ?? '',
            '{mother_name}'           => $data['mother_name'] ?? '',
            '{date_of_birth}'         => $data['date_of_birth'] ?? '',
            '{guardian_name}'         => $data['guardian_name'] ?? '',
            '{student_phone}'         => $data['student_phone'] ?? '',
            '{student_info_table}'    => $data['student_info_table'] ?? '',
            '{subject_academic_table}' => $data['subject_academic_table'] ?? '',
            '{exam_performance_table}' => $data['exam_performance_table'] ?? '',
            '{exam_table}'            => $data['exam_table'] ?? $data['exam_performance_table'] ?? '',
            '{aggregated_table}'      => $data['aggregated_table'] ?? $data['subject_academic_table'] ?? '',
            '{exam_summary}'          => $data['exam_summary'] ?? $data['exam_performance_table'] ?? '',
            '{overall_summary}'       => $data['overall_summary'] ?? $data['final_academic_summary'] ?? '',
            '{final_academic_summary}' => $data['final_academic_summary'] ?? '',
            '{promotion_info}'        => $data['promotion_info'] ?? '',
            '{qr_code}'               => $data['qr_code'] ?? '',
            '{transcript_no}'         => $data['transcript_no'] ?? '',
            '{student_photo}'         => $data['student_photo'] ?? '',
            '{principal_remarks}'     => $data['principal_remarks'] ?? '',
            '{teacher_remarks}'       => $data['teacher_remarks'] ?? '',
            '{principal_signature}'   => $data['principal_signature'] ?? '',
            '{class_teacher_signature}' => $data['class_teacher_signature'] ?? '',
            '{grade_remarks}'         => $data['grade_remarks'] ?? '',
            '{highest_mark}'          => $data['highest_mark'] ?? '',
            '{grading_chart}'         => $data['grading_chart'] ?? '',
            '{current_date}'          => date('d-m-Y'),
            '{current_year}'          => date('Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template_content);
    }

    /**
     * Build student info HTML table.
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
     * Build the subject-wise academic performance table with multiple terms + aggregate.
     */
    protected function buildSubjectAcademicTable($transcriptData): string
    {
        // Get subject academic rows
        $subjectAcademicRows = $transcriptData['subject_academic_rows'];
        if (empty($subjectAcademicRows)) {
            return '<p>No subject data available.</p>';
        }

        // Get exam performance rows
        $exam_performance_data = $transcriptData['exam_performance_rows'];

        // aggregate_performance
        $aggregate_performance = $transcriptData['aggregate_performance'] ?? [];

        // Get all unique exam names from the first row's exam data
        $examNames = [];
        if (!empty($subjectAcademicRows[0]['exams'])) {
            foreach ($subjectAcademicRows[0]['exams'] as $exam) {
                $examNames[] = $exam['name'];
            }
        }

        // Check if any exam has distributions
        $hasDistributions = false;
        foreach ($subjectAcademicRows as $row) {
            foreach ($row['exams'] as $exam) {
                if (!empty($exam['distributions'])) {
                    $hasDistributions = true;
                    break 2;
                }
            }
        }

        // Calculate column spans
        $colspanPerExam = 3; // Total, GP, Grade
        $distribution_count = 0;
        if ($hasDistributions) {
            // Count max distributions across all exams
            $maxDistributions = 0;
            foreach ($subjectAcademicRows as $row) {
                foreach ($row['exams'] as $exam) {
                    $maxDistributions = max($maxDistributions, count($exam['distributions'] ?? []));
                }
            }
            $colspanPerExam = 5 + $maxDistributions; // Total, GP, Grade, highest + distribution columns
            $distribution_count = $maxDistributions;
        }
        $totalExamColspan = count($examNames) * $colspanPerExam;
        $aggregateColspan = 4;
        $totalColspan = 1 + $totalExamColspan + $aggregateColspan;


        // Build a map of combine groups to their subject IDs for later use
        $combine_subjects = [];
        foreach ($subjectAcademicRows as $row) {
            $combine_group = $row['combine_group'] ?? '';
            if($combine_group) {
                $combine_subjects[] = $row;
            }
        }

        // Get build a map of Compulsory Subjects
        $compulsory_subjects = [];
        foreach ($subjectAcademicRows as $row) {
            $combine_group = $row['combine_group'] ?? '';
            $optional_subject = $row['optional'] ?? 0;
            if (!$combine_group && !$optional_subject) {
                $compulsory_subjects[] = $row;
            }
        }

        // get Build a map of Optional Subjects
        $optional_subjects = [];
        foreach ($subjectAcademicRows as $row) {
            $optional_subject = $row['optional'] ?? 0;
            if ($optional_subject) {
                $optional_subjects[] = $row;
            }
        }

       

        $html = '<table width="100%" class="subject-academic-table table table-bordered" >';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th rowspan="2">SL</th>';
        $html .= '<th rowspan="2">Subject</th>';
        
        foreach ($examNames as $ename) {
            $html .= '<th colspan="' . $colspanPerExam . '" >' . esc($ename) . '</th>';
        }

        $html .= '<th colspan="5" >Aggregate Result</th>';
        $html .= '</tr>';
        
        $html .= '<tr>';
        foreach ($examNames as $ename) {
            // Distribution columns first
            if ($hasDistributions) {
                $distNames = [];
                foreach ($subjectAcademicRows as $r) {
                    foreach ($r['exams'] as $e) {
                        if (!empty($e['distributions'])) {
                            foreach ($e['distributions'] as $d) {
                                $distNames[$d['name']] = true;
                            }
                        }
                    }
                }
                foreach (array_keys($distNames) as $distName) {
                    $html .= '<th>' . esc($distName) . '</th>';
                }
            }
            
            $html .= '<th>Total</th>';
            $html .= '<th>Highest</th>';
            $html .= '<th style="text-align:center;">Average</th>';
            $html .= '<th>GP</th>';
            $html .= '<th>Grade</th>';
        }
        $html .= '<th>Total</th>';
        $html .= '<th>Percentage</th>';
        $html .= '<th>GP</th>';
        $html .= '<th>Grade</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        // 1 . Combined Subjects
        $cb_sl = 1;
        // Combined subjects - display each subject in separate rows with shared Average/%/GPA/Grade
        $count = count($combine_subjects);
        foreach ($combine_subjects as $index => $row) {
            $html .= '<tr>'; 

            // SL
            $html .= '<td>' . $cb_sl++ . '</td>';
            $html .= '<td class="subject-name">' . esc($row['subject_name'] ?? '') . '</td>';
            
            // Exam columns
            foreach ($row['exams'] as $exam) {
                // Distribution columns first
                if ($hasDistributions) {
                    $distNames = [];
                    foreach ($subjectAcademicRows as $r) {
                        foreach ($r['exams'] as $e) {
                            if (!empty($e['distributions'])) {
                                foreach ($e['distributions'] as $d) {
                                    $distNames[$d['name']] = true;
                                }
                            }
                        }
                    }
                    $distMap = [];
                    foreach ($exam['distributions'] as $d) {
                        $distMap[$d['name']] = $d['obtained'];
                    }
                    foreach (array_keys($distNames) as $distName) {
                        $value = $distMap[$distName] ?? 0;
                        if ($value === null || $value === 0 ) {
                            $html .= '<td>-</td>';
                        } else {
                            $html .= '<td>' . round($value) . '</td>';
                        }
                        
                    }
                }
                
                $html .= '<td>' . ($exam['total'] !== null ? round($exam['total']) : '-') . '</td>';
                $html .= '<td>' . ($exam['highest_mark'] > 0 ? round($exam['highest_mark']) : '-') . '</td>';

                $average_mark = $exam['average_mark'] ?? 0;
                $average_gp = $exam['average_gp'] ?? 0;
                $average_grade = $exam['average_grade'] ?? '';

                // For combined subjects: Average, %, GPA, Grade with rowspan on first subject only
                if ($index === 0) {
                    $html .= '<td rowspan="' . $count . '">' . $average_mark . '</td>';
                    $html .= '<td rowspan="' . $count . '">' . $average_gp. '</td>';
                    $html .= '<td rowspan="' . $count . '">' . $average_grade. '</td>';
                }
            }

            // Aggregate columns
            if ($index === 0) {
                $agg = $row['aggregate'];
                $html .= '<td rowspan="' . $count . '">' . round($agg['obtained'] ?? 0) . '</td>';
                $html .= '<td rowspan="' . $count . '">' . round($agg['percentage'] ?? 0) . '%</td>';
                $html .= '<td rowspan="' . $count . '">' . number_format($agg['gp'] ?? 0, 2) . '</td>';
                $html .= '<td rowspan="' . $count . '">' . esc($agg['grade'] ?? '-') . '</td>';
            }
            $html .= '</tr>';
        } // end of combined subjects

        // 2 . Compulsory Subjects
        $comp_sl = $cb_sl;
        foreach ($compulsory_subjects as $row) {
            $html .= '<tr>'; 

            // SL
            $html .= '<td>' . $comp_sl++ . '</td>';
            $html .= '<td class="subject-name">' . esc($row['subject_name'] ?? '') . '</td>';
            
            // Exam columns
            foreach ($row['exams'] as $exam) {
                // Distribution columns first
                if ($hasDistributions) {
                    $distNames = [];
                    foreach ($subjectAcademicRows as $r) {
                        foreach ($r['exams'] as $e) {
                            if (!empty($e['distributions'])) {
                                foreach ($e['distributions'] as $d) {
                                    $distNames[$d['name']] = true;
                                }
                            }
                        }
                    }
                    $distMap = [];
                    foreach ($exam['distributions'] as $d) {
                        $distMap[$d['name']] = $d['obtained'];
                    }
                    foreach (array_keys($distNames) as $distName) {
                        $value = $distMap[$distName] ?? 0;
                        if ($value > 0) {
                            $html .= '<td>' . round($value) . '</td>';
                        }else {
                            $html .= '<td>-</td>';
                        }
                        
                    }
                }
                
                $html .= '<td>' . ($exam['total'] !== null ? round($exam['total']) : '-') . '</td>';
                $html .= '<td>' . ($exam['highest_mark'] > 0 ? round($exam['highest_mark']) : '-') . '</td>';

                $html .= '<td>' . ($exam['total'] ?? '-') . '</td>';
                $html .= '<td>' . ($exam['gp'] ?? '-') . '</td>';
                $html .= '<td>' . ($exam['grade'] ?? '-') . '</td>';
            }

            // Aggregate columns
            $agg = $row['aggregate'];
            $html .= '<td>' . round($agg['obtained'] ?? 0) . '</td>';
            $html .= '<td>' . round($agg['percentage'] ?? 0) . '%</td>';
            $html .= '<td>' . number_format($agg['gp'] ?? 0, 2) . '</td>';
            $html .= '<td>' . esc($agg['grade'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        

        // 3 . Optional Subjects
        $opt_sl = $comp_sl;
        foreach ($optional_subjects as $row) {
            $html .= '<tr>'; 

            // SL
            $html .= '<td>' . $opt_sl++ . '</td>';
            $html .= '<td class="subject-name">' . esc($row['subject_name'] ?? '') . '<sup>*</sup> </td>';
            
            // Exam columns
            foreach ($row['exams'] as $exam) {
                // Distribution columns first
                if ($hasDistributions) {
                    $distNames = [];
                    foreach ($subjectAcademicRows as $r) {
                        foreach ($r['exams'] as $e) {
                            if (!empty($e['distributions'])) {
                                foreach ($e['distributions'] as $d) {
                                    $distNames[$d['name']] = true;
                                }
                            }
                        }
                    }
                    $distMap = [];
                    foreach ($exam['distributions'] as $d) {
                        $distMap[$d['name']] = $d['obtained'];
                    }
                    foreach (array_keys($distNames) as $distName) {
                        $value = $distMap[$distName] ?? 0;
                        if ($value === null || $value === 0 ) {
                            $html .= '<td>-</td>';
                        } else {
                            $html .= '<td>' . round($value) . '</td>';
                        }
                        
                    }
                }
                $html .= '<td>' . ($exam['total'] !== null ? round($exam['total']) : '-') . '</td>';
                $html .= '<td>' . ($exam['highest_mark'] > 0 ? round($exam['highest_mark']) : '-') . '</td>';
                $html .= '<td>' . ($exam['total'] ?? '-') . '</td>';
                $html .= '<td>' . ($exam['gp'] ?? '-') . '</td>';
                $html .= '<td>' . ($exam['grade'] ?? '-') . '</td>';
            }

            // Aggregate columns
            $agg = $row['aggregate'];
            $html .= '<td>' . round($agg['obtained'] ?? 0) . '</td>';
            $html .= '<td>' . round($agg['percentage'] ?? 0) . '%</td>';
            $html .= '<td>' . number_format($agg['gp'] ?? 0, 2) . '</td>';
            $html .= '<td>' . esc($agg['grade'] ?? '-') . '</td>';
            $html .= '</tr>';
        }

        // 4 . summary row for total marks, percentage, GPA, Grade
        $html .= '<tr>';
        // sl
        $html .= '<td colspan="2" class="subject-name">Total</td>';

        
        // Exam columns
        foreach ($exam_performance_data as $key => $exam_performance_row) {
            $exam_total = $exam_performance_row['total'] ?? 0;
            $exam_gpa = $exam_performance_row['gpa'] ?? 0;
            $exam_grade = $exam_performance_row['grade'] ?? '';
            $total_grade_point = $exam_performance_row['total_grade_point'] ?? 0;

            // Total column for each exam
            $html .= '<td colspan="'.($distribution_count + 2).'"></td>';
            
            $html .= '<td>' . round($exam_total) . '</td>';
            $html .= '<td>' . number_format($total_grade_point, 2) . '</td>';
            $html .= '<td>' . esc($exam_grade) . '</td>';
        }

        // Aggregate columns
        $aggregateTotal = $aggregate_performance['total'] ?? 0;
        $aggregatePercentage = $aggregate_performance['percentage'] ?? 0;
        $aggregateGPA = $aggregate_performance['gpa'] ?? 0;
        $aggregateGrade = $aggregate_performance['grade'] ?? '';

        $html .= '<td>' . round($aggregateTotal) . '</td>';
        $html .= '<td>' . round($aggregatePercentage) . '%</td>';
        $html .= '<td>' . number_format($aggregateGPA, 2) . '</td>';
        $html .= '<td>' . esc($aggregateGrade) . '</td>';
        
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';


        // 5 . Summary of working days, present days, and attendance percentage
        $html .= '<table width="100%" class="subject-academic-table table table-bordered" style="margin-top: 10px;" >';
        $html .= '<tr>';
        foreach ($exam_performance_data as $key => $exam_performance_row) {
            $html .= '<th colspan="5">' . esc($exam_performance_row['exam_name'] ?? '') . '</th>';
        }
        $html .= '</tr>';
        $html .= '<tr>';
        foreach ($exam_performance_data as $key => $exam_performance_row) {
            $working_days = $exam_performance_row['working_days'] ?? 0;
            $present_days = $exam_performance_row['present_days'] ?? 0;
            $absent_days = $exam_performance_row['absent_days'] ?? 0;
            $attendance_percentage = $exam_performance_row['attendance_percentage'] ?? 0;

            
            $html .= '<td colspan="2">Working Days: ' . $working_days . '</td>';
            $html .= '<td>Present: ' . $present_days . '</td>';
            $html .= '<td>Absent: ' . $absent_days . '</td>';
            $html .= '<td>Percentage: ' . $attendance_percentage . '%</td>';
            
        }
        $html .= '</tr>';
        $html .= '</table>';





        return $html;
    }

    /**
     * Build the exam-wise performance summary table.
     */
    protected function buildExamPerformanceTable(array $examPerformanceRows, array $aggregatePerformance): string
    {
        $html = '<table class="exam-performance-table table table-bordered" cellpadding="6" >';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Exam Name</th>';
        $html .= '<th>Weight</th>';
        $html .= '<th>Total</th>';
        $html .= '<th>Percentage</th>';
        $html .= '<th>GPA</th>';
        $html .= '<th>Grade</th>';
        $html .= '<th>Position</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($examPerformanceRows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . esc($row['exam_name'] ?? '') . '</td>';
            $html .= '<td>' . esc($row['weight'] ?? '') . '</td>';
            $html .= '<td>' . number_format($row['total'] ?? 0, 2) . '</td>';
            $html .= '<td>' . number_format($row['percentage'] ?? 0, 2) . '%</td>';
            $html .= '<td>' . number_format($row['gpa'] ?? 0, 2) . '</td>';
            $html .= '<td>' . esc($row['grade'] ?? '') . '</td>';
            $html .= '<td>' . ($row['position'] ?? '-') . '</td>';
            $html .= '</tr>';
        }

        // Aggregate summary row
        if (!empty($aggregatePerformance)) {
            $html .= '<tr>';
            $html .= '<td>Aggregate Result</td>';
            $html .= '<td>100%</td>';
            $html .= '<td>' . number_format($aggregatePerformance['total'] ?? 0, 2) . '</td>';
            $html .= '<td>' . number_format($aggregatePerformance['percentage'] ?? 0, 2) . '%</td>';
            $html .= '<td>' . number_format($aggregatePerformance['gpa'] ?? 0, 2) . '</td>';
            $html .= '<td>' . esc($aggregatePerformance['grade'] ?? '') . '</td>';
            $html .= '<td>' . ($aggregatePerformance['position'] ?? '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Build the final academic summary box.
     */
    protected function buildFinalAcademicSummary(array $data): string
    {
        $html = '<table class="final-summary-table table table-bordered" >';
        $html .= '<thead><tr><th colspan="2">Final Academic Summary</th></tr></thead>';
        $html .= '<tbody>';
        $html .= '<tr><td>Total Marks</td><td>: ' . number_format($data['total_marks'] ?? 0, 2) . ' / ' . number_format($data['total_full_marks'] ?? 0, 2) . '</td></tr>';
        $html .= '<tr><td>Percentage</td><td>: ' . number_format($data['percentage'] ?? 0, 2) . '%</td></tr>';
        $html .= '<tr><td>Overall GPA</td><td>: ' . number_format($data['gpa'] ?? 0, 2) . '</td></tr>';
        $html .= '<tr><td>Overall Grade</td><td>: ' . esc($data['grade'] ?? '') . '</td></tr>';
        $html .= '<tr><td>Class Position</td><td>: ' . ($data['class_position'] ?? '-') . '</td></tr>';
        $html .= '<tr><td>Section Position</td><td>: ' . ($data['section_position'] ?? '-') . '</td></tr>';

        if (!empty($data['present_days']) || !empty($data['working_days'])) {
            $attendance = ($data['present_days'] ?? 0) . ' / ' . ($data['working_days'] ?? 0);
            $html .= '<tr><td>Attendance</td><td>: ' . $attendance . '</td></tr>';
        }
        if (!empty($data['working_days'])) {
            $html .= '<tr><td>Working Days</td><td>: ' . (int) $data['working_days'] . '</td></tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Build promotion information box.
     */
    protected function buildPromotionInfo(array $data): string
    {
        $html = '<table class="promotion-table table table-bordered" >';
        $html .= '<thead><tr><th colspan="2" >Promotion Information</th></tr></thead>';
        $html .= '<tbody>';
        $html .= '<tr><td>Result</td><td>: ' . esc($data['result_status'] ?? '') . '</td></tr>';
        $html .= '<tr><td>Promotion Status</td><td>: ' . esc($data['promotion_status'] ?? '') . '</td></tr>';

        if (!empty($data['next_session'])) {
            $html .= '<tr><td>Next Academic Year</td><td>: ' . esc($data['next_session']) . '</td></tr>';
        }
        if (!empty($data['next_class'])) {
            $html .= '<tr><td>Next Class</td><td>: ' . esc($data['next_class']) . '</td></tr>';
        }
        if (!empty($data['next_section'])) {
            $html .= '<tr><td>Next Section</td><td>: ' . esc($data['next_section']) . '</td></tr>';
        }
        if (!empty($data['next_roll'])) {
            $html .= '<tr><td>Next Roll</td><td>: ' . esc($data['next_roll']) . '</td></tr>';
        }
        if (!empty($data['promotion_date'])) {
            $html .= '<tr><td>Promotion Date</td><td>: ' . esc($data['promotion_date']) . '</td></tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Get grade remarks.
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

        $html = '<table class="grading-chart-table table table-bordered" border="1" cellpadding="4" >';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="text-align:center;">Grade</th>';
        $html .= '<th style="text-align:center;">Marks Range</th>';
        $html .= '<th style="text-align:center;">Grade Point</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($gradeRules as $rule) {
            $html .= '<tr>';
            $html .= '<td style="text-align:center;font-weight:bold;">' . esc($rule->title) . '</td>';
            $html .= '<td style="text-align:center;">' . ($rule->mark_from ?? 0) . ' - ' . ($rule->mark_to ?? 0) . '</td>';
            $html .= '<td style="text-align:center;">' . ($rule->grade_point ?? 0) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    /**
     * Get the full transcript data for a student.
     */
    protected function getTranscriptData(int $school_id, int $year_id, int $class_id, int $student_id): array
    {
        $data = [
            'exams_data'             => [],
            'subject_academic_rows'  => [],
            'exam_performance_rows'  => [],
            'aggregate_performance'  => [],
            'final_result'           => null,
        ];

        // Get the final result record
        $finalResult = $this->FinalResultModel
            ->where('session_id', $year_id)
            ->where('class_id', $class_id)
            ->where('student_uid', $student_id)
            ->first();

        $data['final_result'] = $finalResult;
        if (!$finalResult) {
            return $data;
        }

        // Get all exams for this school/year ordered by exam_order
        $exams = $this->ExamModel
            ->where('school_id', $school_id)
            ->where('year_id', $year_id)
            ->where('is_aggregate_result', 1)
            ->where('status', 1)
            ->orderBy('exam_order', 'ASC')
            ->findAll();

        $data['exams_data'] = $exams;

        // Get enrollment for this student
        $enrollment = $this->EnrollmentModel
            ->where('student_id', $student_id)
            ->where('school_id', $school_id)
            ->where('class_id', $class_id)
            ->where('session_id', $year_id)
            ->first();

        if (!$enrollment) {
            return $data;
        }

        // Get Student main subject and optional subject ID where 
        $main_subjects = $this->StudentSubjectModel
            ->where('school_id', $school_id)
            ->where('enrollment_id', $enrollment->id)
            ->where('optional_subject_id', NULL)
            ->orWhere('optional_subject_id', 0)
            ->findAll();

        // Optional subjects
        $optional_subjects = $this->StudentSubjectModel
            ->where('school_id', $school_id)
            ->where('enrollment_id', $enrollment->id)
            ->where('optional_subject_id IS NOT NULL AND optional_subject_id !=', 0)
            ->findAll();
        
        // Now join the main subjects and optional subjects to get the final subject list for this student
        $subject_ids = array_merge(array_column($main_subjects, 'subject_id'), array_column($optional_subjects, 'optional_subject_id'));
        
        // Get all subjects for this class/school from subject distribution
        $allSubjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('order_number', 'ASC')
            ->findAll();
        
        // Filter subjects for this specific student
        $subjects = array_filter($allSubjects, function($subject) use ($subject_ids) {
            return in_array($subject->id, $subject_ids);
        });


        // Get subject marks
        $get_student_subject = $this->StudentSubjectModel->get_student_subjects( $school_id, $enrollment->id);
        $subjects = [];

        // 1. Combined Subjects
        if (!empty($get_student_subject['combined_subjects'])) {
            foreach ($get_student_subject['combined_subjects'] as $sm) {
                $subjects['combined_subjects'][] = $sm;
            }
        }

        // 2. Compulsory Subjects
        if (!empty($get_student_subject['compulsory_subjects'])) {
            foreach ($get_student_subject['compulsory_subjects'] as $sm) {
                $subjects['compulsory_subjects'][] = $sm;
            }
        }

        // 3. Optional Subjects
        if (!empty($get_student_subject['optional_subjects'])) {
            foreach ($get_student_subject['optional_subjects'] as $sm) {
                $subjects['optional_subjects'][] = $sm;
            }
        }
            

        // Get aggregate subject results
        $aggregateSubjects = $this->FinalResultSubjectModel
            ->where('student_uid', $student_id)
            ->where('session_id', $year_id)
            ->where('class_id', $class_id)
            ->findAll();

        $aggregateSubjectMap = [];
        foreach ($aggregateSubjects as $as) {
            $aggregateSubjectMap[$as->subject_id] = $as;
        }

        // Get mark distributions per subject (from SubjectDistributionModel)
        $db = \Config\Database::connect();
        $subjectDistributionMap = [];
        $subjectDistQuery = $db->table('examination_subject_distributions')
            ->select('examination_subject_distributions.*, examination_mark_distributions.name AS distribution_name')
            ->join('examination_mark_distributions', 'examination_mark_distributions.id = examination_subject_distributions.distribution_id', 'left')
            ->where('examination_subject_distributions.school_id', $school_id)
            ->where('examination_subject_distributions.status', 1)
            ->orderBy('examination_subject_distributions.sort_order', 'ASC')
            ->get();
        
        foreach ($subjectDistQuery->getResult() as $sd) {
            $subjectId = $sd->subject_id;
            if (!isset($subjectDistributionMap[$subjectId])) {
                $subjectDistributionMap[$subjectId] = [];
            }
            $subjectDistributionMap[$subjectId][] = $sd;
        }

        
        // Build subject academic rows
        $subjectAcademicRows = [];
        foreach ($subjects as $subjectItem) {
            foreach ($subjectItem as $index => $subject) {

                $subject_id = $subject->id;
                $subject_title = $subject->title ?? '';
                $combine_group = $subject->combine_group ?? '';
                $optional = $subject->optional ?? 0;
            
                $examData = [];
                foreach ($exams as $exam) {
                    // Get subject result for this exam
                    $subjectResult = $this->SubjectResultModel
                        ->where('school_id', $school_id)
                        ->where('exam_id', $exam->id)
                        ->where('class_id', $class_id)
                        ->where('student_id', $student_id)
                        ->where('subject_id', $subject_id)
                        ->first();

                    // Get mark distributions for this subject
                    $subjectDists = $subjectDistributionMap[$subject_id] ?? [];
                    $distributionData = [];
                    
                    if ($subjectResult && !empty($subjectDists)) {
                        foreach ($subjectDists as $dist) {
                            $markQuery = $db->table('examination_marks')
                                ->select('obtained_mark')
                                ->where('school_id', $school_id)
                                ->where('exam_id', $exam->id)
                                ->where('subject_id', $subject_id)
                                ->where('student_id', $student_id)
                                ->where('distribution_id', $dist->distribution_id)
                                ->get()
                                ->getRow();
                            
                            $distributionData[] = [
                                'name'    => $dist->distribution_name,
                                'obtained' => $markQuery ? $markQuery->obtained_mark : 0,
                            ];
                        }
                    }

                
                    if ($subjectResult) {
                        $examData[] = [
                            'exam_id'           => $exam->id,
                            'name'              => $exam->title,
                            'total'             => $subjectResult->obtained_mark ?? 0,
                            'gp'                => $subjectResult->grade_point ?? 0,
                            'grade'             => $subjectResult->letter_grade ?? $subjectResult->grade ?? '',
                            'distributions'     => $distributionData,
                            'highest_mark'      => $subjectResult->highest_mark ?? 0,
                            'obtained_mark' => $subjectResult->obtained_mark ?? 0,
                            'include_mark'      => $subjectResult->include_mark ?? 0,
                            'include_grade_point' => $subjectResult->include_grade_point ?? 0,
                            'percentage'         => $subjectResult->percentage ?? 0,
                            'combined_mark'      => $subjectResult->combined_mark ?? 0,
                            'combined_gp'         => $subjectResult->combined_gp ?? 0,
                            'combined_percentage' => $subjectResult->combined_percentage ?? 0,
                            'average_mark'      => $subjectResult->average_mark ?? 0,
                            'average_gp'        => $subjectResult->average_gp ?? 0,
                            'average_grade'     => $subjectResult->average_grade ?? '',
                            'is_fail'             => $subjectResult->is_fail ?? 0,
                        ];
                    } else {
                        $examData[] = [
                            'exam_id'           => $exam->id,
                            'name'              => $exam->title,
                            'total'             => null,
                            'gp'                => null,
                            'grade'             => '-',
                            'distributions'     => [],
                            'highest_mark'      => 0,
                            'obtained_mark' => 0,
                            'include_mark'      => 0,
                            'include_grade_point' => 0,
                            'percentage'         => 0,
                            'combined_mark'      => 0,
                            'combined_gp'         => 0,
                            'combined_percentage' => 0,
                            'is_fail'             => 0,
                        ];
                    }
                
                } // End of exams loop

                // Aggregate data
                $aggSubject = $aggregateSubjectMap[$subject_id] ?? null;
                $aggregateData = [
                    'obtained'   => $aggSubject->aggregate_obtained_mark ?? 0,
                    'full'       => $aggSubject->aggregate_full_mark ?? 0,
                    'percentage' => $aggSubject->aggregate_percentage ?? 0,
                    'gp'         => $aggSubject->grade_point ?? 0,
                    'grade'      => $aggSubject->letter_grade ?? $aggSubject->grade ?? '',
                    'is_fail'    => $aggSubject->is_fail ?? 0,
                ];
                
                // Add to subject academic rows
                $subjectAcademicRows[] = [
                    'subject_id'   => $subject_id,
                    'subject_name' =>   $subject_title,
                    'combine_group' => $combine_group,
                    'optional'     => $optional,
                    'exams'        => $examData,
                    'aggregate'    => $aggregateData,
                ];

                

            } // End of inner loop for each subject in the group
        } // End of subjects loop

        $data['subject_academic_rows'] = $subjectAcademicRows;

        // Build exam performance rows from examination_results
        $examPerformanceRows = [];
        foreach ($exams as $exam) {
            $examResult = $this->ExamResultModel
                ->where('school_id', $school_id)
                ->where('exam_id', $exam->id)
                ->where('class_id', $class_id)
                ->where('student_id', $student_id)
                ->first();

            if ($examResult) {
                $examPerformanceRows[] = [
                    'exam_name'   => $exam->title,
                    'weight'      => ($exam->weight_percentage ?? 0) . '%',
                    'total'       => $examResult->obtained_marks ?? 0,
                    'percentage'  => $examResult->percentage ?? 0,
                    'gpa'         => $examResult->gpa ?? 0,
                    'grade'       => $examResult->letter_grade ?? $examResult->grade ?? '',
                    'total_grade_point' => $examResult->total_grade_point ?? 0,
                    'working_days' => $examResult->working_days ?? 0,
                    'present_days' => $examResult->present_days ?? 0,
                    'absent_days'  => $examResult->absent_days ?? 0,
                    'attendance_percentage' => $examResult->attendance_percentage ?? 0,
                    'position'    => $examResult->class_rank ?? '-',
                ];
            }
        }

        
        $data['exam_performance_rows'] = $examPerformanceRows;

        // Aggregate performance from final result
        if ($finalResult) {
            $data['aggregate_performance'] = [
                'total'      => $finalResult->total_marks ?? 0,
                'full_marks' => $finalResult->total_full_marks ?? 0,
                'percentage' => $finalResult->percentage ?? 0,
                'gpa'        => $finalResult->gpa ?? 0,
                'grade'      => $finalResult->grade_letter ?? '',
                'position'   => $finalResult->class_position ?? '-',
            ];
        }

        return $data;
    }

    /**
     * Build the rendered transcript HTML for a student (shared by index and details).
     */
    protected function buildTranscriptHtml(
        int $school_id,
        int $year_id,
        int $class_id,
        int $student_id,
        object $finalResult,
        string $schoolName,
        string $schoolAddress,
        string $schoolPhone,
        string $schoolEmail,
        string $className,
        string $sessionName,
        string $schoolLogo,
        bool $publicAccess = false
    ): array {
        $school = $this->SchoolModel->find($school_id);
        $class  = $this->ClassModel->find($class_id);
        $year   = $this->YearModel->find($year_id);

        // Try to use template
        $template = $this->getTemplateForSchool($school_id, !$publicAccess);

        // Get enrollment for this student
        $enrollment = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code, students.registration_no, students.photo, students.date_of_birth, students.phone')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('student_enrollments.student_id', $student_id)
            ->where('students.status', 1)
            ->first();

        if (!$enrollment) {
            return ['html' => '', 'not_found' => true];
        }

        $studentName = trim(($enrollment->first_name ?? '') . ' ' . ($enrollment->middle_name ?? '') . ' ' . ($enrollment->last_name ?? ''));
        $studentName = preg_replace('/\s+/', ' ', $studentName);

        // Get guardian info
        $guardians = $this->StudentGuardianModel
            ->where('school_id', $school_id)
            ->where('student_id', $student_id)
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

        $sectionInfo = $enrollment->section_id ? $this->SectionModel->find($enrollment->section_id) : null;
        $shiftInfo   = $enrollment->shift_id ? $this->ShiftModel->find($enrollment->shift_id) : null;

        $dob = !empty($enrollment->date_of_birth) ? date('d-M-Y', strtotime($enrollment->date_of_birth)) : '';

        // Get transcript data
        $transcriptData = $this->getTranscriptData($school_id, $year_id, $class_id, $student_id);
        $finalResultData = $transcriptData['final_result'];

        if (!$finalResultData) {
            return ['html' => '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Transcript not generated for this student. Please generate results first.</div>', 'not_found' => true];
        }

        // Build student info table
        $studentInfoData = [
            'student_name'     => $studentName,
            'student_code'     => $enrollment->student_code ?? '',
            'roll_no'          => $enrollment->roll_no ?? '',
            'registration_no'  => $enrollment->registration_no ?? '',
            'father_name'      => $fatherName,
            'mother_name'      => $motherName,
            'guardian_name'    => $guardianName,
            'class_name'       => $className,
            'section_name'     => $sectionInfo ? $sectionInfo->title : '',
            'session_name'     => $sessionName,
            'shift_name'       => $shiftInfo ? $shiftInfo->title : '',
            'group_name'       => '',
            'date_of_birth'    => $dob,
            'student_phone'    => $enrollment->phone ?? $guardianName,
        ];
        $studentInfoTable = $this->buildStudentInfoTable($studentInfoData);

        // Build subject academic table
        $subjectAcademicTable = $this->buildSubjectAcademicTable($transcriptData);

        // Build exam performance table
        $examPerformanceTable = $this->buildExamPerformanceTable(
            $transcriptData['exam_performance_rows'],
            $transcriptData['aggregate_performance']
        );

        // Final academic summary
        $finalAcademicData = [
            'total_marks'       => $finalResultData->total_marks ?? 0,
            'total_full_marks'  => $finalResultData->total_full_marks ?? 0,
            'percentage'        => $finalResultData->percentage ?? 0,
            'gpa'               => $finalResultData->gpa ?? 0,
            'grade'             =>$finalResultData->grade_letter ?? '',
            'result_status'     => $finalResultData->result_status ?? 'PASS',
            'class_position'    => $finalResultData->class_position ?? '-',
            'section_position'  => $finalResultData->section_position ?? '-',
            'present_days'      => $finalResultData->present_days ?? 0,
            'working_days'      => $finalResultData->working_days ?? 0,
            'promotion_status'  => $finalResultData->promotion_status ?? '',
            'next_class'        => '',
        ];

        if (!empty($finalResultData->next_class_id)) {
            $nextClass = $this->ClassModel->find($finalResultData->next_class_id);
            $finalAcademicData['next_class'] = $nextClass ? $nextClass->title : '';
        }

        $finalAcademicSummary = $this->buildFinalAcademicSummary($finalAcademicData);

        // Promotion info
        $promotionDate = '';
        if (!empty($finalResultData->published_at)) {
            $promotionDate = date('d-M-Y', strtotime($finalResultData->published_at));
        }
        $nextSessionName = '';
        if (!empty($finalResultData->next_session_id)) {
            $nextSession = $this->YearModel->find($finalResultData->next_session_id);
            $nextSessionName = $nextSession ? $nextSession->title : '';
        }

        // Get next section name
        $nextSectionName = '';
        if (!empty($finalResultData->next_section_id)) {
            $nextSection = $this->SectionModel->find($finalResultData->next_section_id);
            $nextSectionName = $nextSection ? $nextSection->title : '';
        }

        $promotionData = [
            'result_status'    => $finalResultData->result_status ?? 'PASS',
            'promotion_status' => $finalResultData->promotion_status ?? '',
            'next_session'     => $nextSessionName,
            'next_class'       => $finalAcademicData['next_class'],
            'next_section'     => $nextSectionName,
            'next_roll'        => $finalResultData->next_roll ?? '',
            'promotion_date'   => $promotionDate,
        ];
        $promotionInfo = $this->buildPromotionInfo($promotionData);

        // QR Code
        $transcriptNo = 'TR-' . ($year->title ?? date('Y')) . '-' . str_pad($finalResultData->id ?? 0, 8, '0', STR_PAD_LEFT);
        $qrCodeUrl = base_url('examination/transcript/' . ($finalResultData->token ?? ''));
        $qrCode = '<div class="qr-code" >';
        //$qrCode .= '<p style="font-size:14px;font-weight:bold;margin-bottom:0px;">QR Code</p>';
        $qrCode .= '<div style="margin:0 auto;">';
        $qrCode .= '<img src="' . generate_qr_code($qrCodeUrl, 120) . '" alt="QR Code" style="width:70px;height:70px;">';
        $qrCode .= '</div>';
        $qrCode .= '<p style="margin:0px;font-size:12px;color:#666;">Scan to verify transcript authenticity</p>';
        $qrCode .= '</div>';

        // Student photo
        $studentPhotoPath = $enrollment->photo && !empty($enrollment->photo)
            ? base_url('uploads/' . $enrollment->photo)
            : base_url('uploads/default.png');
        $studentPhoto = '<img src="' . $studentPhotoPath . '" alt="Student Photo" style="width:100px;height:auto;">';

       

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

        // Get userID
        $userId = $publicAccess
            ? (int) ($template->school_owner_uid ?? 0)
            : $this->getUserId();

        // Generate grading chart
        $gradingChart = '';
        $schoolOwnerUid = $userId ?? 0;
        if ($schoolOwnerUid) {
            $gradingChart = $this->generateGradingChart($schoolOwnerUid, $school_id);
        }

        $resultData = [
            'school_name'           => $schoolName,
            'school_logo'           => $schoolLogo,
            'school_address'        => $schoolAddress,
            'school_phone'          => $schoolPhone,
            'school_email'          => $schoolEmail,
            'school_website'        => '',
            'class_name'            => $className,
            'section_name'          => $sectionInfo ? $sectionInfo->title : '',
            'session_name'          => $sessionName,
            'shift_name'            => $shiftInfo ? $shiftInfo->title : '',
            'group_name'            => '',
            'student_name'          => $studentName,
            'student_code'          => $enrollment->student_code ?? '',
            'roll_no'               => $enrollment->roll_no ?? '',
            'registration_no'       => $enrollment->registration_no ?? '',
            'father_name'           => $fatherName,
            'mother_name'           => $motherName,
            'date_of_birth'         => $dob,
            'guardian_name'         => $guardianName,
            'student_phone'         => $enrollment->phone ?? $guardianName,
            'student_info_table'    => $studentInfoTable,
            'student_photo'         => $studentPhoto,
            'principal_signature'   => $principalSignature,
            'principal_remarks'     => $finalResultData->principal_remark ?? '',
            'teacher_remarks'       => $finalResultData->teacher_remark ?? '',
            'grade_remarks'         => '',
            'grading_chart'         => $gradingChart,
            'subject_academic_table' => $subjectAcademicTable,
            'exam_performance_table' => $examPerformanceTable,
            'exam_table'            => $examPerformanceTable,
            'aggregated_table'      => $subjectAcademicTable,
            'exam_summary'          => $examPerformanceTable,
            'overall_summary'       => $finalAcademicSummary,
            'final_academic_summary' => $finalAcademicSummary,
            'promotion_info'        => $promotionInfo,
            'qr_code'               => $qrCode,
            'transcript_no'         => $transcriptNo,
            'class_teacher_signature' => $classTeacherSignature,
        ];

        
        
        if ($template && !empty($template->template_content)) {


            // orientation
            $orientation = $template->orientation ?? 'portrait';
            
            // Template background image
            $template_bg = '';
            if (!empty($template->template_bg)) {
                $template_bg = base_url('public/uploads/templates/' . $template->template_bg);
            }

            $html = $this->renderTemplate($template->template_content, $resultData);
            return [
                'html'           => $html,
                'template_style' => $template->template_style ?? '',
                'template_type'  => $template->template_type ?? '',
                'template_bg'    => $template_bg,
                'orientation'    => $orientation,
                'not_found'      => false,
            ];
        }

        // Build default HTML
        $html = '<div class="transcript-card" style="font-family:Arial,sans-serif;max-width:1200px;margin:0 auto;padding:0;">';

        // School Header
        $html .= '<div style="text-align:center;border-bottom:2px solid #1a73e8;padding-bottom:15px;">';
        $html .= '<h2 style="color:#1a73e8;">' . esc($schoolName) . '</h2>';
        $html .= '<p style="font-size:14px;">' . esc($schoolAddress) . '</p>';
        $html .= '<p style="font-size:14px;">Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
        $html .= '<h3 style="color:#333;">Academic Transcript / Report Card</h3>';
        $html .= '</div>';

        // Student Info
        $html .= '<h4 style="color:#333;">Student Information</h4>';
        $html .= $studentInfoTable;

        // Subject-wise Academic Performance
        $html .= '<h4 style="color:#333;">Subject-wise Academic Performance</h4>';
        $html .= $subjectAcademicTable;

        // Exam-wise Performance
        $html .= '<h4 style="color:#333;">Exam-wise Performance</h4>';
        $html .= $examPerformanceTable;

        // Final Academic Summary
        $html .= $finalAcademicSummary;

        // Promotion Info
        $html .= $promotionInfo;

        // QR Code
        $html .= $qrCode;

        $html .= '</div>';

        return [
            'html'           => $html,
            'template_style' => '',
            'template_type'  => '',
            'not_found'      => false,
        ];
    }

    /**
     * Build the public transcript page data with the same renderer used by details().
     */
    public function getPublicTranscriptData(string $token): ?array
    {
        $finalResult = $this->FinalResultModel
            ->where('token', $token)
            ->first();

        if (!$finalResult) {
            return null;
        }

        $school_id  = (int) ($finalResult->school_id ?? 0);
        $year_id    = (int) ($finalResult->session_id ?? 0);
        $class_id   = (int) ($finalResult->class_id ?? 0);
        $student_id = (int) ($finalResult->student_uid ?? 0);

        $school = $this->SchoolModel->find($school_id);
        if (!$school) {
            return null;
        }

        $class = $this->ClassModel->find($class_id);
        $year  = $this->YearModel->find($year_id);

        $schoolName    = $school->name ?? '';
        $schoolAddress = $school->address ?? '';
        $schoolPhone   = $school->phone ?? '';
        $schoolEmail   = $school->email ?? '';
        $className     = $class->title ?? '';
        $sessionName   = $year->title ?? '';

        $schoolLogoPath = !empty($school->logo)
            ? base_url('uploads/' . $school->logo)
            : base_url('uploads/default.png');
        $schoolLogo = '<img src="' . $schoolLogoPath . '" alt="School Logo" style="width:100px;height:100px;">';

        $transcriptResult = $this->buildTranscriptHtml(
            $school_id,
            $year_id,
            $class_id,
            $student_id,
            $finalResult,
            $schoolName,
            $schoolAddress,
            $schoolPhone,
            $schoolEmail,
            $className,
            $sessionName,
            $schoolLogo,
            true
        );

        return [
            'school_name'      => $schoolName,
            'school_id'        => $school_id,
            'year_id'          => $year_id,
            'class_id'         => $class_id,
            'student_id'       => $student_id,
            'transcript_token' => $token,
            'final_result'     => $finalResult,
            'rendered_content' => $transcriptResult['html'] ?? '',
            'template_style'   => $transcriptResult['template_style'] ?? '',
            'template_type'    => $transcriptResult['template_type'] ?? '',
            'template_bg'      => $transcriptResult['template_bg'] ?? '',
            'orientation'      => $transcriptResult['orientation'] ?? 'portrait',
            'not_found'        => $transcriptResult['not_found'] ?? false,
        ];
    }

    // ======================================================================
    // INDEX - Filter by school, year, class (shows all students)
    // ======================================================================
    public function index()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Transcript',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $school_id = (int) $this->request->getGet('school_id');
        $year_id   = (int) $this->request->getGet('year_id');
        $class_id  = (int) $this->request->getGet('class_id');

        $data = compact('school_id', 'year_id', 'class_id');
        $data['school_list'] = $this->getSchoolDropdown();
        $data['year_list']   = [];
        $data['class_list']  = [];
        $data['students']    = [];
        $data['not_found']   = false;

        if ($school_id) {
            $data['year_list']  = $this->getActiveOptions($school_id, 'YearModel');
            $data['class_list'] = $this->getActiveOptions($school_id, 'ClassModel');
        }

        // If filters selected, load students
        if ($school_id && $year_id && $class_id) {
            $userSchools = $this->getUserSchools();
            $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

            if (!in_array($school_id, $schoolIds)) {
                return redirect()->to('examination/reports/transcript')->with('error', 'Access denied.');
            }

            $enrollments = $this->EnrollmentModel
                ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code, students.registration_no, students.photo, students.date_of_birth, students.phone')
                ->join('students', 'students.id = student_enrollments.student_id', 'left')
                ->where('student_enrollments.school_id', $school_id)
                ->where('student_enrollments.session_id', $year_id)
                ->where('student_enrollments.class_id', $class_id)
                ->where('students.status', 1)
                ->orderBy('student_enrollments.roll_no', 'ASC')
                ->findAll();

            if (!empty($enrollments)) {
                $studentList = [];
                foreach ($enrollments as $enr) {
                    $s = new \stdClass();
                    $s->id              = $enr->student_id;
                    $s->first_name      = $enr->first_name;
                    $s->middle_name     = $enr->middle_name;
                    $s->last_name       = $enr->last_name;
                    $s->student_code    = $enr->student_code;
                    $s->registration_no = $enr->registration_no;
                    $s->photo           = $enr->photo;
                    $s->roll_no         = $enr->roll_no;
                    $s->enrollment_id   = $enr->id;
                    $s->section_id      = $enr->section_id;
                    $s->shift_id        = $enr->shift_id;
                    $s->date_of_birth   = $enr->date_of_birth;
                    $s->phone           = $enr->phone;

                    // Check if final result exists
                    $finalResult = $this->FinalResultModel
                        ->where('session_id', $year_id)
                        ->where('class_id', $class_id)
                        ->where('student_uid', $s->id)
                        ->first();
                    $s->has_transcript = $finalResult !== null;
                    $s->final_result   = $finalResult;
                    $s->token          = $finalResult ? ($finalResult->token ?? '') : '';

                    $studentList[] = $s;
                }
                $data['students'] = $studentList;
            }
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\transcript', $data)
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
            return redirect()->to('examination/reports/transcript');
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
            return redirect()->to('examination/reports/transcript');
        }
        
        $school_id = (int) $this->request->getPost('school_id');
        $classes = [];
        
        if ($school_id) {
            $classes = $this->getActiveOptions($school_id, 'ClassModel');
        }
        
        return $this->response->setJSON(['success' => true, 'data' => $classes]);
    }
    
    /**
     * AJAX: Get students by filters
     */
    public function ajaxGetStudents()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/reports/transcript');
        }
        
        $school_id = (int) $this->request->getPost('school_id');
        $year_id = (int) $this->request->getPost('year_id');
        $class_id = (int) $this->request->getPost('class_id');
        
        if (!$school_id || !$year_id || !$class_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'All fields are required']);
        }
        
        $user_id = $this->getUserId();
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
        
        if (!in_array($school_id, $schoolIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied']);
        }
        
        // Get all enrollments in this class
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code, students.registration_no, students.photo, students.date_of_birth, students.phone')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1)
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->findAll();

        $studentList = [];
        if (!empty($enrollments)) {
            foreach ($enrollments as $enr) {
                $s = new \stdClass();
                $s->id              = $enr->student_id;
                $s->first_name      = $enr->first_name;
                $s->middle_name     = $enr->middle_name;
                $s->last_name       = $enr->last_name;
                $s->student_code    = $enr->student_code;
                $s->registration_no = $enr->registration_no;
                $s->photo           = $enr->photo;
                $s->roll_no         = $enr->roll_no;
                $s->enrollment_id   = $enr->id;
                $s->section_id      = $enr->section_id;
                $s->shift_id        = $enr->shift_id;
                $s->date_of_birth   = $enr->date_of_birth;
                $s->phone           = $enr->phone;

                // Check if final result exists
                $finalResult = $this->FinalResultModel
                    ->where('session_id', $year_id)
                    ->where('class_id', $class_id)
                    ->where('student_uid', $s->id)
                    ->first();
                $s->has_transcript = $finalResult !== null;
                $s->final_result   = $finalResult;
                $s->token          = $finalResult ? ($finalResult->token ?? '') : '';

                $studentList[] = $s;
            }
        }
        
        // Generate HTML for students table
        $html = '';
        if (!empty($studentList)) {
            $html .= '<div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Students in Class (' . count($studentList) . ' found)</h5>
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
            foreach ($studentList as $stu) {
                $studentName = trim(($stu->first_name ?? '') . ' ' . ($stu->middle_name ?? '') . ' ' . ($stu->last_name ?? ''));
                $studentName = preg_replace('/\s+/', ' ', $studentName);
                
                $html .= '<tr>
                    <td>' . $i++ . '</td>
                    <td>' . esc($stu->roll_no ?? '') . '</td>
                    <td>' . esc($studentName) . '</td>
                    <td>' . esc($stu->student_code ?? '') . '</td>
                    <td>' . esc($stu->registration_no ?? '') . '</td>
                    <td>';
                
                if ($stu->has_transcript) {
                    $html .= '<span class="badge bg-success">Transcript Available</span>';
                } else {
                    $html .= '<span class="badge bg-warning">Not Generated</span>';
                }
                
                $html .= '</td><td>';
                
                if ($stu->has_transcript && !empty($stu->token)) {
                    $html .= '<a href="' . base_url('examination/reports/transcript/details/' . $stu->token) . '" target="_blank" class="btn btn-sm btn-info">
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
     * Download transcript as PDF
     * Route: transcript/download-pdf/(:segment)
     */
    public function downloadPdf(string $token)
    {
        // Find the final result by token
        $finalResult = $this->FinalResultModel
            ->where('token', $token)
            ->first();

        if (!$finalResult) {
            return redirect()->to('examination/reports/transcript')->with('error', 'Invalid transcript token.');
        }

        $school_id = (int) $finalResult->school_id ?? 0;
        $year_id   = (int) $finalResult->session_id;
        $class_id  = (int) $finalResult->class_id;
        $student_id = (int) $finalResult->student_uid;

        // Access check
        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return redirect()->to('examination/reports/transcript')->with('error', 'Access denied.');
        }

        // Get school/class/year info
        $school = $this->SchoolModel->find($school_id);
        $class  = $this->ClassModel->find($class_id);
        $year   = $this->YearModel->find($year_id);

        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';
        $className     = $class ? $class->title : '';
        $sessionName   = $year ? $year->title : '';

        // School logo
        $schoolLogoPath = $school && !empty($school->logo) ? base_url('uploads/' . $school->logo) : base_url('uploads/default.png');
        $schoolLogo     = '<img src="' . $schoolLogoPath . '" alt="School Logo" style="width:100px;height:100px;">';

        // Build transcript HTML
        $transcriptResult = $this->buildTranscriptHtml(
            $school_id, $year_id, $class_id, $student_id,
            $finalResult,
            $schoolName, $schoolAddress, $schoolPhone, $schoolEmail,
            $className, $sessionName, $schoolLogo
        );

        if ($transcriptResult['not_found'] ?? false) {
            return redirect()->to('examination/reports/transcript')->with('error', 'Transcript not generated.');
        }

        $html = $transcriptResult['html'] ?? '';
        $templateStyle = $transcriptResult['template_style'] ?? '';
        $template_bg = $transcriptResult['template_bg'] ?? '';
        $orientation = $transcriptResult['orientation'] ?? 'portrait';

        // Build full HTML document for PDF (following IndividualResultController pattern)
        $pdfHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Transcript - ' . esc($schoolName) . '</title>';
        $pdfHtml .= '<style>';
        $pdfHtml .= 'body { font-family: Arial, sans-serif; margin: 0; padding: 0; position: relative; }';
        $pdfHtml .= 'html { margin: 0; padding: 0; }';
        $pdfHtml .= '* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }';

        if ($orientation === 'portrait') {
            $pdfHtml .= '.result-body {  padding: 10mm 15mm;  }';
        } else {
            $pdfHtml .= '.result-body {  padding: 10mm 5mm;  }';
        }

        if ($template_bg) {
            $pdfHtml .= '.result-background {';
            $pdfHtml .= 'position: fixed;';
            $pdfHtml .= 'top: 0;';
            $pdfHtml .= 'left: 0;';
            $pdfHtml .= 'width: 100%;';
            $pdfHtml .= 'height: 100%;';
            $pdfHtml .= 'z-index: -1;';
            $pdfHtml .= 'background-image: url(\'' . $template_bg . '\');';
            $pdfHtml .= 'background-size: cover;';
            $pdfHtml .= 'background-position: center;';
            $pdfHtml .= 'background-repeat: no-repeat;';
            $pdfHtml .= '}';
        }

        if ($templateStyle) {
            $pdfHtml .= $templateStyle;
        }

        $pdfHtml .= 'p { margin: 0; padding: 0; }';
        $pdfHtml .= '.subject-academic-table th, .subject-academic-table td { text-align: center; vertical-align: middle; }';
        $pdfHtml .= '.subject-academic-table td.subject-name { text-align: left; }';
        $pdfHtml .= '</style></head><body>';

        if ($template_bg) {
            $pdfHtml .= '<div class="result-background"></div>';
        }

        $pdfHtml .= '<div class="result-body">';
        $pdfHtml .= $html;
        $pdfHtml .= '</div>';
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

        if ($orientation === 'portrait') {
            $dompdf->setPaper('A4', 'portrait');
        } else {
            $dompdf->setPaper('A4', 'landscape');
        }

        $dompdf->render();

        $filename = 'Transcript_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $schoolName) . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $className) . '_' . date('Ymd-His') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * DETAILS - View a specific transcript by final result token
     * Route: transcript/details/(:segment)
     */
    public function details(string $token)
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        // Find the final result by token
        $finalResult = $this->FinalResultModel
            ->where('token', $token)
            ->first();

        if (!$finalResult) {
            return redirect()->to('examination/reports/transcript')->with('error', 'Invalid transcript token.');
        }

        $school_id = (int) $finalResult->school_id ?? 0;
        $year_id   = (int) $finalResult->session_id;
        $class_id  = (int) $finalResult->class_id;
        $student_id = (int) $finalResult->student_uid;

        // Access check
        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return redirect()->to('examination/reports/transcript')->with('error', 'Access denied.');
        }

        // Get school/class/year info
        $school = $this->SchoolModel->find($school_id);
        $class  = $this->ClassModel->find($class_id);
        $year   = $this->YearModel->find($year_id);

        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';
        $className     = $class ? $class->title : '';
        $sessionName   = $year ? $year->title : '';

        // School logo
        $schoolLogoPath = $school && !empty($school->logo) ? base_url('uploads/' . $school->logo) : base_url('uploads/default.png');
        $schoolLogo     = '<img src="' . $schoolLogoPath . '" alt="School Logo" style="width:100px;height:100px;">';

        // Build transcript HTML
        $transcriptResult = $this->buildTranscriptHtml(
            $school_id, $year_id, $class_id, $student_id,
            $finalResult,
            $schoolName, $schoolAddress, $schoolPhone, $schoolEmail,
            $className, $sessionName, $schoolLogo
        );

        $template = $this->getTemplateForSchool($school_id);

        

        $data = [
            'school_id'        => $school_id,
            'year_id'          => $year_id,
            'class_id'         => $class_id,
            'student_id'       => $student_id,
            'transcript_token' => $token,
            'final_result'     => $finalResult,
            'rendered_content' => $transcriptResult['html'] ?? '',
            'template_style'   => $transcriptResult['template_style'] ?? '',
            'template_type'    => $transcriptResult['template_type'] ?? '',
            'not_found'        => $transcriptResult['not_found'] ?? false,
        ];

        // orientation
        $data['orientation'] = $template->orientation ?? 'portrait';
        
        // Template background image
        $data['template_bg'] = '';
        if (!empty($template->template_bg)) {
            $data['template_bg'] = base_url('public/uploads/templates/' . $template->template_bg);
        }

        $header_data = [
            'page_title' => 'Transcript Details',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('App\Modules\examination\Views\reports\transcript_details', $data)
            . view('footer', $footer_data);
    }
}

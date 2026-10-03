<?php

use App\Models\StudentSubjectModel;
use App\Modules\examination\Models\GradeRuleModel;

if (!function_exists('get_student_subject_categories')) {
    /**
     * Get student subjects categorized as compulsory, combined, and optional
     * 
     * @param int $school_id
     * @param int $enrollment_id
     * @return array Array with 'compulsory_subjects', 'combined_subjects', and 'optional_subjects'
     */
    function get_student_subject_categories(int $school_id, int $enrollment_id): array
    {
        $studentSubjectModel = new StudentSubjectModel();
        // Get Compulsory subjects (combine_group IS NULL)
        $compulsory_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)
            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        // Get Combined subjects (combine_group IS NOT NULL)
        $combined_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)
            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NOT NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        // Get Optional subjects (optional_subject_id IS NOT NULL AND != 0)
        $optional_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)
            ->where('student_subjects.optional_subject_id IS NOT NULL AND student_subjects.optional_subject_id !=', 0)
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        return [
            'compulsory_subjects' => $compulsory_subjects,
            'combined_subjects' => $combined_subjects,
            'optional_subjects' => $optional_subjects,
        ];
    }
}


// Get grade data
if(!function_exists('get_grade_data')) {
    function get_grade_data($grade_system_id, $aggregatePercentage) {
        $gradePoint = 0;
        $letterGrade = '';
        $isFail = 1;

        if ($grade_system_id) {

            $grade_rule_model = new GradeRuleModel();
            $grading_records = $grade_rule_model
                ->where(
                    'grade_system_id',
                    (int) $grade_system_id
                )
                ->where('status', 1)
                ->orderBy('mark_from', 'DESC')
                ->findAll();

            foreach ($grading_records as $g) {

                if (
                    $aggregatePercentage >= (float) $g->mark_from &&
                    $aggregatePercentage <= (float) $g->mark_to
                ) {
                    $letterGrade = $g->title;
                    $gradePoint  = (float) ($g->grade_point ?? 0);

                    break;
                }
            }
        }

        $isFail = ($gradePoint == 0) ? 1 : 0;

        return [
            'grade_point' => $gradePoint,
            'letter_grade' => $letterGrade,
            'is_fail' => $isFail,
        ];
    }
}


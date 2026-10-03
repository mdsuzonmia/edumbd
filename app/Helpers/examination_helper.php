<?php

use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\StudentSubjectModel;
use App\Modules\examination\Models\GradeRuleModel;
use App\Modules\examination\Models\GradeSystemModel;
use App\Modules\examination\Models\MarkModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\FinalResultModel;
use App\Modules\examination\Models\FinalResultSubjectModel;
use App\Modules\examination\Models\ResultPublishModel;
use App\Modules\examination\Models\MarkLockModel;

// ===================================================================
// AUTHENTICATION & USER HELPERS
// ===================================================================

if (!function_exists('get_exam_user_id')) {
    /**
     * Get current logged-in user ID
     */
    function get_exam_user_id(): int
    {
        return (int) session('user_id');
    }
}

if (!function_exists('get_exam_user_schools')) {
    /**
     * Get schools accessible by current user
     */
    function get_exam_user_schools(): array
    {
        $user_id = get_exam_user_id();
        if (!$user_id) {
            return [];
        }

        $schoolModel = new SchoolModel();
        return $schoolModel
            ->select('schools.id, schools.name, schools.params')
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('school_user_relation.user_id', $user_id)
            ->where('schools.status', 1)
            ->orderBy('schools.name', 'ASC')
            ->findAll();
    }
}

if (!function_exists('get_exam_school_dropdown')) {
    /**
     * Get school list as dropdown array [id => name]
     */
    function get_exam_school_dropdown(): array
    {
        $schools = get_exam_user_schools();
        $list = [];
        foreach ($schools as $s) {
            $list[$s->id] = $s->name;
        }
        return $list;
    }
}

if (!function_exists('is_exam_school_setting_enabled')) {
    /**
     * Check if a school setting is enabled
     */
    function is_exam_school_setting_enabled(int $school_id, string $key): bool
    {
        $schoolModel = new SchoolModel();
        $school = $schoolModel->find($school_id);
        if (!$school || empty($school->params)) {
            return false;
        }
        $params = json_decode($school->params, true);
        return !empty($params[$key]);
    }
}

// ===================================================================
// ACADEMIC DATA HELPERS
// ===================================================================

if (!function_exists('get_exam_active_options')) {
    /**
     * Get active options from a model for dropdown
     * 
     * @param int $school_id
     * @param string $model_name Model property name (e.g., 'YearModel', 'ClassModel')
     * @param object $controller Controller instance with model properties
     * @return array
     */
    function get_exam_active_options(int $school_id, string $model_name, $controller): array
    {
        $list = [];
        $model = $controller->{$model_name};
        
        $orderColumn = ($model_name === 'MarkDistributionModel') ? 'name' : 'title';
        $valueColumn = ($model_name === 'MarkDistributionModel') ? 'name' : 'title';
        
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
}

if (!function_exists('get_exam_academic_data_by_school')) {
    /**
     * Get academic data (years, classes, sections, exams) for a school
     * 
     * @param int $school_id
     * @param object $controller Controller instance
     * @param bool $include_exams Whether to include exams list
     * @return array
     */
    function get_exam_academic_data_by_school(int $school_id, $controller, bool $include_exams = true): array
    {
        $yearList       = get_exam_active_options($school_id, 'YearModel', $controller);
        $classList      = get_exam_active_options($school_id, 'ClassModel', $controller);
        $sectionList    = get_exam_active_options($school_id, 'SectionModel', $controller);
        
        $data = [
            'status'       => true,
            'year_list'    => $yearList,
            'class_list'   => $classList,
            'section_list' => $sectionList,
            'academic_section_enabled' => is_exam_school_setting_enabled($school_id, 'academic_section_enabled'),
        ];
        
        if ($include_exams) {
            $data['exam_list'] = get_exam_active_options($school_id, 'ExamModel', $controller);
        }
        
        return $data;
    }
}

// ===================================================================
// ENROLLMENT & STUDENT HELPERS
// ===================================================================

if (!function_exists('get_exam_enrollments_by_filters')) {
    /**
     * Get student enrollments by school, class, section, and year
     * 
     * @param object $enrollmentModel
     * @param int $school_id
     * @param int $class_id
     * @param int|null $section_id
     * @param int|null $year_id
     * @return array
     */
    function get_exam_enrollments_by_filters($enrollmentModel, int $school_id, int $class_id, ?int $section_id, ?int $year_id): array
    {
        $enrollmentModel
            ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);

        if ($section_id) {
            $enrollmentModel->where('student_enrollments.section_id', $section_id);
        }
        if ($year_id) {
            $enrollmentModel->where('student_enrollments.session_id', $year_id);
        }

        return $enrollmentModel->findAll();
    }
}

if (!function_exists('get_exam_student_subjects')) {
    /**
     * Get main and optional subjects for a student enrollment
     * 
     * @param object $studentSubjectModel
     * @param int $school_id
     * @param int $enrollment_id
     * @return array Array with 'main' and 'optional' subject arrays
     */
    function get_exam_student_subjects($studentSubjectModel, int $school_id, int $enrollment_id): array
    {
        $main_subjects = $studentSubjectModel
            ->where('school_id', $school_id)
            ->where('enrollment_id', $enrollment_id)
            ->groupStart()
                ->where('optional_subject_id', NULL)
                ->orWhere('optional_subject_id', 0)
            ->groupEnd()
            ->findAll();

        $optional_subjects = $studentSubjectModel
            ->where('school_id', $school_id)
            ->where('enrollment_id', $enrollment_id)
            ->where('optional_subject_id IS NOT NULL AND optional_subject_id !=', 0)
            ->findAll();
        
        return [
            'main' => $main_subjects,
            'optional' => $optional_subjects,
            'all_ids' => array_merge(array_column($main_subjects, 'subject_id'), array_column($optional_subjects, 'optional_subject_id'))
        ];
    }
}

if (!function_exists('filter_exam_subjects_for_student')) {
    /**
     * Filter subjects array for a specific student
     * 
     * @param array $allSubjects
     * @param array $studentSubjectIds
     * @return array
     */
    function filter_exam_subjects_for_student(array $allSubjects, array $studentSubjectIds): array
    {
        return array_filter($allSubjects, function($subject) use ($studentSubjectIds) {
            return in_array($subject->id, $studentSubjectIds);
        });
    }
}

// ===================================================================
// GRADE CALCULATION HELPERS
// ===================================================================

if (!function_exists('calculate_exam_grade_for_percentage')) {
    /**
     * Calculate grade and grade point for a given percentage
     * 
     * @param float $percentage
     * @param int|null $grade_system_id
     * @param object $gradeRuleModel
     * @return array ['grade' => '', 'grade_point' => 0, 'letter_grade' => '']
     */
    function calculate_exam_grade_for_percentage(float $percentage, ?int $grade_system_id, $gradeRuleModel): array
    {
        $grade = '';
        $gradePoint = 0;
        $letterGrade = '';

        if ($grade_system_id) {
            $gradingRecords = $gradeRuleModel
                ->where('grade_system_id', (int) $grade_system_id)
                ->where('status', 1)
                ->orderBy('mark_from', 'DESC')
                ->findAll();

            foreach ($gradingRecords as $g) {
                if ($percentage >= (float) $g->mark_from && $percentage <= (float) $g->mark_to) {
                    $grade       = $g->title;
                    $gradePoint  = (float) ($g->grade_point ?? 0);
                    $letterGrade = $g->title;
                    break;
                }
            }
        }

        return [
            'grade' => $grade,
            'grade_point' => $gradePoint,
            'letter_grade' => $letterGrade,
        ];
    }
}

if (!function_exists('calculate_exam_gpa')) {
    /**
     * Calculate GPA from grade points array
     * 
     * @param array $gradePoints Array of grade points
     * @param int $totalSubjects Total number of subjects
     * @return float
     */
    function calculate_exam_gpa(array $gradePoints, int $totalSubjects): float
    {
        if ($totalSubjects === 0) {
            return 0;
        }
        
        $totalGradePoints = array_sum($gradePoints);
        return round($totalGradePoints / $totalSubjects, 2);
    }
}

if (!function_exists('calculate_exam_percentage')) {
    /**
     * Calculate percentage from obtained and full marks
     * 
     * @param float $obtained
     * @param float $full
     * @return float
     */
    function calculate_exam_percentage(float $obtained, float $full): float
    {
        return $full > 0 ? round(($obtained / $full) * 100, 2) : 0;
    }
}

// ===================================================================
// MARK HELPERS
// ===================================================================

if (!function_exists('get_exam_student_raw_marks')) {
    /**
     * Get aggregated raw marks for a student across all mark distributions
     * 
     * @param object $markModel
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $subject_id
     * @param int $student_id
     * @return array ['full_mark' => 0, 'obtained_mark' => 0, 'is_absent' => 0, 'remarks' => '']
     */
    function get_exam_student_raw_marks($markModel, int $school_id, int $exam_id, int $class_id, int $subject_id, int $student_id): array
    {
        $markRecords = $markModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->where('subject_id', $subject_id)
            ->where('student_id', $student_id)
            ->findAll();

        $fullMark     = 0;
        $obtainedMark = 0;
        $isAbsent     = 0;
        $remarks      = '';

        foreach ($markRecords as $mark) {
            $fullMark     += (float) ($mark->full_mark ?? 0);
            $obtainedMark += (float) ($mark->obtained_mark ?? 0);
            if ($mark->is_absent) {
                $isAbsent = 1;
            }
            if (!empty($mark->remarks) && empty($remarks)) {
                $remarks = $mark->remarks;
            }
        }

        return [
            'full_mark'     => $fullMark,
            'obtained_mark' => $obtainedMark,
            'is_absent'     => $isAbsent,
            'remarks'       => $remarks,
        ];
    }
}

if (!function_exists('get_exam_highest_mark_for_subject')) {
    /**
     * Get highest mark for a subject in an exam/class
     * 
     * @param object $subjectResultModel
     * @param object $markModel
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $subject_id
     * @param float $currentStudentMark Current student's mark for comparison
     * @return float
     */
    function get_exam_highest_mark_for_subject($subjectResultModel, $markModel, int $school_id, int $exam_id, int $class_id, int $subject_id, float $currentStudentMark): float
    {
        $highestMark = $subjectResultModel
            ->selectMax('obtained_mark')
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->where('subject_id', $subject_id)
            ->get()
            ->getRow()
            ->obtained_mark ?? 0;

        // If no existing results yet, calculate from marks table
        if (!$highestMark) {
            $highestRow = $markModel
                ->select('student_id, SUM(obtained_mark) as total_obtained')
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('subject_id', $subject_id)
                ->groupBy('student_id')
                ->orderBy('total_obtained', 'DESC')
                ->limit(1)
                ->get()
                ->getRow();
            $highestMark = $highestRow ? (float) $highestRow->total_obtained : $currentStudentMark;
        }

        return max($highestMark, $currentStudentMark);
    }
}

// ===================================================================
// SUBJECT RESULT HELPERS
// ===================================================================

if (!function_exists('build_exam_subject_result_data')) {
    /**
     * Build subject result data array for insert/update
     * 
     * @param array $config Configuration array with all required fields
     * @return array
     */
    function build_exam_subject_result_data(array $config): array
    {
        return [
            'school_id'           => $config['school_id'],
            'school_owner_uid'    => $config['user_id'],
            'exam_id'             => $config['exam_id'],
            'session_id'          => $config['year_id'] ?: $config['enrollment']->session_id,
            'class_id'            => $config['class_id'],
            'section_id'          => $config['section_id'] ?: $config['enrollment']->section_id,
            'student_id'          => $config['enrollment']->student_id,
            'enrollment_id'       => $config['enrollment']->id,
            'subject_id'          => $config['subject_id'],
            'full_mark'           => $config['full_mark'],
            'obtained_mark'       => $config['obtained_mark'],
            'exclude_mark'        => $config['exclude_mark'] ?? 0,
            'exclude_grade_point' => $config['exclude_grade_point'] ?? 0,
            'include_mark'        => $config['include_mark'] ?? $config['obtained_mark'],
            'include_grade_point' => $config['include_grade_point'] ?? 0,
            'percentage'          => $config['percentage'],
            'grade_point'         => $config['grade_point'],
            'grade'               => $config['grade'],
            'letter_grade'        => $config['letter_grade'],
            'highest_mark'        => $config['highest_mark'],
            'is_fail'             => $config['is_fail'],
            'remarks'             => $config['remarks'] ?? '',
        ];
    }
}

if (!function_exists('upsert_exam_subject_result')) {
    /**
     * Insert or update subject result
     * 
     * @param object $subjectResultModel
     * @param array $data Subject result data
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $subject_id
     * @param int $student_id
     * @return int Result ID
     */
    function upsert_exam_subject_result($subjectResultModel, array $data, int $school_id, int $exam_id, int $class_id, int $subject_id, int $student_id): int
    {
        $existing = $subjectResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->where('subject_id', $subject_id)
            ->where('student_id', $student_id)
            ->first();

        if ($existing) {
            $subjectResultModel->update($existing->id, $data);
            return $existing->id;
        } else {
            return $subjectResultModel->insert($data);
        }
    }
}

// ===================================================================
// EXAM RESULT HELPERS
// ===================================================================

if (!function_exists('build_exam_result_data')) {
    /**
     * Build exam result data array for insert/update
     * 
     * @param array $config Configuration array
     * @return array
     */
    function build_exam_result_data(array $config): array
    {
        return [
            'school_id'           => $config['school_id'],
            'school_owner_uid'    => $config['user_id'],
            'exam_id'             => $config['exam_id'],
            'session_id'          => $config['year_id'],
            'class_id'            => $config['class_id'],
            'section_id'          => $config['section_data'] ?: $config['section_id'],
            'student_id'          => $config['student_id'],
            'enrollment_id'       => $config['enrollment_id'],
            'total_subjects'      => $config['total_subjects'],
            'passed_subjects'     => $config['passed_subjects'],
            'failed_subjects'     => $config['failed_subjects'],
            'total_marks'         => $config['total_marks'],
            'obtained_marks'      => $config['obtained_marks'],
            'percentage'          => $config['percentage'],
            'gpa'                 => $config['gpa'],
            'total_grade_point'   => $config['total_grade_point'],
            'grade'               => $config['grade'],
            'letter_grade'        => $config['letter_grade'],
            'result_status'       => $config['result_status'],
            'attendance_percentage' => null,
            'principal_remarks'   => null,
            'teacher_remarks'     => null,
        ];
    }
}

if (!function_exists('upsert_exam_result')) {
    /**
     * Insert or update exam result
     * 
     * @param object $examResultModel
     * @param array $data Exam result data
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $student_id
     * @param callable $tokenGenerator Function to generate token
     * @return void
     */
    function upsert_exam_result($examResultModel, array $data, int $school_id, int $exam_id, int $class_id, int $student_id, callable $tokenGenerator): void
    {
        $existing = $examResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->where('student_id', $student_id)
            ->first();

        if ($existing) {
            $examResultModel->update($existing->id, $data);
        } else {
            $data['token'] = $tokenGenerator();
            $examResultModel->insert($data);
        }
    }
}

// ===================================================================
// POSITION/RANK HELPERS
// ===================================================================

if (!function_exists('assign_exam_ranks_with_ties')) {
    /**
     * Assign ranks to results array with tie handling
     * 
     * @param array $results Array of result objects/arrays with 'gpa' and 'obtained_marks' or 'total_marks'
     * @param string $marks_key Key for marks field ('obtained_marks' or 'total_marks')
     * @param string $rank_key Key to store rank in result
     * @return array Results with rank assigned
     */
    function assign_exam_ranks_with_ties(array $results, string $marks_key = 'obtained_marks', string $rank_key = 'rank'): array
    {
        $rank = 0;
        $prevGpa = null;
        $prevMarks = null;
        $skipRank = 0;

        foreach ($results as $idx => $result) {
            $marks = is_object($result) ? $result->$marks_key : $result[$marks_key];
            $gpa = is_object($result) ? $result->gpa : $result['gpa'];

            if ($prevGpa !== null && $prevGpa == $gpa && $prevMarks == $marks) {
                $skipRank++;
            } else {
                $rank = ($idx + 1);
                $skipRank = 0;
            }

            if (is_object($result)) {
                $result->$rank_key = $rank;
            } else {
                $result[$rank_key] = $rank;
            }

            $prevGpa = $gpa;
            $prevMarks = $marks;
        }

        return $results;
    }
}

if (!function_exists('update_exam_result_ranks_batch')) {
    /**
     * Update ranks for exam results in batch
     * 
     * @param object $examResultModel
     * @param array $results Array of result objects with 'id' and rank fields
     * @param array $rankFields Fields to update ['class_rank', 'section_rank', 'exam_rank']
     * @return void
     */
    function update_exam_result_ranks_batch($examResultModel, array $results, array $rankFields): void
    {
        foreach ($results as $result) {
            $updateData = [];
            foreach ($rankFields as $field) {
                if (isset($result->$field)) {
                    $updateData[$field] = $result->$field;
                }
            }
            
            if (!empty($updateData)) {
                $examResultModel->where('id', $result->id)->set($updateData)->update();
            }
        }
    }
}

// ===================================================================
// AGGREGATE RESULT HELPERS
// ===================================================================

if (!function_exists('calculate_exam_weighted_aggregate')) {
    /**
     * Calculate weighted aggregate for a subject across multiple exams
     * 
     * @param array $examResults Array of exam result data for a subject
     * @param array $exams Array of exam objects with weight_percentage
     * @return array ['weighted_obtained' => 0, 'weighted_full' => 0]
     */
    function calculate_exam_weighted_aggregate(array $examResults, array $exams): array
    {
        $weightedObtained = 0;
        $weightedFull = 0;

        foreach ($exams as $exam) {
            $weight = (float) $exam->weight_percentage / 100;
            $examId = $exam->id;

            if (isset($examResults[$examId])) {
                $examObtained = (float) $examResults[$examId]['obtained'];
                $examFull = (float) $examResults[$examId]['full'];
                
                $weightedObtained += $examObtained * $weight;
                $weightedFull += $examFull * $weight;
            }
        }

        return [
            'weighted_obtained' => $weightedObtained,
            'weighted_full'     => $weightedFull,
        ];
    }
}

if (!function_exists('build_exam_final_result_data')) {
    /**
     * Build final result data array for insert/update
     * 
     * @param array $config Configuration array
     * @return array
     */
    function build_exam_final_result_data(array $config): array
    {
        return [
            'school_id'        => $config['school_id'],
            'school_owner_uid' => $config['user_id'],
            'student_uid'      => $config['student_id'],
            'enrollment_id'    => $config['enrollment_id'],
            'session_id'       => $config['year_id'],
            'class_id'         => $config['class_id'],
            'section_id'       => $config['section_id'],
            'total_marks'      => $config['total_marks'],
            'total_full_marks' => $config['total_full_marks'],
            'percentage'       => $config['percentage'],
            'gpa'              => $config['gpa'],
            'grade_letter'     => $config['letter_grade'],
            'grade_name'       => $config['grade'],
            'class_position'   => $config['class_position'] ?? 0,
            'section_position' => $config['section_position'] ?? 0,
            'result_status'    => $config['result_status'],
            'promotion_status' => ($config['result_status'] == 'PASS') ? 'promoted' : 'not_promoted',
            'is_generated'     => 1,
            'generated_at'     => date('Y-m-d H:i:s'),
            'created_by'       => $config['user_id'],
        ];
    }
}

if (!function_exists('upsert_exam_final_result')) {
    /**
     * Insert or update final result
     * 
     * @param object $finalResultModel
     * @param array $data Final result data
     * @param int $student_id
     * @param int $year_id
     * @param int $class_id
     * @param callable $tokenGenerator Function to generate token
     * @return void
     */
    function upsert_exam_final_result($finalResultModel, array $data, int $student_id, int $year_id, int $class_id, callable $tokenGenerator): void
    {
        $existing = $finalResultModel
            ->where('student_uid', $student_id)
            ->where('session_id', $year_id)
            ->where('class_id', $class_id)
            ->first();

        if ($existing) {
            $data['updated_by'] = $data['created_by'];
            $finalResultModel->update($existing->id, $data);
        } else {
            $data['token'] = $tokenGenerator();
            $finalResultModel->insert($data);
        }
    }
}

// ===================================================================
// PUBLISH HELPERS
// ===================================================================

if (!function_exists('upsert_exam_publish_record')) {
    /**
     * Insert or update result publish record
     * 
     * @param object $resultPublishModel
     * @param array $config Configuration array
     * @return void
     */
    function upsert_exam_publish_record($resultPublishModel, array $config): void
    {
        $existing = $resultPublishModel
            ->where('school_id', $config['school_id'])
            ->where('exam_id', $config['exam_id'])
            ->where('class_id', $config['class_id']);

        if (!empty($config['section_id'])) {
            $existing->where('section_id', $config['section_id']);
        }

        $publishRecord = $existing->first();

        $publishData = [
            'school_id'        => $config['school_id'],
            'school_owner_uid' => $config['user_id'],
            'exam_id'          => $config['exam_id'],
            'class_id'         => $config['class_id'],
            'section_id'       => $config['section_id'] ?: 0,
            'is_published'     => $config['is_published'] ?? 0,
            'published_by'     => $config['published_by'] ?? 0,
            'published_at'     => $config['published_at'] ?? null,
        ];

        if ($publishRecord) {
            // Only update if not already published
            if (!$publishRecord->is_published) {
                $resultPublishModel->update($publishRecord->id, $publishData);
            }
        } else {
            $resultPublishModel->insert($publishData);
        }
    }
}

// ===================================================================
// DELETE HELPERS
// ===================================================================

if (!function_exists('delete_exam_related_results')) {
    /**
     * Delete examination results for recalculation
     * 
     * @param object $subjectResultModel
     * @param object $examResultModel
     * @param object $finalResultModel
     * @param object $resultPublishModel
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int|null $section_id
     * @param int|null $year_id
     * @return void
     */
    function delete_exam_related_results(
        $subjectResultModel,
        $examResultModel,
        $finalResultModel,
        $resultPublishModel,
        int $school_id,
        int $exam_id,
        int $class_id,
        ?int $section_id,
        ?int $year_id
    ): void {
        // Delete subject results
        $subjectDelete = $subjectResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $subjectDelete->where('section_id', $section_id);
        }
        $subjectDelete->delete();

        // Delete exam results
        $examDelete = $examResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $examDelete->where('section_id', $section_id);
        }
        $examDelete->delete();

        // Delete final results
        if ($year_id) {
            $finalDelete = $finalResultModel
                ->where('session_id', $year_id)
                ->where('class_id', $class_id);
            if ($section_id) {
                $finalDelete->where('section_id', $section_id);
            }
            $finalDelete->delete();
        }

        // Delete publish records
        $publishDelete = $resultPublishModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $publishDelete->where('section_id', $section_id);
        }
        $publishDelete->delete();
    }
}

// ===================================================================
// LOCK/UNLOCK HELPERS
// ===================================================================

if (!function_exists('get_exam_lock_error_message')) {
    /**
     * Get formatted lock error message
     * 
     * @param object $markLockModel
     * @param object $examModel
     * @param object $yearModel
     * @param object $classModel
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $session_id
     * @param int|null $subject_id
     * @return string|null Error message or null if not locked
     */
    function get_exam_lock_error_message(
        $markLockModel,
        $examModel,
        $yearModel,
        $classModel,
        int $school_id,
        int $exam_id,
        int $class_id,
        int $session_id,
        ?int $subject_id = null
    ): ?string {
        $isLocked = $markLockModel->isLocked($school_id, $exam_id, $class_id, $session_id, $subject_id);
        
        if (!$isLocked) {
            return null;
        }

        $lockInfo = $markLockModel->getLockStatus($school_id, $exam_id, $class_id, $session_id, $subject_id);
        $exam = $examModel->find($exam_id);
        $examTitle = $exam ? $exam->title : 'this exam';
        $session = $yearModel->find($lockInfo->session_id ?? $session_id);
        $sessionName = $session ? $session->title : 'this session';
        $class = $classModel->find($class_id);
        $className = $class ? $class->title : 'this class';

        return "Marks are locked for the exam '{$examTitle}', session '{$sessionName}', class '{$className}'. Please unlock the marks first.";
    }
}

// ===================================================================
// TOKEN GENERATOR
// ===================================================================

if (!function_exists('generate_exam_token')) {
    /**
     * Generate secure random token
     */
    function generate_exam_token(): string
    {
        return bin2hex(random_bytes(16));
    }
}

// ===================================================================
// HIGHEST MARK RECALCULATION
// ===================================================================

if (!function_exists('recalculate_exam_highest_marks')) {
    /**
     * Recalculate highest marks for all subjects
     * 
     * @param object $subjectResultModel
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int|null $section_id
     * @param array $subjects
     * @return void
     */
    function recalculate_exam_highest_marks($subjectResultModel, int $school_id, int $exam_id, int $class_id, ?int $section_id, array $subjects): void
    {
        foreach ($subjects as $subject) {
            $highestRow = $subjectResultModel
                ->select('MAX(obtained_mark) as highest')
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('subject_id', $subject->id)
                ->get()
                ->getRow();

            $highest = $highestRow ? (float) $highestRow->highest : 0;

            if ($highest > 0) {
                $subjectResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subject->id)
                    ->set(['highest_mark' => $highest])
                    ->update();
            }
        }
    }
}

// ===================================================================
// MERGE/EXCLUDE SUBJECT HELPERS
// ===================================================================

if (!function_exists('apply_exam_merge_subject')) {
    /**
     * Apply merge_others_subject logic to marks
     * 
     * @param float $currentFullMark
     * @param float $currentObtainedMark
     * @param bool $currentIsAbsent
     * @param object|null $mergeSubject
     * @param array $studentRawMarks Array of raw marks keyed by subject_id
     * @return array ['full_mark' => 0, 'obtained_mark' => 0, 'is_absent' => 0]
     */
    function apply_exam_merge_subject(
        float $currentFullMark,
        float $currentObtainedMark,
        bool $currentIsAbsent,
        ?object $mergeSubject,
        array $studentRawMarks
    ): array {
        if (!$mergeSubject || !isset($studentRawMarks[$mergeSubject->id])) {
            return [
                'full_mark'     => $currentFullMark,
                'obtained_mark' => $currentObtainedMark,
                'is_absent'     => $currentIsAbsent,
            ];
        }

        $mergeRaw = $studentRawMarks[$mergeSubject->id];
        
        // Average both subjects' marks: (subject + merged) / 2
        $avgFull = ($currentFullMark + $mergeRaw['full_mark']) / 2;
        $avgObtained = ($currentObtainedMark + $mergeRaw['obtained_mark']) / 2;

        // If merged subject is absent, mark this as absent too
        $isAbsent = $currentIsAbsent || $mergeRaw['is_absent'];

        return [
            'full_mark'     => $avgFull,
            'obtained_mark' => $avgObtained,
            'is_absent'     => $isAbsent,
        ];
    }
}

if (!function_exists('apply_exam_exclude_mark')) {
    /**
     * Apply enabled_exclude_mark logic
     * 
     * @param float $obtainedMark
     * @param object $subject
     * @return array ['include_mark' => 0, 'exclude_mark' => 0, 'exclude_grade_point' => 0, 'exclude_percentage' => 0]
     */
    function apply_exam_exclude_mark(float $obtainedMark, object $subject): array
    {
        $isExcludeEnabled = !empty($subject->enabled_exclude_mark);
        
        if (!$isExcludeEnabled) {
            return [
                'include_mark'        => $obtainedMark,
                'exclude_mark'        => 0,
                'exclude_grade_point' => 0,
                'exclude_percentage'  => 0,
            ];
        }

        $excludeMark = (float) ($subject->exclude_mark ?? 0);
        $excludeGradePoint = (float) ($subject->exclude_grade_point ?? 0);
        $excludePercentage = (float) ($subject->exclude_percentage ?? 0);
        
        // Subtract exclude_mark from obtained marks
        $includeMark = max(0, $obtainedMark - $excludeMark);

        return [
            'include_mark'        => $includeMark,
            'exclude_mark'        => $excludeMark,
            'exclude_grade_point' => $excludeGradePoint,
            'exclude_percentage'  => $excludePercentage,
        ];
    }
}

// ===================================================================
// VALIDATION HELPERS
// ===================================================================

if (!function_exists('validate_exam_marks_exist')) {
    /**
     * Validate that marks exist for all subject-student combinations
     * 
     * @param object $markModel
     * @param array $subjects
     * @param array $enrollments
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @return array ['status' => bool, 'message' => '', 'missing' => []]
     */
    function validate_exam_marks_exist($markModel, array $subjects, array $enrollments, int $school_id, int $exam_id, int $class_id): array
    {
        $missingMarks = [];
        
        foreach ($subjects as $subject) {
            foreach ($enrollments as $enr) {
                $markCount = $markModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subject->id)
                    ->where('student_id', $enr->student_id)
                    ->countAllResults();

                if ($markCount == 0) {
                    $missingMarks[] = 'Subject ID ' . $subject->id . ', Student ID ' . $enr->student_id;
                }
            }
        }

        if (!empty($missingMarks)) {
            $sample = array_slice($missingMarks, 0, 5);
            return [
                'status'  => false,
                'message' => 'Marks are not fully entered. Missing marks for some subject-student combinations. Sample: ' . implode('; ', $sample) . (count($missingMarks) > 5 ? ' and ' . (count($missingMarks) - 5) . ' more.' : '.'),
                'missing' => $missingMarks,
            ];
        }

        return ['status' => true, 'message' => '', 'missing' => []];
    }
}

if (!function_exists('validate_exam_marks_locked')) {
    /**
     * Validate that all marks are locked
     * 
     * @param object $markLockModel
     * @param object $subjectModel
     * @param array $subjects
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $year_id
     * @return array ['status' => bool, 'message' => '']
     */
    function validate_exam_marks_locked($markLockModel, $subjectModel, array $subjects, int $school_id, int $exam_id, int $class_id, int $year_id): array
    {
        foreach ($subjects as $subject) {
            $isLocked = $markLockModel->isLocked($school_id, $exam_id, $class_id, $year_id, $subject->id);
            if (!$isLocked) {
                $subjectInfo = $subjectModel->find($subject->id);
                $subjectName = $subjectInfo ? $subjectInfo->title : 'Subject ID ' . $subject->id;
                return [
                    'status'  => false,
                    'message' => "Marks for subject '{$subjectName}' are not locked. Please lock all marks before generating results.",
                ];
            }
        }

        return ['status' => true, 'message' => ''];
    }
}

// ===================================================================
// JSON RESPONSE HELPER
// ===================================================================

if (!function_exists('exam_json_response')) {
    /**
     * Create JSON response with CSRF token header
     * 
     * @param array $response
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    function exam_json_response(array $response)
    {
        return service('response')
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}

// ===================================================================
// LOGGING HELPER
// ===================================================================

if (!function_exists('exam_log_message')) {
    /**
     * Log examination message with prefix
     * 
     * @param string $type Log level (info, error, debug, etc.)
     * @param string $message Message to log
     * @param string $prefix Prefix for the log message
     * @return void
     */
    function exam_log_message(string $type, string $message, string $prefix = '[Examination]'): void
    {
        log_message($type, $prefix . ' ' . $message);
    }
}
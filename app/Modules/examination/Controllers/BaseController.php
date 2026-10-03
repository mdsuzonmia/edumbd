<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController as CIBaseController;
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

/**
 * Base Controller for Examination Module
 * 
 * This controller automatically loads the examination module helper
 * and provides common functionality for all examination controllers.
 * 
 * Usage:
 *   class YourController extends BaseController
 *   {
 *       // All helper functions are now available
 *   }
 */
class BaseController extends CIBaseController
{
    // Common models used across examination controllers
    protected $db;
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentModel $StudentModel;
    protected SubjectModel $SubjectModel;
    protected ExamModel $ExamModel;
    protected StudentSubjectModel $StudentSubjectModel;
    protected GradeRuleModel $GradeRulesModel;
    protected GradeSystemModel $GradeSystemModel;
    protected MarkModel $MarkModel;
    protected SubjectResultModel $SubjectResultModel;
    protected ExamResultModel $ExamResultModel;
    protected FinalResultModel $FinalResultModel;
    protected FinalResultSubjectModel $FinalResultSubjectModel;
    protected ResultPublishModel $ResultPublishModel;
    protected MarkLockModel $MarkLockModel;
    
    /**
     * Constructor - Load module helper
     */
    public function __construct()
    {
        // App\Controllers\BaseController follows CodeIgniter's initController()
        // lifecycle and intentionally has no PHP constructor to invoke.
        // Load the examination module helpers
        helper('examination');
        helper('results');
        
        // Initialize common models
        $this->db = \Config\Database::connect();
        $this->SchoolModel = new SchoolModel();
        $this->YearModel = new AcademicsYearModel();
        $this->ClassModel = new AcademicsClassesModel();
        $this->SectionModel = new AcademicsSectionModel();
        $this->EnrollmentModel = new StudentEnrollmentModel();
        $this->StudentModel = new StudentModel();
        $this->SubjectModel = new SubjectModel();
        $this->ExamModel = new ExamModel();
        $this->StudentSubjectModel = new StudentSubjectModel();
        $this->GradeRulesModel = new GradeRuleModel();
        $this->GradeSystemModel = new GradeSystemModel();
        $this->MarkModel = new MarkModel();
        $this->SubjectResultModel = new SubjectResultModel();
        $this->ExamResultModel = new ExamResultModel();
        $this->FinalResultModel = new FinalResultModel();
        $this->FinalResultSubjectModel = new FinalResultSubjectModel();
        $this->ResultPublishModel = new ResultPublishModel();
        $this->MarkLockModel = new MarkLockModel();
    }
    
    /**
     * Get current logged-in user ID
     * 
     * @return int
     */
    protected function getUserId(): int
    {
        return get_exam_user_id();
    }
    
    /**
     * Get schools accessible by current user
     * 
     * @return array
     */
    protected function getUserSchools(): array
    {
        return get_exam_user_schools();
    }
    
    /**
     * Get school list as dropdown array [id => name]
     * 
     * @return array
     */
    protected function getSchoolDropdown(): array
    {
        return get_exam_school_dropdown();
    }
    
    /**
     * Check if a school setting is enabled
     * 
     * @param int $school_id
     * @param string $key
     * @return bool
     */
    protected function isSchoolSettingEnabled(int $school_id, string $key): bool
    {
        return is_exam_school_setting_enabled($school_id, $key);
    }
    
    /**
     * Get active options from a model for dropdown
     * 
     * @param int $school_id
     * @param string $model_name
     * @return array
     */
    protected function getActiveOptions(int $school_id, string $model_name): array
    {
        return get_exam_active_options($school_id, $model_name, $this);
    }
    
    /**
     * Get academic data by school
     * 
     * @param int $school_id
     * @param bool $include_exams
     * @return array
     */
    protected function getAcademicDataBySchool(int $school_id, bool $include_exams = true): array
    {
        return get_exam_academic_data_by_school($school_id, $this, $include_exams);
    }
    
    /**
     * Get student enrollments by filters
     * 
     * @param int $school_id
     * @param int $class_id
     * @param int|null $section_id
     * @param int|null $year_id
     * @return array
     */
    protected function getEnrollmentsByFilters(int $school_id, int $class_id, ?int $section_id, ?int $year_id): array
    {
        return get_exam_enrollments_by_filters(
            $this->EnrollmentModel,
            $school_id,
            $class_id,
            $section_id,
            $year_id
        );
    }
    
    /**
     * Get student subjects
     * 
     * @param int $school_id
     * @param int $enrollment_id
     * @return array
     */
    protected function getStudentSubjects(int $school_id, int $enrollment_id): array
    {
        return get_exam_student_subjects($this->StudentSubjectModel, $school_id, $enrollment_id);
    }
    
    /**
     * Filter subjects for student
     * 
     * @param array $allSubjects
     * @param array $studentSubjectIds
     * @return array
     */
    protected function filterSubjectsForStudent(array $allSubjects, array $studentSubjectIds): array
    {
        return filter_exam_subjects_for_student($allSubjects, $studentSubjectIds);
    }
    
    /**
     * Calculate grade for percentage
     * 
     * @param float $percentage
     * @param int|null $grade_system_id
     * @return array
     */
    protected function calculateGradeForPercentage(float $percentage, ?int $grade_system_id): array
    {
        return calculate_exam_grade_for_percentage($percentage, $grade_system_id, $this->GradeRulesModel);
    }
    
    /**
     * Calculate GPA
     * 
     * @param array $gradePoints
     * @param int $totalSubjects
     * @return float
     */
    protected function calculateGPA(array $gradePoints, int $totalSubjects): float
    {
        return calculate_exam_gpa($gradePoints, $totalSubjects);
    }
    
    /**
     * Calculate percentage
     * 
     * @param float $obtained
     * @param float $full
     * @return float
     */
    protected function calculatePercentage(float $obtained, float $full): float
    {
        return calculate_exam_percentage($obtained, $full);
    }
    
    /**
     * Get student raw marks
     * 
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $subject_id
     * @param int $student_id
     * @return array
     */
    protected function getStudentRawMarks(int $school_id, int $exam_id, int $class_id, int $subject_id, int $student_id): array
    {
        return get_exam_student_raw_marks($this->MarkModel, $school_id, $exam_id, $class_id, $subject_id, $student_id);
    }
    
    /**
     * Get highest mark for subject
     * 
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $subject_id
     * @param float $currentStudentMark
     * @return float
     */
    protected function getHighestMarkForSubject(int $school_id, int $exam_id, int $class_id, int $subject_id, float $currentStudentMark): float
    {
        return get_exam_highest_mark_for_subject(
            $this->SubjectResultModel,
            $this->MarkModel,
            $school_id,
            $exam_id,
            $class_id,
            $subject_id,
            $currentStudentMark
        );
    }
    
    /**
     * Build subject result data
     * 
     * @param array $config
     * @return array
     */
    protected function buildSubjectResultData(array $config): array
    {
        return build_exam_subject_result_data($config);
    }
    
    /**
     * Upsert subject result
     * 
     * @param array $data
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $subject_id
     * @param int $student_id
     * @return int
     */
    protected function upsertSubjectResult(array $data, int $school_id, int $exam_id, int $class_id, int $subject_id, int $student_id): int
    {
        return upsert_exam_subject_result($this->SubjectResultModel, $data, $school_id, $exam_id, $class_id, $subject_id, $student_id);
    }
    
    /**
     * Build exam result data
     * 
     * @param array $config
     * @return array
     */
    protected function buildExamResultData(array $config): array
    {
        return build_exam_result_data($config);
    }
    
    /**
     * Upsert exam result
     * 
     * @param array $data
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $student_id
     * @return void
     */
    protected function upsertExamResult(array $data, int $school_id, int $exam_id, int $class_id, int $student_id): void
    {
        upsert_exam_result($this->ExamResultModel, $data, $school_id, $exam_id, $class_id, $student_id, fn() => generate_exam_token());
    }
    
    /**
     * Assign ranks with ties
     * 
     * @param array $results
     * @param string $marks_key
     * @param string $rank_key
     * @return array
     */
    protected function assignRanksWithTies(array $results, string $marks_key = 'obtained_marks', string $rank_key = 'rank'): array
    {
        return assign_exam_ranks_with_ties($results, $marks_key, $rank_key);
    }
    
    /**
     * Update result ranks batch
     * 
     * @param array $results
     * @param array $rankFields
     * @return void
     */
    protected function updateResultRanksBatch(array $results, array $rankFields): void
    {
        update_exam_result_ranks_batch($this->ExamResultModel, $results, $rankFields);
    }
    
    /**
     * Calculate weighted aggregate
     * 
     * @param array $examResults
     * @param array $exams
     * @return array
     */
    protected function calculateWeightedAggregate(array $examResults, array $exams): array
    {
        return calculate_exam_weighted_aggregate($examResults, $exams);
    }
    
    /**
     * Build final result data
     * 
     * @param array $config
     * @return array
     */
    protected function buildFinalResultData(array $config): array
    {
        return build_exam_final_result_data($config);
    }
    
    /**
     * Upsert final result
     * 
     * @param array $data
     * @param int $student_id
     * @param int $year_id
     * @param int $class_id
     * @return void
     */
    protected function upsertFinalResult(array $data, int $student_id, int $year_id, int $class_id): void
    {
        upsert_exam_final_result($this->FinalResultModel, $data, $student_id, $year_id, $class_id, fn() => generate_exam_token());
    }
    
    /**
     * Upsert publish record
     * 
     * @param array $config
     * @return void
     */
    protected function upsertPublishRecord(array $config): void
    {
        upsert_exam_publish_record($this->ResultPublishModel, $config);
    }
    
    /**
     * Delete related results
     * 
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int|null $section_id
     * @param int|null $year_id
     * @return void
     */
    protected function deleteRelatedResults(int $school_id, int $exam_id, int $class_id, ?int $section_id, ?int $year_id): void
    {
        delete_exam_related_results(
            $this->SubjectResultModel,
            $this->ExamResultModel,
            $this->FinalResultModel,
            $this->ResultPublishModel,
            $school_id,
            $exam_id,
            $class_id,
            $section_id,
            $year_id
        );
    }
    
    /**
     * Get lock error message
     * 
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $session_id
     * @param int|null $subject_id
     * @return string|null
     */
    protected function getLockErrorMessage(int $school_id, int $exam_id, int $class_id, int $session_id, ?int $subject_id = null): ?string
    {
        return get_exam_lock_error_message(
            $this->MarkLockModel,
            $this->ExamModel,
            $this->YearModel,
            $this->ClassModel,
            $school_id,
            $exam_id,
            $class_id,
            $session_id,
            $subject_id
        );
    }
    
    /**
     * Generate token
     * 
     * @return string
     */
    protected function generateToken(): string
    {
        return generate_exam_token();
    }
    
    /**
     * Recalculate highest marks
     * 
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int|null $section_id
     * @param array $subjects
     * @return void
     */
    protected function recalculateHighestMarks(int $school_id, int $exam_id, int $class_id, ?int $section_id, array $subjects): void
    {
        recalculate_exam_highest_marks($this->SubjectResultModel, $school_id, $exam_id, $class_id, $section_id, $subjects);
    }
    
    /**
     * Apply merge subject
     * 
     * @param float $currentFullMark
     * @param float $currentObtainedMark
     * @param bool $currentIsAbsent
     * @param object|null $mergeSubject
     * @param array $studentRawMarks
     * @return array
     */
    protected function applyMergeSubject(float $currentFullMark, float $currentObtainedMark, bool $currentIsAbsent, $mergeSubject, array $studentRawMarks): array
    {
        return apply_exam_merge_subject($currentFullMark, $currentObtainedMark, $currentIsAbsent, $mergeSubject, $studentRawMarks);
    }
    
    /**
     * Apply exclude mark
     * 
     * @param float $obtainedMark
     * @param object $subject
     * @return array
     */
    protected function applyExcludeMark(float $obtainedMark, $subject): array
    {
        return apply_exam_exclude_mark($obtainedMark, $subject);
    }
    
    /**
     * Validate marks exist
     * 
     * @param array $subjects
     * @param array $enrollments
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @return array
     */
    protected function validateMarksExist(array $subjects, array $enrollments, int $school_id, int $exam_id, int $class_id): array
    {
        return validate_exam_marks_exist($this->MarkModel, $subjects, $enrollments, $school_id, $exam_id, $class_id);
    }
    
    /**
     * Validate marks locked
     * 
     * @param array $subjects
     * @param int $school_id
     * @param int $exam_id
     * @param int $class_id
     * @param int $year_id
     * @return array
     */
    protected function validateMarksLocked(array $subjects, int $school_id, int $exam_id, int $class_id, int $year_id): array
    {
        return validate_exam_marks_locked($this->MarkLockModel, $this->SubjectModel, $subjects, $school_id, $exam_id, $class_id, $year_id);
    }
    
    /**
     * JSON response
     * 
     * @param array $response
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    protected function jsonResponse(array $response)
    {
        return exam_json_response($response);
    }
    
    /**
     * Log message
     * 
     * @param string $type
     * @param string $message
     * @param string $prefix
     * @return void
     */
    protected function logMessage(string $type, string $message, string $prefix = '[Examination]'): void
    {
        exam_log_message($type, $message, $prefix);
    }
    
    // ===================================================================
    // RESULTS HELPER WRAPPER METHODS
    // ===================================================================
    
    /**
     * Get student subject categories (compulsory, combined, optional)
     * 
     * @param int $school_id
     * @param int $enrollment_id
     * @return array
     */
    protected function getStudentSubjectCategories(int $school_id, int $enrollment_id): array
    {
        return get_student_subject_categories($school_id, $enrollment_id);
    }
    
    /**
     * Calculate exam result statistics
     * 
     * @param array $results
     * @return array
     */
    protected function calculateResultStatistics(array $results): array
    {
        return calculate_exam_result_statistics($results);
    }
    
    /**
     * Get grade distribution
     * 
     * @param array $results
     * @return array
     */
    protected function getGradeDistribution(array $results): array
    {
        return get_exam_grade_distribution($results);
    }
    
    /**
     * Get subject-wise statistics
     * 
     * @param array $subjectResults
     * @return array
     */
    protected function getSubjectWiseStatistics(array $subjectResults): array
    {
        return get_exam_subject_wise_statistics($subjectResults);
    }
    
    /**
     * Calculate positions for students
     * 
     * @param array $students
     * @param string $marks_key
     * @return array
     */
    protected function calculatePositions(array $students, string $marks_key = 'obtained_marks'): array
    {
        return calculate_exam_positions($students, $marks_key);
    }
    
    /**
     * Format result for report
     * 
     * @param object|array $result
     * @param array $subjects
     * @return array
     */
    protected function formatResultForReport($result, array $subjects = []): array
    {
        return format_exam_result_for_report($result, $subjects);
    }
    
    /**
     * Calculate class summary
     * 
     * @param array $results
     * @return array
     */
    protected function calculateClassSummary(array $results): array
    {
        return calculate_exam_class_summary($results);
    }
}

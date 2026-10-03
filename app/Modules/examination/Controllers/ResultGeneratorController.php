<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentModel;
use App\Modules\examination\Models\MarkModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\FinalResultModel;
use App\Services\ServicePricingService;
use App\Models\ServiceOrderModel;
use App\Modules\examination\Models\ResultPublishModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\GradeRuleModel;
use App\Modules\examination\Models\GradeSystemModel;
use App\Modules\examination\Models\MarkLockModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\StudentSubjectModel;

class ResultGeneratorController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentModel $StudentModel;
    protected MarkModel $MarkModel;
    protected SubjectResultModel $SubjectResultModel;
    protected ExamResultModel $ExamResultModel;
    protected FinalResultModel $FinalResultModel;
    protected ResultPublishModel $ResultPublishModel;
    protected SubjectModel $SubjectModel;
    protected GradeRuleModel $GradeRulesModel;
    protected GradeSystemModel $GradeSystemModel;
    protected MarkLockModel $MarkLockModel;
    protected ExamModel $ExamModel;
    protected StudentSubjectModel $StudentSubjectModel;
    protected $db;

    public function __construct()
    {
        $this->SchoolModel          = new SchoolModel();
        $this->YearModel            = new AcademicsYearModel();
        $this->ClassModel           = new AcademicsClassesModel();
        $this->SectionModel         = new AcademicsSectionModel();
        $this->EnrollmentModel      = new StudentEnrollmentModel();
        $this->StudentModel         = new StudentModel();
        $this->MarkModel            = new MarkModel();
        $this->SubjectResultModel   = new SubjectResultModel();
        $this->ExamResultModel      = new ExamResultModel();
        $this->FinalResultModel     = new FinalResultModel();
        $this->ResultPublishModel   = new ResultPublishModel();
        $this->SubjectModel         = new SubjectModel();
        $this->GradeRulesModel      = new GradeRuleModel();
        $this->GradeSystemModel     = new GradeSystemModel();
        $this->MarkLockModel        = new MarkLockModel();
        $this->ExamModel            = new ExamModel();
        $this->StudentSubjectModel  = new StudentSubjectModel();
        $this->db = \Config\Database::connect();
    }

    protected function getUserId(): int
    {
        return (int) session('user_id');
    }

    protected function getUserSchools(): array
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return [];
        }

        return $this->SchoolModel
            ->select('schools.id, schools.name, schools.params')
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('school_user_relation.user_id', $user_id)
            ->where('schools.status', 1)
            ->orderBy('schools.name', 'ASC')
            ->findAll();
    }

    protected function getSchoolDropdown(): array
    {
        $schools = $this->getUserSchools();
        $list = [];
        foreach ($schools as $s) {
            $list[$s->id] = $s->name;
        }
        return $list;
    }

    protected function isSchoolSettingEnabled(int $school_id, string $key): bool
    {
        $school = $this->SchoolModel->find($school_id);
        if (!$school || empty($school->params)) {
            return false;
        }
        $params = json_decode($school->params, true);
        return !empty($params[$key]);
    }

    protected function getActiveOptions(int $school_id, string $modelProperty): array
    {
        $list = [];
        $model = $this->{$modelProperty};
        
        $orderColumn = ($modelProperty === 'MarkDistributionModel') ? 'name' : 'title';
        $valueColumn = ($modelProperty === 'MarkDistributionModel') ? 'name' : 'title';
        
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

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    protected function logMessage(string $type, string $message): void
    {
        log_message($type, '[ResultGenerator] ' . $message);
    }

    // ===================================================================
    // STEP 0: AJAX - Get academic data by school (same pattern as Marks)
    // ===================================================================
    public function getAcademicDataBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/results/generate');
        }

        $school_id = (int) $this->request->getPost('school_id');
        if (!$school_id) {
            return $this->jsonResponse(['status' => false]);
        }

        $yearList       = $this->getActiveOptions($school_id, 'YearModel');
        $classList      = $this->getActiveOptions($school_id, 'ClassModel');
        $sectionList    = $this->getActiveOptions($school_id, 'SectionModel');
        $examList       = $this->getActiveOptions($school_id, 'ExamModel');

        return $this->jsonResponse([
            'status'       => true,
            'year_list'       => $yearList,
            'class_list'      => $classList,
            'section_list'    => $sectionList,
            'exam_list'       => $examList,
            'academic_section_enabled' => $this->isSchoolSettingEnabled($school_id, 'academic_section_enabled'),
        ]);
    }

    // ===================================================================
    // INDEX: Display the generate result form
    // ===================================================================
    public function index()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('ResultGenerator.page_title_generate'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\results\generate', $data)
            . view('footer', $footer_data);
    }

    // ===================================================================
    // MAIN GENERATE: Orchestrates ALL 6 steps
    // ===================================================================
    public function generate()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        // Check subscription
        $subscription_check = check_subscription('examination/results/generate');
        if ($subscription_check) {
            return $this->jsonResponse(['status' => false, 'message' => $subscription_check['html']]);
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');
        $year_id    = (int) $this->request->getPost('year_id');
        $previewOnly = (bool) $this->request->getPost('preview_only');

        if (!$school_id || !$exam_id || !$class_id || !$year_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields.']);
        }

        if (!$previewOnly && !(new ServicePricingService())->isPaid($school_id, ServicePricingService::RESULT, $exam_id)) {
            return $this->jsonResponse(['status'=>false,'message'=>'Final Result তৈরি করতে আগে এই পরীক্ষার পেমেন্ট সম্পন্ন করুন।']);
        }

        $user_id = $this->getUserId();
        $steps_completed = [];

        try {
            $this->db->transBegin();

            // ===================================================================
            // STEP 1: VALIDATE - Check marks exist and are locked
            // ===================================================================
            $validationResult = $this->validateMarks($school_id, $exam_id, $class_id, $section_id, $year_id);
            if (!$validationResult['status']) {
                $this->db->transRollback();
                return $this->jsonResponse($validationResult);
            }
            $steps_completed[] = 'validate';
            $this->logMessage('info', 'Step 1 [Validate]: Passed for school=' . $school_id . ' exam=' . $exam_id . ' class=' . $class_id);

            // ===================================================================
            // STEP 2: GENERATE SUBJECT RESULTS (edum_examination_subject_results)
            // ===================================================================
            $subjectResult = $this->generateSubjectResults($school_id, $exam_id, $class_id, $section_id, $year_id, $user_id);
           
            if (!$subjectResult['status']) {
                $this->db->transRollback();
                return $this->jsonResponse($subjectResult);
            }
            $steps_completed[] = 'subject_results';
            $this->logMessage('info', 'Step 2 [Subject Results]: Generated for ' . $subjectResult['processed'] . ' subjects.');

            // ===================================================================
            // STEP 3: GENERATE EXAM RESULTS (edum_examination_results)
            // ===================================================================
            $examResult = $this->generateExamResults($school_id, $exam_id, $class_id, $section_id, $year_id, $user_id, $subjectResult['data'], $subjectResult['combined_grade_point_sums'] ?? []);
            if (!$examResult['status']) {
                $this->db->transRollback();
                return $this->jsonResponse($examResult);
            }
            $steps_completed[] = 'exam_results';
            $this->logMessage('info', 'Step 3 [Exam Results]: Generated for ' . $examResult['processed'] . ' students.');

            // ===================================================================
            // STEP 4: GENERATE POSITIONS (Ranks within exam_results)
            // ===================================================================
            $positionResult = $this->generatePositions($school_id, $exam_id, $class_id, $section_id, $year_id);
            if (!$positionResult['status']) {
                $this->db->transRollback();
                return $this->jsonResponse($positionResult);
            }
            $steps_completed[] = 'positions';
            $this->logMessage('info', 'Step 4 [Positions]: Generated ranks successfully.');

            
            // ===================================================================
            // STEP 6: PUBLISH (edum_examination_results_publish)
            // ===================================================================
            if (!$previewOnly) {
                $publishResult = $this->autoPublish($school_id, $exam_id, $class_id, $section_id, $user_id);
                if (!$publishResult['status']) {
                    $this->db->transRollback();
                    return $this->jsonResponse($publishResult);
                }
                $steps_completed[] = 'publish';
                $this->logMessage('info', 'Step 6 [Publish]: Auto-publish record created.');
            } else {
                $steps_completed[] = 'preview';
            }

            // ===================================================================
            // COMMIT TRANSACTION
            // ===================================================================
            $this->db->transCommit();

            if (!$previewOnly) {
                $paidOrder = (new ServiceOrderModel())->where(['school_id'=>$school_id,'service'=>ServicePricingService::RESULT,'exam_id'=>$exam_id])->where('status','paid')->orderBy('id','DESC')->first();
                if ($paidOrder) (new ServiceOrderModel())->update($paidOrder->id,['status'=>'completed','completed_at'=>date('Y-m-d H:i:s')]);
            }

            return $this->jsonResponse([
                'status'  => true,
                'message' => 'Results generated successfully through all steps.',
                'steps_completed' => $steps_completed,
                'subject_results' => $subjectResult['processed'],
                'exam_results'    => $examResult['processed'],
                'preview_only'    => $previewOnly,
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            $this->logMessage('error', 'Generate failed: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Error generating results: ' . $e->getMessage(),
            ]);
        }
    }

    // ===================================================================
    // RECALCULATE: Regenerates results for existing data
    // ===================================================================
    public function recalculate()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');
        $year_id    = (int) $this->request->getPost('year_id');

        if (!$school_id || !$exam_id || !$class_id || !$year_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields.']);
        }

        // For recalculate, skip validation (marks might already have been processed)
        // Just remove existing results and regenerate
        $user_id = $this->getUserId();

        try {
            $this->db->transBegin();

            // Delete existing results for this exam/class/section
            $this->deleteExistingResults($school_id, $exam_id, $class_id, $section_id, $year_id);

            // Proceed with generate steps 2-6 (skip validation)
            $subjectResult = $this->generateSubjectResults($school_id, $exam_id, $class_id, $section_id, $year_id, $user_id);
            if (!$subjectResult['status']) {
                $this->db->transRollback();
                return $this->jsonResponse($subjectResult);
            }

            $examResult = $this->generateExamResults($school_id, $exam_id, $class_id, $section_id, $year_id, $user_id, $subjectResult['data'], $subjectResult['combined_grade_point_sums'] ?? []);
            if (!$examResult['status']) {
                $this->db->transRollback();
                return $this->jsonResponse($examResult);
            }

            $positionResult = $this->generatePositions($school_id, $exam_id, $class_id, $section_id, $year_id);
            if (!$positionResult['status']) {
                $this->db->transRollback();
                return $this->jsonResponse($positionResult);
            }

            $this->db->transCommit();

            return $this->jsonResponse([
                'status'  => true,
                'message' => 'Results recalculated successfully.',
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            $this->logMessage('error', 'Recalculate failed: ' . $e->getMessage());
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Error recalculating results: ' . $e->getMessage(),
            ]);
        }
    }

    // ===================================================================
    // STEP 1: VALIDATE MARKS
    // ===================================================================
    private function validateMarks(int $school_id, int $exam_id, int $class_id, int $section_id, int $year_id): array
    {
        // Check only subjects assigned to students in this class/year. A school
        // can have many subjects for other classes; requiring all of them made
        // the guided (and existing) class result flow impossible to complete.
        $subjects = $this->SubjectModel
            ->select('examination_subjects.*')
            ->distinct()
            ->join('student_subjects wizard_ss', 'wizard_ss.subject_id = examination_subjects.id')
            ->join('student_enrollments wizard_se', 'wizard_se.id = wizard_ss.enrollment_id')
            ->where('examination_subjects.school_id', $school_id)
            ->where('examination_subjects.status', 1)
            ->where('wizard_se.class_id', $class_id)
            ->where('wizard_se.session_id', $year_id)
            ->findAll();

        if (empty($subjects)) {
            return ['status' => false, 'message' => 'No subjects found for this class.'];
        }

        // Check 2: Enrolled students exist
        $this->EnrollmentModel
            ->select('student_enrollments.id, student_enrollments.student_id')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);

        if ($section_id) {
            $this->EnrollmentModel->where('student_enrollments.section_id', $section_id);
        }
        if ($year_id) {
            $this->EnrollmentModel->where('student_enrollments.session_id', $year_id);
        }

        $enrollments = $this->EnrollmentModel->findAll();

        if (empty($enrollments)) {
            return ['status' => false, 'message' => 'No students found for the selected criteria.'];
        }

        // Check 3: Marks exist for each subject-student combination
        $missingMarks = [];
        foreach ($subjects as $subject) {
            $isOptionalSubject = !empty($subject->optional);

            // If optional subject, only check students assigned to this optional subject
            if ($isOptionalSubject) {
                $assignments = $this->StudentSubjectModel
                    ->select('enrollment_id')
                    ->where('school_id', $school_id)
                    ->where('optional_subject_id', $subject->id)
                    ->findAll();

                $optionalEnrollmentIds = [];
                foreach ($assignments as $assignment) {
                    $optionalEnrollmentIds[] = (int) $assignment->enrollment_id;
                }

                if (empty($optionalEnrollmentIds)) {
                    continue; // No students assigned to this optional subject, skip
                }

                // Filter enrollments to only those assigned to this optional subject
                $subjectEnrollments = array_filter($enrollments, function ($enr) use ($optionalEnrollmentIds) {
                    return in_array((int) $enr->id, $optionalEnrollmentIds);
                });
            } else {
                $subjectEnrollments = $enrollments;
            }

            foreach ($subjectEnrollments as $enr) {
                $markCount = $this->MarkModel
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
                'status' => false,
                'message' => 'Marks are not fully entered. Missing marks for some subject-student combinations. Sample: ' . implode('; ', $sample) . (count($missingMarks) > 5 ? ' and ' . (count($missingMarks) - 5) . ' more.' : '.'),
            ];
        }

        // Check 4: All marks are locked
        foreach ($subjects as $subject) {
            $isOptionalSubject = !empty($subject->optional);

            // If optional subject, check if any students are assigned. If none, skip lock check.
            if ($isOptionalSubject) {
                $assignedCount = $this->StudentSubjectModel
                    ->where('school_id', $school_id)
                    ->where('optional_subject_id', $subject->id)
                    ->countAllResults();

                if ($assignedCount == 0) {
                    continue; // No students assigned to this optional subject, no lock needed
                }
            }

            $isLocked = $this->MarkLockModel->isLocked($school_id, $exam_id, $class_id, $year_id, $subject->id);
            if (!$isLocked) {
                $subjectInfo = $this->SubjectModel->find($subject->id);
                $subjectName = $subjectInfo ? $subjectInfo->title : 'Subject ID ' . $subject->id;
                return [
                    'status' => false,
                    'message' => "Marks for subject '{$subjectName}' are not locked. Please lock all marks before generating results.",
                ];
            }
        }

        return ['status' => true];
    }

    // ===================================================================
    // STEP 2: GENERATE SUBJECT RESULTS
    // ===================================================================
    private function generateSubjectResults(int $school_id, int $exam_id, int $class_id, int $section_id, int $year_id, int $user_id): array
    {
        // Get all subjects for lookup (used for merge_others_subject and other subject properties)
        $allSubjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->findAll();

        // Build a lookup of subjects by ID for quick access
        $subjectLookup = [];
        foreach ($allSubjects as $s) {
            $subjectLookup[$s->id] = $s;
        }

        $this->EnrollmentModel
            ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('students.status', 1);

        if ($section_id) {
            $this->EnrollmentModel->where('student_enrollments.section_id', $section_id);
        }
        if ($year_id) {
            $this->EnrollmentModel->where('student_enrollments.session_id', $year_id);
        }

        $enrollments = $this->EnrollmentModel->findAll();

        $allSubjectResults = [];
        $processed = 0;
        $allCombinedGradePointSums = []; // Store combined sums for all students

        foreach ($enrollments as $enrollment) {
            // Get categorized subjects using the model method
            $student = $this->StudentModel->find($enrollment->student_id);
            $subjectCategories = $this->StudentSubjectModel->get_student_subject_categories($school_id, $enrollment->id, $exam_id, $class_id, $year_id, $student);

            // DEBUG: Log subject count
            $totalCount = count($subjectCategories['compulsory_subjects'] ?? [])
                + count($subjectCategories['combined_subjects'] ?? [])
                + count($subjectCategories['optional_subjects'] ?? []);
            $this->logMessage('info', 'Student ' . $enrollment->student_id . ': Found ' . $totalCount . ' subjects');

            // Collect ALL subjects into a flat list for raw marks calculation
            $allStudentSubjects = array_merge(
                $subjectCategories['compulsory_subjects'] ?? [],
                $subjectCategories['combined_subjects'] ?? [],
                $subjectCategories['optional_subjects'] ?? []
            );

            // First pass: calculate raw marks for all subjects
            $studentRawMarks = [];
            foreach ($allStudentSubjects as $subject) {
                $subjectId = $subject->id;

                $markRecords = $this->MarkModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subjectId)
                    ->where('student_id', $enrollment->student_id)
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

                $studentRawMarks[$subjectId] = [
                    'full_mark'     => $fullMark,
                    'obtained_mark' => $obtainedMark,
                    'is_absent'     => $isAbsent,
                    'remarks'       => $remarks,
                ];
            }

            // Group combined subjects by combine_group
            $combinedGroups = [];
            foreach (($subjectCategories['combined_subjects'] ?? []) as $subject) {
                $group = $subject->combine_group ?? '';
                if (!empty($group)) {
                    $combinedGroups[$group][] = $subject;
                }
            }

            // Calculate combined grade point sums for each group
            $combinedGradePointSums = [];
            foreach ($combinedGroups as $groupName => $groupSubjects) {
                $gradePointSum = 0;
                foreach ($groupSubjects as $subject) {
                    $subjectId = $subject->id;
                    $raw = $studentRawMarks[$subjectId];
                    $subjectPercentage = $raw['full_mark'] > 0 ? round(($raw['obtained_mark'] / $raw['full_mark']) * 100, 2) : 0;

                    if (!empty($subject->grade_system_id)) {
                        $gradingRecords = $this->GradeRulesModel
                            ->where('grade_system_id', (int) $subject->grade_system_id)
                            ->where('status', 1)
                            ->orderBy('mark_from', 'DESC')
                            ->findAll();

                        foreach ($gradingRecords as $g) {
                            if ($subjectPercentage >= (float) $g->mark_from && $subjectPercentage <= (float) $g->mark_to) {
                                $gradePointSum += (float) ($g->grade_point ?? 0);
                                break;
                            }
                        }
                    }
                }
                $combinedGradePointSums[$groupName] = $gradePointSum;
            }

            // Tracking arrays (matching reference generateSubjectTable pattern)
            $total_included_mark = [];
            $total_include_parcentage = [];
            $total_include_grade_point = [];
            $optionalBonusGP = 0;
            $hasOptional = false;

            // ===================================================================
            // Process subjects in order: Combined → Compulsory → Optional
            // ===================================================================

            // ---------------------------------------------------------------
            // 1. COMBINED SUBJECTS (grouped by combine_group)
            // ---------------------------------------------------------------
            foreach ($combinedGroups as $groupName => $groupSubjects) {
                $count = count($groupSubjects);

                // Calculate combined values across the group
                $totalObtained = 0;
                $totalPercentage = 0;
                $totalGradePoint = 0;
                $combinedFullMark = 0;

                foreach ($groupSubjects as $subject) {
                    $subjectId = $subject->id;
                    $raw = $studentRawMarks[$subjectId];
                    $totalObtained += $raw['obtained_mark'];
                    $totalPercentage += $raw['full_mark'] > 0 ? ($raw['obtained_mark'] / $raw['full_mark']) * 100 : 0;
                    $combinedFullMark += $raw['full_mark'];

                    // Calculate individual grade point
                    $subjectPercentage = $raw['full_mark'] > 0 ? round(($raw['obtained_mark'] / $raw['full_mark']) * 100, 2) : 0;
                    $gp = 0;
                    if (!empty($subject->grade_system_id)) {
                        $gradingRecords = $this->GradeRulesModel
                            ->where('grade_system_id', (int) $subject->grade_system_id)
                            ->where('status', 1)
                            ->orderBy('mark_from', 'DESC')
                            ->findAll();
                        foreach ($gradingRecords as $g) {
                            if ($subjectPercentage >= (float) $g->mark_from && $subjectPercentage <= (float) $g->mark_to) {
                                $gp = (float) ($g->grade_point ?? 0);
                                break;
                            }
                        }
                    }
                    $totalGradePoint += $gp;
                }

                $avgObtained = $count > 0 ? round($totalObtained / $count, 2) : 0;
                $avgPercentage = $count > 0 ? round($totalPercentage / $count, 2) : 0;
                $avgGradePoint = $count > 0 ? round($totalGradePoint / $count, 2) : 0;

                $total_included_mark[] = $avgObtained;
                $total_include_parcentage[] = $avgPercentage;
                $total_include_grade_point[] = $avgGradePoint;

                // Save each subject in the group individually
                foreach ($groupSubjects as $subject) {
                    $subjectId = $subject->id;
                    $raw = $studentRawMarks[$subjectId];
                    $subjectPercentage = $raw['full_mark'] > 0 ? round(($raw['obtained_mark'] / $raw['full_mark']) * 100, 2) : 0;

                    // Calculate grade for this individual subject
                    $grade = '';
                    $gradePoint = 0;
                    $letterGrade = '';
                    $isFail = 0;

                    if (!empty($subject->grade_system_id)) {
                        $gradingRecords = $this->GradeRulesModel
                            ->where('grade_system_id', (int) $subject->grade_system_id)
                            ->where('status', 1)
                            ->orderBy('mark_from', 'DESC')
                            ->findAll();
                        foreach ($gradingRecords as $g) {
                            if ($subjectPercentage >= (float) $g->mark_from && $subjectPercentage <= (float) $g->mark_to) {
                                $grade = $g->title;
                                $gradePoint = (float) ($g->grade_point ?? 0);
                                $letterGrade = $g->title;
                                break;
                            }
                        }
                    }

                    if ($raw['is_absent'] || $gradePoint == 0) {
                        $isFail = 1;
                    }

                    // average_mark and average_gp
                    $average_mark = $totalObtained / $count;
                    $average_gp = $combinedGradePointSums[$groupName] / $count;

                    // Get average grade based on average percentage
                    $average_grade = '';
                    if (!empty($subject->grade_system_id)) {
                        $gradingRecords = $this->GradeRulesModel
                            ->where('grade_system_id', (int) $subject->grade_system_id)
                            ->where('status', 1)
                            ->orderBy('mark_from', 'DESC')
                            ->findAll();
                        foreach ($gradingRecords as $g) {
                            if ($avgPercentage >= (float) $g->mark_from && $avgPercentage <= (float) $g->mark_to) {
                                $average_grade = $g->title;
                                break;
                            }
                        }
                    }

                    $subjectResultData = [
                        'school_id'       => $school_id,
                        'school_owner_uid'=> $user_id,
                        'exam_id'         => $exam_id,
                        'session_id'      => $year_id ?: $enrollment->session_id,
                        'class_id'        => $class_id,
                        'section_id'      => $section_id ?: $enrollment->section_id,
                        'student_id'      => $enrollment->student_id,
                        'enrollment_id'   => $enrollment->id,
                        'subject_id'      => $subjectId,
                        'full_mark'       => $raw['full_mark'],
                        'obtained_mark'   => $raw['obtained_mark'],
                        'exclude_mark'    => 0,
                        'exclude_grade_point' => 0,
                        'include_mark'    => $raw['obtained_mark'],
                        'include_grade_point' => $gradePoint,
                        'percentage'      => $subjectPercentage,
                        'grade_point'     => $gradePoint,
                        'grade'           => $grade,
                        'letter_grade'    => $letterGrade,
                        'highest_mark'    => 0, // Will be recalculated later
                        'is_fail'         => $isFail,
                        'remarks'         => $raw['remarks'],
                        'optional'        => 0,
                        'combine_group'   => $groupName,
                        'combined_mark'   => $totalObtained,
                        'combined_gp'     => $combinedGradePointSums[$groupName] ?? 0,
                        'combined_percentage' => $avgPercentage,
                        'average_mark'    => $average_mark,
                        'average_gp'      => $average_gp,
                        'average_grade'   => $average_grade
                    ];

                    // Upsert
                    $existingSubjectResult = $this->SubjectResultModel
                        ->where('school_id', $school_id)
                        ->where('exam_id', $exam_id)
                        ->where('class_id', $class_id)
                        ->where('subject_id', $subjectId)
                        ->where('student_id', $enrollment->student_id)
                        ->first();

                    if ($existingSubjectResult) {
                        $this->SubjectResultModel->update($existingSubjectResult->id, $subjectResultData);
                        $subjectResultData['id'] = $existingSubjectResult->id;
                    } else {
                        $insertId = $this->SubjectResultModel->insert($subjectResultData);
                        $subjectResultData['id'] = $insertId;
                    }

                    $allSubjectResults[] = $subjectResultData;
                    $processed++;
                }
            }

            // ---------------------------------------------------------------
            // 2. COMPULSORY SUBJECTS (non-combined, non-optional)
            // ---------------------------------------------------------------
            foreach (($subjectCategories['compulsory_subjects'] ?? []) as $subject) {
                $subjectId = $subject->id;
                $raw = $studentRawMarks[$subjectId];

                // Skip subjects where mark_calculation is disabled (0 = No)
                if (isset($subject->mark_calculation) && $subject->mark_calculation == 0) {
                    continue;
                }

                $fullMark     = $raw['full_mark'];
                $obtainedMark = $raw['obtained_mark'];
                $isAbsent     = $raw['is_absent'];
                $remarks      = $raw['remarks'];

                $excludeMark        = 0;
                $excludeGradePoint  = 0;
                $isExcludeEnabled   = !empty($subject->enabled_exclude_mark);
                $include_grade_point = 0;
                $exclude_percentage  = 0;

                // ---- Handle enabled_exclude_mark ----
                $include_mark = $obtainedMark;
                if ($isExcludeEnabled) {
                    $excludeMark = (float) ($subject->exclude_mark ?? 0);
                    $excludeGradePoint = (float) ($subject->exclude_grade_point ?? 0);
                    $exclude_percentage = (float) ($subject->exclude_percentage ?? 0);
                    $include_mark = max(0, $obtainedMark - $excludeMark);
                }

                $subjectPercentage = $fullMark > 0 ? round(($obtainedMark / $fullMark) * 100, 2) : 0;

                // Calculate grade
                $grade      = '';
                $gradePoint = 0;
                $letterGrade = '';
                $isFail     = 0;

                if (!empty($subject->grade_system_id)) {
                    $gradingRecords = $this->GradeRulesModel
                        ->where('grade_system_id', (int) $subject->grade_system_id)
                        ->where('status', 1)
                        ->orderBy('mark_from', 'DESC')
                        ->findAll();

                    foreach ($gradingRecords as $g) {
                        if ($subjectPercentage >= (float) $g->mark_from && $subjectPercentage <= (float) $g->mark_to) {
                            $grade       = $g->title;
                            $gradePoint  = (float) ($g->grade_point ?? 0);
                            $letterGrade = $g->title;
                            break;
                        }
                    }
                }

                // If exclude is enabled and exclude_grade_point is set, use it as the grade point
                if ($isExcludeEnabled && $excludeGradePoint < $gradePoint) {
                    $include_grade_point = $gradePoint - $excludeGradePoint;
                } else {
                    $include_grade_point = $gradePoint;
                }

                if ($isAbsent || $gradePoint == 0) {
                    $isFail = 1;
                }

                // If enabled exclude_percentage
                if ($isExcludeEnabled) {
                    $subjectPercentage = $subjectPercentage - $exclude_percentage;
                    $obtainedMark = $include_mark;
                }

                $total_included_mark[] = $include_mark;
                $total_include_parcentage[] = $subjectPercentage;
                $total_include_grade_point[] = $include_grade_point;

                $subjectResultData = [
                    'school_id'       => $school_id,
                    'school_owner_uid'=> $user_id,
                    'exam_id'         => $exam_id,
                    'session_id'      => $year_id ?: $enrollment->session_id,
                    'class_id'        => $class_id,
                    'section_id'      => $section_id ?: $enrollment->section_id,
                    'student_id'      => $enrollment->student_id,
                    'enrollment_id'   => $enrollment->id,
                    'subject_id'      => $subjectId,
                    'full_mark'       => $fullMark,
                    'obtained_mark'   => $obtainedMark,
                    'exclude_mark'    => $excludeMark,
                    'exclude_grade_point' => $excludeGradePoint,
                    'include_mark'    => $include_mark,
                    'include_grade_point' => $include_grade_point,
                    'percentage'      => $subjectPercentage,
                    'grade_point'     => $gradePoint,
                    'grade'           => $grade,
                    'letter_grade'    => $letterGrade,
                    'highest_mark'    => 0, // Will be recalculated later
                    'is_fail'         => $isFail,
                    'remarks'         => $remarks,
                    'optional'        => 0,
                    'combine_group'   => '',
                    'combined_mark'   => 0,
                    'combined_gp'     => 0,
                    'combined_percentage' => 0,
                ];

                // Upsert
                $existingSubjectResult = $this->SubjectResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subjectId)
                    ->where('student_id', $enrollment->student_id)
                    ->first();

                if ($existingSubjectResult) {
                    $this->SubjectResultModel->update($existingSubjectResult->id, $subjectResultData);
                    $subjectResultData['id'] = $existingSubjectResult->id;
                } else {
                    $insertId = $this->SubjectResultModel->insert($subjectResultData);
                    $subjectResultData['id'] = $insertId;
                }

                $allSubjectResults[] = $subjectResultData;
                $processed++;
            }

            // ---------------------------------------------------------------
            // 3. OPTIONAL SUBJECTS
            // ---------------------------------------------------------------
            foreach (($subjectCategories['optional_subjects'] ?? []) as $subject) {
                $hasOptional = true;
                $subjectId = $subject->id;
                $raw = $studentRawMarks[$subjectId];

                // Skip subjects where mark_calculation is disabled (0 = No)
                if (isset($subject->mark_calculation) && $subject->mark_calculation == 0) {
                    continue;
                }

                $fullMark     = $raw['full_mark'];
                $obtainedMark = $raw['obtained_mark'];
                $isAbsent     = $raw['is_absent'];
                $remarks      = $raw['remarks'];

                $excludeMark        = 0;
                $excludeGradePoint  = 0;
                $isExcludeEnabled   = !empty($subject->enabled_exclude_mark);
                $include_grade_point = 0;
                $exclude_percentage  = 0;

                // ---- Handle enabled_exclude_mark ----
                $include_mark = $obtainedMark;
                if ($isExcludeEnabled) {
                    $excludeMark = (float) ($subject->exclude_mark ?? 0);
                    $excludeGradePoint = (float) ($subject->exclude_grade_point ?? 0);
                    $exclude_percentage = (float) ($subject->exclude_percentage ?? 0);
                    $include_mark = max(0, $obtainedMark - $excludeMark);
                }

                $subjectPercentage = $fullMark > 0 ? round(($obtainedMark / $fullMark) * 100, 2) : 0;

                // Calculate grade
                $grade      = '';
                $gradePoint = 0;
                $letterGrade = '';
                $isFail     = 0;

                if (!empty($subject->grade_system_id)) {
                    $gradingRecords = $this->GradeRulesModel
                        ->where('grade_system_id', (int) $subject->grade_system_id)
                        ->where('status', 1)
                        ->orderBy('mark_from', 'DESC')
                        ->findAll();

                    foreach ($gradingRecords as $g) {
                        if ($subjectPercentage >= (float) $g->mark_from && $subjectPercentage <= (float) $g->mark_to) {
                            $grade       = $g->title;
                            $gradePoint  = (float) ($g->grade_point ?? 0);
                            $letterGrade = $g->title;
                            break;
                        }
                    }
                }

                // For optional subjects: use include_grade_point (after exclude deduction)
                if ($isExcludeEnabled && $excludeGradePoint < $gradePoint) {
                    $include_grade_point = $gradePoint - $excludeGradePoint;
                } else {
                    $include_grade_point = $gradePoint;
                }

                if ($isAbsent || $gradePoint == 0) {
                    $isFail = 1;
                }

                // If enabled exclude_percentage
                if ($isExcludeEnabled) {
                    $subjectPercentage = $subjectPercentage - $exclude_percentage;
                    //$obtainedMark = $include_mark;
                }

                // Optional subject bonus: if GP > 2, bonus = GP - 2, else bonus = 0
                if ($include_grade_point > 2) {
                    $optionalBonusGP += ($include_grade_point - 2);
                }

                $total_included_mark[] = $include_mark;
                $total_include_parcentage[] = $subjectPercentage;
                $total_include_grade_point[] = $include_grade_point;

                $subjectResultData = [
                    'school_id'       => $school_id,
                    'school_owner_uid'=> $user_id,
                    'exam_id'         => $exam_id,
                    'session_id'      => $year_id ?: $enrollment->session_id,
                    'class_id'        => $class_id,
                    'section_id'      => $section_id ?: $enrollment->section_id,
                    'student_id'      => $enrollment->student_id,
                    'enrollment_id'   => $enrollment->id,
                    'subject_id'      => $subjectId,
                    'full_mark'       => $fullMark,
                    'obtained_mark'   => $obtainedMark,
                    'exclude_mark'    => $excludeMark,
                    'exclude_grade_point' => $excludeGradePoint,
                    'include_mark'    => $include_mark,
                    'include_grade_point' => $include_grade_point,
                    'percentage'      => $subjectPercentage,
                    'grade_point'     => $gradePoint,
                    'grade'           => $grade,
                    'letter_grade'    => $letterGrade,
                    'highest_mark'    => 0, // Will be recalculated later
                    'is_fail'         => $isFail,
                    'remarks'         => $remarks,
                    'optional'        => 1,
                    'combine_group'   => '',
                    'combined_mark'   => 0,
                    'combined_gp'     => 0,
                    'combined_percentage' => 0,
                ];

                // Upsert
                $existingSubjectResult = $this->SubjectResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subjectId)
                    ->where('student_id', $enrollment->student_id)
                    ->first();

                if ($existingSubjectResult) {
                    $this->SubjectResultModel->update($existingSubjectResult->id, $subjectResultData);
                    $subjectResultData['id'] = $existingSubjectResult->id;
                } else {
                    $insertId = $this->SubjectResultModel->insert($subjectResultData);
                    $subjectResultData['id'] = $insertId;
                }

                $allSubjectResults[] = $subjectResultData;
                $processed++;
            }

            // Merge this student's combined grade point sums into the main array
            if (!empty($combinedGradePointSums)) {
                foreach ($combinedGradePointSums as $groupName => $sum) {
                    if (!isset($allCombinedGradePointSums[$groupName])) {
                        $allCombinedGradePointSums[$groupName] = $sum;
                    }
                }
            }
        }

        // Do not report a successful generation when every subject was skipped.
        // This commonly happens when Mark Calculation is disabled on all subjects.
        if ($processed === 0 || empty($allSubjectResults)) {
            $this->logMessage('warning', 'generateSubjectResults stopped: no calculable subject results were produced');

            return [
                'status'  => false,
                'message' => 'কোনো বিষয় থেকে ফলাফল তৈরি হয়নি। বিষয়গুলোর নম্বর গণনা চালু আছে কিনা দেখুন।',
            ];
        }

        // Recalculate highest marks for each subject after all subject results are saved
        $this->recalculateHighestMarks($school_id, $exam_id, $class_id, $section_id, $allSubjects);

        // DEBUG: Log final results
        $this->logMessage('info', 'generateSubjectResults completed: processed=' . $processed . ', total_results=' . count($allSubjectResults) . ', combined_groups=' . count($allCombinedGradePointSums));

        return [
            'status'    => true,
            'processed' => $processed,
            'data'      => $allSubjectResults,
            'combined_grade_point_sums' => $allCombinedGradePointSums,
        ];
    }

    /**
     * Recalculate highest marks per subject across all students
     */
    private function recalculateHighestMarks(int $school_id, int $exam_id, int $class_id, int $section_id, array $subjects): void
    {
        foreach ($subjects as $subject) {
            $highestRow = $this->SubjectResultModel
                ->select('MAX(obtained_mark) as highest')
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('subject_id', $subject->id)
                ->get()
                ->getRow();

            $highest = $highestRow ? (float) $highestRow->highest : 0;

            if ($highest > 0) {
                $this->SubjectResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subject->id)
                    ->set(['highest_mark' => $highest])
                    ->update();
            }
        }
    }

    // ===================================================================
    // STEP 3: GENERATE EXAM RESULTS
    // ===================================================================
    private function generateExamResults(int $school_id, int $exam_id, int $class_id, int $section_id, int $year_id, int $user_id, array $subjectResults, array $combinedGradePointSums = []): array
    {
        // Group subject results by student
        $studentResults = [];

        foreach ($subjectResults as $sr) {
            $studentId = $sr['student_id'];
            if (!isset($studentResults[$studentId])) {
                $studentResults[$studentId] = [
                    'subjects' => [],
                    'enrollment_id' => $sr['enrollment_id'],
                    'section_id' => $sr['section_id'],
                    'session_id' => $sr['session_id'],
                ];
            }
            $studentResults[$studentId]['subjects'][] = $sr;
        }

        $processed = 0;

        foreach ($studentResults as $studentId => $data) {
            // ===================================================================
            // Categorize subjects: Compulsory, Combined (grouped), Optional
            // ===================================================================
            $compulsorySubjects = [];
            $combinedGroups = [];
            $optionalSubjects = [];
            $countedCombinedGroups = [];
            $totalFailed = 0;
            $totalPassed = 0;

            foreach ($data['subjects'] as $sr) {
                $isOptional = !empty($sr['optional']);
                $combine_group = $sr['combine_group'] ?? '';

                if ($isOptional) {
                    $optionalSubjects[] = $sr;
                } elseif (!empty($combine_group)) {
                    if (!isset($combinedGroups[$combine_group])) {
                        $combinedGroups[$combine_group] = [];
                    }
                    $combinedGroups[$combine_group][] = $sr;
                } else {
                    $compulsorySubjects[] = $sr;
                }
            }

            // ===================================================================
            // Calculate Total Subjects, Full Marks, Obtained Marks
            // -------------------------------------------------------------------
            // Rules:
            //   - Compulsory: Count each subject normally
            //   - Combined:   Each combine_group counts as ONE subject
            //                 Use full_mark/obtained_mark from reference subject
            //   - Optional:   NOT counted in total_subjects, total_full, total_obtained
            // ===================================================================
            $totalSubjects  = 0;
            $totalFull      = 0;
            $totalObtained  = 0;

            // Compulsory: normal counting
            foreach ($compulsorySubjects as $sr) {
                $totalSubjects++;
                $totalFull += $sr['full_mark'];
                $totalObtained += $sr['include_mark'];
                if ($sr['is_fail']) {
                    $totalFailed++;
                } else {
                    $totalPassed++;
                }
            }

            // Combined: each group = 1 subject
            //   - total_full     = sum of full marks of all subjects in the group
            //   - total_obtained = combined_mark (sum of obtained marks stored on subject result)

            $combine_totalObtained = 0;
            $groupFullMark = 0;
            foreach ($combinedGroups as $groupName => $groupSubjects) {
                $totalSubjects++;
                $firstSr = $groupSubjects[0];
                
                // Sum full marks across all subjects in the combined group
                //$groupFullMark = 0;
                foreach ($groupSubjects as $gs) {
                    //$groupFullMark += $gs['full_mark'];
                }
                //$totalFull = $groupFullMark;
                
                // Combined obtained mark (already summed and stored as combined_mark)
                $groupFullMark = $firstSr['full_mark'];
                $combine_totalObtained = ($firstSr['combined_mark']/2) ?? 0;

                // Check pass/fail: any subject in group failed = group failed
                $groupFailed = false;
                foreach ($groupSubjects as $gs) {
                    if ($gs['is_fail']) {
                        $groupFailed = true;
                        break;
                    }
                }
                if ($groupFailed) {
                    $totalFailed++;
                } else {
                    $totalPassed++;
                }
            }

            $totalFull += $groupFullMark;
            $totalObtained += $combine_totalObtained;
            


            // Optional: NOT counted in totals at all (marks and subjects excluded)
            // Optional subject bonus GP
            foreach ($optionalSubjects as $sr) {
                $totalObtained += $sr['include_mark'];
                
            }

            $overallPercentage = $totalFull > 0 ? round(($totalObtained / $totalFull) * 100, 2) : 0;

            // ===================================================================
            // GPA Calculation (matching reference generateSubjectTable pattern)
            // -------------------------------------------------------------------
            // Reference logic:
            //   $totalGP += $gp;               // Sum of compulsory subject GPs
            //   $totalGP += combinedSum;       // Combined group GP sum (once per group)
            //   $optionalBonusGP += ($gp - 2); // Optional bonus (if GP > 2)
            //   $finalGPA = ($totalGP + $optionalBonusGP) / $totalCompulsorySubjects
            //
            // $totalCompulsorySubjects = compulsory count + combined group count
            // ===================================================================
            $totalGP = 0;
            $optionalBonusGP = 0;
            $gpaCountSubjects = 0; // Counts: compulsory + combined groups (NOT optional)

            // Compulsory subject grade points
            foreach ($compulsorySubjects as $sr) {
                //if (!$sr['is_fail']) {
                    $gp = $sr['include_grade_point'] ?? $sr['grade_point'] ?? 0;
                    $totalGP += $gp;
               // }
                $gpaCountSubjects++;
            }

            // Combined group grade points (use combined GP sum once per group)
            $combined_gp = 0;
            foreach ($combinedGroups as $groupName => $groupSubjects) {
               
                $gpSumInGroup = 0;
                foreach ($groupSubjects as $gs) {
                    if ($gs['is_fail']) {
                        $groupFailed = true;
                        break;
                    }
                    $gpSumInGroup = ($gs['combined_gp'] / 2) ?? 0;
                    
                }

                $combined_gp += $gpSumInGroup;
                
            }

            
            $gpaCountSubjects+= 1;
            $totalGP += $combined_gp;

            // Optional subject bonus GP
            foreach ($optionalSubjects as $sr) {
                if (!$sr['is_fail']) {
                    $optionalBonusGP += $sr['include_grade_point'];
                }
            }

            

            // Final GPA = (total GP + optional bonus GP) / total GPA subjects
            $gpa = $gpaCountSubjects > 0 
                ? round(($totalGP + $optionalBonusGP) / $gpaCountSubjects, 2) 
                : 0;

            // Result status: FAIL if ANY compulsory or combined subject failed
            $resultStatus = ($totalFailed > 0) ? 'FAIL' : 'PASS';

            // Determine overall grade from percentage
            $overallGrade      = '';
            $overallLetterGrade = '';
            $overallGradePoint = 0;

            

            if ($gpa > 0 && !empty($compulsorySubjects)) {
                // Use first compulsory subject's grade system
                $firstCompulsory = $compulsorySubjects[0];
                $subjectInfo = $this->SubjectModel->find($firstCompulsory['subject_id']);
                if ($subjectInfo && $subjectInfo->grade_system_id) {
                    $gradingRecords = $this->GradeRulesModel
                        ->where('grade_system_id', (int) $subjectInfo->grade_system_id)
                        ->where('status', 1)
                        ->orderBy('mark_from', 'DESC')
                        ->findAll();

                    foreach ($gradingRecords as $g) {
                        if ($overallPercentage >= (float) $g->mark_from && $overallPercentage <= (float) $g->mark_to) {
                            $overallGrade       = $g->title;
                            $overallLetterGrade = $g->title;
                            $overallGradePoint  = (float) ($g->grade_point ?? 0);
                            break;
                        }
                    }
                }
            }

            // Total grade points
            $totalGradePoints = $totalGP + $optionalBonusGP;

            $examResultData = [
                'school_id'        => $school_id,
                'school_owner_uid' => $user_id,
                'exam_id'          => $exam_id,
                'session_id'       => $year_id,
                'class_id'         => $class_id,
                'section_id'       => $data['section_id'] ?: $section_id,
                'student_id'       => $studentId,
                'enrollment_id'    => $data['enrollment_id'],
                'total_subjects'   => $totalSubjects,
                'passed_subjects'  => $totalPassed,
                'failed_subjects'  => $totalFailed,
                'total_marks'      => $totalFull,
                'obtained_marks'   => $totalObtained,
                'percentage'       => $overallPercentage,
                'gpa'              => $overallGradePoint,
                'total_grade_point' => $totalGradePoints,
                'grade'            => $overallGrade,
                'letter_grade'     => $overallLetterGrade,
                'result_status'    => $resultStatus,
                'attendance_percentage' => null,
                'principal_remarks'    => null,
                'teacher_remarks'      => null,
            ];

            $existingExamResult = $this->ExamResultModel
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('student_id', $studentId)
                ->first();

            if ($existingExamResult) {
                $this->ExamResultModel->update($existingExamResult->id, $examResultData);
            } else {
                // Token is generated for new records; existing records retain their token
                $examResultData['token'] = $this->generateToken();
                $this->ExamResultModel->insert($examResultData);
            }

            $processed++;
        }

        return [
            'status'    => true,
            'processed' => $processed,
        ];
    }

    // ===================================================================
    // STEP 4: GENERATE POSITIONS (Class Rank, Section Rank, Exam Rank)
    // ===================================================================
    private function generatePositions(int $school_id, int $exam_id, int $class_id, int $section_id, int $year_id): array
    {
        // Assign class rank (rank within the same class for this exam)
        $classResults = $this->ExamResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->orderBy('gpa', 'DESC')
            ->orderBy('obtained_marks', 'DESC')
            ->orderBy('percentage', 'DESC')
            ->findAll();

        $classRank = 0;
        $prevGpa = null;
        $prevMarks = null;

        foreach ($classResults as $idx => $result) {
            if ($prevGpa !== null && $prevGpa == $result->gpa && $prevMarks == $result->obtained_marks) {
                // Same rank for ties
            } else {
                $classRank = ($idx + 1);
            }

            $this->ExamResultModel
                ->where('id', $result->id)
                ->set(['class_rank' => $classRank])
                ->update();

            $prevGpa = $result->gpa;
            $prevMarks = $result->obtained_marks;
        }

        // Assign section rank (rank within the same section for this exam)
        $activeSectionIds = [$section_id];
        if (!$section_id) {
            // Get all distinct section_ids for this exam/class
            $sectionResults = $this->ExamResultModel
                ->select('DISTINCT(section_id)')
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('section_id >', 0)
                ->findAll();
            foreach ($sectionResults as $sr) {
                $activeSectionIds[] = $sr->section_id;
            }
        }

        foreach ($activeSectionIds as $secId) {
            $sectionResults = $this->ExamResultModel
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('section_id', $secId)
                ->orderBy('gpa', 'DESC')
                ->orderBy('obtained_marks', 'DESC')
                ->orderBy('percentage', 'DESC')
                ->findAll();

            $sectionRank = 0;
            $prevGpa = null;
            $prevMarks = null;

            foreach ($sectionResults as $idx => $result) {
                if ($prevGpa !== null && $prevGpa == $result->gpa && $prevMarks == $result->obtained_marks) {
                    // Same rank for ties
                } else {
                    $sectionRank = ($idx + 1);
                }

                $this->ExamResultModel
                    ->where('id', $result->id)
                    ->set(['section_rank' => $sectionRank])
                    ->update();

                $prevGpa = $result->gpa;
                $prevMarks = $result->obtained_marks;
            }
        }

        // Assign exam rank (overall across all classes/sections for this exam)
        $examResults = $this->ExamResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->orderBy('gpa', 'DESC')
            ->orderBy('obtained_marks', 'DESC')
            ->orderBy('percentage', 'DESC')
            ->findAll();

        $examRank = 0;
        $prevGpa = null;
        $prevMarks = null;

        foreach ($examResults as $idx => $result) {
            if ($prevGpa !== null && $prevGpa == $result->gpa && $prevMarks == $result->obtained_marks) {
                // Same rank for ties
            } else {
                $examRank = ($idx + 1);
            }

            $this->ExamResultModel
                ->where('id', $result->id)
                ->set(['exam_rank' => $examRank])
                ->update();

            $prevGpa = $result->gpa;
            $prevMarks = $result->obtained_marks;
        }

        return ['status' => true];
    }

    

    // ===================================================================
    // STEP 6: AUTO-PUBLISH (Create publish record, but don't mark as published)
    // ===================================================================
    private function autoPublish(int $school_id, int $exam_id, int $class_id, int $section_id, int $user_id): array
    {
        // Check if publish record already exists
        $existing = $this->ResultPublishModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);

        if ($section_id) {
            $existing->where('section_id', $section_id);
        }

        $publishRecord = $existing->first();

        $publishData = [
            'school_id'       => $school_id,
            'school_owner_uid'=> $user_id,
            'exam_id'         => $exam_id,
            'class_id'        => $class_id,
            'section_id'      => $section_id ?: 0,
            'is_published'    => 0, // Not auto-published; manual publish required
            'published_by'    => 0,
            'published_at'    => null,
        ];

        if ($publishRecord) {
            // Only update if not already published
            if (!$publishRecord->is_published) {
                $this->ResultPublishModel->update($publishRecord->id, $publishData);
            }
        } else {
            $this->ResultPublishModel->insert($publishData);
        }

        return ['status' => true];
    }

    // ===================================================================
    // HELPER: Delete existing results for recalculation
    // ===================================================================
    private function deleteExistingResults(int $school_id, int $exam_id, int $class_id, int $section_id, int $year_id): void
    {
        // Delete subject results
        $subjectDelete = $this->SubjectResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $subjectDelete->where('section_id', $section_id);
        }
        $subjectDelete->delete();

        // Delete exam results
        $examDelete = $this->ExamResultModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $examDelete->where('section_id', $section_id);
        }
        $examDelete->delete();

        // Delete final results
        $finalDelete = $this->FinalResultModel
            ->where('session_id', $year_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $finalDelete->where('section_id', $section_id);
        }
        $finalDelete->delete();

        // Delete publish records
        $publishDelete = $this->ResultPublishModel
            ->where('school_id', $school_id)
            ->where('exam_id', $exam_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $publishDelete->where('section_id', $section_id);
        }
        $publishDelete->delete();
    }

    // generateToken()
    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}

<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentModel;
use App\Modules\examination\Models\StudentSubjectModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\FinalResultModel;
use App\Modules\examination\Models\FinalResultSubjectModel;
use App\Modules\examination\Models\ResultPublishModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\GradeRuleModel;
use App\Modules\examination\Models\GradeSystemModel;
use App\Modules\examination\Models\ExamModel;

class AggregateController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentModel $StudentModel;
    protected StudentSubjectModel $StudentSubjectModel;
    protected SubjectResultModel $SubjectResultModel;
    protected ExamResultModel $ExamResultModel;
    protected FinalResultModel $FinalResultModel;
    protected FinalResultSubjectModel $FinalResultSubjectModel;
    protected ResultPublishModel $ResultPublishModel;
    protected SubjectModel $SubjectModel;
    protected GradeRuleModel $GradeRulesModel;
    protected GradeSystemModel $GradeSystemModel;
    protected ExamModel $ExamModel;
    protected $db;

    public function __construct()
    {
        // add helpers

        $this->SchoolModel              = new SchoolModel();
        $this->YearModel                = new AcademicsYearModel();
        $this->ClassModel               = new AcademicsClassesModel();
        $this->SectionModel             = new AcademicsSectionModel();
        $this->EnrollmentModel          = new StudentEnrollmentModel();
        $this->StudentModel             = new StudentModel();
        $this->StudentSubjectModel      = new StudentSubjectModel();
        $this->SubjectResultModel       = new SubjectResultModel();
        $this->ExamResultModel          = new ExamResultModel();
        $this->FinalResultModel         = new FinalResultModel();
        $this->FinalResultSubjectModel  = new FinalResultSubjectModel();
        $this->ResultPublishModel       = new ResultPublishModel();
        $this->SubjectModel             = new SubjectModel();
        $this->GradeRulesModel          = new GradeRuleModel();
        $this->GradeSystemModel         = new GradeSystemModel();
        $this->ExamModel                = new ExamModel();
        $this->db = \Config\Database::connect();

        // Load helpers
        helper('Modules\examination\Helpers\results_helper');
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

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    protected function logMessage(string $type, string $message): void
    {
        log_message($type, '[Aggregate] ' . $message);
    }

    // ===================================================================
    // AJAX: Get academic data by school
    // ===================================================================
    public function getAcademicDataBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/aggregate');
        }

        $school_id = (int) $this->request->getPost('school_id');
        if (!$school_id) {
            return $this->jsonResponse(['status' => false]);
        }

        $yearList       = $this->getActiveOptions($school_id, 'YearModel');
        $classList      = $this->getActiveOptions($school_id, 'ClassModel');
        $sectionList    = $this->getActiveOptions($school_id, 'SectionModel');

        return $this->jsonResponse([
            'status'       => true,
            'year_list'       => $yearList,
            'class_list'      => $classList,
            'section_list'    => $sectionList,
            'academic_section_enabled' => $this->isSchoolSettingEnabled($school_id, 'academic_section_enabled'),
        ]);
    }

    // ===================================================================
    // INDEX: Display the aggregate result form
    // ===================================================================
    public function index()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Aggregate Results',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\aggregate\index', $data)
            . view('footer', $footer_data);
    }

    // ===================================================================
    // GENERATE AGGREGATE: Main orchestrator
    // ===================================================================
    public function generate()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks');
        if ($subscription_check) {
            return $this->jsonResponse(['status' => false, 'message' => $subscription_check['html']]);
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');
        $year_id    = (int) $this->request->getPost('year_id');

        if (!$school_id || !$class_id || !$year_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields.']);
        }

        $user_id = $this->getUserId();

        try {
            $this->db->transBegin();

            // ===================================================================
            // STEP 1: VALIDATE - Get exams with weight_percentage for this class/session
            // ===================================================================
            $exams = $this->ExamModel
                ->where('school_id', $school_id)
                ->where('year_id', $year_id)
                ->where('is_aggregate_result', 1)
                ->where('status', 1)
                ->where('weight_percentage >', 0)
                ->orderBy('id', 'ASC')
                ->findAll();

            if (empty($exams)) {
                $this->db->transRollback();
                return $this->jsonResponse([
                    'status' => false,
                    'message' => 'No exams found with weight_percentage > 0 for this class/session. Please set weight percentages in exam setup.',
                ]);
            }

            // Validate total weight = 100%
            $totalWeight = 0;
            foreach ($exams as $exam) {
                $totalWeight += (float) $exam->weight_percentage;
            }

            if (abs($totalWeight - 100) > 0.01) {
                $this->db->transRollback();
                return $this->jsonResponse([
                    'status' => false,
                    'message' => "Total weight percentage of all exams must equal 100%. Current total: {$totalWeight}%",
                ]);
            }

            // ===================================================================
            // STEP 2: Get enrolled students
            // ===================================================================
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

            if (empty($enrollments)) {
                $this->db->transRollback();
                return $this->jsonResponse(['status' => false, 'message' => 'No students found.']);
            }

            // ===================================================================
            // STEP 3: Get subjects for this class
            // ===================================================================
            // Get all subjects for this school
            $allSubjects = $this->SubjectModel
                ->where('school_id', $school_id)
                ->where('status', 1)
                ->findAll();

            if (empty($allSubjects)) {
                $this->db->transRollback();
                return $this->jsonResponse(['status' => false, 'message' => 'No subjects found for this class.']);
            }

            // ===================================================================
            // STEP 4: Delete existing aggregate results for this class/session
            // ===================================================================
            //$this->deleteExistingAggregate($school_id, $class_id, $section_id, $year_id);

            // ===================================================================
            // STEP 5: For each student, calculate weighted aggregate per subject
            // ===================================================================
            $aggregateSubjectResults = [];
            $studentAggregates = [];

            foreach ($enrollments as $enrollment) {
                $studentId = $enrollment->student_id;

                $studentTotalAggregateMarks = 0;
                $studentTotalFullMarks = 0;
                $studentSubjectResults = [];
                $studentFailedSubjects = 0;
                $studentPassedSubjects = 0;
                $totalGradePoints = 0;
                $totalSubjectsCount = 0;

                // Get categorized subjects using the model method (same as generateSubjectResults)
                $student = $this->StudentModel->find($enrollment->student_id);
                $subjectCategories = $this->StudentSubjectModel->get_student_subject_categories($school_id, $enrollment->id, 0, $class_id, $year_id, $student);

                
                // Calculate for $subjectCategories['combined_subjects']
                if(isset($subjectCategories['combined_subjects']) && count($subjectCategories['combined_subjects']) > 0){
                    // First pass: collect all exam results per subject
                    $combined_subject_exam_results = [];
                    $combined_subjects = $subjectCategories['combined_subjects'];
                    foreach ($combined_subjects as $combined_subject) {
                        $combined_subjectId = $combined_subject->id;

                        // Skip subjects where mark_calculation is disabled (0 = No)
                        if (isset($combined_subject->mark_calculation) && $combined_subject->mark_calculation == 0) {
                            continue;
                        }
                        $combined_subject_exam_results[$combined_subjectId] = [
                            'subject' => $combined_subject,
                            'exam_data' => [],
                            'weighted_obtained' => 0,
                            'weighted_full' => 0,
                            'aggregate_percentage' => 0,
                            'grade_point' => 0,
                            'letter_grade' => '',
                            'is_fail' => 0
                        ];
                    

                        foreach ($exams as $exam) {
                            $combined_subject_weight = (float) $exam->weight_percentage / 100;

                            // Get subject result for this exam
                            $combined_subject_results = $this->SubjectResultModel
                                    ->where('school_id', $school_id)
                                    ->where('exam_id', $exam->id)
                                    ->where('class_id', $class_id)
                                    ->where('subject_id', $combined_subjectId)
                                    ->where('student_id', $studentId)
                                    ->first();
                        
                            if($combined_subject_results){
                                    $exam_combined_subject_obtained      = (float) $combined_subject_results->obtained_mark;
                                    $exam_combined_subject_full          = (float) $combined_subject_results->full_mark;
                                    $exam_combined_subject_average_mark  = $combined_subject_results->average_mark;
                                   
                                    $combined_subject_exam_results[$combined_subjectId]['weighted_obtained'] += $exam_combined_subject_average_mark * $combined_subject_weight;
                                    $combined_subject_exam_results[$combined_subjectId]['weighted_full'] += $exam_combined_subject_full * $combined_subject_weight;
                                   
                                    // Exam data for this subject and exam
                                    $combined_subject_exam_results[$combined_subjectId]['exam_data'][$exam->id] = [
                                        'exam'      => $exam->title,
                                        'obtained'  => $exam_combined_subject_obtained,
                                        'average_mark' => $exam_combined_subject_average_mark,
                                        'full'      => $exam_combined_subject_full,
                                        'grade_point' => (float) $combined_subject_results->grade_point,
                                        'letter_grade'  => $combined_subject_results->grade,
                                        'is_fail'   => $combined_subject_results->is_fail,
                                        'include_grade_point' => $combined_subject_results->include_grade_point
                                    ];
                            }else{
                                    // Subject result not found, set default values
                                    $combined_subject_exam_results[$combined_subjectId]['weighted_obtained'] += 0;
                                    $combined_subject_exam_results[$combined_subjectId]['weighted_full'] += 0;
                                    $combined_subject_exam_results[$combined_subjectId]['exam_data'][$exam->id] = [
                                        'exam'      => $exam->title,
                                        'obtained'  => 0,
                                        'full'      => 0,
                                        'grade_point' => 0,
                                        'letter_grade'  => '',
                                        'is_fail'   => 1,
                                    ];
                            }

                        } // End foreach $exams

                        $combined_weighted_obtained = (float) $combined_subject_exam_results[$combined_subjectId]['weighted_obtained'];
                        $combined_weighted_full = (float) $combined_subject_exam_results[$combined_subjectId]['weighted_full'];

                        // Add to student totals (ONCE per subject, after all exams)
                        $studentTotalAggregateMarks = $combined_weighted_obtained;
                        $studentTotalFullMarks = $combined_weighted_full;

                        $combined_aggregate_percentage = $combined_weighted_full > 0
                            ? round(($combined_weighted_obtained / $combined_weighted_full) * 100, 2)
                            : 0;

                        $combined_subject_exam_results[$combined_subjectId]['aggregate_percentage'] = $combined_aggregate_percentage;

                        $grade_data  = get_grade_data($combined_subject->grade_system_id, $combined_aggregate_percentage);
                        $combined_subject_grade_point  = $grade_data['grade_point'];
                        $combined_subject_grade_letter = $grade_data['letter_grade'];
                        $combined_subject_isfail      = $grade_data['is_fail'];

                        if ($combined_subject_grade_point == 0) {
                            $combined_subject_isfail = 1;
                        }

                        if ($combined_subject_isfail) {
                            $studentFailedSubjects = 1;
                        } else {
                            $studentPassedSubjects = 1;
                        }

                        $combined_subject_exam_results[$combined_subjectId]['grade_point']  = $combined_subject_grade_point;
                        $combined_subject_exam_results[$combined_subjectId]['letter_grade'] = $combined_subject_grade_letter;
                        $combined_subject_exam_results[$combined_subjectId]['is_fail']      = $combined_subject_isfail;

                        $totalGradePoints  = $combined_subject_grade_point;
                        $totalSubjectsCount = 1;


                        $studentSubjectResults[] = [
                            'subject_id'   => $combined_subject->id,
                            'subject_name' => $combined_subject->title,
                            'aggregate_obtained' => round($combined_weighted_obtained, 2),
                            'aggregate_full'     => round($combined_weighted_full, 2),
                            'percentage'   => $combined_aggregate_percentage,
                            'grade_point'  => $combined_subject_grade_point,
                            'letter_grade'  => $combined_subject_grade_letter,
                            'is_fail'      => $combined_subject_isfail,
                            //'exam_details' => $combined_subject_exam_results[$combined_subjectId]['exam_data'],
                        ];

                        // Save to final_result_subjects
                        $combined_subjectAggregateData = [
                            'school_id'             => $school_id,
                            'school_owner_uid'      => $user_id,
                            'student_uid'           => $studentId,
                            'enrollment_id'         => $enrollment->id,
                            'session_id'            => $year_id,
                            'class_id'              => $class_id,
                            'section_id'            => $section_id ?: $enrollment->section_id,
                            'subject_id'            => $combined_subjectId,
                            'aggregate_full_mark'   => round($combined_weighted_full, 2),
                            'aggregate_obtained_mark' => round($combined_weighted_obtained, 2),
                            'aggregate_percentage'  => $combined_aggregate_percentage,
                            'grade_point'           => $combined_subject_grade_point,
                            'letter_grade'          => $combined_subject_grade_letter,
                            'is_fail'               => $combined_subject_isfail,
                            'is_generated'          => 1,
                            'generated_at'          => date('Y-m-d H:i:s'),
                            'created_by'            => $user_id
                        ];

                        // existing record for this subject?
                        $combined_subjectExisting = $this->FinalResultSubjectModel
                            ->where('school_id', $school_id)
                            ->where('student_uid', $studentId)
                            ->where('session_id', $year_id)
                            ->where('class_id', $class_id)
                            ->where('subject_id', $combined_subjectId)
                            ->first();

                        // Save or update the record
                        if ($combined_subjectExisting) {
                            $combined_subjectAggregateData['updated_by'] = $user_id;
                            $this->FinalResultSubjectModel->update($combined_subjectExisting->id, $combined_subjectAggregateData);
                        } else {
                            $this->FinalResultSubjectModel->insert($combined_subjectAggregateData);
                        }

                    } // End foreach $combined_subjects

                } // End if isset($subjectCategories['combined_subjects'])

                // Calculate for $subjectCategories['compulsory_subjects']
                if(isset($subjectCategories['compulsory_subjects']) && count($subjectCategories['compulsory_subjects']) > 0){
                    // First pass: collect all exam results per subject
                    $compulsory_subject_exam_results = [];
                    $compulsory_subjects = $subjectCategories['compulsory_subjects'];
                    foreach ($compulsory_subjects as $compulsory_subject) {
                        // Skip subjects where mark_calculation is disabled (0 = No)
                        if (isset($compulsory_subject->mark_calculation) && $compulsory_subject->mark_calculation == 0) {
                            continue;
                        }
                        
                        $compulsory_subject_exam_results[$compulsory_subject->id] = [
                            'subject' => $compulsory_subject,
                            'exam_data' => [],
                            'weighted_obtained' => 0,
                            'weighted_full' => 0,
                            'aggregate_percentage' => 0,
                            'grade_point' => 0,
                            'letter_grade' => '',
                            'is_fail' => 0,
                        ];
                        
                        foreach ($exams as $exam) {
                            $compulsory_subject_weight = (float) $exam->weight_percentage / 100;

                            // Get subject result for this exam
                            $compulsory_subject_result = $this->SubjectResultModel
                                ->where('school_id', $school_id)
                                ->where('exam_id', $exam->id)
                                ->where('class_id', $class_id)
                                ->where('subject_id', $compulsory_subject->id)
                                ->where('student_id', $studentId)
                                ->first();

                            if ($compulsory_subject_result) {
                                $exam_compulsory_subject_obtained = (float) $compulsory_subject_result->obtained_mark;
                                $exam_compulsory_subject_full     = (float) $compulsory_subject_result->full_mark;

                                $compulsory_subject_exam_results[$compulsory_subject->id]['weighted_obtained'] += $exam_compulsory_subject_obtained * $compulsory_subject_weight;
                                $compulsory_subject_exam_results[$compulsory_subject->id]['weighted_full'] += $exam_compulsory_subject_full * $compulsory_subject_weight;
                                // Exam data for this subject and exam
                                $compulsory_subject_exam_results[$compulsory_subject->id]['exam_data'][$exam->id] = [
                                    'exam'      => $exam->title,
                                    'obtained'  => $exam_compulsory_subject_obtained,
                                    'full'      => $exam_compulsory_subject_full,
                                    'grade_point' => (float) $compulsory_subject_result->grade_point,
                                    'letter_grade'  => $compulsory_subject_result->grade,
                                    'is_fail'   => $compulsory_subject_result->is_fail,
                                    'include_grade_point' => $compulsory_subject_result->include_grade_point
                                ];
                            } else {
                                // Subject result not found, set default values
                                $compulsory_subject_exam_results[$compulsory_subject->id]['weighted_obtained'] += 0;
                                $compulsory_subject_exam_results[$compulsory_subject->id]['weighted_full'] += 0;
                                $compulsory_subject_exam_results[$compulsory_subject->id]['exam_data'][$exam->id] = [
                                    'exam'      => $exam->title,
                                    'obtained'  => 0,
                                    'full'      => 0,
                                    'grade_point' => 0,
                                    'letter_grade'  => '',
                                    'is_fail'   => 1,
                                ];
                            }
                        } // End foreach $exams


                        $subjectId = $compulsory_subject->id;

                        $weightedObtained = (float) $compulsory_subject_exam_results[$subjectId]['weighted_obtained'];

                        $weightedFull = (float) $compulsory_subject_exam_results[$subjectId]['weighted_full'];

                        // Add to student totals (ONCE per subject, after all exams)
                        $studentTotalAggregateMarks += $weightedObtained;
                        $studentTotalFullMarks += $weightedFull;

                        $aggregatePercentage = $weightedFull > 0
                            ? round(($weightedObtained / $weightedFull) * 100, 2)
                            : 0;

                        $compulsory_subject_exam_results[$subjectId]['aggregate_percentage'] = $aggregatePercentage;

                        $grade_data  = get_grade_data($compulsory_subject->grade_system_id, $aggregatePercentage);
                        $compulsory_subject_grade_point  = $grade_data['grade_point'];
                        $compulsory_subject_grade_letter = $grade_data['letter_grade'];
                        $compulsory_subject_isfail      = $grade_data['is_fail'];

                        if ($compulsory_subject_grade_point == 0) {
                            $compulsory_subject_isfail = 1;
                        }

                        if ($compulsory_subject_isfail) {
                            $studentFailedSubjects++;
                        } else {
                            $studentPassedSubjects++;
                        }

                        $compulsory_subject_exam_results[$subjectId]['grade_point']  = $compulsory_subject_grade_point;
                        $compulsory_subject_exam_results[$subjectId]['letter_grade'] = $compulsory_subject_grade_letter;
                        $compulsory_subject_exam_results[$subjectId]['is_fail']      = $compulsory_subject_isfail;

                        $totalGradePoints  += $compulsory_subject_grade_point;
                        $totalSubjectsCount += 1;


                        $studentSubjectResults[] = [
                            'subject_id'   => $compulsory_subject->id,
                            'subject_name' => $compulsory_subject->title,
                            'aggregate_obtained' => round($weightedObtained, 2),
                            'aggregate_full'     => round($weightedFull, 2),
                            'percentage'   => $aggregatePercentage,
                            'grade_point'  => $compulsory_subject_grade_point,
                            'letter_grade'  => $compulsory_subject_grade_letter,
                            'is_fail'      => $compulsory_subject_isfail,
                            //'exam_details' => $compulsory_subject_exam_results[$subjectId]['exam_data'],
                        ];

                        // Save to final_result_subjects
                        $compulsory_subjectAggregateData = [
                            'school_id'             => $school_id,
                            'school_owner_uid'      => $user_id,
                            'student_uid'           => $studentId,
                            'enrollment_id'         => $enrollment->id,
                            'session_id'            => $year_id,
                            'class_id'              => $class_id,
                            'section_id'            => $section_id ?: $enrollment->section_id,
                            'subject_id'            => $subjectId,
                            'aggregate_full_mark'   => round($weightedFull, 2),
                            'aggregate_obtained_mark' => round($weightedObtained, 2),
                            'aggregate_percentage'  => $aggregatePercentage,
                            'grade_point'           => $compulsory_subject_grade_point,
                            'letter_grade'          => $compulsory_subject_grade_letter,
                            'is_fail'               => $compulsory_subject_isfail,
                            'is_generated'          => 1,
                            'generated_at'          => date('Y-m-d H:i:s'),
                            'created_by'            => $user_id
                        ];

                        // existing record for this subject?
                        $compulsory_subjectExisting = $this->FinalResultSubjectModel
                            ->where('school_id', $school_id)
                            ->where('student_uid', $studentId)
                            ->where('session_id', $year_id)
                            ->where('class_id', $class_id)
                            ->where('subject_id', $subjectId)
                            ->first();

                        // Save or update the record
                        if ($compulsory_subjectExisting) {
                            $compulsory_subjectAggregateData['updated_by'] = $user_id;
                            $this->FinalResultSubjectModel->update($compulsory_subjectExisting->id, $compulsory_subjectAggregateData);
                        } else {
                            $this->FinalResultSubjectModel->insert($compulsory_subjectAggregateData);
                        }
                    } // End foreach $subjectCategories

                   
                } // End if isset($subjectCategories['compulsory_subjects'])


                // Calculate for $subjectCategories['optional_subjects']
                if(isset($subjectCategories['optional_subjects']) && count($subjectCategories['optional_subjects']) > 0){
                    // First pass: collect all exam results per subject
                    $optional_subject_exam_results = [];
                    $optional_subjects = $subjectCategories['optional_subjects'];
                    foreach ($optional_subjects as $optional_subject) {
                        // Skip subjects where mark_calculation is disabled (0 = No)
                        if (isset($optional_subject->mark_calculation) && $optional_subject->mark_calculation == 0) {
                            continue;
                        }
                        
                        $optional_subject_exam_results[$optional_subject->id] = [
                            'subject' => $optional_subject,
                            'exam_data' => [],
                            'weighted_obtained' => 0,
                            'include_mark' => 0,
                            'weighted_full' => 0,
                            'aggregate_percentage' => 0,
                            'grade_point' => 0,
                            'letter_grade' => '',
                            'is_fail' => 0,
                        ];
                        
                        foreach ($exams as $exam) {
                            $optional_subject_weight = (float) $exam->weight_percentage / 100;

                            // Get subject result for this exam
                            $optional_subject_result = $this->SubjectResultModel
                                ->where('school_id', $school_id)
                                ->where('exam_id', $exam->id)
                                ->where('class_id', $class_id)
                                ->where('subject_id', $optional_subject->id)
                                ->where('student_id', $studentId)
                                ->first();

                            if ($optional_subject_result) {
                                $exam_optional_subject_obtained = (float) $optional_subject_result->obtained_mark;
                                $exam_optional_subject_full     = (float) $optional_subject_result->full_mark;
                                
                                // include_mark
                                $include_mark = (float) $optional_subject_result->include_mark;

                                $optional_subject_exam_results[$optional_subject->id]['weighted_obtained'] += $exam_optional_subject_obtained * $optional_subject_weight;
                                $optional_subject_exam_results[$optional_subject->id]['weighted_full'] += $exam_optional_subject_full * $optional_subject_weight;
                                
                                $optional_subject_exam_results[$optional_subject->id]['include_mark'] += $include_mark * $optional_subject_weight;
                                
                                // Exam data for this subject and exam
                                $optional_subject_exam_results[$optional_subject->id]['exam_data'][$exam->id] = [
                                    'exam'      => $exam->title,
                                    'obtained'  => $exam_optional_subject_obtained,
                                    'full'      => $exam_optional_subject_full,
                                    'grade_point' => (float) $optional_subject_result->grade_point,
                                    'letter_grade'     => $optional_subject_result->grade,
                                    'is_fail'   => $optional_subject_result->is_fail,
                                    'include_mark' => $optional_subject_result->include_mark,
                                    'include_grade_point' => $optional_subject_result->include_grade_point
                                ];
                            } else {
                                // Subject result not found, set default values
                                $optional_subject_exam_results[$optional_subject->id]['weighted_obtained'] += 0;
                                $optional_subject_exam_results[$optional_subject->id]['weighted_full'] += 0;
                                $optional_subject_exam_results[$optional_subject->id]['exam_data'][$exam->id] = [
                                    'exam'      => $exam->title,
                                    'obtained'  => 0,
                                    'full'      => 0,
                                    'grade_point' => 0,
                                    'letter_grade'     => '',
                                    'is_fail'   => 1,
                                ];
                            }
                        } // End foreach $exams


                        $optional_subjectId = $optional_subject->id;

                        $optional_weighted_obtained = (float) $optional_subject_exam_results[$optional_subjectId]['weighted_obtained'];
                        $optional_weighted_full     = (float) $optional_subject_exam_results[$optional_subjectId]['weighted_full'];

                        // include_mark
                        $include_mark = (float) $optional_subject_exam_results[$optional_subjectId]['include_mark'];

                        // Add to student totals (ONCE per subject, after all exams)
                        $studentTotalAggregateMarks += $include_mark;

                        $optional_include_aggregate_percentage = $optional_weighted_full > 0
                            ? round(($include_mark / $optional_weighted_full) * 100, 2)
                            : 0;

                        $optional_subject_exam_results[$optional_subjectId]['aggregate_percentage'] = $optional_include_aggregate_percentage;

                        // Calculation without include_mark
                        $optional_aggregate_percentage = $optional_weighted_full > 0
                            ? round(($optional_weighted_obtained / $optional_weighted_full) * 100, 2)
                            : 0;

                        $grade_data  = get_grade_data($optional_subject->grade_system_id, $optional_aggregate_percentage);
                        $optional_subject_grade_point  = $grade_data['grade_point'];
                        $optional_subject_grade_letter = $grade_data['letter_grade'];
                        $optional_subject_isfail      = $grade_data['is_fail'];

                        if ($optional_subject_grade_point == 0) {
                            $optional_subject_isfail = 1;
                        }

                        // if ($optional_subject_isfail) {
                        //     $studentFailedSubjects++;
                        // } else {
                        //     $studentPassedSubjects++;
                        // }

                        $optional_subject_exam_results[$optional_subjectId]['grade_point']  = $optional_subject_grade_point;
                        $optional_subject_exam_results[$optional_subjectId]['letter_grade'] = $optional_subject_grade_letter;
                        $optional_subject_exam_results[$optional_subjectId]['is_fail']      = $optional_subject_isfail;

                        $totalGradePoints  += $optional_subject_grade_point;
                        //$totalSubjectsCount += 1;

                        $studentSubjectResults[] = [
                            'subject_id'   => $optional_subject->id,
                            'subject_name' => $optional_subject->title,
                            'aggregate_obtained' => round($include_mark, 2),
                            'aggregate_full'     => round($optional_weighted_full, 2),
                            'percentage'   => $optional_include_aggregate_percentage,
                            'grade_point'  => $optional_subject_grade_point,
                            'letter_grade' => $optional_subject_grade_letter,
                            'is_fail'      => $optional_subject_isfail,
                            //'exam_details' => $optional_subject_exam_results[$optional_subjectId]['exam_data'],
                        ];

                        // Save to final_result_subjects
                        $optional_subjectAggregateData = [
                            'school_id'             => $school_id,
                            'school_owner_uid'      => $user_id,
                            'student_uid'           => $studentId,
                            'enrollment_id'         => $enrollment->id,
                            'session_id'            => $year_id,
                            'class_id'              => $class_id,
                            'section_id'            => $section_id ?: $enrollment->section_id,
                            'subject_id'            => $optional_subjectId,
                            'aggregate_full_mark'   => round($optional_weighted_full, 2),
                            'aggregate_obtained_mark' => round($include_mark, 2),
                            'aggregate_percentage'  => $optional_include_aggregate_percentage,
                            'grade_point'           => $optional_subject_grade_point,
                            'letter_grade'          => $optional_subject_grade_letter,
                            'is_fail'               => $optional_subject_isfail,
                            'is_generated'          => 1,
                            'generated_at'          => date('Y-m-d H:i:s'),
                            'created_by'            => $user_id
                        ];

                        // existing record for this subject?
                        $optional_subjectExisting = $this->FinalResultSubjectModel
                            ->where('school_id', $school_id)
                            ->where('student_uid', $studentId)
                            ->where('session_id', $year_id)
                            ->where('class_id', $class_id)
                            ->where('subject_id', $optional_subjectId)
                            ->first();

                        // Save or update the record
                        if ($optional_subjectExisting) {
                            $optional_subjectAggregateData['updated_by'] = $user_id;
                            $this->FinalResultSubjectModel->update($optional_subjectExisting->id, $optional_subjectAggregateData);
                        } else {
                            $this->FinalResultSubjectModel->insert($optional_subjectAggregateData);
                        }
                    } // End foreach $subjectCategories

                    
                } // End if isset($subjectCategories['optional_subjects'])
                
           
                // Calculate overall aggregate
                $overallPercentage = $studentTotalFullMarks > 0 ? round(($studentTotalAggregateMarks / $studentTotalFullMarks) * 100, 2) : 0;

                $totalGradePoints = $totalGradePoints ?: 0;
                $totalSubjectsCount = $totalSubjectsCount ?: 1; // Avoid division by zero

                $gpa = round($totalGradePoints / $totalSubjectsCount, 2);

                // Determine overall grade
                $overallLetterGrade = '';
                $overallLetterGradePoint = 0;
                $resultStatus = ($studentFailedSubjects > 0) ? 'FAIL' : 'PASS';

                $firstCompulsory = $subjectCategories['compulsory_subjects'][0] ?? null;
                if ($firstCompulsory && $firstCompulsory->grade_system_id && $gpa > 0) {
                    $gradingRecords = $this->GradeRulesModel
                        ->where('grade_system_id', (int) $firstCompulsory->grade_system_id)
                        ->where('status', 1)
                        ->orderBy('mark_from', 'DESC')
                        ->findAll();

                    foreach ($gradingRecords as $g) {
                        if ($overallPercentage >= (float) $g->mark_from && $overallPercentage <= (float) $g->mark_to) {
                            $overallLetterGrade = $g->title;
                            $overallLetterGradePoint = (float) $g->grade_point;
                            break;
                        }
                    }
                }


                $studentAggregates[$studentId] = [
                    'student_id'        => $studentId,
                    'enrollment_id'     => $enrollment->id,
                    'section_id'        => $section_id ?: $enrollment->section_id,
                    'total_marks'       => round($studentTotalAggregateMarks, 2),
                    'total_full_marks'  => round($studentTotalFullMarks, 2),
                    'percentage'        => $overallPercentage,
                    'gpa'               => $overallLetterGradePoint,
                    'letter_grade'      => $overallLetterGrade,
                    'result_status'     => $resultStatus,
                    'passed_subjects'   => $studentPassedSubjects,
                    'failed_subjects'   => $studentFailedSubjects,
                    'total_subjects'    => $totalSubjectsCount,
                    'subject_results'   => $studentSubjectResults,
                ];

                
            } // End foreach $enrollments


            // echo '<pre>';
            // print_r($studentAggregates);
            // echo '</pre>';

            // ===================================================================
            // STEP 6: Assign positions (class rank, section rank)
            // ===================================================================
            $this->assignAggregatePositions($studentAggregates, $class_id, $section_id);

            // ===================================================================
            // STEP 7: Save to final_results table
            // ===================================================================
            $processed = 0;
            foreach ($studentAggregates as $studentId => $data) {

                $existingFinal = $this->FinalResultModel
                    ->where('student_uid', $studentId)
                    ->where('session_id', $year_id)
                    ->where('class_id', $class_id)
                    ->first();

                $finalData = [
                    'school_id'        => $school_id,
                    'school_owner_uid' => $user_id,
                    'student_uid'      => $studentId,
                    'enrollment_id'    => $data['enrollment_id'],
                    'session_id'       => $year_id,
                    'class_id'         => $class_id,
                    'section_id'       => $data['section_id'],
                    'total_marks'      => $data['total_marks'],
                    'total_full_marks' => $data['total_full_marks'],
                    'percentage'       => $data['percentage'],
                    'gpa'              => $data['gpa'],
                    'grade_letter'     => $data['letter_grade'],
                    'grade_name'       => $data['letter_grade'],
                    'class_position'   => $data['class_position'] ?? 0,
                    'section_position' => $data['section_position'] ?? 0,
                    'result_status'    => $data['result_status'],
                    'promotion_status' => ($data['result_status'] == 'PASS') ? 'promoted' : 'not_promoted',
                    'is_generated'     => 1,
                    'generated_at'     => date('Y-m-d H:i:s'),
                    'created_by'       => $user_id,
                ];

                if ($existingFinal) {
                    $finalData['updated_by'] = $user_id;
                    $this->FinalResultModel->update($existingFinal->id, $finalData);
                } else {
                    // Token generation for new final result
                    $finalData['token'] = $this->generateToken();
                    $this->FinalResultModel->insert($finalData);
                }

                $processed++;
            }

            $this->db->transCommit();

            // Build exam info for response
            $examInfo = [];
            foreach ($exams as $exam) {
                $examInfo[] = [
                    'id'       => $exam->id,
                    'title'    => $exam->title,
                    'weight'   => $exam->weight_percentage,
                ];
            }

            return $this->jsonResponse([
                'status'    => true,
                'message'   => "Aggregate results generated for {$processed} student(s) successfully.",
                'processed' => $processed,
                'exams'     => $examInfo,
                'total_weight' => $totalWeight,
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            $this->logMessage('error', 'Aggregate generate failed: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Error generating aggregate results: ' . $e->getMessage(),
            ]);
        }
    }

    // ===================================================================
    // Assign positions for aggregate results
    // ===================================================================
    private function assignAggregatePositions(array &$studentAggregates, int $class_id, int $section_id): void
    {
        // Sort by GPA desc, then marks desc for class position
        $classStudents = $studentAggregates;
        usort($classStudents, function ($a, $b) {
            if ($b['gpa'] != $a['gpa']) {
                return $b['gpa'] <=> $a['gpa'];
            }
            return $b['total_marks'] <=> $a['total_marks'];
        });

        $classRank = 0;
        $prevGpa = null;
        $prevMarks = null;
        foreach ($classStudents as $idx => $data) {
            $sid = $data['student_id'];
            if ($prevGpa !== null && $prevGpa == $data['gpa'] && $prevMarks == $data['total_marks']) {
                // Tie - same rank
            } else {
                $classRank = $idx + 1;
            }
            $studentAggregates[$sid]['class_position'] = $classRank;
            $prevGpa = $data['gpa'];
            $prevMarks = $data['total_marks'];
        }

        // Section position
        if ($section_id) {
            $sectionStudents = array_filter($studentAggregates, function ($data) use ($section_id) {
                return $data['section_id'] == $section_id;
            });
            usort($sectionStudents, function ($a, $b) {
                if ($b['gpa'] != $a['gpa']) {
                    return $b['gpa'] <=> $a['gpa'];
                }
                return $b['total_marks'] <=> $a['total_marks'];
            });

            $sectionRank = 0;
            $prevGpa = null;
            $prevMarks = null;
            foreach ($sectionStudents as $idx => $data) {
                $sid = $data['student_id'];
                if ($prevGpa !== null && $prevGpa == $data['gpa'] && $prevMarks == $data['total_marks']) {
                    // Tie
                } else {
                    $sectionRank = $idx + 1;
                }
                $studentAggregates[$sid]['section_position'] = $sectionRank;
                $prevGpa = $data['gpa'];
                $prevMarks = $data['total_marks'];
            }
        } else {
            // No section filter - section position = class position
            foreach ($studentAggregates as $sid => $data) {
                $studentAggregates[$sid]['section_position'] = $data['class_position'];
            }
        }
    }

    // ===================================================================
    // Delete existing aggregate data
    // ===================================================================
    private function deleteExistingAggregate(int $school_id, int $class_id, int $section_id, int $year_id): void
    {
        // Delete final_result_subjects
        $subjectDelete = $this->FinalResultSubjectModel
            ->where('school_id', $school_id)
            ->where('session_id', $year_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $subjectDelete->where('section_id', $section_id);
        }
        $subjectDelete->delete();

        // Delete final_results (but keep is_generated = 0 records)
        $finalDelete = $this->FinalResultModel
            ->where('school_id', $school_id)
            ->where('session_id', $year_id)
            ->where('class_id', $class_id);
        if ($section_id) {
            $finalDelete->where('section_id', $section_id);
        }
        $finalDelete->delete();
    }

    // generateToken()
    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    // ======================================================================
    // REMARKS PAGE - Final result remarks (principal, teacher, promotion)
    // Route: examination/aggregate/remarks
    // ======================================================================
    public function remarks()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Final Result Remarks',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $school_id   = (int) $this->request->getGet('school_id');
        $year_id     = (int) $this->request->getGet('year_id');
        $class_id    = (int) $this->request->getGet('class_id');
        $section_id  = (int) $this->request->getGet('section_id');

        $data = compact('school_id', 'year_id', 'class_id', 'section_id');
        $data['school_list']     = $this->getSchoolDropdown();
        $data['year_list']       = $this->getActiveOptions($school_id ?: 0, 'YearModel');
        $data['class_list']      = [];
        $data['section_list']    = [];
        $data['results']         = [];
        $data['sessions']        = [];

        if ($school_id) {
            $data['class_list']   = $this->getActiveOptions($school_id, 'ClassModel');
            $data['section_list'] = $this->getActiveOptions($school_id, 'SectionModel');
        }

        // If filters are set, get final results
        if ($school_id && $year_id && $class_id) {
            $userSchools = $this->getUserSchools();
            $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

            if (!in_array($school_id, $schoolIds)) {
                return redirect()->to('examination/aggregate/remarks')->with('error', 'Access denied.');
            }

            $this->FinalResultModel
                ->select('examination_final_results.*, 
                    students.first_name, students.middle_name, students.last_name, students.student_code,
                    student_enrollments.roll_no,
                    academic_classes.title AS class_title,
                    academic_sections.title AS section_title,
                    next_session.title AS next_session_title,
                    next_class.title AS next_class_title,
                    next_section.title AS next_section_title,
                    examination_final_results.next_roll')
                ->join('students', 'students.id = examination_final_results.student_uid', 'left')
                ->join('student_enrollments', 'student_enrollments.id = examination_final_results.enrollment_id', 'left')
                ->join('academic_classes', 'academic_classes.id = examination_final_results.class_id', 'left')
                ->join('academic_sections', 'academic_sections.id = examination_final_results.section_id', 'left')
                ->join('academic_years AS next_session', 'next_session.id = examination_final_results.next_session_id', 'left')
                ->join('academic_classes AS next_class', 'next_class.id = examination_final_results.next_class_id', 'left')
                ->join('academic_sections AS next_section', 'next_section.id = examination_final_results.next_section_id', 'left')
                ->where('examination_final_results.school_id', $school_id)
                ->where('examination_final_results.session_id', $year_id)
                ->where('examination_final_results.class_id', $class_id)
                ->orderBy('student_enrollments.roll_no', 'ASC');

            if ($section_id) {
                $this->FinalResultModel->where('examination_final_results.section_id', $section_id);
            }

            if ($section_id) {
                $this->FinalResultModel->where('examination_final_results.section_id', $section_id);
            }

            $results = $this->FinalResultModel->findAll();
            $data['results'] = $results;

            // Get available sessions for next_session dropdown
            $sessions = $this->YearModel
                ->where('school_id', $school_id)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
            $data['sessions'] = $sessions;

            // Get available classes for next_class dropdown
            $data['next_class_list'] = $this->getActiveOptions($school_id, 'ClassModel');
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\aggregate\remarks', $data)
            . view('footer', $footer_data);
    }

    // ======================================================================
    // AJAX: Get remarks results data
    // Route: examination/remarks/aggregate/results
    // ======================================================================
    public function ajaxGetRemarksResults()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Invalid request']);
        }

        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated']);
        }

        $school_id = (int) $this->request->getPost('school_id');
        $year_id   = (int) $this->request->getPost('year_id');
        $class_id  = (int) $this->request->getPost('class_id');

        if (!$school_id || !$year_id || !$class_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields']);
        }

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);
        if (!in_array($school_id, $schoolIds)) {
            return $this->jsonResponse(['status' => false, 'message' => 'Access denied']);
        }

        $results = $this->FinalResultModel
            ->select('examination_final_results.*, 
                students.first_name, students.middle_name, students.last_name, students.student_code,
                student_enrollments.roll_no,
                academic_classes.title AS class_title,
                academic_sections.title AS section_title,
                next_session.title AS next_session_title,
                next_class.title AS next_class_title,
                next_section.title AS next_section_title,
                examination_final_results.next_roll')
            ->join('students', 'students.id = examination_final_results.student_uid', 'left')
            ->join('student_enrollments', 'student_enrollments.id = examination_final_results.enrollment_id', 'left')
            ->join('academic_classes', 'academic_classes.id = examination_final_results.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = examination_final_results.section_id', 'left')
            ->join('academic_years AS next_session', 'next_session.id = examination_final_results.next_session_id', 'left')
            ->join('academic_classes AS next_class', 'next_class.id = examination_final_results.next_class_id', 'left')
            ->join('academic_sections AS next_section', 'next_section.id = examination_final_results.next_section_id', 'left')
            ->where('examination_final_results.school_id', $school_id)
            ->where('examination_final_results.session_id', $year_id)
            ->where('examination_final_results.class_id', $class_id)
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->findAll();

        // Get sessions for next_session dropdown
        $sessions = $this->YearModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        // Get classes for next_class dropdown
        $nextClassList = $this->getActiveOptions($school_id, 'ClassModel');

        // Get sections for next_section dropdown
        $sectionList = $this->getActiveOptions($school_id, 'SectionModel');

        // Build HTML
        $html = '';
        if (empty($results)) {
            $html = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> No final results found. Please generate aggregate results first.</div>';
        } else {
            $html = view('App\Modules\examination\Views\aggregate\remarks_table', [
                'results' => $results,
                'sessions' => $sessions,
                'next_class_list' => $nextClassList,
                'section_list' => $sectionList,
            ]);
        }

        return $this->jsonResponse([
            'status' => true,
            'html' => $html,
            'count' => count($results),
        ]);
    }

    // ======================================================================
    // SAVE REMARKS - AJAX endpoint to save final result remarks
    // Route: examination/remarks/aggregate/save
    // ======================================================================
    public function saveRemarks()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Unauthorized access.'
            ]);
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks');
        if ($subscription_check) {
            return $this->jsonResponse(['status' => false, 'message' => $subscription_check['message']]);
        }

        $final_result_id = (int) $this->request->getPost('final_result_id');
        $principal_remark = $this->request->getPost('principal_remark');
        $teacher_remark = $this->request->getPost('teacher_remark');
        $next_session_id = $this->request->getPost('next_session_id');
        $next_class_id = $this->request->getPost('next_class_id');
        $next_section_id = $this->request->getPost('next_section_id');
        $next_roll = $this->request->getPost('next_roll');

        if (!$final_result_id) {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Invalid final result ID.'
            ]);
        }

        // Verify the result belongs to user's school
        $finalResult = $this->FinalResultModel
            ->select('examination_final_results.*, schools.id AS school_id')
            ->join('schools', 'schools.id = examination_final_results.school_id', 'left')
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('examination_final_results.id', $final_result_id)
            ->where('school_user_relation.user_id', $user_id)
            ->first();

        if (!$finalResult) {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Result not found or unauthorized access.'
            ]);
        }

        $updateData = [
            'principal_remark' => $principal_remark,
            'teacher_remark'   => $teacher_remark,
        ];

        if ($next_session_id) {
            $updateData['next_session_id'] = $next_session_id;
        }
        if ($next_class_id) {
            $updateData['next_class_id'] = $next_class_id;
        }
        if ($next_section_id) {
            $updateData['next_section_id'] = $next_section_id;
        }
        if ($next_roll) {
            $updateData['next_roll'] = $next_roll;
        }

        $updated = $this->FinalResultModel->update($final_result_id, $updateData);

        if ($updated) {
            return $this->jsonResponse([
                'status' => true,
                'message' => 'Remarks saved successfully.'
            ]);
        } else {
            return $this->jsonResponse([
                'status' => false,
                'message' => 'Failed to save remarks.'
            ]);
        }
    }
}

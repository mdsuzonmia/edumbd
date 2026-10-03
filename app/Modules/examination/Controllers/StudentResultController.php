<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\SchoolModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\MarkDistributionModel;

class StudentResultController extends BaseController
{
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected SchoolModel $SchoolModel;
    protected ExamModel $ExamModel;
    protected ExamResultModel $ExamResultModel;
    protected SubjectResultModel $SubjectResultModel;
    protected SubjectModel $SubjectModel;

    public function __construct()
    {
        $this->StudentModel       = new StudentModel();
        $this->EnrollmentModel    = new StudentEnrollmentModel();
        $this->SchoolModel        = new SchoolModel();
        $this->ExamModel          = new ExamModel();
        $this->ExamResultModel    = new ExamResultModel();
        $this->SubjectResultModel = new SubjectResultModel();
        $this->SubjectModel       = new SubjectModel();
    }

    public function index()
    {
        $user_id = session('user_id');
        if (!$user_id) {
            return redirect()->to('login');
        }

        $student = $this->StudentModel
            ->where('user_id', $user_id)
            ->where('status', 1)
            ->first();

        if (!$student) {
            return redirect()->to('login')->with('error', 'Student not found.');
        }

        $school = $this->SchoolModel->find((int) $student->school_id);

        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, academic_years.title AS session_title, academic_classes.title AS class_title')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->where('student_enrollments.student_id', $student->id)
            ->orderBy('student_enrollments.session_id', 'DESC')
            ->findAll();

        // Get exams for each enrollment with result tokens
        $enrollment_exams = [];
        foreach ($enrollments as $enrollment) {
            $exams = $this->ExamModel
                ->where('school_id', $student->school_id)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
            
            // Attach result token to each exam
            foreach ($exams as $exam) {
                $result = $this->ExamResultModel
                    ->where('school_id', $student->school_id)
                    ->where('exam_id', $exam->id)
                    ->where('class_id', $enrollment->class_id)
                    ->where('student_id', $student->id)
                    ->where('session_id', $enrollment->session_id)
                    ->first();
                $exam->result_token = $result ? $result->token : '';
            }
            
            $enrollment_exams[$enrollment->id] = $exams;
        }

        $header_data['page_title'] = 'My Results';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data = [
            'student'           => $student,
            'school'            => $school,
            'enrollments'       => $enrollments,
            'enrollment_exams'  => $enrollment_exams,
        ];

        return view('header', $header_data)
            . view('App\Modules\examination\Views\student\results', $data)
            . view('footer', $footer_data);
    }

    /**
     * View individual result details for student
     * Accepts exam result token as parameter
     */
    public function viewResult($token = null)
    {
        $user_id = session('user_id');
        if (!$user_id) {
            return redirect()->to('login');
        }

        $student = $this->StudentModel
            ->where('user_id', $user_id)
            ->where('status', 1)
            ->first();

        if (!$student) {
            return redirect()->to('login')->with('error', 'Student not found.');
        }

        if (!$token) {
            return redirect()->to('examination/student/result')->with('error', 'Invalid token.');
        }

        // Find exam result by token
        $result = $this->ExamResultModel
            ->where('token', $token)
            ->where('student_id', $student->id)
            ->first();

        if (!$result) {
            return redirect()->to('examination/student/result')->with('error', 'Result not found or you do not have permission to view it.');
        }

        // Get all necessary data from the result
        $school_id = $result->school_id;
        $exam_id = $result->exam_id;
        $class_id = $result->class_id;
        $student_id = $result->student_id;
        $year_id = $result->session_id ?? 0;

        $school = $this->SchoolModel->find($school_id);
        $exam = $this->ExamModel->find($exam_id);
        
        // Get student details with enrollment
        $studentData = $this->StudentModel
            ->select('students.*, student_enrollments.roll_no, student_enrollments.section_id, student_enrollments.session_id')
            ->join('student_enrollments', 'student_enrollments.student_id = students.id', 'left')
            ->where('students.id', $student_id)
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('student_enrollments.session_id', $year_id)
            ->first();

        if (!$studentData) {
            return redirect()->to('examination/student/result')->with('error', 'Student enrollment data not found.');
        }

        // Get subject-wise marks
        $subjectMarks = $this->SubjectResultModel
            ->select('examination_subject_results.*, examination_subjects.title AS subject_title')
            ->join('examination_subjects', 'examination_subjects.id = examination_subject_results.subject_id', 'left')
            ->where('examination_subject_results.school_id', $school_id)
            ->where('examination_subject_results.exam_id', $exam_id)
            ->where('examination_subject_results.class_id', $class_id)
            ->where('examination_subject_results.student_id', $student_id)
            ->orderBy('examination_subjects.title', 'ASC')
            ->findAll();

        // Get mark distribution data
        $distributionData = $this->getDistributionMarks($school_id, $exam_id, $class_id, $student_id);

        $header_data['page_title'] = 'My Result Details';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data = [
            'student' => $studentData,
            'school' => $school,
            'exam' => $exam,
            'result' => $result,
            'subject_marks' => $subjectMarks,
            'distribution_data' => $distributionData,
            'token' => $token,
        ];

        return view('header', $header_data)
            . view('App\Modules\examination\Views\student\result_details', $data)
            . view('footer', $footer_data);
    }

    /**
     * Get distribution marks for a student
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
}

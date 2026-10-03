<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\SubjectResultHistoryModel;
use App\Models\ExamResultHistoryModel;
use App\Models\ExamResultModel;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsSubjectModel;
use App\Models\AcademicsExamModel;
use App\Models\StudentModel;

class HistoryController extends BaseController
{
    protected SubjectResultHistoryModel $SubjectResultHistoryModel;
    protected ExamResultHistoryModel $ExamResultHistoryModel;
    protected ExamResultModel $ExamResultModel;
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsSubjectModel $SubjectModel;
    protected AcademicsExamModel $ExamModel;
    protected StudentModel $StudentModel;

    public function __construct()
    {
        $this->SubjectResultHistoryModel = new SubjectResultHistoryModel();
        $this->ExamResultHistoryModel    = new ExamResultHistoryModel();
        $this->ExamResultModel           = new ExamResultModel();
        $this->SchoolModel               = new SchoolModel();
        $this->YearModel                 = new AcademicsYearModel();
        $this->ClassModel                = new AcademicsClassesModel();
        $this->SectionModel              = new AcademicsSectionModel();
        $this->SubjectModel              = new AcademicsSubjectModel();
        $this->ExamModel                 = new AcademicsExamModel();
        $this->StudentModel              = new StudentModel();
    }

    protected function getUserId(): int
    {
        return (int) session('user_id');
    }

    /**
     * Display exam result history
     */
    public function examHistory($examId)
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $exam = $this->ExamModel->find($examId);
        if (!$exam) {
            return redirect()->to('school-owner/dashboard')->with('error', 'Exam not found.');
        }

        $header_data = [
            'page_title' => 'Exam Result History - ' . $exam->title,
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        // Get exam result history records
        $history = $this->ExamResultHistoryModel
            ->select('exam_result_history.*, students.first_name, students.middle_name, students.last_name, students.student_code, academic_classes.title AS class_title, academic_sections.title AS section_title')
            ->join('students', 'students.id = exam_result_history.student_id', 'left')
            ->join('academic_classes', 'academic_classes.id = exam_result_history.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = exam_result_history.section_id', 'left')
            ->where('exam_result_history.exam_id', $examId)
            ->orderBy('exam_result_history.id', 'DESC')
            ->findAll();

        $data['history'] = $history;
        $data['exam']    = $exam;

        return view('header', $header_data)
            . view('school_owner/history/exam_history', $data)
            . view('footer', $footer_data);
    }

    /**
     * Display student result history
     */
    public function studentHistory($studentId)
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $student = $this->StudentModel->find($studentId);
        if (!$student) {
            return redirect()->to('school-owner/dashboard')->with('error', 'Student not found.');
        }

        $studentName = trim($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
        $studentName = preg_replace('/\s+/', ' ', $studentName);

        $header_data = [
            'page_title' => 'Student Result History - ' . $studentName,
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        // Get exam result history for this student
        $examHistory = $this->ExamResultHistoryModel
            ->select('exam_result_history.*, academic_exams.title AS exam_title, academic_classes.title AS class_title, academic_sections.title AS section_title')
            ->join('academic_exams', 'academic_exams.id = exam_result_history.exam_id', 'left')
            ->join('academic_classes', 'academic_classes.id = exam_result_history.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = exam_result_history.section_id', 'left')
            ->where('exam_result_history.student_id', $studentId)
            ->orderBy('exam_result_history.id', 'DESC')
            ->findAll();

        // Get subject result history for this student
        $subjectHistory = $this->SubjectResultHistoryModel
            ->select('subject_result_history.*, academic_exams.title AS exam_title, academic_subjects.title AS subject_title')
            ->join('academic_exams', 'academic_exams.id = subject_result_history.exam_id', 'left')
            ->join('academic_subjects', 'academic_subjects.id = subject_result_history.subject_id', 'left')
            ->where('subject_result_history.student_id', $studentId)
            ->orderBy('subject_result_history.id', 'DESC')
            ->findAll();

        $data['examHistory']    = $examHistory;
        $data['subjectHistory'] = $subjectHistory;
        $data['student']        = $student;
        $data['student_name']   = $studentName;

        return view('header', $header_data)
            . view('school_owner/history/student_history', $data)
            . view('footer', $footer_data);
    }
}
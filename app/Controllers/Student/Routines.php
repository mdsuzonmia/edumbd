<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;

class Routines extends BaseController
{
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;

    public function __construct()
    {
        $this->StudentModel       = new StudentModel();
        $this->EnrollmentModel    = new StudentEnrollmentModel();
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

        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, academic_years.title AS session_title, academic_classes.title AS class_title')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->where('student_enrollments.student_id', $student->id)
            ->orderBy('student_enrollments.session_id', 'DESC')
            ->findAll();

        $header_data['page_title'] = 'My Routines';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data = [
            'student'     => $student,
            'enrollments' => $enrollments,
        ];

        return view('header', $header_data)
            . view('student/routines', $data)
            . view('footer', $footer_data);
    }
}
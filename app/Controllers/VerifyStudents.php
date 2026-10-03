<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\SchoolModel;

class VerifyStudents extends BaseController
{
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->StudentModel       = new StudentModel();
        $this->EnrollmentModel    = new StudentEnrollmentModel();
        $this->SchoolModel        = new SchoolModel();
    }

    public function verify(string $token)
    {
        $student = $this->StudentModel
            ->where('token', $token)
            ->where('status', 1)
            ->first();

        if (!$student) {
            return view('verify_student', [
                'status'  => 'error',
                'message' => 'Student not found or invalid QR code.',
            ]);
        }

        $school = $this->SchoolModel->find((int) $student->school_id);

        $enrollment = $this->EnrollmentModel
            ->select('student_enrollments.*, academic_years.title AS session_title, academic_classes.title AS class_title')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->where('student_enrollments.student_id', $student->id)
            ->orderBy('student_enrollments.session_id', 'DESC')
            ->first();

        $data = [
            'status'    => 'success',
            'student'   => $student,
            'school'    => $school,
            'enrollment'=> $enrollment,
        ];

        return view('verify_student', $data);
    }

    public function verify_qr_code(string $token)
    {
        $student = $this->StudentModel
            ->where('token', $token)
            ->where('status', 1)
            ->first();

        if (!$student) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid QR code. Student not found.',
            ]);
        }

        $school = $this->SchoolModel->find((int) $student->school_id);

        $enrollment = $this->EnrollmentModel
            ->select('student_enrollments.*, academic_years.title AS session_title, academic_classes.title AS class_title')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->where('student_enrollments.student_id', $student->id)
            ->orderBy('student_enrollments.session_id', 'DESC')
            ->first();

        return $this->response->setJSON([
            'status'     => 'success',
            'student'    => [
                'id'            => $student->id,
                'student_code'  => $student->student_code,
                'first_name'    => $student->first_name,
                'middle_name'   => $student->middle_name,
                'last_name'     => $student->last_name,
                'gender'        => $student->gender,
                'date_of_birth' => $student->date_of_birth,
                'phone'         => $student->phone,
                'email'         => $student->email,
                'photo'         => $student->photo ? base_url('uploads/' . $student->photo) : base_url('uploads/default.png'),
            ],
            'school'     => $school ? [
                'id'   => $school->id,
                'name' => $school->name,
            ] : null,
            'enrollment' => $enrollment ? [
                'session_title' => $enrollment->session_title,
                'class_title'   => $enrollment->class_title,
                'roll_no'       => $enrollment->roll_no,
            ] : null,
        ]);
    }
}
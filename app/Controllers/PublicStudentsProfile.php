<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\SchoolModel;
use App\Models\StudentGuardianModel;
use App\Models\StudentAddressModel;
use App\Models\StudentDocumentModel;

class PublicStudentsProfile extends BaseController
{
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected SchoolModel $SchoolModel;
    protected StudentGuardianModel $GuardianModel;
    protected StudentAddressModel $AddressModel;
    protected StudentDocumentModel $DocumentModel;

    public function __construct()
    {
        $this->StudentModel       = new StudentModel();
        $this->EnrollmentModel    = new StudentEnrollmentModel();
        $this->SchoolModel        = new SchoolModel();
        $this->GuardianModel      = new StudentGuardianModel();
        $this->AddressModel       = new StudentAddressModel();
        $this->DocumentModel      = new StudentDocumentModel();
    }

    public function profile(string $token)
    {
        $student = $this->StudentModel
            ->where('token', $token)
            ->where('status', 1)
            ->first();

        if (!$student) {
            return view('public_student_profile', [
                'status'  => 'error',
                'message' => 'Student not found.',
            ]);
        }

        $school = $this->SchoolModel->find((int) $student->school_id);

        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, academic_years.title AS session_title, academic_classes.title AS class_title, academic_sections.title AS section_title')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = student_enrollments.section_id', 'left')
            ->where('student_enrollments.student_id', $student->id)
            ->orderBy('student_enrollments.session_id', 'DESC')
            ->findAll();

        $guardians = $this->GuardianModel
            ->where('student_id', $student->id)
            ->findAll();

        $address = $this->AddressModel
            ->where('student_id', $student->id)
            ->first();

        $documents = $this->DocumentModel
            ->where('student_id', $student->id)
            ->findAll();

        $data = [
            'status'     => 'success',
            'student'    => $student,
            'school'     => $school,
            'enrollments'=> $enrollments,
            'guardians'  => $guardians,
            'address'    => $address,
            'documents'  => $documents,
        ];

        return view('public_student_profile', $data);
    }
}
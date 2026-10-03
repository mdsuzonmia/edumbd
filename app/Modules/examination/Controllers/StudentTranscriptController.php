<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\SchoolModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsShiftModel;
use App\Models\StudentGuardianModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\ResultTemplateModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Modules\examination\Models\FinalResultModel;
use App\Modules\examination\Models\FinalResultSubjectModel;
use App\Modules\examination\Models\GradeRuleModel;

class StudentTranscriptController extends BaseController
{
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected SchoolModel $SchoolModel;
    protected ExamModel $ExamModel;
    protected ExamResultModel $ExamResultModel;
    protected SubjectResultModel $SubjectResultModel;
    protected SubjectModel $SubjectModel;
    protected MarkDistributionModel $MarkDistributionModel;
    protected FinalResultModel $FinalResultModel;
    protected FinalResultSubjectModel $FinalResultSubjectModel;
    protected GradeRuleModel $GradeRuleModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsShiftModel $ShiftModel;
    protected StudentGuardianModel $StudentGuardianModel;
    protected ResultTemplateModel $TemplateModel;

    public function __construct()
    {
        $this->StudentModel            = new StudentModel();
        $this->EnrollmentModel         = new StudentEnrollmentModel();
        $this->SchoolModel             = new SchoolModel();
        $this->ExamModel               = new ExamModel();
        $this->ExamResultModel         = new ExamResultModel();
        $this->SubjectResultModel      = new SubjectResultModel();
        $this->SubjectModel            = new SubjectModel();
        $this->MarkDistributionModel   = new MarkDistributionModel();
        $this->FinalResultModel        = new FinalResultModel();
        $this->FinalResultSubjectModel = new FinalResultSubjectModel();
        $this->GradeRuleModel          = new GradeRuleModel();
        $this->SectionModel            = new AcademicsSectionModel();
        $this->ShiftModel              = new AcademicsShiftModel();
        $this->StudentGuardianModel    = new StudentGuardianModel();
        $this->TemplateModel           = new ResultTemplateModel();
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

        // Get final result tokens for each enrollment
        $enrollment_exams = [];
        foreach ($enrollments as $enrollment) {
            $exams = $this->ExamModel
                ->where('school_id', $student->school_id)
                ->where('status', 1)
                ->orderBy('title', 'ASC')
                ->findAll();
            
            // Get final result token for this enrollment (from final_results table)
            $finalResult = $this->FinalResultModel
                ->where('school_id', $student->school_id)
                ->where('session_id', $enrollment->session_id)
                ->where('class_id', $enrollment->class_id)
                ->where('student_uid', $student->id)
                ->first();
            
            $token = $finalResult ? $finalResult->token : '';
            
            // Attach token to all exams (we'll use the first exam's token)
            foreach ($exams as $exam) {
                $exam->result_token = $token;
            }
            
            $enrollment_exams[$enrollment->id] = $exams;
        }

        $header_data['page_title'] = 'My Transcript';
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
            . view('App\Modules\examination\Views\student\transcript', $data)
            . view('footer', $footer_data);
    }

    /**
     * View individual transcript details for student
     * Accepts final result token as parameter
     */
    public function viewTranscript($token = null)
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
            return redirect()->to('examination/student/transcript')->with('error', 'Invalid token.');
        }

        // Find the final result by token
        $finalResult = $this->FinalResultModel
            ->where('token', $token)
            ->first();

        if (!$finalResult) {
            return redirect()->to('examination/student/transcript')->with('error', 'Transcript not found or you do not have permission to view it.');
        }

        $school_id  = (int) $finalResult->school_id ?? 0;
        $year_id    = (int) $finalResult->session_id;
        $class_id   = (int) $finalResult->class_id;
        $student_id = (int) $finalResult->student_uid;

        // Verify student owns this transcript
        if ($student_id != $student->id) {
            return redirect()->to('examination/student/transcript')->with('error', 'You do not have permission to view this transcript.');
        }

        // Get school info
        $school = $this->SchoolModel->find($school_id);
        if (!$school) {
            return redirect()->to('examination/student/transcript')->with('error', 'School not found.');
        }

        $class = model('App\Models\AcademicsClassesModel')->find($class_id);
        $year  = model('App\Models\AcademicsYearModel')->find($year_id);

        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';
        $className     = $class ? $class->title : '';
        $sessionName   = $year ? $year->title : '';

        // School logo
        $schoolLogoPath = $school && !empty($school->logo) ? base_url('uploads/' . $school->logo) : base_url('uploads/default.png');
        $schoolLogoHtml = '<img src="' . $schoolLogoPath . '" alt="School Logo" style="max-width:100px;">';

        // Get enrollment
        $enrollment = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code, students.registration_no, students.photo, students.date_of_birth, students.phone')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('student_enrollments.student_id', $student_id)
            ->where('students.status', 1)
            ->first();

        if (!$enrollment) {
            return redirect()->to('examination/student/transcript')->with('error', 'Enrollment not found.');
        }

        $studentName = trim(($enrollment->first_name ?? '') . ' ' . ($enrollment->middle_name ?? '') . ' ' . ($enrollment->last_name ?? ''));
        $studentName = preg_replace('/\s+/', ' ', $studentName);

        // Guardian info
        $guardians = $this->StudentGuardianModel
            ->where('school_id', $school_id)
            ->where('student_id', $student_id)
            ->orderBy('id', 'ASC')
            ->findAll();

        $fatherName   = '';
        $motherName   = '';
        $guardianName = '';
        foreach ($guardians as $g) {
            $relation = strtolower($g->relation_type ?? '');
            if (strpos($relation, 'father') !== false) {
                $fatherName = $g->name ?? '';
            } elseif (strpos($relation, 'mother') !== false) {
                $motherName = $g->name ?? '';
            } else {
                $guardianName = $g->name ?? '';
            }
        }

        $sectionInfo = $enrollment->section_id ? $this->SectionModel->find($enrollment->section_id) : null;
        $shiftInfo   = $enrollment->shift_id ? $this->ShiftModel->find($enrollment->shift_id) : null;
        $dob         = !empty($enrollment->date_of_birth) ? date('d-M-Y', strtotime($enrollment->date_of_birth)) : '';

        // Get transcript data
        $transcriptData = $this->getTranscriptData($school_id, $year_id, $class_id, $student_id);
        $finalResultData = $transcriptData['final_result'];

        if (!$finalResultData) {
            return redirect()->to('examination/student/transcript')->with('error', 'Final result not found. Please contact your school administrator.');
        }

        // Build student info table
        $studentInfoData = [
            'student_name'     => $studentName,
            'student_code'     => $enrollment->student_code ?? '',
            'roll_no'          => $enrollment->roll_no ?? '',
            'registration_no'  => $enrollment->registration_no ?? '',
            'father_name'      => $fatherName,
            'mother_name'      => $motherName,
            'guardian_name'    => $guardianName,
            'class_name'       => $className,
            'section_name'     => $sectionInfo ? $sectionInfo->title : '',
            'session_name'     => $sessionName,
            'shift_name'       => $shiftInfo ? $shiftInfo->title : '',
            'group_name'       => '',
            'date_of_birth'    => $dob,
            'student_phone'    => $enrollment->phone ?? $guardianName,
        ];
        $studentInfoTable = $this->buildStudentInfoTable($studentInfoData);

        // Subject academic table
        $grandTotal = 0;
        $grandFull  = 0;
        foreach ($transcriptData['subject_academic_rows'] as $row) {
            $grandTotal += $row['aggregate']['obtained'];
            $grandFull  += $row['aggregate']['full'];
        }
        $subjectAcademicTable = $this->buildSubjectAcademicTable($transcriptData['subject_academic_rows'], $grandTotal, $grandFull);
        $examPerformanceTable = $this->buildExamPerformanceTable(
            $transcriptData['exam_performance_rows'],
            $transcriptData['aggregate_performance']
        );

        // Final academic summary
        $finalAcademicData = [
            'total_marks'       => $finalResultData->total_marks ?? 0,
            'total_full_marks'  => $finalResultData->total_full_marks ?? 0,
            'percentage'        => $finalResultData->percentage ?? 0,
            'gpa'               => $finalResultData->gpa ?? 0,
            'grade'             => $finalResultData->grade_name ?? $finalResultData->grade_letter ?? '',
            'result_status'     => $finalResultData->result_status ?? 'PASS',
            'class_position'    => $finalResultData->class_position ?? '-',
            'section_position'  => $finalResultData->section_position ?? '-',
            'present_days'      => $finalResultData->present_days ?? 0,
            'working_days'      => $finalResultData->working_days ?? 0,
            'promotion_status'  => $finalResultData->promotion_status ?? '',
            'next_class'        => '',
        ];

        if (!empty($finalResultData->next_class_id)) {
            $nextClass = model('App\Models\AcademicsClassesModel')->find($finalResultData->next_class_id);
            $finalAcademicData['next_class'] = $nextClass ? $nextClass->title : '';
        }
        $finalAcademicSummary = $this->buildFinalAcademicSummary($finalAcademicData);

        // Promotion info
        $promotionDate = '';
        if (!empty($finalResultData->published_at)) {
            $promotionDate = date('d-M-Y', strtotime($finalResultData->published_at));
        }
        $nextSessionName = '';
        if (!empty($finalResultData->next_session_id)) {
            $nextSession = model('App\Models\AcademicsYearModel')->find($finalResultData->next_session_id);
            $nextSessionName = $nextSession ? $nextSession->title : '';
        }
        $nextSectionName = '';
        if (!empty($finalResultData->next_section_id)) {
            $nextSection = $this->SectionModel->find($finalResultData->next_section_id);
            $nextSectionName = $nextSection ? $nextSection->title : '';
        }

        $promotionData = [
            'result_status'    => $finalResultData->result_status ?? 'PASS',
            'promotion_status' => $finalResultData->promotion_status ?? '',
            'next_session'     => $nextSessionName,
            'next_class'       => $finalAcademicData['next_class'],
            'next_section'     => $nextSectionName,
            'next_roll'        => $finalResultData->next_roll ?? '',
            'promotion_date'   => $promotionDate,
        ];
        $promotionInfo = $this->buildPromotionInfo($promotionData);

        // QR code for public link
        $transcriptNo = 'TR-' . ($year->title ?? date('Y')) . '-' . str_pad($finalResultData->id ?? 0, 8, '0', STR_PAD_LEFT);
        $qrCodeUrl = base_url('examination/transcript/' . $token);
        $qrCode = '<div>';
        $qrCode .= '<p style="font-size:16px;font-weight:bold; margin-bottom:0px;">QR Code</p>';
        $qrCode .= '<div style="margin:0 auto;">';
        $qrCode .= '<img src="' . generate_qr_code($qrCodeUrl, 120) . '" alt="QR Code" style="width:80px;height:80px;">';
        $qrCode .= '</div>';
        $qrCode .= '<p style="margin:0px;font-size:12px;color:#666;">Scan to verify transcript authenticity</p>';
        $qrCode .= '<p style="font-size:13px;font-weight:bold;margin:0px;">Transcript No: ' . $transcriptNo . '</p>';
        $qrCode .= '</div>';

        // Student photo
        $studentPhotoPath = $enrollment->photo && !empty($enrollment->photo)
            ? base_url('uploads/' . $enrollment->photo)
            : base_url('uploads/default.png');
        $studentPhoto = '<img src="' . $studentPhotoPath . '" alt="Student Photo" style="width:100px;height:auto;">';

        // Principal signature
        $principalSignature = '';
        if ($school && !empty($school->params)) {
            $schoolParams = json_decode($school->params, true);
            if (isset($schoolParams['principal_signature']) && !empty($schoolParams['principal_signature'])) {
                $principalSignature = base_url('uploads/signatures/' . $schoolParams['principal_signature']);
            }
        }

        // Generate grading chart - get school owner from school record
        $gradingChart = '';
        if ($school_id) {
            $gradingChart = $this->generateGradingChart($school_id);
        }

        $resultData = [
            'school_name'            => $schoolName,
            'school_logo'            => $schoolLogoHtml,
            'school_address'         => $schoolAddress,
            'school_phone'           => $schoolPhone,
            'school_email'           => $schoolEmail,
            'school_website'         => '',
            'class_name'             => $className,
            'section_name'           => $sectionInfo ? $sectionInfo->title : '',
            'session_name'           => $sessionName,
            'shift_name'             => $shiftInfo ? $shiftInfo->title : '',
            'group_name'             => '',
            'student_name'           => $studentName,
            'student_code'           => $enrollment->student_code ?? '',
            'roll_no'                => $enrollment->roll_no ?? '',
            'registration_no'        => $enrollment->registration_no ?? '',
            'father_name'            => $fatherName,
            'mother_name'            => $motherName,
            'date_of_birth'          => $dob,
            'guardian_name'          => $guardianName,
            'student_phone'          => $enrollment->phone ?? $guardianName,
            'student_info_table'     => $studentInfoTable,
            'student_photo'          => $studentPhoto,
            'principal_signature'    => $principalSignature,
            'principal_remarks'      => $finalResultData->principal_remark ?? '',
            'teacher_remarks'        => $finalResultData->teacher_remark ?? '',
            'grade_remarks'          => '',
            'subject_academic_table' => $subjectAcademicTable,
            'exam_performance_table' => $examPerformanceTable,
            'exam_table'             => $examPerformanceTable,
            'aggregated_table'       => $subjectAcademicTable,
            'exam_summary'           => $examPerformanceTable,
            'overall_summary'        => $finalAcademicSummary,
            'final_academic_summary' => $finalAcademicSummary,
            'promotion_info'         => $promotionInfo,
            'qr_code'                => $qrCode,
            'transcript_no'          => $transcriptNo,
            'grading_chart'          => $gradingChart,
        ];

        // Try using template
        $template = $this->getTemplateForSchool($school_id);
        $renderedContent = '';
        $templateStyle = '';

        if ($template && !empty($template->template_content)) {
            $renderedContent = $this->renderTemplate($template->template_content, $resultData);
            $templateStyle = $template->template_style ?? '';
        } else {
            // Build default HTML
            $renderedContent = '<div class="transcript-card" style="font-family:Arial,sans-serif;max-width:1200px;margin:0 auto;padding:20px;">';
            $renderedContent .= '<div style="text-align:center;border-bottom:2px solid #1a73e8;padding-bottom:15px;margin-bottom:20px;">';
            $renderedContent .= '<h2 style="color:#1a73e8;margin:0;">' . esc($schoolName) . '</h2>';
            $renderedContent .= '<p style="margin:5px 0;font-size:14px;">' . esc($schoolAddress) . '</p>';
            $renderedContent .= '<p style="margin:5px 0;font-size:14px;">Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
            $renderedContent .= '<h3 style="color:#333;margin-top:15px;">Academic Transcript</h3>';
            $renderedContent .= '</div>';
            $renderedContent .= '<h4 style="color:#333;margin-bottom:10px;">Student Information</h4>';
            $renderedContent .= $studentInfoTable;
            $renderedContent .= '<h4 style="color:#333;margin-bottom:10px;">Subject-wise Academic Performance</h4>';
            $renderedContent .= $subjectAcademicTable;
            $renderedContent .= '<h4 style="color:#333;margin-bottom:10px;">Exam-wise Performance</h4>';
            $renderedContent .= $examPerformanceTable;
            $renderedContent .= $finalAcademicSummary;
            $renderedContent .= $promotionInfo;
            $renderedContent .= $qrCode;
            $renderedContent .= '</div>';
        }

        $header_data['page_title'] = 'My Transcript Details';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data = [
            'school_name'      => $schoolName,
            'school_logo'      => $schoolLogoHtml,
            'student_name'     => $studentName,
            'class_name'       => $className,
            'session_name'     => $sessionName,
            'rendered_content' => $renderedContent,
            'template_style'   => $templateStyle,
            'transcript_token' => $token,
        ];

        return view('header', $header_data)
            . view('App\Modules\examination\Views\student\transcript_details', $data)
            . view('footer', $footer_data);
    }

    /**
     * Download transcript as PDF for student
     */
    public function downloadPdf($token = null)
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
            return redirect()->to('examination/student/transcript')->with('error', 'Invalid token.');
        }

        // Find the final result by token
        $finalResult = $this->FinalResultModel
            ->where('token', $token)
            ->first();

        if (!$finalResult) {
            return redirect()->to('examination/student/transcript')->with('error', 'Transcript not found.');
        }

        $school_id  = (int) $finalResult->school_id ?? 0;
        $year_id    = (int) $finalResult->session_id;
        $class_id   = (int) $finalResult->class_id;
        $student_id = (int) $finalResult->student_uid;

        // Verify student owns this transcript
        if ($student_id != $student->id) {
            return redirect()->to('examination/student/transcript')->with('error', 'You do not have permission to download this transcript.');
        }

        // Get school info
        $school = $this->SchoolModel->find($school_id);
        $class = model('App\Models\AcademicsClassesModel')->find($class_id);
        $year  = model('App\Models\AcademicsYearModel')->find($year_id);

        $schoolName    = $school ? $school->name : '';
        $schoolAddress = $school ? $school->address : '';
        $schoolPhone   = $school ? $school->phone : '';
        $schoolEmail   = $school ? $school->email : '';
        $className     = $class ? $class->title : '';
        $sessionName   = $year ? $year->title : '';

        // School logo
        $schoolLogoPath = $school && !empty($school->logo) ? base_url('uploads/' . $school->logo) : base_url('uploads/default.png');
        $schoolLogoHtml = '<img src="' . $schoolLogoPath . '" alt="School Logo" style="max-width:100px;">';

        // Get enrollment
        $enrollment = $this->EnrollmentModel
            ->select('student_enrollments.*, students.id AS student_id, students.first_name, students.middle_name, students.last_name, students.student_code, students.registration_no, students.photo, students.date_of_birth, students.phone')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('student_enrollments.student_id', $student_id)
            ->where('students.status', 1)
            ->first();

        if (!$enrollment) {
            return redirect()->to('examination/student/transcript')->with('error', 'Enrollment not found.');
        }

        $studentName = trim(($enrollment->first_name ?? '') . ' ' . ($enrollment->middle_name ?? '') . ' ' . ($enrollment->last_name ?? ''));
        $studentName = preg_replace('/\s+/', ' ', $studentName);

        // Guardian info
        $guardians = $this->StudentGuardianModel
            ->where('school_id', $school_id)
            ->where('student_id', $student_id)
            ->orderBy('id', 'ASC')
            ->findAll();

        $fatherName   = '';
        $motherName   = '';
        $guardianName = '';
        foreach ($guardians as $g) {
            $relation = strtolower($g->relation_type ?? '');
            if (strpos($relation, 'father') !== false) {
                $fatherName = $g->name ?? '';
            } elseif (strpos($relation, 'mother') !== false) {
                $motherName = $g->name ?? '';
            } else {
                $guardianName = $g->name ?? '';
            }
        }

        $sectionInfo = $enrollment->section_id ? $this->SectionModel->find($enrollment->section_id) : null;
        $shiftInfo   = $enrollment->shift_id ? $this->ShiftModel->find($enrollment->shift_id) : null;
        $dob         = !empty($enrollment->date_of_birth) ? date('d-M-Y', strtotime($enrollment->date_of_birth)) : '';

        // Get transcript data
        $transcriptData = $this->getTranscriptData($school_id, $year_id, $class_id, $student_id);
        $finalResultData = $transcriptData['final_result'];

        if (!$finalResultData) {
            return redirect()->to('examination/student/transcript')->with('error', 'Final result not found.');
        }

        // Build student info table
        $studentInfoData = [
            'student_name'     => $studentName,
            'student_code'     => $enrollment->student_code ?? '',
            'roll_no'          => $enrollment->roll_no ?? '',
            'registration_no'  => $enrollment->registration_no ?? '',
            'father_name'      => $fatherName,
            'mother_name'      => $motherName,
            'guardian_name'    => $guardianName,
            'class_name'       => $className,
            'section_name'     => $sectionInfo ? $sectionInfo->title : '',
            'session_name'     => $sessionName,
            'shift_name'       => $shiftInfo ? $shiftInfo->title : '',
            'group_name'       => '',
            'date_of_birth'    => $dob,
            'student_phone'    => $enrollment->phone ?? $guardianName,
        ];
        $studentInfoTable = $this->buildStudentInfoTable($studentInfoData);

        // Subject academic table
        $grandTotal = 0;
        $grandFull  = 0;
        foreach ($transcriptData['subject_academic_rows'] as $row) {
            $grandTotal += $row['aggregate']['obtained'];
            $grandFull  += $row['aggregate']['full'];
        }
        $subjectAcademicTable = $this->buildSubjectAcademicTable($transcriptData['subject_academic_rows'], $grandTotal, $grandFull);
        $examPerformanceTable = $this->buildExamPerformanceTable(
            $transcriptData['exam_performance_rows'],
            $transcriptData['aggregate_performance']
        );

        // Final academic summary
        $finalAcademicData = [
            'total_marks'       => $finalResultData->total_marks ?? 0,
            'total_full_marks'  => $finalResultData->total_full_marks ?? 0,
            'percentage'        => $finalResultData->percentage ?? 0,
            'gpa'               => $finalResultData->gpa ?? 0,
            'grade'             => $finalResultData->grade_name ?? $finalResultData->grade_letter ?? '',
            'result_status'     => $finalResultData->result_status ?? 'PASS',
            'class_position'    => $finalResultData->class_position ?? '-',
            'section_position'  => $finalResultData->section_position ?? '-',
            'present_days'      => $finalResultData->present_days ?? 0,
            'working_days'      => $finalResultData->working_days ?? 0,
            'promotion_status'  => $finalResultData->promotion_status ?? '',
            'next_class'        => '',
        ];

        if (!empty($finalResultData->next_class_id)) {
            $nextClass = model('App\Models\AcademicsClassesModel')->find($finalResultData->next_class_id);
            $finalAcademicData['next_class'] = $nextClass ? $nextClass->title : '';
        }
        $finalAcademicSummary = $this->buildFinalAcademicSummary($finalAcademicData);

        // Promotion info
        $promotionDate = '';
        if (!empty($finalResultData->published_at)) {
            $promotionDate = date('d-M-Y', strtotime($finalResultData->published_at));
        }
        $nextSessionName = '';
        if (!empty($finalResultData->next_session_id)) {
            $nextSession = model('App\Models\AcademicsYearModel')->find($finalResultData->next_session_id);
            $nextSessionName = $nextSession ? $nextSession->title : '';
        }
        $nextSectionName = '';
        if (!empty($finalResultData->next_section_id)) {
            $nextSection = $this->SectionModel->find($finalResultData->next_section_id);
            $nextSectionName = $nextSection ? $nextSection->title : '';
        }

        $promotionData = [
            'result_status'    => $finalResultData->result_status ?? 'PASS',
            'promotion_status' => $finalResultData->promotion_status ?? '',
            'next_session'     => $nextSessionName,
            'next_class'       => $finalAcademicData['next_class'],
            'next_section'     => $nextSectionName,
            'next_roll'        => $finalResultData->next_roll ?? '',
            'promotion_date'   => $promotionDate,
        ];
        $promotionInfo = $this->buildPromotionInfo($promotionData);

        // QR code
        $transcriptNo = 'TR-' . ($year->title ?? date('Y')) . '-' . str_pad($finalResultData->id ?? 0, 8, '0', STR_PAD_LEFT);
        $qrCodeUrl = base_url('examination/transcript/' . $token);
        $qrCode = '<div>';
        $qrCode .= '<p style="font-size:16px;font-weight:bold; margin-bottom:0px;">QR Code</p>';
        $qrCode .= '<div style="margin:0 auto;">';
        $qrCode .= '<img src="' . generate_qr_code($qrCodeUrl, 120) . '" alt="QR Code" style="width:80px;height:80px;">';
        $qrCode .= '</div>';
        $qrCode .= '<p style="margin:0px;font-size:12px;color:#666;">Scan to verify transcript authenticity</p>';
        $qrCode .= '<p style="font-size:13px;font-weight:bold;margin:0px;">Transcript No: ' . $transcriptNo . '</p>';
        $qrCode .= '</div>';

        // Student photo
        $studentPhotoPath = $enrollment->photo && !empty($enrollment->photo)
            ? base_url('uploads/' . $enrollment->photo)
            : base_url('uploads/default.png');
        $studentPhoto = '<img src="' . $studentPhotoPath . '" alt="Student Photo" style="width:100px;height:auto;">';

        // Principal signature
        $principalSignature = '';
        if ($school && !empty($school->params)) {
            $schoolParams = json_decode($school->params, true);
            if (isset($schoolParams['principal_signature']) && !empty($schoolParams['principal_signature'])) {
                $principalSignature = base_url('uploads/signatures/' . $schoolParams['principal_signature']);
            }
        }

        // Generate grading chart
        $gradingChart = '';
        if ($school_id) {
            $gradingChart = $this->generateGradingChart($school_id);
        }

        $resultData = [
            'school_name'            => $schoolName,
            'school_logo'            => $schoolLogoHtml,
            'school_address'         => $schoolAddress,
            'school_phone'           => $schoolPhone,
            'school_email'           => $schoolEmail,
            'school_website'         => '',
            'class_name'             => $className,
            'section_name'           => $sectionInfo ? $sectionInfo->title : '',
            'session_name'           => $sessionName,
            'shift_name'             => $shiftInfo ? $shiftInfo->title : '',
            'group_name'             => '',
            'student_name'           => $studentName,
            'student_code'           => $enrollment->student_code ?? '',
            'roll_no'                => $enrollment->roll_no ?? '',
            'registration_no'        => $enrollment->registration_no ?? '',
            'father_name'            => $fatherName,
            'mother_name'            => $motherName,
            'date_of_birth'          => $dob,
            'guardian_name'          => $guardianName,
            'student_phone'          => $enrollment->phone ?? $guardianName,
            'student_info_table'     => $studentInfoTable,
            'student_photo'          => $studentPhoto,
            'principal_signature'    => $principalSignature,
            'principal_remarks'      => $finalResultData->principal_remark ?? '',
            'teacher_remarks'        => $finalResultData->teacher_remark ?? '',
            'grade_remarks'          => '',
            'subject_academic_table' => $subjectAcademicTable,
            'exam_performance_table' => $examPerformanceTable,
            'exam_table'             => $examPerformanceTable,
            'aggregated_table'       => $subjectAcademicTable,
            'exam_summary'           => $examPerformanceTable,
            'overall_summary'        => $finalAcademicSummary,
            'final_academic_summary' => $finalAcademicSummary,
            'promotion_info'         => $promotionInfo,
            'qr_code'                => $qrCode,
            'transcript_no'          => $transcriptNo,
            'grading_chart'          => $gradingChart,
        ];

        // Build rendered HTML
        $template = $this->getTemplateForSchool($school_id);
        $html = '';
        $templateStyle = '';

        if ($template && !empty($template->template_content)) {
            $html = $this->renderTemplate($template->template_content, $resultData);
            $templateStyle = $template->template_style ?? '';
        } else {
            // Build default HTML
            $html = '<div class="transcript-card" style="font-family:Arial,sans-serif;max-width:1200px;margin:0 auto;padding:20px;">';
            $html .= '<div style="text-align:center;border-bottom:2px solid #1a73e8;padding-bottom:15px;margin-bottom:20px;">';
            $html .= '<h2 style="color:#1a73e8;margin:0;">' . esc($schoolName) . '</h2>';
            $html .= '<p style="margin:5px 0;font-size:14px;">' . esc($schoolAddress) . '</p>';
            $html .= '<p style="margin:5px 0;font-size:14px;">Phone: ' . esc($schoolPhone) . ' | Email: ' . esc($schoolEmail) . '</p>';
            $html .= '<h3 style="color:#333;margin-top:15px;">Academic Transcript</h3>';
            $html .= '</div>';
            $html .= '<h4 style="color:#333;margin-bottom:10px;">Student Information</h4>';
            $html .= $studentInfoTable;
            $html .= '<h4 style="color:#333;margin-bottom:10px;">Subject-wise Academic Performance</h4>';
            $html .= $subjectAcademicTable;
            $html .= '<h4 style="color:#333;margin-bottom:10px;">Exam-wise Performance</h4>';
            $html .= $examPerformanceTable;
            $html .= $finalAcademicSummary;
            $html .= $promotionInfo;
            $html .= $qrCode;
            $html .= '</div>';
        }

        // Build full HTML document for PDF
        $pdfHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Transcript - ' . esc($schoolName) . '</title>';
        $pdfHtml .= '<style>';
        if ($templateStyle) {
            $pdfHtml .= $templateStyle;
        }
        $pdfHtml .= 'body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }';
        $pdfHtml .= 'html { margin: 0; padding: 0; }';
        $pdfHtml .= '
        p {
            margin: 0;
            padding: 0;
        }
        .subject-academic-table{
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .subject-academic-table th, .subject-academic-table td{
            padding: 0px 5px;
            border: 1px solid #000;
        }
        
        .transcript-card { margin: 0; padding: 10px; }';
        $pdfHtml .= '</style></head><body>';
        $pdfHtml .= $html;
        $pdfHtml .= '</body></html>';

        // Generate PDF using Dompdf
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('margin_top', 0.5);
        $options->set('margin_bottom', 0.5);
        $options->set('margin_left', 0.5);
        $options->set('margin_right', 0.5);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($pdfHtml);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Transcript_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $schoolName) . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $className) . '_' . date('Ymd-His') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * Get transcript template for school.
     */
    protected function getTemplateForSchool(int $school_id): ?object
    {
        $template = $this->TemplateModel
            ->where('school_id', $school_id)
            ->where('support_multiple_exams', 1)
            ->where('support_aggregated_result', 1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        if ($template) {
            return $template;
        }

        $template = $this->TemplateModel
            ->where('school_id', 0)
            ->orWhere('school_id IS NULL')
            ->where('support_multiple_exams', 1)
            ->where('support_aggregated_result', 1)
            ->where('is_default', 1)
            ->first();

        return $template;
    }

    /**
     * Render template with placeholder replacement.
     */
    protected function renderTemplate(string $template_content, array $data): string
    {
        $replacements = [
            '{school_name}'            => $data['school_name'] ?? '',
            '{school_address}'         => $data['school_address'] ?? '',
            '{school_phone}'           => $data['school_phone'] ?? '',
            '{school_email}'           => $data['school_email'] ?? '',
            '{school_website}'         => $data['school_website'] ?? '',
            '{school_logo}'            => $data['school_logo'] ?? '',
            '{student_name}'           => $data['student_name'] ?? '',
            '{student_code}'           => $data['student_code'] ?? '',
            '{roll_no}'                => $data['roll_no'] ?? '',
            '{registration_no}'        => $data['registration_no'] ?? '',
            '{class_name}'             => $data['class_name'] ?? '',
            '{section_name}'           => $data['section_name'] ?? '',
            '{session_name}'           => $data['session_name'] ?? '',
            '{shift_name}'             => $data['shift_name'] ?? '',
            '{group_name}'             => $data['group_name'] ?? '',
            '{father_name}'            => $data['father_name'] ?? '',
            '{mother_name}'            => $data['mother_name'] ?? '',
            '{date_of_birth}'          => $data['date_of_birth'] ?? '',
            '{guardian_name}'          => $data['guardian_name'] ?? '',
            '{student_phone}'          => $data['student_phone'] ?? '',
            '{student_info_table}'     => $data['student_info_table'] ?? '',
            '{subject_academic_table}' => $data['subject_academic_table'] ?? '',
            '{exam_performance_table}' => $data['exam_performance_table'] ?? '',
            '{exam_table}'             => $data['exam_table'] ?? $data['exam_performance_table'] ?? '',
            '{aggregated_table}'       => $data['aggregated_table'] ?? $data['subject_academic_table'] ?? '',
            '{exam_summary}'           => $data['exam_summary'] ?? $data['exam_performance_table'] ?? '',
            '{overall_summary}'        => $data['overall_summary'] ?? $data['final_academic_summary'] ?? '',
            '{final_academic_summary}' => $data['final_academic_summary'] ?? '',
            '{promotion_info}'         => $data['promotion_info'] ?? '',
            '{qr_code}'                => $data['qr_code'] ?? '',
            '{transcript_no}'          => $data['transcript_no'] ?? '',
            '{student_photo}'          => $data['student_photo'] ?? '',
            '{principal_remarks}'      => $data['principal_remarks'] ?? '',
            '{teacher_remarks}'        => $data['teacher_remarks'] ?? '',
            '{principal_signature}'    => $data['principal_signature'] ?? '',
            '{grade_remarks}'          => $data['grade_remarks'] ?? '',
            '{grading_chart}'          => $data['grading_chart'] ?? '',
            '{highest_mark}'           => $data['highest_mark'] ?? '',
            '{current_date}'           => date('d-m-Y'),
            '{current_year}'           => date('Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template_content);
    }

    /**
     * Build student info table.
     */
    protected function buildStudentInfoTable(array $data): string
    {
        $html = '<table class="student-info-table table table-bordered" border="1" cellpadding="6" >';
        $html .= '<tr><td><strong>Student Name</strong></td><td>: ' . esc($data['student_name'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Student ID</strong></td><td>: ' . esc($data['student_code'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Roll No</strong></td><td>: ' . esc($data['roll_no'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Session</strong></td><td>: ' . esc($data['session_name'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Class</strong></td><td>: ' . esc($data['class_name'] ?? '') . '</td></tr>';
        $html .= '<tr><td><strong>Section</strong></td><td>: ' . esc($data['section_name'] ?? '') . '</td></tr>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Build subject academic table.
     */
    protected function buildSubjectAcademicTable(array $subjectAcademicRows, float $grandTotal = 0, float $grandFull = 0): string
    {
        if (empty($subjectAcademicRows)) {
            return '<p>No subject data available.</p>';
        }

        $examNames = [];
        if (!empty($subjectAcademicRows[0]['exams'])) {
            foreach ($subjectAcademicRows[0]['exams'] as $exam) {
                $examNames[] = $exam['name'];
            }
        }

        $hasDistributions = false;
        foreach ($subjectAcademicRows as $row) {
            foreach ($row['exams'] as $exam) {
                if (!empty($exam['distributions'])) {
                    $hasDistributions = true;
                    break 2;
                }
            }
        }

        $colspanPerExam = 3;
        if ($hasDistributions) {
            $maxDistributions = 0;
            foreach ($subjectAcademicRows as $row) {
                foreach ($row['exams'] as $exam) {
                    $maxDistributions = max($maxDistributions, count($exam['distributions'] ?? []));
                }
            }
            $colspanPerExam = 4 + $maxDistributions;
        }
        $totalExamColspan = count($examNames) * $colspanPerExam;
        $aggregateColspan = 4;
        $totalColspan = 1 + $totalExamColspan + $aggregateColspan;

        $html = '<table class="subject-academic-table table table-bordered" border="1" cellpadding="6" style="width:100%;border-collapse:collapse;margin-bottom:15px;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th rowspan="2">Subject</th>';
        foreach ($examNames as $ename) {
            $html .= '<th colspan="' . $colspanPerExam . '">' . esc($ename) . '</th>';
        }
        $html .= '<th colspan="5">Aggregate Result</th>';
        $html .= '</tr>';
        $html .= '<tr>';
        foreach ($examNames as $ename) {
            if ($hasDistributions) {
                $distNames = [];
                foreach ($subjectAcademicRows as $r) {
                    foreach ($r['exams'] as $e) {
                        if (!empty($e['distributions'])) {
                            foreach ($e['distributions'] as $d) {
                                $distNames[$d['name']] = true;
                            }
                        }
                    }
                }
                foreach (array_keys($distNames) as $distName) {
                    $html .= '<th>' . esc($distName) . '</th>';
                }
            }
            $html .= '<th>Total</th>';
            $html .= '<th>Highest</th>';
            $html .= '<th>GP</th>';
            $html .= '<th>Grade</th>';
        }
        $html .= '<th>Total</th>';
        $html .= '<th>Percentage</th>';
        $html .= '<th>GP</th>';
        $html .= '<th>Grade</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($subjectAcademicRows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . esc($row['subject_name'] ?? '') . '</td>';
            foreach ($row['exams'] as $exam) {
                if ($hasDistributions) {
                    $distNames = [];
                    foreach ($subjectAcademicRows as $r) {
                        foreach ($r['exams'] as $e) {
                            if (!empty($e['distributions'])) {
                                foreach ($e['distributions'] as $d) {
                                    $distNames[$d['name']] = true;
                                }
                            }
                        }
                    }
                    $distMap = [];
                    foreach ($exam['distributions'] as $d) {
                        $distMap[$d['name']] = $d['obtained'];
                    }
                    foreach (array_keys($distNames) as $distName) {
                        $value = $distMap[$distName] ?? 0;
                        $html .= '<td>' . number_format($value, 2) . '</td>';
                    }
                }
                $html .= '<td>' . ($exam['total'] !== null ? number_format($exam['total'], 2) : '-') . '</td>';
                $html .= '<td>' . ($exam['highest_mark'] > 0 ? number_format($exam['highest_mark'], 2) : '-') . '</td>';
                $html .= '<td>' . ($exam['gp'] !== null ? number_format($exam['gp'], 2) : '-') . '</td>';
                $html .= '<td>' . esc($exam['grade'] ?? '-') . '</td>';
            }
            $agg = $row['aggregate'];
            $html .= '<td>' . number_format($agg['obtained'] ?? 0, 2) . '</td>';
            $html .= '<td>' . number_format($agg['percentage'] ?? 0, 2) . '%</td>';
            $html .= '<td>' . number_format($agg['gp'] ?? 0, 2) . '</td>';
            $html .= '<td>' . esc($agg['grade'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        if ($grandFull > 0) {
            $html .= '<tfoot><tr>';
            $html .= '<td colspan="' . $totalColspan . '">Grand Total : ' . number_format($grandTotal, 2) . ' / ' . number_format($grandFull, 2) . '</td>';
            $html .= '</tr></tfoot>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Build exam performance table.
     */
    protected function buildExamPerformanceTable(array $examPerformanceRows, array $aggregatePerformance): string
    {
        $html = '<table width="100%" class="exam-performance-table table table-bordered" border="1" cellpadding="6" >';
        $html .= '<thead><tr>';
        $html .= '<th>Exam Name</th><th>Weight</th><th>Total</th><th>Percentage</th><th>GPA</th><th>Grade</th><th>Position</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($examPerformanceRows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . esc($row['exam_name'] ?? '') . '</td>';
            $html .= '<td>' . esc($row['weight'] ?? '') . '</td>';
            $html .= '<td>' . number_format($row['total'] ?? 0, 2) . '</td>';
            $html .= '<td>' . number_format($row['percentage'] ?? 0, 2) . '%</td>';
            $html .= '<td>' . number_format($row['gpa'] ?? 0, 2) . '</td>';
            $html .= '<td>' . esc($row['grade'] ?? '') . '</td>';
            $html .= '<td>' . ($row['position'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        if (!empty($aggregatePerformance)) {
            $html .= '<tr>';
            $html .= '<td>Aggregate Result</td>';
            $html .= '<td>100%</td>';
            $html .= '<td>' . number_format($aggregatePerformance['total'] ?? 0, 2) . '</td>';
            $html .= '<td>' . number_format($aggregatePerformance['percentage'] ?? 0, 2) . '%</td>';
            $html .= '<td>' . number_format($aggregatePerformance['gpa'] ?? 0, 2) . '</td>';
            $html .= '<td>' . esc($aggregatePerformance['grade'] ?? '') . '</td>';
            $html .= '<td>' . ($aggregatePerformance['position'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Build final academic summary.
     */
    protected function buildFinalAcademicSummary(array $data): string
    {
        $html = '<table class="final-summary-table table table-bordered" border="1" cellpadding="6" >';
        $html .= '<thead><tr><th colspan="2">Final Academic Summary</th></tr></thead>';
        $html .= '<tbody>';
        $html .= '<tr><td>Total Marks</td><td>: ' . number_format($data['total_marks'] ?? 0, 2) . ' / ' . number_format($data['total_full_marks'] ?? 0, 2) . '</td></tr>';
        $html .= '<tr><td>Percentage</td><td>: ' . number_format($data['percentage'] ?? 0, 2) . '%</td></tr>';
        $html .= '<tr><td>Overall GPA</td><td>: ' . number_format($data['gpa'] ?? 0, 2) . '</td></tr>';
        $html .= '<tr><td>Overall Grade</td><td>: ' . esc($data['grade'] ?? '') . '</td></tr>';
        $html .= '<tr><td>Class Position</td><td>: ' . ($data['class_position'] ?? '-') . '</td></tr>';
        $html .= '<tr><td>Section Position</td><td>: ' . ($data['section_position'] ?? '-') . '</td></tr>';
        if (!empty($data['present_days']) || !empty($data['working_days'])) {
            $attendance = ($data['present_days'] ?? 0) . ' / ' . ($data['working_days'] ?? 0);
            $html .= '<tr><td>Attendance</td><td>: ' . $attendance . '</td></tr>';
        }
        if (!empty($data['working_days'])) {
            $html .= '<tr><td>Working Days</td><td>: ' . (int) $data['working_days'] . '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Build promotion info.
     */
    protected function buildPromotionInfo(array $data): string
    {
        $html = '<table class="promotion-table table table-bordered" border="1" cellpadding="6" >';
        $html .= '<thead><tr><th colspan="2">Promotion Information</th></tr></thead>';
        $html .= '<tbody>';
        $html .= '<tr><td>Result</td><td>: ' . esc($data['result_status'] ?? '') . '</td></tr>';
        $html .= '<tr><td>Promotion Status</td><td>: ' . esc($data['promotion_status'] ?? '') . '</td></tr>';
        if (!empty($data['next_session'])) {
            $html .= '<tr><td>Next Academic Year</td><td>: ' . esc($data['next_session']) . '</td></tr>';
        }
        if (!empty($data['next_class'])) {
            $html .= '<tr><td>Next Class</td><td>: ' . esc($data['next_class']) . '</td></tr>';
        }
        if (!empty($data['next_section'])) {
            $html .= '<tr><td>Next Section</td><td>: ' . esc($data['next_section']) . '</td></tr>';
        }
        if (!empty($data['next_roll'])) {
            $html .= '<tr><td>Next Roll</td><td>: ' . esc($data['next_roll']) . '</td></tr>';
        }
        if (!empty($data['promotion_date'])) {
            $html .= '<tr><td>Promotion Date</td><td>: ' . esc($data['promotion_date']) . '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Get transcript data (exams, subjects, marks).
     */
    protected function getTranscriptData(int $school_id, int $year_id, int $class_id, int $student_id): array
    {
        $data = [
            'exams_data'             => [],
            'subject_academic_rows'  => [],
            'exam_performance_rows'  => [],
            'aggregate_performance'  => [],
            'final_result'           => null,
        ];

        $finalResult = $this->FinalResultModel
            ->where('session_id', $year_id)
            ->where('class_id', $class_id)
            ->where('student_uid', $student_id)
            ->first();

        $data['final_result'] = $finalResult;
        if (!$finalResult) {
            return $data;
        }

        $exams = $this->ExamModel
            ->where('school_id', $school_id)
            ->where('year_id', $year_id)
            ->where('status', 1)
            ->orderBy('exam_order', 'ASC')
            ->findAll();

        $data['exams_data'] = $exams;

        $subjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        $aggregateSubjects = $this->FinalResultSubjectModel
            ->where('student_uid', $student_id)
            ->where('session_id', $year_id)
            ->where('class_id', $class_id)
            ->findAll();

        $aggregateSubjectMap = [];
        foreach ($aggregateSubjects as $as) {
            $aggregateSubjectMap[$as->subject_id] = $as;
        }

        $db = \Config\Database::connect();
        $subjectDistributionMap = [];
        $subjectDistQuery = $db->table('examination_subject_distributions')
            ->select('examination_subject_distributions.*, examination_mark_distributions.name AS distribution_name')
            ->join('examination_mark_distributions', 'examination_mark_distributions.id = examination_subject_distributions.distribution_id', 'left')
            ->where('examination_subject_distributions.school_id', $school_id)
            ->where('examination_subject_distributions.status', 1)
            ->orderBy('examination_subject_distributions.sort_order', 'ASC')
            ->get();

        foreach ($subjectDistQuery->getResult() as $sd) {
            $subjectId = $sd->subject_id;
            if (!isset($subjectDistributionMap[$subjectId])) {
                $subjectDistributionMap[$subjectId] = [];
            }
            $subjectDistributionMap[$subjectId][] = $sd;
        }

        $highestMarksMap = [];
        foreach ($exams as $exam) {
            foreach ($subjects as $subject) {
                $highestQuery = $db->table('examination_subject_results')
                    ->select('MAX(obtained_mark) as highest_mark')
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam->id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subject->id)
                    ->get()
                    ->getRow();
                $key = $exam->id . '_' . $subject->id;
                $highestMarksMap[$key] = $highestQuery ? $highestQuery->highest_mark : 0;
            }
        }

        $subjectAcademicRows = [];
        foreach ($subjects as $subject) {
            $examData = [];
            foreach ($exams as $exam) {
                $subjectResult = $this->SubjectResultModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam->id)
                    ->where('class_id', $class_id)
                    ->where('student_id', $student_id)
                    ->where('subject_id', $subject->id)
                    ->first();

                $subjectDists = $subjectDistributionMap[$subject->id] ?? [];
                $distributionData = [];
                if ($subjectResult && !empty($subjectDists)) {
                    foreach ($subjectDists as $dist) {
                        $markQuery = $db->table('examination_marks')
                            ->select('obtained_mark')
                            ->where('school_id', $school_id)
                            ->where('exam_id', $exam->id)
                            ->where('subject_id', $subject->id)
                            ->where('student_id', $student_id)
                            ->where('distribution_id', $dist->distribution_id)
                            ->get()
                            ->getRow();
                        $distributionData[] = [
                            'name'     => $dist->distribution_name,
                            'obtained' => $markQuery ? $markQuery->obtained_mark : 0,
                        ];
                    }
                }

                $highestKey = $exam->id . '_' . $subject->id;
                $highestMark = $highestMarksMap[$highestKey] ?? 0;

                if ($subjectResult) {
                    $examData[] = [
                        'exam_id'       => $exam->id,
                        'name'          => $exam->title,
                        'total'         => $subjectResult->obtained_mark ?? 0,
                        'gp'            => $subjectResult->grade_point ?? 0,
                        'grade'         => $subjectResult->letter_grade ?? $subjectResult->grade ?? '',
                        'distributions' => $distributionData,
                        'highest_mark'  => $highestMark,
                    ];
                } else {
                    $examData[] = [
                        'exam_id'       => $exam->id,
                        'name'          => $exam->title,
                        'total'         => null,
                        'gp'            => null,
                        'grade'         => '-',
                        'distributions' => [],
                        'highest_mark'  => 0,
                    ];
                }
            }

            $aggSubject = $aggregateSubjectMap[$subject->id] ?? null;
            $aggregateData = [
                'obtained'   => $aggSubject->aggregate_obtained_mark ?? 0,
                'full'       => $aggSubject->aggregate_full_mark ?? 0,
                'percentage' => $aggSubject->aggregate_percentage ?? 0,
                'gp'         => $aggSubject->grade_point ?? 0,
                'grade'      => $aggSubject->letter_grade ?? $aggSubject->grade ?? '',
                'is_fail'    => $aggSubject->is_fail ?? 0,
            ];

            $subjectAcademicRows[] = [
                'subject_id'   => $subject->id,
                'subject_name' => $subject->title,
                'exams'        => $examData,
                'aggregate'    => $aggregateData,
            ];
        }

        $data['subject_academic_rows'] = $subjectAcademicRows;

        $examPerformanceRows = [];
        foreach ($exams as $exam) {
            $examResult = $this->ExamResultModel
                ->where('school_id', $school_id)
                ->where('exam_id', $exam->id)
                ->where('class_id', $class_id)
                ->where('student_id', $student_id)
                ->first();

            if ($examResult) {
                $examPerformanceRows[] = [
                    'exam_name'  => $exam->title,
                    'weight'     => ($exam->weight_percentage ?? 0) . '%',
                    'total'      => $examResult->obtained_marks ?? 0,
                    'percentage' => $examResult->percentage ?? 0,
                    'gpa'        => $examResult->gpa ?? 0,
                    'grade'      => $examResult->letter_grade ?? $examResult->grade ?? '',
                    'position'   => $examResult->class_rank ?? '-',
                ];
            }
        }

        $data['exam_performance_rows'] = $examPerformanceRows;

        if ($finalResult) {
            $data['aggregate_performance'] = [
                'total'      => $finalResult->total_marks ?? 0,
                'full_marks' => $finalResult->total_full_marks ?? 0,
                'percentage' => $finalResult->percentage ?? 0,
                'gpa'        => $finalResult->gpa ?? 0,
                'grade'      => $finalResult->grade_name ?? $finalResult->grade_letter ?? '',
                'position'   => $finalResult->class_position ?? '-',
            ];
        }

        return $data;
    }

    /**
     * Generate grading chart HTML.
     */
    protected function generateGradingChart(int $school_id = 0): string
    {
        $gradingSystemId = null;
        if ($school_id) {
            $school = $this->SchoolModel->find($school_id);
            if ($school && !empty($school->params)) {
                $params = json_decode($school->params, true);
                if (isset($params['grading_system']) && !empty($params['grading_system'])) {
                    $gradingSystemId = (int) $params['grading_system'];
                }
            }
        }

        $query = $this->GradeRuleModel
            ->where('status', 1);

        if ($gradingSystemId) {
            $query->where('grade_system_id', $gradingSystemId);
        }

        $gradeRules = $query->orderBy('field_order', 'ASC')->findAll();

        if (empty($gradeRules)) {
            return '';
        }

        $html = '<table class="grading-chart-table table table-bordered" border="1" cellpadding="4" >
            <thead>
                <tr>
                    <th style="text-align:center;">Grade</th>
                    <th style="text-align:center;">Marks Range</th>
                    <th style="text-align:center;">Grade Point</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($gradeRules as $rule) {
            $html .= '<tr>
                <td style="text-align:center;font-weight:bold;">' . esc($rule->title) . '</td>
                <td style="text-align:center;">' . ($rule->mark_from ?? 0) . ' - ' . ($rule->mark_to ?? 0) . '</td>
                <td style="text-align:center;">' . ($rule->grade_point ?? 0) . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }
}
<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Modules\examination\Models\ResultTemplateModel;

class ResultTemplateController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected ResultTemplateModel $TemplateModel;

    public function __construct()
    {
        $this->SchoolModel = new SchoolModel();
        $this->TemplateModel = new ResultTemplateModel();
    }

    protected function getUserId(): int
    {
        return (int) session('user_id');
    }

    protected function isSaasAdmin(): bool
    {
        return session('role') === 'super-admin' || (int) session('role_id') === 1;
    }

    protected function getUserSchools(): array
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return [];
        }

        if ($this->isSaasAdmin()) {
            return $this->SchoolModel
                ->select('schools.id, schools.name')
                ->where('schools.status', 1)
                ->orderBy('schools.name', 'ASC')
                ->findAll();
        }

        return $this->SchoolModel
            ->select('schools.id, schools.name')
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

    protected function canManageSchool(int $schoolId): bool
    {
        if ($this->isSaasAdmin()) {
            return $schoolId === 0 || $this->SchoolModel
                ->where('id', $schoolId)
                ->where('status', 1)
                ->countAllResults() > 0;
        }

        if ($schoolId <= 0) {
            return false;
        }

        foreach ($this->getUserSchools() as $school) {
            if ((int) $school->id === $schoolId) {
                return true;
            }
        }

        return false;
    }

    protected function findManageableTemplate(int $templateId): ?object
    {
        $query = $this->TemplateModel->where('id', $templateId);
        if (!$this->isSaasAdmin()) {
            $query->where('school_owner_uid', $this->getUserId());
        }

        return $query->first();
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    /**
     * List all templates with filter by school
     */
    public function index()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Result Templates',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $school_id = (int) $this->request->getGet('school_id');
        
        $data = [
            'school_list' => $this->getSchoolDropdown(),
            'selected_school' => $school_id,
            'is_saas_admin' => $this->isSaasAdmin(),
        ];

        // Get templates - filter by school if provided
        $templates = $this->TemplateModel;
        if (!$this->isSaasAdmin()) {
            $templates->where('school_owner_uid', $this->getUserId());
        }
        
        if ($school_id > 0) {
            $templates->where('school_id', $school_id);
        }
        
        $data['templates'] = $templates
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\templates\index', $data)
            . view('footer', $footer_data);
    }

    /**
     * Template builder page
     */
    public function builder()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Template Builder',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $school_id = (int) $this->request->getGet('school_id');
        $template_id = (int) $this->request->getGet('template_id');
        
        $data = [
            'school_list' => $this->getSchoolDropdown(),
            'selected_school' => $school_id,
            'is_saas_admin' => $this->isSaasAdmin(),
        ];

        // Available placeholders
        $data['placeholders'] = [
            'school' => [
                '{school_name}'        => 'School Name',
                '{school_address}'     => 'School Address',
                '{school_phone}'       => 'School Phone',
                '{school_email}'       => 'School Email',
                '{school_website}'     => 'School Website',
                '{school_logo}'        => 'School Logo',
            ],
            'student' => [
                '{student_name}'       => 'Student Full Name',
                '{student_code}'       => 'Student Code/ID',
                '{roll_no}'            => 'Roll Number',
                '{class_name}'         => 'Class Name',
                '{section_name}'       => 'Section Name',
                '{session_name}'       => 'Academic Session',
                '{shift_name}'         => 'Shift Name',
                '{registration_no}'    => 'Registration Number',
                '{guardian_name}'      => 'Guardian Name',
                '{student_phone}'      => 'Student Phone Number',
                '{student_info_table}' => 'Student Information formatted table (name, roll, class, session, ID, registration, section, shift, guardian, phone)',
            ],
            'exam' => [
                '{exam_name}'          => 'Exam Name',
                '{exam_year}'          => 'Exam Year',
            ],
            'result' => [
                '{total_subjects}'     => 'Total Subjects',
                '{passed_subjects}'    => 'Passed Subjects',
                '{failed_subjects}'    => 'Failed Subjects',
                '{total_marks}'        => 'Total Marks',
                '{obtained_marks}'     => 'Obtained Marks',
                '{percentage}'         => 'Percentage',
                '{gpa}'                => 'GPA',
                '{grade}'              => 'Grade',
                '{grade_remarks}'      => 'Grade Remarks/Performance (from Grade Rules)',
                '{result_status}'      => 'Result Status (Pass/Fail)',
                '{class_rank}'         => 'Class Rank',
                '{section_rank}'       => 'Section Rank',
                '{attendance}'         => 'Attendance Percentage',
                '{working_days}'       => 'Working Days',
                '{present_days}'       => 'Present Days',
                '{absent_days}'        => 'Absent Days',
                '{principal_remarks}'  => 'Principal Remarks',
                '{teacher_remarks}'    => 'Teacher Remarks',
                '{highest_mark}'       => 'Highest Mark in Exam',
                '{qr_code}'            => 'QR Code (auto-generated with verification link)',
            ],
            'student_info' => [
                '{student_photo}'      => 'Student Photo',
            ],
            'school_info' => [
                '{principal_signature}'       => 'Principal Signature',
                '{class_teacher_signature}'    => 'Class Teacher Signature',
                '{template_bg}'               => 'Template Background Image URL (auto-generated from upload)',
            ],
            'grading' => [
                '{grading_chart}'      => 'Grading Chart (auto-generated from Grade Rules)',
            ],
            'subject_table' => [
                '{subject_table}'      => 'Subject-wise marks table (auto-generated)',
            ],
            'exam_tables' => [
                '{exam_table}'         => 'Individual exam tables (when multiple exams selected)',
                '{aggregated_table}'   => 'Aggregated result table across multiple exams',
            ],
            'summary' => [
                '{overall_summary}'    => 'Overall summary across all exams',
                '{exam_summary}'       => 'Exam Summary box (total marks, percentage, GPA, grade, position, attendance, result)',
                '{exam_summary_one_row}' => 'Exam Summary one row (total marks, percentage, GPA, grade, position, attendance, result)',
                '{exam_wise_summary}'  => 'Exam Wise Summary table (exam, total, percentage, GPA, grade, position)',
                '{optional_summary}'   => 'Optional Subject summary box (Total Compulsory Subjects, Total GP, Optional Subject Bonus, Final GPA)',
                '{optional_summary_horizontal}' => 'Optional Subject summary horizontal (flexbox layout)',
                '{attendance_summary}' => 'Attendance Summary table (Working Days, Present Days, Absent Days, Attendance Percentage with color coding)',
            ],
            'promotion' => [
                '{promotion_info}'     => 'Promotion/Remedial information',
            ],
            'date' => [
                '{current_date}'       => 'Current Date',
                '{current_year}'       => 'Current Year',
            ],
        ];

        // Default template
        $data['default_template'] = '<div style="text-align:center; margin-bottom:20px;">
    <h2>{school_name}</h2>
    <p>{school_address} | {school_phone} | {school_email}</p>
    <hr>
    <h3>Academic Result Card</h3>
    <p><strong>Exam:</strong> {exam_name} | <strong>Class:</strong> {class_name} | <strong>Session:</strong> {session_name}</p>
</div>

<div style="margin-bottom:15px;">
    <table style="width:100%;">
        <tr>
            <td><strong>Student Name:</strong> {student_name}</td>
            <td><strong>Roll No:</strong> {roll_no}</td>
        </tr>
        <tr>
            <td><strong>Student Code:</strong> {student_code}</td>
            <td><strong>Section:</strong> {section_name}</td>
        </tr>
    </table>
</div>

{subject_table}

<div style="margin-top:15px;">
    <table style="width:100%;">
        <tr>
            <td><strong>Total Marks:</strong> {total_marks}</td>
            <td><strong>Obtained Marks:</strong> {obtained_marks}</td>
            <td><strong>Percentage:</strong> {percentage}%</td>
        </tr>
        <tr>
            <td><strong>GPA:</strong> {gpa}</td>
            <td><strong>Grade:</strong> {grade}</td>
            <td><strong>Result:</strong> {result_status}</td>
        </tr>
        <tr>
            <td><strong>Class Rank:</strong> {class_rank}</td>
            <td><strong>Attendance:</strong> {attendance}%</td>
            <td></td>
        </tr>
    </table>
</div>

<div style="margin-top:15px;">
    <p><strong>Teacher Remarks:</strong> {teacher_remarks}</p>
    <p><strong>Principal Remarks:</strong> {principal_remarks}</p>
</div>

<div style="margin-top:30px; text-align:center;">
    <p>_________________________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_________________________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_________________________</p>
    <p>Class Teacher&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Principal&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Guardian</p>
</div>

<div style="margin-top:10px; text-align:center;">
    <p><small>Generated on: {current_date}</small></p>
</div>';

        // If editing an existing template, load it and use its school
        if ($template_id > 0) {
            $template = $this->findManageableTemplate($template_id);
            
            if ($template) {
                $data['edit_template'] = $template;
                // Use the template's school when editing
                $data['selected_school'] = $template->school_id ?? 0;
            }
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\templates\builder', $data)
            . view('footer', $footer_data);
    }

    /**
     * Save template
     */
    public function save()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Platform administration is not tied to a school subscription.
        if (!$this->isSaasAdmin()) {
            $subscription_check = check_subscription('examination/templates');
            if ($subscription_check) {
                return $subscription_check;
            }
        }

        $user_id = $this->getUserId();
        $template_id = (int) $this->request->getPost('template_id');
        $school_id = (int) $this->request->getPost('school_id');
        $template_name = $this->request->getPost('template_name');
        $template_type = $this->request->getPost('template_type');
        $orientation = $this->request->getPost('orientation');
        $template_content = $this->request->getPost('template_content');
        $template_style = $this->request->getPost('template_style');
        $is_default = $this->request->getPost('is_default') ? 1 : 0;
        $support_multiple_exams = $this->request->getPost('support_multiple_exams') ? 1 : 0;
        $support_aggregated_result = $this->request->getPost('support_aggregated_result') ? 1 : 0;
        $remove_template_bg = $this->request->getPost('remove_template_bg') ? 1 : 0;
        $remove_class_teacher_signature = $this->request->getPost('remove_class_teacher_signature') ? 1 : 0;
        $remove_principal_signature = $this->request->getPost('remove_principal_signature') ? 1 : 0;
        $save_back = $this->request->getGet('back') || $this->request->getPost('save_back');

        if (!$template_name || !$template_content) {
            return redirect()->to('examination/templates/builder')->with('error', 'Template name and content are required.');
        }

        if (!$this->canManageSchool($school_id)) {
            return redirect()->back()->withInput()->with('error', 'Please select a school you are allowed to manage.');
        }

        $existingTemplate = $template_id > 0 ? $this->findManageableTemplate($template_id) : null;
        if ($template_id > 0 && !$existingTemplate) {
            return redirect()->to('examination/templates')->with('error', 'Template not found or access denied.');
        }

        // If setting as default, unset other defaults for this user
        if ($is_default) {
            $defaultQuery = $this->TemplateModel
                ->where('school_id', $school_id)
                ->where('template_type', $template_type)
                ->where('support_multiple_exams', $support_multiple_exams);
            if (!$this->isSaasAdmin()) {
                $defaultQuery->where('school_owner_uid', $user_id);
            }
            $defaultQuery
                ->set('is_default', 0)
                ->update();
        }

        $saveData = [
            'school_id' => $school_id,
            'school_owner_uid' => $existingTemplate->school_owner_uid ?? $user_id,
            'template_name' => $template_name,
            'template_type' => $template_type,
            'orientation' => $orientation ?: null,
            'template_content' => $template_content,
            'template_style' => $template_style,
            'is_default' => $is_default,
            'support_multiple_exams' => $support_multiple_exams,
            'support_aggregated_result' => $support_aggregated_result,
        ];

        // Handle template background image upload
        $file = $this->request->getFile('template_bg');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                return redirect()->back()->withInput()->with('error', 'Invalid image type. Allowed: JPG, PNG, GIF, WebP.');
            }

            $uploadPath = ROOTPATH . 'public/uploads/templates';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Delete old background if exists
            if ($template_id > 0) {
                $existing = $this->TemplateModel->find($template_id);
                if ($existing && !empty($existing->template_bg)) {
                    $oldFile = $uploadPath . '/' . $existing->template_bg;
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
            }

            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);
            $saveData['template_bg'] = $newName;
        } elseif ($remove_template_bg && $template_id > 0) {
            // Remove existing background
            $existing = $this->TemplateModel->find($template_id);
            if ($existing && !empty($existing->template_bg)) {
                $oldFile = ROOTPATH . 'public/uploads/templates/' . $existing->template_bg;
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }
            $saveData['template_bg'] = null;
        }

        // Handle class teacher signature upload
        $classTeacherFile = $this->request->getFile('class_teacher_signature');
        if ($classTeacherFile && $classTeacherFile->isValid() && !$classTeacherFile->hasMoved()) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($classTeacherFile->getMimeType(), $allowedTypes)) {
                return redirect()->back()->withInput()->with('error', 'Invalid image type for class teacher signature. Allowed: JPG, PNG, GIF, WebP.');
            }
            $uploadPath = ROOTPATH . 'public/uploads/templates';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            if ($template_id > 0) {
                $existing = $this->TemplateModel->find($template_id);
                if ($existing && !empty($existing->class_teacher_signature)) {
                    $oldFile = $uploadPath . '/' . $existing->class_teacher_signature;
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
            }
            $newName = $classTeacherFile->getRandomName();
            $classTeacherFile->move($uploadPath, $newName);
            $saveData['class_teacher_signature'] = $newName;
        } elseif ($remove_class_teacher_signature && $template_id > 0) {
            $existing = $this->TemplateModel->find($template_id);
            if ($existing && !empty($existing->class_teacher_signature)) {
                $oldFile = ROOTPATH . 'public/uploads/templates/' . $existing->class_teacher_signature;
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }
            $saveData['class_teacher_signature'] = null;
        }

        // Handle principal signature upload
        $principalFile = $this->request->getFile('principal_signature');
        if ($principalFile && $principalFile->isValid() && !$principalFile->hasMoved()) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($principalFile->getMimeType(), $allowedTypes)) {
                return redirect()->back()->withInput()->with('error', 'Invalid image type for principal signature. Allowed: JPG, PNG, GIF, WebP.');
            }
            $uploadPath = ROOTPATH . 'public/uploads/templates';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            if ($template_id > 0) {
                $existing = $this->TemplateModel->find($template_id);
                if ($existing && !empty($existing->principal_signature)) {
                    $oldFile = $uploadPath . '/' . $existing->principal_signature;
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
            }
            $newName = $principalFile->getRandomName();
            $principalFile->move($uploadPath, $newName);
            $saveData['principal_signature'] = $newName;
        } elseif ($remove_principal_signature && $template_id > 0) {
            $existing = $this->TemplateModel->find($template_id);
            if ($existing && !empty($existing->principal_signature)) {
                $oldFile = ROOTPATH . 'public/uploads/templates/' . $existing->principal_signature;
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }
            $saveData['principal_signature'] = null;
        }

        $redirectTemplateId = $template_id;
        
        if ($template_id > 0) {
            // Update existing template
            $this->TemplateModel->update($template_id, $saveData);
        } else {
            // Insert new template
            $this->TemplateModel->insert($saveData);
            $redirectTemplateId = $this->TemplateModel->getInsertID();
        }

        // Redirect based on save_back parameter
        if ($save_back) {
            return redirect()->to('examination/templates')->with('success', 'Template saved successfully.');
        }
        
        return redirect()->to('examination/templates/builder?template_id=' . $redirectTemplateId)->with('success', 'Template saved successfully.');
    }

    /**
     * Delete a template
     */
    public function delete($id = null)
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        if (!$this->isSaasAdmin()) {
            $subscription_check = check_subscription('examination/templates');
            if ($subscription_check) {
                return $this->jsonResponse(['status' => false, 'message' => 'Your subscription does not allow this action.']);
            }
        }

        if (!$id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Template ID missing.']);
        }

        // Clean up files before deleting
        $existing = $this->findManageableTemplate((int) $id);

        if (!$existing) {
            return $this->jsonResponse(['status' => false, 'message' => 'Template not found or access denied.']);
        }

        if ($existing) {
            $filesToDelete = ['template_bg', 'class_teacher_signature', 'principal_signature'];
            foreach ($filesToDelete as $field) {
                if (!empty($existing->{$field})) {
                    $filePath = ROOTPATH . 'public/uploads/templates/' . $existing->{$field};
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }

        $this->TemplateModel->delete((int) $id);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Template deleted successfully.',
        ]);
    }

    /**
     * Get template for editing (AJAX)
     */
    public function getTemplate($id = null)
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        if (!$id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Template ID missing.']);
        }

        $template = $this->findManageableTemplate((int) $id);

        if ($template) {
            return $this->jsonResponse([
                'status' => true,
                'template' => $template,
            ]);
        }

        return $this->jsonResponse(['status' => false, 'message' => 'Template not found.']);
    }
}

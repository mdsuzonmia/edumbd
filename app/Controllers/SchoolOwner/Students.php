<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentGuardianModel;
use App\Models\StudentAddressModel;
use App\Models\StudentDocumentModel;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsDepartmentModel;
use App\Models\AcademicsCategoryModel;
use App\Models\UserModel;
use App\Models\CustomField\CustomFieldEntityModel;
use App\Models\CustomField\CustomFieldValueModel;
use App\Models\CustomField\CustomFieldModel;
use Dompdf\Dompdf;

class Students extends BaseController
{
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentGuardianModel $GuardianModel;
    protected StudentAddressModel $AddressModel;
    protected StudentDocumentModel $DocumentModel;
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsDepartmentModel $DepartmentModel;
    protected AcademicsCategoryModel $CategoryModel;
    protected \App\Models\AcademicsShiftModel $ShiftModel;
    protected UserModel $UserModel;
    protected CustomFieldEntityModel $EntityModel;
    protected CustomFieldValueModel $ValueModel;
    protected CustomFieldModel $FieldModel;

    public function __construct()
    {
        $this->StudentModel       = new StudentModel();
        $this->EnrollmentModel    = new StudentEnrollmentModel();
        $this->GuardianModel      = new StudentGuardianModel();
        $this->AddressModel       = new StudentAddressModel();
        $this->DocumentModel      = new StudentDocumentModel();
        $this->SchoolModel        = new SchoolModel();
        $this->YearModel          = new AcademicsYearModel();
        $this->ClassModel         = new AcademicsClassesModel();
        $this->SectionModel       = new AcademicsSectionModel();
        $this->DepartmentModel    = new AcademicsDepartmentModel();
        $this->CategoryModel      = new AcademicsCategoryModel();
        $this->ShiftModel         = new \App\Models\AcademicsShiftModel();
        $this->UserModel          = new UserModel();
        $this->EntityModel        = new CustomFieldEntityModel();
        $this->ValueModel         = new CustomFieldValueModel();
        $this->FieldModel         = new CustomFieldModel();
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

    protected function getAllowedSchoolId(int $schoolId): int
    {
        if (!$schoolId) {
            return 0;
        }

        $schoolIds = array_map(static function ($school) {
            return (int) $school->id;
        }, $this->getUserSchools());

        return in_array($schoolId, $schoolIds, true) ? $schoolId : 0;
    }

    protected function isStudentAccountEnabled(int $school_id): bool
    {
        return $this->isSchoolSettingEnabled($school_id, 'student_account_enabled');
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

    protected function getSchoolParam(int $school_id, string $key, $default = '')
    {
        $school = $this->SchoolModel->find($school_id);
        if (!$school || empty($school->params)) {
            return $default;
        }
        $params = json_decode($school->params, true);
        return $params[$key] ?? $default;
    }

    protected function applyOwnerFilter()
    {
        $this->StudentModel->where('students.school_owner_uid', $this->getUserId());
    }

    protected function verifyOwnership(int $id): ?object
    {
        return $this->StudentModel
            ->where('id', $id)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    protected function verifyOwnershipByToken(string $token): ?object
    {
        return $this->StudentModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    protected function getActiveOptions(int $school_id, string $modelProperty): array
    {
        $list = [];
        $records = $this->{$modelProperty}
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        foreach ($records as $r) {
            $list[$r->id] = $r->title;
        }
        return $list;
    }

    protected function getStudentStatusList(): array
    {
        return [
            'Active'      => 'Active',
            'Inactive'    => 'Inactive',
            'Graduated'   => 'Graduated',
            'Transferred' => 'Transferred',
            'Dropped'     => 'Dropped',
            'Suspended'   => 'Suspended',
        ];
    }

    protected function getAdmissionSourceList(): array
    {
        return [
            'Online'   => 'Online',
            'Offline'  => 'Offline',
            'Transfer' => 'Transfer',
            'Referral' => 'Referral',
        ];
    }

    protected function getStudentEnrollmentsWithTitles(int $studentId): array
    {
        return $this->EnrollmentModel
            ->select('student_enrollments.*, academic_years.title AS session_title, academic_classes.title AS class_title, academic_sections.title AS section_title, academic_departments.title AS department_title, academic_category.title AS category_title, academic_shift.title AS shift_title')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = student_enrollments.section_id', 'left')
            ->join('academic_departments', 'academic_departments.id = student_enrollments.department_id', 'left')
            ->join('academic_category', 'academic_category.id = student_enrollments.category_id', 'left')
            ->join('academic_shift', 'academic_shift.id = student_enrollments.shift_id', 'left')
            ->where('student_enrollments.student_id', $studentId)
            ->orderBy('student_enrollments.session_id', 'DESC')
            ->orderBy('student_enrollments.id', 'DESC')
            ->findAll();
    }

    protected function getOfficeCopyStudents(array $filters): array
    {
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(static function ($school) {
            return (int) $school->id;
        }, $userSchools);

        if (empty($schoolIds)) {
            return [];
        }

        $schoolId = $this->getAllowedSchoolId((int) ($filters['school_id'] ?? 0));
        $yearId   = (int) ($filters['year_id'] ?? 0);
        $classId  = (int) ($filters['class_id'] ?? 0);
        $status   = $filters['status'] ?? '';
        $text     = trim((string) ($filters['text'] ?? ''));

        $this->StudentModel
            ->select('students.*, schools.name AS school_name, student_enrollments.session_id, student_enrollments.class_id, student_enrollments.section_id, student_enrollments.department_id, student_enrollments.category_id, student_enrollments.shift_id, student_enrollments.roll_no, academic_years.title AS session_title, academic_classes.title AS class_title, academic_sections.title AS section_title, academic_departments.title AS department_title, academic_category.title AS category_title, academic_shift.title AS shift_title')
            ->join('schools', 'schools.id = students.school_id', 'left')
            ->join('student_enrollments', 'student_enrollments.student_id = students.id', 'left')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = student_enrollments.section_id', 'left')
            ->join('academic_departments', 'academic_departments.id = student_enrollments.department_id', 'left')
            ->join('academic_category', 'academic_category.id = student_enrollments.category_id', 'left')
            ->join('academic_shift', 'academic_shift.id = student_enrollments.shift_id', 'left')
            ->whereIn('students.school_id', $schoolIds);
        $this->applyOwnerFilter();

        if ($schoolId && in_array($schoolId, $schoolIds, true)) {
            $this->StudentModel->where('students.school_id', $schoolId);
        }

        if ($yearId) {
            $this->StudentModel->where('student_enrollments.session_id', $yearId);
        }

        if ($classId) {
            $this->StudentModel->where('student_enrollments.class_id', $classId);
        }

        if ($text !== '') {
            $this->StudentModel->groupStart()
                ->like('students.first_name', $text)
                ->orLike('students.last_name', $text)
                ->orLike('students.student_code', $text)
                ->orLike('students.phone', $text)
                ->orLike('students.email', $text)
                ->groupEnd();
        }

        if ($status !== '') {
            if ($status === 'active') {
                $this->StudentModel->where('students.student_status', 'Active');
            } elseif ($status === 'inactive') {
                $this->StudentModel->where('students.student_status', 'Inactive');
            } elseif (in_array($status, ['0', '1', '2'], true)) {
                $this->StudentModel->where('students.status', $status);
            }
        }

        $rows = $this->StudentModel
            ->orderBy('schools.name', 'ASC')
            ->orderBy('academic_classes.title', 'ASC')
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->orderBy('students.first_name', 'ASC')
            ->orderBy('student_enrollments.id', 'DESC')
            ->findAll();

        $students = [];
        foreach ($rows as $row) {
            if (!isset($students[$row->id])) {
                $students[$row->id] = $row;
            }
        }

        return array_values($students);
    }

    protected function getOfficeCopyData(array $filters = []): array
    {
        $schoolId = $this->getAllowedSchoolId((int) ($filters['school_id'] ?? 0));
        $yearId   = (int) ($filters['year_id'] ?? 0);
        $classId  = (int) ($filters['class_id'] ?? 0);

        return [
            'students' => $this->getOfficeCopyStudents($filters),
            'school'   => $schoolId ? $this->SchoolModel->find($schoolId) : null,
            'year'     => $yearId ? $this->YearModel->find($yearId) : null,
            'class'    => $classId ? $this->ClassModel->find($classId) : null,
            'filters'  => $filters,
        ];
    }

    public function index()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        // Get ccheck student limit and redirect if limit is reached using check_subscription_plan('student_limit')
        $plan_status = check_subscription_plan('student_limit');
        
        $header_data = [
            'page_title' => lang('Student.page_title_student_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $text   = $this->request->getGet('text');
        $status = $this->request->getGet('status');
        $show   = $this->request->getGet('show');
        $school_id = (int) $this->request->getGet('school_id') ?: 0;
        $year_id = (int) $this->request->getGet('year_id') ?: 0;
        $class_id = (int) $this->request->getGet('class_id') ?: 0;

        $data = compact('text', 'status', 'show', 'year_id', 'class_id');
		$data['plan_status'] = '';

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);
        if ($school_id && !in_array($school_id, $schoolIds)) {
            $school_id = 0;
            $year_id = 0;
            $class_id = 0;
        }

        $this->StudentModel
            ->select('students.*')
            ->whereIn('students.school_id', $schoolIds);
        $this->applyOwnerFilter();

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->StudentModel->where('students.school_id', $school_id);
        }

        if ($year_id || $class_id) {
            $this->StudentModel->join('student_enrollments', 'student_enrollments.student_id = students.id', 'inner');
            if ($year_id) {
                $this->StudentModel->where('student_enrollments.session_id', $year_id);
            }
            if ($class_id) {
                $this->StudentModel->where('student_enrollments.class_id', $class_id);
            }
            $this->StudentModel->distinct();
        }

        if ($text) {
            $this->StudentModel->groupStart()
                ->like('students.first_name', $text)
                ->orLike('students.last_name', $text)
                ->orLike('students.student_code', $text)
                ->orLike('students.phone', $text)
                ->orLike('students.email', $text)
                ->groupEnd();
        }

        if (isset($status) && $status !== '') {
            if ($status === 'active') {
                $this->StudentModel->where('students.student_status', 'Active');
            } elseif ($status === 'inactive') {
                $this->StudentModel->where('students.student_status', 'Inactive');
            } elseif (in_array($status, ['0', '1', '2'])) {
                $this->StudentModel->where('students.status', $status);
            }
        }

        $this->StudentModel->orderBy('students.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->StudentModel->paginate($perPage);
        $data['pager']           = $this->StudentModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;
        $data['selected_year']   = $year_id;
        $data['selected_class']  = $class_id;
        $data['year_list']       = $school_id ? $this->getActiveOptions($school_id, 'YearModel') : [];
        $data['class_list']      = $school_id ? $this->getActiveOptions($school_id, 'ClassModel') : [];
        $data['student_statuses'] = $this->getStudentStatusList();
        if (!$plan_status['status']) {
            $data['plan_status'] = message_generator('error', $plan_status['message']);
        }

        return view('header', $header_data)
            . view('school_owner/students/list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('Student.page_title_student_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']              = [];
        $data['is_edit']                = false;
        $data['school_list']            = $this->getSchoolDropdown();
        $data['year_list']              = [];
        $data['class_list']             = [];
        $data['section_list']           = [];
        $data['department_list']        = [];
        $data['category_list']          = [];
        $data['student_statuses']       = $this->getStudentStatusList();
        $data['admission_sources']      = $this->getAdmissionSourceList();
        $data['gender_list']            = ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'];
        $data['blood_groups']           = ['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-'];
        $data['guardians']              = [];
        $data['student_account_enabled'] = false;
        $data['login_user']             = null;
        $data['login_username']         = '';
        $data['login_password']         = '';
        $data['login_email']            = '';
        $data['login_name']             = '';
        $data['login_phone']            = '';

        return view('header', $header_data)
            . view('school_owner/students/form', $data)
            . view('footer', $footer_data);
    }

    public function edit($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $student = $this->verifyOwnershipByToken($token);
        if (!$student) {
            return redirect()->to('school-owner/students')
                ->with('error', 'Student not found.');
        }

        $header_data['page_title'] = lang('Student.page_title_student_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $school_id = (int) $student->school_id;
        $post_data = (array) $student;

        // Get all enrollments (ordered by year/session)
        $enrollments = $this->EnrollmentModel
            ->select('student_enrollments.*, academic_years.title AS session_title, academic_classes.title AS class_title, academic_sections.title AS section_title')
            ->join('academic_years', 'academic_years.id = student_enrollments.session_id', 'left')
            ->join('academic_classes', 'academic_classes.id = student_enrollments.class_id', 'left')
            ->join('academic_sections', 'academic_sections.id = student_enrollments.section_id', 'left')
            ->where('student_enrollments.student_id', $student->id)
            ->orderBy('student_enrollments.session_id', 'DESC')
            ->findAll();

        // Ensure all enrollments are objects (join queries may return arrays)
        foreach ($enrollments as $key => $enr) {
            if (is_array($enr)) {
                $enrollments[$key] = (object) $enr;
            }
        }

        // Set the first (latest) enrollment as the primary one for backward compatibility
        $enrollment = !empty($enrollments) ? $enrollments[0] : null;
        if ($enrollment) {
            // Handle both object and array returns from join queries
            $e = is_object($enrollment) ? $enrollment : (object) $enrollment;
            $post_data['session_id']      = $e->session_id;
            $post_data['class_id']        = $e->class_id;
            $post_data['section_id']      = $e->section_id;
            $post_data['department_id']   = $e->department_id;
            $post_data['category_id']     = $e->category_id;
            $post_data['shift_id']        = $e->shift_id;
            $post_data['roll_no']         = $e->roll_no;
        }

        // Get guardians
        $guardians = $this->GuardianModel
            ->where('student_id', $student->id)
            ->findAll();
        $post_data['guardians'] = $guardians;

        // Get address
        $address = $this->AddressModel
            ->where('student_id', $student->id)
            ->first();
        if ($address) {
            $post_data['present_address']   = $address->present_address;
            $post_data['permanent_address'] = $address->permanent_address;
            $post_data['city']              = $address->city;
            $post_data['district']          = $address->district;
            $post_data['state']             = $address->state;
            $post_data['postal_code']       = $address->postal_code;
            $post_data['address_country']   = $address->country;
        }

        // Get documents
        $documents = $this->DocumentModel
            ->where('student_id', $student->id)
            ->findAll();
        $post_data['documents'] = $documents;

        // Get login user if exists
        $loginUser = null;
        $login_username = '';
        $login_email = '';
        $login_name = '';
        $login_phone = '';
        if (!empty($student->user_id)) {
            $loginUser = $this->UserModel->find($student->user_id);
            if ($loginUser) {
                $login_username = $loginUser->email;
                $login_email    = $loginUser->email;
                $login_name     = $loginUser->name;
                $login_phone    = $loginUser->phone ?? '';
            }
        }

        $student_account_enabled = $this->isStudentAccountEnabled($school_id);

        $data['enrollments']            = $enrollments;
        $data['post_data']              = $post_data;
        $data['is_edit']                = true;
        $data['token']                  = $student->token;
        $data['school_list']            = $this->getSchoolDropdown();
        $data['year_list']              = $school_id ? $this->getActiveOptions($school_id, 'YearModel') : [];
        $data['class_list']             = $school_id ? $this->getActiveOptions($school_id, 'ClassModel') : [];
        $data['section_list']           = !empty($post_data['class_id']) ? $this->getActiveOptions($school_id, 'SectionModel') : [];
        $data['department_list']        = $school_id ? $this->getActiveOptions($school_id, 'DepartmentModel') : [];
        $data['category_list']          = $school_id ? $this->getActiveOptions($school_id, 'CategoryModel') : [];
        $data['shift_list']             = $school_id ? $this->getActiveOptions($school_id, 'ShiftModel') : [];
        $data['student_statuses']       = $this->getStudentStatusList();
        $data['admission_sources']      = $this->getAdmissionSourceList();
        $data['gender_list']            = ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'];
        $data['blood_groups']           = ['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-'];
        $data['guardians']              = $guardians ?? [];
        $data['student_account_enabled'] = $student_account_enabled;
        $data['login_user']             = $loginUser;
        $data['login_username']         = $login_username;
        $data['login_password']         = '';
        $data['login_email']            = $login_email;
        $data['login_name']             = $login_name;
        $data['login_phone']            = $login_phone;

        return view('header', $header_data)
            . view('school_owner/students/form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/students');
        if ($subscription_check) {
            return $subscription_check;
        }

        // Check subscription plan
        $plan_status = check_subscription_plan('student_limit');
        if (!$plan_status['status']) {
            return redirect()->to('school-owner/students')->with('error', $plan_status['message']);
        }

        $post_data = $this->request->getPost();
        
        // Resolve ID from token (if editing), or null for new students
        $id = null;
        $existing = null;
        $token = !empty($post_data['token']) ? $post_data['token'] : null;
        if ($token) {
            $existing = $this->verifyOwnershipByToken($token);
            if (!$existing) {
                return redirect()->to('school-owner/students')
                    ->with('error', 'Student not found.');
            }
            $id = (int) $existing->id;
        }

        // Validate basic student info
        $validationRule = [
            'first_name' => 'required|max_length[100]',
            'school_id'  => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $id, $this->validator);
        }

        // Validate that at least one enrollment has session_id and class_id
        $enrollments_input = $post_data['enrollments'] ?? [];
        $hasValidEnrollment = false;
        foreach ($enrollments_input as $enr) {
            if (!empty($enr['session_id']) && !empty($enr['class_id'])) {
                $hasValidEnrollment = true;
                break;
            }
        }

        if (!$hasValidEnrollment) {
            $this->validator->setError('enrollments', 'At least one academic year with Year and Class is required.');
            return $this->renderForm($post_data, $id, $this->validator);
        }

        $user_id   = $this->getUserId();
        $school_id = (int) $post_data['school_id'];
        $now       = date('Y-m-d H:i:s');

        // Handle photo upload
        $photo     = $this->request->getFile('photo');
        $photoName = null;
        if ($photo && $photo->isValid() && !$photo->hasMoved()) {
            $photoName = $photo->getRandomName();
            $photo->move(WRITEPATH . 'uploads', $photoName);
        }

        // Generate alias from name
        $alias = $this->generateAlias($post_data['first_name'], $post_data['middle_name'] ?? '', $post_data['last_name'] ?? '');

        // Get token - use existing for updates, generate for new
        if (!$token) {
            $token = $this->generateToken();
        }

        // On update, preserve existing student_code and registration_no if form fields are empty
        $existingStudentCode = $id && $existing ? $existing->student_code : '';
        $existingRegNo = $id && $existing ? $existing->registration_no : '';

        $studentData = [
            'school_id'       => $school_id,
            'school_owner_uid' => $user_id,
            'student_code'    => !empty($post_data['student_code']) ? $post_data['student_code'] : ($id ? $existingStudentCode : $this->generateStudentCode($school_id)),
            'registration_no' => !empty($post_data['registration_no']) ? $post_data['registration_no'] : ($id ? $existingRegNo : $this->generateRegistrationNo($school_id)),
            'first_name'      => $post_data['first_name'],
            'middle_name'     => $post_data['middle_name'] ?? null,
            'last_name'       => $post_data['last_name'] ?? null,
            'alias'           => $alias,
            'token'           => $token,
            'gender'          => $post_data['gender'] ?? null,
            'date_of_birth'   => $post_data['date_of_birth'] ?? null,
            'blood_group'     => $post_data['blood_group'] ?? null,
            'religion'        => $post_data['religion'] ?? null,
            'nationality'     => $post_data['nationality'] ?? null,
            'phone'           => $post_data['phone'] ?? null,
            'email'           => $post_data['email'] ?? null,
            'admission_date'  => $post_data['admission_date'] ?? null,
            'student_status'  => $post_data['student_status'] ?? 'Active',
            'admission_source' => $post_data['admission_source'] ?? 'Offline',
            'student_qr_code' => $post_data['student_qr_code'] ?? ($id ? null : $this->generateQRCode($school_id)),
            'rfid_number'     => !empty($post_data['rfid_number']) ? $post_data['rfid_number'] : null,
            'status'          => 1,
            'updated_at'      => $now,
            'updated_by'      => $user_id,
        ];

        if ($photoName) {
            $studentData['photo'] = $photoName;
        } elseif ($id) {
            $existingStudent = $this->StudentModel->find($id);
            if ($existingStudent && $existingStudent->photo) {
                $studentData['photo'] = $existingStudent->photo;
            }
        }

        if ($id) {
            $this->StudentModel->update($id, $studentData);
            $student_id = $id;
        } else {
            $studentData['created_at'] = $now;
            $studentData['created_by'] = $user_id;
            $this->StudentModel->insert($studentData);
            $student_id = (int) $this->StudentModel->insertID();
        }

        // Create / Update User Account if student accounts are enabled
        $student_account_enabled = $this->isStudentAccountEnabled($school_id);
        if ($student_account_enabled) {
            $login_email    = $post_data['login_email'] ?? '';
            $login_password = $post_data['login_password'] ?? '';
            $login_name     = $post_data['login_name'] ?? ($post_data['first_name'] . ' ' . ($post_data['last_name'] ?? ''));
            $login_phone    = $post_data['login_phone'] ?? $post_data['phone'] ?? null;

            if (!empty($login_email) && !empty($login_password)) {
                $existingUser = null;
                $existingStudentRecord = $this->StudentModel->find($student_id);
                if ($existingStudentRecord && !empty($existingStudentRecord->user_id)) {
                    $existingUser = $this->UserModel->find($existingStudentRecord->user_id);
                }

                $userData = [
                    'name'       => $login_name,
                    'email'      => $login_email,
                    'phone'      => $login_phone,
                    'status'     => 1,
                    'updated_at' => $now,
                    'updated_by' => $user_id,
                ];

                if (!empty($login_password)) {
                    $userData['password'] = password_hash($login_password, PASSWORD_DEFAULT);
                }

                if ($existingUser) {
                    $this->UserModel->update($existingUser->id, $userData);
                    $user_account_id = $existingUser->id;
                } else {
                    $userData['password']     = password_hash($login_password, PASSWORD_DEFAULT);
                    $userData['created_at']   = $now;
                    $userData['created_by']   = $user_id;
                    $user_account_id = $this->UserModel->insert($userData);

                    // Assign student role
                    $this->assignStudentRole($user_account_id, $school_id);
                }

                // Link user to student record
                $this->StudentModel->update($student_id, ['user_id' => $user_account_id]);
            }
        }

        // Save / update multiple enrollments (one per academic year)
        $enrollments_input = $post_data['enrollments'] ?? [];
        $saved_enrollment_ids = [];
        
        // Collect existing enrollment IDs for this student
        $existing_enrollments = $this->EnrollmentModel
            ->where('student_id', $student_id)
            ->findAll();
        $existing_enrollment_ids = [];
        foreach ($existing_enrollments as $ee) {
            $existing_enrollment_ids[] = (int) $ee->id;
        }

        // Build a map of existing enrollments by session_id for quick lookup
        $existingBySessionMap = [];
        foreach ($existing_enrollments as $ee) {
            $existingBySessionMap[(int) $ee->session_id] = $ee;
        }

        $processed_session_ids = [];
        
        foreach ($enrollments_input as $enr) {
            if (empty($enr['session_id']) || empty($enr['class_id'])) {
                continue; // Skip incomplete rows
            }

            $session_id = (int) $enr['session_id'];
            $class_id   = (int) $enr['class_id'];

            // Skip if we've already processed this session/year for this student
            if (isset($processed_session_ids[$session_id])) {
                continue;
            }

            $enrollmentData = [
                'school_id'       => $school_id,
                'school_owner_uid' => $user_id,
                'student_id'      => $student_id,
                'session_id'      => $session_id,
                'class_id'        => $class_id,
                'section_id'      => !empty($enr['section_id']) ? (int) $enr['section_id'] : null,
                'department_id'   => !empty($enr['department_id']) ? (int) $enr['department_id'] : null,
                'category_id'     => !empty($enr['category_id']) ? (int) $enr['category_id'] : null,
                'shift_id'        => !empty($enr['shift_id']) ? (int) $enr['shift_id'] : null,
                'roll_no'         => $enr['roll_no'] ?? null,
                'registration_no' => $enr['registration_no'] ?? null,
                'status'          => 1,
                'updated_at'      => $now,
                'updated_by'      => $user_id,
            ];

            // Check if this enrollment has an ID (editing existing row)
            $enrollment_id = !empty($enr['id']) ? (int) $enr['id'] : null;
            
            if ($enrollment_id && in_array($enrollment_id, $existing_enrollment_ids)) {
                // Update existing enrollment by ID
                $this->EnrollmentModel->update($enrollment_id, $enrollmentData);
                $saved_enrollment_ids[] = $enrollment_id;
                $processed_session_ids[$session_id] = $enrollment_id;
            } elseif (isset($existingBySessionMap[$session_id])) {
                // Update existing enrollment matched by session_id (handles duplicate key)
                $existing = $existingBySessionMap[$session_id];
                $this->EnrollmentModel->update($existing->id, $enrollmentData);
                $saved_enrollment_ids[] = (int) $existing->id;
                $processed_session_ids[$session_id] = (int) $existing->id;
            } else {
                // Insert new enrollment
                $enrollmentData['created_at'] = $now;
                $enrollmentData['created_by'] = $user_id;
                $this->EnrollmentModel->insert($enrollmentData);
                $new_id = (int) $this->EnrollmentModel->insertID();
                $saved_enrollment_ids[] = $new_id;
                $processed_session_ids[$session_id] = $new_id;
            }
        }

        // Delete enrollments that were removed from the form
        $enrollments_to_delete = array_diff($existing_enrollment_ids, $saved_enrollment_ids);
        if (!empty($enrollments_to_delete)) {
            $this->EnrollmentModel->whereIn('id', $enrollments_to_delete)->delete();
        }

        // Save guardians
        $this->GuardianModel->where('student_id', $student_id)->delete();
        $guardian_names       = $post_data['guardian_name'] ?? [];
        $guardian_relations   = $post_data['guardian_relation'] ?? [];
        $guardian_phones      = $post_data['guardian_phone'] ?? [];
        $guardian_emails      = $post_data['guardian_email'] ?? [];
        $guardian_occupations = $post_data['guardian_occupation'] ?? [];

        if (!empty($guardian_names) && is_array($guardian_names)) {
            foreach ($guardian_names as $index => $gname) {
                if (empty($gname)) continue;
                $this->GuardianModel->insert([
                    'school_id'     => $school_id,
                    'student_id'    => $student_id,
                    'relation_type' => $guardian_relations[$index] ?? 'Guardian',
                    'name'          => $gname,
                    'phone'         => $guardian_phones[$index] ?? null,
                    'email'         => $guardian_emails[$index] ?? null,
                    'occupation'    => $guardian_occupations[$index] ?? null,
                    'created_at'    => $now,
                ]);
            }
        }

        // Save address
        $addressData = [
            'school_id'         => $school_id,
            'student_id'        => $student_id,
            'present_address'   => $post_data['present_address'] ?? null,
            'permanent_address' => $post_data['permanent_address'] ?? null,
            'city'              => $post_data['city'] ?? null,
            'district'          => $post_data['district'] ?? null,
            'state'             => $post_data['state'] ?? null,
            'postal_code'       => $post_data['postal_code'] ?? null,
            'country'           => $post_data['address_country'] ?? null,
        ];

        $existingAddress = $this->AddressModel
            ->where('student_id', $student_id)
            ->first();

        if ($existingAddress) {
            $this->AddressModel->update($existingAddress->id, $addressData);
        } else {
            $this->AddressModel->insert($addressData);
        }

        // Handle document uploads (only process if files were actually uploaded)
        $docTypes  = $post_data['document_type'] ?? [];
        $docTitles = $post_data['document_title'] ?? [];

        if (!empty($docTypes) && is_array($docTypes)) {
            $docFiles = $this->request->getFileMultiple('documents');
            $hasNewFiles = false;
            if (!empty($docFiles)) {
                foreach ($docFiles as $df) {
                    if ($df && $df->isValid() && !$df->hasMoved()) {
                        $hasNewFiles = true;
                        break;
                    }
                }
            }

            if ($hasNewFiles) {
                // Only delete & re-insert when new files are uploaded
                $this->DocumentModel->where('student_id', $student_id)->delete();

                foreach ($docTypes as $docIndex => $docType) {
                    $docFile = $docFiles[$docIndex] ?? null;
                    $docName = null;

                    if ($docFile && $docFile->isValid() && !$docFile->hasMoved()) {
                        $docName = $docFile->getRandomName();
                        $docFile->move(WRITEPATH . 'uploads', $docName);
                    }

                    if (!empty($docType) || $docName) {
                        $this->DocumentModel->insert([
                            'school_id'      => $school_id,
                            'student_id'     => $student_id,
                            'document_type'  => $docType,
                            'document_title' => $docTitles[$docIndex] ?? null,
                            'file_name'      => $docName,
                            'file_size'      => $docName ? $docFile->getSize() : null,
                            'uploaded_by'    => $user_id,
                            'uploaded_at'    => $now,
                        ]);
                    }
                }
            }
        }

        // Save custom field values
        $customFields = $post_data['custom_fields'] ?? [];
        if (!empty($customFields) && is_array($customFields)) {
            // Get entity ID for "student"
            $studentEntity = $this->EntityModel->where('slug', 'student')->first();
            $entityId = $studentEntity ? (int) $studentEntity->id : 0;

            if ($entityId) {
                foreach ($customFields as $fieldId => $value) {
                    // Handle array values (checkbox, multiselect)
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }

                    $this->ValueModel->saveFieldValue(
                        $school_id,
                        (int) $fieldId,
                        $entityId,
                        $student_id,
                        $value !== null ? (string) $value : null
                    );
                }
            }
        }

        return redirect()->to('school-owner/students')
            ->with('success', lang('Student.sys_saved'));
    }

    protected function assignStudentRole(int $user_id, int $school_id)
    {
        $userRoleModel = new \App\Models\UserRoleModel();
        $userRoleModel->insert([
            'user_id'    => $user_id,
            'role_id'    => 4, // Student role
            'school_id'  => $school_id,
        ]);
    }

    public function update($token)
    {
        // Verify the student exists before passing through
        $student = $this->verifyOwnershipByToken($token);
        if (!$student) {
            return redirect()->to('school-owner/students')
                ->with('error', 'Student not found.');
        }
        
        // store() will resolve the ID from the token in POST data
        return $this->store();
    }

    protected function renderForm(array $post_data, ?int $id, $validation = null)
    {
        $header_data['page_title'] = $id
            ? lang('Student.page_title_student_edit')
            : lang('Student.page_title_student_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $school_id = !empty($post_data['school_id']) ? (int) $post_data['school_id'] : 0;

        $loginUser = null;
        $login_username = '';
        $login_email = '';
        $login_name = '';
        $login_phone = '';
        $student_account_enabled = $school_id ? $this->isStudentAccountEnabled($school_id) : false;

        if ($id && $student_account_enabled) {
            $studentRecord = $this->StudentModel->find($id);
            if ($studentRecord && !empty($studentRecord->user_id)) {
                $loginUser = $this->UserModel->find($studentRecord->user_id);
                if ($loginUser) {
                    $login_username = $loginUser->email;
                    $login_email    = $loginUser->email;
                    $login_name     = $loginUser->name;
                    $login_phone    = $loginUser->phone ?? '';
                }
            }
        }

        $form_data['post_data']              = $post_data;
        $form_data['is_edit']                = (bool) $id;
        $form_data['school_list']            = $this->getSchoolDropdown();
        $form_data['year_list']              = $school_id ? $this->getActiveOptions($school_id, 'YearModel') : [];
        $form_data['class_list']             = $school_id ? $this->getActiveOptions($school_id, 'ClassModel') : [];
        $form_data['section_list']           = !empty($post_data['class_id']) ? $this->getActiveOptions($school_id, 'SectionModel') : [];
        $form_data['department_list']        = $school_id ? $this->getActiveOptions($school_id, 'DepartmentModel') : [];
        $form_data['category_list']          = $school_id ? $this->getActiveOptions($school_id, 'CategoryModel') : [];
        $form_data['shift_list']             = $school_id ? $this->getActiveOptions($school_id, 'ShiftModel') : [];
        $form_data['enrollments']            = $post_data['enrollments'] ?? [];
        $form_data['student_statuses']       = $this->getStudentStatusList();
        $form_data['admission_sources']      = $this->getAdmissionSourceList();
        $form_data['gender_list']            = ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'];
        $form_data['blood_groups']           = ['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-'];
        $form_data['student_account_enabled'] = $student_account_enabled;
        $form_data['login_user']             = $loginUser;
        $form_data['login_username']         = $login_username;
        $form_data['login_password']         = '';
        $form_data['login_email']            = $login_email;
        $form_data['login_name']             = $login_name;
        $form_data['login_phone']            = $login_phone;

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('school_owner/students/form', $form_data)
            . view('footer', $footer_data);
    }

    protected function generateStudentCode(int $school_id): string
    {
        $usePrefix = $this->getSchoolParam($school_id, 'use_id_prefix', 0);
        $prefix    = $usePrefix ? $this->getSchoolParam($school_id, 'id_prefix', 'STU-') : '';
        $digit     = (int) $this->getSchoolParam($school_id, 'id_digit', 8);
        $digit     = max(1, $digit);

        $last = $this->StudentModel
            ->select('student_code')
            ->where('school_id', $school_id)
            ->orderBy('id', 'DESC')
            ->first();

        $pattern = '/\d{' . $digit . '}$/';
        if ($last && preg_match($pattern, $last->student_code, $matches)) {
            $num = (int) $matches[0] + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, $digit, '0', STR_PAD_LEFT);
    }

    protected function generateRegistrationNo(int $school_id): string
    {
        $usePrefix = $this->getSchoolParam($school_id, 'use_reg_prefix', 0);
        $prefix    = $usePrefix ? $this->getSchoolParam($school_id, 'reg_prefix', 'REG-') : '';
        $digit     = (int) $this->getSchoolParam($school_id, 'reg_digit', 8);
        $digit     = max(1, $digit);

        $last = $this->StudentModel
            ->select('registration_no')
            ->where('school_id', $school_id)
            ->orderBy('id', 'DESC')
            ->first();

        $pattern = '/\d{' . $digit . '}$/';
        if ($last && preg_match($pattern, $last->registration_no, $matches)) {
            $num = (int) $matches[0] + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, $digit, '0', STR_PAD_LEFT);
    }

    protected function generateQRCode(int $school_id): string
    {
        $usePrefix = $this->getSchoolParam($school_id, 'use_qr_prefix', 0);
        $prefix    = $usePrefix ? $this->getSchoolParam($school_id, 'qr_prefix', 'QR-') : '';
        $digit     = (int) $this->getSchoolParam($school_id, 'qr_digit', 8);
        $digit     = max(1, $digit);

        $random = strtoupper(bin2hex(random_bytes(4)));
        // Pad or truncate random part to match digit count
        if (strlen($random) > $digit) {
            $random = substr($random, 0, $digit);
        } else {
            $random = str_pad($random, $digit, '0', STR_PAD_LEFT);
        }

        return $prefix . $random;
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function generateAlias(string $firstName, string $middleName, string $lastName): string
    {
        // Combine names and convert to lowercase
        $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
        
        // Convert to lowercase and replace spaces with hyphens
        $alias = strtolower($fullName);
        $alias = preg_replace('/\s+/', '-', $alias);
        
        // Remove special characters, keep only letters, numbers, and hyphens
        $alias = preg_replace('/[^a-z0-9-]/', '', $alias);
        
        // Remove multiple consecutive hyphens
        $alias = preg_replace('/-+/', '-', $alias);
        
        // Trim hyphens from start and end
        $alias = trim($alias, '-');
        
        // If empty, use a default
        if (empty($alias)) {
            $alias = 'student-' . time();
        }
        
        return $alias;
    }

    protected function viewPrint($id)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $student = $this->verifyOwnership((int) $id);
        if (!$student) {
            return redirect()->to('school-owner/students')
                ->with('error', 'Student not found.');
        }

        $enrollments = $this->getStudentEnrollmentsWithTitles((int) $student->id);
        $enrollment  = $enrollments[0] ?? null;

        // Get guardians
        $guardians = $this->GuardianModel
            ->where('student_id', $student->id)
            ->findAll();

        // Get address
        $address = $this->AddressModel
            ->where('student_id', $student->id)
            ->first();

        // Get documents
        $documents = $this->DocumentModel
            ->where('student_id', $student->id)
            ->findAll();

        // Get custom field values grouped by group_name
        $customFields = [];
        $customFieldsByGroup = [];
        $studentEntity = $this->EntityModel->where('slug', 'student')->first();
        if ($studentEntity && !empty($student->school_id)) {
            helper('customfield');
            $fields = $this->FieldModel->getFields((int) $student->school_id, (int) $studentEntity->id, true);
            foreach ($fields as $field) {
                $value = get_custom_field_value((int) $student->school_id, (int) $studentEntity->id, (int) $student->id, $field->id);
                $fieldData = [
                    'field' => $field,
                    'value' => $value
                ];
                $customFields[] = $fieldData;
                $groupName = $field->group_name ?? 'Other';
                $customFieldsByGroup[$groupName][] = $fieldData;
            }
        }

        // Get school info
        $school = $this->SchoolModel->find((int) $student->school_id);

        $data = [
            'student'             => $student,
            'enrollment'          => $enrollment,
            'enrollments'         => $enrollments,
            'guardians'           => $guardians,
            'address'             => $address,
            'documents'           => $documents,
            'customFields'        => $customFields,
            'customFieldsByGroup' => $customFieldsByGroup,
            'school'              => $school,
        ];

        return view('school_owner/students/print', $data);
    }

    public function view($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $student = $this->verifyOwnershipByToken($token);
        if (!$student) {
            return redirect()->to('school-owner/students')
                ->with('error', 'Student not found.');
        }

        // Check if print/PDF view is requested
        $format = $this->request->getGet('format');
        if ($format === 'print' || $format === 'pdf') {
            return $this->viewPrint($student->id);
        }

        $enrollments = $this->getStudentEnrollmentsWithTitles((int) $student->id);
        $enrollment  = $enrollments[0] ?? null;

        // Get guardians
        $guardians = $this->GuardianModel
            ->where('student_id', $student->id)
            ->findAll();

        // Get address
        $address = $this->AddressModel
            ->where('student_id', $student->id)
            ->first();

        // Get documents
        $documents = $this->DocumentModel
            ->where('student_id', $student->id)
            ->findAll();

        // Get custom field values grouped by group_name
        $customFields = [];
        $customFieldsByGroup = [];
        $studentEntity = $this->EntityModel->where('slug', 'student')->first();
        if ($studentEntity && !empty($student->school_id)) {
            helper('customfield');
            $fields = $this->FieldModel->getFields((int) $student->school_id, (int) $studentEntity->id, true);
            foreach ($fields as $field) {
                $value = get_custom_field_value((int) $student->school_id, (int) $studentEntity->id, (int) $student->id, $field->id);
                $fieldData = [
                    'field' => $field,
                    'value' => $value
                ];
                $customFields[] = $fieldData;
                $groupName = $field->group_name ?? 'Other';
                $customFieldsByGroup[$groupName][] = $fieldData;
            }
        }

        // Get login user if exists
        $loginUser = null;
        if (!empty($student->user_id)) {
            $loginUser = $this->UserModel->find($student->user_id);
        }

        $header_data['page_title'] = $student->first_name . ' ' . ($student->last_name ?? '');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data = [
            'student'             => $student,
            'enrollment'          => $enrollment,
            'enrollments'         => $enrollments,
            'guardians'           => $guardians,
            'address'             => $address,
            'documents'           => $documents,
            'customFields'        => $customFields,
            'customFieldsByGroup' => $customFieldsByGroup,
            'login_user'          => $loginUser,
        ];

        return view('header', $header_data)
            . view('school_owner/students/view', $data)
            . view('footer', $footer_data);
    }

    public function trash($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/students');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/students');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];
        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->verifyOwnershipByToken($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->StudentModel->update($record->id, [
            'status'     => 2,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->getUserId(),
        ]);

        $response['status'] = (bool) $updated;
        $response['html']   = $updated
            ? message_generator('success', lang('Common.data_trashed'))
            : message_generator('error', lang('Common.data_error_trashed'));

        return $this->jsonResponse($response);
    }

    public function empty_trash()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/students');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/students');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $token    = $this->request->getPost('id');
        $response = ['status' => false, 'html' => ''];

        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->verifyOwnershipByToken($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        // Delete related records
        $this->EnrollmentModel->where('student_id', $record->id)->delete();
        $this->GuardianModel->where('student_id', $record->id)->delete();
        $this->AddressModel->where('student_id', $record->id)->delete();
        $this->DocumentModel->where('student_id', $record->id)->delete();

        $deleted = $this->StudentModel->delete($record->id);
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function restore($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/students');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/students');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];
        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->verifyOwnershipByToken($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->StudentModel->update($record->id, [
            'status'     => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->getUserId(),
        ]);

        $response['status'] = (bool) $updated;
        $response['html']   = $updated
            ? message_generator('success', lang('Common.data_restored'))
            : message_generator('error', lang('Common.data_error_restored'));

        return $this->jsonResponse($response);
    }

    /**
     * AJAX: Get all academic dropdowns (years, classes, sections, departments, categories) by school.
     */
    public function getAcademicData()
    {
        $school_id = (int) $this->request->getPost('school_id');
        if (!$school_id) {
            return $this->jsonResponse(['status' => false]);
        }

        $yearList       = $this->getActiveOptions($school_id, 'YearModel');
        $classList      = $this->getActiveOptions($school_id, 'ClassModel');
        $sectionList    = $this->getActiveOptions($school_id, 'SectionModel');
        $departmentList = $this->getActiveOptions($school_id, 'DepartmentModel');
        $categoryList   = $this->getActiveOptions($school_id, 'CategoryModel');
        $shiftList      = $this->getActiveOptions($school_id, 'ShiftModel');

        return $this->jsonResponse([
            'status'       => true,
            'years'        => $yearList,
            'classes'      => $classList,
            'sections'     => $sectionList,
            'departments'  => $departmentList,
            'categories'   => $categoryList,
            'shifts'       => $shiftList,
            'year_list'       => $yearList,
            'class_list'      => $classList,
            'section_list'    => $sectionList,
            'department_list' => $departmentList,
            'category_list'   => $categoryList,
            'shift_list'      => $shiftList,
            'student_account_enabled'       => $this->isStudentAccountEnabled($school_id),
            'academic_class_roll_enabled'   => $this->isSchoolSettingEnabled($school_id, 'academic_class_roll_enabled'),
            'academic_section_enabled'      => $this->isSchoolSettingEnabled($school_id, 'academic_section_enabled'),
            'academic_department_enabled'   => $this->isSchoolSettingEnabled($school_id, 'academic_department_enabled'),
            'academic_category_enabled'     => $this->isSchoolSettingEnabled($school_id, 'academic_category_enabled'),
            'academic_shift_enabled'        => $this->isSchoolSettingEnabled($school_id, 'academic_shift_enabled'),
            'enable_student_id'             => $this->isSchoolSettingEnabled($school_id, 'enable_student_id'),
            'enable_qr_code'                => $this->isSchoolSettingEnabled($school_id, 'enable_qr_code'),
            'enable_registration_no'        => $this->isSchoolSettingEnabled($school_id, 'enable_registration_no'),
            'enable_rfid'                   => $this->isSchoolSettingEnabled($school_id, 'enable_rfid'),
        ]);
    }

    /**
     * AJAX: Auto-generate next student code, registration no, and QR code for the selected school.
     */
    public function getAutoGenCodes()
    {
        $school_id = (int) $this->request->getPost('school_id');
        if (!$school_id) {
            return $this->jsonResponse(['status' => false]);
        }

        return $this->jsonResponse([
            'status'          => true,
            'student_code'    => $this->generateStudentCode($school_id),
            'registration_no' => $this->generateRegistrationNo($school_id),
            'student_qr_code' => $this->generateQRCode($school_id),
            'enable_student_id'          => $this->isSchoolSettingEnabled($school_id, 'enable_student_id'),
            'enable_qr_code'             => $this->isSchoolSettingEnabled($school_id, 'enable_qr_code'),
            'enable_registration_no'     => $this->isSchoolSettingEnabled($school_id, 'enable_registration_no'),
            'enable_rfid'                => $this->isSchoolSettingEnabled($school_id, 'enable_rfid'),
        ]);
    }

    /**
     * AJAX: Get sections filtered by school and class.
     */
    public function getSectionsByClass()
    {
        $school_id = (int) $this->request->getPost('school_id');
        $class_id  = (int) $this->request->getPost('class_id');

        $records = $this->SectionModel
            ->where('school_id', $school_id)
            ->where('class_id', $class_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        $sections = [];
        foreach ($records as $r) {
            $sections[$r->id] = $r->title;
        }

        return $this->jsonResponse([
            'status'   => true,
            'sections' => $sections,
        ]);
    }

    public function office_copy_print()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $data = $this->getOfficeCopyData($this->request->getGet());
        $data['is_pdf'] = false;

        return view('school_owner/students/office_copy', $data);
    }

    public function office_copy_pdf()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $data = $this->getOfficeCopyData($this->request->getGet());
        $data['is_pdf'] = true;

        $html = view('school_owner/students/office_copy', $data);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'students-office-copy-' . date('Ymd-His') . '.pdf';
        return $dompdf->stream($filename, ['Attachment' => true]);
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function profile_print($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $student = $this->verifyOwnershipByToken($token);
        if (!$student) {
            return redirect()->to('school-owner/students')
                ->with('error', 'Student not found.');
        }

        $school_id = (int) $student->school_id;

        $enrollments = $this->getStudentEnrollmentsWithTitles((int) $student->id);
        $enrollment  = $enrollments[0] ?? null;

        $guardians = $this->GuardianModel
            ->where('student_id', $student->id)
            ->findAll();

        $address = $this->AddressModel
            ->where('student_id', $student->id)
            ->first();

        $documents = $this->DocumentModel
            ->where('student_id', $student->id)
            ->findAll();

        $customFields = [];
        $customFieldsByGroup = [];
        $studentEntity = $this->EntityModel->where('slug', 'student')->first();
        if ($studentEntity && !empty($student->school_id)) {
            helper('customfield');
            $fields = $this->FieldModel->getFields((int) $student->school_id, (int) $studentEntity->id, true);
            foreach ($fields as $field) {
                $value = get_custom_field_value((int) $student->school_id, (int) $studentEntity->id, (int) $student->id, $field->id);
                $fieldData = [
                    'field' => $field,
                    'value' => $value
                ];
                $customFields[] = $fieldData;
                $groupName = $field->group_name ?? 'Other';
                $customFieldsByGroup[$groupName][] = $fieldData;
            }
        }

        $school = $this->SchoolModel->find((int) $student->school_id);

        $data = [
            'student'             => $student,
            'enrollment'          => $enrollment,
            'enrollments'         => $enrollments,
            'guardians'           => $guardians,
            'address'             => $address,
            'documents'           => $documents,
            'customFields'        => $customFields,
            'customFieldsByGroup' => $customFieldsByGroup,
            'school'              => $school,
        ];

        return view('school_owner/students/print', $data);
    }

    public function profile_pdf($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $student = $this->verifyOwnershipByToken($token);
        if (!$student) {
            return redirect()->to('school-owner/students')
                ->with('error', 'Student not found.');
        }

        $school_id = (int) $student->school_id;

        $enrollments = $this->getStudentEnrollmentsWithTitles((int) $student->id);
        $enrollment  = $enrollments[0] ?? null;

        $guardians = $this->GuardianModel
            ->where('student_id', $student->id)
            ->findAll();

        $address = $this->AddressModel
            ->where('student_id', $student->id)
            ->first();

        $documents = $this->DocumentModel
            ->where('student_id', $student->id)
            ->findAll();

        $customFields = [];
        $customFieldsByGroup = [];
        $studentEntity = $this->EntityModel->where('slug', 'student')->first();
        if ($studentEntity && !empty($student->school_id)) {
            helper('customfield');
            $fields = $this->FieldModel->getFields((int) $student->school_id, (int) $studentEntity->id, true);
            foreach ($fields as $field) {
                $value = get_custom_field_value((int) $student->school_id, (int) $studentEntity->id, (int) $student->id, $field->id);
                $fieldData = [
                    'field' => $field,
                    'value' => $value
                ];
                $customFields[] = $fieldData;
                $groupName = $field->group_name ?? 'Other';
                $customFieldsByGroup[$groupName][] = $fieldData;
            }
        }

        $school = $this->SchoolModel->find((int) $student->school_id);

        $data = [
            'student'             => $student,
            'enrollment'          => $enrollment,
            'enrollments'         => $enrollments,
            'guardians'           => $guardians,
            'address'             => $address,
            'documents'           => $documents,
            'customFields'        => $customFields,
            'customFieldsByGroup' => $customFieldsByGroup,
            'school'              => $school,
            'is_pdf'              => true,
        ];

        // Prepare base64 images for PDF using HTTP URLs (works reliably on Windows)
        $baseUrl = rtrim(base_url(), '/');
        
        $studentPhotoBase64 = '';
        $studentPhotoMime = 'image/png';
        $studentPhotoUrl = $baseUrl . '/uploads/' . ($student->photo ?: 'default.png');
        $photoContent = @file_get_contents($studentPhotoUrl);
        if ($photoContent) {
            $studentPhotoMime = 'image/png';
            $studentPhotoBase64 = base64_encode($photoContent);
        }
        
        $schoolLogoBase64 = '';
        $schoolLogoMime = 'image/png';
        if (!empty($school->logo)) {
            $schoolLogoUrl = $baseUrl . '/uploads/' . $school->logo;
            $logoContent = @file_get_contents($schoolLogoUrl);
            if ($logoContent) {
                $schoolLogoMime = 'image/png';
                $schoolLogoBase64 = base64_encode($logoContent);
            }
        }
        
        $qrBase64 = '';
        $qrMime = 'image/png';
        if (!empty($student->student_qr_code)) {
            $qrData = base_url('school-owner/students/view/' . $student->token);
            $qrUrl = generate_qr_code($qrData, 150);
            // If generate_qr_code returns a URL, try to fetch it
            if (filter_var($qrUrl, FILTER_VALIDATE_URL)) {
                $qrContent = @file_get_contents($qrUrl);
                if ($qrContent) {
                    $qrMime = 'image/png';
                    $qrBase64 = base64_encode($qrContent);
                }
            } elseif (str_starts_with($qrUrl, 'data:')) {
                // Already a data URI, extract the base64 part
                if (preg_match('/base64,([^"]+)/', $qrUrl, $matches)) {
                    $qrBase64 = $matches[1];
                    $qrMime = 'image/png';
                }
            }
        }

        $data['student_photo_base64'] = $studentPhotoBase64;
        $data['student_photo_mime'] = $studentPhotoMime;
        $data['school_logo_base64'] = $schoolLogoBase64;
        $data['school_logo_mime'] = $schoolLogoMime;
        $data['qr_base64'] = $qrBase64;
        $data['qr_mime'] = $qrMime;

        $html = view('school_owner/students/print', $data);

        // Replace image src with base64 data URIs using file system paths (most reliable)
        $fileBasePath = rtrim(str_replace('\\', '/', FCPATH), '/');
        $html = preg_replace_callback(
            '#src=("|\')([^"\']*)/uploads/([^"\']+)\1#',
            function ($matches) use ($fileBasePath) {
                $filePath = $fileBasePath . '/uploads/' . $matches[3];
                if (file_exists($filePath)) {
                    $mimeType = mime_content_type($filePath) ?: 'image/png';
                    $data = base64_encode(file_get_contents($filePath));
                    return 'src=' . $matches[1] . 'data:' . $mimeType . ';base64,' . $data . $matches[1];
                }
                return $matches[0];
            },
            $html
        );

        // Generate PDF using Dompdf
        $dompdf = new Dompdf();
        $dompdf->setBasePath('file:///' . $fileBasePath . '/');
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'student-profile-' . $student->student_code . '.pdf';
        return $dompdf->stream($filename, ['Attachment' => true]);
    }
}

<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Models\MarkModel;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsDepartmentModel;
use App\Models\AcademicsCategoryModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\ExamModel;
use App\Models\AcademicsShiftModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Modules\examination\Models\SubjectResultModel;
use App\Modules\examination\Models\SubjectResultHistoryModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\ResultHistoryModel;
use App\Modules\examination\Models\ResultPublishModel;
use App\Modules\examination\Models\GradeRuleModel;
use App\Modules\examination\Models\GradeSystemModel;
use App\Modules\examination\Models\MarkLockModel;
use App\Modules\examination\Models\StudentSubjectModel;

class MarksController extends BaseController
{
    protected MarkModel $MarkModel;
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsDepartmentModel $DepartmentModel;
    protected AcademicsCategoryModel $CategoryModel;
    protected SubjectModel $SubjectModel;
    protected ExamModel $ExamModel;
    protected AcademicsShiftModel $ShiftModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentModel $StudentModel;
    protected UserModel $UserModel;
    protected MarkDistributionModel $MarkDistributionModel;
    protected SubjectResultModel $SubjectResultModel;
    protected SubjectResultHistoryModel $SubjectResultHistoryModel;
    protected ExamResultModel $ExamResultModel;
    protected ResultHistoryModel $ResultHistoryModel;
    protected ResultPublishModel $ResultPublishModel;
    protected GradeRuleModel $GradeRulesModel;
    protected GradeSystemModel $GradeSystemModel;
    protected MarkLockModel $MarkLockModel;
    protected StudentSubjectModel $StudentSubjectModel;
    protected $db;

    public function __construct()
    {
        $this->MarkModel             = new MarkModel();
        $this->SchoolModel           = new SchoolModel();
        $this->YearModel             = new AcademicsYearModel();
        $this->ClassModel            = new AcademicsClassesModel();
        $this->SectionModel          = new AcademicsSectionModel();
        $this->DepartmentModel       = new AcademicsDepartmentModel();
        $this->CategoryModel         = new AcademicsCategoryModel();
        $this->SubjectModel          = new SubjectModel();
        $this->ExamModel             = new ExamModel();
        $this->ShiftModel            = new AcademicsShiftModel();
        $this->EnrollmentModel       = new StudentEnrollmentModel();
        $this->StudentModel          = new StudentModel();
        $this->UserModel             = new UserModel();
        $this->MarkDistributionModel = new MarkDistributionModel();
        $this->SubjectResultModel      = new SubjectResultModel();
        $this->SubjectResultHistoryModel = new SubjectResultHistoryModel();
        $this->ExamResultModel         = new ExamResultModel();
        $this->ResultHistoryModel  = new ResultHistoryModel();
        $this->ResultPublishModel        = new ResultPublishModel();
        $this->GradeRulesModel         = new GradeRuleModel();
        $this->GradeSystemModel        = new GradeSystemModel();
        $this->MarkLockModel           = new MarkLockModel();
        $this->StudentSubjectModel     = new StudentSubjectModel();
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

    protected function isSchoolSettingEnabled(int $school_id, string $key): bool
    {
        $school = $this->SchoolModel->find($school_id);
        if (!$school || empty($school->params)) {
            return false;
        }
        $params = json_decode($school->params, true);
        return !empty($params[$key]);
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function index()
    {
        $user_id   = $this->getUserId();
        $school_id = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;

        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('Mark.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $text   = $this->request->getGet('text');
        $status = $this->request->getGet('status');
        $show   = $this->request->getGet('show');
        $subject_id = $this->request->getGet('subject_id');
        $exam_id = $this->request->getGet('exam_id');
        $distribution_id = $this->request->getGet('distribution_id');
        $year_id = $this->request->getGet('year_id') ? (int) $this->request->getGet('year_id') : 0;
        $class_id = $this->request->getGet('class_id') ? (int) $this->request->getGet('class_id') : 0;

        $data = compact('text', 'status', 'show', 'subject_id', 'exam_id', 'distribution_id');

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

        $this->MarkModel
            ->select('examination_marks.*, schools.name AS school_name, examination_subjects.title AS subject_title, examination_exams.title AS exam_title, students.first_name, students.last_name, examination_mark_distributions.name AS distribution_name, student_enrollments.roll_no')
            ->join('schools', 'schools.id = examination_marks.school_id', 'left')
            ->join('examination_subjects', 'examination_subjects.id = examination_marks.subject_id', 'left')
            ->join('examination_exams', 'examination_exams.id = examination_marks.exam_id', 'left')
            ->join('students', 'students.id = examination_marks.student_id', 'left')
            ->join('student_enrollments', 'student_enrollments.student_id = examination_marks.student_id AND student_enrollments.school_id = examination_marks.school_id AND student_enrollments.class_id = examination_marks.class_id', 'left')
            ->join('examination_mark_distributions', 'examination_mark_distributions.id = examination_marks.distribution_id', 'left');

        $this->MarkModel->where('examination_marks.school_owner_uid', $user_id);

        if (!empty($schoolIds)) {
            $this->MarkModel->whereIn('examination_marks.school_id', $schoolIds);
        } else {
            $this->MarkModel->where('1 = 0');
        }

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->MarkModel->where('examination_marks.school_id', $school_id);
        }

        if ($text) {
            $this->MarkModel->groupStart();
            $this->MarkModel->like('students.first_name', $text);
            $this->MarkModel->orLike('students.last_name', $text);
            $this->MarkModel->orLike('examination_subjects.title', $text);
            $this->MarkModel->groupEnd();
        }

        if (isset($status) && $status !== '') {
            $this->MarkModel->where('examination_marks.status', $status);
        }

        if ($subject_id) {
            $this->MarkModel->where('examination_marks.subject_id', (int) $subject_id);
        }

        if ($exam_id) {
            $this->MarkModel->where('examination_marks.exam_id', (int) $exam_id);
        }

        if ($distribution_id) {
            $this->MarkModel->where('examination_marks.distribution_id', (int) $distribution_id);
        }

        $this->MarkModel->orderBy('examination_marks.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->MarkModel->paginate($perPage);
        $data['pager']           = $this->MarkModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;
        $data['selected_subject'] = $subject_id;
        $data['selected_exam'] = $exam_id;
        $data['selected_year'] = $year_id;
        $data['selected_class'] = $class_id;
        
        if ($school_id) {
            $data['subject_list'] = $this->getActiveOptions($school_id, 'SubjectModel');
            $data['exam_list'] = $this->getActiveOptions($school_id, 'ExamModel');
            $data['year_list'] = $this->getActiveOptions($school_id, 'YearModel');
            $data['class_list'] = $this->getActiveOptions($school_id, 'ClassModel');
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\marks\list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('Mark.page_title_input'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();
        $data['post_data']   = [];

        return view('header', $header_data)
            . view('App\Modules\examination\Views\marks\input', $data)
            . view('footer', $footer_data);
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function store()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks');
        if ($subscription_check) {
            return $this->jsonResponse(['status' => false, 'message' => $subscription_check['html']]);
        }

        $post_data = $this->request->getPost();
        $school_id  = (int) ($post_data['school_id'] ?? 0);
        $exam_id    = (int) ($post_data['exam_id'] ?? 0);
        $class_id   = (int) ($post_data['class_id'] ?? 0);
        $section_id = !empty($post_data['section_id']) ? (int) $post_data['section_id'] : null;
        $subject_id = (int) ($post_data['subject_id'] ?? 0);
        $year_id    = (int) ($post_data['year_id'] ?? 0);
        $students   = $post_data['students'] ?? [];

        if (!$school_id || !$exam_id || !$class_id || !$subject_id || empty($students)) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields.']);
        }

        // Check if marks are locked
        $isLocked = $this->MarkLockModel->isLocked($school_id, $exam_id, $class_id, $year_id, $subject_id);
        if ($isLocked) {
            $lockInfo = $this->MarkLockModel->getLockStatus($school_id, $exam_id, $class_id, $year_id, $subject_id);
            $exam = $this->ExamModel->find($exam_id);
            $examTitle = $exam ? $exam->title : 'this exam';
            $session = $this->YearModel->find($lockInfo->session_id ?? $year_id);
            $sessionName = $session ? $session->title : 'this session';
            $class = $this->ClassModel->find($class_id);
            $className = $class ? $class->title : 'this class';
            
            return $this->jsonResponse([
                'status' => false, 
                'message' => "Cannot save marks. Subject marks are locked for the exam '{$examTitle}', session '{$sessionName}', class '{$className}'. Please unlock the marks first."
            ]);
        }

        $user_id = $this->getUserId();
        $now     = date('Y-m-d H:i:s');
        $saved   = 0;
        $errors  = [];

        foreach ($students as $studentData) {
            $student_id     = (int) ($studentData['student_id'] ?? 0);
            $enrollment_id  = (int) ($studentData['enrollment_id'] ?? 0);
            $distributions  = $studentData['distributions'] ?? [];

            if (!$student_id || empty($distributions)) {
                continue;
            }

            foreach ($distributions as $dist) {
                $distribution_id = (int) ($dist['distribution_id'] ?? 0);
                $obtained_mark   = (float) ($dist['obtained'] ?? 0);
                $full_mark       = (float) ($dist['max'] ?? 0);

                if (!$distribution_id) {
                    continue;
                }

                $existing = $this->MarkModel
                    ->where('school_id', $school_id)
                    ->where('exam_id', $exam_id)
                    ->where('class_id', $class_id)
                    ->where('subject_id', $subject_id)
                    ->where('student_id', $student_id)
                    ->where('distribution_id', $distribution_id)
                    ->first();

                $saveData = [
                    'school_id'             => $school_id,
                    'school_owner_uid'      => $user_id,
                    'exam_id'               => $exam_id,
                    'session_id'            => $year_id,
                    'class_id'              => $class_id,
                    'section_id'            => $section_id,
                    'subject_id'            => $subject_id,
                    'student_id'            => $student_id,
                    'enrollment_id'         => $enrollment_id,
                    'distribution_id'       => $distribution_id,
                    'obtained_mark'         => $obtained_mark,
                    'full_mark'             => $full_mark,
                    'is_absent'             => $studentData['is_absent'] ?? 0,
                    'roll_no'               => $studentData['roll_no'] ?? null,
                    'created_by'            => $user_id,
                    'updated_by'            => $user_id,
                ];

                if ($existing) {
                    $saveData['updated_by'] = $user_id;
                    $this->MarkModel->update($existing->id, $saveData);
                } else {
                    $saveData['token'] = $this->generateToken();
                    $this->MarkModel->insert($saveData);
                }
                $saved++;
            }
        }

        return $this->jsonResponse([
            'status'  => true,
            'message' => $saved . ' distribution mark(s) saved successfully.',
            'saved'   => $saved,
        ]);
    }

    public function edit($token = null)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        if (!$token) {
            return redirect()->to('examination/marks')
                ->with('error', 'Invalid mark token.');
        }

        $record = $this->MarkModel
            ->select('examination_marks.*, 
                     students.first_name, students.middle_name, students.last_name,
                     schools.name as school_name,
                     academic_classes.title as class_name,
                     examination_exams.title as exam_title,
                     examination_subjects.title as subject_title,
                     academic_years.title as session_name')
            ->join('students', 'students.id = examination_marks.student_id', 'left')
            ->join('schools', 'schools.id = examination_marks.school_id', 'left')
            ->join('academic_classes', 'academic_classes.id = examination_marks.class_id', 'left')
            ->join('examination_exams', 'examination_exams.id = examination_marks.exam_id', 'left')
            ->join('examination_subjects', 'examination_subjects.id = examination_marks.subject_id', 'left')
            ->join('academic_years', 'academic_years.id = examination_marks.session_id', 'left')
            ->where('examination_marks.token', $token)
            ->where('examination_marks.school_owner_uid', $this->getUserId())
            ->first();

        if (!$record) {
            return redirect()->to('examination/marks')
                ->with('error', 'Mark not found or access denied.');
        }

       
        // Check if marks are locked
        $isLocked = $this->MarkLockModel->isLocked($record->school_id, $record->exam_id, $record->class_id, $record->session_id, $record->subject_id);
        
        
        if ($isLocked) {
            $lockInfo = $this->MarkLockModel->getLockStatus($record->school_id, $record->exam_id, $record->class_id, $record->session_id, $record->subject_id);
            $exam = $this->ExamModel->find($record->exam_id);
            $examTitle = $exam ? $exam->title : 'this exam';
            $session = $this->YearModel->find($lockInfo->session_id ?? $record->session_id);
            $sessionName = $session ? $session->title : 'this session';
            $class = $this->ClassModel->find($record->class_id);
            $className = $class ? $class->title : 'this class';
            
            return redirect()->to('examination/marks/list')
                ->with('error', "Cannot edit marks. Subject marks are locked for the exam '{$examTitle}', session '{$sessionName}', class '{$className}'. Please unlock the marks first.");
        }

        $studentName = trim(($record->first_name ?? '') . ' ' . ($record->middle_name ?? '') . ' ' . ($record->last_name ?? ''));
        $studentName = preg_replace('/\s+/', ' ', $studentName);

        $header_data['page_title'] = lang('Mark.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $postData = (array) $record;
        $postData['student_name'] = $studentName;
        $data['post_data']   = $postData;
        $data['is_edit']     = true;
        
        // Check if marks are locked
        $isLocked = $this->MarkLockModel->isLocked($record->school_id, $record->exam_id, $record->class_id, $record->session_id, $record->subject_id);
        $data['is_locked'] = $isLocked;
        if ($isLocked) {
            $lockInfo = $this->MarkLockModel->getLockStatus($record->school_id, $record->exam_id, $record->class_id, $record->session_id, $record->subject_id);
            $data['lock_info'] = $lockInfo;
        }
        
        return view('header', $header_data)
            . view('App\Modules\examination\Views\marks\form', $data)
            . view('footer', $footer_data);
    }

    public function update($token = null)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks/list');
        if ($subscription_check) {
            return $subscription_check;
        }

        if (!$token) {
            return redirect()->to('examination/marks')
                ->with('error', 'Invalid mark token.');
        }

        $record = $this->MarkModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();

        if (!$record) {
            return redirect()->to('examination/marks')
                ->with('error', 'Mark not found or access denied.');
        }

        // Check if marks are locked
        $isLocked = $this->MarkLockModel->isLocked($record->school_id, $record->exam_id, $record->class_id, $record->session_id, $record->subject_id);
        if ($isLocked) {
            $lockInfo = $this->MarkLockModel->getLockStatus($record->school_id, $record->exam_id, $record->class_id, $record->session_id, $record->subject_id);
            $exam = $this->ExamModel->find($record->exam_id);
            $examTitle = $exam ? $exam->title : 'this exam';
            $session = $this->YearModel->find($lockInfo->session_id ?? $record->session_id);
            $sessionName = $session ? $session->title : 'this session';
            $class = $this->ClassModel->find($record->class_id);
            $className = $class ? $class->title : 'this class';
            
            return redirect()->to('examination/marks/list')
                ->with('error', "Cannot update marks. Subject marks are locked for the exam '{$examTitle}', session '{$sessionName}', class '{$className}'. Please unlock the marks first.");
        }

        $post_data = $this->request->getPost();
        $user_id   = $this->getUserId();

        $saveData = [
            'obtained_mark'  => (float) ($post_data['obtained_mark'] ?? $record->obtained_mark),
            'is_absent'      => !empty($post_data['is_absent']) ? 1 : 0,
            'remarks'        => $post_data['remarks'] ?? null,
            'updated_by'     => $user_id,
        ];

        $this->MarkModel->update($record->id, $saveData);

        return redirect()->to('examination/marks/list')
            ->with('success', lang('Mark.sys_updated'));
    }



    /**
     * Lock marks for a given exam/class/subject combination
     */
    public function lock()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

        $response = ['status' => false, 'message' => ''];
        $school_id  = (int) $this->request->getPost('school_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = !empty($this->request->getPost('section_id')) ? (int) $this->request->getPost('section_id') : null;
        $subject_id = !empty($this->request->getPost('subject_id')) ? (int) $this->request->getPost('subject_id') : null;
        $session_id = !empty($this->request->getPost('session_id')) ? (int) $this->request->getPost('session_id') : null;
        $lock_reason = $this->request->getPost('lock_reason');

        if (!$school_id || !$exam_id || !$class_id || !$session_id) {
            $response['message'] = 'Missing required fields.';
            return $this->jsonResponse($response);
        }

        // Check if already locked
        if ($this->MarkLockModel->isLocked($school_id, $exam_id, $class_id, $session_id, $subject_id)) {
            $response['message'] = 'Marks are already locked for this selection.';
            return $this->jsonResponse($response);
        }

        // Create lock record
        $lockData = [
            'school_id'        => $school_id,
            'school_owner_uid' => $this->getUserId(),
            'exam_id'          => $exam_id,
            'session_id'       => $session_id,
            'class_id'         => $class_id,
            'section_id'       => $section_id,
            'subject_id'       => $subject_id,
            'is_locked'        => 1,
            'locked_by'        => $this->getUserId(),
            'lock_reason'      => $lock_reason,
        ];

        if ($this->MarkLockModel->setLock($lockData)) {
            $response['status']  = true;
            $response['message'] = 'Marks locked successfully.';
        } else {
            $response['message'] = 'Failed to lock marks.';
        }

        return $this->jsonResponse($response);
    }

    /**
     * Unlock marks for a given exam/class/subject combination
     */
    public function unlock()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks/list');
        }

        $response = ['status' => false, 'message' => ''];
        $school_id  = (int) $this->request->getPost('school_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $subject_id = !empty($this->request->getPost('subject_id')) ? (int) $this->request->getPost('subject_id') : null;
        $session_id = (int) $this->request->getPost('session_id');
        $unlock_reason = $this->request->getPost('unlock_reason');

        if (!$school_id || !$exam_id || !$class_id || !$session_id) {
            $response['message'] = 'Missing required fields.';
            return $this->jsonResponse($response);
        }

        // Find the lock record
        $lock = $this->MarkLockModel->getLockStatus($school_id, $exam_id, $class_id, $session_id,  $subject_id);

        if (!$lock) {
            $response['message'] = 'No lock found for this selection.';
            return $this->jsonResponse($response);
        }

        // Update lock record
        $lockData = [
            'is_locked'    => 0,
            'unlocked_at'  => date('Y-m-d H:i:s'),
            'unlocked_by'  => $this->getUserId(),
            'lock_reason'  => $lock->lock_reason . ($unlock_reason ? ' | Unlock Reason: ' . $unlock_reason : ''),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        if ($this->MarkLockModel->update($lock->id, $lockData)) {
            $response['status']  = true;
            $response['message'] = 'Marks unlocked successfully.';
        } else {
            $response['message'] = 'Failed to unlock marks.';
        }

        return $this->jsonResponse($response);
    }

    public function view($id)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->MarkModel
            ->select('examination_marks.*, schools.name AS school_name, examination_subjects.title AS subject_title, students.first_name, students.last_name')
            ->join('schools', 'schools.id = examination_marks.school_id', 'left')
            ->join('examination_subjects', 'examination_subjects.id = examination_marks.subject_id', 'left')
            ->join('students', 'students.id = examination_marks.student_id', 'left')
            ->where('examination_marks.id', (int) $id)
            ->where('examination_marks.school_owner_uid', $this->getUserId())
            ->first();

        if (!$record) {
            return redirect()->to('examination/marks')
                ->with('error', 'Mark not found or access denied.');
        }

        $header_data = [
            'page_title' => 'View Mark',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['record'] = $record;

        return view('header', $header_data)
            . view('App\Modules\examination\Views\marks\view', $data)
            . view('footer', $footer_data);
    }

    public function trash($token = null)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];
        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->MarkModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();

        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $db = \Config\Database::connect();
        $db->table('examination_marks')
            ->where('token', $token)
            ->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);

        $response['status'] = true;
        $response['html']   = message_generator('success', lang('Common.data_trashed'));

        return $this->jsonResponse($response);
    }

    public function restore($token = null)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];
        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->MarkModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();

        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $db = \Config\Database::connect();
        $db->table('examination_marks')
            ->where('token', $token)
            ->update(['status' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

        $response['status'] = true;
        $response['html']   = message_generator('success', lang('Common.data_restored'));

        return $this->jsonResponse($response);
    }

    public function emptyTrash()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/marks');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $token    = $this->request->getPost('token');
        $response = ['status' => false, 'html' => ''];

        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->MarkModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();

        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $deleted = $this->MarkModel->where('token', $token)->delete();
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function getAcademicDataBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

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
        $subjectList    = $this->getActiveOptions($school_id, 'SubjectModel');
        $examList       = $this->getActiveOptions($school_id, 'ExamModel');

        return $this->jsonResponse([
            'status'       => true,
            'year_list'       => $yearList,
            'class_list'      => $classList,
            'section_list'    => $sectionList,
            'department_list' => $departmentList,
            'category_list'   => $categoryList,
            'shift_list'      => $shiftList,
            'subject_list'    => $subjectList,
            'exam_list'       => $examList,
            'academic_section_enabled'    => $this->isSchoolSettingEnabled($school_id, 'academic_section_enabled'),
            'academic_department_enabled' => $this->isSchoolSettingEnabled($school_id, 'academic_department_enabled'),
            'academic_category_enabled'   => $this->isSchoolSettingEnabled($school_id, 'academic_category_enabled'),
            'academic_shift_enabled'      => $this->isSchoolSettingEnabled($school_id, 'academic_shift_enabled'),
        ]);
    }

    /**
     * Check if marks are locked for given criteria
     */
    public function checkLockStatus()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks/list');
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $session_id = !empty($this->request->getPost('session_id')) ? (int) $this->request->getPost('session_id') : null;
        $subject_id = !empty($this->request->getPost('subject_id')) ? (int) $this->request->getPost('subject_id') : null;
       
        if (!$school_id || !$exam_id || !$class_id || !$session_id || !$subject_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required fields.', 'is_locked' => false]);
        }

        $isLocked = $this->MarkLockModel->isLocked($school_id, $exam_id, $class_id, $session_id, $subject_id);
        $lockInfo = $this->MarkLockModel->getLockStatus($school_id, $exam_id, $class_id, $session_id, $subject_id);

        $response = [
            'status'     => true,
            'is_locked'  => $isLocked,
        ];

        if ($isLocked && $lockInfo) {
            $response['locked_at']   = $lockInfo->locked_at;
            $response['lock_reason'] = $lockInfo->lock_reason;
            
            // Get locker name
            $locker = $this->UserModel->find($lockInfo->locked_by);
            $response['locked_by_name'] = $locker ? trim(($locker->name ?? '')) : 'Unknown';
            
            // Get exam, session, and class names for lock message
            $exam = $this->ExamModel->find($exam_id);
            $response['exam_title'] = $exam ? $exam->title : null;
            
            $session = $this->YearModel->find($lockInfo->session_id);
            $response['session_name'] = $session ? $session->title : null;
            
            $class = $this->ClassModel->find($class_id);
            $response['class_name'] = $class ? $class->title : null;
        }

        return $this->jsonResponse($response);
    }

    /**
     * Get all locked marks list
     */
    public function lockedMarksList()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $school_id = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;
        $exam_id = $this->request->getGet('exam_id') ? (int) $this->request->getGet('exam_id') : 0;
        $year_id = $this->request->getGet('year_id') ? (int) $this->request->getGet('year_id') : 0;
        $class_id = $this->request->getGet('class_id') ? (int) $this->request->getGet('class_id') : 0;
        $subject_id = $this->request->getGet('subject_id') ? (int) $this->request->getGet('subject_id') : 0;

        $header_data = [
            'page_title' => 'Locked Marks List',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $lockedMarks = [];
        
        // Get all lock records (both locked and unlocked)
        // If school_id is provided, filter by it; otherwise show all schools user has access to
        $builder = $this->db->table('examination_mark_locks');
        $builder->select('examination_mark_locks.*, 
                         edum_schools.name as school_name,
                         examination_exams.title as exam_title,
                         academic_classes.title as class_title,
                         examination_subjects.title as subject_title,
                         CONCAT(edum_users.name) as locked_by_name,
                         CONCAT(unlocker.name) as unlocked_by_name')
                 ->join('edum_schools', 'edum_schools.id = examination_mark_locks.school_id', 'left')
                 ->join('examination_exams', 'examination_exams.id = examination_mark_locks.exam_id', 'left')
                 ->join('academic_classes', 'academic_classes.id = examination_mark_locks.class_id', 'left')
                 ->join('examination_subjects', 'examination_subjects.id = examination_mark_locks.subject_id', 'left')
                 ->join('edum_users', 'edum_users.id = examination_mark_locks.locked_by', 'left')
                 ->join('edum_users as unlocker', 'unlocker.id = examination_mark_locks.unlocked_by', 'left')
                 ->orderBy('examination_mark_locks.created_at', 'DESC');

        // Filter by user's schools if no specific school selected
        if ($school_id) {
            $builder->where('examination_mark_locks.school_id', $school_id);
        } else {
            $userSchools = $this->getUserSchools();
            $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);
            if (!empty($schoolIds)) {
                $builder->whereIn('examination_mark_locks.school_id', $schoolIds);
            } else {
                $builder->where('1 = 0'); // No schools, no results
            }
        }

        if ($exam_id) {
            $builder->where('examination_mark_locks.exam_id', $exam_id);
        }
        if ($year_id) {
            $builder->where('examination_mark_locks.session_id', $year_id);
        }
        if ($class_id) {
            $builder->where('examination_mark_locks.class_id', $class_id);
        }
        if ($subject_id) {
            $builder->where('examination_mark_locks.subject_id', $subject_id);
        }

        $query = $builder->get();
        $lockedMarks = $query->getResult();

        $data['locked_marks'] = $lockedMarks;
        $data['school_list']  = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;
        $data['selected_exam'] = $exam_id;
        $data['selected_year'] = $year_id;
        $data['selected_class'] = $class_id;
        $data['selected_subject'] = $subject_id;

        if ($school_id) {
            $data['exam_list'] = $this->getActiveOptions($school_id, 'ExamModel');
            $data['year_list'] = $this->getActiveOptions($school_id, 'YearModel');
            $data['class_list'] = $this->getActiveOptions($school_id, 'ClassModel');
            $data['subject_list'] = $this->getActiveOptions($school_id, 'SubjectModel');
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\marks\locked_list', $data)
            . view('footer', $footer_data);
    }

    public function getStudentsByFilter()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/marks');
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $section_id = (int) $this->request->getPost('section_id');
        $subject_id = (int) $this->request->getPost('subject_id');
        $exam_id    = (int) $this->request->getPost('exam_id');
        $year_id    = (int) $this->request->getPost('year_id');

        if (!$school_id || !$class_id || !$subject_id || !$exam_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Missing required filters.']);
        }

        $this->EnrollmentModel
            ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code, students.photo')
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

        // Check if the selected subject is an optional subject
        $subject = $this->SubjectModel->find($subject_id);
        $isOptionalSubject = $subject && !empty($subject->optional);

        // If optional subject, filter to only students assigned to this optional subject
        if ($isOptionalSubject) {
            $assignments = $this->StudentSubjectModel
                ->select('enrollment_id')
                ->where('school_id', $school_id)
                ->where('optional_subject_id', $subject_id)
                ->findAll();

            $optionalEnrollmentIds = [];
            foreach ($assignments as $assignment) {
                $optionalEnrollmentIds[] = (int) $assignment->enrollment_id;
            }

            if (!empty($optionalEnrollmentIds)) {
                $this->EnrollmentModel->whereIn('student_enrollments.id', $optionalEnrollmentIds);
            } else {
                return $this->jsonResponse(['status' => false, 'message' => 'No students are assigned to this optional subject.']);
            }
        }

        $enrollments = $this->EnrollmentModel
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->orderBy('students.first_name', 'ASC')
            ->findAll();

        if (empty($enrollments)) {
            return $this->jsonResponse(['status' => false, 'message' => 'No students found for the selected filters.']);
        }

        $markDistribution = null;
        $subjectDistributions = $this->MarkDistributionModel
            ->select('examination_mark_distributions.*, examination_subject_distributions.full_mark, examination_subject_distributions.pass_mark, examination_subject_distributions.weight_percent, examination_subject_distributions.sort_order')
            ->join('examination_subject_distributions', 'examination_subject_distributions.distribution_id = examination_mark_distributions.id', 'left')
            ->where('examination_subject_distributions.subject_id', $subject_id)
            ->where('examination_mark_distributions.status', 1)
            ->orderBy('examination_subject_distributions.sort_order', 'ASC')
            ->orderBy('examination_mark_distributions.name', 'ASC')
            ->findAll();

        if (!empty($subjectDistributions)) {
            $markDistribution = [];
            foreach ($subjectDistributions as $sd) {
                $markDistribution[] = [
                    'id'       => $sd->id,
                    'field'    => $sd->name,
                    'code'     => $sd->code,
                    'max'      => (float) ($sd->full_mark ?? 0),
                    'pass'     => (float) ($sd->pass_mark ?? 0),
                    'weight'   => (float) ($sd->weight_percent ?? 0),
                    'sort'     => (int) ($sd->sort_order ?? 0),
                ];
            }
        }

        $gradingSystem = null;
        $gradeRules = [];
        if ($subject && $subject->grade_system_id) {
            $gradeSystemModel = new \App\Modules\examination\Models\GradeSystemModel();
            $gradingSystem = $gradeSystemModel->find((int) $subject->grade_system_id);

            $gradeRulesModel = new \App\Modules\examination\Models\GradeRuleModel();
            $gradeRulesRecords = $gradeRulesModel
                ->where('grade_system_id', (int) $subject->grade_system_id)
                ->where('status', 1)
                ->orderBy('mark_from', 'DESC')
                ->findAll();

            foreach ($gradeRulesRecords as $rule) {
                $gradeRules[] = [
                    'grade'       => $rule->title,
                    'grade_point' => (float) ($rule->grade_point ?? 0),
                    'mark_from'   => (float) ($rule->mark_from ?? 0),
                    'mark_to'     => (float) ($rule->mark_to ?? 100),
                ];
            }
        }

        $studentIds = array_map(function ($e) { return $e->student_id; }, $enrollments);
        $existingMarksByStudent = [];
        if (!empty($studentIds)) {
            $marksRecords = $this->MarkModel
                ->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('subject_id', $subject_id)
                ->whereIn('student_id', $studentIds)
                ->findAll();

            foreach ($marksRecords as $m) {
                $sid = $m->student_id;
                if (!isset($existingMarksByStudent[$sid])) {
                    $existingMarksByStudent[$sid] = [];
                }
                $existingMarksByStudent[$sid][$m->distribution_id] = $m;
            }
        }

        $students = [];
        foreach ($enrollments as $enr) {
            $studentName = trim($enr->first_name . ' ' . ($enr->middle_name ?? '') . ' ' . $enr->last_name);
            $studentName = preg_replace('/\s+/', ' ', $studentName);

            $studentExistingMarks = $existingMarksByStudent[$enr->student_id] ?? [];

            $existingDistributions = [];
            $totalObtained = 0;
            $totalFull = 0;
            $isAbsent = 0;
            $remarks = '';
            foreach ($studentExistingMarks as $distId => $markRec) {
                $existingDistributions[$distId] = [
                    'obtained' => (float) ($markRec->obtained_mark ?? 0),
                    'max'      => (float) ($markRec->full_mark ?? 0),
                ];
                $totalObtained += (float) ($markRec->obtained_mark ?? 0);
                $totalFull += (float) ($markRec->full_mark ?? 0);
                if ($markRec->is_absent) {
                    $isAbsent = 1;
                }
                if (!empty($markRec->remarks)) {
                    $remarks = $markRec->remarks;
                }
            }

            $grade = '';
            $percentage = $totalFull > 0 ? round(($totalObtained / $totalFull) * 100, 2) : 0;
            if (!empty($gradeRules)) {
                foreach ($gradeRules as $rule) {
                    if ($percentage >= $rule['mark_from'] && $percentage <= $rule['mark_to']) {
                        $grade = $rule['grade'];
                        break;
                    }
                }
            }

            $students[] = [
                'enrollment_id'         => $enr->id,
                'student_id'            => $enr->student_id,
                'student_code'          => $enr->student_code,
                'student_name'          => $studentName,
                'roll_no'               => $enr->roll_no,
                'photo'                 => $enr->photo,
                'existing_distributions'=> $existingDistributions,
                'total_obtained'        => $totalObtained ?: null,
                'total_marks'           => $totalFull ?: null,
                'percentage'            => $percentage,
                'grade'                 => $grade,
                'is_absent'             => $isAbsent,
                'remarks'               => $remarks,
                'has_existing'          => !empty($studentExistingMarks),
            ];
        }

        return $this->jsonResponse([
            'status'            => true,
            'students'          => $students,
            'mark_distribution' => $markDistribution,
            'grading_system'    => $gradingSystem,
            'grade_rules'       => $gradeRules,
            'subject'           => $subject,
        ]);
    }
}
<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\GradeSystemModel;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Modules\examination\Models\StudentSubjectModel;
use App\Models\SchoolModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;

class SubjectController extends BaseController
{
    protected SubjectModel $SubjectModel;
    protected SchoolModel $SchoolModel;
    protected GradeSystemModel $GradeSystemModel;
    protected MarkDistributionModel $MarkDistributionModel;
    protected StudentSubjectModel $StudentSubjectModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentModel $StudentModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected $db;

    public function __construct()
    {
        $this->SubjectModel = new SubjectModel();
        $this->SchoolModel  = new SchoolModel();
        $this->GradeSystemModel = new GradeSystemModel();
        $this->MarkDistributionModel = new MarkDistributionModel();
        $this->StudentSubjectModel = new StudentSubjectModel();
        $this->EnrollmentModel = new StudentEnrollmentModel();
        $this->StudentModel = new StudentModel();
        $this->YearModel = new AcademicsYearModel();
        $this->ClassModel = new AcademicsClassesModel();
        $this->SectionModel = new AcademicsSectionModel();
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

    protected function getMarkDistributionDropdown(int $school_id): array
    {
        $records = $this->MarkDistributionModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        $list = [];
        foreach ($records as $r) {
            $list[$r->id] = $r->name;
        }
        return $list;
    }

    protected function getGradingDropdown(int $school_id): array
    {
        $records = $this->GradeSystemModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        $list = [];
        foreach ($records as $r) {
            $list[$r->id] = $r->title;
        }
        return $list;
    }

    protected function getSubjectDropdown(int $school_id, int $exclude_id = 0): array
    {
        $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC');
        if ($exclude_id) {
            $this->SubjectModel->where('id !=', $exclude_id);
        }
        $records = $this->SubjectModel->findAll();

        $list = [];
        foreach ($records as $r) {
            $list[$r->id] = $r->title;
        }
        return $list;
    }

    protected function applyOwnerFilter()
    {
        $this->SubjectModel->where('examination_subjects.school_owner_uid', $this->getUserId());
    }

    protected function getSubjectDistributions(int $subject_id): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('examination_subject_distributions');
        $builder->select('examination_subject_distributions.*, examination_mark_distributions.name AS distribution_name');
        $builder->join('examination_mark_distributions', 'examination_mark_distributions.id = examination_subject_distributions.distribution_id', 'left');
        $builder->where('examination_subject_distributions.subject_id', $subject_id);
        $builder->where('examination_subject_distributions.status', 1);
        $builder->orderBy('examination_subject_distributions.sort_order', 'ASC');
        $query = $builder->get();
        
        $result = [];
        foreach ($query->getResult() as $row) {
            $result[] = [
                'id' => $row->id,
                'distribution_id' => $row->distribution_id,
                'distribution_name' => $row->distribution_name,
                'full_mark' => $row->full_mark,
                'pass_mark' => $row->pass_mark,
                'weight_percent' => $row->weight_percent,
                'sort_order' => $row->sort_order,
            ];
        }
        return $result;
    }

    protected function verifyOwnership(string $token): ?object
    {
        return $this->SubjectModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    public function changeStatus($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subjects');
        }

        $response = ['status' => false, 'html' => ''];
        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $id = (int) $record->id;
        $newStatus = $record->status == 1 ? 0 : 1;
        $updated = $this->SubjectModel->update($id, [
            'status'     => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->getUserId(),
        ]);

        $response['status'] = (bool) $updated;
        $response['html']   = $updated
            ? message_generator('success', lang('Common.data_updated'))
            : message_generator('error', lang('Common.data_error_updated'));

        return $this->jsonResponse($response);
    }

    public function emptyTrash()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subjects');
        }

        $token    = $this->request->getPost('token');
        $response = ['status' => false, 'html' => ''];

        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $id = (int) $record->id;
        $deleted = $this->SubjectModel->delete($id);
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function index()
    {
        $user_id   = $this->getUserId();
        $school_id = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;

        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('Subject.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $text   = $this->request->getGet('text');
        $status = $this->request->getGet('status');
        $show   = $this->request->getGet('show');

        $data = compact('text', 'status', 'show');

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

        $this->SubjectModel
            ->select('examination_subjects.*, schools.name AS school_name')
            ->join('schools', 'schools.id = examination_subjects.school_id', 'left');

        $this->applyOwnerFilter();

        if (!empty($schoolIds)) {
            $this->SubjectModel->whereIn('examination_subjects.school_id', $schoolIds);
        } else {
            $this->SubjectModel->where('1 = 0');
        }

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->SubjectModel->where('examination_subjects.school_id', $school_id);
        }

        if ($text) {
            $this->SubjectModel->groupStart();
            $this->SubjectModel->like('examination_subjects.title', $text);
            $this->SubjectModel->orLike('examination_subjects.subject_code', $text);
            $this->SubjectModel->orLike('examination_subjects.short_title', $text);
            $this->SubjectModel->groupEnd();
        }

        if (isset($status) && $status !== '') {
            $this->SubjectModel->where('examination_subjects.status', $status);
        }

        $this->SubjectModel->orderBy('examination_subjects.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->SubjectModel->paginate($perPage);
        $data['pager']           = $this->SubjectModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subjects\list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('Subject.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']              = [];
        $data['is_edit']                = false;
        $data['school_list']            = $this->getSchoolDropdown();
        $data['grade_system_list']      = [];
        $data['mark_distribution_list'] = [];
        $data['subject_list']           = [];
        $data['subject_distributions']  = [];

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subjects\form', $data)
            . view('footer', $footer_data);
    }

    public function edit($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            return redirect()->to('examination/subjects')
                ->with('error', 'Subject not found or access denied.');
        }

        $header_data['page_title'] = lang('Subject.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $school_id = (int) $record->school_id;

        $data['post_data']              = (array) $record;
        $data['is_edit']                = true;
        $data['token']                  = $record->token;
        $data['school_list']            = $this->getSchoolDropdown();
        $data['mark_distribution_list'] = $this->getMarkDistributionDropdown($school_id);
        $data['grading_list']           = $this->getGradingDropdown($school_id);
        $data['subject_list']           = $this->getSubjectDropdown($school_id, $record->id);
        $data['subject_distributions']  = $this->getSubjectDistributions($record->id);

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subjects\form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subjects');
        if ($subscription_check) {
            return $subscription_check;
        }

        $post_data = $this->request->getPost();
        $token     = !empty($post_data['token']) ? $post_data['token'] : null;

        $validationRule = [
            'title'                => 'required|max_length[255]',
            'school_id'            => 'required|is_natural_no_zero',
            'optional'             => 'permit_empty|in_list[0,1]',
            'order_number'         => 'permit_empty|is_natural',
            'enabled_exclude_mark' => 'permit_empty|in_list[0,1]',
            'exclude_mark'         => 'permit_empty|numeric',
            'exclude_percentage'   => 'permit_empty|numeric',
            'exclude_grade_point'  => 'permit_empty|numeric',
            'merge_others_subject' => 'permit_empty|is_natural',
            'mark_calculation'     => 'permit_empty|in_list[0,1]',
            'combine_group'        => 'permit_empty|max_length[255]',
            'combine_order'        => 'permit_empty|is_natural',
            'combine_method'       => 'permit_empty|in_list[single,average,sum]',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $token, $this->validator);
        }

        if ($token) {
            $existing = $this->verifyOwnership($token);
            if (!$existing) {
                return redirect()->to('examination/subjects')
                    ->with('error', 'Subject not found or access denied.');
            }
        }

        $user_id   = $this->getUserId();
        $school_id = (int) $post_data['school_id'];
        $now       = date('Y-m-d H:i:s');

        $saveData = [
            'token'                => $token ?? bin2hex(random_bytes(16)),
            'title'                => $post_data['title'],
            'short_title'          => $post_data['short_title'] ?? null,
            'subject_code'         => $post_data['subject_code'] ?? null,
            'grade_system_id'      => !empty($post_data['grade_system_id']) ? (int) $post_data['grade_system_id'] : null,
            'school_id'            => $school_id,
            'optional'             => !empty($post_data['optional']) ? (int) $post_data['optional'] : 0,
            'order_number'         => !empty($post_data['order_number']) ? (int) $post_data['order_number'] : null,
            'enabled_exclude_mark' => !empty($post_data['enabled_exclude_mark']) ? (int) $post_data['enabled_exclude_mark'] : 0,
            'exclude_mark'         => $post_data['exclude_mark'] !== '' ? (float) $post_data['exclude_mark'] : null,
            'exclude_percentage'   => $post_data['exclude_percentage'] !== '' ? (float) $post_data['exclude_percentage'] : null,
            'exclude_grade_point'  => $post_data['exclude_grade_point'] !== '' ? (float) $post_data['exclude_grade_point'] : null,
            'merge_others_subject' => !empty($post_data['merge_others_subject']) ? (int) $post_data['merge_others_subject'] : null,
            'mark_calculation'     => !empty($post_data['mark_calculation']) ? (int) $post_data['mark_calculation'] : 1,
            'combine_group'        => !empty($post_data['combine_group']) ? $post_data['combine_group'] : null,
            'combine_order'        => !empty($post_data['combine_order']) ? (int) $post_data['combine_order'] : null,
            'combine_method'       => !empty($post_data['combine_method']) ? $post_data['combine_method'] : null,
            'status'               => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid'     => $user_id,
            'updated_at'           => $now,
            'updated_by'           => $user_id,
        ];

        if ($token) {
            $this->SubjectModel->where('token', $token)->set($saveData)->update();
        } else {
            $saveData['created_at'] = $now;
            $saveData['created_by'] = $user_id;
            $this->SubjectModel->insert($saveData);
        }

        // merge_others_subject id
        if (!empty($post_data['merge_others_subject'])) {
            $this->SubjectModel->where('id', $post_data['merge_others_subject'])->set(['hide_mark' => 1])->update();
        }

        return redirect()->to('examination/subjects')
            ->with('success', lang('Subject.sys_saved'));
    }

    public function update($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $post_data = $this->request->getPost();
        $post_data['token'] = $token;

        return $this->store();
    }

    protected function renderForm(array $post_data, ?string $token, $validation = null)
    {
        $header_data['page_title'] = $token
            ? lang('Subject.page_title_edit')
            : lang('Subject.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $school_id = !empty($post_data['school_id']) ? (int) $post_data['school_id'] : 0;

        $form_data['post_data']              = $post_data;
        $form_data['is_edit']                = (bool) $token;
        $form_data['school_list']            = $this->getSchoolDropdown();
        $form_data['mark_distribution_list'] = $school_id ? $this->getMarkDistributionDropdown($school_id) : [];
        $form_data['grading_list']           = $school_id ? $this->getGradingDropdown($school_id) : [];
        $form_data['subject_list']           = $school_id ? $this->getSubjectDropdown($school_id) : [];

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subjects\form', $form_data)
            . view('footer', $footer_data);
    }

    public function trash($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subjects');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subjects');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];
        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->SubjectModel->where('token', $token)->set([
            'status'     => 2,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->getUserId(),
        ])->update();

        $response['status'] = (bool) $updated;
        $response['html']   = $updated
            ? message_generator('success', lang('Common.data_trashed'))
            : message_generator('error', lang('Common.data_error_trashed'));

        return $this->jsonResponse($response);
    }

    public function restore($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subjects');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subjects');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];
        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->SubjectModel->where('token', $token)->set([
            'status'     => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->getUserId(),
        ])->update();

        $response['status'] = (bool) $updated;
        $response['html']   = $updated
            ? message_generator('success', lang('Common.data_restored'))
            : message_generator('error', lang('Common.data_error_restored'));

        return $this->jsonResponse($response);
    }

    public function getDropdownsBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/subjects');
        }

        $school_id = (int) $this->request->getPost('school_id');

        $gradingSystems = $this->getGradingDropdown($school_id);

        // Get subjects for merge_others_subject dropdown (excluding current subject if editing)
        $excludeId = $this->request->getPost('exclude_subject_id') ? (int) $this->request->getPost('exclude_subject_id') : 0;
        $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC');
        if ($excludeId) {
            $this->SubjectModel->where('id !=', $excludeId);
        }
        $subjects = $this->SubjectModel->findAll();
        $subjectList = [];
        foreach ($subjects as $s) {
            $subjectList[$s->id] = $s->title;
        }

        return $this->jsonResponse([
            'status'          => true,
            'grading_systems' => $gradingSystems,
            'subject_list'    => $subjectList,
        ]);
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    // ========================
    // Assign Students to Subjects
    // ========================

    public function assignStudents()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subjects');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        if ($this->request->getMethod() === 'POST') {
            return $this->saveAssignStudents();
        }

        $header_data = [
            'page_title' => 'Assign Students to Subjects',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();
        $data['year_list']   = [];
        $data['class_list']  = [];
        $data['subject_list'] = [];

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subjects\assign_students', $data)
            . view('footer', $footer_data);
    }

    protected function saveAssignStudents()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized.']);
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subjects');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $school_id        = (int) $this->request->getPost('school_id');
        $student_subjects = $this->request->getPost('student_subjects');

        if (!$school_id || !is_array($student_subjects) || empty($student_subjects)) {
            return $this->jsonResponse(['status' => false, 'message' => 'Invalid data. No student assignments provided.']);
        }

        $this->db = \Config\Database::connect();
        $this->db->transStart();

        try {
            $now = date('Y-m-d H:i:s');

            foreach ($student_subjects as $item) {
                $enrollment_id      = (int) ($item['enrollment_id'] ?? 0);
                $subject_ids        = $item['subject_ids'] ?? [];
                $optional_subject_id = !empty($item['optional_subject_id']) ? (int) $item['optional_subject_id'] : null;

                if ($enrollment_id <= 0 || !is_array($subject_ids)) {
                    continue;
                }

                // Get existing records for this enrollment
                $existingRecords = $this->StudentSubjectModel
                    ->where('school_id', $school_id)
                    ->where('enrollment_id', $enrollment_id)
                    ->findAll();
                $existingSubjectIds = [];
                foreach ($existingRecords as $rec) {
                    $existingSubjectIds[] = $rec->subject_id;
                }

                // Remove subjects that were deselected
                $subjectsToRemove = array_diff($existingSubjectIds, $subject_ids);
                if (!empty($subjectsToRemove)) {
                    $this->StudentSubjectModel
                        ->where('school_id', $school_id)
                        ->where('enrollment_id', $enrollment_id)
                        ->whereIn('subject_id', $subjectsToRemove)
                        ->delete();
                }

                // Insert newly selected subjects
                $subjectsToAdd = array_diff($subject_ids, $existingSubjectIds);
                foreach ($subjectsToAdd as $subject_id) {
                    $subject_id = (int) $subject_id;
                    if ($subject_id <= 0) {
                        continue;
                    }
                    $this->StudentSubjectModel->insert([
                        'school_id'      => $school_id,
                        'enrollment_id'  => $enrollment_id,
                        'subject_id'     => $subject_id,
                        'optional_subject_id' => ($optional_subject_id && $optional_subject_id === $subject_id) ? $optional_subject_id : null,
                        'created_at'     => $now,
                    ]);
                }

                // Update optional_subject_id on existing matching records, clear on others
                foreach ($existingRecords as $rec) {
                    $newOptional = ($optional_subject_id && (int) $rec->subject_id === $optional_subject_id) ? $optional_subject_id : null;
                    if ((int) $rec->optional_subject_id !== $newOptional) {
                        $this->StudentSubjectModel
                            ->where('id', $rec->id)
                            ->set('optional_subject_id', $newOptional)
                            ->update();
                    }
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->jsonResponse(['status' => false, 'message' => 'Database error occurred.']);
            }

            return $this->jsonResponse([
                'status'  => true,
                'message' => 'Student subject assignments saved successfully.',
            ]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->jsonResponse(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function getAcademicDataBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subjects/assign-students');
        }

        $school_id = (int) $this->request->getPost('school_id');

        if (!$school_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'School ID required.']);
        }

        // Get years
        $years = $this->YearModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->findAll();
        $year_list = [];
        foreach ($years as $y) {
            $year_list[$y->id] = $y->title;
        }

        // Get classes
        $classes = $this->ClassModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();
        $class_list = [];
        foreach ($classes as $c) {
            $class_list[$c->id] = $c->title;
        }

        // Get subjects
        $subjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();
        $subject_list = [];
        $optional_subject_list = [];
        foreach ($subjects as $s) {
            if (!empty($s->optional)) {
                $optional_subject_list[$s->id] = $s->title;
            } else {
                $subject_list[$s->id] = $s->title;
            }
        }

        return $this->jsonResponse([
            'status'                => true,
            'year_list'             => $year_list,
            'class_list'            => $class_list,
            'subject_list'          => $subject_list,
            'optional_subject_list' => $optional_subject_list,
        ]);
    }

    public function getStudentsByFilter()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subjects/assign-students');
        }

        $school_id  = (int) $this->request->getPost('school_id');
        $class_id   = (int) $this->request->getPost('class_id');
        $year_id    = (int) $this->request->getPost('year_id');

        if (!$school_id || !$class_id || !$year_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Please select School, Academic Year, and Class.', 'students' => []]);
        }

        // Get enrolled students
        $this->EnrollmentModel
            ->select('student_enrollments.*, students.first_name, students.middle_name, students.last_name, students.student_code, students.photo')
            ->join('students', 'students.id = student_enrollments.student_id', 'left')
            ->where('student_enrollments.school_id', $school_id)
            ->where('student_enrollments.class_id', $class_id)
            ->where('student_enrollments.session_id', $year_id)
            ->where('students.status', 1);

        $enrollments = $this->EnrollmentModel
            ->orderBy('student_enrollments.roll_no', 'ASC')
            ->orderBy('students.first_name', 'ASC')
            ->findAll();

        if (empty($enrollments)) {
            return $this->jsonResponse(['status' => false, 'message' => 'No students found for the selected filters.', 'students' => []]);
        }

        // Get all subjects for this school (separate regular and optional)
        $subjects = $this->SubjectModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();
        $subject_list = [];
        $optional_subject_list = [];
        foreach ($subjects as $s) {
            if (!empty($s->optional)) {
                $optional_subject_list[$s->id] = $s->title;
            } else {
                $subject_list[$s->id] = $s->title;
            }
        }

        // Get all existing assignments for this school/class/year
        $enrollmentIds = array_map(function ($e) { return $e->id; }, $enrollments);
        $allAssignments = [];
        $allOptionalAssignments = [];
        if (!empty($enrollmentIds)) {
            $assignments = $this->StudentSubjectModel
                ->where('school_id', $school_id)
                ->whereIn('enrollment_id', $enrollmentIds)
                ->findAll();
            foreach ($assignments as $a) {
                if (!isset($allAssignments[$a->enrollment_id])) {
                    $allAssignments[$a->enrollment_id] = [];
                }
                $allAssignments[$a->enrollment_id][] = $a->subject_id;

                // Track optional subject assignment
                if (!empty($a->optional_subject_id)) {
                    $allOptionalAssignments[$a->enrollment_id] = $a->optional_subject_id;
                }
            }
        }

        $students = [];
        foreach ($enrollments as $enr) {
            $studentName = trim($enr->first_name . ' ' . ($enr->middle_name ?? '') . ' ' . $enr->last_name);
            $studentName = preg_replace('/\s+/', ' ', $studentName);

            $students[] = [
                'enrollment_id'              => $enr->id,
                'student_id'                 => $enr->student_id,
                'student_name'               => $studentName,
                'student_code'               => $enr->student_code ?? '',
                'roll_no'                    => $enr->roll_no ?? '',
                'assigned_subject_ids'       => $allAssignments[$enr->id] ?? [],
                'assigned_optional_subject_id' => $allOptionalAssignments[$enr->id] ?? null,
            ];
        }

        return $this->jsonResponse([
            'status'                => true,
            'students'              => $students,
            'subject_list'          => $subject_list,
            'optional_subject_list' => $optional_subject_list,
        ]);
    }

    // ========================
    // Subject Distribution CRUD
    // ========================

    public function saveDistribution()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Invalid request.']);
        }

        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized.']);
        }

        $token = $this->request->getPost('token');
        if (!$token) {
            return $this->jsonResponse(['status' => false, 'message' => 'Subject token required.']);
        }

        $subject = $this->verifyOwnership($token);
        if (!$subject) {
            return $this->jsonResponse(['status' => false, 'message' => 'Subject not found or access denied.']);
        }

        $distribution_id = $this->request->getPost('distribution_id');
        $full_mark = $this->request->getPost('full_mark');
        $pass_mark = $this->request->getPost('pass_mark');
        $weight_percent = $this->request->getPost('weight_percent');
        $sort_order = $this->request->getPost('sort_order');

        if (empty($distribution_id) || $full_mark === '' || $pass_mark === '') {
            return $this->jsonResponse(['status' => false, 'message' => 'Distribution, full mark, and pass mark are required.']);
        }

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        // Check if editing existing distribution
        $edit_id = $this->request->getPost('edit_id');
        if ($edit_id) {
            $updated = $db->table('examination_subject_distributions')
                ->where('id', $edit_id)
                ->where('subject_id', $subject->id)
                ->update([
                    'distribution_id' => $distribution_id,
                    'full_mark' => $full_mark,
                    'pass_mark' => $pass_mark,
                    'weight_percent' => $weight_percent ?? 0,
                    'sort_order' => $sort_order ?? 0,
                    'updated_at' => $now,
                    'updated_by' => $user_id,
                ]);
            
            if ($updated) {
                return $this->jsonResponse(['status' => true, 'message' => 'Distribution updated successfully.']);
            } else {
                return $this->jsonResponse(['status' => false, 'message' => 'Failed to update distribution.']);
            }
        } else {
            // Check if this distribution already exists for this subject
            $existing = $db->table('examination_subject_distributions')
                ->where('subject_id', $subject->id)
                ->where('distribution_id', $distribution_id)
                ->get()
                ->getRow();

            if ($existing) {
                return $this->jsonResponse(['status' => false, 'message' => 'This distribution already exists for this subject.']);
            }

            $inserted = $db->table('examination_subject_distributions')->insert([
                'token' => bin2hex(random_bytes(16)),
                'subject_id' => $subject->id,
                'distribution_id' => $distribution_id,
                'full_mark' => $full_mark,
                'pass_mark' => $pass_mark,
                'weight_percent' => $weight_percent ?? 0,
                'sort_order' => $sort_order ?? 0,
                'school_id' => $subject->school_id,
                'school_owner_uid' => $user_id,
                'status' => 1,
                'created_at' => $now,
                'created_by' => $user_id,
                'updated_at' => $now,
                'updated_by' => $user_id,
            ]);

            if ($inserted) {
                return $this->jsonResponse(['status' => true, 'message' => 'Distribution added successfully.']);
            } else {
                return $this->jsonResponse(['status' => false, 'message' => 'Failed to add distribution.']);
            }
        }
    }

    public function getDistribution()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Invalid request.']);
        }

        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized.']);
        }

        $id = $this->request->getPost('id');
        if (!$id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Distribution ID required.']);
        }

        $db = \Config\Database::connect();
        $distribution = $db->table('examination_subject_distributions')
            ->where('id', $id)
            ->get()
            ->getRow();

        if (!$distribution) {
            return $this->jsonResponse(['status' => false, 'message' => 'Distribution not found.']);
        }

        // Verify ownership through subject
        $subject = $this->SubjectModel->find($distribution->subject_id);
        if (!$subject || $subject->school_owner_uid != $user_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Access denied.']);
        }

        return $this->jsonResponse([
            'status' => true,
            'data' => [
                'id' => $distribution->id,
                'distribution_id' => $distribution->distribution_id,
                'full_mark' => $distribution->full_mark,
                'pass_mark' => $distribution->pass_mark,
                'weight_percent' => $distribution->weight_percent,
                'sort_order' => $distribution->sort_order,
            ]
        ]);
    }

    public function deleteDistribution()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Invalid request.']);
        }

        $user_id = $this->getUserId();
        if (!$user_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthorized.']);
        }

        $id = $this->request->getPost('id');
        if (!$id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Distribution ID required.']);
        }

        $db = \Config\Database::connect();
        $distribution = $db->table('examination_subject_distributions')
            ->where('id', $id)
            ->get()
            ->getRow();

        if (!$distribution) {
            return $this->jsonResponse(['status' => false, 'message' => 'Distribution not found.']);
        }

        // Verify ownership through subject
        $subject = $this->SubjectModel->find($distribution->subject_id);
        if (!$subject || $subject->school_owner_uid != $user_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Access denied.']);
        }

        $deleted = $db->table('examination_subject_distributions')
            ->where('id', $id)
            ->delete();

        if ($deleted) {
            return $this->jsonResponse(['status' => true, 'message' => 'Distribution deleted successfully.']);
        } else {
            return $this->jsonResponse(['status' => false, 'message' => 'Failed to delete distribution.']);
        }
    }
}

<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Models\ExamModel;
use App\Models\AcademicsYearModel;
use App\Models\SchoolModel;

class ExamSetupController extends BaseController
{
    protected ExamModel $ExamModel;
    protected AcademicsYearModel $YearModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->ExamModel  = new ExamModel();
        $this->YearModel  = new AcademicsYearModel();
        $this->SchoolModel = new SchoolModel();
    }

    protected function getUserId(): int
    {
        return (int) session('user_id');
    }

    /**
     * Get academic year options for the given school (active only).
     */
    protected function getYearDropdown(int $school_id): array
    {
        $records = $this->YearModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'DESC')
            ->findAll();

        $list = [];
        foreach ($records as $r) {
            $list[$r->id] = $r->title;
        }
        return $list;
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

    protected function applyOwnerFilter()
    {
        $this->ExamModel->where('examination_exams.school_owner_uid', $this->getUserId());
    }

    protected function verifyOwnership(string $token): ?object
    {
        return $this->ExamModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    public function index()
    {
        $user_id   = $this->getUserId();
        $school_id = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;

        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('Exam.page_title_list'),
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

        $this->ExamModel
            ->select('examination_exams.*, schools.name AS school_name')
            ->join('schools', 'schools.id = examination_exams.school_id', 'left');

        $this->applyOwnerFilter();

        if (!empty($schoolIds)) {
            $this->ExamModel->whereIn('examination_exams.school_id', $schoolIds);
        } else {
            $this->ExamModel->where('1 = 0');
        }

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->ExamModel->where('examination_exams.school_id', $school_id);
        }

        if ($text) {
            $this->ExamModel->like('examination_exams.title', $text);
        }

        if (isset($status) && $status !== '') {
            $this->ExamModel->where('examination_exams.status', $status);
        }

        $this->ExamModel->orderBy('examination_exams.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->ExamModel->paginate($perPage);
        $data['pager']           = $this->ExamModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;

        return view('header', $header_data)
            . view('App\Modules\examination\Views\exam_setup\list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('Exam.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']   = [];
        $data['is_edit']     = false;
        $data['school_list'] = $this->getSchoolDropdown();
        $data['year_list']   = [];

        return view('header', $header_data)
            . view('App\Modules\examination\Views\exam_setup\form', $data)
            . view('footer', $footer_data);
    }

    public function edit($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            return redirect()->to('examination/exam-setup')
                ->with('error', lang('Exam.exam_not_found'));
        }

        $header_data['page_title'] = lang('Exam.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']   = (array) $record;
        $data['is_edit']     = true;
        $data['school_list'] = $this->getSchoolDropdown();
        $data['year_list']   = $this->getYearDropdown($record->school_id);
        return view('header', $header_data)
            . view('App\Modules\examination\Views\exam_setup\form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/exam-setup');
        if ($subscription_check) {
            return $subscription_check;
        }

        $post_data = $this->request->getPost();
        $token     = !empty($post_data['token']) ? $post_data['token'] : null;

        $validationRule = [
            'title'      => 'required|max_length[255]',
            'school_id'  => 'required|is_natural_no_zero',
            'year_id'    => 'required|is_natural_no_zero',
            'exam_order' => 'permit_empty|is_natural',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $token, $this->validator);
        }

        if ($token) {
            $existing = $this->verifyOwnership($token);
            if (!$existing) {
                return redirect()->to('examination/exam-setup')
                    ->with('error', lang('Exam.exam_not_found'));
            }
        }

        $user_id   = $this->getUserId();
        $school_id = (int) $post_data['school_id'];
        $now       = date('Y-m-d H:i:s');

        // Format times: HTML time input gives HH:MM, DB columns are datetime
        // Combine exam_date with time to create full datetime
        $examDate  = $post_data['exam_date'] ?? null;
        $startTime = $post_data['start_time'] ?? null;
        $endTime   = $post_data['end_time'] ?? null;

        $startDatetime = null;
        $endDatetime   = null;

        if ($examDate && $startTime) {
            $startDatetime = $examDate . ' ' . $startTime . ':00';
        }
        if ($examDate && $endTime) {
            $endDatetime = $examDate . ' ' . $endTime . ':00';
        }

        $saveData = [
            'title'            => $post_data['title'],
            'exam_short_name'  => $post_data['exam_short_name'] ?? null,
            'is_aggregate_result' => isset($post_data['is_aggregate_result']) ? (int) $post_data['is_aggregate_result'] : 1,
            'weight_percentage'=> !empty($post_data['weight_percentage']) ? (float) $post_data['weight_percentage'] : null,
            'year_id'          => (int) $post_data['year_id'],
            'exam_date'        => $examDate,
            'description'      => $post_data['description'] ?? null,
            'duration'         => !empty($post_data['duration']) ? (int) $post_data['duration'] : null,
            'start_time'       => $startDatetime,
            'end_time'         => $endDatetime,
            'school_id'        => $school_id,
            'status'           => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid' => $user_id,
            'updated_at'       => $now,
            'updated_by'       => $user_id,
            'exam_order'       => !empty($post_data['exam_order']) ? (int) $post_data['exam_order'] : null,
        ];

        if ($token) {
            $saveData['token'] = $token;
            $this->ExamModel->where('token', $token)->set($saveData)->update();
        } else {
            $saveData['created_at'] = $now;
            $saveData['created_by'] = $user_id;
            $saveData['token'] = bin2hex(random_bytes(16));
            $this->ExamModel->insert($saveData);
        }

        return redirect()->to('examination/exam-setup')
            ->with('success', lang('Exam.sys_saved'));
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
            ? lang('Exam.page_title_edit')
            : lang('Exam.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data']   = $post_data;
        $form_data['is_edit']     = (bool) $token;
        $form_data['school_list'] = $this->getSchoolDropdown();

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\exam_setup\form', $form_data)
            . view('footer', $footer_data);
    }

    public function trash($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/exam-setup');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/exam-setup');
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

        $updated = $this->ExamModel->where('token', $token)->set([
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

    public function emptyTrash()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/exam-setup');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/exam-setup');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
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

        $deleted = $this->ExamModel->where('token', $token)->delete();
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function restore($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/exam-setup');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/exam-setup');
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

        $updated = $this->ExamModel->where('token', $token)->set([
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

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    /**
     * AJAX endpoint: get academic years for a given school.
     */
    public function getYearsBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/exam-setup');
        }

        $school_id = (int) $this->request->getPost('school_id');

        return $this->jsonResponse([
            'status' => true,
            'years'  => $this->getYearDropdown($school_id),
        ]);
    }

    /**
     * AJAX endpoint: get exams for a given school and year.
     */
    public function getExamsBySchoolAndYear()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/exam-setup');
        }

        $school_id = (int) $this->request->getPost('school_id');
        $year_id = (int) $this->request->getPost('year_id');

        $exams = [];
        if ($school_id && $year_id) {
            $records = $this->ExamModel
                ->where('school_id', $school_id)
                ->where('year_id', $year_id)
                ->where('status', 1)
                ->orderBy('exam_order', 'ASC')
                ->orderBy('title', 'ASC')
                ->findAll();
            
            foreach ($records as $e) {
                $exams[$e->id] = $e->title;
            }
        }

        return $this->jsonResponse([
            'status' => true,
            'exams' => $exams,
        ]);
    }
}

<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Models\GradeSystemModel;
use App\Models\SchoolModel;

class GradeSystemController extends BaseController
{
    protected GradeSystemModel $GradeSystemModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->GradeSystemModel = new GradeSystemModel();
        $this->SchoolModel      = new SchoolModel();
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

    protected function applyOwnerFilter()
    {
        $this->GradeSystemModel->where('examination_grade_systems.school_owner_uid', $this->getUserId());
    }

    protected function verifyOwnershipByToken(string $token): ?object
    {
        return $this->GradeSystemModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function grades()
    {
        $user_id   = $this->getUserId();
        $school_id = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;

        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('GradeSystem.page_title_list'),
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

        $this->GradeSystemModel
            ->select('examination_grade_systems.*, schools.name AS school_name')
            ->join('schools', 'schools.id = examination_grade_systems.school_id', 'left');

        $this->applyOwnerFilter();

        if (!empty($schoolIds)) {
            $this->GradeSystemModel->whereIn('examination_grade_systems.school_id', $schoolIds);
        } else {
            $this->GradeSystemModel->where('1 = 0');
        }

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->GradeSystemModel->where('examination_grade_systems.school_id', $school_id);
        }

        if ($text) {
            $this->GradeSystemModel->like('examination_grade_systems.title', $text);
        }

        if (isset($status) && $status !== '') {
            $this->GradeSystemModel->where('examination_grade_systems.status', $status);
        }

        $this->GradeSystemModel->orderBy('examination_grade_systems.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->GradeSystemModel->paginate($perPage);
        $data['pager']           = $this->GradeSystemModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;

        return view('header', $header_data)
            . view('App\Modules\examination\Views\grade_systems\list', $data)
            . view('footer', $footer_data);
    }

    public function createGrade()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('GradeSystem.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']   = [];
        $data['is_edit']     = false;
        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\grade_systems\form', $data)
            . view('footer', $footer_data);
    }

    public function editGrade(string $token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnershipByToken($token);
        if (!$record) {
            return redirect()->to('examination/grade-systems')
                ->with('error', 'Grade System not found or access denied.');
        }

        $header_data['page_title'] = lang('GradeSystem.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']   = (array) $record;
        $data['is_edit']     = true;
        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\grade_systems\form', $data)
            . view('footer', $footer_data);
    }

    public function storeGrade()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/grade-systems');
        if ($subscription_check) {
            return $subscription_check;
        }

        $post_data = $this->request->getPost();
        $id        = !empty($post_data['id']) ? (int) $post_data['id'] : null;

        $validationRule = [
            'title'     => 'required|max_length[255]',
            'total_mark' => 'required|numeric',
            'school_id' => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $id, $this->validator);
        }

        if ($id) {
            $existing = $this->GradeSystemModel->find($id);
            if (!$existing || $existing->school_owner_uid != $this->getUserId()) {
                return redirect()->to('examination/grade-systems')
                    ->with('error', 'Grade System not found or access denied.');
            }
        }

        $user_id   = $this->getUserId();
        $school_id = (int) $post_data['school_id'];
        $now       = date('Y-m-d H:i:s');

        $saveData = [
            'token'           => $this->generateToken(),
            'title'           => $post_data['title'],
            'description'     => $post_data['description'] ?? null,
            'total_mark'      => (float) $post_data['total_mark'],
            'school_id'       => $school_id,
            'status'          => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid'=> $user_id,
            'updated_at'      => $now,
            'updated_by'      => $user_id,
        ];

        if ($id) {
            $this->GradeSystemModel->update($id, $saveData);
        } else {
            $saveData['created_at'] = $now;
            $saveData['created_by'] = $user_id;
            $this->GradeSystemModel->insert($saveData);
        }

        return redirect()->to('examination/grade-systems')
            ->with('success', lang('GradeSystem.sys_saved'));
    }

    protected function renderForm(array $post_data, ?int $id, $validation = null)
    {
        $header_data['page_title'] = $id
            ? lang('GradeSystem.page_title_edit')
            : lang('GradeSystem.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data']   = $post_data;
        $form_data['is_edit']     = (bool) $id;
        $form_data['school_list'] = $this->getSchoolDropdown();

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\grade_systems\form', $form_data)
            . view('footer', $footer_data);
    }

    public function trashGrade(string $token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/grade-systems');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/grade-systems');
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

        $updated = $this->GradeSystemModel->update($record->id, [
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

    public function empty_trashGrade()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/grade-systems');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/grade-systems');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $token    = $this->request->getPost('token');
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

        $deleted = $this->GradeSystemModel->delete($record->id);
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function restoreGrade(string $token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/grade-systems');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/grade-systems');
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

        $updated = $this->GradeSystemModel->update($record->id, [
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

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}
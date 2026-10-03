<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Models\SchoolModel;

class MarkDistributionController extends BaseController
{
    protected MarkDistributionModel $MarkDistributionModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->MarkDistributionModel = new MarkDistributionModel();
        $this->SchoolModel           = new SchoolModel();
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

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function applyOwnerFilter()
    {
        $this->MarkDistributionModel->where('examination_mark_distributions.school_owner_uid', $this->getUserId());
    }

    protected function verifyOwnership(string $token): ?object
    {
        return $this->MarkDistributionModel
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
            'page_title' => lang('MarkDistribution.page_title_list'),
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

        $this->MarkDistributionModel
            ->select('examination_mark_distributions.*, schools.name AS school_name')
            ->join('schools', 'schools.id = examination_mark_distributions.school_id', 'left');

        $this->applyOwnerFilter();

        if (!empty($schoolIds)) {
            $this->MarkDistributionModel->whereIn('examination_mark_distributions.school_id', $schoolIds);
        } else {
            $this->MarkDistributionModel->where('1 = 0');
        }

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->MarkDistributionModel->where('examination_mark_distributions.school_id', $school_id);
        }

        if ($text) {
            $this->MarkDistributionModel->groupStart();
            $this->MarkDistributionModel->like('examination_mark_distributions.name', $text);
            $this->MarkDistributionModel->orLike('examination_mark_distributions.code', $text);
            $this->MarkDistributionModel->groupEnd();
        }

        if (isset($status) && $status !== '') {
            $this->MarkDistributionModel->where('examination_mark_distributions.status', $status);
        }

        $this->MarkDistributionModel->orderBy('examination_mark_distributions.sort_order', 'ASC');
        $this->MarkDistributionModel->orderBy('examination_mark_distributions.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->MarkDistributionModel->paginate($perPage);
        $data['pager']           = $this->MarkDistributionModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;

        return view('header', $header_data)
            . view('App\Modules\examination\Views\mark_distributions\list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('MarkDistribution.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']   = [];
        $data['is_edit']     = false;
        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\mark_distributions\form', $data)
            . view('footer', $footer_data);
    }

    public function edit($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            return redirect()->to('examination/mark-distributions')
                ->with('error', 'Mark Distribution not found or access denied.');
        }

        $header_data['page_title'] = lang('MarkDistribution.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']   = (array) $record;
        $data['is_edit']     = true;
        $data['token']       = $record->token;
        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\mark_distributions\form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/mark-distributions');
        if ($subscription_check) {
            return $subscription_check;
        }

        $post_data = $this->request->getPost();
        $token     = !empty($post_data['token']) ? $post_data['token'] : null;

        $existing = null;
        if ($token) {
            $existing = $this->verifyOwnership($token);
            if (!$existing) {
                return redirect()->to('examination/mark-distributions')
                    ->with('error', 'Mark Distribution not found or access denied.');
            }
        }

        $validationRule = [
            'name'     => 'required|max_length[100]',
            'school_id' => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $token, $this->validator);
        }

        $user_id   = $this->getUserId();
        $school_id = (int) $post_data['school_id'];
        $now       = date('Y-m-d H:i:s');

        if (!$token) {
            $token = $this->generateToken();
        }

        $saveData = [
            'token'       => $token,
            'name'        => $post_data['name'],
            'code'        => $post_data['code'] ?? null,
            'description' => $post_data['description'] ?? null,
            'sort_order'  => (int) ($post_data['sort_order'] ?? 0),
            'school_id'   => $school_id,
            'status'      => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid' => $user_id,
            'updated_at'  => $now,
            'updated_by'  => $user_id,
        ];

        if ($token) {
            $this->MarkDistributionModel->where('token', $token)->set($saveData)->update();
        } else {
            $saveData['created_at'] = $now;
            $saveData['created_by'] = $user_id;
            $this->MarkDistributionModel->insert($saveData);
        }

        return redirect()->to('examination/mark-distributions')
            ->with('success', lang('MarkDistribution.sys_saved'));
    }

    public function update($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            return redirect()->to('examination/mark-distributions')
                ->with('error', 'Mark Distribution not found or access denied.');
        }

        return $this->store();
    }

    public function trash($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/mark-distributions');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/mark-distributions');
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

        $updated = $this->MarkDistributionModel->where('token', $token)->set([
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
            return redirect()->to('examination/mark-distributions');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/mark-distributions');
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

        $updated = $this->MarkDistributionModel->where('token', $token)->set([
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

    public function emptyTrash()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/mark-distributions');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/mark-distributions');
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

        $deleted = $this->MarkDistributionModel->where('token', $token)->delete();
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    protected function renderForm(array $post_data, ?string $token, $validation = null)
    {
        $header_data['page_title'] = $token
            ? lang('MarkDistribution.page_title_edit')
            : lang('MarkDistribution.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $token = $post_data['token'] ?? '';

        $form_data['post_data']   = $post_data;
        $form_data['is_edit']     = (bool) $token;
        $form_data['token']       = $token;
        $form_data['school_list'] = $this->getSchoolDropdown();

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\mark_distributions\form', $form_data)
            . view('footer', $footer_data);
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}
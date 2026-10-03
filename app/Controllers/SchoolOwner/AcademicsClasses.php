<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\AcademicsClassesModel;
use App\Models\SchoolModel;

class AcademicsClasses extends BaseController
{
    protected AcademicsClassesModel $ClassModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->ClassModel  = new AcademicsClassesModel();
        $this->SchoolModel = new SchoolModel();
    }

    /**
     * Get the current logged-in user ID.
     */
    protected function getUserId(): int
    {
        return (int) session('user_id');
    }

    /**
     * Get schools accessible by the current user.
     */
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

    /**
     * Build a dropdown-friendly array from the school list.
     */
    protected function getSchoolDropdown(): array
    {
        $schools = $this->getUserSchools();
        $list = [];
        foreach ($schools as $s) {
            $list[$s->id] = $s->name;
        }
        return $list;
    }

    /**
     * Add a WHERE school_owner_uid = current user to the model query.
     */
    protected function applyOwnerFilter()
    {
        $this->ClassModel->where('school_owner_uid', $this->getUserId());
    }

    /**
     * Verify that a class record belongs to the current user by token.
     */
    protected function verifyOwnership(string $token): ?object
    {
        return $this->ClassModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    public function classes()
    {
        $user_id    = $this->getUserId();
        $school_id  = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;

        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('Class.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $text   = $this->request->getGet('text');
        $status = $this->request->getGet('status');
        $show   = $this->request->getGet('show');

        $data = compact('text', 'status', 'show');

        // Filter by user's schools
        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

        // Join with schools to get school name
        $this->ClassModel
            ->select('academic_classes.*, schools.name AS school_name')
            ->join('schools', 'schools.id = academic_classes.school_id', 'left');

        // Always filter by school_owner_uid
        $this->applyOwnerFilter();

        if (!empty($schoolIds)) {
            $this->ClassModel->whereIn('academic_classes.school_id', $schoolIds);
        } else {
            // No schools assigned → no results
            $this->ClassModel->where('1 = 0');
        }

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->ClassModel->where('academic_classes.school_id', $school_id);
        }

        if ($text) {
            $this->ClassModel->like('academic_classes.title', $text);
        }

        if (isset($status) && $status !== '') {
            $this->ClassModel->where('academic_classes.status', $status);
        }

        $this->ClassModel->orderBy('academic_classes.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->ClassModel->paginate($perPage);
        $data['pager']           = $this->ClassModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;

        return view('header', $header_data)
            . view('school_owner/academics/classes/list', $data)
            . view('footer', $footer_data);
    }

    public function createClass()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('Class.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']      = [];
        $data['is_edit']        = false;
        $data['school_list']    = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('school_owner/academics/classes/form', $data)
            . view('footer', $footer_data);
    }

    public function editClass($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnership($token);

        if (!$record) {
            return redirect()->to('school-owner/academics/classes')
                ->with('error', 'Academic Class not found or access denied.');
        }

        $header_data['page_title'] = lang('Class.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']      = (array) $record;
        $data['is_edit']        = true;
        $data['token']          = $record->token;
        $data['school_list']    = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('school_owner/academics/classes/form', $data)
            . view('footer', $footer_data);
    }

    public function storeClass()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/academics/classes');
        if ($subscription_check) {
            return $subscription_check;
        }

        $post_data = $this->request->getPost();
        $token     = !empty($post_data['token']) ? $post_data['token'] : null;

        $validationRule = [
            'title'     => 'required|max_length[255]',
            'school_id' => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $token, $this->validator);
        }

        // If updating, verify ownership
        if ($token) {
            $existing = $this->verifyOwnership($token);
            if (!$existing) {
                return redirect()->to('school-owner/academics/classes')
                    ->with('error', 'Academic Class not found or access denied.');
            }
        }

        $user_id   = $this->getUserId();
        $school_id = (int) $post_data['school_id'];
        $now       = date('Y-m-d H:i:s');

        $saveData = [
            'token'                => $token ?? bin2hex(random_bytes(16)),
            'title'                => $post_data['title'],
            'school_id'            => $school_id,
            'status'               => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid'     => $user_id,
            'updated_at'           => $now,
            'updated_by'           => $user_id,
        ];

        if ($token) {
            $this->ClassModel->where('token', $token)->set($saveData)->update();
        } else {
            $saveData['created_at'] = $now;
            $saveData['created_by'] = $user_id;
            $this->ClassModel->insert($saveData);
        }

        return redirect()->to('school-owner/academics/classes')
            ->with('success', lang('Class.sys_saved'));
    }

    protected function renderForm(array $post_data, ?string $token, $validation = null)
    {
        $header_data['page_title'] = $token
            ? lang('Class.page_title_edit')
            : lang('Class.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data']   = $post_data;
        $form_data['is_edit']     = (bool) $token;
        $form_data['token']       = $token;
        $form_data['school_list'] = $this->getSchoolDropdown();

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('school_owner/academics/classes/form', $form_data)
            . view('footer', $footer_data);
    }

    public function trashClass($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/academics/classes');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/academics/classes');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];

        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        // Verify ownership before trashing
        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->ClassModel->where('token', $token)->set([
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

    public function empty_trashClass($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/academics/classes');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/academics/classes');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];

        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        // Verify ownership before permanent delete
        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $deleted = $this->ClassModel->where('token', $token)->delete();
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function restoreClass($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/academics/classes');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/academics/classes');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];

        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        // Verify ownership before restoring
        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->ClassModel->where('token', $token)->set([
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
}
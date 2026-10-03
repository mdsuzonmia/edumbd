<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Models\GradeRuleModel;
use App\Modules\examination\Models\GradeSystemModel;
use App\Models\SchoolModel;

class GradeRuleController extends BaseController
{
    protected GradeRuleModel $GradeRuleModel;
    protected GradeSystemModel $GradeSystemModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->GradeRuleModel     = new GradeRuleModel();
        $this->GradeSystemModel   = new GradeSystemModel();
        $this->SchoolModel        = new SchoolModel();
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

    /**
     * Get all grade systems for filter dropdown (for user's schools) - returns token-based array.
     */
    protected function getAllGradeSystemsForFilter(): array
    {
        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

        if (empty($schoolIds)) {
            return [];
        }

        $systems = $this->GradeSystemModel
            ->select('id, token, title')
            ->whereIn('school_id', $schoolIds)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        $list = [];
        foreach ($systems as $s) {
            $list[$s->token] = $s->title;
        }
        return $list;
    }

    /**
     * Get grade systems for the given school (active only) - returns token-based array.
     */
    protected function getGradeSystemDropdown(int $school_id): array
    {
        $systems = $this->GradeSystemModel
            ->where('school_id', $school_id)
            ->where('status', 1)
            ->orderBy('title', 'ASC')
            ->findAll();

        $list = [];
        foreach ($systems as $s) {
            $list[$s->token] = $s->title;
        }
        return $list;
    }

    /**
     * Convert grade system token to ID.
     */
    protected function getGradeSystemIdByToken(string $token): ?int
    {
        $system = $this->GradeSystemModel
            ->select('id')
            ->where('token', $token)
            ->first();

        return $system ? (int) $system->id : null;
    }

    protected function applyOwnerFilter()
    {
        $this->GradeRuleModel->where('examination_grade_rules.school_owner_uid', $this->getUserId());
    }

    protected function verifyOwnershipByToken(string $token): ?object
    {
        return $this->GradeRuleModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    public function index($grade_system_token = 0)
    {
        $user_id = $this->getUserId();

        // Get grade_system_id by token if provided
        if ($grade_system_token) {
            $grade_system_id = $this->getGradeSystemIdByToken($grade_system_token);
        }else {
            $grade_system_id = null;
        }

        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('GradeRule.page_title_list'),
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

        $this->GradeRuleModel
            ->select('examination_grade_rules.*, examination_grade_systems.title AS system_title')
            ->join('examination_grade_systems', 'examination_grade_systems.id = examination_grade_rules.grade_system_id', 'left');

        $this->applyOwnerFilter();

        // Filter by user's schools through grade_systems
        if (!empty($schoolIds)) {
            $this->GradeRuleModel->whereIn('examination_grade_systems.school_id', $schoolIds);
        } else {
            $this->GradeRuleModel->where('1 = 0');
        }

        if ($grade_system_id) {
            $this->GradeRuleModel->where('examination_grade_rules.grade_system_id', $grade_system_id);
        }

        if ($text) {
            $this->GradeRuleModel->like('examination_grade_rules.title', $text);
        }

        if (isset($status) && $status !== '') {
            $this->GradeRuleModel->where('examination_grade_rules.status', $status);
        }

        $this->GradeRuleModel->orderBy('examination_grade_rules.field_order', 'ASC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']                = $this->GradeRuleModel->paginate($perPage);
        $data['pager']                = $this->GradeRuleModel->pager;
        $data['pagerTemplate']        = 'custom_pagination';
        $data['school_list']          = $this->getSchoolDropdown();
        $data['selected_system_id']   = $grade_system_token;
        $data['system_filter_list']   = $this->getAllGradeSystemsForFilter();

        return view('header', $header_data)
            . view('App\Modules\examination\Views\grade_rules\list', $data)
            . view('footer', $footer_data);
    }

    public function create($grade_system_token = '')
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('GradeRule.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']       = [];
        $data['is_edit']         = false;
        $data['school_list']     = $this->getSchoolDropdown();
        $data['system_list']     = $this->getAllGradeSystemsForFilter();
        $data['grade_system_id'] = $grade_system_token;

        return view('header', $header_data)
            . view('App\Modules\examination\Views\grade_rules\form', $data)
            . view('footer', $footer_data);
    }

    public function edit(string $token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnershipByToken($token);
        if (!$record) {
            return redirect()->to('examination/grade-rules')
                ->with('error', 'Grade Rule not found or access denied.');
        }

        $header_data['page_title'] = lang('GradeRule.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $post_data = (array) $record;

        // Get school_id from the related grade system
        $grade_system = $this->GradeSystemModel->find($post_data['grade_system_id']);
        $school_id = $grade_system ? (int) $grade_system->school_id : 0;

        $data['post_data']       = $post_data;
        $data['is_edit']         = true;
        $data['school_list']     = $this->getSchoolDropdown();
        $data['system_list']     = $school_id ? $this->getGradeSystemDropdown($school_id) : [];
        $data['grade_system_id'] = $grade_system->token ?? '';

        return view('header', $header_data)
            . view('App\Modules\examination\Views\grade_rules\form', $data)
            . view('footer', $footer_data);
    }

    public function store()
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

        // Convert grade_system_token to grade_system_id
        $grade_system_token = $post_data['grade_system_id'] ?? '';
        $grade_system_id = $this->getGradeSystemIdByToken($grade_system_token);

        $validationRule = [
            'grade_system_id' => 'required',
            'title'           => 'required|max_length[255]',
            'grade_point'     => 'required|numeric',
            'mark_from'       => 'required|numeric',
            'mark_to'         => 'required|numeric',
            'field_order'     => 'required|integer',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $id, $this->validator);
        }

        if (!$grade_system_id) {
            return redirect()->back()
                ->with('error', 'Invalid grade system selected.')
                ->withInput();
        }

        if ($id) {
            $existing = $this->GradeRuleModel->find($id);
            if (!$existing || $existing->school_owner_uid != $this->getUserId()) {
                return redirect()->to('examination/grade-systems/rules/'.$grade_system_token)
                    ->with('error', 'Grade Rule not found or access denied.');
            }
        }

        $user_id = $this->getUserId();
        $now     = date('Y-m-d H:i:s');

        $saveData = [
            'token'           => $this->generateToken(),
            'grade_system_id' => $grade_system_id,
            'title'           => $post_data['title'],
            'grade_point'     => (float) $post_data['grade_point'],
            'mark_from'       => (float) $post_data['mark_from'],
            'mark_to'         => (float) $post_data['mark_to'],
            'field_order'     => (int) $post_data['field_order'],
            'remarks'         => $post_data['remarks'] ?? null,
            'status'          => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid'=> $user_id
        ];

        if ($id) {
            $saveData['updated_at'] = $now;
            $saveData['updated_by'] = $user_id;
            $this->GradeRuleModel->update($id, $saveData);
        } else {
            $saveData['created_at'] = $now;
            $saveData['created_by'] = $user_id;
            $this->GradeRuleModel->insert($saveData);
        }

        return redirect()->to('examination/grade-systems/rules/'.$grade_system_token)
            ->with('success', lang('GradeRule.sys_saved'));
    }

    protected function renderForm(array $post_data, ?int $id, $validation = null)
    {
        $header_data['page_title'] = $id
            ? lang('GradeRule.page_title_edit')
            : lang('GradeRule.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $school_id = !empty($post_data['school_id']) ? (int) $post_data['school_id'] : 0;

        $form_data['post_data']       = $post_data;
        $form_data['is_edit']         = (bool) $id;
        $form_data['school_list']     = $this->getSchoolDropdown();
        $form_data['system_list']     = $this->getAllGradeSystemsForFilter();
        $form_data['grade_system_id'] = !empty($post_data['grade_system_id']) ? $post_data['grade_system_id'] : '';

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\grade_rules\form', $form_data)
            . view('footer', $footer_data);
    }

    public function trash(string $token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/grade-systems/rules');
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

        $updated = $this->GradeRuleModel->update($record->id, [
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
            return redirect()->to('examination/grade-systems/rules');
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

        $deleted = $this->GradeRuleModel->delete($record->id);
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function restore(string $token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/grade-systems/rules');
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

        $updated = $this->GradeRuleModel->update($record->id, [
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

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    /**
     * AJAX endpoint: get grade systems for a given school.
     */
    public function getGradeSystemsBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/grade-systems/rules');
        }

        $school_id = (int) $this->request->getPost('school_id');
        $systems = $this->getGradeSystemDropdown($school_id);

        return $this->jsonResponse([
            'status'   => true,
            'systems'  => $systems,
        ]);
    }
}
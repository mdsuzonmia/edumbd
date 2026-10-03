<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Models\SubjectDistributionModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\MarkDistributionModel;
use App\Models\SchoolModel;

class SubjectDistributionController extends BaseController
{
    protected SubjectDistributionModel $SubjectDistributionModel;
    protected SubjectModel $SubjectModel;
    protected MarkDistributionModel $MarkDistributionModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->SubjectDistributionModel = new SubjectDistributionModel();
        $this->SubjectModel             = new SubjectModel();
        $this->MarkDistributionModel    = new MarkDistributionModel();
        $this->SchoolModel              = new SchoolModel();
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
        $this->SubjectDistributionModel->where('examination_subject_distributions.school_owner_uid', $this->getUserId());
    }

    protected function verifyOwnership(string $token): ?object
    {
        return $this->SubjectDistributionModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    protected function getSubjectDropdown(int $school_id): array
    {
        $records = $this->SubjectModel
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

    public function index()
    {
        $user_id   = $this->getUserId();
        $school_id = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;

        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('SubjectDistribution.page_title_list'),
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

        $this->SubjectDistributionModel
            ->select('examination_subject_distributions.*, schools.name AS school_name, examination_subjects.title AS subject_title, examination_mark_distributions.name AS distribution_name')
            ->join('schools', 'schools.id = examination_subject_distributions.school_id', 'left')
            ->join('examination_subjects', 'examination_subjects.id = examination_subject_distributions.subject_id', 'left')
            ->join('examination_mark_distributions', 'examination_mark_distributions.id = examination_subject_distributions.distribution_id', 'left');

        $this->applyOwnerFilter();

        if (!empty($schoolIds)) {
            $this->SubjectDistributionModel->whereIn('examination_subject_distributions.school_id', $schoolIds);
        } else {
            $this->SubjectDistributionModel->where('1 = 0');
        }

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->SubjectDistributionModel->where('examination_subject_distributions.school_id', $school_id);
        }

        if ($text) {
            $this->SubjectDistributionModel->groupStart();
            $this->SubjectDistributionModel->like('examination_subjects.title', $text);
            $this->SubjectDistributionModel->orLike('examination_mark_distributions.name', $text);
            $this->SubjectDistributionModel->groupEnd();
        }

        if (isset($status) && $status !== '') {
            $this->SubjectDistributionModel->where('examination_subject_distributions.status', $status);
        }

        $this->SubjectDistributionModel->orderBy('examination_subject_distributions.sort_order', 'ASC');
        $this->SubjectDistributionModel->orderBy('examination_subject_distributions.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->SubjectDistributionModel->paginate($perPage);
        $data['pager']           = $this->SubjectDistributionModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subject_distributions\list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('SubjectDistribution.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']              = [];
        $data['is_edit']                = false;
        $data['school_list']            = $this->getSchoolDropdown();
        $data['subject_list']           = [];
        $data['mark_distribution_list'] = [];

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subject_distributions\form', $data)
            . view('footer', $footer_data);
    }

    public function edit($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            return redirect()->to('examination/subject-distributions')
                ->with('error', 'Subject Distribution not found or access denied.');
        }

        $header_data['page_title'] = lang('SubjectDistribution.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $school_id = (int) $record->school_id;

        $data['post_data']              = (array) $record;
        $data['is_edit']                = true;
        $data['token']                  = $record->token;
        $data['school_list']            = $this->getSchoolDropdown();
        $data['subject_list']           = $this->getSubjectDropdown($school_id);
        $data['mark_distribution_list'] = $this->getMarkDistributionDropdown($school_id);

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subject_distributions\form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subject-distributions');
        if ($subscription_check) {
            return $subscription_check;
        }

        $post_data = $this->request->getPost();
        $token     = !empty($post_data['token']) ? $post_data['token'] : null;

        $existing = null;
        if ($token) {
            $existing = $this->verifyOwnership($token);
            if (!$existing) {
                return redirect()->to('examination/subject-distributions')
                    ->with('error', 'Subject Distribution not found or access denied.');
            }
        }

        $validationRule = [
            'subject_id'     => 'required|is_natural_no_zero',
            'distribution_id' => 'required|is_natural_no_zero',
            'school_id'      => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $token, $this->validator);
        }

        $user_id   = $this->getUserId();
        $school_id = (int) $post_data['school_id'];
        $now       = date('Y-m-d H:i:s');

        
        $saveData = [        
            'subject_id'      => (int) $post_data['subject_id'],
            'distribution_id' => (int) $post_data['distribution_id'],
            'full_mark'       => (float) ($post_data['full_mark'] ?? 0),
            'pass_mark'       => (float) ($post_data['pass_mark'] ?? 0),
            'weight_percent'  => (float) ($post_data['weight_percent'] ?? 0),
            'sort_order'      => (int) ($post_data['sort_order'] ?? 0),
            'school_id'       => $school_id,
            'status'          => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid'=> $user_id,
            'updated_at'      => $now,
            'updated_by'      => $user_id,
        ];

        $success_message = '';
        if ($token) {
            $this->SubjectDistributionModel->where('token', $token)->set($saveData)->update();
            $success_message = lang('SubjectDistribution.sys_updated');
        } else {
            $saveData['token']      = $this->generateToken();
            $saveData['created_at'] = $now;
            $saveData['created_by'] = $user_id;
            $this->SubjectDistributionModel->insert($saveData);
            $success_message = lang('SubjectDistribution.sys_created');
        }

        return redirect()->to('examination/subject-distributions')
            ->with('success', $success_message);
    }

    public function update($token)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $record = $this->verifyOwnership($token);
        if (!$record) {
            return redirect()->to('examination/subject-distributions')
                ->with('error', 'Subject Distribution not found or access denied.');
        }

        return $this->store();
    }

    public function trash($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subject-distributions');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subject-distributions');
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

        $updated = $this->SubjectDistributionModel->where('token', $token)->set([
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
            return redirect()->to('examination/subject-distributions');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subject-distributions');
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

        $updated = $this->SubjectDistributionModel->where('token', $token)->set([
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
            return redirect()->to('examination/subject-distributions');
        }

        // Check subscription
        $subscription_check = check_subscription('examination/subject-distributions');
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

        $deleted = $this->SubjectDistributionModel->where('token', $token)->delete();
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function getSubjectsBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subject-distributions');
        }

        $school_id = (int) $this->request->getPost('school_id');
        $subjects = $this->getSubjectDropdown($school_id);

        return $this->jsonResponse([
            'status' => true,
            'subjects' => $subjects,
        ]);
    }

    public function getMarkDistributionsBySchool()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('examination/subject-distributions');
        }

        $school_id = (int) $this->request->getPost('school_id');
        $markDistributions = $this->getMarkDistributionDropdown($school_id);

        return $this->jsonResponse([
            'status' => true,
            'mark_distributions' => $markDistributions,
        ]);
    }

    protected function renderForm(array $post_data, ?string $token, $validation = null)
    {
        $header_data['page_title'] = $token
            ? lang('SubjectDistribution.page_title_edit')
            : lang('SubjectDistribution.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $school_id = !empty($post_data['school_id']) ? (int) $post_data['school_id'] : 0;
        $token = $post_data['token'] ?? '';

        $form_data['post_data']              = $post_data;
        $form_data['is_edit']                = (bool) $token;
        $form_data['token']                  = $token;
        $form_data['school_list']            = $this->getSchoolDropdown();
        $form_data['subject_list']           = $school_id ? $this->getSubjectDropdown($school_id) : [];
        $form_data['mark_distribution_list'] = $school_id ? $this->getMarkDistributionDropdown($school_id) : [];

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\subject_distributions\form', $form_data)
            . view('footer', $footer_data);
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}
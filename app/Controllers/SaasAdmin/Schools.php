<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\PlanModel;
use App\Models\SchoolModel;
use App\Models\SubscriptionModel;

class Schools extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected PlanModel $PlanModel;
    protected SubscriptionModel $SubscriptionModel;

    public function __construct()
    {
        $this->SchoolModel = new SchoolModel();
        $this->PlanModel = new PlanModel();
        $this->SubscriptionModel = new SubscriptionModel();
    }

    public function index()
    {
        $header_data = [
            'page_title' => lang('School.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        $text = $this->request->getGet('text');
        $status = $this->request->getGet('status');
        $show = $this->request->getGet('show');

        $data = compact('text', 'status', 'show');

        $this->SchoolModel
            ->select([
                'schools.*'
            ]);
           

        if ($text) {
            $this->SchoolModel->groupStart()
                ->like('schools.name', $text)
                ->orLike('schools.email', $text)
                ->orLike('schools.phone', $text)
                ->groupEnd();
        }

        if (isset($status) && $status !== '') {
            $this->SchoolModel->where('schools.status', $status);
        }

        $this->SchoolModel
            ->groupBy('schools.id')
            ->orderBy('schools.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items'] = $this->SchoolModel->paginate($perPage);
        $data['pager'] = $this->SchoolModel->pager;
        $data['pagerTemplate'] = 'custom_pagination';

        return view('header', $header_data)
            . view('saas_admin/schools/list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        $header_data['page_title'] = lang('School.page_title_create');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['school_data'] = null;
        $data['post_data'] = [];
        $data['is_edit'] = false;

        return view('header', $header_data)
            . view('saas_admin/schools/form', $data)
            . view('footer', $footer_data);
    }

    public function edit($school_id)
    {
        $school = $this->SchoolModel->find($school_id);

        if (!$school) {
            return redirect()->to('saas-admin/schools')->with('error', 'School not found.');
        }

        $header_data['page_title'] = lang('School.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $post_data = (array) $school;
        
        $data['school_data'] = $school;
        $data['post_data'] = $post_data;
        $data['is_edit'] = true;

        return view('header', $header_data)
            . view('saas_admin/schools/form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        return $this->saveSchool();
    }

    public function update($school_id)
    {
        return $this->saveSchool((int) $school_id);
    }

    public function view($school_id)
    {
        $school = $this->SchoolModel
            ->select([
                'schools.*'
            ])
            
            ->where('schools.id', $school_id)
            ->first();

        if (!$school) {
            return redirect()->to('saas-admin/schools')->with('error', 'School not found.');
        }

        $header_data['page_title'] = $school->name;
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('saas_admin/schools/view', ['school' => $school])
            . view('footer', $footer_data);
    }

    public function delete($school_id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/schools');
        }

        $hardDelete = (int) $this->request->getPost('empty_trash') === 1;
        $response = ['status' => false, 'html' => ''];

        if (!$school_id) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        if ($hardDelete) {
            $this->deleteSchoolLogo((int) $school_id);
            $deleted = $this->SchoolModel->delete($school_id);
            $response['status'] = (bool) $deleted;
            $response['html'] = $deleted
                ? message_generator('success', lang('Common.data_empty_trashed'))
                : message_generator('error', lang('Common.data_error_empty_trashed'));

            return $this->jsonResponse($response);
        }

        $updated = $this->SchoolModel->update($school_id, [
            'status' => 2,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => session()->get('user_id'),
        ]);

        $response['status'] = (bool) $updated;
        $response['html'] = $updated
            ? message_generator('success', lang('Common.data_trashed'))
            : message_generator('error', lang('Common.data_error_trashed'));

        return $this->jsonResponse($response);
    }

    public function changeStatus($school_id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/schools');
        }

        $status = $this->request->getPost('status');
        $response = ['status' => false, 'html' => ''];

        if (!$school_id || !in_array((string) $status, ['0', '1', '2'], true)) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        $updated = $this->SchoolModel->update($school_id, [
            'status' => (int) $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => session()->get('user_id'),
        ]);

        $response['status'] = (bool) $updated;
        $response['html'] = $updated
            ? message_generator('success', 'School status updated successfully.')
            : message_generator('error', 'School status could not be updated.');

        return $this->jsonResponse($response);
    }

    protected function saveSchool(?int $school_id = null)
    {
        $post_data = $this->request->getPost();
        $school_id = $school_id ?: (!empty($post_data['school_id']) ? (int) $post_data['school_id'] : null);

        $post_data['slug'] = $this->normalizeSlug($post_data['slug'] ?? $post_data['name'] ?? '');

        $validationRule = [
            'name' => 'required|max_length[255]',
            'slug' => 'required|max_length[150]',
            'country' => 'required|max_length[50]',
            'timezone' => 'required|max_length[50]',
            'email' => 'permit_empty|max_length[255]|valid_email',
            'phone' => 'permit_empty|max_length[50]',
            'status' => 'required|in_list[0,1,2]',
        ];

        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            $photoMaxSize = (int) setting('application', 'photo_max_size', 2048);
            $allowedExtensions = setting('application', 'allowed_photo_extensions', 'jpg,jpeg,png,gif,webp');
            $validationRule['logo'] = 'uploaded[logo]|max_size[logo,' . $photoMaxSize . ']|ext_in[logo,' . $allowedExtensions . ']';
        }

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $school_id, $this->validator);
        }

        if ($this->slugExists($post_data['slug'], $school_id)) {
            return $this->renderForm($post_data, $school_id, null, 'School slug already exists.');
        }

        $now = date('Y-m-d H:i:s');
        $userId = session()->get('user_id');
        $schoolData = [
            'name' => $post_data['name'],
            'slug' => $post_data['slug'],
            'country' => $post_data['country'],
            'timezone' => $post_data['timezone'],
            'address' => $post_data['address'] ?? null,
            'email' => $post_data['email'] ?? null,
            'phone' => $post_data['phone'] ?? null,
            'custom_domain' => $post_data['custom_domain'] ?? null,
            'status' => (int) $post_data['status'],
            'updated_at' => $now,
            'updated_by' => $userId,
        ];

        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            if ($school_id) {
                $this->deleteSchoolLogo($school_id);
            }

            $logoName = $logo->getRandomName();
            $logo->move(WRITEPATH . 'uploads', $logoName);
            $schoolData['logo'] = $logoName;
        }

        if ($school_id) {
            $this->SchoolModel->update($school_id, $schoolData);
        } else {
            $schoolData['created_at'] = $now;
            $schoolData['created_by'] = $userId;
            $this->SchoolModel->insert($schoolData);
            $school_id = (int) $this->SchoolModel->insertID();
        }

        
        return redirect()->to('saas-admin/schools')->with('success', lang('School.sys_saved'));
    }

    protected function renderForm(array $post_data, ?int $school_id, $validation = null, string $error = '')
    {
        $header_data['page_title'] = $school_id ? lang('School.page_title_edit') : lang('School.page_title_create');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data'] = $post_data;
        $form_data['school_data'] = $school_id ? $this->SchoolModel->find($school_id) : null;
        $form_data['is_edit'] = (bool) $school_id;

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        if ($error !== '') {
            session()->setFlashdata('error', $error);
        }

        return view('header', $header_data)
            . view('saas_admin/schools/form', $form_data)
            . view('footer', $footer_data);
    }

   

    protected function slugExists(string $slug, ?int $school_id): bool
    {
        $builder = $this->SchoolModel->where('slug', $slug);

        if ($school_id) {
            $builder->where('id !=', $school_id);
        }

        return (bool) $builder->first();
    }

    protected function normalizeSlug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim((string) $slug, '-');

        return $slug ?: uniqid('school-', false);
    }

    protected function deleteSchoolLogo(int $school_id): void
    {
        $school = $this->SchoolModel->find($school_id);
        if (!$school || empty($school->logo)) {
            return;
        }

        $path = realpath(WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $school->logo);
        $uploadsPath = realpath(WRITEPATH . 'uploads');

        if ($path && $uploadsPath && str_starts_with($path, $uploadsPath) && is_file($path)) {
            unlink($path);
        }
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}

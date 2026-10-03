<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Models\SchoolUserModel;
use App\Models\ModuleModel;
use App\Modules\examination\Models\GradeSystemModel;

class Schools extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected SchoolUserModel $SchoolUserModel;
    protected GradeSystemModel $GradeSystemModel;
    protected ModuleModel $ModuleModel;

    public function __construct()
    {
        $this->SchoolModel = new SchoolModel();
        $this->SchoolUserModel = new SchoolUserModel();
        $this->GradeSystemModel = new GradeSystemModel();
        $this->ModuleModel = new ModuleModel();
    }

    /**
     * Retrieve all published (status = 1) modules to display in the school form.
     *
     * @return object[]
     */
    protected function getPublishedModules(): array
    {
        return $this->ModuleModel
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    public function index()
    {
        // Get user id
        $user_id = session('user_id') ? (int) session('user_id') : null;

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
		$data['plan_status'] = '';

        // Get ccheck student limit and redirect if limit is reached
        $plan_status = check_subscription_plan('branch_limit');
        if (!$plan_status['status']) {
            $data['plan_status'] = message_generator('error', $plan_status['message']);
        }


        $this->SchoolModel
            ->select([
                'schools.*'
            ])
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('school_user_relation.user_id', $user_id)
            ;
            

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
            . view('school_owner/schools/list', $data)
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
        $data['grade_systems'] = $this->GradeSystemModel->findAll();
        $data['modules'] = $this->getPublishedModules();

        return view('header', $header_data)
            . view('school_owner/schools/form', $data)
            . view('footer', $footer_data);
    }

    public function edit($school_id)
    {
        $school = $this->SchoolModel->find($school_id);

        if (!$school) {
            return redirect()->to('school-owner/schools')->with('error', 'School not found.');
        }

        $header_data['page_title'] = lang('School.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $post_data = (array) $school;

        // Decode params JSON and merge into post_data for form checkboxes & modules
        if (!empty($school->params)) {
            $params = json_decode($school->params, true);
            if (is_array($params)) {
                foreach ($params as $key => $val) {
                    $post_data[$key] = $val;
                }
            }
        }

        // Core Bangladesh academic fields are mandatory and cannot be disabled.
        $post_data['academic_class_roll_enabled'] = 1;
        $post_data['academic_category_enabled'] = 1;
        $post_data['academic_section_enabled'] = 1;

        $data['school_data'] = $school;
        $data['post_data'] = $post_data;
        $data['is_edit'] = true;
        $data['grade_systems'] = $this->GradeSystemModel->findAll();
        $data['modules'] = $this->getPublishedModules();

        return view('header', $header_data)
            . view('school_owner/schools/form', $data)
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
        $user_id = session('user_id') ? (int) session('user_id') : null;

        // First verify ownership via school_user_relation
        $owned = $this->SchoolModel
            ->select('schools.id')
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('schools.id', $school_id)
            ->where('school_user_relation.user_id', $user_id)
            ->groupBy('schools.id')
            ->first();

        if (!$owned) {
            return redirect()->to('school-owner/schools')->with('error', 'School not found.');
        }

        // Fetch school with subscription info
        $school = $this->SchoolModel
            ->select([
                'schools.*',
                'subscription_plans.name AS plan_name',
                'subscriptions.status AS subscription_status',
                'subscriptions.start_date',
                'subscriptions.end_date',
                'subscriptions.billing_cycle',
                'subscriptions.amount',
                'subscriptions.currency',
            ])
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->join('subscriptions', 'subscriptions.user_id = school_user_relation.user_id AND subscriptions.status IN (1, 2)', 'left')
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('schools.id', $school_id)
            ->groupBy('schools.id')
            ->first();

        if (!$school) {
            return redirect()->to('school-owner/schools')->with('error', 'School not found.');
        }

        $header_data['page_title'] = $school->name;
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('school_owner/schools/view', ['school' => $school])
            . view('footer', $footer_data);
    }

    public function delete($school_id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/schools');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/schools');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
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
            return redirect()->to('school-owner/schools');
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

    protected function renderForm(array $post_data, ?int $school_id, $validation = null, string $error = '')
    {
        $header_data['page_title'] = $school_id ? lang('School.page_title_edit') : lang('School.page_title_create');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data'] = $post_data;
        $form_data['school_data'] = $school_id ? $this->SchoolModel->find($school_id) : null;
        $form_data['is_edit'] = (bool) $school_id;
        $form_data['grade_systems'] = $this->GradeSystemModel->findAll();
        $form_data['modules'] = $this->getPublishedModules();

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        if ($error !== '') {
            session()->setFlashdata('error', $error);
        }

        return view('header', $header_data)
            . view('school_owner/schools/form', $form_data)
            . view('footer', $footer_data);
    }

    protected function saveSchoolUserRelation(int $school_id, int $user_id)
    {
        $this->SchoolUserModel->insert([
            'school_id' => $school_id,
            'user_id' => $user_id,
        ]);
    }

    protected function getCurrentSubscription(int $school_id)
    {
        return $this->SubscriptionModel
            ->where('school_id', $school_id)
            ->whereIn('status', [1, 2])
            ->orderBy('id', 'DESC')
            ->first();
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

    /**
     * List of all result sheet configuration fields (checkboxes/switches) that store 0 or 1.
     */
    private function getResultSheetSwitchFields(): array
    {
        return [
            'enable_rank_by_total_mark',
            'enable_rank_by_percentage',
            'enable_rank_by_grade_point',
            'show_principal_signature',
            'show_highest_mark'
        ];
    }

    /**
     * List of all result sheet configuration text/select fields.
     */
    private function getResultSheetTextFieldss(): array
    {
        return [
            'grading_system',
            'rank_calc_first',
            'rank_calc_second',
            'rank_calc_third'
        ];
    }

    /**
     * Delete a saved principal signature image file.
     */
    protected function deletePrincipalSignature(string $filename): void
    {
        if (empty($filename)) {
            return;
        }

        $path = realpath(WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $filename);
        $uploadsPath = realpath(WRITEPATH . 'uploads');

        if ($path && $uploadsPath && str_starts_with($path, $uploadsPath) && is_file($path)) {
            unlink($path);
        }
    }

    protected function saveSchool(?int $school_id = null)
    {
        // Check subscription
        $subscription_check = check_subscription('school-owner/schools');
        if ($subscription_check) {
            return $subscription_check;
        }

        // Check subscription plan
        $plan_status = check_subscription_plan('branch_limit');
        if (!$plan_status['status']) {
            return redirect()->to('school-owner/schools')->with('error', $plan_status['message']);
        }

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

        // Build params JSON for settings, modules & result sheet configuration
        $params = [];

        // --- Academic Settings Fields ---
        $settingsFields = [
            'student_account_enabled',
            'parent_account_enabled',
            'academic_class_roll_enabled',
            'academic_category_enabled',
            'academic_section_enabled',
            'academic_shift_enabled',
            'academic_department_enabled',
        ];
        foreach ($settingsFields as $field) {
            $params[$field] = !empty($post_data[$field]) ? 1 : 0;
        }
        $params['academic_class_roll_enabled'] = 1;
        $params['academic_category_enabled'] = 1;
        $params['academic_section_enabled'] = 1;

        // --- ID Format Settings Fields ---
        $idFormatFields = [
            'use_id_prefix',
            'use_qr_prefix',
            'use_reg_prefix',
            'use_rfid_prefix',
        ];
        foreach ($idFormatFields as $field) {
            $params[$field] = !empty($post_data[$field]) ? 1 : 0;
        }

        $params['id_prefix'] = $post_data['id_prefix'] ?? '';
        $params['id_digit']  = $post_data['id_digit'] ?? '';
        $params['qr_prefix'] = $post_data['qr_prefix'] ?? '';
        $params['qr_digit']  = $post_data['qr_digit'] ?? '';
        $params['reg_prefix'] = $post_data['reg_prefix'] ?? '';
        $params['reg_digit']  = $post_data['reg_digit'] ?? '';
        $params['rfid_prefix'] = $post_data['rfid_prefix'] ?? '';
        $params['rfid_digit']  = $post_data['rfid_digit'] ?? '';

        // --- Enable/Disable Fields ---
        $enableFields = [
            'enable_student_id',
            'enable_qr_code',
            'enable_registration_no',
            'enable_rfid',
        ];
        foreach ($enableFields as $field) {
            $params[$field] = !empty($post_data[$field]) ? 1 : 0;
        }

        // --- Modules ---
        $params['modules'] = !empty($post_data['modules']) ? (array) $post_data['modules'] : [];

        // --- Result Sheet Configuration (switch/checkbox fields) ---
        foreach ($this->getResultSheetSwitchFields() as $field) {
            $params[$field] = !empty($post_data[$field]) ? 1 : 0;
        }

        // --- Result Sheet Configuration (text/select fields) ---
        foreach ($this->getResultSheetTextFieldss() as $field) {
            $params[$field] = $post_data[$field] ?? '';
        }

        // --- Principal Signature Image Upload ---
        $principalSignature = $this->request->getFile('principal_signature');
        if ($principalSignature && $principalSignature->isValid() && !$principalSignature->hasMoved()) {
            $allowedExtensions = setting('application', 'allowed_photo_extensions', 'jpg,jpeg,png,gif,webp');
            $photoMaxSize = (int) setting('application', 'photo_max_size', 2048);

            if ($principalSignature->getSizeByUnit('kb') <= $photoMaxSize && $principalSignature->getExtension() && in_array($principalSignature->getExtension(), explode(',', $allowedExtensions))) {
                // Delete old signature if updating
                if ($school_id) {
                    $existingParams = $this->SchoolModel->find($school_id);
                    if ($existingParams && !empty($existingParams->params)) {
                        $existingParamsArray = json_decode($existingParams->params, true);
                        if (!empty($existingParamsArray['principal_signature_img'])) {
                            $this->deletePrincipalSignature($existingParamsArray['principal_signature_img']);
                        }
                    }
                }

                $sigName = $principalSignature->getRandomName();
                $principalSignature->move(WRITEPATH . 'uploads', $sigName);
                $params['principal_signature_img'] = $sigName;
            }
        } else {
            // If no new file uploaded, keep existing signature (only in edit mode)
            if ($school_id) {
                $existingParams = $this->SchoolModel->find($school_id);
                if ($existingParams && !empty($existingParams->params)) {
                    $existingParamsArray = json_decode($existingParams->params, true);
                    if (!empty($existingParamsArray['principal_signature_img'])) {
                        $params['principal_signature_img'] = $existingParamsArray['principal_signature_img'];
                    }
                }
            }
        }

        $schoolData = [
            'name' => $post_data['name'],
            'slug' => $post_data['slug'],
            'country' => $post_data['country'],
            'timezone' => $post_data['timezone'],
            'address' => $post_data['address'] ?? null,
            'email' => $post_data['email'] ?? null,
            'phone_code' => $post_data['phone_code'] ?? null,
            'phone' => $post_data['phone'] ?? null,
            'custom_domain' => $post_data['custom_domain'] ?? null,
            'status' => (int) $post_data['status'],
            'params' => json_encode($params),
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

            $this->saveSchoolUserRelation($school_id, $userId);
        }

        return redirect()->to('school-owner/schools')->with('success', lang('School.sys_saved'));
    }
}

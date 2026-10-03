<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\AcademicsYearModel;
use App\Models\SchoolModel;

class AcademicsYears extends BaseController
{
    protected AcademicsYearModel $YearModel;
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->YearModel   = new AcademicsYearModel();
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
        $this->YearModel->where('school_owner_uid', $this->getUserId());
    }

    /**
     * Verify that a year record belongs to the current user.
     */
    protected function verifyOwnership(int $year_id): ?object
    {
        $year = $this->YearModel
            ->where('id', $year_id)
            ->where('school_owner_uid', $this->getUserId())
            ->first();

        return $year;
    }

    public function years()
    {
        $user_id    = $this->getUserId();
        $school_id  = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;

        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => lang('AcademicYear.page_title_list'),
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
        $this->YearModel
            ->select('academic_years.*, schools.name AS school_name')
            ->join('schools', 'schools.id = academic_years.school_id', 'left');

        // Always filter by school_owner_uid
        $this->applyOwnerFilter();

        if (!empty($schoolIds)) {
            $this->YearModel->whereIn('academic_years.school_id', $schoolIds);
        } else {
            // No schools assigned → no results
            $this->YearModel->where('1 = 0');
        }

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->YearModel->where('academic_years.school_id', $school_id);
        }

        if ($text) {
            $this->YearModel->like('academic_years.title', $text);
        }

        if (isset($status) && $status !== '') {
            $this->YearModel->where('academic_years.status', $status);
        }

        $this->YearModel->orderBy('academic_years.id', 'DESC');

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show ? (int) $show : $defaultPerPage;

        $data['items']           = $this->YearModel->paginate($perPage);
        $data['pager']           = $this->YearModel->pager;
        $data['pagerTemplate']   = 'custom_pagination';
        $data['school_list']     = $this->getSchoolDropdown();
        $data['selected_school'] = $school_id;

        return view('header', $header_data)
            . view('school_owner/academics/years/list', $data)
            . view('footer', $footer_data);
    }

    public function createYear()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = lang('AcademicYear.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']      = [];
        $data['is_edit']        = false;
        $data['school_list']    = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('school_owner/academics/years/form', $data)
            . view('footer', $footer_data);
    }

    public function editYear($year_id)
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $year = $this->verifyOwnership((int) $year_id);

        if (!$year) {
            return redirect()->to('school-owner/academics/years')
                ->with('error', 'Academic Year not found or access denied.');
        }

        $header_data['page_title'] = lang('AcademicYear.page_title_edit');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['post_data']      = (array) $year;
        $data['is_edit']        = true;
        $data['school_list']    = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('school_owner/academics/years/form', $data)
            . view('footer', $footer_data);
    }

    public function storeYear()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $post_data = $this->request->getPost();
        $year_id   = !empty($post_data['id']) ? (int) $post_data['id'] : null;

        $validationRule = [
            'title'     => 'required|max_length[255]',
            'school_id' => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($validationRule)) {
            return $this->renderForm($post_data, $year_id, $this->validator);
        }

        // If updating, verify ownership
        if ($year_id) {
            $existing = $this->verifyOwnership($year_id);
            if (!$existing) {
                return redirect()->to('school-owner/academics/years')
                    ->with('error', 'Academic Year not found or access denied.');
            }
        }

        $user_id   = $this->getUserId();
        $school_id = (int) $post_data['school_id'];
        $now       = date('Y-m-d H:i:s');

        $saveData = [
            'title'           => $post_data['title'],
            'school_id'       => $school_id,
            'status'          => !empty($post_data['status']) ? (int) $post_data['status'] : 1,
            'school_owner_uid'=> $user_id,
            'updated_at'      => $now,
            'updated_by'      => $user_id,
        ];

        if ($year_id) {
            $this->YearModel->update($year_id, $saveData);
        } else {
            $saveData['created_at'] = $now;
            $saveData['created_by'] = $user_id;
            $this->YearModel->insert($saveData);
        }

        return redirect()->to('school-owner/academics/years')
            ->with('success', lang('AcademicYear.sys_saved'));
    }

    protected function renderForm(array $post_data, ?int $year_id, $validation = null)
    {
        $header_data['page_title'] = $year_id
            ? lang('AcademicYear.page_title_edit')
            : lang('AcademicYear.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data']   = $post_data;
        $form_data['is_edit']     = (bool) $year_id;
        $form_data['school_list'] = $this->getSchoolDropdown();

        if ($validation) {
            $form_data['validation'] = $validation;
        }

        return view('header', $header_data)
            . view('school_owner/academics/years/form', $form_data)
            . view('footer', $footer_data);
    }

    public function trashYear($year_id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/academics/years');
        }

        $response = ['status' => false, 'html' => ''];

        if (!$year_id) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        // Verify ownership before trashing
        $year = $this->verifyOwnership((int) $year_id);
        if (!$year) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->YearModel->update($year_id, [
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

    public function empty_trashYear()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/academics/years');
        }

        $year_id = (int) $this->request->getPost('id');
        $response = ['status' => false, 'html' => ''];

        if (!$year_id) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        // Verify ownership before permanent delete
        $year = $this->verifyOwnership($year_id);
        if (!$year) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $deleted = $this->YearModel->delete($year_id);
        $response['status'] = (bool) $deleted;
        $response['html']   = $deleted
            ? message_generator('success', lang('Common.data_empty_trashed'))
            : message_generator('error', lang('Common.data_error_empty_trashed'));

        return $this->jsonResponse($response);
    }

    public function restoreYear($year_id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/academics/years');
        }

        $response = ['status' => false, 'html' => ''];

        if (!$year_id) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        // Verify ownership before restoring
        $year = $this->verifyOwnership((int) $year_id);
        if (!$year) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->YearModel->update($year_id, [
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
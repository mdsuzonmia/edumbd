<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\CustomField\CustomFieldModel;
use App\Models\CustomField\CustomFieldGroupModel;
use App\Models\CustomField\CustomFieldOptionModel;
use App\Models\CustomField\CustomFieldValueModel;
use App\Models\CustomField\CustomFieldEntityModel;
use App\Models\CustomField\CustomFieldTypeModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;

class CustomFields extends BaseController
{
    protected CustomFieldModel $FieldModel;
    protected CustomFieldGroupModel $GroupModel;
    protected CustomFieldOptionModel $OptionModel;
    protected CustomFieldValueModel $ValueModel;
    protected CustomFieldEntityModel $EntityModel;
    protected CustomFieldTypeModel $TypeModel;
    protected StudentModel $StudentModel;

    public function __construct()
    {
        $this->FieldModel  = new CustomFieldModel();
        $this->GroupModel  = new CustomFieldGroupModel();
        $this->OptionModel = new CustomFieldOptionModel();
        $this->ValueModel  = new CustomFieldValueModel();
        $this->EntityModel = new CustomFieldEntityModel();
        $this->TypeModel   = new CustomFieldTypeModel();
        $this->StudentModel = new StudentModel();
    }

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

        return (new SchoolModel())
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
     * Verify that a field record belongs to the current user by token.
     */
    protected function verifyOwnership(string $token): ?object
    {
        return $this->FieldModel
            ->where('token', $token)
            ->where('school_owner_uid', $this->getUserId())
            ->first();
    }

    // ===================== FIELDS =====================

    public function index()
    {
        $schoolId = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);

        $header_data = [
            'page_title' => lang('System.page_title_custom_fields') ?: 'Custom Fields',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['items'] = $this->FieldModel->getFieldsList($schoolId, $schoolIds);
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();
        $data['school_list'] = $this->getSchoolDropdown();
        $data['selected_school'] = $schoolId;

        return view('header', $header_data)
            . view('school_owner/custom_fields/list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        $header_data['page_title'] = lang('System.page_title_cf_create') ?: 'Create Custom Field';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['field'] = null;
        $data['is_edit'] = false;
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();
        $data['types'] = $this->TypeModel->findAll();
        $data['groups'] = [];
        $data['school_list'] = $this->getSchoolDropdown();
        $data['selected_school'] = 0;
        $data['token'] = '';

        return view('header', $header_data)
            . view('school_owner/custom_fields/form', $data)
            . view('footer', $footer_data);
    }

    public function edit($token)
    {
        $record = $this->verifyOwnership($token);
        if (!$record) {
            return redirect()->to('school-owner/custom-fields')->with('error', 'Field not found or access denied.');
        }

        $header_data['page_title'] = lang('System.page_title_cf_edit') ?: 'Edit Custom Field';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['field'] = $record;
        $data['is_edit'] = true;
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();
        $data['types'] = $this->TypeModel->findAll();
        $data['groups'] = $this->GroupModel
            ->where('entity_id', $record->entity_id)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->findAll();
        $data['school_list'] = $this->getSchoolDropdown();
        $data['selected_school'] = (int) $record->school_id;
        $data['token'] = $record->token;

        $data['options'] = $this->OptionModel
            ->where('field_id', $record->id)
            ->orderBy('sort_order')
            ->findAll();

        return view('header', $header_data)
            . view('school_owner/custom_fields/form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        // Check subscription
        $subscription_check = check_subscription('school-owner/custom-fields');
        if ($subscription_check) {
            return $subscription_check;
        }

        $post = $this->request->getPost();
        $token = !empty($post['token']) ? $post['token'] : null;
        $schoolId = (int) ($post['school_id'] ?? 0);

        $validationRule = [
            'school_id'  => 'required|is_natural_no_zero',
            'label'      => 'required|max_length[255]',
            'field_key'  => 'required|max_length[150]',
            'entity_id'  => 'required|integer',
            'field_type_id' => 'required|integer',
        ];

        if (!$this->validate($validationRule)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        // If updating, verify ownership
        if ($token) {
            $existing = $this->verifyOwnership($token);
            if (!$existing) {
                return redirect()->to('school-owner/custom-fields')
                    ->with('error', 'Custom Field not found or access denied.');
            }
        }

        if ($this->FieldModel->fieldKeyExists($schoolId, $post['field_key'], $token)) {
            return redirect()->back()->withInput()->with('error', 'Field key already exists.');
        }

        $user_id = $this->getUserId();
        $now = date('Y-m-d H:i:s');

        $data = [
            'token'                => $token ?? bin2hex(random_bytes(16)),
            'school_id'     => $schoolId,
            'entity_id'     => (int) $post['entity_id'],
            'group_id'      => !empty($post['group_id']) ? (int) $post['group_id'] : null,
            'field_type_id' => (int) $post['field_type_id'],
            'label'         => $post['label'],
            'field_key'     => $post['field_key'],
            'placeholder'   => $post['placeholder'] ?? null,
            'help_text'     => $post['help_text'] ?? null,
            'default_value' => $post['default_value'] ?? null,
            'validation_rules' => $post['validation_rules'] ?? null,
            'is_required'   => (int) ($post['is_required'] ?? 0),
            'is_unique'     => (int) ($post['is_unique'] ?? 0),
            'is_searchable' => (int) ($post['is_searchable'] ?? 0),
            'show_on_registration' => (int) ($post['show_on_registration'] ?? 0),
            'show_on_admission'    => (int) ($post['show_on_admission'] ?? 0),
            'allow_bulk_import'    => (int) ($post['allow_bulk_import'] ?? 1),
            'allow_bulk_export'    => (int) ($post['allow_bulk_export'] ?? 1),
            'show_on_profile'      => (int) ($post['show_on_profile'] ?? 1),
            'show_on_list'         => (int) ($post['show_on_list'] ?? 0),
            'show_on_idcard'       => (int) ($post['show_on_idcard'] ?? 0),
            'show_on_resultcard'   => (int) ($post['show_on_resultcard'] ?? 0),
            'sort_order'    => (int) ($post['sort_order'] ?? 0),
            'status'        => (int) ($post['status'] ?? 1),
            'school_owner_uid' => $user_id,
            'updated_at'    => $now,
            'updated_by'    => $user_id,
        ];

        if ($token) {
            $this->FieldModel->where('token', $token)->set($data)->update();
            $fieldId = $this->FieldModel->where('token', $token)->first()->id;
        } else {
            $data['created_at'] = $now;
            $data['created_by'] = $user_id;
            $fieldId = $this->FieldModel->insert($data);
        }

        $this->saveOptions($fieldId, $post['options'] ?? []);

        return redirect()->to('school-owner/custom-fields')->with('success', 'Custom field saved successfully.');
    }

    public function update($token)
    {
        $post = $this->request->getPost();
        $post['token'] = $token;
        return $this->store();
    }

    public function delete($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/custom-fields');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $response = ['status' => false, 'html' => ''];

        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        // Verify ownership before deleting
        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $deleted = $this->FieldModel->where('token', $token)->delete();
        $response['status'] = (bool) $deleted;
        $response['html'] = $deleted
            ? message_generator('success', 'Field deleted successfully.')
            : message_generator('error', 'Field could not be deleted.');

        return $this->jsonResponse($response);
    }

    public function changeStatus($token)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields');
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/custom-fields');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $status = (int) $this->request->getPost('status');
        $response = ['status' => false, 'html' => ''];

        if (!$token) {
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing'));
            return $this->jsonResponse($response);
        }

        // Verify ownership before updating
        $record = $this->verifyOwnership($token);
        if (!$record) {
            $response['html'] = message_generator('error', 'Access denied.');
            return $this->jsonResponse($response);
        }

        $updated = $this->FieldModel->where('token', $token)->set([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->getUserId(),
        ])->update();

        $response['status'] = (bool) $updated;
        $response['html'] = $updated
            ? message_generator('success', 'Field status updated.')
            : message_generator('error', 'Field status could not be updated.');

        return $this->jsonResponse($response);
    }

    // ===================== GROUPS =====================

    public function groups()
    {
        $schoolId = $this->request->getGet('school_id') ? (int) $this->request->getGet('school_id') : 0;
        $userSchools = $this->getUserSchools();
        $schoolIds = array_map(function ($s) { return $s->id; }, $userSchools);

        $header_data = [
            'page_title' => lang('System.page_title_cf_groups') ?: 'Field Groups',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['items'] = $this->GroupModel
            ->where('school_id', $schoolId)
            ->orderBy('sort_order')
            ->findAll();

        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();

        return view('header', $header_data)
            . view('school_owner/custom_fields/groups_list', $data)
            . view('footer', $footer_data);
    }

    public function createGroup()
    {
        $header_data['page_title'] = lang('System.page_title_cf_group_create') ?: 'Create Group';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['group'] = null;
        $data['is_edit'] = false;
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();

        return view('header', $header_data)
            . view('school_owner/custom_fields/groups_form', $data)
            . view('footer', $footer_data);
    }

    public function editGroup($id)
    {
        $group = $this->GroupModel->find($id);
        if (!$group) {
            return redirect()->to('school-owner/custom-fields/groups')->with('error', 'Group not found.');
        }

        $header_data['page_title'] = lang('System.page_title_cf_group_edit') ?: 'Edit Group';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['group'] = $group;
        $data['is_edit'] = true;
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();

        return view('header', $header_data)
            . view('school_owner/custom_fields/groups_form', $data)
            . view('footer', $footer_data);
    }

    public function storeGroup()
    {
        $post = $this->request->getPost();
        $schoolId = (int) ($post['school_id'] ?? 0);

        $validationRule = [
            'title'     => 'required|max_length[255]',
            'entity_id' => 'required|integer',
        ];

        if (!$this->validate($validationRule)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $data = [
            'school_id'  => $schoolId,
            'entity_id'  => (int) $post['entity_id'],
            'title'      => $post['title'],
            'description' => $post['description'] ?? null,
            'sort_order' => (int) ($post['sort_order'] ?? 0),
            'status'     => (int) ($post['status'] ?? 1),
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => session()->get('user_id'),
        ];

        $id = $post['id'] ?? null;
        if ($id) {
            $this->GroupModel->update($id, $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = session()->get('user_id');
            $this->GroupModel->insert($data);
        }

        return redirect()->to('school-owner/custom-fields/groups')->with('success', 'Group saved successfully.');
    }

    public function updateGroup($id)
    {
        $post = $this->request->getPost();
        $post['id'] = $id;
        return $this->storeGroup();
    }

    public function deleteGroup($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields/groups');
        }

        $response = ['status' => false, 'html' => ''];
        $deleted = $this->GroupModel->delete($id);
        $response['status'] = (bool) $deleted;
        $response['html'] = $deleted
            ? message_generator('success', 'Group deleted successfully.')
            : message_generator('error', 'Group could not be deleted.');

        return $this->jsonResponse($response);
    }

    // ===================== FIELDS BY ENTITY (AJAX) =====================

    public function getFieldsByEntity()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields');
        }

        $schoolId = (int) $this->request->getPost('school_id');
        $entitySlug = $this->request->getPost('entity_slug');
        $recordIdOrToken = $this->request->getPost('record_id');

        $response = ['status' => false, 'html' => '', 'debug' => []];

        if (!$schoolId || !$entitySlug) {
            $response['debug'] = ['error' => 'Missing school_id or entity_slug'];
            return $this->jsonResponse($response);
        }

        // Get entity ID from slug
        $entity = $this->EntityModel->where('slug', $entitySlug)->first();
        if (!$entity) {
            $response['debug'] = ['error' => 'Entity not found for slug: ' . $entitySlug];
            return $this->jsonResponse($response);
        }

        $entityId = (int) $entity->id;

        // Get fields for this school + entity
        $fields = $this->FieldModel->getFields($schoolId, $entityId, true);

        if (empty($fields)) {
            $response['status'] = true;
            $response['html'] = '<p class="text-muted">No custom fields found for this school.</p>';
            return $this->jsonResponse($response);
        }

        // Convert token to ID if needed
        $actualRecordId = 0;
        if ($recordIdOrToken) {
            // Check if it's a token (starts with STU-, QR-, REG-, or is a hex string of 32+ chars)
            $isToken = false;
            if (is_string($recordIdOrToken)) {
                if (str_starts_with($recordIdOrToken, 'STU-') || str_starts_with($recordIdOrToken, 'QR-') || str_starts_with($recordIdOrToken, 'REG-')) {
                    $isToken = true;
                } elseif (preg_match('/^[a-f0-9]{32,}$/i', $recordIdOrToken)) {
                    $isToken = true;
                }
            }
            
            if ($isToken) {
                $student = $this->StudentModel->where('token', $recordIdOrToken)->first();
                if ($student) {
                    $actualRecordId = (int) $student->id;
                }
            } else {
                $actualRecordId = (int) $recordIdOrToken;
            }
        }

        // Render using helper
        helper('customfield');
        
        $response['status'] = true;
        $response['html'] = render_custom_fields_section($fields, $actualRecordId, $schoolId, $entityId);

        return $this->jsonResponse($response);
    }

    // ===================== GROUPS =====================

    public function getGroupsByEntity()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields');
        }

        $entityId = (int) $this->request->getPost('entity_id');
        $response = ['status' => false, 'groups' => []];

        if ($entityId) {
            $groups = $this->GroupModel
                ->where('entity_id', $entityId)
                ->where('status', 1)
                ->orderBy('sort_order')
                ->findAll();

            $groupList = [];
            foreach ($groups as $g) {
                $groupList[] = ['id' => $g->id, 'title' => $g->title];
            }

            $response['status'] = true;
            $response['groups'] = $groupList;
        }

        return $this->jsonResponse($response);
    }

    // ===================== OPTIONS =====================

    public function storeOption()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields');
        }

        $post = $this->request->getPost();
        $response = ['status' => false, 'html' => ''];

        $data = [
            'field_id'     => (int) $post['field_id'],
            'option_label' => $post['option_label'],
            'option_value' => $post['option_value'],
            'sort_order'   => (int) ($post['sort_order'] ?? 0),
            'status'       => 1,
        ];

        $inserted = $this->OptionModel->insert($data);
        $response['status'] = (bool) $inserted;

        return $this->jsonResponse($response);
    }

    public function deleteOption($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields');
        }

        $response = ['status' => false, 'html' => ''];
        $deleted = $this->OptionModel->delete($id);
        $response['status'] = (bool) $deleted;

        return $this->jsonResponse($response);
    }

    // ===================== VALUES =====================

    public function saveFieldValue()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields');
        }

        $post = $this->request->getPost();
        $response = ['status' => false, 'html' => ''];
        $schoolId = (int) ($post['school_id'] ?? 0);

        if (!$schoolId) {
            $response['html'] = 'School ID is required.';
            return $this->jsonResponse($response);
        }

        $saved = $this->ValueModel->saveFieldValue(
            $schoolId,
            (int) $post['field_id'],
            (int) $post['entity_id'],
            (int) $post['record_id'],
            $post['value'] ?? null
        );

        $response['status'] = (bool) $saved;

        return $this->jsonResponse($response);
    }

    public function getRecordValues()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('school-owner/custom-fields');
        }

        $post = $this->request->getPost();
        $response = ['status' => true, 'data' => []];
        $schoolId = (int) ($post['school_id'] ?? 0);

        if (!$schoolId) {
            return $this->jsonResponse($response);
        }

        $values = $this->ValueModel->getRecordValues(
            $schoolId,
            (int) $post['entity_id'],
            (int) $post['record_id']
        );

        $response['data'] = $values;

        return $this->jsonResponse($response);
    }

    // ===================== HELPERS =====================

    protected function saveOptions($fieldId, array $options): void
    {
        $this->OptionModel->where('field_id', $fieldId)->delete();

        foreach ($options as $order => $opt) {
            if (empty($opt['label']) || empty($opt['value'])) continue;

            $this->OptionModel->insert([
                'field_id'     => $fieldId,
                'option_label' => $opt['label'],
                'option_value' => $opt['value'],
                'sort_order'   => (int) ($order + 1),
                'status'       => 1,
            ]);
        }
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}
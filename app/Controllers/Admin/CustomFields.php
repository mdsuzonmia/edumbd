<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CustomField\CustomFieldModel;
use App\Models\CustomField\CustomFieldGroupModel;
use App\Models\CustomField\CustomFieldOptionModel;
use App\Models\CustomField\CustomFieldValueModel;
use App\Models\CustomField\CustomFieldEntityModel;
use App\Models\CustomField\CustomFieldTypeModel;

class CustomFields extends BaseController
{
    protected CustomFieldModel $FieldModel;
    protected CustomFieldGroupModel $GroupModel;
    protected CustomFieldOptionModel $OptionModel;
    protected CustomFieldValueModel $ValueModel;
    protected CustomFieldEntityModel $EntityModel;
    protected CustomFieldTypeModel $TypeModel;

    public function __construct()
    {
        $this->FieldModel  = new CustomFieldModel();
        $this->GroupModel  = new CustomFieldGroupModel();
        $this->OptionModel = new CustomFieldOptionModel();
        $this->ValueModel  = new CustomFieldValueModel();
        $this->EntityModel = new CustomFieldEntityModel();
        $this->TypeModel   = new CustomFieldTypeModel();
    }

    protected function getSchoolId(): int
    {
        return (int) (session()->get('school_id') ?? 0);
    }

    // ===================== FIELDS =====================

    public function index()
    {
        $schoolId = $this->getSchoolId();

        $header_data = [
            'page_title' => lang('System.page_title_custom_fields') ?: 'Custom Fields',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['items'] = $this->FieldModel->getFields($schoolId, 0, false);
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();

        return view('header', $header_data)
            . view('school_admin/custom_fields/list', $data)
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
        $data['groups'] = $this->GroupModel
            ->where('school_id', $this->getSchoolId())
            ->where('status', 1)
            ->findAll();

        return view('header', $header_data)
            . view('school_admin/custom_fields/form', $data)
            . view('footer', $footer_data);
    }

    public function edit($id)
    {
        $field = $this->FieldModel->getField((int) $id);
        if (!$field) {
            return redirect()->to('admin/custom-fields')->with('error', 'Field not found.');
        }

        $header_data['page_title'] = lang('System.page_title_cf_edit') ?: 'Edit Custom Field';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['field'] = $field;
        $data['is_edit'] = true;
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();
        $data['types'] = $this->TypeModel->findAll();
        $data['groups'] = $this->GroupModel
            ->where('school_id', $this->getSchoolId())
            ->where('status', 1)
            ->findAll();

        $data['options'] = $this->OptionModel
            ->where('field_id', $id)
            ->orderBy('sort_order')
            ->findAll();

        return view('header', $header_data)
            . view('school_admin/custom_fields/form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $schoolId = $this->getSchoolId();

        $validationRule = [
            'label'    => 'required|max_length[255]',
            'field_key' => 'required|max_length[150]',
            'entity_id' => 'required|integer',
            'field_type_id' => 'required|integer',
        ];

        if (!$this->validate($validationRule)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        if ($this->FieldModel->fieldKeyExists($schoolId, $post['field_key'], $post['id'] ?? null)) {
            return redirect()->back()->withInput()->with('error', 'Field key already exists.');
        }

        $data = [
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
            'updated_at'    => date('Y-m-d H:i:s'),
            'updated_by'    => session()->get('user_id'),
        ];

        $id = $post['id'] ?? null;
        if ($id) {
            $this->FieldModel->update($id, $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = session()->get('user_id');
            $id = $this->FieldModel->insert($data);
        }

        $this->saveOptions($id, $post['options'] ?? []);

        return redirect()->to('admin/custom-fields')->with('success', 'Custom field saved successfully.');
    }

    public function update($id)
    {
        $post = $this->request->getPost();
        $post['id'] = $id;
        return $this->store();
    }

    public function delete($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('admin/custom-fields');
        }

        $response = ['status' => false, 'html' => ''];
        $deleted = $this->FieldModel->delete($id);
        $response['status'] = (bool) $deleted;
        $response['html'] = $deleted
            ? message_generator('success', 'Field deleted successfully.')
            : message_generator('error', 'Field could not be deleted.');

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function changeStatus($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('admin/custom-fields');
        }

        $status = (int) $this->request->getPost('status');
        $response = ['status' => false, 'html' => ''];

        $updated = $this->FieldModel->update($id, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => session()->get('user_id'),
        ]);

        $response['status'] = (bool) $updated;
        $response['html'] = $updated
            ? message_generator('success', 'Field status updated.')
            : message_generator('error', 'Field status could not be updated.');

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    // ===================== GROUPS =====================

    public function groups()
    {
        $schoolId = $this->getSchoolId();

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
            . view('school_admin/custom_fields/groups_list', $data)
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
            . view('school_admin/custom_fields/groups_form', $data)
            . view('footer', $footer_data);
    }

    public function editGroup($id)
    {
        $group = $this->GroupModel->find($id);
        if (!$group) {
            return redirect()->to('admin/custom-fields/groups')->with('error', 'Group not found.');
        }

        $header_data['page_title'] = lang('System.page_title_cf_group_edit') ?: 'Edit Group';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['group'] = $group;
        $data['is_edit'] = true;
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();

        return view('header', $header_data)
            . view('school_admin/custom_fields/groups_form', $data)
            . view('footer', $footer_data);
    }

    public function storeGroup()
    {
        $post = $this->request->getPost();
        $schoolId = $this->getSchoolId();

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

        return redirect()->to('admin/custom-fields/groups')->with('success', 'Group saved successfully.');
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
            return redirect()->to('admin/custom-fields/groups');
        }

        $response = ['status' => false, 'html' => ''];
        $deleted = $this->GroupModel->delete($id);
        $response['status'] = (bool) $deleted;
        $response['html'] = $deleted
            ? message_generator('success', 'Group deleted successfully.')
            : message_generator('error', 'Group could not be deleted.');

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    // ===================== OPTIONS =====================

    public function storeOption()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('admin/custom-fields');
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

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function deleteOption($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('admin/custom-fields');
        }

        $response = ['status' => false, 'html' => ''];
        $deleted = $this->OptionModel->delete($id);
        $response['status'] = (bool) $deleted;

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    // ===================== VALUES =====================

    public function saveFieldValue()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('admin/custom-fields');
        }

        $post = $this->request->getPost();
        $response = ['status' => false, 'html' => ''];

        $saved = $this->ValueModel->saveFieldValue(
            $this->getSchoolId(),
            (int) $post['field_id'],
            (int) $post['entity_id'],
            (int) $post['record_id'],
            $post['value'] ?? null
        );

        $response['status'] = (bool) $saved;

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function getRecordValues()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('admin/custom-fields');
        }

        $post = $this->request->getPost();
        $response = ['status' => true, 'data' => []];

        $values = $this->ValueModel->getRecordValues(
            $this->getSchoolId(),
            (int) $post['entity_id'],
            (int) $post['record_id']
        );

        $response['data'] = $values;

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
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
}
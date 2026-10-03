<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\CustomField\CustomFieldEntityModel;
use App\Models\CustomField\CustomFieldTypeModel;
use App\Models\CustomField\CustomFieldModel;
use App\Models\CustomField\CustomFieldGroupModel;

class CustomFields extends BaseController
{
    protected CustomFieldEntityModel $EntityModel;
    protected CustomFieldTypeModel $TypeModel;
    protected CustomFieldModel $FieldModel;
    protected CustomFieldGroupModel $GroupModel;

    public function __construct()
    {
        $this->EntityModel = new CustomFieldEntityModel();
        $this->TypeModel = new CustomFieldTypeModel();
        $this->FieldModel = new CustomFieldModel();
        $this->GroupModel = new CustomFieldGroupModel();
    }

    // ===================== ENTITIES =====================

    public function entities()
    {
        $header_data = [
            'page_title' => lang('System.page_title_cf_entities') ?: 'Field Entities',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['items'] = $this->EntityModel
            ->where('status !=', 2)
            ->orderBy('id', 'DESC')
            ->findAll();

        $data['trashed'] = $this->EntityModel
            ->where('status', 2)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('header', $header_data)
            . view('saas_admin/custom_fields/entities_list', $data)
            . view('footer', $footer_data);
    }

    public function createEntity()
    {
        $header_data['page_title'] = lang('System.page_title_cf_entity_create') ?: 'Create Entity';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['entity'] = null;
        $data['is_edit'] = false;

        return view('header', $header_data)
            . view('saas_admin/custom_fields/entities_form', $data)
            . view('footer', $footer_data);
    }

    public function editEntity($id)
    {
        $entity = $this->EntityModel->find($id);
        if (!$entity) {
            return redirect()->to('saas-admin/custom-fields/entities')->with('error', 'Entity not found.');
        }

        $header_data['page_title'] = lang('System.page_title_cf_entity_edit') ?: 'Edit Entity';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['entity'] = $entity;
        $data['is_edit'] = true;

        return view('header', $header_data)
            . view('saas_admin/custom_fields/entities_form', $data)
            . view('footer', $footer_data);
    }

    public function storeEntity($editId = null)
    {
        $post = $this->request->getPost();

        $validationRule = [
            'title' => 'required|max_length[100]',
            'slug'  => 'required|max_length[100]|is_unique[custom_field_entities.slug,id,' . ($editId ?: $post['id'] ?? '') . ']',
        ];

        if (!$this->validate($validationRule)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $data = [
            'title' => $post['title'],
            'slug'  => $post['slug'],
            'status' => (int) ($post['status'] ?? 1),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = $editId ?: ($post['id'] ?? null);
        if ($id) {
            $this->EntityModel->update($id, $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->EntityModel->insert($data);
        }

        return redirect()->to('saas-admin/custom-fields/entities')->with('success', 'Entity saved successfully.');
    }

    public function updateEntity($id)
    {
        return $this->storeEntity($id);
    }

    public function trashEntity()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/entities');
        }

        $response = ['status' => true, 'html' => ''];
        $entityId = $this->request->getPost('entity_id');

        if ($entityId) {
            $trashed = $this->EntityModel->update($entityId, ['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
            $response['status'] = (bool) $trashed;
            $response['html'] = $trashed
                ? message_generator('success', lang('Common.data_trashed'))
                : message_generator('error', lang('Common.data_error_trashed'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function restoreEntity()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/entities');
        }

        $response = ['status' => true, 'html' => ''];
        $entityId = $this->request->getPost('entity_id');

        if ($entityId) {
            $restored = $this->EntityModel->update($entityId, ['status' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            $response['status'] = (bool) $restored;
            $response['html'] = $restored
                ? message_generator('success', lang('Common.data_restored'))
                : message_generator('error', lang('Common.data_error_restored'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function emptyTrashEntity()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/entities');
        }

        $response = ['status' => true, 'html' => ''];
        $entityId = $this->request->getPost('entity_id');

        if ($entityId) {
            $deleted = $this->EntityModel->where('id', $entityId)->where('status', 2)->delete();
            $response['status'] = (bool) $deleted;
            $response['html'] = $deleted
                ? message_generator('success', lang('Common.data_empty_trashed'))
                : message_generator('error', lang('Common.data_error_empty_trashed'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_empty_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    // ===================== TYPES =====================

    public function types()
    {
        $header_data = [
            'page_title' => lang('System.page_title_cf_types') ?: 'Field Types',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['items'] = $this->TypeModel
            ->where('status !=', 2)
            ->orderBy('id', 'ASC')
            ->findAll();

        $data['trashed'] = $this->TypeModel
            ->where('status', 2)
            ->orderBy('id', 'ASC')
            ->findAll();

        return view('header', $header_data)
            . view('saas_admin/custom_fields/types_list', $data)
            . view('footer', $footer_data);
    }

    public function createType()
    {
        $header_data['page_title'] = lang('System.page_title_cf_type_create') ?: 'Create Field Type';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['type'] = null;
        $data['is_edit'] = false;

        return view('header', $header_data)
            . view('saas_admin/custom_fields/types_form', $data)
            . view('footer', $footer_data);
    }

    public function editType($id)
    {
        $type = $this->TypeModel->find($id);
        if (!$type) {
            return redirect()->to('saas-admin/custom-fields/types')->with('error', 'Field type not found.');
        }

        $header_data['page_title'] = lang('System.page_title_cf_type_edit') ?: 'Edit Field Type';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['type'] = $type;
        $data['is_edit'] = true;

        return view('header', $header_data)
            . view('saas_admin/custom_fields/types_form', $data)
            . view('footer', $footer_data);
    }

    public function storeType($editId = null)
    {
        $post = $this->request->getPost();

        $validationRule = [
            'title' => 'required|max_length[100]',
            'slug'  => 'required|max_length[100]|is_unique[custom_field_types.slug,id,' . ($editId ?: $post['id'] ?? '') . ']',
        ];

        if (!$this->validate($validationRule)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $data = [
            'title' => $post['title'],
            'slug'  => $post['slug'],
        ];

        $id = $editId ?: ($post['id'] ?? null);
        if ($id) {
            $this->TypeModel->update($id, $data);
        } else {
            $data['status'] = 1;
            $this->TypeModel->insert($data);
        }

        return redirect()->to('saas-admin/custom-fields/types')->with('success', 'Field type saved successfully.');
    }

    public function updateType($id)
    {
        return $this->storeType($id);
    }

    public function trashType()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/types');
        }

        $response = ['status' => true, 'html' => ''];
        $typeId = $this->request->getPost('type_id');

        if ($typeId) {
            $trashed = $this->TypeModel->update($typeId, ['status' => 2]);
            $response['status'] = (bool) $trashed;
            $response['html'] = $trashed
                ? message_generator('success', lang('Common.data_trashed'))
                : message_generator('error', lang('Common.data_error_trashed'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function restoreType()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/types');
        }

        $response = ['status' => true, 'html' => ''];
        $typeId = $this->request->getPost('type_id');

        if ($typeId) {
            $restored = $this->TypeModel->update($typeId, ['status' => 1]);
            $response['status'] = (bool) $restored;
            $response['html'] = $restored
                ? message_generator('success', lang('Common.data_restored'))
                : message_generator('error', lang('Common.data_error_restored'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function emptyTrashType()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/types');
        }

        $response = ['status' => true, 'html' => ''];
        $typeId = $this->request->getPost('type_id');

        if ($typeId) {
            $deleted = $this->TypeModel->where('id', $typeId)->where('status', 2)->delete();
            $response['status'] = (bool) $deleted;
            $response['html'] = $deleted
                ? message_generator('success', lang('Common.data_empty_trashed'))
                : message_generator('error', lang('Common.data_error_empty_trashed'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_empty_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    // ===================== CUSTOM FIELDS =====================

    public function fields()
    {
        $header_data = [
            'page_title' => lang('System.page_title_custom_fields') ?: 'Custom Fields',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $search   = $this->request->getGet('search');
        $schoolId = (int) ($this->request->getGet('school_id') ?? 0);

        $data['items'] = $this->FieldModel->getAllFields(false, $search, $schoolId);
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();
        $data['types'] = $this->TypeModel->findAll();
        $data['schools'] = (new \App\Models\SchoolModel())->findAll();
        $data['search'] = $search;
        $data['selected_school'] = $schoolId;

        return view('header', $header_data)
            . view('saas_admin/custom_fields/list', $data)
            . view('footer', $footer_data);
    }

    public function trashField()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields');
        }

        $response = ['status' => true, 'html' => ''];
        $fieldId = $this->request->getPost('field_id');

        if ($fieldId) {
            $trashed = $this->FieldModel->update($fieldId, ['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
            $response['status'] = (bool) $trashed;
            $response['html'] = $trashed
                ? message_generator('success', lang('Common.data_trashed'))
                : message_generator('error', lang('Common.data_error_trashed'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function restoreField()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields');
        }

        $response = ['status' => true, 'html' => ''];
        $fieldId = $this->request->getPost('field_id');

        if ($fieldId) {
            $restored = $this->FieldModel->update($fieldId, ['status' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            $response['status'] = (bool) $restored;
            $response['html'] = $restored
                ? message_generator('success', lang('Common.data_restored'))
                : message_generator('error', lang('Common.data_error_restored'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function emptyTrashField()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields');
        }

        $response = ['status' => true, 'html' => ''];
        $fieldId = $this->request->getPost('field_id');

        if ($fieldId) {
            $deleted = $this->FieldModel->where('id', $fieldId)->where('status', 2)->delete();
            $response['status'] = (bool) $deleted;
            $response['html'] = $deleted
                ? message_generator('success', lang('Common.data_empty_trashed'))
                : message_generator('error', lang('Common.data_error_empty_trashed'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_empty_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    // ===================== FIELD GROUPS =====================

    public function groups()
    {
        $header_data = [
            'page_title' => lang('System.page_title_cf_groups') ?: 'Field Groups',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['items'] = $this->GroupModel->getAllGroups();
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();
        $data['schools'] = (new \App\Models\SchoolModel())->findAll();

        return view('header', $header_data)
            . view('saas_admin/custom_fields/groups_list', $data)
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
            . view('saas_admin/custom_fields/groups_form', $data)
            . view('footer', $footer_data);
    }

    public function editGroup($id)
    {
        $group = $this->GroupModel->find($id);
        if (!$group) {
            return redirect()->to('saas-admin/custom-fields/groups')->with('error', 'Group not found.');
        }

        $header_data['page_title'] = lang('System.page_title_cf_group_edit') ?: 'Edit Group';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['group'] = $group;
        $data['is_edit'] = true;
        $data['entities'] = $this->EntityModel->where('status', 1)->findAll();

        return view('header', $header_data)
            . view('saas_admin/custom_fields/groups_form', $data)
            . view('footer', $footer_data);
    }

    public function storeGroup($editId = null)
    {
        $post = $this->request->getPost();

        $validationRule = [
            'title'     => 'required|max_length[255]',
            'entity_id' => 'required|integer',
        ];

        if (!$this->validate($validationRule)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $data = [
            'title'       => $post['title'],
            'entity_id'   => (int) $post['entity_id'],
            'description' => $post['description'] ?? null,
            'sort_order'  => (int) ($post['sort_order'] ?? 0),
            'status'      => (int) ($post['status'] ?? 1),
        ];

        $id = $editId ?: ($post['id'] ?? null);
        if ($id) {
            $this->GroupModel->update($id, $data);
        } else {
            $this->GroupModel->insert($data);
        }

        return redirect()->to('saas-admin/custom-fields/groups')->with('success', 'Group saved successfully.');
    }

    public function updateGroup($id)
    {
        return $this->storeGroup($id);
    }

    public function trashGroup()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/groups');
        }

        $response = ['status' => true, 'html' => ''];
        $groupId = $this->request->getPost('group_id');

        if ($groupId) {
            $trashed = $this->GroupModel->update($groupId, ['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
            $response['status'] = (bool) $trashed;
            $response['html'] = $trashed
                ? message_generator('success', lang('Common.data_trashed'))
                : message_generator('error', lang('Common.data_error_trashed'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function restoreGroup()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/groups');
        }

        $response = ['status' => true, 'html' => ''];
        $groupId = $this->request->getPost('group_id');

        if ($groupId) {
            $restored = $this->GroupModel->update($groupId, ['status' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            $response['status'] = (bool) $restored;
            $response['html'] = $restored
                ? message_generator('success', lang('Common.data_restored'))
                : message_generator('error', lang('Common.data_error_restored'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    public function emptyTrashGroup()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('saas-admin/custom-fields/groups');
        }

        $response = ['status' => true, 'html' => ''];
        $groupId = $this->request->getPost('group_id');

        if ($groupId) {
            $deleted = $this->GroupModel->where('id', $groupId)->where('status', 2)->delete();
            $response['status'] = (bool) $deleted;
            $response['html'] = $deleted
                ? message_generator('success', lang('Common.data_empty_trashed'))
                : message_generator('error', lang('Common.data_error_empty_trashed'));
        } else {
            $response['status'] = false;
            $response['html'] = message_generator('error', lang('Common.data_error_id_missing') . '<br>' . lang('Common.data_error_empty_trashed'));
        }

        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}
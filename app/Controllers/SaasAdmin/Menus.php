<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\EdumMenuModel;
use App\Models\MenuRoleModel;
use App\Models\RoleModel;
use App\Models\ModuleModel;

class Menus extends BaseController
{
    protected EdumMenuModel $menuModel;
    protected MenuRoleModel $menuRoleModel;
    protected RoleModel $roleModel;
    protected ModuleModel $moduleModel;

    public function __construct()
    {
        $this->menuModel     = new EdumMenuModel();
        $this->menuRoleModel = new MenuRoleModel();
        $this->roleModel     = new RoleModel();
        $this->moduleModel   = new ModuleModel();
    }

    private function getFilteredRedirectUrl(string $url = 'saas-admin/menus'): string
    {
        $roleId = $this->request->getGet('role_id') ?: $this->request->getPost('role_id');
        return $roleId ? $url . '?role_id=' . (int) $roleId : $url;
    }

    public function index()
    {
        $header_data = [
            'page_title' => 'Menu Management',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $filterRoleId = $this->request->getGet('role_id') ? (int) $this->request->getGet('role_id') : null;

        $query = $this->menuModel->where('status !=', 2)->orderBy('menu_order', 'ASC')->orderBy('id', 'ASC');

        if ($filterRoleId) {
            $menuIds = array_map(fn($r) => (int) $r->menu_id, $this->menuRoleModel->where('role_id', $filterRoleId)->findAll());
            if (!empty($menuIds)) {
                $query->whereIn('id', $menuIds);
            } else {
                $query->where('id', -1);
            }
        }

        $data['flatMenus']    = $query->findAll();
        $data['menus']        = $this->menuModel->buildTree($data['flatMenus']);
        $data['allRoles']     = $this->roleModel->where('status', 1)->orderBy('name', 'ASC')->findAll();
        $data['filterRoleId'] = $filterRoleId;

        return view('header', $header_data)
            . view('saas_admin/menus/list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        $header_data = [
            'page_title' => 'Create Menu',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['menu']            = null;
        $data['is_edit']         = false;
        $data['parentMenus']     = $this->menuModel->where('status !=', 2)->orderBy('menu_order', 'ASC')->findAll();
        $data['modules']         = $this->moduleModel->where('status', 1)->findAll();
        $data['allRoles']        = $this->roleModel->where('status', 1)->orderBy('name', 'ASC')->findAll();
        $data['assignedRoleIds'] = [];
        $data['filterRoleId']    = $this->request->getGet('role_id') ? (int) $this->request->getGet('role_id') : null;

        return view('header', $header_data)
            . view('saas_admin/menus/form', $data)
            . view('footer', $footer_data);
    }

    public function edit($id)
    {
        $menu = $this->menuModel->find($id);
        if (!$menu) {
            return redirect()->to($this->getFilteredRedirectUrl())->with('error', 'Menu not found.');
        }

        $header_data = [
            'page_title' => 'Edit Menu',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['menu']             = $menu;
        $data['is_edit']          = true;
        $data['parentMenus']      = $this->menuModel->where('status !=', 2)->orderBy('menu_order', 'ASC')->findAll();
        $data['modules']          = $this->moduleModel->where('status', 1)->findAll();
        $data['allRoles']         = $this->roleModel->where('status', 1)->orderBy('name', 'ASC')->findAll();
        $data['assignedRoleIds']  = array_map(fn($r) => (int) $r->role_id, $this->menuRoleModel->getRolesByMenuId($id));
        $data['filterRoleId']     = $this->request->getGet('role_id') ? (int) $this->request->getGet('role_id') : null;

        return view('header', $header_data)
            . view('saas_admin/menus/form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        $rules = [
            'title'      => 'required|max_length[150]',
            'slug'       => 'required|max_length[150]|alpha_dash',
            'route'      => 'required|max_length[255]',
            'menu_order' => 'permit_empty|integer',
            'status'     => 'permit_empty|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $menuData = [
            'module_id'   => $this->request->getPost('module_id') ?: null,
            'parent_id'   => $this->request->getPost('parent_id') ?: null,
            'title'       => trim((string) $this->request->getPost('title')),
            'slug'        => trim((string) $this->request->getPost('slug')),
            'route'       => trim((string) $this->request->getPost('route')),
            'icon'        => trim((string) $this->request->getPost('icon')),
            'menu_order'  => (int) $this->request->getPost('menu_order'),
            'status'      => (int) $this->request->getPost('status'),
        ];

        $menuId = $this->menuModel->insert($menuData);

        if ($menuId) {
            $roleIds = $this->request->getPost('role_ids');
            $roleIds = is_array($roleIds) ? array_map('intval', $roleIds) : [];
            $this->menuRoleModel->syncRoles($menuId, $roleIds);

            return redirect()->to($this->getFilteredRedirectUrl())->with('success', 'Menu created successfully.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to create menu.');
    }

    public function update($id)
    {
        $menu = $this->menuModel->find($id);
        if (!$menu) {
            return redirect()->to($this->getFilteredRedirectUrl())->with('error', 'Menu not found.');
        }

        $rules = [
            'title'      => 'required|max_length[150]',
            'slug'       => 'required|max_length[150]|alpha_dash',
            'route'      => 'required|max_length[255]',
            'menu_order' => 'permit_empty|integer',
            'status'     => 'permit_empty|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', $this->validator->listErrors());
        }

        $menuData = [
            'module_id'   => $this->request->getPost('module_id') ?: null,
            'parent_id'   => $this->request->getPost('parent_id') ?: null,
            'title'       => trim((string) $this->request->getPost('title')),
            'slug'        => trim((string) $this->request->getPost('slug')),
            'route'       => trim((string) $this->request->getPost('route')),
            'icon'        => trim((string) $this->request->getPost('icon')),
            'menu_order'  => (int) $this->request->getPost('menu_order'),
            'status'      => (int) $this->request->getPost('status'),
        ];

        if ($this->menuModel->update($id, $menuData)) {
            $roleIds = $this->request->getPost('role_ids');
            $roleIds = is_array($roleIds) ? array_map('intval', $roleIds) : [];
            $this->menuRoleModel->syncRoles($id, $roleIds);

            return redirect()->to($this->getFilteredRedirectUrl())->with('success', 'Menu updated successfully.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to update menu.');
    }

    public function trash()
    {
        if ($this->request->getPost('menu_id')) {
            $response = ['status' => true];
            $html     = '';

            $menuId = (int) $this->request->getPost('menu_id');

            if ($menuId) {
                if ($this->menuModel->update($menuId, ['status' => 2])) {
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_trashed'));
                } else {
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_trashed'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Common.data_error_trashed');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON($response);
        }
    }

    public function empty_trash()
    {
        if ($this->request->getPost('menu_id')) {
            $response = ['status' => true];
            $html     = '';

            $menuId = (int) $this->request->getPost('menu_id');

            if ($menuId) {
                $this->menuRoleModel->where('menu_id', $menuId)->delete();
                $this->menuModel->where('parent_id', $menuId)->update(['parent_id' => null]);

                if ($this->menuModel->delete($menuId)) {
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_empty_trashed'));
                } else {
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_empty_trashed'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Common.data_error_deleted');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON($response);
        }
    }

    public function restore()
    {
        if ($this->request->getPost('menu_id')) {
            $response = ['status' => true];
            $html     = '';

            $menuId = (int) $this->request->getPost('menu_id');

            if ($menuId) {
                if ($this->menuModel->update($menuId, ['status' => 1])) {
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_restored'));
                } else {
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_restored'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Common.data_error_deleted');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON($response);
        }
    }

    public function updateOrder()
    {
        $menuId = $this->request->getPost('menu_id');
        $menuOrder = $this->request->getPost('menu_order');

        $menuId = (int) $menuId;
        $menuOrder = (int) $menuOrder;

        $menu = $this->menuModel->find($menuId);
        if (!$menu) {
            return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON(['status' => false, 'message' => 'Menu not found.']);
        }

        if ($this->menuModel->update($menuId, ['menu_order' => $menuOrder])) {
            return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON(['status' => true, 'message' => 'Order updated successfully.']);
        }

        return $this->response->setHeader('X-CSRF-TOKEN', csrf_hash())->setJSON(['status' => false, 'message' => 'Failed to update order.']);
    }

    public function changeStatus()
    {
        $id     = (int) $this->request->getPost('menu_id');
        $status = (int) $this->request->getPost('status');

        if (!$id) {
            return redirect()->back()->with('error', 'Menu ID is missing.');
        }

        if (!in_array($status, [0, 1], true)) {
            return redirect()->back()->with('error', 'Invalid status value.');
        }

        if ($this->menuModel->update($id, ['status' => $status])) {
            $message = $status === 1 ? 'Menu activated.' : 'Menu deactivated.';
            return redirect()->to($this->getFilteredRedirectUrl())->with('success', $message);
        }

        return redirect()->back()->with('error', 'Failed to update menu status.');
    }

    public function roles($menuId)
    {
        $menu = $this->menuModel->find($menuId);
        if (!$menu) {
            return redirect()->to($this->getFilteredRedirectUrl())->with('error', 'Menu not found.');
        }

        $header_data = [
            'page_title' => 'Assign Roles - ' . esc($menu->title),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $assignedRoleIds = array_map(fn($r) => (int) $r->role_id, $this->menuRoleModel->getRolesByMenuId($menuId));
        $allRoles        = $this->roleModel->where('status', 1)->orderBy('name', 'ASC')->findAll();

        $data['menu']           = $menu;
        $data['allRoles']       = $allRoles;
        $data['assignedRoleIds'] = $assignedRoleIds;

        return view('header', $header_data)
            . view('saas_admin/menus/roles', $data)
            . view('footer', $footer_data);
    }

    public function syncRoles($menuId)
    {
        $menu = $this->menuModel->find($menuId);
        if (!$menu) {
            return redirect()->to($this->getFilteredRedirectUrl())->with('error', 'Menu not found.');
        }

        $roleIds = $this->request->getPost('role_ids');
        $roleIds = is_array($roleIds) ? array_map('intval', $roleIds) : [];

        $this->menuRoleModel->syncRoles($menuId, $roleIds);

        return redirect()->to('saas-admin/menus/roles/' . $menuId)->with('success', 'Roles updated successfully.');
    }
}
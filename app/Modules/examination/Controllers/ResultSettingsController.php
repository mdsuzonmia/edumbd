<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;

class ResultSettingsController extends BaseController
{
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        $this->SchoolModel = new SchoolModel();
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

    public function index()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Result Settings',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $data['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view('examination/settings/index', $data)
            . view('footer', $footer_data);
    }

    public function save()
    {
        if (!$this->getUserId()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Unauthenticated.']);
        }

        $school_id = (int) $this->request->getPost('school_id');
        $settings = $this->request->getPost('settings');

        if (!$school_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'School is required.']);
        }

        $school = $this->SchoolModel->find($school_id);
        if (!$school) {
            return $this->jsonResponse(['status' => false, 'message' => 'School not found.']);
        }

        $params = json_decode($school->params ?? '{}', true);
        $params = is_array($params) ? $params : [];
        
        foreach ($settings as $key => $value) {
            $params[$key] = $value;
        }

        $this->SchoolModel->update($school_id, [
            'params' => json_encode($params),
        ]);

        return $this->jsonResponse([
            'status' => true,
            'message' => 'Settings saved successfully.',
        ]);
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }
}
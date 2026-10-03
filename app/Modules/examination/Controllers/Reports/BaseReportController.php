<?php

namespace App\Modules\examination\Controllers\Reports;

use App\Modules\examination\Controllers\BaseController;
use App\Models\SchoolModel;

class BaseReportController extends BaseController
{
    protected SchoolModel $SchoolModel;

    public function __construct()
    {
        parent::__construct();
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

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    protected function renderWithHeaderFooter(string $pageTitle, string $viewPath, array $viewData = []): string
    {
        $header_data = [
            'page_title' => $pageTitle,
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $viewData['school_list'] = $this->getSchoolDropdown();

        return view('header', $header_data)
            . view($viewPath, $viewData)
            . view('footer', $footer_data);
    }
}
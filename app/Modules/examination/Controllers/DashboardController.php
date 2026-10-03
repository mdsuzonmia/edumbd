<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Models\AcademicsClassesModel;
use App\Modules\examination\Models\ExamModel;
use App\Modules\examination\Models\ExamResultModel;
use App\Modules\examination\Models\MarkModel;
use App\Modules\examination\Models\SubjectModel;
use App\Modules\examination\Models\FinalResultModel;

class DashboardController extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected StudentModel $StudentModel;
    protected AcademicsClassesModel $ClassModel;
    protected ExamModel $ExamModel;
    protected ExamResultModel $ExamResultModel;
    protected MarkModel $MarkModel;
    protected SubjectModel $SubjectModel;
    protected FinalResultModel $FinalResultModel;

    public function __construct()
    {
        $this->SchoolModel          = new SchoolModel();
        $this->StudentModel         = new StudentModel();
        $this->ClassModel           = new AcademicsClassesModel();
        $this->ExamModel            = new ExamModel();
        $this->ExamResultModel      = new ExamResultModel();
        $this->MarkModel            = new MarkModel();
        $this->SubjectModel         = new SubjectModel();
        $this->FinalResultModel     = new FinalResultModel();
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

    public function index()
    {
        $user_id = $this->getUserId();
        
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data = [
            'page_title' => 'Examination Dashboard',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];
        $footer_data['admin_area'] = 'yes';

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);

        // Get statistics
        $data['total_schools'] = count($userSchools);
        $data['total_exams'] = 0;
        $data['total_results'] = 0;
        $data['pending_results'] = 0;
        $data['total_subjects'] = 0;
        $data['total_students'] = 0;
        $data['total_classes'] = 0;
        $data['total_aggregates'] = 0;

        if (!empty($schoolIds)) {
            // Count exams
            $data['total_exams'] = $this->ExamModel
                ->whereIn('school_id', $schoolIds)
                ->where('status', 1)
                ->countAllResults();

            // Count results
            $data['total_results'] = $this->ExamResultModel
                ->whereIn('school_id', $schoolIds)
                ->countAllResults();

            // Count pending results (not published)
            $this->ExamResultModel->whereIn('school_id', $schoolIds);
            $this->ExamResultModel->where('published_at IS NULL');
            $data['pending_results'] = $this->ExamResultModel->countAllResults();

            // Count subjects
            $data['total_subjects'] = $this->SubjectModel
                ->whereIn('school_id', $schoolIds)
                ->where('status', 1)
                ->countAllResults();

            // Count students
            $data['total_students'] = $this->StudentModel
                ->whereIn('school_id', $schoolIds)
                ->where('status', 1)
                ->countAllResults();

            // Count classes
            $data['total_classes'] = $this->ClassModel
                ->whereIn('school_id', $schoolIds)
                ->where('status', 1)
                ->countAllResults();

            // Count aggregates
            $data['total_aggregates'] = $this->FinalResultModel
                ->whereIn('school_id', $schoolIds)
                ->where('is_generated', 1)
                ->countAllResults();
        }

        return view('header', $header_data)
            . view('App\Modules\examination\Views\dashboard', $data)
            . view('footer', $footer_data);
    }
}
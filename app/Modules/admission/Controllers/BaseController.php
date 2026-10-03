<?php

namespace App\Modules\admission\Controllers;

use App\Controllers\BaseController as AppBaseController;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsShiftModel;
use App\Models\AcademicsYearModel;
use App\Models\SchoolModel;
use App\Modules\admission\Models\AdmissionApplicationModel;
use App\Modules\admission\Models\AdmissionCircularModel;
use App\Modules\admission\Models\AdmissionLotteryModel;
use App\Modules\admission\Models\AdmissionSessionModel;

abstract class BaseController extends AppBaseController
{
    protected $db;
    protected SchoolModel $schools;
    protected AdmissionSessionModel $sessions;
    protected AdmissionCircularModel $circulars;
    protected AdmissionApplicationModel $applications;
    protected AdmissionLotteryModel $lotteries;

    public function __construct()
    {
        helper(['form','url','system']);
        $this->db = db_connect();
        $this->schools = new SchoolModel();
        $this->sessions = new AdmissionSessionModel();
        $this->circulars = new AdmissionCircularModel();
        $this->applications = new AdmissionApplicationModel();
        $this->lotteries = new AdmissionLotteryModel();
    }

    protected function userId(): int { return (int)session('user_id'); }

    protected function userSchools(): array
    {
        return $this->schools->select('schools.id,schools.name,schools.slug,schools.logo')
            ->join('school_user_relation','school_user_relation.school_id=schools.id','inner')
            ->where('school_user_relation.user_id',$this->userId())->where('schools.status',1)
            ->orderBy('schools.name','ASC')->findAll();
    }

    protected function schoolIds(): array { return array_map(static fn($s)=>(int)$s->id,$this->userSchools()); }
    protected function ownsSchool(int $id): bool { return in_array($id,$this->schoolIds(),true); }

    protected function findOwned($model, string $token): ?object
    {
        $ids=$this->schoolIds();
        return $ids === [] ? null : $model->where('token',$token)->whereIn('school_id',$ids)->first();
    }

    protected function options(string $modelClass, int $schoolId): array
    {
        return (new $modelClass())->where('school_id',$schoolId)->where('status',1)->orderBy('title','ASC')->findAll();
    }

    protected function academicData(int $schoolId): array
    {
        return [
            'years'=>$this->options(AcademicsYearModel::class,$schoolId),
            'classes'=>$this->options(AcademicsClassesModel::class,$schoolId),
            'sections'=>$this->options(AcademicsSectionModel::class,$schoolId),
            'shifts'=>$this->options(AcademicsShiftModel::class,$schoolId),
        ];
    }

    protected function render(string $view, array $data = [], string $title = 'Admission')
    {
        return view('header',['page_title'=>$title,'body_class'=>'nav-md','admin_area'=>'yes'])
            .view('App\\Modules\\admission\\Views\\'.$view,$data)
            .view('footer',['admin_area'=>'yes']);
    }
}

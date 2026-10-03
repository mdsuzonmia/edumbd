<?php
namespace App\Modules\admission\Models;
class AdmissionSessionModel extends BaseAdmissionModel
{
    protected $table = 'admission_sessions';
    protected $allowedFields = ['token','school_id','school_owner_uid','title','academic_year_id','application_start','application_end','admission_start','admission_end','status','created_by','updated_by'];
}

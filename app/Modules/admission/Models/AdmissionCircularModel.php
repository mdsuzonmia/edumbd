<?php
namespace App\Modules\admission\Models;
class AdmissionCircularModel extends BaseAdmissionModel
{
    protected $table = 'admission_circulars';
    protected $allowedFields = ['token','school_id','admission_session_id','title','description','class_id','shift_id','version_id','department_id','group_id','available_seats','minimum_age','maximum_age','application_start','application_deadline','application_fee','admission_fee','lottery_required','instructions','required_documents','status','created_by','updated_by'];
}

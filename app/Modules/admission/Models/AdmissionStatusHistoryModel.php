<?php
namespace App\Modules\admission\Models;
class AdmissionStatusHistoryModel extends BaseAdmissionModel
{
    protected $table = 'admission_status_history';
    protected $updatedField = '';
    protected $allowedFields = ['school_id','application_id','from_status','to_status','note','created_by'];
}

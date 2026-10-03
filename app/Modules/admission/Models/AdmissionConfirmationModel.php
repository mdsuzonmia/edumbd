<?php
namespace App\Modules\admission\Models;
class AdmissionConfirmationModel extends BaseAdmissionModel
{
    protected $table = 'admission_confirmations';
    protected $allowedFields = ['school_id','application_id','student_id','enrollment_id','admission_no','roll_no','confirmed_by','confirmed_at','notes'];
}

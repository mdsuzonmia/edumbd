<?php
namespace App\Modules\admission\Models;
class AdmissionApplicationModel extends BaseAdmissionModel
{
    public const STATUSES = ['draft','submitted','payment_pending','under_review','correction_required','eligible','ineligible','selected','waiting','not_selected','admission_pending','admitted','rejected','cancelled'];
    protected $table = 'admission_applications';
    protected $allowedFields = ['school_id','admission_session_id','circular_id','application_no','token','academic_year_id','class_id','section_id','shift_id','version_id','department_id','group_id','student_name','student_name_bn','dob','gender','birth_registration_no','nationality','religion','blood_group','photo','father_name','father_occupation','father_mobile','father_email','father_nid','mother_name','mother_occupation','mother_mobile','mother_email','mother_nid','guardian_name','guardian_mobile','guardian_relation','present_address','permanent_address','previous_school','previous_class','previous_roll','previous_result','board','passing_year','application_status','payment_status','application_fee_status','admission_fee_status','correction_message','submitted_at','reviewed_at','admitted_at','student_id','created_by','updated_by'];
}

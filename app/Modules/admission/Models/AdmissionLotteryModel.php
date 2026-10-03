<?php
namespace App\Modules\admission\Models;
class AdmissionLotteryModel extends BaseAdmissionModel
{
    protected $table = 'admission_lotteries';
    protected $allowedFields = ['token','school_id','admission_session_id','circular_id','name','academic_year_id','class_id','shift_id','version_id','seat_count','waiting_count','candidate_count','revision','status','algorithm_version','candidate_hash','result_hash','locked_at','drawn_at','finalized_at','cancelled_at','cancelled_by','cancellation_reason','created_by','updated_by'];
}

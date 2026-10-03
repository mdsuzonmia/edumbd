<?php
namespace App\Modules\admission\Models;
class AdmissionLotteryResultModel extends BaseAdmissionModel
{
    protected $table = 'admission_lottery_results';
    protected $updatedField = '';
    protected $allowedFields = ['school_id','lottery_id','application_id','draw_position','result','waiting_position'];
}

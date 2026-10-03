<?php
namespace App\Modules\admission\Models;
class AdmissionLotteryCandidateModel extends BaseAdmissionModel
{
    protected $table = 'admission_lottery_candidates';
    protected $updatedField = '';
    protected $allowedFields = ['school_id','lottery_id','application_id','application_no','snapshot_hash'];
}

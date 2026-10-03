<?php
namespace App\Modules\admission\Models;
class AdmissionTestAdmitCardModel extends BaseAdmissionModel { protected $table='admission_test_admit_cards'; protected $allowedFields=['token','school_id','test_id','application_id','seat_allocation_id','card_no','status','generated_at','generated_by']; }

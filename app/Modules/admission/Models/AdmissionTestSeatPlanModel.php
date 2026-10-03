<?php
namespace App\Modules\admission\Models;
class AdmissionTestSeatPlanModel extends BaseAdmissionModel { protected $table='admission_test_seat_plans'; protected $allowedFields=['token','school_id','test_id','title','allocation_method','seat_number_format','random_seed','candidate_count','status','is_locked','locked_at','created_by','updated_by']; }

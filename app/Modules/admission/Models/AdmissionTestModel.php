<?php
namespace App\Modules\admission\Models;
class AdmissionTestModel extends BaseAdmissionModel { protected $table='admission_tests'; protected $allowedFields=['token','school_id','circular_id','title','test_date','start_time','end_time','reporting_time','total_marks','pass_marks','instructions','candidate_statuses','status','created_by','updated_by']; }

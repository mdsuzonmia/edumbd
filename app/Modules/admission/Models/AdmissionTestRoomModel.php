<?php
namespace App\Modules\admission\Models;
class AdmissionTestRoomModel extends BaseAdmissionModel { protected $table='admission_test_rooms'; protected $allowedFields=['token','school_id','room_name','room_no','building','floor','capacity','rows_count','columns_count','status','created_by','updated_by']; }

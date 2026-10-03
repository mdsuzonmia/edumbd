<?php
namespace App\Modules\admission\Models;
class AdmissionFormFieldModel extends BaseAdmissionModel { protected $table='admission_form_fields'; protected $allowedFields=['school_id','circular_id','field_key','label','field_type','options_json','validation_rules','placeholder','is_required','sort_order','status']; }

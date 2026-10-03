<?php
namespace App\Models;
use CodeIgniter\Model;
class ServiceBillingSettingModel extends Model { protected $table='service_billing_settings'; protected $returnType='object'; protected $useTimestamps=true; protected $allowedFields=['setting_key','setting_value']; public function value(string $key,$default=null){$r=$this->where('setting_key',$key)->first();return $r?$r->setting_value:$default;} }

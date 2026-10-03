<?php
namespace App\Models;
use CodeIgniter\Model;
class ServicePricingRuleModel extends Model { protected $table='service_pricing_rules'; protected $returnType='object'; protected $useTimestamps=true; protected $allowedFields=['service','service_mode','pricing_type','min_students','max_students','amount','sort_order','status']; }

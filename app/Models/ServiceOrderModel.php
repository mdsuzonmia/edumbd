<?php
namespace App\Models;
use CodeIgniter\Model;
class ServiceOrderModel extends Model { protected $table='service_orders'; protected $returnType='object'; protected $useTimestamps=true; protected $allowedFields=['token','invoice_no','school_id','user_id','service','service_mode','reference_type','reference_id','exam_id','student_count','pricing_type','applied_rate','subtotal','discount','total','currency','status','pricing_snapshot','payment_method','transaction_id','payment_note','paid_at','completed_at','approved_by']; }

<?php
namespace App\Modules\admission\Models;
class AdmissionPaymentModel extends BaseAdmissionModel { protected $table='admission_payments'; protected $allowedFields=['token','school_id','application_id','payment_type','amount','currency','gateway','transaction_id','payer_reference','proof_file','status','paid_at','reviewed_by','reviewed_at','rejection_reason','metadata']; }

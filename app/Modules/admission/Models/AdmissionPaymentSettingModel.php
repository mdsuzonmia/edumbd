<?php

namespace App\Modules\admission\Models;

class AdmissionPaymentSettingModel extends BaseAdmissionModel
{
    protected $table='admission_payment_settings';
    protected $allowedFields=['school_id','currency','manual_enabled','manual_instructions','stripe_enabled','stripe_publishable_key','stripe_secret_key','paypal_enabled','paypal_client_id','paypal_client_secret','paypal_sandbox','created_by','updated_by'];
}

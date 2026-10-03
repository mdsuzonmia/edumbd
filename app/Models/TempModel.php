<?php

namespace App\Models;

use CodeIgniter\Model;

class TempModel extends Model
{
    protected $table      = 'temp_registrations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'temp_token', 'is_trial','school_name', 'school_email', 'country', 'phone', 'admin_name', 'admin_email', 'password_hash', 'plan_id',  'billing_cycle', 'billing_price', 'currency', 
        'payment_gateway',
        'payment_status',
        'transaction_id',
        'note',
        'is_processed',
        'processed_at'
    ];
    protected $useTimestamps = true;

    // Set the return type as an object
    protected $returnType = 'object';

    
}

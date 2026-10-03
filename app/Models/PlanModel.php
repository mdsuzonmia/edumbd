<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table      = 'subscription_plans';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'name',
        'slug',
        'price',
        'prices',
        'monthly_price',
        'yearly_price',
        'lifetime_price',
        'currency',
        'trial_days',
        'student_limit',
        'teachers_limit',
        'branch_limit',
        'admin_limit',
        'sms_limit',
        'storage_limit_mb',
        'custom_domain',
        'mobile_app_access',
        'api_access',
        'features',
        'description',
        'is_popular',
        'is_featured',
        'sort_order',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';

    // Find plan by ID
    public function findPlanById($id)
    {
       return $this->where('id', $id)->first();
    }

    

    
}
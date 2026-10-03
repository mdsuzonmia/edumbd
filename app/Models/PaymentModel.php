<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table      = 'payments';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'payment_token',
        'payment_type',
        'subscription_id',
        'user_id',
        'plan_id',
        'gateway',
        'payment_gateway',
        'billing_cycle',
        'amount',
        'currency',
        'transaction_id',
        'gateway_payment_id',
        'status',
        'payment_payload',
        'paid_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';

    public function getPaymentDetails($id)
    {
        return $this->select([
                'payments.*',
                'users.name AS user_name',
                'users.email AS user_email',
                'subscription_plans.name AS plan_name',
            ])
            
            ->join('users', 'users.id = payments.user_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = payments.plan_id', 'left')
            ->where('payments.id', $id)
            ->first();
    }

    // Get payment details by payment_token
    public function getPaymentDetailsByToken($payment_token)
    {
        return $this->select([
                'payments.*',
                'users.name AS user_name',
                'users.email AS user_email',
                'subscription_plans.name AS plan_name',
            ])
            
            ->join('users', 'users.id = payments.user_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = payments.plan_id', 'left')
            ->where('payments.payment_token', $payment_token)
            ->first();
    }

    public function getPayments()
    {
        return $this->select([
                'payments.*',
                'subscription_plans.name AS plan_name',
            ])
            ->join('subscription_plans', 'subscription_plans.id = payments.plan_id', 'left')
            ->where('payments.user_id', session()->get('user_id'))
            ->orderBy('payments.created_at', 'DESC')
            ->findAll();
    }

    // Get payments by subscription ID
    public function getPaymentsBySubscription($subscription_id)
    {
        return $this->select([
                'payments.*',
                'users.name AS user_name',
                'users.email AS user_email',
                'subscription_plans.name AS plan_name',
            ])
            ->join('users', 'users.id = payments.user_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = payments.plan_id', 'left')
            ->where('payments.subscription_id', $subscription_id)
            ->orderBy('payments.created_at', 'DESC')
            ->findAll();
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table      = 'subscriptions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'token',
        'user_id',
        'plan_id',
        'status',
        'is_trial',
        'trial_start',
        'trial_end',
        'trial_used',
        'start_date',
        'end_date',
        'amount',
        'currency',
        'billing_cycle',
        'payment_gateway',
        'gateway_subscription_id',
        'gateway_customer_id',
        'last_payment_at',
        'next_billing_at',
        'meta',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';

    public function findSubscriptionById($id)
    {
        return $this->where('id', $id)->first();
    }

    // findByToken
    public function findByToken($token)
    {
        return $this->where('token', $token)->first();
    }

    // getActiveSubscriptionByUserId
    public function getActiveSubscriptionByUserId($user_id)
    {
        
        return $this->where('user_id', $user_id)
                    ->whereIn('status', [1, 2]) // 0=Pending, 1=Trial, 2=Active, 3=suspended, 4=Expired, 5=Cancelled, 6=trashed
                    ->orderBy('created_at', 'DESC')
                    ->first();
    }

    public function getActiveSubscriptionWithPlanByUserId($user_id)
    {
        return $this->select([
                'subscriptions.*',
                'subscription_plans.name AS plan_name',
                'subscription_plans.slug AS plan_slug',
                'subscription_plans.currency AS plan_currency',
            ])
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.user_id', $user_id)
            ->whereIn('subscriptions.status', [1, 2])
            ->orderBy('subscriptions.id', 'DESC')
            ->first();
    }

    public function getSubscriptionHistoryByUserId($user_id)
    {
        return $this->select([
                'subscriptions.*',
                'subscription_plans.name AS plan_name',
                'subscription_plans.slug AS plan_slug',
            ])
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.user_id', $user_id)
            ->orderBy('subscriptions.id', 'DESC')
            ->findAll();
    }

    public function getPendingSubscriptionWithPlanByUserId($user_id)
    {
        return $this->select([
                'subscriptions.*',
                'subscription_plans.name AS plan_name',
                'subscription_plans.slug AS plan_slug',
                'subscription_plans.currency AS plan_currency',
            ])
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.user_id', $user_id)
            ->where('subscriptions.status', 0)
            ->orderBy('subscriptions.id', 'DESC')
            ->first();
    }

    public function getSubscriptionDetails($id)
    {
        return $this->select([
                'subscriptions.*',
                'users.name AS user_name',
                'users.email AS user_email',
                'subscription_plans.name AS plan_name',
                'subscription_plans.slug AS plan_slug',
            ])
            ->join('users', 'users.id = subscriptions.user_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.id', $id)
            ->first();
    }

    // getPaymentsBySubscription
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

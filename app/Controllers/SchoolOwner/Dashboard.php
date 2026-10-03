<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use Config\Database;
use App\Models\SubscriptionModel;
use App\Models\PlanModel;

class Dashboard extends BaseController
{
    protected $db;
    protected SubscriptionModel $subscriptionModel;
    protected PlanModel $planModel;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
        $this->planModel = new PlanModel();
    }





   

    public function index()
    {
        if (session('role') !== 'school-owner') {
            return redirect()->to('/login');
        }

        $user_id = session('user_id') ? (int) session('user_id') : null;
        $plan_id = session('plan_id') ? (int) session('plan_id') : null;

        // Get Plan Details by Plan ID
        $plan = $plan_id ? $this->planModel->findPlanById($plan_id) : null;

        // Get Active Subscription by User ID
        $subscription = $this->subscriptionModel->getActiveSubscriptionByUserId($user_id);

        // Prepare data for view
        $data = [
            'subscription' => $subscription,
            'plan' => $plan,
            'subscription_data' => calculateSubscriptionPercent() ?: [
                'percent' => 0,
                'total_day' => 0,
                'used_days' => 0,
                'remaining_days' => 0,
                'expiry_date' => '-',
                'is_expired' => true,
                'is_expiring_soon' => false
            ],
            'pendingUpgrade' => $this->subscriptionModel->getPendingSubscriptionWithPlanByUserId($user_id),
        ];

        $header_data['page_title'] = lang('OwnerDashboard.page_title');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('school_owner/dashboard', $data)
            . view('footer', $footer_data);
    }
}

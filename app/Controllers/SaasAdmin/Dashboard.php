<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\SaasAdmin\DashboardModel;

class Dashboard extends BaseController
{
    protected DashboardModel $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new DashboardModel();
    }

    public function index()
    {
        // Security: Only super admin
        if (session('role') !== 'super-admin') {
            return redirect()->to('/login');
        }

        $subscriptionCounts = $this->dashboardModel->getSubscriptionStatusCounts();

        $data = [
            'totalSchools'        => $this->dashboardModel->getTotalSchools(),
            'newSchools'          => $this->dashboardModel->getNewSchoolsThisMonth(),
            'subscriptionCounts'  => $subscriptionCounts,
            'activeSubscriptions' => $subscriptionCounts[2] ?? 0,
            'trialSubscriptions'  => $subscriptionCounts[1] ?? 0,
            'expiringSoon'        => $this->dashboardModel->getExpiringSubscriptions(),
            'pendingPayments'     => $this->dashboardModel->getPendingPayments(),
            'revenue'             => $this->dashboardModel->getRevenueSummary(),
            'revenueTrend'        => $this->dashboardModel->getRevenueTrend(),
            'recentSchools'       => $this->dashboardModel->getRecentSchools(),
            'recentSubscriptions' => $this->dashboardModel->getRecentSubscriptions(),
        ];

        $header_data['page_title'] = lang('Auth.page_title_saas_dashboard');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $header_data['page_styles'] = ['assets/css/saas-dashboard.css'];
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('saas_admin/dashboard', $data)
            . view('footer', $footer_data);

    }
}
